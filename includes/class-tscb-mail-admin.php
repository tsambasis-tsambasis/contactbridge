<?php
/**
 * Email connection settings and content-free delivery diagnostics.
 *
 * @package Kontelio
 */

defined( 'ABSPATH' ) || exit;

/** Administrator interface for the optional plugin-only SMTP transport. */
final class TSCB_Mail_Admin {
	/** Register only the explicit, authenticated log-clear action. */
	public static function init() {
		add_action( 'admin_post_tscb_clear_mail_log', array( __CLASS__, 'clear' ) );
	}

	/** Clear status records without accepting an arbitrary redirect destination. */
	public static function clear() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'kontelio' ), '', array( 'response' => 403 ) );
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_die( esc_html__( 'Please submit the form.', 'kontelio' ), '', array( 'response' => 405 ) );
		}
		check_admin_referer( 'tscb_clear_mail_log' );
		TSCB_Mail::clear_log();
		wp_safe_redirect( add_query_arg( array( 'page' => 'kontelio', 'tscb_mail_log_cleared' => '1' ), admin_url( 'options-general.php' ) ) . '#tscb-mail-log' );
		exit;
	}

	/** Read a scalar display value without accessing credential decryption. */
	private static function value( $settings, $key, $fallback = '' ) {
		return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? (string) $settings[ $key ] : $fallback;
	}

	/** Render a labeled input used only by these mail settings. */
	private static function field( $settings, $key, $label, $help = '', $type = 'text' ) {
		?>
		<div class="tscb-admin-field">
			<label for="tscb-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input class="regular-text" id="tscb-<?php echo esc_attr( $key ); ?>" name="tscb_settings[<?php echo esc_attr( $key ); ?>]" type="<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( self::value( $settings, $key ) ); ?>" aria-describedby="tscb-mail-help-<?php echo esc_attr( $key ); ?>" <?php if ( 'number' === $type ) : ?>min="1" max="65535" step="1"<?php endif; ?> />
			<p class="description" id="tscb-mail-help-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $help ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render inside the existing settings form, after the recipient address.
	 * No nested form and no credential values are included in the HTML.
	 *
	 * @param array $settings Current merged plugin settings.
	 */
	public static function settings( $settings ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$method     = 'smtp' === self::value( $settings, 'email_method', 'wordpress' ) ? 'smtp' : 'wordpress';
		$encryption = 'ssl' === self::value( $settings, 'smtp_encryption', 'tls' ) ? 'ssl' : 'tls';
		?>
		<div class="tscb-mail-settings">
			<input type="hidden" name="tscb_settings[email_settings_present]" value="1" />
			<div class="tscb-admin-field">
				<label for="tscb-email_method"><?php esc_html_e( 'Email delivery method', 'kontelio' ); ?></label>
				<select id="tscb-email_method" name="tscb_settings[email_method]" aria-describedby="tscb-mail-method-help">
					<option value="wordpress" <?php selected( $method, 'wordpress' ); ?>><?php esc_html_e( 'WordPress mail (default)', 'kontelio' ); ?></option>
					<option value="smtp" <?php selected( $method, 'smtp' ); ?>><?php esc_html_e( 'Custom SMTP for this plugin', 'kontelio' ); ?></option>
				</select>
				<p class="description" id="tscb-mail-method-help"><?php esc_html_e( 'WordPress mail uses your website\'s current email setup, including an existing SMTP plugin. Custom SMTP applies only to emails sent by this plugin.', 'kontelio' ); ?></p>
			</div>
			<div class="tscb-field-grid">
				<?php self::field( $settings, 'email_from', __( 'Sender email address', 'kontelio' ), __( 'Use a valid mailbox approved by your mail provider. Required for custom SMTP. Leave blank with WordPress mail to use its existing sender; the default may be wordpress@your-domain.', 'kontelio' ), 'email' ); ?>
				<?php self::field( $settings, 'email_from_name', __( 'Sender name', 'kontelio' ), __( 'The name recipients see next to the sender address. Leave blank to use the existing default.', 'kontelio' ) ); ?>
			</div>
			<details class="tscb-help tscb-smtp-details" id="tscb-smtp-details" <?php if ( 'smtp' === $method ) : ?>open<?php endif; ?>>
				<summary><?php esc_html_e( 'SMTP connection settings', 'kontelio' ); ?></summary>
				<p><?php esc_html_e( 'Enter the outgoing mail settings supplied by your mail provider. These settings are used only when Custom SMTP is selected. Saving settings does not send a test message.', 'kontelio' ); ?></p>
				<p class="description"><?php esc_html_e( 'Custom SMTP supports a username and password or an app password. If your provider requires OAuth, choose WordPress mail and use a compatible mail plugin.', 'kontelio' ); ?></p>
				<div class="tscb-field-grid">
					<?php self::field( $settings, 'smtp_host', __( 'SMTP server', 'kontelio' ), __( 'Server hostname only, for example smtp.example.com. Do not include a protocol, port, or path.', 'kontelio' ) ); ?>
					<?php self::field( $settings, 'smtp_port', __( 'SMTP port', 'kontelio' ), __( 'Use the port specified by your provider. STARTTLS commonly uses 587; TLS/SSL commonly uses 465.', 'kontelio' ), 'number' ); ?>
				</div>
				<div class="tscb-admin-field">
					<label for="tscb-smtp_encryption"><?php esc_html_e( 'Connection encryption', 'kontelio' ); ?></label>
					<select id="tscb-smtp_encryption" name="tscb_settings[smtp_encryption]" aria-describedby="tscb-mail-encryption-help">
						<option value="tls" <?php selected( $encryption, 'tls' ); ?>><?php esc_html_e( 'STARTTLS', 'kontelio' ); ?></option>
						<option value="ssl" <?php selected( $encryption, 'ssl' ); ?>><?php esc_html_e( 'TLS/SSL', 'kontelio' ); ?></option>
					</select>
					<p class="description" id="tscb-mail-encryption-help"><?php esc_html_e( 'Choose the encryption mode required by your provider. Unencrypted SMTP is not offered.', 'kontelio' ); ?></p>
				</div>
				<label class="tscb-check"><input type="checkbox" id="tscb-smtp_auth" name="tscb_settings[smtp_auth]" value="1" <?php checked( ! empty( $settings['smtp_auth'] ) ); ?> /> <?php esc_html_e( 'SMTP server requires authentication', 'kontelio' ); ?></label>
				<?php self::field( $settings, 'smtp_username', __( 'SMTP username', 'kontelio' ), __( 'Often your full mailbox address. Use the login name supplied by your provider.', 'kontelio' ) ); ?>
				<div class="tscb-admin-field">
					<?php if ( defined( 'TSCB_SMTP_PASSWORD' ) ) : ?>
						<p class="tscb-mail-label"><?php esc_html_e( 'SMTP password', 'kontelio' ); ?></p>
						<p class="tscb-credential-status"><?php esc_html_e( 'Configured in wp-config.php. Change the password there.', 'kontelio' ); ?> <code>TSCB_SMTP_PASSWORD</code></p>
					<?php else : ?>
						<label for="tscb-smtp_password"><?php esc_html_e( 'SMTP password', 'kontelio' ); ?></label>
						<input class="regular-text" id="tscb-smtp_password" name="tscb_settings[smtp_password]" type="password" value="" autocomplete="new-password" spellcheck="false" aria-describedby="tscb-mail-password-help" />
						<p class="description" id="tscb-mail-password-help"><?php echo empty( $settings['smtp_password'] ) ? esc_html__( 'No SMTP password saved yet.', 'kontelio' ) : esc_html__( 'SMTP password saved. Leave this field blank to keep it.', 'kontelio' ); ?></p>
						<?php if ( ! empty( $settings['smtp_password'] ) ) : ?>
							<label class="tscb-check"><input type="checkbox" name="tscb_settings[delete_smtp_password]" value="1" /> <?php esc_html_e( 'Delete the stored SMTP password when saving', 'kontelio' ); ?></label>
						<?php endif; ?>
					<?php endif; ?>
					<p class="description"><?php esc_html_e( 'SMTP passwords saved in the plugin settings are encrypted using WordPress site keys. If those keys change, enter the password again.', 'kontelio' ); ?></p>
				</div>
			</details>
			<label class="tscb-check"><input type="checkbox" id="tscb-email_log_enabled" name="tscb_settings[email_log_enabled]" value="1" <?php checked( ! empty( $settings['email_log_enabled'] ) ); ?> /> <?php esc_html_e( 'Keep an email diagnostic log', 'kontelio' ); ?></label>
			<p class="description"><?php esc_html_e( 'Optional: keep up to 50 delivery status records for seven days. No message content, email addresses, or credentials are recorded. Turning logging off removes the existing log.', 'kontelio' ); ?></p>
			<p class="description"><?php esc_html_e( 'Save your settings, then use the email test below. Acceptance by WordPress or an SMTP server does not confirm delivery to the inbox.', 'kontelio' ); ?></p>
			<?php self::last_attempt(); ?>
		</div>
		<?php
	}

	/** Show the existing safe health code beside the connection settings. */
	private static function last_attempt() {
		$health = get_transient( 'tscb_health_email' );
		if ( ! is_array( $health ) || ! isset( $health['code'] ) || ! is_string( $health['code'] ) ) {
			return;
		}
		$ok = ! empty( $health['ok'] );
		$code = $ok && 'accepted' === $health['code'] ? 'mail_accepted' : $health['code'];
		if ( $ok && isset( $health['time'] ) && is_numeric( $health['time'] ) ) {
			$entries = TSCB_Mail::log_entries();
			$latest = is_array( $entries ) && isset( $entries[0] ) ? $entries[0] : null;
			if ( is_array( $latest ) && ! empty( $latest['ok'] ) && isset( $latest['time'], $latest['code'] ) && abs( (int) $latest['time'] - (int) $health['time'] ) <= 2 && 'mail_delegated' === $latest['code'] ) {
				$code = 'mail_delegated';
			}
		}
		?>
		<div class="tscb-mail-last-attempt <?php echo $ok ? 'is-success' : 'is-error'; ?>">
			<strong><?php esc_html_e( 'Last email attempt', 'kontelio' ); ?></strong>
			<p><?php echo esc_html( TSCB_Mail::diagnostic( $code ) ); ?></p>
		</div>
		<?php
	}

	/** Render the log after the settings form so its delete form is independent. */
	public static function logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = TSCB_Settings::get();
		$entries  = TSCB_Mail::log_entries();
		$entries  = is_array( $entries ) ? $entries : array();
		$cutoff   = time() - 7 * DAY_IN_SECONDS;
		$entries  = array_filter( $entries, static function ( $entry ) use ( $cutoff ) {
			return is_array( $entry ) && isset( $entry['time'] ) && is_numeric( $entry['time'] ) && (int) $entry['time'] >= $cutoff;
		} );
		usort( $entries, static function ( $left, $right ) {
			return (int) $right['time'] <=> (int) $left['time'];
		} );
		$entries = array_slice( $entries, 0, 50 );
		// This read-only flag selects a fixed notice; deleting logs requires POST and a nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cleared = isset( $_GET['tscb_mail_log_cleared'] ) && '1' === $_GET['tscb_mail_log_cleared'];
		?>
		<section class="tscb-admin-panel tscb-mail-log" id="tscb-mail-log" aria-labelledby="tscb-mail-log-title">
			<div class="tscb-mail-log-heading">
				<div><h2 id="tscb-mail-log-title"><?php esc_html_e( 'Email diagnostics', 'kontelio' ); ?></h2><p><?php esc_html_e( 'Check the most recent email attempts without storing the messages themselves.', 'kontelio' ); ?></p></div>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="tscb_clear_mail_log" />
					<?php wp_nonce_field( 'tscb_clear_mail_log' ); ?>
					<button type="submit" class="button" <?php disabled( empty( $entries ) ); ?>><?php esc_html_e( 'Clear email log', 'kontelio' ); ?></button>
				</form>
			</div>
			<?php if ( $cleared ) : ?>
				<p class="tscb-mail-log-cleared" role="status"><?php esc_html_e( 'The email diagnostic log was cleared.', 'kontelio' ); ?></p>
			<?php endif; ?>
			<?php if ( empty( $settings['email_log_enabled'] ) ) : ?>
				<p class="description"><?php esc_html_e( 'Logging is off. Enable it in the email settings to record future attempts.', 'kontelio' ); ?></p>
			<?php endif; ?>
			<?php if ( ! $entries ) : ?>
				<p class="tscb-mail-log-empty"><?php esc_html_e( 'No email attempts have been recorded in the last seven days.', 'kontelio' ); ?></p>
			<?php else : ?>
				<div class="tscb-mail-log-scroll" role="region" aria-label="<?php esc_attr_e( 'Recent email delivery attempts', 'kontelio' ); ?>" tabindex="0">
					<table class="widefat striped">
						<thead><tr><th scope="col"><?php esc_html_e( 'Time', 'kontelio' ); ?></th><th scope="col"><?php esc_html_e( 'Result', 'kontelio' ); ?></th><th scope="col"><?php esc_html_e( 'Method', 'kontelio' ); ?></th><th scope="col"><?php esc_html_e( 'Context', 'kontelio' ); ?></th><th scope="col"><?php esc_html_e( 'Diagnostic', 'kontelio' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $entries as $entry ) : ?>
								<?php
								$ok      = ! empty( $entry['ok'] );
								$method  = isset( $entry['method'] ) && 'smtp' === $entry['method'] ? __( 'Custom SMTP', 'kontelio' ) : __( 'WordPress mail', 'kontelio' );
								$context = isset( $entry['context'] ) && 'test' === $entry['context'] ? __( 'Test message', 'kontelio' ) : __( 'Contact form', 'kontelio' );
								$code    = isset( $entry['code'] ) && is_string( $entry['code'] ) ? $entry['code'] : 'mail_generic';
								?>
								<tr>
									<td><time datetime="<?php echo esc_attr( gmdate( 'c', (int) $entry['time'] ) ); ?>"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $entry['time'] ) ); ?></time></td>
									<td><span class="tscb-mail-result <?php echo $ok ? 'is-success' : 'is-error'; ?>"><?php echo $ok ? esc_html__( 'Accepted', 'kontelio' ) : esc_html__( 'Failed', 'kontelio' ); ?></span></td>
									<td><?php echo esc_html( $method ); ?></td>
									<td><?php echo esc_html( $context ); ?></td>
									<td><?php echo esc_html( TSCB_Mail::diagnostic( $code ) ); ?><br /><code><?php echo esc_html( $code ); ?><?php if ( isset( $entry['smtp_code'] ) ) : ?> · SMTP <?php echo esc_html( (string) absint( $entry['smtp_code'] ) ); ?><?php endif; ?></code></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Up to 50 records are kept for a maximum of seven days. Accepted means the mail system accepted the message; it does not confirm inbox delivery. Addresses, message content, and credentials are never included in this log.', 'kontelio' ); ?></p>
		</section>
		<?php
	}
}
