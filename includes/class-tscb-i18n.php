<?php
/** Plugin-scoped language selection and editable factory texts. @package ContactBridge */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSCB_I18n {
	public static function init() {
		add_filter( 'load_translation_file', array( __CLASS__, 'translation_file' ), 10, 3 );
		add_action( 'init', array( __CLASS__, 'load' ), 0 );
		add_action( 'switch_blog', array( __CLASS__, 'switch_site' ) );
		add_action( 'change_locale', array( __CLASS__, 'switch_site' ) );
	}

	/** Read the raw option to avoid recursively translating settings defaults. */
	public static function language() {
		$options = get_option( 'tscb_settings', array() );
		$value = is_array( $options ) && isset( $options['language'] ) ? $options['language'] : 'de_DE';
		return in_array( $value, array( 'de_DE', 'en_US', 'wordpress' ), true ) ? $value : 'de_DE';
	}

	public static function locale() {
		return 'wordpress' === self::language() ? determine_locale() : self::language();
	}

	/** Select only this domain's files; never change the WordPress/user locale. */
	public static function translation_file( $file, $domain, $locale ) {
		if ( 'tsambasis-contact-bridge' !== $domain ) {
			return $file;
		}
		$selected = 'wordpress' === self::language() ? $locale : self::language();
		if ( ! is_string( $selected ) || ! preg_match( '/^[a-zA-Z0-9_@-]+$/D', $selected ) ) {
			return $file;
		}
		$extension = '.mo' === substr( $file, -3 ) ? '.mo' : '.l10n.php';
		$pack = WP_LANG_DIR . '/plugins/tsambasis-contact-bridge-' . $selected . $extension;
		if ( is_readable( $pack ) ) {
			return $pack;
		}
		$pack_mo = WP_LANG_DIR . '/plugins/tsambasis-contact-bridge-' . $selected . '.mo';
		if ( '.l10n.php' === $extension && is_readable( $pack_mo ) ) {
			return $pack_mo;
		}
		$bundled = TSCB_PATH . 'languages/tsambasis-contact-bridge-' . $selected . $extension;
		if ( is_readable( $bundled ) ) {
			return $bundled;
		}
		// Regional German and English locales use the complete bundled base catalogs.
		$base = 0 === strpos( $selected, 'de_' ) ? 'de_DE' : 'en_US';
		$fallback = TSCB_PATH . 'languages/tsambasis-contact-bridge-' . $base . $extension;
		return is_readable( $fallback ) ? $fallback : $file;
	}

	/** Load at init, including before publication when no language pack exists. */
	public static function load() {
		// An explicit plugin language may change while WordPress keeps the same locale.
		// Core's reloadable unload retains catalog caches, so clear this domain only.
		WP_Translation_Controller::get_instance()->unload_textdomain( 'tsambasis-contact-bridge' );
		unload_textdomain( 'tsambasis-contact-bridge', true );
		load_textdomain( 'tsambasis-contact-bridge', TSCB_PATH . 'languages/tsambasis-contact-bridge-' . self::locale() . '.mo', determine_locale() );
	}

	public static function switch_site() {
		if ( did_action( 'init' ) ) {
			self::load();
		}
	}

	/** Factory text snapshots let language changes preserve every custom value. */
	public static function factory_texts() {
		$keys = array( 'heading', 'intro', 'eyebrow', 'submit_label', 'success_message', 'label_name', 'label_email', 'label_subject', 'label_message', 'placeholder_name', 'placeholder_email', 'placeholder_subject', 'placeholder_message', 'required_note', 'message_hint', 'privacy_label', 'privacy_link_text' );
		return array_intersect_key( TSCB_Design::defaults(), array_flip( $keys ) );
	}

	public static function resolve_texts( $options, $defaults ) {
		$settings = wp_parse_args( $options, $defaults );
		$baseline = isset( $options['_text_defaults'] ) && is_array( $options['_text_defaults'] ) ? $options['_text_defaults'] : array();
		foreach ( $defaults['_text_defaults'] as $key => $value ) {
			if ( ! array_key_exists( $key, $options ) || ( isset( $baseline[ $key ] ) && $options[ $key ] === $baseline[ $key ] ) ) {
				$settings[ $key ] = $value;
			}
		}
		// Internal actions may persist this resolved array, so keep its baseline in sync.
		$settings['_text_defaults'] = $defaults['_text_defaults'];
		return $settings;
	}
}
