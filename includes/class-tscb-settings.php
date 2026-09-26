<?php
/**
 * Administrator settings for ContactBridge.
 *
 * @package ContactBridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Manage plugin settings without exposing saved credentials in the browser. */
class TSCB_Settings {
	/** Register settings hooks. */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Return default options.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array_merge( array(
			'language'           => 'de_DE',
			'_text_defaults'     => TSCB_I18n::factory_texts(),
			'channels'           => array( 'email' ),
			'email_to'           => get_option( 'admin_email', '' ),
			'privacy_email'      => false,
			'privacy_telegram'   => false,
			'privacy_whatsapp'   => false,
			'inbox_retention_days' => 30,
			'telegram_token'     => '',
			'telegram_chat_id'   => '',
			'whatsapp_token'     => '',
			'whatsapp_phone_id'  => '',
			'whatsapp_to'        => '',
			'whatsapp_version'   => 'v24.0',
			'whatsapp_template'  => '',
			'whatsapp_privacy_template' => '',
			'whatsapp_language'  => 'de',
			'theme'              => 'light',
			'shape'              => 'rounded',
			'accent'             => '#22624b',
			'heading'            => __( 'Let\'s get in touch.', 'tsambasis-contact-bridge' ),
			'intro'              => __( 'Send us a message. We look forward to hearing from you.', 'tsambasis-contact-bridge' ),
			'submit_label'       => __( 'Send message', 'tsambasis-contact-bridge' ),
			'success_message'    => __( 'Thank you! Your inquiry has been received.', 'tsambasis-contact-bridge' ),
			'privacy_label'      => __( 'I have read the privacy notice and agree to my information being processed to respond to my inquiry.', 'tsambasis-contact-bridge' ),
			'privacy_url'        => get_privacy_policy_url(),
			'require_consent'    => true,
			'delete_data'        => false,
		), TSCB_Design::defaults(), TSCB_Mail::defaults() );
	}

	/**
	 * Return stored options merged with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$options = get_option( 'tscb_settings', array() );
		return TSCB_I18n::resolve_texts( is_array( $options ) ? $options : array(), self::defaults() );
	}

	/** Add the settings page. */
	public static function menu() {
		add_options_page(
			__( 'ContactBridge', 'tsambasis-contact-bridge' ),
			__( 'ContactBridge', 'tsambasis-contact-bridge' ),
			'manage_options',
			'tsambasis-contact-bridge',
			array( __CLASS__, 'render' )
		);
	}

	/** Register the single Settings API option. */
	public static function register() {
		register_setting(
			'tscb_settings_group',
			'tscb_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Enqueue assets only on our settings page.
	 *
	 * @param string $hook Current screen hook.
	 */
	public static function assets( $hook ) {
		if ( 'settings_page_tsambasis-contact-bridge' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'tscb-admin', TSCB_URL . 'assets/admin.css', array(), TSCB_VERSION );
		wp_enqueue_script( 'tscb-admin', TSCB_URL . 'assets/admin.js', array(), TSCB_VERSION, true );
		$settings = self::get();
		wp_localize_script( 'tscb-admin', 'tscbAdmin', array(
			'url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'tscb_preview' ),
			'keys' => TSCB_Design::keys(),
			'presets' => TSCB_Design::presets(),
			'updating' => __( 'Updating preview …', 'tsambasis-contact-bridge' ),
			'updated' => __( 'Preview up to date · changes not saved yet', 'tsambasis-contact-bridge' ),
			'ready' => __( 'Preview up to date · settings saved', 'tsambasis-contact-bridge' ),
			'error' => __( 'The preview could not be updated. Your changes are still here. Please try again.', 'tsambasis-contact-bridge' ),
			'unsaved' => __( 'Unsaved changes', 'tsambasis-contact-bridge' ),
			'custom' => __( 'Custom design', 'tsambasis-contact-bridge' ),
			'fields' => TSCB_Fields::get( $settings ),
			'rawFields' => isset( $settings['fields'] ) && is_array( $settings['fields'] ) ? $settings['fields'] : array(),
			'fieldTypes' => TSCB_Fields::type_labels(),
			'fieldStrings' => array(
				/* translators: %1$d: current field count; %2$d: maximum field count. */
				'count' => __( '%1$d of %2$d fields', 'tsambasis-contact-bridge' ),
				/* translators: %1$d: position; %2$s: field label. */
				'row' => __( 'Field %1$d: %2$s', 'tsambasis-contact-bridge' ),
				/* translators: %s: field label. */
				'up' => __( 'Move %s up', 'tsambasis-contact-bridge' ),
				/* translators: %s: field label. */
				'down' => __( 'Move %s down', 'tsambasis-contact-bridge' ),
				/* translators: %s: field label. */
				'remove' => __( 'Remove %s', 'tsambasis-contact-bridge' ),
				'empty' => __( 'Add at least one field before saving.', 'tsambasis-contact-bridge' ),
				'limit' => __( 'You can add up to 20 fields.', 'tsambasis-contact-bridge' ),
				'choices' => __( 'Enter between 1 and 20 choices, one per line.', 'tsambasis-contact-bridge' ),
				'removed' => __( 'Field removed. You can undo this change.', 'tsambasis-contact-bridge' ),
				'restored' => __( 'Field restored.', 'tsambasis-contact-bridge' ),
				'added' => __( 'Field added.', 'tsambasis-contact-bridge' ),
				'moved' => __( 'Field order updated.', 'tsambasis-contact-bridge' ),
				'optionOne' => __( 'Option 1', 'tsambasis-contact-bridge' ),
				'optionTwo' => __( 'Option 2', 'tsambasis-contact-bridge' ),
			),
		) );
	}

	/**
	 * Safely extract one text value from potentially malformed input.
	 *
	 * @param array  $input  Submitted options.
	 * @param string $key    Field name.
	 * @param int    $limit  Maximum character count.
	 * @param bool   $multi  Whether newlines are allowed.
	 * @return string
	 */
	private static function text( $input, $key, $limit = 200, $multi = false ) {
		if ( ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
			return '';
		}
		$value = $multi ? sanitize_textarea_field( (string) $input[ $key ] ) : sanitize_text_field( (string) $input[ $key ] );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $limit ) : substr( $value, 0, $limit );
	}

	/**
	 * Whether a submitted checkbox has the expected value.
	 *
	 * @param array  $input Submitted options.
	 * @param string $key   Field name.
	 * @return bool
	 */
	private static function checked_value( $input, $key ) {
		return isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) && '1' === (string) $input[ $key ];
	}

	/** Only the explicit privacy editor may replace privacy and retention settings. */
	private static function privacy_settings( $input, $previous ) {
		$keys = array( 'privacy_email', 'privacy_telegram', 'privacy_whatsapp', 'whatsapp_privacy_template', 'inbox_retention_days' );
		$result = array_intersect_key( $previous, array_flip( $keys ) );
		if ( ! isset( $input['privacy_settings_present'] ) || ! in_array( $input['privacy_settings_present'], array( true, 1, '1' ), true ) ) {
			return $result;
		}
		$invalid = false;
		foreach ( array( 'privacy_email', 'privacy_telegram', 'privacy_whatsapp' ) as $key ) {
			if ( ! array_key_exists( $key, $input ) ) {
				$result[ $key ] = false;
			} elseif ( in_array( $input[ $key ], array( true, false, 1, 0, '1', '0' ), true ) ) {
				$result[ $key ] = in_array( $input[ $key ], array( true, 1, '1' ), true );
			} else {
				$invalid = true;
			}
		}
		if ( array_key_exists( 'whatsapp_privacy_template', $input ) ) {
			$template = $input['whatsapp_privacy_template'];
			if ( is_string( $template ) && strlen( $template ) <= 512 && ( '' === $template || preg_match( '/^[a-z0-9_]{1,512}$/D', $template ) ) ) {
				$result['whatsapp_privacy_template'] = $template;
			} else {
				$invalid = true;
			}
		}
		if ( array_key_exists( 'inbox_retention_days', $input ) ) {
			$days = $input['inbox_retention_days'];
			if ( ( is_int( $days ) || ( is_string( $days ) && preg_match( '/^[0-9]{1,5}$/D', $days ) ) ) && (int) $days >= 0 && (int) $days <= 36500 ) {
				$result['inbox_retention_days'] = (int) $days;
			} else {
				$invalid = true;
			}
		}
		if ( $invalid ) {
			add_settings_error( 'tscb_settings', 'tscb_privacy_settings', __( 'Some privacy settings were invalid and were kept at their previous values. Use whole retention days from 0 to 36500 and a valid WhatsApp template name.', 'tsambasis-contact-bridge' ) );
		}
		return $result;
	}

	/**
	 * Validate and sanitize Settings API input.
	 *
	 * @param mixed $input Submitted options.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$previous = self::get();
		if ( ! current_user_can( 'manage_options' ) ) {
			return $previous;
		}
		if ( ! is_array( $input ) ) {
			add_settings_error( 'tscb_settings', 'tscb_invalid', __( 'The settings could not be read.', 'tsambasis-contact-bridge' ) );
			return $previous;
		}

		$clean             = self::defaults();
		$clean['language'] = isset( $input['language'] ) && in_array( $input['language'], array( 'de_DE', 'en_US', 'wordpress' ), true ) ? $input['language'] : $previous['language'];
		$clean['channels'] = array();
		if ( isset( $input['channels'] ) && is_array( $input['channels'] ) ) {
			foreach ( $input['channels'] as $channel ) {
				if ( is_string( $channel ) && in_array( $channel, array( 'email', 'telegram', 'whatsapp' ), true ) ) {
					$clean['channels'][] = $channel;
				}
			}
			$clean['channels'] = array_values( array_unique( $clean['channels'] ) );
		}
		if ( empty( $clean['channels'] ) ) {
			add_settings_error( 'tscb_settings', 'tscb_no_channel', __( 'No delivery channel is enabled. The form cannot send messages until a channel is enabled.', 'tsambasis-contact-bridge' ), 'warning' );
		}

		$email             = self::text( $input, 'email_to', 254 );
		$clean['email_to'] = is_email( $email ) ? sanitize_email( $email ) : '';
		if ( '' !== $email && '' === $clean['email_to'] ) {
			$clean['email_to'] = $previous['email_to'];
			add_settings_error( 'tscb_settings', 'tscb_email', __( 'Please enter one valid recipient email address. The previous value has been kept.', 'tsambasis-contact-bridge' ) );
		}

		foreach ( array( 'telegram', 'whatsapp' ) as $service ) {
			$key           = $service . '_token';
			$constant      = 'TSCB_' . strtoupper( $service ) . '_TOKEN';
			$clean[ $key ] = $previous[ $key ];
			if ( defined( $constant ) ) {
				continue;
			}
			if ( self::checked_value( $input, 'clear_' . $key ) ) {
				$clean[ $key ] = '';
				continue;
			}
			$token = self::text( $input, $key, 4096 );
			if ( '' === $token ) {
				continue;
			}
			$valid = 'telegram' === $service ? (bool) preg_match( '/^[0-9]{5,20}:[A-Za-z0-9_-]{20,200}$/D', $token ) : (bool) preg_match( '/^[A-Za-z0-9_.\-]{20,4096}$/D', $token );
			if ( $valid ) {
				$clean[ $key ] = $token;
			} else {
				add_settings_error( 'tscb_settings', 'tscb_' . $key, __( 'An API token has an invalid format. The previous token has been kept.', 'tsambasis-contact-bridge' ) );
			}
		}

		$chat = self::text( $input, 'telegram_chat_id', 100 );
		$clean['telegram_chat_id'] = preg_match( '/^(?:-?[0-9]{1,20}|@[A-Za-z][A-Za-z0-9_]{4,31})$/D', $chat ) ? $chat : '';
		if ( '' !== $chat && '' === $clean['telegram_chat_id'] ) {
			add_settings_error( 'tscb_settings', 'tscb_chat_id', __( 'The Telegram chat ID must be a number (which may start with a minus sign) or a public @channelname.', 'tsambasis-contact-bridge' ) );
		}
		$phone_id = self::text( $input, 'whatsapp_phone_id', 30 );
		$clean['whatsapp_phone_id'] = preg_match( '/^[0-9]{5,30}$/D', $phone_id ) ? $phone_id : '';
		$to = preg_replace( '/[+\s().-]/', '', self::text( $input, 'whatsapp_to', 40 ) );
		$clean['whatsapp_to'] = preg_match( '/^[1-9][0-9]{6,14}$/D', $to ) ? $to : '';
		if ( ( '' !== $phone_id && '' === $clean['whatsapp_phone_id'] ) || ( '' !== $to && '' === $clean['whatsapp_to'] ) ) {
			add_settings_error( 'tscb_settings', 'tscb_whatsapp_numbers', __( 'Please check the WhatsApp Phone Number ID and the recipient\'s number in international format, e.g. 491701234567.', 'tsambasis-contact-bridge' ) );
		}
		$version = self::text( $input, 'whatsapp_version', 10 );
		$clean['whatsapp_version'] = preg_match( '/^v[0-9]{2,3}\.0$/D', $version ) ? $version : 'v24.0';
		$template = self::text( $input, 'whatsapp_template', 512 );
		$clean['whatsapp_template'] = preg_match( '/^[a-z0-9_]{1,512}$/D', $template ) ? $template : '';
		$language = self::text( $input, 'whatsapp_language', 10 );
		$clean['whatsapp_language'] = preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/D', $language ) ? $language : 'de';
		$theme = self::text( $input, 'theme', 10 );
		$clean['theme'] = in_array( $theme, array( 'light', 'dark', 'auto' ), true ) ? $theme : 'light';
		$shape = self::text( $input, 'shape', 10 );
		$clean['shape'] = in_array( $shape, array( 'rounded', 'square' ), true ) ? $shape : 'rounded';
		$accent = sanitize_hex_color( self::text( $input, 'accent', 7 ) );
		$clean['accent'] = $accent ? $accent : '#22624b';
		foreach ( array( 'heading' => 200, 'intro' => 1000, 'submit_label' => 80, 'success_message' => 500, 'privacy_label' => 1000 ) as $key => $limit ) {
			$clean[ $key ] = self::text( $input, $key, $limit, in_array( $key, array( 'intro', 'success_message', 'privacy_label' ), true ) );
		}
		foreach ( array( 'submit_label', 'success_message', 'privacy_label' ) as $required ) {
			if ( '' === $clean[ $required ] ) {
				$clean[ $required ] = self::defaults()[ $required ];
			}
		}
		$clean['privacy_url']     = esc_url_raw( self::text( $input, 'privacy_url', 2048 ), array( 'https', 'http' ) );
		$clean['require_consent'] = self::checked_value( $input, 'require_consent' );
		$clean['delete_data']     = self::checked_value( $input, 'delete_data' );

		$result = array_merge( $clean, TSCB_Design::sanitize( $input, $previous ), TSCB_Mail::sanitize( $input, $previous ), self::privacy_settings( $input, $previous ) );
		// Only an initialized editor explicitly replaces the saved field schema.
		$result['fields'] = $previous['fields'];
		if ( self::checked_value( $input, 'fields_present' ) ) {
			$fields = TSCB_Fields::decode( isset( $input['fields_json'] ) ? $input['fields_json'] : null );
			if ( is_wp_error( $fields ) ) {
				add_settings_error( 'tscb_settings', 'tscb_fields', $fields->get_error_message() );
			} else {
				$result['fields'] = $fields;
			}
		}
		return $result;
	}

	/**
	 * Render a labeled input row.
	 *
	 * @param array  $settings Current settings.
	 * @param string $key      Setting key.
	 * @param string $label    Label.
	 * @param string $help     Optional help text.
	 * @param string $type     Input type or textarea.
	 */
	private static function field( $settings, $key, $label, $help = '', $type = 'text' ) {
		?>
		<div class="tscb-admin-field">
			<label for="tscb-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php if ( 'textarea' === $type ) : ?>
				<textarea class="large-text" id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" rows="3"<?php echo $help ? ' aria-describedby="tscb-help-' . esc_attr( $key ) . '"' : ''; ?>><?php echo esc_textarea( $settings[ $key ] ); ?></textarea>
			<?php else : ?>
				<input class="regular-text" type="<?php echo esc_attr( $type ); ?>" id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>"<?php echo $help ? ' aria-describedby="tscb-help-' . esc_attr( $key ) . '"' : ''; ?> />
			<?php endif; ?>
			<?php if ( $help ) : ?>
				<p class="description" id="tscb-help-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $help ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render an empty password field; stored secrets never enter the HTML.
	 *
	 * @param array  $settings Current settings.
	 * @param string $service  Service key.
	 */
	private static function secret( $settings, $service ) {
		$key      = $service . '_token';
		$constant = 'TSCB_' . strtoupper( $service ) . '_TOKEN';
		?>
		<div class="tscb-admin-field">
			<label for="tscb-<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'API token', 'tsambasis-contact-bridge' ); ?></label>
			<?php if ( defined( $constant ) ) : ?>
				<p class="tscb-credential-status"><?php esc_html_e( 'Set in wp-config.php. Make any changes there.', 'tsambasis-contact-bridge' ); ?> <code><?php echo esc_html( $constant ); ?></code></p>
			<?php else : ?>
				<input class="regular-text" type="password" id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" value="" autocomplete="new-password" spellcheck="false" aria-describedby="tscb-help-<?php echo esc_attr( $key ); ?>" />
				<p class="description" id="tscb-help-<?php echo esc_attr( $key ); ?>"><?php echo empty( $settings[ $key ] ) ? esc_html__( 'No token saved yet.', 'tsambasis-contact-bridge' ) : esc_html__( 'Token saved. Leave this blank to keep it.', 'tsambasis-contact-bridge' ); ?></p>
				<?php if ( ! empty( $settings[ $key ] ) ) : ?>
					<label class="tscb-check"><input type="checkbox" name="tscb_settings[clear_<?php echo esc_attr( $key ); ?>]" value="1" /> <?php esc_html_e( 'Delete the stored token when saving', 'tsambasis-contact-bridge' ); ?></label>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/** Display validated status messages from admin actions. */
	private static function notices() {
		// These query parameters only select fixed presentation strings; they authorize no action.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$result = isset( $_GET['tscb_test_result'] ) && is_string( $_GET['tscb_test_result'] ) ? sanitize_key( wp_unslash( $_GET['tscb_test_result'] ) ) : '';
		$test_channel = isset( $_GET['channel'] ) && is_string( $_GET['channel'] ) ? sanitize_key( wp_unslash( $_GET['channel'] ) ) : '';
		$discovery = isset( $_GET['tscb_discover_result'] ) && is_string( $_GET['tscb_discover_result'] ) ? sanitize_key( wp_unslash( $_GET['tscb_discover_result'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( in_array( $result, array( 'ok', 'failed' ), true ) ) {
			$message = 'ok' === $result ? __( 'The delivery service accepted the test message. Check that the recipient received it.', 'tsambasis-contact-bridge' ) : __( 'The test message could not be handed over to the service. Check your credentials, recipient, and provider setup.', 'tsambasis-contact-bridge' );
			if ( 'failed' === $result && 'email' === $test_channel ) {
				$code = get_transient( 'tscb_mail_test_' . get_current_user_id() );
				if ( is_string( $code ) && 0 === strpos( $code, 'mail_' ) ) { $message = TSCB_Mail::diagnostic( $code ); }
			}
			printf( '<div class="notice %1$s"><p>%2$s</p></div>', 'ok' === $result ? 'notice-success' : 'notice-error', esc_html( $message ) );
		}
		if ( in_array( $discovery, array( 'ok', 'empty', 'failed', 'expired' ), true ) ) {
			$messages = array(
				'ok'     => __( 'The verified Telegram chat ID was saved. Send a test message to check delivery.', 'tsambasis-contact-bridge' ),
				'empty'  => __( 'No private message with your current pairing code was found. Send the exact code below to your bot, then try again. If a webhook is active, enter the chat ID manually.', 'tsambasis-contact-bridge' ),
				'failed' => __( 'The Telegram recipient could not be confirmed. Check the token and use the pairing code in only one private chat, or enter the intended chat ID manually.', 'tsambasis-contact-bridge' ),
				'expired' => __( 'The pairing code expired or the bot token changed. Send the new code below to your bot, then confirm again.', 'tsambasis-contact-bridge' ),
			);
			printf( '<div class="notice %1$s"><p>%2$s</p></div>', 'ok' === $discovery ? 'notice-success' : 'notice-warning', esc_html( $messages[ $discovery ] ) );
		}
	}

	/** Render content-free delivery diagnostics retained for at most 24 hours. */
	private static function health() {
		$labels = array( 'email' => __( 'Email', 'tsambasis-contact-bridge' ), 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp' );
		$errors = array(
			'configuration' => __( 'Setup is incomplete. Check the fields for this channel.', 'tsambasis-contact-bridge' ),
			'disabled'      => __( 'This channel is disabled. Enable it and save your settings before testing.', 'tsambasis-contact-bridge' ),
			'mail'          => __( 'WordPress could not hand the email over to the mail server.', 'tsambasis-contact-bridge' ),
			'network'       => __( 'The web server could not reach the delivery service.', 'tsambasis-contact-bridge' ),
		);
		?>
		<div class="tscb-health-grid">
			<?php foreach ( $labels as $channel => $label ) : ?>
				<?php
				$health = get_transient( 'tscb_health_' . $channel );
				$valid  = is_array( $health ) && isset( $health['time'], $health['ok'] ) && is_numeric( $health['time'] );
				$ok     = $valid && true === $health['ok'];
				$code   = $valid && isset( $health['code'] ) && is_string( $health['code'] ) ? sanitize_key( $health['code'] ) : '';
				$state  = $valid ? ( $ok ? 'ok' : 'failed' ) : 'unknown';
				?>
				<div class="tscb-health tscb-health-<?php echo esc_attr( $state ); ?>">
					<strong><?php echo esc_html( $label ); ?></strong>
					<?php if ( $valid ) : ?>
						<p class="tscb-health-state"><?php echo $ok ? esc_html__( 'Last attempt accepted', 'tsambasis-contact-bridge' ) : esc_html__( 'Last attempt failed', 'tsambasis-contact-bridge' ); ?></p>
						<p class="description"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), absint( $health['time'] ) ) ); ?></p>
						<?php if ( ! $ok ) : ?>
							<p><?php echo esc_html( 'email' === $channel && 0 === strpos( $code, 'mail_' ) ? TSCB_Mail::diagnostic( $code ) : ( isset( $errors[ $code ] ) ? $errors[ $code ] : __( 'The service did not confirm the request. Check your credentials, recipient, and WhatsApp template where applicable.', 'tsambasis-contact-bridge' ) ) ); ?></p>
							<?php if ( isset( $errors[ $code ] ) || preg_match( '/^(?:provider_[0-9]{1,3}|mail_[a-z_]{1,30})$/D', $code ) ) : ?>
								<code><?php echo esc_html( $code ); ?></code>
							<?php endif; ?>
						<?php endif; ?>
					<?php else : ?>
						<p><?php esc_html_e( 'No status recorded in the last 24 hours.', 'tsambasis-contact-bridge' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Each channel shows only its most recent delivery attempt, including tests. Acceptance by a service does not confirm final delivery. Status records contain no message content and expire after 24 hours.', 'tsambasis-contact-bridge' ); ?></p>
		<?php
	}

	/** Render one channel's explicit choice without including it in appearance presets. */
	private static function privacy_control( $settings, $channel ) {
		$key = 'privacy_' . $channel;
		$labels = array( 'email' => __( 'Email', 'tsambasis-contact-bridge' ), 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp' );
		?>
		<div class="tscb-channel-privacy">
			<label class="tscb-check"><input type="checkbox" id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> aria-describedby="tscb-help-<?php echo esc_attr( $key ); ?>" /> <?php esc_html_e( 'Extended privacy', 'tsambasis-contact-bridge' ); ?> <span class="screen-reader-text">(<?php echo esc_html( $labels[ $channel ] ); ?>)</span></label>
			<p class="description" id="tscb-help-<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'This channel receives only the website name and a protected administrator link. Form answers, the visitor\'s name, email address and subject are not sent. The original inquiry is stored encrypted on this website. Other channels with this option off still receive the full inquiry.', 'tsambasis-contact-bridge' ); ?></p>
		</div>
		<?php
	}

	/** Render one appearance or text control; the marker restricts preview payloads. */
	private static function design_control( $settings, $key, $label, $type = 'text', $help = '', $choices = array(), $limits = array() ) {
		?>
		<div class="tscb-admin-field">
			<label for="tscb-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php if ( 'textarea' === $type ) : ?>
				<textarea data-tscb-design class="large-text" id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" rows="3" aria-describedby="tscb-help-<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( $settings[ $key ] ); ?></textarea>
			<?php elseif ( 'select' === $type ) : ?>
				<select data-tscb-design id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" aria-describedby="tscb-help-<?php echo esc_attr( $key ); ?>">
					<?php foreach ( $choices as $value => $choice ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings[ $key ], $value ); ?>><?php echo esc_html( $choice ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input data-tscb-design class="regular-text" type="<?php echo esc_attr( $type ); ?>" id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>" aria-describedby="tscb-help-<?php echo esc_attr( $key ); ?>" <?php if ( 'number' === $type ) : ?>min="<?php echo esc_attr( $limits[0] ); ?>" max="<?php echo esc_attr( $limits[1] ); ?>" step="1"<?php endif; ?> />
			<?php endif; ?>
			<p class="description" id="tscb-help-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $help ); ?></p>
		</div>
		<?php
	}

	/** Render a design checkbox without a duplicate hidden input. */
	private static function design_checkbox( $settings, $key, $label ) {
		?>
		<label class="tscb-check" for="tscb-<?php echo esc_attr( $key ); ?>"><input data-tscb-design type="checkbox" id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> /> <?php echo esc_html( $label ); ?></label>
		<?php
	}

	/** Render the field editor shell; only an initialized editor submits its JSON. */
	private static function field_builder() {
		$types = TSCB_Fields::type_labels();
		?>
		<details class="tscb-design-group" id="tscb-field-builder-group" open>
			<summary><?php esc_html_e( 'Form fields', 'tsambasis-contact-bridge' ); ?></summary>
			<div class="tscb-design-group-body" id="tscb-field-builder">
				<p class="description"><?php esc_html_e( 'Add, remove, or reorder fields. Each field can have its own label, type, and width. Changes appear in the preview and take effect on your website after saving.', 'tsambasis-contact-bridge' ); ?></p>
				<p class="description"><?php esc_html_e( 'Unchanged default labels follow the plugin language. Your custom labels stay as entered. Leave a label blank to use its default.', 'tsambasis-contact-bridge' ); ?></p>
				<div class="tscb-fields-toolbar">
					<label for="tscb-add-field-type"><?php esc_html_e( 'New field type', 'tsambasis-contact-bridge' ); ?></label>
					<select id="tscb-add-field-type">
						<?php foreach ( $types as $type => $label ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button" id="tscb-add-field"><?php esc_html_e( 'Add field', 'tsambasis-contact-bridge' ); ?></button>
					<button type="button" class="button-link" id="tscb-undo-field" hidden><?php esc_html_e( 'Undo removal', 'tsambasis-contact-bridge' ); ?></button>
				</div>
				<p class="tscb-field-count" id="tscb-field-count"></p>
				<div id="tscb-fields-list"></div>
				<p class="description tscb-field-feedback" id="tscb-field-status" role="status" aria-live="polite"></p>
				<noscript><p><?php esc_html_e( 'Enable JavaScript to add or edit form fields. Your existing fields are preserved when you save other settings.', 'tsambasis-contact-bridge' ); ?></p></noscript>
			</div>
		</details>
		<template id="tscb-field-template">
			<article class="tscb-builder-field">
				<header class="tscb-builder-field-heading">
					<div><span class="tscb-field-number" aria-hidden="true"></span><strong class="tscb-field-title"></strong></div>
					<div class="tscb-field-actions">
						<button type="button" class="button tscb-field-move" data-field-action="up"><span aria-hidden="true">↑</span></button>
						<button type="button" class="button tscb-field-move" data-field-action="down"><span aria-hidden="true">↓</span></button>
						<button type="button" class="button-link-delete" data-field-action="remove"><?php esc_html_e( 'Remove', 'tsambasis-contact-bridge' ); ?></button>
					</div>
				</header>
				<div class="tscb-builder-field-body tscb-field-grid">
					<label class="tscb-builder-control"><span><?php esc_html_e( 'Field type', 'tsambasis-contact-bridge' ); ?></span><select data-field-key="type"><?php foreach ( $types as $type => $label ) : ?><option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label class="tscb-builder-control"><span><?php esc_html_e( 'Field label', 'tsambasis-contact-bridge' ); ?></span><input type="text" data-field-key="label" maxlength="100" /></label>
					<label class="tscb-builder-control"><span><?php esc_html_e( 'Placeholder', 'tsambasis-contact-bridge' ); ?></span><input type="text" data-field-key="placeholder" maxlength="150" /></label>
					<label class="tscb-builder-control"><span><?php esc_html_e( 'Field width', 'tsambasis-contact-bridge' ); ?></span><select data-field-key="width"><option value="half"><?php esc_html_e( 'Half width', 'tsambasis-contact-bridge' ); ?></option><option value="full"><?php esc_html_e( 'Full width', 'tsambasis-contact-bridge' ); ?></option></select></label>
					<label class="tscb-check tscb-builder-required"><input type="checkbox" data-field-key="required" /> <?php esc_html_e( 'Required field', 'tsambasis-contact-bridge' ); ?></label>
					<label class="tscb-builder-control tscb-builder-options" hidden><span><?php esc_html_e( 'Choices (one per line)', 'tsambasis-contact-bridge' ); ?></span><textarea data-field-key="options" rows="3"></textarea><small><?php esc_html_e( 'Enter between 1 and 20 choices, one per line.', 'tsambasis-contact-bridge' ); ?></small></label>
				</div>
			</article>
		</template>
		<?php
	}

	/** Designer fields are rendered once and remain enabled in both editor modes. */
	private static function designer( $settings ) {
		$presets = TSCB_Design::presets();
		?>
		<section class="tscb-admin-panel tscb-designer" id="tscb-designer" aria-labelledby="tscb-design-title">
			<div class="tscb-section-heading"><div><p class="tscb-eyebrow"><?php esc_html_e( 'FORM DESIGNER', 'tsambasis-contact-bridge' ); ?></p><h2 id="tscb-design-title"><?php esc_html_e( 'Make your form your own.', 'tsambasis-contact-bridge' ); ?></h2></div></div>
			<input type="hidden" name="tscb_settings[design_present]" value="1" />
			<input data-tscb-design type="hidden" id="tscb-preset" name="tscb_settings[preset]" value="<?php echo esc_attr( $settings['preset'] ); ?>" />
			<fieldset class="tscb-mode-switch"><legend class="screen-reader-text"><?php esc_html_e( 'Editing mode', 'tsambasis-contact-bridge' ); ?></legend>
				<label><input type="radio" name="tscb_settings[editor_mode]" value="simple" <?php checked( $settings['editor_mode'], 'simple' ); ?> aria-controls="tscb-advanced-options" /><span><?php esc_html_e( 'Simple', 'tsambasis-contact-bridge' ); ?></span></label>
				<label><input type="radio" name="tscb_settings[editor_mode]" value="advanced" <?php checked( $settings['editor_mode'], 'advanced' ); ?> aria-controls="tscb-advanced-options" /><span><?php esc_html_e( 'Advanced', 'tsambasis-contact-bridge' ); ?></span></label>
			</fieldset>
			<p class="description"><?php esc_html_e( 'Choose a preset and edit your text. Switch to Advanced to customize every detail. Your settings are kept when switching modes.', 'tsambasis-contact-bridge' ); ?></p>
			<h3><?php esc_html_e( 'Design preset', 'tsambasis-contact-bridge' ); ?></h3>
			<div class="tscb-preset-grid">
				<?php foreach ( $presets as $slug => $preset ) : ?>
					<button type="button" class="tscb-preset tscb-preset-<?php echo esc_attr( $slug ); ?>" data-tscb-preset="<?php echo esc_attr( $slug ); ?>" aria-pressed="<?php echo $settings['preset'] === $slug ? 'true' : 'false'; ?>"><span class="tscb-preset-sample" aria-hidden="true"><span></span><i></i><i></i><b></b></span><strong><?php echo esc_html( $preset['label'] ); ?></strong><small><?php echo esc_html( $preset['description'] ); ?></small></button>
				<?php endforeach; ?>
			</div>
			<p class="description" id="tscb-preset-status"><?php echo esc_html( isset( $presets[ $settings['preset'] ] ) ? $presets[ $settings['preset'] ]['label'] : __( 'Custom design', 'tsambasis-contact-bridge' ) ); ?></p>
			<p class="description"><?php esc_html_e( 'Presets only change the appearance. Your text, privacy notice, and delivery channels are kept.', 'tsambasis-contact-bridge' ); ?></p>
			<?php self::design_checkbox( $settings, 'embedded', __( 'Embed seamlessly', 'tsambasis-contact-bridge' ) ); ?>
			<p class="description"><?php esc_html_e( 'Transparent background, no outer border, shadow or container spacing. Field styling and maximum width are kept. Choose text colors that suit your page background; the preview uses a sample background.', 'tsambasis-contact-bridge' ); ?></p>
			<div class="tscb-designer-divider"></div>
			<h3><?php esc_html_e( 'Your text', 'tsambasis-contact-bridge' ); ?></h3>
			<?php self::design_control( $settings, 'heading', __( 'Heading', 'tsambasis-contact-bridge' ) ); ?>
			<?php self::design_control( $settings, 'intro', __( 'Introduction', 'tsambasis-contact-bridge' ), 'textarea' ); ?>
			<?php self::design_control( $settings, 'submit_label', __( 'Submit button text', 'tsambasis-contact-bridge' ) ); ?>
			<details class="tscb-design-group" open><summary><?php esc_html_e( 'Privacy notice', 'tsambasis-contact-bridge' ); ?></summary><div class="tscb-design-group-body">
				<?php self::design_checkbox( $settings, 'require_consent', __( 'Require confirmation of the privacy notice', 'tsambasis-contact-bridge' ) ); ?>
				<?php self::design_control( $settings, 'privacy_label', __( 'Confirmation text', 'tsambasis-contact-bridge' ), 'textarea', __( 'Place {privacy_link} where you want the link to appear. If you omit the placeholder, the link is added at the end.', 'tsambasis-contact-bridge' ) ); ?>
				<button type="button" class="button" id="tscb-insert-privacy-link"><?php esc_html_e( 'Insert privacy link into text', 'tsambasis-contact-bridge' ); ?></button>
				<p class="description"><?php esc_html_e( 'The button inserts the link at the cursor position. The preview shows your chosen link text there.', 'tsambasis-contact-bridge' ); ?></p>
				<?php self::design_control( $settings, 'privacy_link_text', __( 'Link text', 'tsambasis-contact-bridge' ) ); ?>
				<?php self::design_control( $settings, 'privacy_url', __( 'Privacy policy link', 'tsambasis-contact-bridge' ), 'url', __( 'A full http:// or https:// address.', 'tsambasis-contact-bridge' ) ); ?>
				<?php self::design_checkbox( $settings, 'privacy_new_tab', __( 'Open the privacy policy in a new tab', 'tsambasis-contact-bridge' ) ); ?>
			</div></details>
			<div id="tscb-advanced-options" class="tscb-advanced-options">
				<h3><?php esc_html_e( 'Customize details', 'tsambasis-contact-bridge' ); ?></h3>
				<details class="tscb-design-group" open><summary><?php esc_html_e( 'Layout & spacing', 'tsambasis-contact-bridge' ); ?></summary><div class="tscb-design-group-body tscb-field-grid">
					<?php self::design_control( $settings, 'theme', __( 'Color scheme', 'tsambasis-contact-bridge' ), 'select', '', array( 'light' => __( 'Light', 'tsambasis-contact-bridge' ), 'dark' => __( 'Dark', 'tsambasis-contact-bridge' ), 'auto' => __( 'Match device settings', 'tsambasis-contact-bridge' ) ) ); ?>
					<?php self::design_control( $settings, 'shape', __( 'Shape', 'tsambasis-contact-bridge' ), 'select', '', array( 'rounded' => __( 'Rounded', 'tsambasis-contact-bridge' ), 'square' => __( 'Square', 'tsambasis-contact-bridge' ) ) ); ?>
					<?php self::design_control( $settings, 'layout', __( 'Field layout', 'tsambasis-contact-bridge' ), 'select', __( 'Fields always stack vertically on small screens.', 'tsambasis-contact-bridge' ), array( 'two-column' => __( 'Two columns', 'tsambasis-contact-bridge' ), 'single-column' => __( 'One column', 'tsambasis-contact-bridge' ) ) ); ?>
					<?php self::design_control( $settings, 'width', __( 'Maximum width (px)', 'tsambasis-contact-bridge' ), 'number', __( '360 to 1000 pixels; automatically adapts to smaller screens.', 'tsambasis-contact-bridge' ), array(), array( 360, 1000 ) ); ?>
					<?php self::design_control( $settings, 'spacing', __( 'Spacing', 'tsambasis-contact-bridge' ), 'select', '', array( 'compact' => __( 'Compact', 'tsambasis-contact-bridge' ), 'comfortable' => __( 'Comfortable', 'tsambasis-contact-bridge' ), 'airy' => __( 'Spacious', 'tsambasis-contact-bridge' ) ) ); ?>
					<?php self::design_control( $settings, 'font_size', __( 'Font size (px)', 'tsambasis-contact-bridge' ), 'number', __( '14 to 20 pixels.', 'tsambasis-contact-bridge' ), array(), array( 14, 20 ) ); ?>
					<?php self::design_checkbox( $settings, 'shadow', __( 'Show a subtle shadow', 'tsambasis-contact-bridge' ) ); ?>
				</div></details>
				<details class="tscb-design-group"><summary><?php esc_html_e( 'Colors', 'tsambasis-contact-bridge' ); ?></summary><div class="tscb-design-group-body">
					<?php self::design_control( $settings, 'accent', __( 'Accent color', 'tsambasis-contact-bridge' ), 'color', __( 'Used for the submit button and highlighted elements.', 'tsambasis-contact-bridge' ) ); ?>
					<?php self::design_checkbox( $settings, 'custom_colors', __( 'Use custom background and text colors', 'tsambasis-contact-bridge' ) ); ?>
					<p class="description"><?php esc_html_e( 'These colors override the color scheme. Choose colors with readable contrast. Your color choices are kept when this option is turned off.', 'tsambasis-contact-bridge' ); ?></p>
					<div class="tscb-field-grid tscb-custom-colors">
						<?php foreach ( array( 'color_surface' => __( 'Form background', 'tsambasis-contact-bridge' ), 'color_field' => __( 'Input fields', 'tsambasis-contact-bridge' ), 'color_text' => __( 'Text', 'tsambasis-contact-bridge' ), 'color_muted' => __( 'Helper text', 'tsambasis-contact-bridge' ), 'color_border' => __( 'Borders', 'tsambasis-contact-bridge' ) ) as $key => $label ) : ?>
							<?php self::design_control( $settings, $key, $label, 'color' ); ?>
						<?php endforeach; ?>
					</div>
				</div></details>
				<?php self::field_builder(); ?>
				<details class="tscb-design-group"><summary><?php esc_html_e( 'Button & guidance', 'tsambasis-contact-bridge' ); ?></summary><div class="tscb-design-group-body">
					<?php self::design_control( $settings, 'eyebrow', __( 'Small text above the heading', 'tsambasis-contact-bridge' ), 'text', __( 'Leave blank to hide it.', 'tsambasis-contact-bridge' ) ); ?>
					<?php self::design_control( $settings, 'button_align', __( 'Button alignment', 'tsambasis-contact-bridge' ), 'select', '', array( 'left' => __( 'Left', 'tsambasis-contact-bridge' ), 'center' => __( 'Center', 'tsambasis-contact-bridge' ), 'right' => __( 'Right', 'tsambasis-contact-bridge' ), 'stretch' => __( 'Full width', 'tsambasis-contact-bridge' ) ) ); ?>
					<?php self::design_checkbox( $settings, 'button_arrow', __( 'Show an arrow on the submit button', 'tsambasis-contact-bridge' ) ); ?>
					<?php self::design_checkbox( $settings, 'show_counter', __( 'Show a character counter below the message field', 'tsambasis-contact-bridge' ) ); ?>
					<?php self::design_checkbox( $settings, 'show_required_note', __( 'Show the required fields notice', 'tsambasis-contact-bridge' ) ); ?>
					<?php self::design_control( $settings, 'required_note', __( 'Required fields notice', 'tsambasis-contact-bridge' ) ); ?>
					<?php self::design_control( $settings, 'message_hint', __( 'Helper text below the message field', 'tsambasis-contact-bridge' ), 'text', __( '{max} is replaced with the maximum number of characters allowed.', 'tsambasis-contact-bridge' ) ); ?>
					<?php self::design_control( $settings, 'success_message', __( 'Confirmation after acceptance', 'tsambasis-contact-bridge' ), 'textarea', __( 'Shown after the inquiry has been stored securely or accepted by at least one delivery service. Select Confirmation above the preview to see it.', 'tsambasis-contact-bridge' ) ); ?>
				</div></details>
			</div>
			<noscript><p><?php esc_html_e( 'Enable JavaScript to switch presets and use the live preview. You can still edit and save all settings directly.', 'tsambasis-contact-bridge' ); ?></p></noscript>
		</section>
		<?php
	}

	/** A sandboxed document keeps the real rendered form separate from settings. */
	private static function preview( $settings ) {
		?>
		<aside class="tscb-preview-panel" aria-labelledby="tscb-preview-title">
			<div class="tscb-preview-heading"><div><p class="tscb-eyebrow"><?php esc_html_e( 'LIVE PREVIEW', 'tsambasis-contact-bridge' ); ?></p><h2 id="tscb-preview-title"><?php esc_html_e( 'Your form', 'tsambasis-contact-bridge' ); ?></h2></div><span class="tscb-preview-badge"><?php esc_html_e( 'Preview', 'tsambasis-contact-bridge' ); ?></span></div>
			<div class="tscb-preview-toolbar">
				<div class="tscb-device-switch" role="group" aria-label="<?php esc_attr_e( 'Preview size', 'tsambasis-contact-bridge' ); ?>"><button type="button" data-tscb-device="desktop" aria-pressed="true"><?php esc_html_e( 'Desktop', 'tsambasis-contact-bridge' ); ?></button><button type="button" data-tscb-device="mobile" aria-pressed="false"><?php esc_html_e( 'Mobile · 375 px', 'tsambasis-contact-bridge' ); ?></button></div>
				<label class="screen-reader-text" for="tscb-preview-state"><?php esc_html_e( 'Preview state', 'tsambasis-contact-bridge' ); ?></label><select id="tscb-preview-state"><option value="form"><?php esc_html_e( 'Form', 'tsambasis-contact-bridge' ); ?></option><option value="success"><?php esc_html_e( 'Confirmation', 'tsambasis-contact-bridge' ); ?></option></select>
			</div>
			<div class="tscb-preview-canvas"><div class="tscb-preview-viewport"><iframe id="tscb-preview-frame" title="<?php esc_attr_e( 'Live contact form preview', 'tsambasis-contact-bridge' ); ?>" sandbox="" referrerpolicy="no-referrer" srcdoc="<?php echo esc_attr( TSCB_Design::preview_document( $settings, 'form' ) ); ?>"></iframe></div></div>
			<div class="tscb-preview-feedback"><p id="tscb-preview-status" role="status" aria-live="polite"><?php esc_html_e( 'Saved design. Your changes appear here immediately.', 'tsambasis-contact-bridge' ); ?></p><button type="button" class="button-link" id="tscb-preview-retry" hidden><?php esc_html_e( 'Try again', 'tsambasis-contact-bridge' ); ?></button></div>
			<p class="description"><?php esc_html_e( 'The preview does not send messages. Changes only appear on your website after you select Save settings.', 'tsambasis-contact-bridge' ); ?></p>
		</aside>
		<?php
	}

	/** Render the complete settings page. */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = self::get();
		$channels = is_array( $settings['channels'] ) ? $settings['channels'] : array();
		$labels   = array( 'email' => __( 'Email', 'tsambasis-contact-bridge' ), 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp' );
		?>
		<div class="wrap tscb-admin">
			<header class="tscb-admin-header">
				<div class="tscb-admin-brand">
					<img class="tscb-admin-logo" src="<?php echo esc_url( TSCB_URL . 'assets/plugin-icon.png' ); ?>" alt="" width="52" height="52" decoding="async" />
					<p class="tscb-eyebrow"><?php esc_html_e( 'ContactBridge', 'tsambasis-contact-bridge' ); ?></p>
				</div>
				<h1><?php esc_html_e( 'One form. Your channels.', 'tsambasis-contact-bridge' ); ?></h1>
				<p><?php esc_html_e( 'Receive contact inquiries by email, Telegram, or WhatsApp. Easily add the form to your website.', 'tsambasis-contact-bridge' ); ?></p>
			</header>
			<?php settings_errors( 'tscb_settings' ); ?>
			<?php self::notices(); ?>
			<div class="tscb-shortcode-box">
				<div><strong><?php esc_html_e( 'Add to a page', 'tsambasis-contact-bridge' ); ?></strong><p><?php esc_html_e( 'Add this shortcode to a Shortcode block or the classic editor.', 'tsambasis-contact-bridge' ); ?></p></div>
				<code>[contact_bridge]</code>
			</div>
			<nav class="tscb-admin-nav" aria-label="<?php esc_attr_e( 'Sections', 'tsambasis-contact-bridge' ); ?>"><a href="#tscb-designer"><?php esc_html_e( 'Design your form', 'tsambasis-contact-bridge' ); ?></a><a href="#tscb-connections"><?php esc_html_e( 'Delivery channels', 'tsambasis-contact-bridge' ); ?></a><a href="#tscb-tests"><?php esc_html_e( 'Test connection', 'tsambasis-contact-bridge' ); ?></a><a href="<?php echo esc_url( admin_url( 'options-general.php?page=tscb-inquiries' ) ); ?>"><?php esc_html_e( 'Inquiries', 'tsambasis-contact-bridge' ); ?></a></nav>
			<div class="tscb-workspace">
			<form action="options.php" method="post" id="tscb-settings-form" class="tscb-editor-form">
				<?php settings_fields( 'tscb_settings_group' ); ?>
				<input type="hidden" name="tscb_settings[privacy_settings_present]" value="1" />
				<div class="tscb-admin-panel tscb-admin-field">
					<label for="tscb-language"><?php esc_html_e( 'Plugin language', 'tsambasis-contact-bridge' ); ?></label>
					<select id="tscb-language" name="tscb_settings[language]" aria-describedby="tscb-language-help">
						<option value="de_DE" <?php selected( $settings['language'], 'de_DE' ); ?>><?php esc_html_e( 'German (default)', 'tsambasis-contact-bridge' ); ?></option>
						<option value="en_US" <?php selected( $settings['language'], 'en_US' ); ?>><?php esc_html_e( 'English', 'tsambasis-contact-bridge' ); ?></option>
						<option value="wordpress" <?php selected( $settings['language'], 'wordpress' ); ?>><?php esc_html_e( 'WordPress language', 'tsambasis-contact-bridge' ); ?></option>
					</select>
					<p class="description" id="tscb-language-help"><?php esc_html_e( 'Applies after saving to the plugin settings and form. Factory texts follow the language; your custom texts stay unchanged. The WordPress interface is not affected.', 'tsambasis-contact-bridge' ); ?></p>
				</div>
				<?php self::designer( $settings ); ?>
				<details class="tscb-admin-panel tscb-connections" id="tscb-connections">
					<summary id="tscb-routing-title"><?php esc_html_e( 'Set up delivery channels', 'tsambasis-contact-bridge' ); ?></summary>
					<p><?php esc_html_e( 'Enable one or more channels. Each inquiry is passed to all enabled channels. Telegram and WhatsApp only receive data after you explicitly enable them; a connection test also contacts the selected service.', 'tsambasis-contact-bridge' ); ?></p>
					<div class="tscb-channel-grid">
						<?php foreach ( $labels as $channel => $label ) : ?>
							<label class="tscb-channel-choice"><input type="checkbox" name="tscb_settings[channels][]" value="<?php echo esc_attr( $channel ); ?>" <?php checked( in_array( $channel, $channels, true ) ); ?> /><span><strong><?php echo esc_html( $label ); ?></strong><small><?php echo esc_html( array( 'email' => __( 'Through your website\'s email service', 'tsambasis-contact-bridge' ), 'telegram' => __( 'To a bot chat or a group', 'tsambasis-contact-bridge' ), 'whatsapp' => __( 'Through the official Meta Cloud API', 'tsambasis-contact-bridge' ) )[ $channel ] ); ?></small></span></label>
						<?php endforeach; ?>
					</div>
					<div class="tscb-service" id="tscb-service-email">
						<h3><?php esc_html_e( 'Email', 'tsambasis-contact-bridge' ); ?></h3>
						<?php self::privacy_control( $settings, 'email' ); ?>
						<?php self::field( $settings, 'email_to', __( 'Recipient email address', 'tsambasis-contact-bridge' ), __( 'One email address. Delivery depends on your WordPress installation\'s email service; test it after saving.', 'tsambasis-contact-bridge' ), 'email' ); ?>
						<?php TSCB_Mail_Admin::settings( $settings ); ?>
					</div>
					<div class="tscb-service" id="tscb-service-telegram">
						<h3>Telegram</h3>
						<?php self::privacy_control( $settings, 'telegram' ); ?>
						<p><?php esc_html_e( 'Create a bot with @BotFather and save its token. Then use the one-time code in the recipient confirmation section below, or enter your chat ID manually.', 'tsambasis-contact-bridge' ); ?></p>
						<?php self::secret( $settings, 'telegram' ); ?>
						<?php self::field( $settings, 'telegram_chat_id', __( 'Chat ID', 'tsambasis-contact-bridge' ), __( 'Private chats: numeric ID. Groups: often a negative ID. Public channels: @channelname; the bot needs permission to post.', 'tsambasis-contact-bridge' ) ); ?>
						<p class="description"><?php esc_html_e( 'Automatic discovery is intended for a new bot that has received a private message. It does not change an existing webhook and cannot work while a webhook is active.', 'tsambasis-contact-bridge' ); ?></p>
					</div>
					<div class="tscb-service" id="tscb-service-whatsapp">
						<h3>WhatsApp</h3>
						<?php self::privacy_control( $settings, 'whatsapp' ); ?>
						<p><?php esc_html_e( 'WhatsApp requires a configured WhatsApp Business account, a Cloud API phone number, a suitable access token, and a message template approved by Meta. An API key alone is not enough. Charges may apply according to Meta\'s pricing.', 'tsambasis-contact-bridge' ); ?></p>
						<?php self::secret( $settings, 'whatsapp' ); ?>
						<div class="tscb-field-grid">
							<?php self::field( $settings, 'whatsapp_phone_id', __( 'Phone Number ID', 'tsambasis-contact-bridge' ), __( 'The numeric ID of the sending WhatsApp Business phone number in Meta, not the phone number itself.', 'tsambasis-contact-bridge' ) ); ?>
							<?php self::field( $settings, 'whatsapp_to', __( 'Recipient phone number', 'tsambasis-contact-bridge' ), __( 'Phone number in international format, e.g. 491701234567. The recipient must have agreed to receive your WhatsApp messages.', 'tsambasis-contact-bridge' ), 'tel' ); ?>
							<?php self::field( $settings, 'whatsapp_template', __( 'Approved full-content template name', 'tsambasis-contact-bridge' ), __( 'Used when extended privacy is off. The approved template requires five body text parameters. Lowercase letters, digits, and underscores only.', 'tsambasis-contact-bridge' ) ); ?>
							<?php self::field( $settings, 'whatsapp_privacy_template', __( 'Approved extended-privacy template name', 'tsambasis-contact-bridge' ), __( 'Required when extended privacy is on. Use a separately approved body-only template with exactly two positional text parameters: {{1}} website name and {{2}} protected administrator URL. No variable headers or buttons.', 'tsambasis-contact-bridge' ) ); ?>
							<?php self::field( $settings, 'whatsapp_language', __( 'Template language', 'tsambasis-contact-bridge' ), __( 'The exact language code of the approved template, e.g. de or en_US.', 'tsambasis-contact-bridge' ) ); ?>
							<?php self::field( $settings, 'whatsapp_version', __( 'Graph API version', 'tsambasis-contact-bridge' ), __( 'Version in the format v24.0. Use a version supported by Meta.', 'tsambasis-contact-bridge' ) ); ?>
						</div>
						<details class="tscb-help"><summary><?php esc_html_e( 'Set up your full-content WhatsApp template', 'tsambasis-contact-bridge' ); ?></summary>
							<p><?php esc_html_e( 'Keep exactly five positional body text parameters: name, email address, subject, all labelled form answers, website. Do not use variable headers or buttons. All labels and answers together are limited to 500 characters; the five parameter values to 900. Use at most 124 characters of fixed template text. Missing name or email is shown as a dash.', 'tsambasis-contact-bridge' ); ?></p>
							<pre><?php echo esc_html( __( "New contact inquiry\nName: {{1}}\nEmail: {{2}}\nSubject: {{3}}\nDetails: {{4}}\nWebsite: {{5}}", 'tsambasis-contact-bridge' ) ); ?></pre>
							<p><?php esc_html_e( 'This example shows the required structure. Meta reviews the content, category, and intended use; approval is not guaranteed. Provide suitable sample values for all five variables when applying.', 'tsambasis-contact-bridge' ); ?></p>
						</details>
						<details class="tscb-help"><summary><?php esc_html_e( 'Set up your extended-privacy WhatsApp template', 'tsambasis-contact-bridge' ); ?></summary>
							<p><?php esc_html_e( 'Create and submit a separate template in WhatsApp Manager. Keep exactly two positional body text parameters in this order: website name, protected administrator URL. The existing five-parameter template cannot be used for extended privacy. Meta decides whether to approve the template.', 'tsambasis-contact-bridge' ); ?></p>
							<pre><?php echo esc_html( __( 'New inquiry on {{1}}. Open securely: {{2}}', 'tsambasis-contact-bridge' ) ); ?></pre>
						</details>
					</div>
				</details>
				<details class="tscb-admin-panel tscb-connections">
					<summary id="tscb-privacy-title"><?php esc_html_e( 'Privacy & retention', 'tsambasis-contact-bridge' ); ?></summary>
					<p><?php esc_html_e( 'Explain the enabled delivery channels and local storage in your privacy policy. Channels with extended privacy receive only the website name and a protected administrator link; channels without it receive the form answers. Original inquiries for extended privacy are stored encrypted on this website. Hosting, email and messaging services may keep their own data.', 'tsambasis-contact-bridge' ); ?></p>
					<div class="tscb-admin-field">
						<label for="tscb-inbox_retention_days"><?php esc_html_e( 'Keep locally stored inquiries for this many days', 'tsambasis-contact-bridge' ); ?></label>
						<input type="number" class="small-text" id="tscb-inbox_retention_days" name="tscb_settings[inbox_retention_days]" min="0" max="36500" step="1" inputmode="numeric" value="<?php echo esc_attr( (string) $settings['inbox_retention_days'] ); ?>" aria-describedby="tscb-retention-help tscb-retention-existing" />
						<p class="description" id="tscb-retention-help"><?php esc_html_e( 'Default: 30 days. Set 0 to keep inquiries indefinitely until you delete them. Values from 1 to 36500 set the retention period in whole days.', 'tsambasis-contact-bridge' ); ?></p>
						<p class="description" id="tscb-retention-existing"><?php esc_html_e( 'Changes apply only to new inquiries. Existing inquiries keep their saved expiry date; delete them separately in Inquiries.', 'tsambasis-contact-bridge' ); ?></p>
						<p><a href="<?php echo esc_url( admin_url( 'options-general.php?page=tscb-inquiries' ) ); ?>"><?php esc_html_e( 'Open inquiries', 'tsambasis-contact-bridge' ); ?></a></p>
					</div>
					<label class="tscb-check"><input type="checkbox" name="tscb_settings[delete_data]" value="1" <?php checked( $settings['delete_data'] ); ?> /> <?php esc_html_e( 'Delete settings and stored credentials when the plugin is deleted', 'tsambasis-contact-bridge' ); ?></label>
					<p class="description"><?php esc_html_e( 'Stored inquiries and runtime data are always removed when the plugin is deleted, regardless of this setting. Deactivation does not delete settings or inquiries. Remove wp-config.php values and messages held by external services separately.', 'tsambasis-contact-bridge' ); ?></p>
				</details>
				<div class="tscb-save-bar"><?php submit_button( __( 'Save settings', 'tsambasis-contact-bridge' ) ); ?><span id="tscb-save-status" role="status"><?php esc_html_e( 'All changes saved', 'tsambasis-contact-bridge' ); ?></span></div>
			</form>
			<?php self::preview( $settings ); ?>
			</div>
			<section class="tscb-admin-panel" id="tscb-tests" aria-labelledby="tscb-test-title">
				<h2 id="tscb-test-title"><?php esc_html_e( 'Test connection', 'tsambasis-contact-bridge' ); ?></h2>
				<?php self::health(); ?>
				<p><?php esc_html_e( 'Enable the channel you want to use and save your settings first. Tests send sample data to the selected service and configured recipient. Chat ID discovery uses the stored Telegram token.', 'tsambasis-contact-bridge' ); ?></p>
				<div class="tscb-test-actions">
					<?php foreach ( $labels as $channel => $label ) : ?>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="tscb_test" /><input type="hidden" name="channel" value="<?php echo esc_attr( $channel ); ?>" />
							<?php wp_nonce_field( 'tscb_test' ); ?>
							<?php /* translators: %s: Name of a delivery channel. */ ?>
							<button type="submit" class="button" <?php disabled( ! in_array( $channel, $channels, true ) ); ?>><?php echo esc_html( sprintf( __( 'Test %s', 'tsambasis-contact-bridge' ), $label ) ); ?></button>
						</form>
					<?php endforeach; ?>
				</div>
				<div class="tscb-telegram-pairing">
					<h3><?php esc_html_e( 'Confirm your Telegram recipient', 'tsambasis-contact-bridge' ); ?></h3>
					<?php $pairing_code = TSCB_Transports::pairing_code( $settings ); ?>
					<?php if ( '' !== $pairing_code ) : ?>
						<p><?php esc_html_e( 'Send this one-time code to your bot in a private Telegram chat, then confirm below. The code expires after 10 minutes. Only share it with your intended recipient.', 'tsambasis-contact-bridge' ); ?></p>
						<p><code dir="ltr"><?php echo esc_html( $pairing_code ); ?></code></p>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="tscb_discover" /><?php wp_nonce_field( 'tscb_discover' ); ?>
							<button type="submit" class="button"><?php esc_html_e( 'Confirm Telegram recipient', 'tsambasis-contact-bridge' ); ?></button>
						</form>
					<?php else : ?>
						<p><?php esc_html_e( 'Save a valid Telegram bot token first to generate a pairing code. You can also enter the chat ID manually.', 'tsambasis-contact-bridge' ); ?></p>
					<?php endif; ?>
				</div>
			</section>
			<?php TSCB_Mail_Admin::logs(); ?>
			<footer class="tscb-admin-footer"><p><?php esc_html_e( 'Signal is not integrated because it has no comparable official bot API. This plugin uses the official Cloud API for WhatsApp and the Bot API for Telegram.', 'tsambasis-contact-bridge' ); ?></p><p><a href="https://core.telegram.org/bots/tutorial" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Telegram setup', 'tsambasis-contact-bridge' ); ?></a> · <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp setup with Meta', 'tsambasis-contact-bridge' ); ?></a></p></footer>
		</div>
		<?php
	}
}
