<?php
/**
 * Frontend Banner class.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

namespace WPEU\CookieSuite\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use WPEU\CookieSuite\Consent\Categories;
use WPEU\CookieSuite\Consent\BannerTexts;
use WPEU\CookieSuite\Settings\SettingsRepository;

/**
 * Handles the cookie consent banner on the frontend.
 */
final class Banner {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'attach_inline_assets' ), 100 );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets(): void {
		if ( ( is_admin() && ! defined( 'WPEU_CS_PREVIEW' ) ) || is_login() ) {
			return;
		}

		$script_path = WPEU_CS_PATH . 'assets/js/cookieconsent.bundle.js';
		$style_path  = WPEU_CS_PATH . 'assets/css/cookieconsent.bundle.css';

		if ( ! is_readable( $script_path ) || ! is_readable( $style_path ) ) {
			return;
		}

		wp_enqueue_style(
			'wpeu-cs-cookieconsent',
			WPEU_CS_URL . 'assets/css/cookieconsent.bundle.css',
			array(),
			WPEU_CS_VERSION
		);

		wp_enqueue_style(
			'wpeu-cs-frontend',
			WPEU_CS_URL . 'assets/css/frontend.css',
			array(),
			WPEU_CS_VERSION
		);

		wp_enqueue_script(
			'wpeu-cs-cookieconsent',
			WPEU_CS_URL . 'assets/js/cookieconsent.bundle.js',
			array(),
			WPEU_CS_VERSION,
			true
		);
	}

	/**
	 * Attach inline banner styles and CookieConsent bootstrap via wp_enqueue API.
	 */
	public function attach_inline_assets(): void {
		if ( ( is_admin() && ! defined( 'WPEU_CS_PREVIEW' ) ) || is_login() ) {
			return;
		}

		if ( ! wp_script_is( 'wpeu-cs-cookieconsent', 'enqueued' ) ) {
			return;
		}

		$config        = $this->get_config();
		$settings      = SettingsRepository::get_effective_settings();
		$banner_ui     = $settings['banner_ui'] ?? array();
		$theme         = $banner_ui['theme'] ?? 'light';
		$primary       = sanitize_hex_color( $banner_ui['primary_color'] ?? '' ) ?: '#30363c';
		$is_preview    = defined( 'WPEU_CS_PREVIEW' );
		$cookie_secure = is_ssl() && ! $is_preview;
		$wp_consent_map = Categories::get_wp_consent_map();
		$locale         = BannerTexts::get_active_locale();
		$reload_on_revoke = ! empty( $settings['reload_on_revoke'] );

		wp_add_inline_style(
			'wpeu-cs-frontend',
			':root {'
			. '--cc-btn-primary-bg:' . esc_html( $primary ) . ';'
			. '--cc-btn-primary-border-color:' . esc_html( $primary ) . ';'
			. '--cc-toggle-on-bg:' . esc_html( $primary ) . ';'
			. '--cc-link-color:' . esc_html( $primary ) . ';'
			. '}'
		);

		if ( 'dark' === $theme ) {
			wp_add_inline_script(
				'wpeu-cs-cookieconsent',
				"document.documentElement.classList.add('cc--darkmode');",
				'before'
			);
		}

		$secure_js   = $cookie_secure ? "'; Secure'" : "''";
		$is_preview_js = $is_preview ? 'true' : 'false';
		$reload_js   = $reload_on_revoke ? 'true' : 'false';

		$script = '(function(){window.addEventListener("load",function(){'
			. 'const cc=window.CookieConsent;if(!cc||typeof cc.run!=="function"){return;}'
			. 'const isPreview=' . $is_preview_js . ';'
			. 'if(!isPreview&&typeof window.wp_set_consent!=="function"){'
			. 'window.wp_set_consent=function(category,status){'
			. 'document.dispatchEvent(new CustomEvent("wp_api_set_consent",{detail:{category:category,status:status}}));'
			. '};}'
			. 'if(isPreview&&typeof cc.reset==="function"){cc.reset(true);}'
			. 'const getWpeuUuid=function(){const name="wpeu_consent_uuid=";const decodedCookie=decodeURIComponent(document.cookie);'
			. 'const ca=decodedCookie.split(";");for(let i=0;i<ca.length;i++){let c=ca[i];while(c.charAt(0)===" "){c=c.substring(1);}'
			. 'if(c.indexOf(name)===0){return c.substring(name.length,c.length);}}return "";};'
			. 'const generateWpeuUuid=function(){return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,function(c){'
			. 'const r=Math.random()*16|0,v=c==="x"?r:(r&0x3|0x8);return v.toString(16);});};'
			. 'let wpeuUuid=getWpeuUuid();'
			. 'const ensureWpeuUuid=function(){if(isPreview){return "";}if(wpeuUuid){return wpeuUuid;}'
			. 'wpeuUuid=generateWpeuUuid();document.cookie="wpeu_consent_uuid="+wpeuUuid+"; path=/; max-age=34128000; SameSite=Lax"+' . $secure_js . ';'
			. 'return wpeuUuid;};'
			. 'const logConsentEvent=function(eventType,categories){if(isPreview||!ensureWpeuUuid()){return;}'
			. 'const data=new FormData();data.append("action","wpeu_cs_log_consent");'
			. 'data.append("nonce",' . wp_json_encode( wp_create_nonce( 'wpeu-cs-log' ) ) . ');'
			. 'data.append("event_type",eventType);data.append("consent_uuid",wpeuUuid);'
			. 'data.append("page_url",window.location.href);'
			. 'data.append("locale",' . wp_json_encode( $locale ) . ');'
			. 'if(categories){Object.keys(categories).forEach(function(cat){data.append("categories["+cat+"]",categories[cat]?1:0);});}'
			. 'fetch(' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ',{method:"POST",body:data}).catch(function(err){console.error("WPEU Log Error:",err);});};'
			. 'const syncWpeuCookies=function(){if(typeof cc.validConsent==="function"&&!cc.validConsent()){return {};}'
			. 'const categories=Object.keys(cc.getConfig().categories);const consentData={};'
			. 'const mapping=' . wp_json_encode( $wp_consent_map ) . ';'
			. 'const secureAttr=' . $secure_js . ';const wpConsentState={};'
			. 'Object.keys(mapping).forEach(function(slug){const wpCat=mapping[slug];'
			. 'if(wpCat&&typeof wpConsentState[wpCat]==="undefined"){wpConsentState[wpCat]=false;}});'
			. 'categories.forEach(function(cat){const accepted=cc.acceptedCategory(cat);consentData[cat]=accepted;'
			. 'document.cookie="wpeu_"+cat+"="+(accepted?"1":"0")+"; path=/; max-age=31536000; SameSite=Lax"+secureAttr;'
			. 'const wpCat=mapping[cat];if(wpCat&&accepted){wpConsentState[wpCat]=true;}});'
			. 'Object.keys(wpConsentState).forEach(function(wpCat){window.wp_set_consent(wpCat,wpConsentState[wpCat]?"allow":"deny");});'
			. 'document.cookie="wpeu_consent="+encodeURIComponent(JSON.stringify(consentData))+"; path=/; max-age=31536000; SameSite=Lax"+secureAttr;'
			. 'document.dispatchEvent(new CustomEvent("wpeu-consent-updated",{detail:consentData}));return consentData;};'
			. 'const logConsentFromState=function(consentData){const categories=Object.keys(cc.getConfig().categories);'
			. 'let eventType="save_preferences";const allEnabled=categories.filter(function(c){return !cc.getConfig().categories[c].readOnly;});'
			. 'const acceptedEnabled=allEnabled.filter(function(c){return consentData[c];});'
			. 'if(acceptedEnabled.length===allEnabled.length){eventType="accept_all";}'
			. 'else if(acceptedEnabled.length===0){eventType="reject_all";}'
			. 'logConsentEvent(eventType,consentData);};'
			. 'const ccConfig=' . wp_json_encode( $config ) . ';'
			. 'ccConfig.onConsent=function(){syncWpeuCookies();};'
			. 'ccConfig.onFirstConsent=function(){ensureWpeuUuid();const consentData=syncWpeuCookies();logConsentFromState(consentData);};'
			. 'ccConfig.onChange=function(event){const consentData=syncWpeuCookies();'
			. 'if(event&&event.changedCategories&&event.changedCategories.length>0){logConsentFromState(consentData);}};'
			. 'cc.run(ccConfig);'
			. 'if(isPreview&&typeof cc.show==="function"){cc.show(true);return;}'
			. 'window.addEventListener("wpeu-cs-revoke",function(){const secureAttr=' . $secure_js . ';'
			. 'const categories=Object.keys(cc.getConfig().categories);logConsentEvent("revoke",{});'
			. 'categories.forEach(function(cat){document.cookie="wpeu_"+cat+"=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax"+secureAttr;});'
			. 'document.cookie="wpeu_consent=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax"+secureAttr;'
			. 'document.cookie="wpeu_consent_uuid=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax"+secureAttr;'
			. 'cc.reset(true);wpeuUuid="";document.dispatchEvent(new CustomEvent("wpeu-consent-revoked"));'
			. 'if(' . $reload_js . '){window.location.reload();}else{cc.run(ccConfig);if(typeof cc.show==="function"){cc.show(true);}}});'
			. '});})();';

		wp_add_inline_script( 'wpeu-cs-cookieconsent', $script, 'after' );
	}

	/**
	 * Legacy hook for inline banner config (deprecated).
	 *
	 * @deprecated Use attach_inline_assets().
	 */
	public function render_config(): void {
		$this->attach_inline_assets();
	}

	/**
	 * Build CookieConsent config array.
	 *
	 * @return array<string, mixed>
	 */
	private function get_config(): array {
		$settings           = SettingsRepository::get_effective_settings();
		$all_categories     = Categories::get_enabled_for_banner();
		$enabled_categories = $settings['enabled_categories'] ?? array( 'preferences', 'statistics', 'marketing' );
		$show_reject_all    = $settings['show_reject_all'] ?? true;
		$privacy_url        = $settings['privacy_policy_url'] ?? '';
		$cookie_url         = $settings['cookie_policy_url'] ?? '';
		$eu_mode            = $settings['eu_mode'] ?? true;

		$banner_ui = $settings['banner_ui'] ?? array();
		$layout    = in_array( $banner_ui['layout'] ?? 'box', array( 'box', 'bar' ), true ) ? $banner_ui['layout'] : 'box';
		$position  = self::map_consent_modal_position( (string) ( $banner_ui['position'] ?? 'bottom-right' ) );

		$locale = BannerTexts::get_active_locale();
		$texts  = BannerTexts::get_strings( $locale );

		$categories_config = array();
		$sections          = array(
			array(
				'title'       => $texts['preferences_intro_title'],
				'description' => $texts['preferences_intro_description'],
			),
		);

		foreach ( $all_categories as $id => $category ) {
			$categories_config[ $id ] = array(
				'readOnly' => $category['read_only'] ?? false,
				'enabled'  => $category['enabled'] ?? false,
			);

			$sections[] = array(
				'title'          => $texts[ $id . '_label' ] ?? $category['label'],
				'description'    => $texts[ $id . '_description' ] ?? $category['description'],
				'linkedCategory' => $id,
			);
		}

		$footer_links = array();
		if ( ! empty( $privacy_url ) ) {
			$footer_links[] = '<a href="' . esc_url( $privacy_url ) . '">' . __( 'Privacy Policy', 'privaro-cookie-consent-banner' ) . '</a>';
		}
		if ( ! empty( $cookie_url ) ) {
			$footer_links[] = '<a href="' . esc_url( $cookie_url ) . '">' . __( 'Cookie Policy', 'privaro-cookie-consent-banner' ) . '</a>';
		}

		$footer_html = implode( ' | ', $footer_links );

		$config = array(
			'revision'   => max( 0, (int) ( $settings['consent_revision'] ?? 0 ) ),
			'mode'       => $eu_mode ? 'opt-in' : 'opt-out',
			'guiOptions' => array(
				'consentModal' => array(
					'layout'             => $layout,
					'position'           => $position,
					'flipButtons'        => false,
					'equalWeightButtons' => true,
				),
				'preferencesModal' => array(
					'layout'             => 'box',
					'position'           => 'right',
					'flipButtons'        => false,
					'equalWeightButtons' => true,
				),
			),
			'categories' => $categories_config,
			'language' => array(
				'default'      => $locale,
				'translations' => array(
					$locale => array(
						'consentModal' => array(
							'title'              => $texts['consent_modal_title'],
							'description'        => $texts['consent_modal_description'],
							'acceptAllBtn'       => $texts['accept_all_btn'],
							'acceptNecessaryBtn' => $show_reject_all ? $texts['accept_necessary_btn'] : '',
							'showPreferencesBtn' => $texts['show_preferences_btn'],
							'footer'             => $footer_html,
						),
						'preferencesModal' => array(
							'title'              => $texts['preferences_modal_title'],
							'acceptAllBtn'       => $texts['accept_all_btn'],
							'acceptNecessaryBtn' => $show_reject_all ? $texts['accept_necessary_btn'] : '',
							'savePreferencesBtn' => $texts['save_preferences_btn'],
							'closeIconLabel'     => $texts['close_icon_label'],
							'sections'           => $sections,
						),
					),
				),
			),
		);

		if ( defined( 'WPEU_CS_PREVIEW' ) ) {
			$config['autoShow'] = false;
			$config['cookie']   = array(
				'name'             => 'wpeu_cs_preview_cc',
				'expiresAfterDays' => 1,
			);
		}

		return $config;
	}

	/**
	 * Map admin position slug to CookieConsent modal position.
	 *
	 * @param string $position Admin position value.
	 * @return string
	 */
	private static function map_consent_modal_position( string $position ): string {
		$normalized = strtolower( str_replace( ' ', '-', trim( $position ) ) );

		$map = array(
			'bottom-left'   => 'bottom left',
			'bottom-center' => 'bottom center',
			'bottom-right'  => 'bottom right',
			'center'        => 'middle center',
			'middle-center' => 'middle center',
			'top-left'      => 'top left',
			'top-center'    => 'top center',
			'top-right'     => 'top right',
		);

		if ( isset( $map[ $normalized ] ) ) {
			return $map[ $normalized ];
		}

		$spaced = str_replace( '-', ' ', $normalized );
		$valid  = array(
			'top',
			'bottom',
			'middle',
			'top left',
			'top center',
			'top right',
			'middle left',
			'middle center',
			'middle right',
			'bottom left',
			'bottom center',
			'bottom right',
		);

		if ( in_array( $spaced, $valid, true ) ) {
			return $spaced;
		}

		return 'bottom right';
	}
}
