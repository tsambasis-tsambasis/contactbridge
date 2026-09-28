<?php
/** Delivery through configured, fixed service endpoints. @package Kontelio */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSCB_Transports {
	public static function init() {
		add_action( 'admin_post_tscb_test', array( __CLASS__, 'test' ) );
		add_action( 'admin_post_tscb_discover', array( __CLASS__, 'discover' ) );
	}

	public static function secret( $channel, $settings ) {
		$constant = 'TSCB_' . strtoupper( $channel ) . '_TOKEN';
		return defined( $constant ) ? (string) constant( $constant ) : (string) $settings[ $channel . '_token' ];
	}

	public static function configured( $channel, $settings ) {
		if ( 'email' === $channel ) {
			return (bool) is_email( $settings['email_to'] );
		}
		if ( 'telegram' === $channel ) {
			return (bool) ( preg_match( '/^\d+:[A-Za-z0-9_-]{20,}$/D', self::secret( 'telegram', $settings ) ) && preg_match( '/^(?:-?\d+|@[A-Za-z0-9_]{5,})$/D', $settings['telegram_chat_id'] ) );
		}
		if ( 'whatsapp' === $channel ) {
			$template = ! empty( $settings['privacy_whatsapp'] ) ? ( isset( $settings['whatsapp_privacy_template'] ) ? $settings['whatsapp_privacy_template'] : '' ) : $settings['whatsapp_template'];
			return (bool) ( preg_match( '/^[A-Za-z0-9_.-]{20,4096}$/D', self::secret( 'whatsapp', $settings ) ) && preg_match( '/^\d{5,30}$/D', $settings['whatsapp_phone_id'] ) && preg_match( '/^[1-9]\d{6,14}$/D', $settings['whatsapp_to'] ) && preg_match( '/^v\d{2,3}\.0$/D', $settings['whatsapp_version'] ) && is_string( $template ) && preg_match( '/^[a-z0-9_]{1,512}$/D', $template ) && preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/D', $settings['whatsapp_language'] ) );
		}
		return false;
	}

	/** No visitor can choose an endpoint or recipient. */
	public static function send( $channel, $data, $settings, $context = 'form' ) {
		if ( ! self::configured( $channel, $settings ) ) {
			return new WP_Error( 'configuration', __( 'Please complete the setup for this channel.', 'kontelio' ) );
		}
		$site = TSCB_Fields::site_name();
		$privacy = ! empty( $settings[ 'privacy_' . $channel ] );
		$inquiry_url = '';
		if ( $privacy ) {
			// The record ID is supplied only by the validated submission pipeline, never by a request field.
			if ( ! class_exists( 'TSCB_Inbox' ) || ( 'test' !== $context && ( ! isset( $data['_inquiry_id'] ) || ! is_int( $data['_inquiry_id'] ) || $data['_inquiry_id'] < 1 ) ) ) {
				return new WP_Error( 'privacy_storage', __( 'The inquiry could not be stored securely. No notification was sent.', 'kontelio' ) );
			}
			$inquiry_url = TSCB_Inbox::admin_url( 'test' === $context ? 0 : $data['_inquiry_id'] );
			/* translators: %s: website name, not visitor-supplied information. */
			$body = sprintf( __( 'New inquiry via the contact form on %s.', 'kontelio' ), $site ) . "\n\n" . $inquiry_url;
		} else {
			$body = isset( $data['fields'] ) ? TSCB_Fields::notification( $data ) : implode( "\n", array( __( 'Contact form: ', 'kontelio' ) . $site, __( 'Name: ', 'kontelio' ) . $data['name'], __( 'Email: ', 'kontelio' ) . $data['email'], __( 'Subject: ', 'kontelio' ) . $data['subject'], '', $data['message'] ) );
		}
		if ( 'email' === $channel ) {
			$subject = '[' . preg_replace( '/[\r\n]+/', ' ', $site ) . '] ' . ( $privacy ? __( 'New contact inquiry', 'kontelio' ) : $data['subject'] );
			$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
			if ( ! $privacy && '' !== $data['email'] && is_email( $data['email'] ) && ! preg_match( '/[\r\n]/', $data['email'] ) ) { $headers[] = 'Reply-To: ' . $data['email']; }
			return TSCB_Mail::send( $settings['email_to'], $subject, $body, $headers, $settings, $context );
		}
		if ( 'telegram' === $channel ) {
			$url = 'https://api.telegram.org/bot' . self::secret( 'telegram', $settings ) . '/sendMessage';
			$payload = array( 'chat_id' => $settings['telegram_chat_id'], 'text' => $body, 'link_preview_options' => array( 'is_disabled' => true ) );
			return self::request( $url, $payload, array(), 'telegram' );
		}
		$params = array();
		$values = $privacy ? array( $site, $inquiry_url ) : TSCB_Fields::whatsapp_parameters( $data );
		foreach ( $values as $value ) {
			// Meta text parameters cannot contain newlines, tabs or long whitespace runs.
			$params[] = array( 'type' => 'text', 'text' => $privacy ? preg_replace( '/\s+/u', ' ', $value ) : $value );
		}
		$payload = array(
			'messaging_product' => 'whatsapp',
			'to' => $settings['whatsapp_to'],
			'type' => 'template',
			'template' => array( 'name' => $privacy ? $settings['whatsapp_privacy_template'] : $settings['whatsapp_template'], 'language' => array( 'code' => $settings['whatsapp_language'] ), 'components' => array( array( 'type' => 'body', 'parameters' => $params ) ) ),
		);
		$url = 'https://graph.facebook.com/' . $settings['whatsapp_version'] . '/' . $settings['whatsapp_phone_id'] . '/messages';
		return self::request( $url, $payload, array( 'Authorization' => 'Bearer ' . self::secret( 'whatsapp', $settings ) ), 'whatsapp' );
	}

	/** Return safe diagnostics; never reflect provider bodies, tokens or visitor data. */
	private static function request( $url, $payload, $headers, $service ) {
		$response = wp_remote_post( $url, array( 'timeout' => 12, 'redirection' => 0, 'limit_response_size' => 65536, 'headers' => array_merge( array( 'Content-Type' => 'application/json' ), $headers ), 'body' => wp_json_encode( $payload ), 'data_format' => 'body' ) );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'network', __( 'The service could not be reached. Please check the server connection and service status.', 'kontelio' ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		$accepted = 'telegram' === $service ? ! empty( $json['ok'] ) : ! empty( $json['messages'][0]['id'] );
		if ( $code >= 200 && $code < 300 && $accepted ) {
			return true;
		}
		return new WP_Error( 'provider_' . absint( $code ), __( 'The service did not confirm the request. Please check the token and recipient, and for WhatsApp, the template, language, and account status.', 'kontelio' ) );
	}

	public static function test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'kontelio' ), '', array( 'response' => 403 ) );
		}
		self::require_post();
		check_admin_referer( 'tscb_test' );
		$channel = isset( $_POST['channel'] ) && is_string( $_POST['channel'] ) ? sanitize_key( wp_unslash( $_POST['channel'] ) ) : '';
		$settings = TSCB_Settings::get();
		$result = new WP_Error( 'disabled', __( 'Please enable the delivery channel and save your settings first.', 'kontelio' ) );
		if ( in_array( $channel, $settings['channels'], true ) ) {
			$result = self::send( $channel, array( 'name' => __( 'Plugin test', 'kontelio' ), 'email' => '', 'subject' => __( 'Kontelio – Test', 'kontelio' ), 'message' => __( 'The connection works. This is a test message from the WordPress settings.', 'kontelio' ) ), $settings, 'test' );
		}
		self::status( $channel, $result );
		if ( 'email' === $channel ) {
			set_transient( 'tscb_mail_test_' . get_current_user_id(), is_wp_error( $result ) ? $result->get_error_code() : 'mail_accepted', 5 * MINUTE_IN_SECONDS );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'kontelio', 'tscb_test_result' => is_wp_error( $result ) ? 'failed' : 'ok', 'channel' => $channel ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	public static function status( $channel, $result ) {
		if ( ! in_array( $channel, array( 'email', 'telegram', 'whatsapp' ), true ) ) {
			return;
		}
		set_transient( 'tscb_health_' . $channel, array( 'time' => time(), 'ok' => ! is_wp_error( $result ), 'code' => is_wp_error( $result ) ? $result->get_error_code() : 'accepted' ), DAY_IN_SECONDS );
	}

	/** Reject side-effecting admin actions sent without an explicit POST. */
	private static function require_post() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_die( esc_html__( 'Please submit the form.', 'kontelio' ), '', array( 'response' => 405 ) );
		}
	}

	/** Generate an expiring, administrator-specific proof of the intended Telegram account. */
	public static function pairing_code( $settings ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		$token = self::secret( 'telegram', $settings );
		if ( ! preg_match( '/^\d+:[A-Za-z0-9_-]{20,}$/D', $token ) ) {
			return '';
		}
		$key = 'tscb_pairing_' . get_current_user_id();
		$pairing = get_transient( $key );
		$fingerprint = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
		if ( ! self::valid_pairing( $pairing, $fingerprint ) ) {
			$pairing = array( 'code' => 'TSCB-' . strtoupper( bin2hex( random_bytes( 12 ) ) ), 'token' => $fingerprint, 'expires' => time() + 10 * MINUTE_IN_SECONDS );
			set_transient( $key, $pairing, 10 * MINUTE_IN_SECONDS );
		}
		return $pairing['code'];
	}

	/** Validate the challenge without ever exposing or storing an extra copy of the bot token. */
	private static function valid_pairing( $pairing, $fingerprint ) {
		return is_array( $pairing ) && isset( $pairing['code'], $pairing['token'], $pairing['expires'] ) && is_string( $pairing['code'] ) && is_string( $pairing['token'] ) && is_numeric( $pairing['expires'] ) && $pairing['expires'] > time() && preg_match( '/^TSCB-[A-F0-9]{24}$/D', $pairing['code'] ) && hash_equals( $fingerprint, $pairing['token'] );
	}

	/** Save only a private chat that proves possession of this administrator's current code. */
	public static function discover() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'kontelio' ), '', array( 'response' => 403 ) );
		}
		self::require_post();
		check_admin_referer( 'tscb_discover' );
		$settings = TSCB_Settings::get();
		$token = self::secret( 'telegram', $settings );
		$pairing_key = 'tscb_pairing_' . get_current_user_id();
		$pairing = get_transient( $pairing_key );
		$fingerprint = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
		$status = 'expired';
		if ( self::valid_pairing( $pairing, $fingerprint ) && preg_match( '/^\d+:[A-Za-z0-9_-]{20,}$/D', $token ) ) {
			$status = 'failed';
			$response = wp_remote_post( 'https://api.telegram.org/bot' . $token . '/getUpdates', array( 'timeout' => 12, 'redirection' => 0, 'limit_response_size' => 262144, 'body' => array( 'limit' => 100, 'timeout' => 0 ) ) );
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$json = json_decode( wp_remote_retrieve_body( $response ), true );
				$chats = array();
				if ( ! empty( $json['ok'] ) && isset( $json['result'] ) && is_array( $json['result'] ) ) {
					foreach ( $json['result'] as $update ) {
						$message = isset( $update['message'] ) && is_array( $update['message'] ) ? $update['message'] : array();
						$chat = isset( $message['chat'] ) && is_array( $message['chat'] ) ? $message['chat'] : array();
						if ( isset( $chat['id'], $chat['type'], $message['text'] ) && is_scalar( $chat['id'] ) && preg_match( '/^[1-9][0-9]{0,19}$/D', (string) $chat['id'] ) && 'private' === $chat['type'] && is_string( $message['text'] ) && hash_equals( $pairing['code'], trim( $message['text'] ) ) ) {
							$chats[ (string) $chat['id'] ] = true;
						}
					}
					$status = 'empty';
					if ( 1 === count( $chats ) ) {
						// Re-read after network I/O so a changed token or expired/consumed code fails closed.
						$latest = TSCB_Settings::get();
						$current = get_transient( $pairing_key );
						$latest_fingerprint = hash_hmac( 'sha256', self::secret( 'telegram', $latest ), wp_salt( 'auth' ) );
						$status = 'expired';
						if ( self::valid_pairing( $current, $latest_fingerprint ) && hash_equals( $pairing['code'], $current['code'] ) && hash_equals( $fingerprint, $latest_fingerprint ) ) {
							$latest['telegram_chat_id'] = (string) array_key_first( $chats );
							update_option( 'tscb_settings', $latest, false );
							delete_transient( $pairing_key );
							$status = 'ok';
						}
					} elseif ( count( $chats ) > 1 ) {
						$status = 'failed';
					}
				}
			}
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'kontelio', 'tscb_discover_result' => $status ), admin_url( 'options-general.php' ) ) );
		exit;
	}
}
