<?php
/**
 * Shortcodes registration and rendering.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Consent\BannerTexts;
use WPEU\CookieSuite\Scanner\CookieRepository;

/**
 * Shortcodes class.
 */
final class Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'wpeu_cookie_table', array( $this, 'render_cookie_table' ) );
		add_shortcode( 'wpeu_cookie_policy', array( $this, 'render_cookie_policy' ) );
		add_shortcode( 'wpeu_manage_consent', array( $this, 'render_manage_consent' ) );
	}

	/**
	 * Render [wpeu_cookie_table] shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_cookie_table( $atts ): string {
		$atts = shortcode_atts(
			array(
				'category'         => '',
				'show_description' => 'true',
			),
			$atts,
			'wpeu_cookie_table'
		);

		$show_description = filter_var( $atts['show_description'], FILTER_VALIDATE_BOOLEAN );
		$repository       = new CookieRepository();
		$cookies          = $repository->all( array( 'category' => $atts['category'] ) );

		if ( empty( $cookies ) ) {
			return '<p>' . esc_html__( 'No cookies detected.', 'wp-eu-cookie-suite' ) . '</p>';
		}

		$all_categories = Categories::get_all();
		$grouped        = array();

		foreach ( $cookies as $cookie ) {
			$cat = $cookie['category'] ?: 'necessary';
			if ( ! isset( $grouped[ $cat ] ) ) {
				$grouped[ $cat ] = array();
			}
			$grouped[ $cat ][] = $cookie;
		}

		ob_start();
		?>
		<div class="wpeu-cookie-inventory">
			<?php foreach ( $grouped as $cat_id => $cat_cookies ) : ?>
				<?php
				$cat_label = $all_categories[ $cat_id ]['label'] ?? $cat_id;
				$cat_desc  = $all_categories[ $cat_id ]['description'] ?? '';
				?>
				<div class="wpeu-cookie-category-section">
					<h4><?php echo esc_html( $cat_label ); ?></h4>
					<?php if ( $show_description && $cat_desc ) : ?>
						<p class="wpeu-cookie-category-description"><?php echo esc_html( $cat_desc ); ?></p>
					<?php endif; ?>

					<table class="wpeu-cookie-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Name', 'wp-eu-cookie-suite' ); ?></th>
								<th><?php esc_html_e( 'Domain', 'wp-eu-cookie-suite' ); ?></th>
								<th><?php esc_html_e( 'Service', 'wp-eu-cookie-suite' ); ?></th>
								<th><?php esc_html_e( 'Duration', 'wp-eu-cookie-suite' ); ?></th>
								<?php if ( $show_description ) : ?>
									<th><?php esc_html_e( 'Description', 'wp-eu-cookie-suite' ); ?></th>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $cat_cookies as $cookie ) : ?>
								<tr>
									<td><?php echo esc_html( $cookie['name'] ); ?></td>
									<td><?php echo esc_html( $cookie['domain'] ); ?></td>
									<td><?php echo esc_html( $cookie['service'] ); ?></td>
									<td><?php echo esc_html( $cookie['duration'] ); ?></td>
									<?php if ( $show_description ) : ?>
										<td><?php echo esc_html( $cookie['description'] ); ?></td>
									<?php endif; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean() ?: '';
	}

	/**
	 * Render [wpeu_cookie_policy] shortcode.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Wrapped content.
	 * @return string
	 */
	public function render_cookie_policy( $atts, ?string $content = null ): string {
		$settings     = get_option( 'wpeu_cs_settings', array() );
		$locale       = BannerTexts::get_active_locale();
		$policy_texts = $settings['policy_texts'][ $locale ] ?? array();

		$intro    = $policy_texts['intro'] ?? '';
		$template = $policy_texts['template'] ?? BannerTexts::get_default_policy_template( $locale );

		$table = $this->render_cookie_table( array() );

		$output = str_replace(
			array( '{{intro}}', '{{table}}', '{{content}}' ),
			array(
				wpautop( esc_html( $intro ) ),
				$table,
				$content ? do_shortcode( $content ) : '',
			),
			$template
		);

		return '<div class="wpeu-cookie-policy-wrapper">' . $output . '</div>';
	}

	/**
	 * Render [wpeu_manage_consent] shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_manage_consent( $atts ): string {
		$atts = shortcode_atts(
			array(
				'label' => '',
				'style' => 'link',
			),
			$atts,
			'wpeu_manage_consent'
		);

		$style = 'button' === $atts['style'] ? 'button' : 'link';
		$label = $atts['label'];

		if ( '' === $label ) {
			$locale = BannerTexts::get_active_locale();
			$texts  = BannerTexts::get_strings( $locale );
			$label  = $texts['manage_consent_label'] ?? __( 'Cookie settings', 'wp-eu-cookie-suite' );
		}

		$onclick = "if(window.CookieConsent&&typeof window.CookieConsent.showPreferences==='function'){window.CookieConsent.showPreferences();}else if(window.CookieConsent&&typeof window.CookieConsent.show==='function'){window.CookieConsent.show(true);}return false;";

		if ( 'button' === $style ) {
			return sprintf(
				'<button type="button" class="wpeu-manage-consent wpeu-manage-consent--button" onclick="%1$s">%2$s</button>',
				esc_attr( $onclick ),
				esc_html( $label )
			);
		}

		return sprintf(
			'<a href="#" class="wpeu-manage-consent wpeu-manage-consent--link" role="button" onclick="%1$s">%2$s</a>',
			esc_attr( $onclick ),
			esc_html( $label )
		);
	}
}
