<?php
/** Public submission handling and abuse controls. @package ContactBridge */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSCB_Submission {
	public static function init() {
		foreach ( array( 'admin_post_tscb_submit', 'admin_post_nopriv_tscb_submit', 'wp_ajax_tscb_submit', 'wp_ajax_nopriv_tscb_submit' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'handle' ) );
		}
		add_action( 'wp_ajax_tscb_nonce', array( __CLASS__, 'nonce' ) );
		add_action( 'wp_ajax_nopriv_tscb_nonce', array( __CLASS__, 'nonce' ) );
		add_action( 'tscb_cleanup', array( __CLASS__, 'cleanup' ) );
	}

	/** Public, non-authorizing form nonce; AJAX response must not be page-cached. */
	public static function nonce() {
		nocache_headers();
		wp_send_json_success( array( 'nonce' => wp_create_nonce( 'tscb_submit' ) ) );
	}

	public static function length( $text ) {
		return preg_match_all( '/./us', $text, $unused );
	}

	public static function cut( $text, $limit ) {
		preg_match( '/^.{0,' . absint( $limit ) . '}/us', $text, $match );
		return isset( $match[0] ) ? $match[0] : '';
	}

	private static function value( $input, $key ) {
		return isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? $input[ $key ] : '';
	}

	private static function error( $code, $message, $fields = array() ) {
		return new WP_Error( $code, $message, array( 'fields' => $fields ) );
	}

	/** Testable entry point; caller supplies already-unslashed input. */
	public static function process( $input ) {
		if ( ! wp_verify_nonce( self::value( $input, 'tscb_nonce' ), 'tscb_submit' ) ) {
			return self::error( 'expired', __( 'The form has expired. Please reload the page and try again.', 'tsambasis-contact-bridge' ) );
		}
		if ( '' !== self::value( $input, 'tscb_company' ) ) {
			return self::error( 'spam', __( 'The message could not be accepted.', 'tsambasis-contact-bridge' ) );
		}
		$started = absint( self::value( $input, 'tscb_started' ) );
		if ( $started > time() - 2 || 0 === $started ) {
			return self::error( 'spam', __( 'Please wait a moment and submit again.', 'tsambasis-contact-bridge' ) );
		}
		$id = self::value( $input, 'tscb_id' );
		if ( ! preg_match( '/^[a-f0-9-]{36}$/Di', $id ) ) {
			return self::error( 'invalid', __( 'Please reload the page.', 'tsambasis-contact-bridge' ) );
		}
		$settings = TSCB_Settings::get();
		$data = TSCB_Fields::validate( $input, $settings );
		if ( is_wp_error( $data ) ) { return $data; }
		$fields = array();
		if ( $settings['require_consent'] && '1' !== self::value( $input, 'tscb_consent' ) ) {
			$fields['tscb_consent'] = __( 'Please confirm the privacy notice.', 'tsambasis-contact-bridge' );
		}
		if ( $fields ) {
			return self::error( 'invalid', __( 'Please check the highlighted details.', 'tsambasis-contact-bridge' ), $fields );
		}
		$channels = array_values( array_intersect( array( 'email', 'telegram', 'whatsapp' ), $settings['channels'] ) );
		if ( ! $channels ) {
			return self::error( 'unconfigured', __( 'The contact form has not been set up yet. Please use another way to get in touch.', 'tsambasis-contact-bridge' ) );
		}
		$address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = hash_hmac( 'sha256', $address . $id . wp_json_encode( $data ), wp_salt( 'nonce' ) );
		if ( get_transient( 'tscb_done_' . $key ) ) {
			return true;
		}
		// REMOTE_ADDR only: visitor-supplied forwarded headers must not bypass the limit.
		$client = hash_hmac( 'sha256', $address, wp_salt( 'auth' ) );
		$client_lock = self::lock( $client );
		if ( ! $client_lock ) {
			return self::error( 'rate', __( 'A request is already being processed. Please wait a moment.', 'tsambasis-contact-bridge' ) );
		}
		try {
			if ( get_transient( 'tscb_done_' . $key ) ) {
				return true;
			}
			$rate_key = 'tscb_rate_' . $client;
			$rate = get_transient( $rate_key );
			if ( ! is_array( $rate ) || $rate['until'] <= time() ) {
				$rate = array( 'count' => 0, 'until' => time() + 10 * MINUTE_IN_SECONDS );
			}
			if ( $rate['count'] >= 5 ) {
				return self::error( 'rate', __( 'Too many requests. Please try again in ten minutes.', 'tsambasis-contact-bridge' ) );
			}
			++$rate['count'];
			set_transient( $rate_key, $rate, max( 1, $rate['until'] - time() ) );
			$message_lock = self::lock( $key );
			if ( ! $message_lock ) {
				return self::error( 'rate', __( 'This message is already being processed. Please wait a moment.', 'tsambasis-contact-bridge' ) );
			}
			try {
				$accepted = false;
				foreach ( $channels as $channel ) {
					if ( ! empty( $settings[ 'privacy_' . $channel ] ) ) {
						try {
							// The HMAC is computed here and never accepted from submitted data.
							$inquiry_id = class_exists( 'TSCB_Inbox' ) ? TSCB_Inbox::create( $data, $settings, $key ) : false;
						} catch ( Throwable $error ) {
							$inquiry_id = false;
						}
						if ( ! is_int( $inquiry_id ) || $inquiry_id < 1 ) {
							$result = self::error( 'failed', __( 'The inquiry could not be stored securely. Please try again later or use another way to get in touch.', 'tsambasis-contact-bridge' ) );
							foreach ( $channels as $blocked_channel ) {
								TSCB_Transports::status( $blocked_channel, new WP_Error( 'privacy_storage', __( 'The inquiry could not be stored securely. No notification was sent.', 'tsambasis-contact-bridge' ) ) );
							}
							return $result;
						}
						$data['_inquiry_id'] = $inquiry_id;
						$accepted = true;
						// A stored inquiry is accepted even if its notifications fail or the worker stops.
						set_transient( 'tscb_done_' . $key, 1, HOUR_IN_SECONDS );
						break;
					}
				}
				foreach ( $channels as $channel ) {
					try {
						$result = TSCB_Transports::send( $channel, $data, $settings );
					} catch ( Throwable $error ) {
						$result = new WP_Error( 'delivery_failed', __( 'The notification could not be sent. Please check the delivery settings.', 'tsambasis-contact-bridge' ) );
					}
					TSCB_Transports::status( $channel, $result );
					$accepted = $accepted || ! is_wp_error( $result );
				}
				if ( $accepted ) {
					set_transient( 'tscb_done_' . $key, 1, HOUR_IN_SECONDS );
					return true;
				}
				return self::error( 'failed', __( 'The message could not be passed to a delivery service right now. Please try again later or use another way to get in touch.', 'tsambasis-contact-bridge' ) );
			} finally {
				self::unlock( $key, $message_lock );
			}
		} finally {
			self::unlock( $client, $client_lock );
		}
	}

	/** Acquire one owner token, atomically replacing only the observed expired value. */
	private static function lock( $key ) {
		global $wpdb;
		$name = 'tscb_lock_' . $key;
		$existing = get_option( $name );
		$owner = ( time() + 120 ) . ':' . wp_generate_uuid4();
		if ( false === $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- add_option may update on duplicate keys; INSERT IGNORE is the atomic insert-if-absent operation required for a lock.
			$inserted = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')", $name, $owner ) );
			self::clear_lock_cache( $name );
			return 1 === $inserted ? $owner : false;
		}
		if ( ! is_scalar( $existing ) || (int) $existing >= time() ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Compare-and-swap is required: separate delete/add calls permit two expired-lock owners. Invalidate the option cache below.
		$changed = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $owner, $name, (string) $existing ) );
		self::clear_lock_cache( $name );
		return 1 === $changed ? $owner : false;
	}

	/** A stale worker must never release a newer worker's lock. */
	private static function unlock( $key, $owner ) {
		self::delete_owned_lock( 'tscb_lock_' . $key, $owner );
	}

	/** Delete only the exact lock value that was observed or acquired. */
	private static function delete_owned_lock( $name, $owner ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Conditional deletion preserves a replacement lock; delete_option cannot provide that atomic condition.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, (string) $owner ) );
		self::clear_lock_cache( $name );
	}

	/** Direct atomic writes must invalidate both positive and negative option caches. */
	private static function clear_lock_cache( $name ) {
		wp_cache_delete( $name, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/** Clear only expired plugin locks, never message content or unrelated options. */
	public static function cleanup() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Read bounded expired values; conditional deletion below protects locks renewed after this query.
		$locks = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d LIMIT 1000", $wpdb->esc_like( 'tscb_lock_' ) . '%', time() ), ARRAY_A );
		foreach ( $locks as $lock ) {
			self::delete_owned_lock( $lock['option_name'], $lock['option_value'] );
		}
	}

	public static function handle() {
		nocache_headers();
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_die( esc_html__( 'Please submit the form.', 'tsambasis-contact-bridge' ), '', array( 'response' => 405 ) );
		}
		// Nonce and every consumed field are validated inside process().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validation boundary is process(), not the untrusted request read.
		$input = wp_unslash( $_POST );
		$result = self::process( $input );
		if ( wp_doing_ajax() ) {
			if ( is_wp_error( $result ) ) {
				$details = $result->get_error_data();
				wp_send_json_error( array( 'code' => $result->get_error_code(), 'message' => $result->get_error_message(), 'fields' => $details['fields'] ), 'rate' === $result->get_error_code() ? 429 : 400 );
			}
			$settings = TSCB_Settings::get();
			wp_send_json_success( array( 'message' => $settings['success_message'] ) );
		}
		$return = esc_url_raw( self::value( $input, 'tscb_return' ) );
		// Constrain to the site's origin even if another plugin extends allowed_redirect_hosts.
		if ( wp_parse_url( $return, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			$return = home_url( '/' );
		}
		$return = wp_validate_redirect( $return, home_url( '/' ) );
		wp_safe_redirect( add_query_arg( 'tscb_status', is_wp_error( $result ) ? $result->get_error_code() : 'ok', $return ), 303 );
		exit;
	}
}
