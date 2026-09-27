<?php
/**
 * Accessible shortcode form and its progressively enhanced assets.
 *
 * @package ContactBridge
 */

defined( 'ABSPATH' ) || exit;

/** Frontend rendering. */
final class TSCB_Form {

	/** @var int Predictable per-request instance counter for redirect anchors. */
	private static $instance_count = 0;

	/** Register shortcode and front-end assets. */
	public static function init() {
		add_shortcode( 'contact_bridge', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/** Register locally served assets, loading early for ordinary post content. */
	public static function register_assets() {
		wp_register_style( 'tscb-form', TSCB_URL . 'assets/form.css', array(), TSCB_VERSION );
		wp_register_script( 'tscb-form', TSCB_URL . 'assets/form.js', array(), TSCB_VERSION, true );

		$post = get_post();
		if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'contact_bridge' ) ) {
			wp_enqueue_style( 'tscb-form' );
			wp_enqueue_script( 'tscb-form' );
		}
	}

	/**
	 * Render an independent contact form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Complete form HTML.
	 */
	public static function shortcode( $atts = array() ) {
		return self::render( TSCB_Settings::get(), $atts );
	}

	/**
	 * Render a non-submitting settings preview with the public form markup.
	 *
	 * @param array  $settings Sanitized settings for the preview.
	 * @param string $state    Preview state: form, success, or error.
	 * @return string Preview HTML without credentials, endpoints, or a real form.
	 */
	public static function preview( $settings, $state = 'form' ) {
		return self::render( $settings, array(), true, $state );
	}

