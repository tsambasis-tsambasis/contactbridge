<?php
/** Bounded field schemas and server-side validation. @package Kontelio */
defined( 'ABSPATH' ) || exit;

final class TSCB_Fields {
	const MAX_FIELDS = 20;

	public static function type_labels() {
		return array( 'text' => __( 'Text', 'kontelio' ), 'email' => __( 'Email', 'kontelio' ), 'tel' => __( 'Phone', 'kontelio' ), 'textarea' => __( 'Message', 'kontelio' ), 'select' => __( 'Selection', 'kontelio' ), 'checkbox' => __( 'Checkbox', 'kontelio' ) );
	}

	private static function text( $value, $limit, $multiline = false ) {
		$value = $multiline ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
		preg_match( '/^.{0,' . absint( $limit ) . '}/us', $value, $match );
		return isset( $match[0] ) ? $match[0] : '';
	}

	/** Start a new form with four standard fields until a custom schema is saved. */
	public static function raw( $settings ) {
		if ( isset( $settings['fields'] ) && is_array( $settings['fields'] ) ) {
			return $settings['fields'];
		}
		$fields = array();
		foreach ( array( 'name' => 'text', 'email' => 'email', 'subject' => 'text', 'message' => 'textarea' ) as $id => $type ) {
			$fields[] = array( 'id' => $id, 'type' => $type, 'label' => '', 'placeholder' => isset( $settings[ 'placeholder_' . $id ] ) ? $settings[ 'placeholder_' . $id ] : '', 'required' => true, 'width' => in_array( $id, array( 'name', 'email' ), true ) ? 'half' : 'full', 'options' => array() );
		}
		return $fields;
	}

