<?php
/** Local information for active installations before directory publication. @package ContactBridge */
defined( 'ABSPATH' ) || exit;

final class TSCB_Plugin_Info {
	const SLUG = 'tsambasis-contact-bridge';

	public static function init() {
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 4 );
		add_filter( 'plugins_api', array( __CLASS__, 'information' ), 20, 3 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'styles' ) );
	}

	/** A normal external link; rendering it never requests the destination. */
	public static function more_link() {
		return '<a href="' . esc_url( 'https://tsambasis.net/' ) . '" title="' . esc_attr( 'Tsambasis & Tsambasis' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'More information', 'tsambasis-contact-bridge' ) . '</a>';
	}

	private static function active() {
		return function_exists( 'is_plugin_active' ) && is_plugin_active( plugin_basename( TSCB_FILE ) );
	}

	/** Only inspect WordPress's existing update cache; never initiate a directory request. */
	private static function directory_known() {
		$updates = get_site_transient( 'update_plugins' );
		$file = plugin_basename( TSCB_FILE );
		foreach ( array( 'response', 'no_update' ) as $group ) {
			if ( is_object( $updates ) && isset( $updates->$group ) && is_array( $updates->$group ) && isset( $updates->{$group}[ $file ] ) ) {
				$entry = $updates->{$group}[ $file ];
				if ( is_object( $entry ) && isset( $entry->slug ) && is_string( $entry->slug ) && '' !== $entry->slug ) { return true; }
			}
		}
		return false;
	}

	/** WordPress already loads the native ThickBox assets on its installed-plugins screen. */
	public static function row_meta( $links, $file, $data, $status ) {
		if ( plugin_basename( TSCB_FILE ) !== $file || ! self::active() || ! current_user_can( 'install_plugins' ) || ! empty( $data['slug'] ) || self::directory_known() ) { return $links; }
		$name = __( 'ContactBridge by Tsambasis', 'tsambasis-contact-bridge' );
		$url = add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => self::SLUG, 'tscb_local_details' => '1', 'TB_iframe' => 'true', 'width' => 772, 'height' => 600 ), network_admin_url( 'plugin-install.php' ) );
		/* translators: %s: The plugin's translated name. */
		$label = sprintf( __( 'View details about %s', 'tsambasis-contact-bridge' ), $name );
		$links[] = '<a class="thickbox open-plugin-details-modal tscb-local-details" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '" data-title="' . esc_attr( $name ) . '">' . esc_html__( 'View details', 'tsambasis-contact-bridge' ) . '</a>';
		return $links;
	}

	/** Share the same fixed request and capability boundary with the modal-only styling. */
	private static function local_request() {
		global $pagenow;
		if ( ! is_admin() || 'plugin-install.php' !== $pagenow || ! current_user_can( 'install_plugins' ) || ! self::active() ) { return false; }
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Fixed read-only modal selection; no settings or records are changed, and WordPress's installer capability is required above.
		$local = isset( $_GET['tscb_local_details'] ) && is_string( $_GET['tscb_local_details'] ) && '1' === $_GET['tscb_local_details'];
		$tab = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) && 'plugin-information' === $_GET['tab'];
		$plugin = isset( $_GET['plugin'] ) && is_string( $_GET['plugin'] ) && self::SLUG === $_GET['plugin'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return $local && $tab && $plugin && ! self::directory_known();
	}

	/** Add no public/global CSS; Core permits the image class in the modal description. */
	public static function styles() {
		if ( self::local_request() ) {
			wp_add_inline_style( 'common', '#plugin-information-scrollable img.tscb-local-info-icon{display:block;width:72px;height:72px;object-fit:contain;margin:0 0 16px;}' );
		}
	}

	/** Supply only explicit local information; every other API action/result stays untouched. */
	public static function information( $result, $action, $args ) {
		if ( false !== $result || 'plugin_information' !== $action || ! is_object( $args ) || ! isset( $args->slug ) || self::SLUG !== $args->slug || ! self::local_request() ) { return $result; }
		$name = __( 'ContactBridge by Tsambasis', 'tsambasis-contact-bridge' );
		$icon = '<p><img class="tscb-local-info-icon" src="' . esc_url( TSCB_URL . 'assets/plugin-icon.png' ) . '" alt="' . esc_attr( $name ) . '" /></p>';
		$description = $icon . '<p>' . esc_html__( 'A free, ad-free contact form for your WordPress website. Build your fields, choose a design and add the form with a shortcode.', 'tsambasis-contact-bridge' ) . '</p><p>' . esc_html__( 'Receive inquiries by email, Telegram or WhatsApp. Email works with your existing WordPress mail setup or optional authenticated SMTP for this plugin.', 'tsambasis-contact-bridge' ) . '</p><p>' . esc_html__( 'This information comes from the installed plugin. It does not represent a WordPress.org listing, rating or download count.', 'tsambasis-contact-bridge' ) . '</p><p>' . self::more_link() . '</p>';
		$installation = '<ol><li>' . esc_html__( 'Open the plugin settings and select your delivery channels. Save the recipient and connection details, then send a test message.', 'tsambasis-contact-bridge' ) . '</li><li>' . esc_html__( 'Choose a preset or customize the fields and appearance. The live preview shows your draft without sending any messages.', 'tsambasis-contact-bridge' ) . '</li><li>' . esc_html__( 'Add this shortcode to a page or a Shortcode block:', 'tsambasis-contact-bridge' ) . ' <code>[contact_bridge]</code></li></ol>';
		$privacy = '<p>' . esc_html__( 'For each delivery channel, choose full-content delivery or extended privacy. Extended privacy stores the inquiry encrypted on your website and sends only a general notification with a link that requires an administrator login.', 'tsambasis-contact-bridge' ) . '</p><p>' . esc_html__( 'Stored inquiries use the retention period selected when they arrive: 30 days by default, a custom period or unlimited retention. Administrators can read and permanently delete them; the WordPress privacy tools support export and erasure.', 'tsambasis-contact-bridge' ) . '</p><p>' . esc_html__( 'The plugin includes no advertising, tracking, remote fonts or analytics. External delivery services are contacted only when their channels are configured and used. Your site operator remains responsible for the privacy notice and provider agreements.', 'tsambasis-contact-bridge' ) . '</p>';
		$metadata = get_file_data( TSCB_FILE, array( 'requires' => 'Requires at least', 'requires_php' => 'Requires PHP', 'author' => 'Author', 'author_uri' => 'Author URI' ), 'plugin' );
		return (object) array(
			'name' => esc_html( $name ),
			'slug' => self::SLUG,
			'version' => esc_html( TSCB_VERSION ),
			'author' => '<a href="' . esc_url( $metadata['author_uri'] ) . '">' . esc_html( $metadata['author'] ) . '</a>',
			'requires' => esc_html( $metadata['requires'] ),
			'requires_php' => esc_html( $metadata['requires_php'] ),
			'external' => true,
			'sections' => array( 'description' => $description, 'installation' => $installation, 'other_notes' => '<h3>' . esc_html__( 'Privacy and data handling', 'tsambasis-contact-bridge' ) . '</h3>' . $privacy ),
		);
	}
}
