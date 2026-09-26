<?php
/** Scoped email delivery and privacy-preserving diagnostics. @package ContactBridge */
defined( 'ABSPATH' ) || exit;

final class TSCB_Mail {
	/** Return only this component's settings. */
	public static function defaults() {
		return array( 'email_method' => 'wordpress', 'email_from' => '', 'email_from_name' => '', 'smtp_host' => '', 'smtp_port' => 587, 'smtp_encryption' => 'tls', 'smtp_auth' => true, 'smtp_username' => '', 'smtp_password' => '', 'email_log_enabled' => true );
	}

	private static function checked( $value ) { return in_array( $value, array( true, 1, '1' ), true ); }
	private static function valid_port( $port ) { return ( is_int( $port ) || ( is_string( $port ) && ctype_digit( $port ) ) ) && (int) $port >= 1 && (int) $port <= 65535; }

	/** A single host only: PHPMailer also accepts host lists and schemes, which we deliberately reject. */
	private static function valid_host( $host ) {
		if ( ! is_string( $host ) || '' === $host || strlen( $host ) > 253 || preg_match( '/[\s\/;\\\\\x00-\x1f\x7f]/', $host ) ) { return false; }
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) { return true; }
		return (bool) preg_match( '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*\.?$/iD', $host );
	}

	private static function clean_line( $value, $max ) {
		return is_string( $value ) && strlen( $value ) <= $max && ! preg_match( '/[\x00-\x1f\x7f]/', $value ) && wp_check_invalid_utf8( $value ) === $value;
	}

	/** Preserve mail values when another settings section is saved. */
	public static function sanitize( $input, $previous ) {
		$defaults = self::defaults();
		$result = array_merge( $defaults, array_intersect_key( is_array( $previous ) ? $previous : array(), $defaults ) );
		if ( ! is_array( $input ) || ! isset( $input['email_settings_present'] ) || ! self::checked( $input['email_settings_present'] ) ) { return $result; }
		$invalid = false;
		foreach ( array( 'email_method' => array( 'wordpress', 'smtp' ), 'smtp_encryption' => array( 'tls', 'ssl' ) ) as $key => $allowed ) {
			if ( array_key_exists( $key, $input ) ) {
				if ( in_array( $input[ $key ], $allowed, true ) ) { $result[ $key ] = $input[ $key ]; }
				else { $invalid = true; }
			}
		}
		foreach ( array( 'email_from' => 254, 'email_from_name' => 600, 'smtp_username' => 320, 'smtp_host' => 253 ) as $key => $max ) {
			if ( ! array_key_exists( $key, $input ) ) { continue; }
			$value = $input[ $key ];
			$valid = self::clean_line( $value, $max );
			if ( $valid ) { $value = trim( $value ); }
			if ( $valid && 'email_from' === $key && '' !== $value ) { $valid = (bool) is_email( $value ); }
			if ( $valid && 'smtp_host' === $key && '' !== $value ) { $valid = self::valid_host( $value ); }
			if ( $valid ) { $result[ $key ] = 'email_from_name' === $key ? sanitize_text_field( $value ) : $value; }
			else { $invalid = true; }
		}
		if ( array_key_exists( 'smtp_port', $input ) ) {
			$port = $input['smtp_port'];
			if ( self::valid_port( $port ) ) { $result['smtp_port'] = (int) $port; }
			else { $invalid = true; }
		}
		foreach ( array( 'smtp_auth', 'email_log_enabled' ) as $key ) {
			$result[ $key ] = isset( $input[ $key ] ) && self::checked( $input[ $key ] );
		}
		if ( isset( $input['delete_smtp_password'] ) && self::checked( $input['delete_smtp_password'] ) ) {
			$result['smtp_password'] = '';
		} elseif ( ! defined( 'TSCB_SMTP_PASSWORD' ) && isset( $input['smtp_password'] ) && '' !== $input['smtp_password'] ) {
			if ( ! is_string( $input['smtp_password'] ) || strlen( $input['smtp_password'] ) > 4096 || false !== strpos( $input['smtp_password'], "\0" ) ) {
				$invalid = true;
			} else {
				$encrypted = self::encrypt( $input['smtp_password'] );
				if ( is_wp_error( $encrypted ) ) {
					if ( function_exists( 'add_settings_error' ) ) { add_settings_error( 'tscb_settings', 'mail_password', __( 'The SMTP password could not be encrypted. The previously stored password was kept. Enable OpenSSL with AES-256-GCM support or use TSCB_SMTP_PASSWORD.', 'tsambasis-contact-bridge' ) ); }
				} else { $result['smtp_password'] = $encrypted; }
			}
		}
		if ( $invalid && function_exists( 'add_settings_error' ) ) {
			add_settings_error( 'tscb_settings', 'mail_configuration', __( 'Some email settings were invalid and were kept at their previous values. Use a single SMTP hostname or IP address, a valid port, TLS or SSL, and values without control characters.', 'tsambasis-contact-bridge' ) );
		}
		if ( ! $result['email_log_enabled'] ) { self::clear_log(); }
		return $result;
	}

	private static function crypto_available() {
		return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' ) && in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true );
	}

	/** Store an authenticated ciphertext; an encryption failure never stores plaintext. */
	private static function encrypt( $password ) {
		if ( ! self::crypto_available() ) { return self::error( 'mail_password' ); }
		try {
			$iv = random_bytes( 12 ); $tag = '';
			$ciphertext = openssl_encrypt( $password, 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, $iv, $tag, 'tsambasis-contact-bridge/smtp-password/v1', 16 );
			if ( false === $ciphertext || 16 !== strlen( $tag ) ) { return self::error( 'mail_password' ); }
			return 'tscb1:' . base64_encode( $iv . $tag . $ciphertext );
		} catch ( Throwable $error ) { return self::error( 'mail_password' ); }
	}

	/** Plaintext is returned only to the server-side transport, never to settings HTML. */
	public static function password( $settings ) {
		if ( defined( 'TSCB_SMTP_PASSWORD' ) ) {
			$value = constant( 'TSCB_SMTP_PASSWORD' );
			return is_string( $value ) && strlen( $value ) <= 4096 && false === strpos( $value, "\0" ) ? $value : self::error( 'mail_password' );
		}
		$value = isset( $settings['smtp_password'] ) ? $settings['smtp_password'] : '';
		if ( '' === $value ) { return ''; }
		if ( ! is_string( $value ) || strlen( $value ) > 5600 || 0 !== strpos( $value, 'tscb1:' ) || ! self::crypto_available() ) { return self::error( 'mail_password' ); }
		$payload = base64_decode( substr( $value, 6 ), true );
		if ( false === $payload || strlen( $payload ) <= 28 || strlen( $payload ) > 4124 ) { return self::error( 'mail_password' ); }
		try {
			$password = openssl_decrypt( substr( $payload, 28 ), 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, substr( $payload, 0, 12 ), substr( $payload, 12, 16 ), 'tsambasis-contact-bridge/smtp-password/v1' );
			return false === $password ? self::error( 'mail_password' ) : $password;
		} catch ( Throwable $error ) { return self::error( 'mail_password' ); }
	}

	/** All persisted and user-facing error codes are drawn from this fixed vocabulary. */
	private static function diagnostics() {
		return array(
			'mail_accepted' => __( 'WordPress accepted this message for sending. This does not confirm inbox delivery.', 'tsambasis-contact-bridge' ),
			'mail_delegated' => __( 'Another WordPress mail handler accepted the message before this plugin\'s mailer ran. Check that handler\'s delivery logs; any SMTP settings in this plugin were not used.', 'tsambasis-contact-bridge' ),
			'mail_auth' => __( 'SMTP login was rejected. Check the username, password or app password, and whether SMTP access is enabled.', 'tsambasis-contact-bridge' ),
			'mail_connect' => __( 'The SMTP server could not be reached. Check the hostname, port and hosting firewall.', 'tsambasis-contact-bridge' ),
			'mail_tls' => __( 'A secure SMTP connection could not be established. Check encryption, port and the server certificate; certificate checks remain enabled.', 'tsambasis-contact-bridge' ),
			'mail_sender' => __( 'The sender address was rejected. Use an address permitted by your mail provider and check its domain authentication.', 'tsambasis-contact-bridge' ),
			'mail_recipient' => __( 'The recipient address was rejected. Check the destination mailbox and the provider\'s sending rules.', 'tsambasis-contact-bridge' ),
			'mail_blocked' => __( 'Another WordPress component stopped this email before sending. Check other mail or security plugins.', 'tsambasis-contact-bridge' ),
			'mail_unavailable' => __( 'The WordPress mail function is unavailable. Configure authenticated SMTP or ask your hosting provider to enable outgoing email.', 'tsambasis-contact-bridge' ),
			'mail_configuration' => __( 'The email settings are incomplete or invalid. SMTP requires a hostname, port, encryption, an explicit sender address and, when enabled, login credentials.', 'tsambasis-contact-bridge' ),
			'mail_password' => __( 'The stored SMTP password could not be read securely. Save it again or define TSCB_SMTP_PASSWORD; check that OpenSSL and the WordPress authentication salts are available.', 'tsambasis-contact-bridge' ),
			'mail_generic' => __( 'The email could not be handed to the mail service. Check the sending configuration and run another test.', 'tsambasis-contact-bridge' ),
		);
	}

	public static function diagnostic( $code ) {
		$messages = self::diagnostics();
		return is_string( $code ) && isset( $messages[ $code ] ) ? $messages[ $code ] : $messages['mail_generic'];
	}

	private static function error( $code ) { return new WP_Error( $code, self::diagnostic( $code ) ); }

	/** Return only sanitized recent metadata, even if another component altered the transient. */
	public static function log_entries() {
		$stored = get_transient( 'tscb_mail_log' ); $entries = array(); $codes = self::diagnostics(); $now = time();
		if ( ! is_array( $stored ) ) {
			if ( false !== $stored ) { self::clear_log(); }
			return $entries;
		}
		foreach ( array_slice( $stored, 0, 50 ) as $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['time'], $entry['code'], $entry['ok'], $entry['method'], $entry['context'] ) || ! is_int( $entry['time'] ) || $entry['time'] < $now - 7 * DAY_IN_SECONDS || $entry['time'] > $now + 60 || ! is_string( $entry['code'] ) || ! isset( $codes[ $entry['code'] ] ) || ! is_bool( $entry['ok'] ) || ! in_array( $entry['method'], array( 'wordpress', 'smtp' ), true ) || ! in_array( $entry['context'], array( 'form', 'test' ), true ) ) { continue; }
			$safe = array( 'time' => $entry['time'], 'ok' => $entry['ok'], 'code' => $entry['code'], 'method' => $entry['method'], 'context' => $entry['context'] );
			if ( isset( $entry['smtp_code'] ) && is_int( $entry['smtp_code'] ) && $entry['smtp_code'] >= 100 && $entry['smtp_code'] <= 599 ) { $safe['smtp_code'] = $entry['smtp_code']; }
			$entries[] = $safe;
		}
		// Reading and the hourly plugin cleanup remove expired rows without discarding newer entries.
		if ( $entries !== $stored ) {
			if ( $entries ) { set_transient( 'tscb_mail_log', $entries, 7 * DAY_IN_SECONDS ); }
			else { self::clear_log(); }
		}
		return $entries;
	}

	/** Request authorization belongs to the caller, not this internal lifecycle helper. */
	public static function clear_log() { return delete_transient( 'tscb_mail_log' ); }

	private static function result( $ok, $code, $settings, $context, $smtp_code = 0 ) {
		if ( ! empty( $settings['email_log_enabled'] ) ) {
			$entry = array( 'time' => time(), 'ok' => (bool) $ok, 'code' => $code, 'method' => 'smtp' === $settings['email_method'] ? 'smtp' : 'wordpress', 'context' => 'test' === $context ? 'test' : 'form' );
			if ( $smtp_code >= 100 && $smtp_code <= 599 ) { $entry['smtp_code'] = (int) $smtp_code; }
			$entries = self::log_entries(); array_unshift( $entries, $entry );
			set_transient( 'tscb_mail_log', array_slice( $entries, 0, 50 ), 7 * DAY_IN_SECONDS );
		} else { self::clear_log(); }
		return $ok ? true : self::error( $code );
	}

	private static function same_message( $data, $own ) {
		return is_array( $data ) && is_array( $own ) && isset( $data['subject'], $data['message'], $own['subject'], $own['message'] ) && $data['subject'] === $own['subject'] && $data['message'] === $own['message'] && isset( $data['to'], $own['to'] ) && (array) $data['to'] === (array) $own['to'];
	}

	/** Classify transient error text, without returning or persisting any of that text. */
	private static function classify( $text, $mailer, &$smtp_code ) {
		$translations = array();
		try {
			if ( is_object( $mailer ) ) {
				$text .= ' ' . (string) $mailer->ErrorInfo;
				$translations = $mailer->getTranslations();
				$error = $mailer->getSMTPInstance()->getError();
				if ( isset( $error['smtp_code'] ) && preg_match( '/^[1-5][0-9]{2}$/D', (string) $error['smtp_code'] ) ) { $smtp_code = (int) $error['smtp_code']; }
				foreach ( array( 'error', 'detail' ) as $key ) { if ( isset( $error[ $key ] ) && is_string( $error[ $key ] ) ) { $text .= ' ' . $error[ $key ]; } }
			}
		} catch ( Throwable $ignored ) { /* A diagnostic must not hide the original failure. */ }
		$text = strtolower( substr( $text, 0, 8192 ) );
		if ( preg_match( '/certificate|certificat|zertifikat|starttls|\btls\b|\bssl\b|crypto|peer.verify/', $text ) ) { return 'mail_tls'; }
		if ( in_array( $smtp_code, array( 530, 534, 535, 538 ), true ) || preg_match( '/authenticat|authentifiz|credentials|password|passwort|\b535\b/', $text ) ) { return 'mail_auth'; }
		foreach ( array( 'authenticate' => 'mail_auth', 'from_failed' => 'mail_sender', 'recipients_failed' => 'mail_recipient', 'instantiate' => 'mail_unavailable', 'execute' => 'mail_unavailable', 'connect_host' => 'mail_connect', 'smtp_connect_failed' => 'mail_connect' ) as $key => $code ) {
			if ( isset( $translations[ $key ] ) && '' !== trim( $translations[ $key ] ) && false !== strpos( $text, strtolower( trim( $translations[ $key ] ) ) ) ) { return $code; }
		}
		if ( preg_match( '/sender|from.address|mail.from|absender|invalid.address.*\(from\)/', $text ) ) { return 'mail_sender'; }
		if ( preg_match( '/recipient|rcpt.to|empfänger|empfaenger|invalid.address.*\(to\)/', $text ) ) { return 'mail_recipient'; }
		if ( preg_match( '/mail.function|mail\(\)|instantiate|sendmail|undefined.function|mail.funktion|could.not.execute/', $text ) ) { return 'mail_unavailable'; }
		if ( preg_match( '/connect|verbindung|timed.out|resolve|network|dns|unreachable/', $text ) ) { return 'mail_connect'; }
		return 'mail_generic';
	}

	/** Send one plugin email while restoring the complete previous global mailer afterwards. */
	public static function send( $to, $subject, $body, $headers, $settings, $context = 'form' ) {
		$settings = array_merge( self::defaults(), is_array( $settings ) ? $settings : array() );
		if ( ! is_string( $to ) || ! self::clean_line( $to, 254 ) || ! is_email( $to ) ) { return self::result( false, 'mail_recipient', $settings, $context ); }
		if ( ! in_array( $settings['email_method'], array( 'wordpress', 'smtp' ), true ) || ! self::clean_line( $settings['email_from'], 254 ) || ( '' !== $settings['email_from'] && ! is_email( $settings['email_from'] ) ) || ! self::clean_line( $settings['email_from_name'], 600 ) ) { return self::result( false, 'mail_sender', $settings, $context ); }
		$smtp = 'smtp' === $settings['email_method']; $password = '';
		if ( $smtp ) {
			if ( ! self::valid_host( $settings['smtp_host'] ) || ! self::valid_port( $settings['smtp_port'] ) || ! in_array( $settings['smtp_encryption'], array( 'tls', 'ssl' ), true ) || '' === $settings['email_from'] || ! self::clean_line( $settings['smtp_username'], 320 ) ) { return self::result( false, 'mail_configuration', $settings, $context ); }
			if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'stream_socket_enable_crypto' ) ) { return self::result( false, 'mail_tls', $settings, $context ); }
			if ( ! empty( $settings['smtp_auth'] ) ) {
				$password = self::password( $settings );
				if ( is_wp_error( $password ) ) { return self::result( false, 'mail_password', $settings, $context ); }
				if ( '' === $password || '' === $settings['smtp_username'] ) { return self::result( false, 'mail_configuration', $settings, $context ); }
			}
		}
		if ( ! function_exists( 'wp_mail' ) ) { return self::result( false, 'mail_unavailable', $settings, $context ); }
		$headers = is_array( $headers ) ? $headers : explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
		if ( '' !== $settings['email_from'] ) { $headers[] = 'From: ' . $settings['email_from']; }
		$had_global = array_key_exists( 'phpmailer', $GLOBALS );
		$previous = $had_global ? $GLOBALS['phpmailer'] : null;
		$validator = null; $validator_saved = false; $fresh = null; $active = null;
		$token = wp_generate_uuid4(); $marked = false; $own = null; $preempted = null; $failure = ''; $sent = false; $smtp_code = 0;
		$mark = static function ( $atts ) use ( &$marked, $token, $to, $subject, $body ) {
			if ( ! $marked && self::same_message( $atts, array( 'to' => $to, 'subject' => $subject, 'message' => $body ) ) ) { $atts['_tscb_scope'] = $token; $marked = true; }
			return $atts;
		};
		$observe = static function ( $pre, $atts ) use ( $token, &$own, &$preempted ) {
			if ( isset( $atts['_tscb_scope'] ) && $token === $atts['_tscb_scope'] ) { $own = $atts; $preempted = $pre; }
			return $pre;
		};
		$failed = static function ( $error ) use ( &$own, &$failure ) {
			if ( is_wp_error( $error ) && self::same_message( $error->get_error_data(), $own ) ) { $failure = implode( ' ', $error->get_error_messages() ); }
		};
		$configure = static function ( $mailer ) use ( &$own, &$active, $settings, $smtp, $password ) {
			if ( ! is_array( $own ) || $mailer->Subject !== $own['subject'] || $mailer->Body !== $own['message'] ) { return; }
			$active = $mailer;
			$mailer->SMTPDebug = 0; $mailer->SMTPKeepAlive = false; $mailer->Timeout = 12;
			$mailer->Debugoutput = static function () {};
			if ( '' !== $settings['email_from'] ) { $mailer->setFrom( $settings['email_from'], '' !== $settings['email_from_name'] ? $settings['email_from_name'] : $mailer->FromName, false ); }
			elseif ( '' !== $settings['email_from_name'] ) { $mailer->FromName = $settings['email_from_name']; }
			if ( $smtp ) {
				$mailer->isSMTP();
				$mailer->AuthType = ''; $mailer->Sender = $settings['email_from'];
				$mailer->Host = filter_var( $settings['smtp_host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? '[' . $settings['smtp_host'] . ']' : $settings['smtp_host'];
				$mailer->Port = (int) $settings['smtp_port']; $mailer->SMTPSecure = $settings['smtp_encryption']; $mailer->SMTPAutoTLS = false;
				$mailer->SMTPAuth = ! empty( $settings['smtp_auth'] ); $mailer->Username = $mailer->SMTPAuth ? $settings['smtp_username'] : ''; $mailer->Password = $mailer->SMTPAuth ? $password : '';
				$mailer->SMTPOptions = array( 'ssl' => array( 'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false ) );
				$mailer->getSMTPInstance()->Timeout = 12; $mailer->getSMTPInstance()->Timelimit = 12;
			}
		};
		try {
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			if ( is_readable( ABSPATH . WPINC . '/class-wp-phpmailer.php' ) ) { require_once ABSPATH . WPINC . '/class-wp-phpmailer.php'; }
			$validator = \PHPMailer\PHPMailer\PHPMailer::$validator; $validator_saved = true;
			\PHPMailer\PHPMailer\PHPMailer::$validator = static function ( $email ) { return (bool) is_email( $email ); };
			$fresh = class_exists( 'WP_PHPMailer' ) ? new WP_PHPMailer( true ) : new \PHPMailer\PHPMailer\PHPMailer( true );
			$GLOBALS['phpmailer'] = $fresh;
			add_filter( 'wp_mail', $mark, PHP_INT_MIN );
			add_filter( 'pre_wp_mail', $observe, PHP_INT_MAX, 2 );
			add_action( 'wp_mail_failed', $failed, PHP_INT_MAX );
			add_action( 'phpmailer_init', $configure, PHP_INT_MAX );
			$sent = true === wp_mail( $to, $subject, $body, $headers );
		} catch ( Throwable $error ) { $failure = $error->getMessage(); }
		finally {
			$code = $sent ? ( null !== $preempted || ! $active ? 'mail_delegated' : 'mail_accepted' ) : ( false === $preempted && '' === $failure ? 'mail_blocked' : self::classify( $failure, $active ? $active : $fresh, $smtp_code ) );
			try {
				// Capture diagnostics before QUIT, which may replace the last SMTP error.
				if ( $active ) { $active->smtpClose(); }
				if ( $fresh && $fresh !== $active ) { $fresh->smtpClose(); }
			} catch ( Throwable $ignored ) { /* Cleanup must never prevent restoring the previous global. */ }
			remove_filter( 'wp_mail', $mark, PHP_INT_MIN ); remove_filter( 'pre_wp_mail', $observe, PHP_INT_MAX );
			remove_action( 'wp_mail_failed', $failed, PHP_INT_MAX ); remove_action( 'phpmailer_init', $configure, PHP_INT_MAX );
			if ( $validator_saved ) { \PHPMailer\PHPMailer\PHPMailer::$validator = $validator; }
			if ( $had_global ) { $GLOBALS['phpmailer'] = $previous; } else { unset( $GLOBALS['phpmailer'] ); }
		}
		return self::result( $sent, $code, $settings, $context, $smtp_code );
	}
}
