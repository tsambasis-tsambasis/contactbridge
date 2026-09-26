<?php
/** Encrypted, administrator-only contact inbox. @package ContactBridge */
defined( 'ABSPATH' ) || exit;

final class TSCB_Inbox {
	const TYPE = 'tscb_inquiry';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_tscb_delete_inquiry', array( __CLASS__, 'handle_delete' ) );
		add_action( 'tscb_cleanup', array( __CLASS__, 'cleanup' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'erasers' ) );
	}

	public static function register() {
		$caps = array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts', 'read' ), 'manage_options' );
		register_post_type( self::TYPE, array( 'label' => __( 'Contact inquiries', 'tsambasis-contact-bridge' ), 'public' => false, 'publicly_queryable' => false, 'show_ui' => false, 'show_in_rest' => false, 'show_in_menu' => false, 'query_var' => false, 'rewrite' => false, 'exclude_from_search' => true, 'can_export' => false, 'supports' => false, 'capabilities' => $caps, 'map_meta_cap' => false, 'delete_with_user' => false ) );
	}

	public static function supports_storage() {
		return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' ) && in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true );
	}

	public static function any_enabled( $settings ) {
		foreach ( array( 'email', 'telegram', 'whatsapp' ) as $channel ) {
			if ( ! empty( $settings[ 'privacy_' . $channel ] ) && isset( $settings['channels'] ) && is_array( $settings['channels'] ) && in_array( $channel, $settings['channels'], true ) ) { return true; }
		}
		return false;
	}

	private static function error( $code = 'inbox_storage' ) {
		$messages = array(
			'inbox_storage' => __( 'The inquiry could not be stored securely. Please try again later.', 'tsambasis-contact-bridge' ),
			'inbox_forbidden' => __( 'You do not have permission to view or delete inquiries.', 'tsambasis-contact-bridge' ),
			'inbox_missing' => __( 'This inquiry is unavailable or its retention period has ended.', 'tsambasis-contact-bridge' ),
			'inbox_decrypt' => __( 'This inquiry could not be decrypted. The WordPress security keys may have changed.', 'tsambasis-contact-bridge' ),
			'inbox_privacy_state' => __( 'The inquiry privacy task expired or its page order changed. Please restart the export or erasure request.', 'tsambasis-contact-bridge' ),
			'inbox_retention' => __( 'This server cannot represent the selected retention period. Choose fewer days or unlimited retention.', 'tsambasis-contact-bridge' ),
		);
		return new WP_Error( $code, isset( $messages[ $code ] ) ? $messages[ $code ] : $messages['inbox_storage'] );
	}

	/** A separate key and authenticated context prevent SMTP/blog ciphertext reuse. */
	private static function key() { return hash_hmac( 'sha256', 'contact-bridge/inbox/key/v1/blog/' . get_current_blog_id(), wp_salt( 'auth' ), true ); }
	private static function aad( $created, $expires ) { return 'contact-bridge/inbox/v1/blog/' . get_current_blog_id() . '/' . $created . '/' . $expires; }

	/** Copy only validated form answers, never request metadata or arbitrary caller keys. */
	private static function payload( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['fields'] ) || ! is_array( $data['fields'] ) || count( $data['fields'] ) > 20 ) { return false; }
		$clean = array();
		foreach ( array( 'name', 'email', 'subject', 'message' ) as $key ) {
			$value = isset( $data[ $key ] ) ? $data[ $key ] : '';
			if ( ! is_string( $value ) || strlen( $value ) > 48000 || wp_check_invalid_utf8( $value ) !== $value ) { return false; }
			$clean[ $key ] = $value;
		}
		$clean['fields'] = array();
		foreach ( $data['fields'] as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['id'], $field['type'], $field['label'], $field['value'] ) || ! is_string( $field['id'] ) || ! preg_match( '/^(?:name|email|subject|message|f_[a-z0-9]{8,24})$/D', $field['id'] ) || ! in_array( $field['type'], array( 'text', 'email', 'tel', 'textarea', 'select', 'checkbox' ), true ) || ! is_string( $field['label'] ) || strlen( $field['label'] ) > 600 || ! is_string( $field['value'] ) || strlen( $field['value'] ) > 48000 ) { return false; }
			$clean['fields'][] = array_intersect_key( $field, array_flip( array( 'id', 'type', 'label', 'value' ) ) );
		}
		$json = wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return is_string( $json ) && strlen( $json ) <= 150000 ? $clean : false;
	}

	/** Create only after normal form validation; callers serialize retries using their submission lock. */
	public static function create( $data, $settings, $dedupe_key = '' ) {
		if ( ! self::supports_storage() || ! self::any_enabled( $settings ) ) { return self::error(); }
		$clean = self::payload( $data );
		if ( false === $clean || ! is_string( $dedupe_key ) || ( '' !== $dedupe_key && ! preg_match( '/^[a-f0-9]{64}$/D', $dedupe_key ) ) ) { return self::error(); }
		$slug = '' !== $dedupe_key ? 'tscb-' . hash_hmac( 'sha256', 'inbox-dedup/' . $dedupe_key, self::key() ) : '';
		if ( '' !== $slug ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- This private encrypted datastore has no language variants; hiding an existing HMAC slug through presentation filters would break retry deduplication.
			$existing = get_posts( array( 'post_type' => self::TYPE, 'post_status' => 'private', 'name' => $slug, 'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => true ) );
			if ( $existing ) {
				$record = self::read( $existing[0] );
				if ( is_wp_error( $record ) ) { return $record; }
				// Repair retention metadata if a prior process stopped immediately after its row insert.
				update_post_meta( $existing[0], '_tscb_created', $record['created'] );
				update_post_meta( $existing[0], '_tscb_expires', $record['expires'] );
				return (int) $existing[0];
			}
		}
		$created = time();
		$days = isset( $settings['inbox_retention_days'] ) && is_numeric( $settings['inbox_retention_days'] ) ? max( 0, min( 36500, (int) $settings['inbox_retention_days'] ) ) : 30;
		if ( $days > intdiv( PHP_INT_MAX - $created, DAY_IN_SECONDS ) ) { return self::error( 'inbox_retention' ); }
		$expires = $days ? $created + $days * DAY_IN_SECONDS : 0;
		try {
			$iv = random_bytes( 12 ); $tag = '';
			$cipher = openssl_encrypt( wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, self::aad( $created, $expires ), 16 );
			if ( false === $cipher || 16 !== strlen( $tag ) ) { return self::error(); }
			// Only timestamps and cryptographic structure are plaintext; all form answers are encrypted.
			$content = wp_json_encode( array( 'v' => 1, 'created' => $created, 'expires' => $expires, 'iv' => base64_encode( $iv ), 'tag' => base64_encode( $tag ), 'cipher' => base64_encode( $cipher ) ) );
		} catch ( Throwable $error ) { return self::error(); }
		$id = wp_insert_post( wp_slash( array( 'post_type' => self::TYPE, 'post_status' => 'private', 'post_author' => 0, 'post_title' => 'Contact inquiry', 'post_name' => $slug, 'post_content' => $content, 'post_excerpt' => '', 'comment_status' => 'closed', 'ping_status' => 'closed', 'meta_input' => array( '_tscb_created' => $created, '_tscb_expires' => $expires ) ) ), true );
		return is_wp_error( $id ) || ! $id ? self::error() : (int) $id;
	}

	/** Internal read is also used by authorized privacy callbacks; it never exposes expired answers. */
	private static function read( $id, $allow_expired = false ) {
		$post = get_post( (int) $id );
		if ( ! $post || self::TYPE !== $post->post_type || 'private' !== $post->post_status ) { return self::error( 'inbox_missing' ); }
		$envelope = json_decode( $post->post_content, true );
		if ( ! is_array( $envelope ) || ! isset( $envelope['v'], $envelope['created'], $envelope['expires'], $envelope['iv'], $envelope['tag'], $envelope['cipher'] ) || 1 !== $envelope['v'] || ! is_int( $envelope['created'] ) || ! is_int( $envelope['expires'] ) || $envelope['created'] < 1 || $envelope['expires'] < 0 || ( $envelope['expires'] > 0 && $envelope['expires'] < $envelope['created'] ) ) { return self::error( 'inbox_decrypt' ); }
		if ( ! $allow_expired && $envelope['expires'] && $envelope['expires'] <= time() ) { return self::error( 'inbox_missing' ); }
		if ( ! self::supports_storage() ) { return self::error( 'inbox_decrypt' ); }
		foreach ( array( 'iv', 'tag', 'cipher' ) as $key ) {
			if ( ! is_string( $envelope[ $key ] ) || strlen( $envelope[ $key ] ) > 210000 ) { return self::error( 'inbox_decrypt' ); }
			$envelope[ $key ] = base64_decode( $envelope[ $key ], true );
			if ( false === $envelope[ $key ] ) { return self::error( 'inbox_decrypt' ); }
		}
		if ( 12 !== strlen( $envelope['iv'] ) || 16 !== strlen( $envelope['tag'] ) ) { return self::error( 'inbox_decrypt' ); }
		try { $plain = openssl_decrypt( $envelope['cipher'], 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $envelope['iv'], $envelope['tag'], self::aad( $envelope['created'], $envelope['expires'] ) ); }
		catch ( Throwable $error ) { return self::error( 'inbox_decrypt' ); }
		$data = is_string( $plain ) ? self::payload( json_decode( $plain, true ) ) : false;
		if ( false === $data ) { return self::error( 'inbox_decrypt' ); }
		return array_merge( $data, array( 'id' => (int) $id, 'created' => $envelope['created'], 'expires' => $envelope['expires'] ) );
	}

	public static function get( $id ) { return current_user_can( 'manage_options' ) ? self::read( $id ) : self::error( 'inbox_forbidden' ); }
	public static function delete( $id ) {
		if ( ! current_user_can( 'manage_options' ) ) { return self::error( 'inbox_forbidden' ); }
		return self::remove( $id );
	}
	private static function remove( $id ) {
		$post = get_post( (int) $id );
		return $post && self::TYPE === $post->post_type ? (bool) wp_delete_post( $post->ID, true ) : false;
	}

	/** At most 100 expired records per hourly/admin pass; no unbounded payload loads. */
	public static function cleanup() {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query, WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- Expiry metadata is required to find expired/interrupted records, bounded to 100 private-CPT IDs. Presentation or language filters must not hide records from mandatory retention cleanup.
		$ids = get_posts( array( 'post_type' => self::TYPE, 'post_status' => array( 'private', 'trash' ), 'posts_per_page' => 100, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'meta_query' => array( 'relation' => 'OR', array( 'key' => '_tscb_expires', 'value' => array( 1, time() ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ), array( 'key' => '_tscb_expires', 'compare' => 'NOT EXISTS' ) ), 'suppress_filters' => true ) );
		foreach ( $ids as $id ) {
			if ( metadata_exists( 'post', $id, '_tscb_expires' ) ) { self::remove( $id ); continue; }
			// Recover index metadata after an interrupted insert so orphaned rows also expire.
			$post = get_post( $id );
			$envelope = $post ? json_decode( $post->post_content, true ) : null;
			if ( is_array( $envelope ) && isset( $envelope['created'], $envelope['expires'] ) && is_int( $envelope['created'] ) && is_int( $envelope['expires'] ) && $envelope['expires'] >= 0 && ( 0 === $envelope['expires'] || $envelope['expires'] > time() ) ) {
				update_post_meta( $id, '_tscb_created', $envelope['created'] );
				update_post_meta( $id, '_tscb_expires', $envelope['expires'] );
			} else { self::remove( $id ); }
		}
	}

	public static function admin_url( $id = 0 ) {
		$args = array( 'page' => 'tscb-inquiries' );
		if ( (int) $id > 0 ) { $args['inquiry'] = (int) $id; }
		return add_query_arg( $args, admin_url( 'options-general.php' ) );
	}
	public static function menu() { add_options_page( __( 'Contact inquiries', 'tsambasis-contact-bridge' ), __( 'Contact inquiries', 'tsambasis-contact-bridge' ), 'manage_options', 'tscb-inquiries', array( __CLASS__, 'render' ) ); }

	public static function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html( self::error( 'inbox_forbidden' )->get_error_message() ), '', array( 'response' => 403 ) ); }
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) { wp_die( esc_html__( 'Please submit the form.', 'tsambasis-contact-bridge' ), '', array( 'response' => 405 ) ); }
		$id = isset( $_POST['inquiry'] ) && is_scalar( $_POST['inquiry'] ) ? absint( $_POST['inquiry'] ) : 0;
		check_admin_referer( 'tscb_delete_inquiry_' . $id );
		self::delete( $id );
		wp_safe_redirect( self::admin_url() );
		exit;
	}

	private static function delete_form( $id ) {
		?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="tscb_delete_inquiry" /><input type="hidden" name="inquiry" value="<?php echo esc_attr( (string) $id ); ?>" /><?php wp_nonce_field( 'tscb_delete_inquiry_' . $id ); ?><button type="submit" class="button"><?php esc_html_e( 'Permanently delete inquiry', 'tsambasis-contact-bridge' ); ?></button></form><?php
	}
	private static function date( $timestamp ) { return gmdate( 'Y-m-d H:i', $timestamp ) . ' UTC'; }

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html( self::error( 'inbox_forbidden' )->get_error_message() ), '', array( 'response' => 403 ) ); }
		nocache_headers();
		self::cleanup();
		// Read-only navigation values; all mutations use the separately nonced POST action.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$id = isset( $_GET['inquiry'] ) && is_scalar( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;
		$page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		?><div class="wrap"><h1><?php esc_html_e( 'Contact inquiries', 'tsambasis-contact-bridge' ); ?></h1><p><?php esc_html_e( 'Messages are encrypted in your WordPress database and are visible only to administrators. Expired inquiries are automatically deleted. Unrestricted retention keeps inquiries until you delete them.', 'tsambasis-contact-bridge' ); ?></p><?php
		if ( $id ) {
			$record = self::get( $id );
			?><p><a href="<?php echo esc_url( self::admin_url() ); ?>"><?php esc_html_e( 'Back to all inquiries', 'tsambasis-contact-bridge' ); ?></a></p><?php
			if ( is_wp_error( $record ) ) { ?><div class="notice notice-error"><p><?php echo esc_html( $record->get_error_message() ); ?></p></div><?php }
			else {
				?><p><strong><?php esc_html_e( 'Received', 'tsambasis-contact-bridge' ); ?>:</strong> <?php echo esc_html( self::date( $record['created'] ) ); ?> · <strong><?php esc_html_e( 'Automatic deletion', 'tsambasis-contact-bridge' ); ?>:</strong> <?php echo esc_html( $record['expires'] ? self::date( $record['expires'] ) : __( 'No time limit', 'tsambasis-contact-bridge' ) ); ?></p><table class="widefat striped"><tbody><?php
				foreach ( $record['fields'] as $field ) { ?><tr><th scope="row" style="width:25%;overflow-wrap:anywhere"><?php echo esc_html( $field['label'] ); ?></th><td style="white-space:pre-wrap;overflow-wrap:anywhere"><?php echo esc_html( $field['value'] ); ?></td></tr><?php }
				?></tbody></table><p><?php esc_html_e( 'Deleting an inquiry is permanent.', 'tsambasis-contact-bridge' ); ?></p><?php
			}
			$post = get_post( $id );
			if ( $post && self::TYPE === $post->post_type ) { self::delete_form( $id ); }
		} else {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The administrator-only inbox must exclude expired records before pagination; query only this private CPT and decrypt at most 20 returned IDs.
			$query = new WP_Query( array( 'post_type' => self::TYPE, 'post_status' => 'private', 'posts_per_page' => 20, 'paged' => $page, 'orderby' => 'ID', 'order' => 'DESC', 'fields' => 'ids', 'meta_query' => array( 'relation' => 'OR', array( 'key' => '_tscb_expires', 'value' => 0, 'compare' => '=', 'type' => 'NUMERIC' ), array( 'key' => '_tscb_expires', 'value' => time(), 'compare' => '>', 'type' => 'NUMERIC' ) ) ) );
			if ( ! $query->posts ) { ?><p><?php esc_html_e( 'No inquiries are currently stored.', 'tsambasis-contact-bridge' ); ?></p><?php }
			else {
				?><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Received', 'tsambasis-contact-bridge' ); ?></th><th><?php esc_html_e( 'Subject', 'tsambasis-contact-bridge' ); ?></th><th><?php esc_html_e( 'Automatic deletion', 'tsambasis-contact-bridge' ); ?></th><th><?php esc_html_e( 'Actions', 'tsambasis-contact-bridge' ); ?></th></tr></thead><tbody><?php
				foreach ( $query->posts as $record_id ) {
					$record = self::get( $record_id );
					if ( is_wp_error( $record ) && 'inbox_missing' === $record->get_error_code() ) { continue; }
					?><tr><td><?php echo esc_html( is_wp_error( $record ) ? '—' : self::date( $record['created'] ) ); ?></td><td style="overflow-wrap:anywhere"><a href="<?php echo esc_url( self::admin_url( $record_id ) ); ?>"><?php echo esc_html( is_wp_error( $record ) ? __( 'Encrypted inquiry', 'tsambasis-contact-bridge' ) : ( '' !== trim( $record['subject'] ) ? $record['subject'] : __( 'Contact inquiry', 'tsambasis-contact-bridge' ) ) ); ?></a></td><td><?php echo esc_html( is_wp_error( $record ) ? '—' : ( $record['expires'] ? self::date( $record['expires'] ) : __( 'No time limit', 'tsambasis-contact-bridge' ) ) ); ?></td><td><?php self::delete_form( $record_id ); ?></td></tr><?php
				}
				?></tbody></table><?php
				$links = paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', self::admin_url() ), 'format' => '', 'current' => $page, 'total' => (int) $query->max_num_pages ) );
				if ( $links ) { ?><div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post( $links ); ?></div></div><?php }
			}
		}
		?></div><?php
	}

	public static function exporters( $exporters ) { $exporters['tscb-inquiries'] = array( 'exporter_friendly_name' => __( 'Contact inquiries', 'tsambasis-contact-bridge' ), 'callback' => array( __CLASS__, 'export_personal_data' ) ); return $exporters; }
	public static function erasers( $erasers ) { $erasers['tscb-inquiries'] = array( 'eraser_friendly_name' => __( 'Contact inquiries', 'tsambasis-contact-bridge' ), 'callback' => array( __CLASS__, 'erase_personal_data' ) ); return $erasers; }

	private static function matches_email( $record, $email ) {
		if ( isset( $record['email'] ) && 0 === strcasecmp( trim( $record['email'] ), $email ) ) { return true; }
		foreach ( $record['fields'] as $field ) { if ( 'email' === $field['type'] && 0 === strcasecmp( trim( $field['value'] ), $email ) ) { return true; } }
		return false;
	}
	/** ID cursors prevent deleting matching rows from skipping later pages. Stored cursors contain no email. */
	private static function privacy_batch( $email, $page, $erase ) {
		global $wpdb;
		if ( ! current_user_can( 'manage_options' ) ) { return self::error( 'inbox_forbidden' ); }
		if ( ! is_string( $email ) || ! is_email( $email ) ) { return self::error( 'inbox_privacy_state' ); }
		$page = max( 1, (int) $page );
		$key = 'tscb_inbox_privacy_' . hash_hmac( 'sha256', ( $erase ? 'erase/' : 'export/' ) . strtolower( $email ) . '/' . get_current_user_id(), self::key() );
		$state = $page > 1 ? get_transient( $key ) : null;
		if ( $page > 1 && ( ! is_array( $state ) || ! isset( $state['page'], $state['cursor'], $state['ids'], $state['done'] ) ) ) { return self::error( 'inbox_privacy_state' ); }
		if ( $page > 1 && $page === $state['page'] ) { return array( $state['ids'], $state['done'] ); }
		if ( $page > 1 && $page !== $state['page'] + 1 ) { return self::error( 'inbox_privacy_state' ); }
		$cursor = $page > 1 ? $state['cursor'] : 0;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded ID cursor is required because erasure changes the result set between privacy batches.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID > %d ORDER BY ID ASC LIMIT 50", self::TYPE, (int) $cursor ) );
		$done = count( $ids ) < 50;
		set_transient( $key, array( 'page' => $page, 'cursor' => $ids ? (int) end( $ids ) : (int) $cursor, 'ids' => $ids, 'done' => $done ), HOUR_IN_SECONDS );
		return array( $ids, $done );
	}
	public static function export_personal_data( $email, $page = 1 ) {
		$batch = self::privacy_batch( $email, $page, false );
		if ( is_wp_error( $batch ) ) { return $batch; }
		list( $ids, $done ) = $batch;
		$items = array();
		foreach ( $ids as $id ) {
			$record = self::read( $id );
			if ( is_wp_error( $record ) && 'inbox_decrypt' === $record->get_error_code() ) { return $record; }
			if ( is_wp_error( $record ) || ! self::matches_email( $record, $email ) ) { continue; }
			$values = array( array( 'name' => __( 'Received', 'tsambasis-contact-bridge' ), 'value' => self::date( $record['created'] ) ), array( 'name' => __( 'Automatic deletion', 'tsambasis-contact-bridge' ), 'value' => $record['expires'] ? self::date( $record['expires'] ) : __( 'No time limit', 'tsambasis-contact-bridge' ) ) );
			foreach ( $record['fields'] as $field ) { $values[] = array( 'name' => $field['label'], 'value' => $field['value'] ); }
			$items[] = array( 'group_id' => 'tscb-inquiries', 'group_label' => __( 'Contact inquiries', 'tsambasis-contact-bridge' ), 'item_id' => 'tscb-inquiry-' . $id, 'data' => $values );
		}
		return array( 'data' => $items, 'done' => $done );
	}
	public static function erase_personal_data( $email, $page = 1 ) {
		$batch = self::privacy_batch( $email, $page, true );
		if ( is_wp_error( $batch ) ) { return $batch; }
		list( $ids, $done ) = $batch;
		$removed = false; $retained = false;
		foreach ( $ids as $id ) {
			// Erasure must also remove matching expired ciphertext while cron deletion is pending.
			$record = self::read( $id, true );
			if ( is_wp_error( $record ) && 'inbox_decrypt' === $record->get_error_code() ) { $retained = true; continue; }
			if ( is_wp_error( $record ) || ! self::matches_email( $record, $email ) ) { continue; }
			if ( self::remove( $id ) ) { $removed = true; } else { $retained = true; }
		}
		return array( 'items_removed' => $removed, 'items_retained' => $retained, 'messages' => $retained ? array( __( 'Some inquiries could not be decrypted or deleted, so their personal data could not be fully checked. Please ask the site administrator to review them.', 'tsambasis-contact-bridge' ) ) : array(), 'done' => $done );
	}
}
