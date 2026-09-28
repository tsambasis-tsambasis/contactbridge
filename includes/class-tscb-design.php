<?php
/** Shared design schema and administrator-only, non-sending preview. @package Kontelio */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSCB_Design {
	public static function init() {
		add_action( 'wp_ajax_tscb_preview', array( __CLASS__, 'preview_request' ) );
	}

	/** Factory values for form appearance, text and the field editor. */
	public static function defaults() {
		return array(
			'fields' => null,
			'editor_mode' => 'simple',
			'preset' => 'custom',
			'theme' => 'light',
			'shape' => 'rounded',
			'accent' => '#22624b',
			'heading' => __( 'Let\'s get in touch.', 'kontelio' ),
			'intro' => __( 'Send us a message. We look forward to hearing from you.', 'kontelio' ),
			'eyebrow' => __( 'Get in touch', 'kontelio' ),
			'submit_label' => __( 'Send message', 'kontelio' ),
			'success_message' => __( 'Thank you! Your inquiry has been received.', 'kontelio' ),
			'label_name' => __( 'Your name', 'kontelio' ),
			'label_email' => __( 'Your email address', 'kontelio' ),
			'label_subject' => __( 'What is it about?', 'kontelio' ),
			'label_message' => __( 'Your message', 'kontelio' ),
			'placeholder_name' => '',
			'placeholder_email' => '',
			'placeholder_subject' => '',
			'placeholder_message' => '',
			'required_note' => __( 'Fields marked with * are required.', 'kontelio' ),
			/* translators: Keep {max}; it is replaced with the maximum allowed character count. */
			'message_hint' => __( 'Maximum {max} characters. Please do not send sensitive information.', 'kontelio' ),
			/* translators: Keep {privacy_link}; it is replaced with the configured privacy notice link. */
			'privacy_label' => __( 'I have read the {privacy_link} and agree to my information being processed to respond to my inquiry.', 'kontelio' ),
			'privacy_link_text' => __( 'privacy notice', 'kontelio' ),
			'privacy_url' => get_privacy_policy_url(),
			'privacy_new_tab' => true,
			'require_consent' => true,
			'layout' => 'two-column',
			'width' => 740,
			'spacing' => 'comfortable',
			'font_size' => 16,
			'button_align' => 'left',
			'button_arrow' => true,
			'show_counter' => true,
			'show_required_note' => true,
			'shadow' => true,
			'embedded' => false,
			'custom_colors' => false,
			'color_surface' => '#fffefb',
			'color_field' => '#ffffff',
			'color_text' => '#202c28',
			'color_muted' => '#58635e',
			'color_border' => '#c1cac4',
		);
	}

	/** Only these public appearance fields may enter a preview request. */
	public static function keys() {
		return array_keys( self::defaults() );
	}

	/** Presets deliberately contain no text, recipients, consent or credentials. */
	public static function presets() {
		$base = array( 'layout' => 'two-column', 'width' => 740, 'spacing' => 'comfortable', 'font_size' => 16, 'button_align' => 'left', 'button_arrow' => true, 'show_counter' => true, 'show_required_note' => true, 'shadow' => true, 'custom_colors' => false );
		return array(
			'forest' => array( 'label' => __( 'Forest', 'kontelio' ), 'description' => __( 'Light, soft, and welcoming.', 'kontelio' ), 'settings' => array_merge( $base, array( 'theme' => 'light', 'shape' => 'rounded', 'accent' => '#22624b' ) ) ),
			'midnight' => array( 'label' => __( 'Midnight', 'kontelio' ), 'description' => __( 'Dark with a fresh accent.', 'kontelio' ), 'settings' => array_merge( $base, array( 'theme' => 'dark', 'shape' => 'rounded', 'accent' => '#a8dec0' ) ) ),
			'studio' => array( 'label' => __( 'Studio', 'kontelio' ), 'description' => __( 'Clean edges and bold blue.', 'kontelio' ), 'settings' => array_merge( $base, array( 'theme' => 'light', 'shape' => 'square', 'accent' => '#2451ce' ) ) ),
			'minimal' => array( 'label' => __( 'Minimal', 'kontelio' ), 'description' => __( 'Compact, single-column, and minimal.', 'kontelio' ), 'settings' => array_merge( $base, array( 'theme' => 'light', 'shape' => 'square', 'accent' => '#26312c', 'width' => 640, 'layout' => 'single-column', 'spacing' => 'compact', 'button_align' => 'stretch', 'button_arrow' => false, 'shadow' => false ) ) ),
		);
	}

	private static function text( $value, $limit, $multiline = false ) {
		$value = $multiline ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
		preg_match( '/^.{0,' . absint( $limit ) . '}/us', $value, $match );
		return isset( $match[0] ) ? $match[0] : '';
	}

	/** Validate appearance in one place for both saving and ephemeral preview. */
	public static function sanitize( $input, $previous = array() ) {
		$defaults = self::defaults();
		$clean = array_intersect_key( wp_parse_args( $previous, $defaults ), $defaults );
		if ( ! is_array( $input ) ) {
			return $clean;
		}
		$enums = array( 'editor_mode' => array( 'simple', 'advanced' ), 'preset' => array_merge( array( 'custom' ), array_keys( self::presets() ) ), 'theme' => array( 'light', 'dark', 'auto' ), 'shape' => array( 'rounded', 'square' ), 'layout' => array( 'two-column', 'single-column' ), 'spacing' => array( 'compact', 'comfortable', 'airy' ), 'button_align' => array( 'left', 'center', 'right', 'stretch' ) );
		foreach ( $enums as $key => $allowed ) {
			if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ) {
				$clean[ $key ] = $input[ $key ];
			}
		}
		foreach ( array( 'accent', 'color_surface', 'color_field', 'color_text', 'color_muted', 'color_border' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
				$color = sanitize_hex_color( $input[ $key ] );
				if ( $color ) {
					$clean[ $key ] = $color;
				}
			}
		}
		foreach ( array( 'width' => array( 360, 1000 ), 'font_size' => array( 14, 20 ) ) as $key => $range ) {
			if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) && is_numeric( $input[ $key ] ) ) {
				$clean[ $key ] = max( $range[0], min( $range[1], (int) $input[ $key ] ) );
			}
		}
		$texts = array( 'heading' => 200, 'intro' => 1000, 'eyebrow' => 100, 'submit_label' => 80, 'success_message' => 500, 'label_name' => 100, 'label_email' => 100, 'label_subject' => 100, 'label_message' => 100, 'placeholder_name' => 150, 'placeholder_email' => 150, 'placeholder_subject' => 150, 'placeholder_message' => 300, 'required_note' => 300, 'message_hint' => 500, 'privacy_label' => 1000, 'privacy_link_text' => 150 );
		foreach ( $texts as $key => $limit ) {
			if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
				$clean[ $key ] = self::text( $input[ $key ], $limit, in_array( $key, array( 'intro', 'success_message', 'message_hint', 'privacy_label', 'placeholder_message' ), true ) );
			}
		}
		foreach ( array( 'submit_label', 'success_message', 'label_name', 'label_email', 'label_subject', 'label_message', 'privacy_label', 'privacy_link_text' ) as $key ) {
			if ( '' === trim( $clean[ $key ] ) ) {
				$clean[ $key ] = $defaults[ $key ];
			}
		}
		if ( isset( $input['privacy_url'] ) && is_string( $input['privacy_url'] ) ) {
			$url = trim( $input['privacy_url'] );
			// Require an explicit web scheme; do not turn javascript: or data: into a link.
			$clean['privacy_url'] = strlen( $url ) <= 2048 && preg_match( '~^https?://~i', $url ) && wp_parse_url( $url, PHP_URL_HOST ) ? esc_url_raw( $url, array( 'http', 'https' ) ) : '';
		}
		$complete = isset( $input['design_present'] ) && is_scalar( $input['design_present'] ) && '1' === (string) $input['design_present'];
		foreach ( array( 'privacy_new_tab', 'require_consent', 'button_arrow', 'show_counter', 'show_required_note', 'shadow', 'embedded', 'custom_colors' ) as $key ) {
			if ( $complete || array_key_exists( $key, $input ) ) {
				$clean[ $key ] = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) && in_array( $input[ $key ], array( true, 1, '1' ), true );
			}
		}
		if ( isset( $input['fields_present'] ) && in_array( $input['fields_present'], array( true, 1, '1' ), true ) ) {
			$fields = TSCB_Fields::decode( isset( $input['fields_json'] ) ? $input['fields_json'] : null, true );
			if ( ! is_wp_error( $fields ) ) { $clean['fields'] = $fields; }
		} elseif ( isset( $input['fields'] ) && is_array( $input['fields'] ) ) {
			$fields = TSCB_Fields::sanitize( $input['fields'], true );
			if ( ! is_wp_error( $fields ) ) { $clean['fields'] = $fields; }
		}
		return $clean;
	}

	/** Build an isolated document using exactly the production form renderer. */
	public static function preview_document( $settings, $state = 'form' ) {
		$channels = isset( $settings['channels'] ) && is_array( $settings['channels'] ) ? $settings['channels'] : array();
		$privacy = array();
		foreach ( array( 'privacy_whatsapp', 'privacy_telegram' ) as $key ) {
			$privacy[ $key ] = isset( $settings[ $key ] ) && in_array( $settings[ $key ], array( true, 1, '1' ), true );
		}
		$settings = self::sanitize( $settings );
		$settings = array_merge( $settings, $privacy );
		$settings['channels'] = array_values( array_filter( $channels, static function ( $channel ) { return is_string( $channel ) && in_array( $channel, array( 'email', 'telegram', 'whatsapp' ), true ); } ) );
		$state = in_array( $state, array( 'form', 'success', 'error' ), true ) ? $state : 'form';
		wp_register_style( 'tscb-preview-form', TSCB_URL . 'assets/form.css', array(), TSCB_VERSION );
		// Print a separate document through the WordPress Styles API without consuming the admin page's queue.
		$styles = clone wp_styles();
		$styles->registered['tscb-preview-form'] = clone $styles->registered['tscb-preview-form'];
		$styles->do_concat = false;
		$styles->enqueue( 'tscb-preview-form' );
		$preview_css = 'html{background:#eef1ed}body{margin:0;padding:16px;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}body>.tscb:not(.tscb--embedded){margin:0 auto}@media(max-width:420px){body{padding:8px}}';
		if ( ! empty( $settings['embedded'] ) ) {
			if ( ! empty( $settings['custom_colors'] ) ) {
				$preview_css .= 'html{background:' . sanitize_hex_color( $settings['color_surface'] ) . '}';
			} elseif ( 'dark' === $settings['theme'] ) {
				$preview_css .= 'html{background:#101916}';
			} elseif ( 'auto' === $settings['theme'] ) {
				$preview_css .= '@media(prefers-color-scheme:dark){html{background:#101916}}';
			}
		}
		$styles->add_inline_style( 'tscb-preview-form', $preview_css );
		ob_start();
		$styles->do_items( array( 'tscb-preview-form' ) );
		$stylesheet = ob_get_clean();
		$html = '<!doctype html><html lang="' . esc_attr( str_replace( '_', '-', TSCB_I18n::locale() ) ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html__( 'Form preview', 'kontelio' ) . '</title>';
		$html .= $stylesheet . '</head><body>';
		$html .= TSCB_Form::preview( $settings, $state );
		return $html . '</body></html>';
	}

	/** Read-only preview endpoint: capability and CSRF checks precede all rendering. */
	public static function preview_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to view the preview.', 'kontelio' ) ), 403 );
		}
		check_ajax_referer( 'tscb_preview', 'nonce' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The bounded appearance-only schema below is the shared sanitization boundary.
		$input = isset( $_POST['tscb_settings'] ) && is_array( $_POST['tscb_settings'] ) ? wp_unslash( $_POST['tscb_settings'] ) : array();
		$settings = self::sanitize( $input, TSCB_Settings::get() );
		// Only these non-secret flags affect limits and hints; retain no inbox, recipient or credential settings.
		foreach ( array( 'privacy_whatsapp', 'privacy_telegram' ) as $key ) {
			$settings[ $key ] = isset( $input[ $key ] ) && in_array( $input[ $key ], array( true, 1, '1' ), true );
		}
		$settings['channels'] = array();
		if ( isset( $input['channels'] ) && is_array( $input['channels'] ) ) {
			foreach ( $input['channels'] as $channel ) {
				if ( is_string( $channel ) && in_array( $channel, array( 'email', 'telegram', 'whatsapp' ), true ) ) {
					$settings['channels'][] = $channel;
				}
			}
		}
		$state = isset( $_POST['state'] ) && is_string( $_POST['state'] ) ? sanitize_key( wp_unslash( $_POST['state'] ) ) : 'form';
		nocache_headers();
		wp_send_json_success( array( 'html' => self::preview_document( $settings, $state ) ) );
	}
}