	/**
	 * Shared public and preview renderer.
	 *
	 * @param array  $settings      Form settings.
	 * @param array  $atts          Shortcode attributes.
	 * @param bool   $preview       Whether to omit all submission functionality.
	 * @param string $preview_state Preview state: form, success, or error.
	 * @return string Complete form or preview HTML.
	 */
	public static function render( $settings, $atts = array(), $preview = false, $preview_state = 'form' ) {
		$settings = array_merge( TSCB_Settings::defaults(), TSCB_Design::defaults(), is_array( $settings ) ? $settings : array() );
		$atts     = shortcode_atts(
			array(
				'theme'   => $settings['theme'],
				'shape'   => $settings['shape'],
				'heading' => $settings['heading'],
				'embedded' => '',
			),
			$atts,
			'contact_bridge'
		);
		$theme    = in_array( $atts['theme'], array( 'light', 'dark', 'auto' ), true ) ? $atts['theme'] : 'light';
		$shape    = in_array( $atts['shape'], array( 'rounded', 'square' ), true ) ? $atts['shape'] : 'rounded';
		$heading  = sanitize_text_field( $atts['heading'] );
		$embedded = ! empty( $settings['embedded'] );
		if ( in_array( $atts['embedded'], array( 'true', '1' ), true ) ) {
			$embedded = true;
		} elseif ( in_array( $atts['embedded'], array( 'false', '0' ), true ) ) {
			$embedded = false;
		}
		$accent   = sanitize_hex_color( $settings['accent'] );
		$accent   = $accent ? $accent : '#22624b';
		$instance = 'tscb-form-' . ++self::$instance_count;
		$fields   = TSCB_Fields::get( $settings );
		$interactive = ! $preview && ! empty( $fields );
		$return   = '';
		if ( ! $preview ) {
			$return = get_permalink();
			$return = $return ? $return : home_url( '/' );
			$return = add_query_arg( 'tscb_form', $instance, $return ) . '#' . $instance;
		}
		$status   = '';

		// Public display-only result codes contain no contact data or privileged actions.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! $preview && isset( $_GET['tscb_status'] ) && is_string( $_GET['tscb_status'] ) ) {
			$target = isset( $_GET['tscb_form'] ) && is_string( $_GET['tscb_form'] ) ? sanitize_key( wp_unslash( $_GET['tscb_form'] ) ) : '';
			if ( '' === $target || $instance === $target ) {
				$status = sanitize_key( wp_unslash( $_GET['tscb_status'] ) );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( $preview ) {
			$status = 'success' === $preview_state ? 'ok' : ( 'error' === $preview_state ? 'invalid' : '' );
		}
		if ( ! $fields ) {
			$status = 'unconfigured';
		}

		$messages = array(
			'ok'           => $settings['success_message'],
			'invalid'      => __( 'Please check your details and complete all required fields.', 'contactbridge' ),
			'expired'      => __( 'Your session has expired. Reload the page and send your message again.', 'contactbridge' ),
			'spam'         => __( 'Your message could not be sent. Please wait a moment and try again.', 'contactbridge' ),
			'rate'         => __( 'You have sent several messages in a short time. Please try again later.', 'contactbridge' ),
			'failed'       => __( 'Your message could not be sent. Please try again later.', 'contactbridge' ),
			'unconfigured' => __( 'The contact form is currently unavailable. Please use another way to get in touch.', 'contactbridge' ),
		);
		$message  = isset( $messages[ $status ] ) ? $messages[ $status ] : '';
		$state    = '' !== $message ? ( 'ok' === $status ? 'success' : 'error' ) : '';
		$consent  = ! empty( $settings['require_consent'] );
		$classes  = array( 'tscb', 'tscb--' . $theme, 'tscb--' . $shape );
		if ( $embedded ) {
			$classes[] = 'tscb--embedded';
		}
		foreach ( array( 'layout' => array( 'two-column', 'single-column' ), 'spacing' => array( 'comfortable', 'compact', 'airy' ), 'button_align' => array( 'left', 'center', 'right', 'stretch' ) ) as $key => $allowed ) {
			$value = in_array( $settings[ $key ], $allowed, true ) ? $settings[ $key ] : $allowed[0];
			$classes[] = 'tscb--' . str_replace( '_', '-', $key ) . '-' . $value;
		}
		if ( empty( $settings['shadow'] ) ) {
			$classes[] = 'tscb--no-shadow';
		}
		if ( $preview ) {
			$classes[] = 'tscb--preview';
		}
		$preview_error = '';
		if ( $preview && 'error' === $preview_state && $fields ) {
			$preview_error = $fields[0]['id'];
			foreach ( $fields as $field ) {
				if ( 'message' === $field['id'] ) {
					$preview_error = 'message';
					break;
				}
			}
		}

		ob_start();
		if ( ! $preview ) {
			if ( ! wp_style_is( 'tscb-form', 'registered' ) ) {
				self::register_assets();
			}
			wp_enqueue_style( 'tscb-form' );
			wp_enqueue_script( 'tscb-form' );
			// Widgets and template shortcodes can render after wp_head has already run.
			if ( did_action( 'wp_head' ) && ! wp_style_is( 'tscb-form', 'done' ) ) {
				wp_print_styles( array( 'tscb-form' ) );
			}
		}
		?>
		<section lang="<?php echo esc_attr( str_replace( '_', '-', TSCB_I18n::locale() ) ); ?>" id="<?php echo esc_attr( $instance ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( self::style_variables( $settings, $accent ) ); ?>" aria-labelledby="<?php echo esc_attr( $instance ); ?>-heading">
			<header class="tscb__header">
				<?php if ( '' !== $settings['eyebrow'] ) : ?>
					<p class="tscb__eyebrow"><span class="tscb__dot" aria-hidden="true"></span><?php echo esc_html( $settings['eyebrow'] ); ?></p>
				<?php endif; ?>
				<h2 id="<?php echo esc_attr( $instance ); ?>-heading" class="tscb__heading"><?php echo esc_html( '' !== $heading ? $heading : __( 'Send us a message.', 'contactbridge' ) ); ?></h2>
				<?php if ( ! empty( $settings['intro'] ) ) : ?>
					<p class="tscb__intro"><?php echo esc_html( $settings['intro'] ); ?></p>
				<?php endif; ?>
			</header>
			<?php if ( ! $interactive ) : ?>
			<div class="tscb__form" role="form" aria-labelledby="<?php echo esc_attr( $instance ); ?>-heading">
			<?php else : ?>
			<form class="tscb__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-tscb-form data-endpoint="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-pending="<?php esc_attr_e( 'Sending …', 'contactbridge' ); ?>" data-network-error="<?php esc_attr_e( 'We could not confirm that your message was sent. Please try again later.', 'contactbridge' ); ?>" data-validation-error="<?php esc_attr_e( 'Please check the highlighted fields.', 'contactbridge' ); ?>" data-success="<?php echo esc_attr( $settings['success_message'] ); ?>">
				<input type="hidden" name="action" value="tscb_submit">
				<input type="hidden" name="tscb_nonce" value="<?php echo esc_attr( wp_create_nonce( 'tscb_submit' ) ); ?>">
				<input type="hidden" name="tscb_id" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>">
				<input type="hidden" name="tscb_schema" value="<?php echo esc_attr( TSCB_Fields::signature( $settings ) ); ?>">
				<input type="hidden" name="tscb_started" value="<?php echo esc_attr( (string) time() ); ?>">
				<input type="hidden" name="tscb_return" value="<?php echo esc_url( $return ); ?>">
				<div class="tscb__honeypot" aria-hidden="true" inert>
					<label for="<?php echo esc_attr( $instance ); ?>-company"><?php esc_html_e( 'Please leave this field empty.', 'contactbridge' ); ?></label>
					<input id="<?php echo esc_attr( $instance ); ?>-company" name="tscb_company" type="text" value="" tabindex="-1" autocomplete="off">
				</div>
			<?php endif; ?>
				<?php if ( ! empty( $settings['show_required_note'] ) && '' !== $settings['required_note'] ) : ?>
					<p class="tscb__required-note"><?php echo esc_html( $settings['required_note'] ); ?></p>
				<?php endif; ?>
				<?php $delivery_hint = TSCB_Fields::delivery_hint( $settings ); ?>
				<?php if ( '' !== $delivery_hint && $fields ) : ?>
					<p class="tscb__hint tscb__delivery-hint"><?php echo esc_html( $delivery_hint ); ?></p>
				<?php endif; ?>
				<div class="tscb__grid">
					<?php foreach ( $fields as $field ) : ?>
						<?php self::field( $field, $settings, $instance, $preview, $preview_error === $field['id'] ); ?>
					<?php endforeach; ?>
				</div>
				<?php if ( $consent ) : ?>
					<div class="tscb__consent-field">
						<div class="tscb__consent">
							<label class="tscb__checkbox-target" for="<?php echo esc_attr( $instance ); ?>-consent"><input id="<?php echo esc_attr( $instance ); ?>-consent" name="tscb_consent" type="checkbox" value="1" required aria-labelledby="<?php echo esc_attr( $instance ); ?>-consent-copy" aria-describedby="<?php echo esc_attr( $instance ); ?>-consent-error"></label>
							<div id="<?php echo esc_attr( $instance ); ?>-consent-copy" class="tscb__consent-copy"><?php self::consent_text( $settings, $instance, $preview ); ?><?php self::required_marker(); ?></div>
						</div>
						<p class="tscb__field-error" id="<?php echo esc_attr( $instance ); ?>-consent-error" data-error-for="tscb_consent" hidden></p>
					</div>
				<?php elseif ( ! empty( $settings['privacy_url'] ) ) : ?>
					<?php self::privacy_link( $settings, $preview ); ?>
				<?php endif; ?>
				<div class="tscb__footer">
					<button class="tscb__submit" type="<?php echo $interactive ? 'submit' : 'button'; ?>"<?php echo $fields ? '' : ' disabled'; ?>><span data-tscb-button-label><?php echo esc_html( $settings['submit_label'] ); ?></span><?php if ( ! empty( $settings['button_arrow'] ) ) : ?><span class="tscb__arrow" aria-hidden="true">↗</span><?php endif; ?></button>
				</div>
				<div class="tscb__status" role="status" aria-live="polite" aria-atomic="true" tabindex="-1" data-tscb-status data-state="<?php echo esc_attr( $state ); ?>"<?php echo '' === $message ? ' hidden' : ''; ?>><?php echo esc_html( $message ); ?></div>
			<?php if ( ! $interactive ) : ?>
			</div>
			<?php else : ?>
				<noscript><p class="tscb__hint"><?php esc_html_e( 'This page will reload after you submit the form. Your confirmation will then appear next to the form.', 'contactbridge' ); ?></p></noscript>
			</form>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render one validated schema field in either the public form or the preview.
	 *
	 * @param array  $field    Validated field definition.
	 * @param array  $settings Form settings.
	 * @param string $instance Current form instance ID.
	 * @param bool   $preview  Whether to display a non-submitting preview.
	 * @param bool   $invalid  Whether to demonstrate the field error state.
	 */
	private static function field( $field, $settings, $instance, $preview, $invalid ) {
		$id = $instance . '-' . $field['id'];
		$name = 'tscb_' . $field['id'];
		$type = $field['type'];
		$limit = TSCB_Fields::limit( $field, $settings );
		$hint = 'textarea' === $type ? str_replace( '{max}', (string) $limit, $settings['message_hint'] ) : '';
		$description = ( '' !== $hint ? $id . '-hint ' : '' ) . $id . '-error';
		$autocomplete = 'email' === $type ? 'email' : ( 'tel' === $type ? 'tel' : ( 'name' === $field['id'] ? 'name' : 'off' ) );
		?>
		<div class="tscb__field tscb__field--<?php echo 'half' === $field['width'] ? 'half' : 'full'; ?>">
			<?php if ( 'checkbox' === $type ) : ?>
				<label class="tscb__check-label" for="<?php echo esc_attr( $id ); ?>">
					<input id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" type="checkbox" value="1"<?php echo $field['required'] ? ' required' : ''; ?> aria-describedby="<?php echo esc_attr( $description ); ?>"<?php echo $invalid ? ' aria-invalid="true"' : ''; ?>>
					<span><?php echo esc_html( $field['label'] ); ?><?php if ( $field['required'] ) { self::required_marker(); } ?></span>
				</label>
			<?php else : ?>
				<div class="tscb__label-row">
					<label class="tscb__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php if ( $field['required'] ) { self::required_marker(); } ?></label>
					<?php if ( 'textarea' === $type && ! empty( $settings['show_counter'] ) ) : ?>
						<span class="tscb__count" data-tscb-counter="<?php echo esc_attr( $name ); ?>" aria-hidden="true"<?php echo $preview ? '' : ' hidden'; ?>>0 / <?php echo esc_html( (string) $limit ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( 'textarea' === $type ) : ?>
					<textarea class="tscb__input tscb__textarea" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" rows="5" maxlength="<?php echo esc_attr( (string) $limit ); ?>"<?php echo $field['required'] ? ' required' : ''; ?> aria-describedby="<?php echo esc_attr( $description ); ?>"<?php echo $invalid ? ' aria-invalid="true"' : ''; ?>></textarea>
				<?php elseif ( 'select' === $type ) : ?>
					<div class="tscb__select-wrap">
						<select class="tscb__input tscb__select" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"<?php echo $field['required'] ? ' required' : ''; ?> aria-describedby="<?php echo esc_attr( $description ); ?>"<?php echo $invalid ? ' aria-invalid="true"' : ''; ?>>
							<option value=""><?php echo esc_html( '' !== $field['placeholder'] ? $field['placeholder'] : __( 'Please choose an option.', 'contactbridge' ) ); ?></option>
							<?php foreach ( $field['options'] as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php else : ?>
					<input class="tscb__input" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" type="<?php echo esc_attr( $type ); ?>" autocomplete="<?php echo esc_attr( $autocomplete ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" maxlength="<?php echo esc_attr( (string) $limit ); ?>"<?php echo 'email' === $type ? ' inputmode="email" autocapitalize="none" spellcheck="false"' : ( 'tel' === $type ? ' inputmode="tel"' : '' ); ?><?php echo $field['required'] ? ' required' : ''; ?> aria-describedby="<?php echo esc_attr( $description ); ?>"<?php echo $invalid ? ' aria-invalid="true"' : ''; ?>>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( 'textarea' === $type ) : ?>
				<p class="tscb__hint" id="<?php echo esc_attr( $id ); ?>-hint"<?php echo '' === $hint ? ' hidden' : ''; ?>><?php echo esc_html( $hint ); ?></p>
			<?php endif; ?>
			<p class="tscb__field-error" id="<?php echo esc_attr( $id ); ?>-error" data-error-for="<?php echo esc_attr( $name ); ?>"<?php echo $invalid ? '' : ' hidden'; ?>><?php echo $invalid ? esc_html__( 'Please check this field.', 'contactbridge' ) : ''; ?></p>
		</div>
		<?php
	}

	/** Show a visible marker with an accessible explanation. */
	private static function required_marker() {
		?><span class="tscb__required" aria-hidden="true"> *</span><span class="tscb__sr-only"> <?php esc_html_e( '(required)', 'contactbridge' ); ?></span><?php
	}

	/**
	 * Print a consent sentence, keeping the privacy link outside clickable labels.
	 *
	 * @param array  $settings Sanitized settings.
	 * @param string $instance Current form instance ID.
	 * @param bool   $preview  Whether the link is non-interactive.
	 */
	private static function consent_text( $settings, $instance, $preview ) {
		$text = $settings['privacy_label'];
		if ( false === strpos( $text, '{privacy_link}' ) && ! empty( $settings['privacy_url'] ) ) {
			$text .= ' {privacy_link}';
		}
		$parts = explode( '{privacy_link}', $text );
		foreach ( $parts as $index => $part ) {
			if ( $index > 0 ) {
				if ( ! empty( $settings['privacy_url'] ) ) {
					self::privacy_link( $settings, $preview );
				} else {
					echo esc_html( $settings['privacy_link_text'] );
				}
			}
			if ( '' !== $part ) {
				?><label for="<?php echo esc_attr( $instance ); ?>-consent"><?php echo esc_html( $part ); ?></label><?php
			}
		}
	}

	/**
	 * Print only the permitted privacy link attributes.
	 *
	 * @param array $settings Sanitized settings.
	 * @param bool  $preview  Whether the link is non-interactive.
	 */
	private static function privacy_link( $settings, $preview ) {
		$new_tab = ! $preview && ! empty( $settings['privacy_new_tab'] );
		$label   = $settings['privacy_link_text'];
		if ( $new_tab ) {
			/* translators: %s: configured privacy link text. */
			$label = sprintf( __( '%s (opens in a new tab)', 'contactbridge' ), $label );
		}
		?><a class="tscb__privacy" href="<?php echo $preview ? '#' : esc_url( $settings['privacy_url'] ); ?>"<?php echo $new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?><?php echo $preview ? ' aria-disabled="true" tabindex="-1"' : ''; ?> aria-label="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $settings['privacy_link_text'] ); ?></a><?php
	}

	/**
	 * Build a small allowlisted set of CSS custom properties.
	 *
	 * @param array  $settings Design settings.
	 * @param string $accent  Sanitized accent color.
	 * @return string Safe inline style declaration.
	 */
	private static function style_variables( $settings, $accent ) {
		$variables = array(
			'--tscb-accent'    => $accent,
			'--tscb-on-accent' => self::accent_foreground( $accent ),
			'--tscb-width'     => max( 360, min( 1000, (int) $settings['width'] ) ) . 'px',
			'--tscb-font-size' => max( 14, min( 20, (int) $settings['font_size'] ) ) . 'px',
		);
		if ( ! empty( $settings['custom_colors'] ) ) {
			foreach ( array( 'surface' => 'surface', 'field' => 'field', 'text' => 'ink', 'muted' => 'muted', 'border' => 'line' ) as $key => $property ) {
				$color = sanitize_hex_color( $settings[ 'color_' . $key ] );
				if ( $color ) {
					$variables[ '--tscb-' . $property ] = $color;
					if ( 'surface' === $key ) {
						// Outlines sit on the card surface, including when custom colors invert the theme.
						$variables['--tscb-focus'] = self::accent_foreground( $color );
					}
					if ( 'border' === $key ) {
						$variables['--tscb-soft-line'] = $color;
					}
				}
			}
		}
		$declarations = array();
		foreach ( $variables as $property => $value ) {
			$declarations[] = $property . ':' . $value;
		}
		return implode( ';', $declarations ) . ';';
	}

	/**
	 * Choose the more readable black or white button text for a custom accent.
	 *
	 * @param string $hex Sanitized CSS hexadecimal color.
	 * @return string Foreground color.
	 */
	private static function accent_foreground( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$rgb = array();
		foreach ( array( 0, 2, 4 ) as $offset ) {
			$value = hexdec( substr( $hex, $offset, 2 ) ) / 255;
			$rgb[] = $value <= 0.04045 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
		}
		$luminance = 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
		return $luminance > 0.179 ? '#000000' : '#ffffff';
	}
}
