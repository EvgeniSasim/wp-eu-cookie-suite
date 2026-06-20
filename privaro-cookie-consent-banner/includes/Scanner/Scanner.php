<?php
/**
 * Scanner class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handles website scanning for cookies and scripts.
 */
final class Scanner {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpeu_cs_get_scan_urls', array( $this, 'ajax_get_scan_urls' ) );
		add_action( 'wp_ajax_wpeu_cs_scan_url', array( $this, 'ajax_scan_url' ) );
		add_action( 'wp_ajax_wpeu_cs_import_scan', array( $this, 'ajax_import_scan' ) );
	}

	/**
	 * AJAX handler to get URLs to scan.
	 */
	public function ajax_get_scan_urls(): void {
		check_ajax_referer( 'wpeu-cs-scanner', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'privaro-cookie-consent-banner' ) ) );
		}

		$last_scan = get_option( 'wpeu_cs_last_scan_time', 0 );
		if ( time() - $last_scan < MINUTE_IN_SECONDS ) {
			wp_send_json_error( array( 'message' => __( 'Please wait at least one minute between scans.', 'privaro-cookie-consent-banner' ) ) );
		}

		update_option( 'wpeu_cs_last_scan_time', time() );
		delete_option( 'wpeu_cs_scan_results' );

		$urls = $this->get_urls_to_scan();

		wp_send_json_success( array( 'urls' => $urls ) );
	}

	/**
	 * Get URLs to scan.
	 *
	 * @return string[]
	 */
	private function get_urls_to_scan(): array {
		$urls = array( home_url( '/' ) );

		// Try to get URLs from WordPress Sitemaps (WP 5.5+)
		if ( function_exists( 'wp_sitemaps_get_server' ) ) {
			$sitemaps = wp_sitemaps_get_server();
			$index    = $sitemaps->index->get_sitemap_list();

			foreach ( $index as $sitemap ) {
				$response = wp_remote_get( $sitemap['loc'] );
				if ( is_wp_error( $response ) ) {
					continue;
				}

				$body = wp_remote_retrieve_body( $response );
				if ( preg_match_all( '/<loc>(.*?)<\/loc>/', $body, $matches ) ) {
					$urls = array_merge( $urls, $matches[1] );
				}

				if ( count( $urls ) >= 50 ) {
					break;
				}
			}
		}

		$urls = array_unique( $urls );

		// Fallback or supplement with last 20 published pages
		if ( count( $urls ) < 5 ) {
			$posts = get_posts(
				array(
					'post_type'      => 'any',
					'post_status'    => 'publish',
					'posts_per_page' => 20,
					'fields'         => 'ids',
				)
			);

			foreach ( $posts as $post_id ) {
				$urls[] = get_permalink( $post_id );
			}
		}

		$urls = array_unique( $urls );
		return array_values( array_slice( $urls, 0, 50 ) );
	}

	/**
	 * AJAX handler to scan a single URL.
	 */
	public function ajax_scan_url(): void {
		check_ajax_referer( 'wpeu-cs-scanner', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'privaro-cookie-consent-banner' ) ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid URL.', 'privaro-cookie-consent-banner' ) ) );
		}

		$results = $this->scan_url( $url );

		// Update results in option
		$all_results = get_option( 'wpeu_cs_scan_results', array() );
		$all_results = $this->merge_results( $all_results, $results );
		update_option( 'wpeu_cs_scan_results', $all_results );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Scan a single URL.
	 *
	 * @param string $url URL to scan.
	 * @return array
	 */
	private function scan_url( string $url ): array {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'user-agent' => 'Privaro Cookie Consent Banner Scanner',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$cookies = array();
		$headers = wp_remote_retrieve_headers( $response );
		$set_cookies = $headers['set-cookie'] ?? array();
		if ( is_string( $set_cookies ) ) {
			$set_cookies = array( $set_cookies );
		}

		foreach ( $set_cookies as $cookie_str ) {
			if ( preg_match( '/^([^=]+)=/i', $cookie_str, $matches ) ) {
				$name = trim( $matches[1] );
				$cookies[ $name ] = array(
					'name'     => $name,
					'type'     => 'cookie',
					'category' => $this->detect_category( $name, 'cookie' ),
				);
			}
		}

		$scripts = array();
		$body = wp_remote_retrieve_body( $response );
		if ( preg_match_all( '/<script[^>]+src=[\'"]([^\'"]+)[\'"]/i', $body, $matches ) ) {
			foreach ( $matches[1] as $src ) {
				$domain = wp_parse_url( $src, PHP_URL_HOST );
				if ( ! $domain ) {
					continue;
				}

				$scripts[ $domain ] = array(
					'name'     => $domain,
					'type'     => 'script',
					'category' => $this->detect_category( $src, 'script' ),
				);
			}
		}

		return array(
			'cookies' => array_values( $cookies ),
			'scripts' => array_values( $scripts ),
		);
	}

	/**
	 * Detect category based on name/src.
	 *
	 * @param string $identifier Cookie name or script src.
	 * @param string $type       'cookie' or 'script'.
	 * @return string
	 */
	private function detect_category( string $identifier, string $type ): string {
		$heuristics = array(
			'_ga'  => \WPEU\CookieSuite\Consent\Categories::STATISTICS,
			'_gid' => \WPEU\CookieSuite\Consent\Categories::STATISTICS,
			'_gat' => \WPEU\CookieSuite\Consent\Categories::STATISTICS,
			'_hj'  => \WPEU\CookieSuite\Consent\Categories::STATISTICS,
			'_fbp' => \WPEU\CookieSuite\Consent\Categories::MARKETING,
			'IDE'  => \WPEU\CookieSuite\Consent\Categories::MARKETING,
			'test_cookie' => \WPEU\CookieSuite\Consent\Categories::MARKETING,
		);

		foreach ( $heuristics as $pattern => $cat ) {
			if ( str_contains( $identifier, $pattern ) ) {
				return $cat;
			}
		}

		if ( 'script' === $type ) {
			$services = \WPEU\CookieSuite\Frontend\ScriptRegistry::get_services();
			foreach ( $services as $service ) {
				foreach ( $service['patterns'] as $pattern ) {
					if ( str_contains( $identifier, $pattern ) ) {
						return $service['category'];
					}
				}
			}
		}

		return \WPEU\CookieSuite\Consent\Categories::NECESSARY;
	}

	/**
	 * AJAX handler to import scan results to inventory.
	 */
	public function ajax_import_scan(): void {
		check_ajax_referer( 'wpeu-cs-scanner', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'privaro-cookie-consent-banner' ) ) );
		}

		$results = get_option( 'wpeu_cs_scan_results', array() );
		if ( empty( $results ) ) {
			wp_send_json_error( array( 'message' => __( 'No scan results to import.', 'privaro-cookie-consent-banner' ) ) );
		}

		$repository = new CookieRepository();
		$count      = $repository->import_from_scan( $results );

		wp_send_json_success(
			array(
				/* translators: %d: number of cookies */
				'message' => sprintf( __( 'Imported %d items to inventory.', 'privaro-cookie-consent-banner' ), $count ),
			)
		);
	}

	/**
	 * Merge results.
	 *
	 * @param array $all_results Existing results.
	 * @param array $new_results New results.
	 * @return array
	 */
	private function merge_results( array $all_results, array $new_results ): array {
		if ( ! isset( $all_results['cookies'] ) ) {
			$all_results['cookies'] = array();
		}
		if ( ! isset( $all_results['scripts'] ) ) {
			$all_results['scripts'] = array();
		}

		foreach ( $new_results['cookies'] as $cookie ) {
			$found = false;
			foreach ( $all_results['cookies'] as &$existing ) {
				if ( $existing['name'] === $cookie['name'] ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$all_results['cookies'][] = $cookie;
			}
		}

		foreach ( $new_results['scripts'] as $script ) {
			$found = false;
			foreach ( $all_results['scripts'] as &$existing ) {
				if ( $existing['name'] === $script['name'] ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$all_results['scripts'][] = $script;
			}
		}

		return $all_results;
	}
}