	/** Normalize saved configuration; invalid schemas fail closed. */
	public static function get( $settings ) {
		$fields = self::sanitize( self::raw( $settings ), true );
		if ( is_wp_error( $fields ) ) { return array(); }
		$labels = self::type_labels();
		foreach ( $fields as &$field ) {
			if ( '' === $field['label'] ) {
				$key = 'label_' . $field['id'];
				$field['label'] = isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) && '' !== $settings[ $key ] ? self::text( $settings[ $key ], 100 ) : $labels[ $field['type'] ];
			}
		}
		unset( $field );
		return $fields;
	}

	public static function decode( $json, $allow_empty = false ) {
		if ( ! is_string( $json ) || strlen( $json ) > 65536 ) { return self::schema_error(); }
		$fields = json_decode( $json, true, 8 );
		return JSON_ERROR_NONE === json_last_error() ? self::sanitize( $fields, $allow_empty ) : self::schema_error();
	}

	private static function schema_error() {
		return new WP_Error( 'tscb_fields', __( 'Please check the form fields. Use 1 to 20 fields with unique IDs and supported types; selections need 1 to 20 choices.', 'kontelio' ) );
	}

	/** Browser-supplied schema is accepted only at the administrator settings boundary. */
	public static function sanitize( $fields, $allow_empty = false ) {
		if ( ! is_array( $fields ) || count( $fields ) > self::MAX_FIELDS || ( ! $allow_empty && ! $fields ) ) { return self::schema_error(); }
		$clean = array(); $seen = array(); $types = self::type_labels();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['id'], $field['type'] ) || ! is_string( $field['id'] ) || ! preg_match( '/^(?:name|email|subject|message|f_[a-z0-9]{8,24})$/D', $field['id'] ) || isset( $seen[ $field['id'] ] ) || ! is_string( $field['type'] ) || ! isset( $types[ $field['type'] ] ) ) { return self::schema_error(); }
			$seen[ $field['id'] ] = true;
			foreach ( array( 'label', 'placeholder' ) as $key ) {
				if ( isset( $field[ $key ] ) && ! is_string( $field[ $key ] ) ) { return self::schema_error(); }
			}
			$options = 'select' === $field['type'] && isset( $field['options'] ) ? $field['options'] : array();
			if ( ! is_array( $options ) || count( $options ) > 20 ) { return self::schema_error(); }
			$choices = array();
			foreach ( $options as $option ) {
				if ( ! is_string( $option ) ) { return self::schema_error(); }
				$option = trim( self::text( $option, 100 ) );
				if ( '' !== $option && ! in_array( $option, $choices, true ) ) { $choices[] = $option; }
			}
			if ( 'select' === $field['type'] && ! $choices ) { return self::schema_error(); }
			$clean[] = array(
				'id' => $field['id'], 'type' => $field['type'],
				'label' => self::text( isset( $field['label'] ) ? $field['label'] : '', 100 ),
				'placeholder' => self::text( isset( $field['placeholder'] ) ? $field['placeholder'] : '', 150 ),
				'required' => isset( $field['required'] ) && in_array( $field['required'], array( true, 1, '1' ), true ),
				'width' => isset( $field['width'] ) && 'half' === $field['width'] ? 'half' : 'full',
				'options' => 'select' === $field['type'] ? $choices : array(),
			);
		}
		return $clean;
	}

	public static function limit( $field, $settings ) {
		if ( 'email' === $field['type'] ) { return 254; }
		if ( 'tel' === $field['type'] ) { return 50; }
		if ( 'select' === $field['type'] ) { return 100; }
		if ( 'checkbox' === $field['type'] ) { return 1; }
		if ( 'name' === $field['id'] ) { return 100; }
		if ( 'subject' === $field['id'] ) { return 150; }
		return 'textarea' === $field['type'] ? ( self::full_content( 'whatsapp', $settings ) ? 500 : 2000 ) : 250;
	}

	/** Visitor content budgets apply only to channels that actually transmit the answers. */
	public static function full_content( $channel, $settings ) {
		return in_array( $channel, isset( $settings['channels'] ) ? (array) $settings['channels'] : array(), true ) && empty( $settings[ 'privacy_' . $channel ] );
	}

	/** Hash only validation rules, so translated labels do not invalidate a cached form. */
	public static function signature( $settings ) {
		$rules = array();
		foreach ( self::get( $settings ) as $field ) {
			$rules[] = array( $field['id'], $field['type'], $field['required'], $field['options'], self::limit( $field, $settings ) );
		}
		return hash( 'sha256', wp_json_encode( $rules ) );
	}

	public static function delivery_hint( $settings ) {
		if ( self::full_content( 'whatsapp', $settings ) ) { return __( 'WhatsApp: all field labels and answers together must fit within 500 characters. Longer entries are rejected without being shortened.', 'kontelio' ); }
		if ( self::full_content( 'telegram', $settings ) ) { return __( 'Telegram: the complete notification must fit within 4,000 text units; emoji may count twice. Longer entries are rejected without being shortened.', 'kontelio' ); }
		return '';
	}

	/** Ordered plain-text answers for every configured field, never visitor-supplied labels. */
	public static function details( $fields ) {
		$lines = array();
		foreach ( $fields as $field ) { $lines[] = $field['label'] . ': ' . ( '' === $field['value'] ? '—' : $field['value'] ); }
		return implode( "\n", $lines );
	}

	public static function site_name() { return self::text( wp_strip_all_tags( get_bloginfo( 'name' ) ), 100 ); }

	public static function notification( $data ) {
		return __( 'Contact form: ', 'kontelio' ) . self::site_name() . "\n" . self::details( $data['fields'] );
	}

	/** One representation for both size validation and the five template parameters. */
	public static function whatsapp_parameters( $data ) {
		$values = array( $data['name'], $data['email'], $data['subject'], $data['message'], self::site_name() );
		return array_map( static function ( $value ) { return '' === trim( $value ) ? '—' : preg_replace( '/\s+/u', ' ', $value ); }, $values );
	}

	private static function error( $message, $fields = array(), $code = 'invalid' ) { return new WP_Error( $code, $message, array( 'fields' => $fields ) ); }

	/** Consume only fields in the saved schema. No visitor can define fields or recipients. */
	public static function validate( $input, $settings ) {
		$schema = self::get( $settings );
		if ( ! $schema ) { return self::error( __( 'The contact form is currently unavailable. Please use another way to get in touch.', 'kontelio' ), array(), 'unconfigured' ); }
		if ( isset( $input['tscb_schema'] ) && ( ! is_string( $input['tscb_schema'] ) || ! hash_equals( self::signature( $settings ), $input['tscb_schema'] ) ) ) { return self::error( __( 'This form has changed. Reload the page before sending your message.', 'kontelio' ), array(), 'expired' ); }
		$data = array( 'name' => '', 'email' => '', 'subject' => '', 'message' => '', 'fields' => array() );
		$errors = array(); $has_value = false;
		foreach ( $schema as $field ) {
			$key = 'tscb_' . $field['id'];
			$raw = isset( $input[ $key ] ) ? $input[ $key ] : '';
			$limit = self::limit( $field, $settings );
			if ( ! is_string( $raw ) || strlen( $raw ) > $limit * 4 || wp_check_invalid_utf8( $raw ) !== $raw ) {
				$errors[ $key ] = __( 'The entry is too long or contains invalid characters.', 'kontelio' ); continue;
			}
			$value = trim( 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw ) );
			if ( 'checkbox' === $field['type'] ) {
				if ( ! in_array( $raw, array( '', '1' ), true ) || ( $field['required'] && '1' !== $raw ) ) { $errors[ $key ] = __( 'Please confirm this checkbox.', 'kontelio' ); }
				$has_value = $has_value || '1' === $raw;
				$value = '1' === $raw ? __( 'Yes', 'kontelio' ) : __( 'No', 'kontelio' );
			} else {
				if ( ( $field['required'] && '' === $value ) || TSCB_Submission::length( $value ) > $limit ) {
					/* translators: %d: maximum permitted character count. */
					$errors[ $key ] = sprintf( __( 'Please complete this field using no more than %d characters.', 'kontelio' ), $limit );
				}
				if ( '' !== $value ) {
					$has_value = true;
					if ( 'email' === $field['type'] && ( ! is_email( trim( $raw ) ) || preg_match( '/[\r\n]/', $raw ) ) ) { $errors[ $key ] = __( 'Please enter a valid email address.', 'kontelio' ); }
					if ( 'tel' === $field['type'] && ( ! preg_match( '/^[0-9+(). \/#*x-]{3,50}$/iD', trim( $raw ) ) || strlen( preg_replace( '/[^0-9]/', '', $raw ) ) < 3 ) ) { $errors[ $key ] = __( 'Please enter a valid phone number.', 'kontelio' ); }
				}
				if ( 'select' === $field['type'] && '' !== $raw && ! in_array( $raw, $field['options'], true ) ) { $errors[ $key ] = __( 'Please choose one of the available options.', 'kontelio' ); }
			}
			$data['fields'][] = array( 'id' => $field['id'], 'type' => $field['type'], 'label' => $field['label'], 'value' => $value );
			if ( in_array( $field['id'], array( 'name', 'subject' ), true ) ) { $data[ $field['id'] ] = sanitize_text_field( $value ); }
			if ( 'email' === $field['type'] && '' === $data['email'] && '' !== $value && ! isset( $errors[ $key ] ) ) { $data['email'] = sanitize_email( $value ); }
		}
		if ( $errors ) { return self::error( __( 'Please check the highlighted details.', 'kontelio' ), $errors ); }
		if ( ! $has_value ) { return self::error( __( 'Please complete at least one form field.', 'kontelio' ) ); }
		if ( '' === $data['subject'] ) { $data['subject'] = __( 'Contact inquiry', 'kontelio' ); }
		$data['message'] = self::details( $data['fields'] );
		if ( TSCB_Submission::length( $data['message'] ) > 12000 ) { return self::error( __( 'Your answers are too long in total. Please shorten them and try again.', 'kontelio' ) ); }
		if ( self::full_content( 'whatsapp', $settings ) && ( TSCB_Submission::length( $data['message'] ) > 500 || TSCB_Submission::length( implode( '', self::whatsapp_parameters( $data ) ) ) > 900 ) ) { return self::error( __( 'Your answers are too long for WhatsApp. All labels and answers together may contain at most 500 characters; please shorten your entries.', 'kontelio' ) ); }
		$body = self::notification( $data );
		$units = TSCB_Submission::length( $body ) + preg_match_all( '/[\x{10000}-\x{10FFFF}]/u', $body, $unused );
		if ( self::full_content( 'telegram', $settings ) && $units > 4000 ) { return self::error( __( 'Your answers are too long for Telegram. Please shorten them; emoji may count as two text units.', 'kontelio' ) ); }
		return $data;
	}
}
