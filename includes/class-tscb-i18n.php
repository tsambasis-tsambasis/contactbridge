<?php
/** Plugin-scoped language selection and editable factory texts. @package Kontelio */
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
		$value = is_array( $options ) && isset( $options['language'] ) ? $options['language'] : 'wordpress';
		return in_array( $value, array( 'de_DE', 'en_US', 'wordpress' ), true ) ? $value : 'wordpress';
	}

	public static function locale() {
		return 'wordpress' === self::language() ? determine_locale() : self::language();
	}

	/** Select only this domain's files; never change the WordPress/user locale. */
	public static function translation_file( $file, $domain, $locale ) {
		if ( 'kontelio' !== $domain ) {
			return $file;
		}
		$selected = 'wordpress' === self::language() ? $locale : self::language();
		if ( ! is_string( $selected ) || ! preg_match( '/^[a-zA-Z0-9_@-]+$/D', $selected ) ) {
			return $file;
		}
		$extension = '.mo' === substr( $file, -3 ) ? '.mo' : '.l10n.php';
		$pack = WP_LANG_DIR . '/plugins/kontelio-' . $selected . $extension;
		if ( is_readable( $pack ) ) {
			return $pack;
		}
		$pack_mo = WP_LANG_DIR . '/plugins/kontelio-' . $selected . '.mo';
		if ( '.l10n.php' === $extension && is_readable( $pack_mo ) ) {
			return $pack_mo;
		}
		// Regional German locales can use the installed base language pack.
		if ( 0 === strpos( $selected, 'de_' ) && 'de_DE' !== $selected ) {
			$fallback = WP_LANG_DIR . '/plugins/kontelio-de_DE' . $extension;
			if ( is_readable( $fallback ) ) {
				return $fallback;
			}
			$fallback_mo = WP_LANG_DIR . '/plugins/kontelio-de_DE.mo';
			if ( '.l10n.php' === $extension && is_readable( $fallback_mo ) ) {
				return $fallback_mo;
			}
		}
		// Missing packs fall back to the English source strings. Return the selected
		// path even when absent so JIT loading cannot substitute another language.
		return $pack;
	}

	/** Load native WordPress language packs; English requires no catalog. */
	public static function load() {
		// An explicit plugin language may change while WordPress keeps the same locale.
		// Core's reloadable unload retains catalog caches, so clear this domain only.
		WP_Translation_Controller::get_instance()->unload_textdomain( 'kontelio' );
		unload_textdomain( 'kontelio', true );
		load_textdomain( 'kontelio', WP_LANG_DIR . '/plugins/kontelio-' . self::locale() . '.mo', determine_locale() );
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
