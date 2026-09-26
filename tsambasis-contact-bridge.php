<?php
/**
 * Plugin Name: ContactBridge by Tsambasis
 * Description: Free, ad-free contact forms with email, Telegram, WhatsApp and optional protected local inquiries.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Tsambasis & Tsambasis
 * Author URI: https://tsambasis.net/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tsambasis-contact-bridge
 * Domain Path: /languages
 *
 * @package ContactBridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TSCB_VERSION', '1.0.0' );
define( 'TSCB_FILE', __FILE__ );
define( 'TSCB_PATH', plugin_dir_path( __FILE__ ) );
define( 'TSCB_URL', plugin_dir_url( __FILE__ ) );

require_once TSCB_PATH . 'includes/class-tscb-i18n.php';
require_once TSCB_PATH . 'includes/class-tscb-plugin-info.php';
require_once TSCB_PATH . 'includes/class-tscb-fields.php';
require_once TSCB_PATH . 'includes/class-tscb-design.php';
require_once TSCB_PATH . 'includes/class-tscb-mail.php';
require_once TSCB_PATH . 'includes/class-tscb-mail-admin.php';
require_once TSCB_PATH . 'includes/class-tscb-settings.php';
require_once TSCB_PATH . 'includes/class-tscb-inbox.php';
require_once TSCB_PATH . 'includes/class-tscb-transports.php';
require_once TSCB_PATH . 'includes/class-tscb-submission.php';
require_once TSCB_PATH . 'includes/class-tscb-form.php';

TSCB_I18n::init();
TSCB_Plugin_Info::init();
TSCB_Design::init();
TSCB_Settings::init();
TSCB_Mail_Admin::init();
TSCB_Inbox::init();
add_action( 'tscb_cleanup', array( 'TSCB_Mail', 'log_entries' ) );
TSCB_Transports::init();
TSCB_Submission::init();
TSCB_Form::init();

/** Initialize each site lazily, including sites added after network activation. */
function tscb_initialize_site() {
	if ( false === get_option( 'tscb_settings', false ) ) {
		add_option( 'tscb_settings', TSCB_Settings::defaults(), '', false );
	}
	if ( ! wp_next_scheduled( 'tscb_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'tscb_cleanup' );
	}
}
add_action( 'init', 'tscb_initialize_site' );

/** Set non-autoloading options; activation never contacts a service. */
function tscb_activate() {
	TSCB_I18n::load();
	tscb_initialize_site();
}
register_activation_hook( __FILE__, 'tscb_activate' );

/**
 * Remove cleanup events from every affected site on deactivation.
 *
 * @param bool $network_wide Whether the plugin is being network-deactivated.
 */
function tscb_deactivate( $network_wide = false ) {
	if ( ! is_multisite() || ! $network_wide ) {
		wp_clear_scheduled_hook( 'tscb_cleanup' );
		return;
	}

	$offset = 0;
	do {
		$site_ids = get_sites(
			array(
				'fields'     => 'ids',
				'network_id' => get_current_network_id(),
				'number'     => 100,
				'offset'     => $offset,
				'orderby'    => 'id',
				'order'      => 'ASC',
			)
		);
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			try {
				wp_clear_scheduled_hook( 'tscb_cleanup' );
			} finally {
				restore_current_blog();
			}
		}
		$offset += 100;
	} while ( 100 === count( $site_ids ) );
}
register_deactivation_hook( __FILE__, 'tscb_deactivate' );

/** Add direct settings and optional website information links. */
function tscb_settings_link( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=tsambasis-contact-bridge' ) ) . '">' . esc_html__( 'Settings', 'tsambasis-contact-bridge' ) . '</a>' );
	$links['tscb_more_information'] = TSCB_Plugin_Info::more_link();
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tscb_settings_link' );
add_filter( 'network_admin_plugin_action_links_' . plugin_basename( __FILE__ ), 'tscb_settings_link' );

/** Explain actual data handling in the WordPress privacy policy helper. */
function tscb_privacy_help() {
	if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		wp_add_privacy_policy_content( __( 'ContactBridge by Tsambasis', 'tsambasis-contact-bridge' ), wp_kses_post(
			'<p>' . __( 'Our contact form processes the fields configured by the site operator, such as name, email, phone number, subject, message and selected options. For each enabled channel, the operator chooses full-content delivery or extended privacy. Full-content channels send the submitted details by email, Telegram or the WhatsApp Business Platform. Extended-privacy channels receive only a general notification, the website name and a link requiring an authorized WordPress administrator login; no submitted fields or visitor reply address are included.', 'tsambasis-contact-bridge' ) . '</p>' .
			'<p>' . __( 'When extended privacy is enabled for an active channel, the inquiry is stored encrypted in this website\'s WordPress database. Administrators can read and delete it. The default retention is 30 days; the operator can choose another period or permanent storage. Each inquiry retains the period selected when it was received. Expired inquiries are hidden and automatically deleted through scheduled cleanup; backups may retain separate copies. If all channels use full-content delivery, the plugin does not create a local inquiry archive.', 'tsambasis-contact-bridge' ) . '</p>' .
			'<p>' . __( 'To prevent abuse, the plugin temporarily stores keyed identifiers and delivery status without message content. The server processes the IP address but the plugin does not store it in plain text. The plugin adds no tracking cookies, remote fonts, advertising or analytics. Specify the actual recipients, purposes, legal bases, retention periods and any international transfers applicable to your website; enabling extended privacy does not by itself guarantee GDPR compliance.', 'tsambasis-contact-bridge' ) . '</p>' .
			'<p>' . __( 'If enabled, the email delivery log stores at most 50 events for seven days: time, transport, test or form submission, result and a safe error category. It contains no addresses, subjects, message bodies or credentials. Email is handled by the configured WordPress mail service or SMTP provider. Administrators can disable or clear this log.', 'tsambasis-contact-bridge' ) . '</p>'
		) );
	}
}
add_action( 'admin_init', 'tscb_privacy_help' );
