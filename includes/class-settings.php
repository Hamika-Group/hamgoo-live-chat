<?php
/** Settings and validation. */

namespace Hamgoo\LiveChat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	const OPTION  = 'hamgoo_live_chat_settings';
	const HOME    = 'https://hamkaran.cloud/';
	const PANEL   = 'https://cp.hamkaran.cloud/';
	const TERMS   = 'https://hamkaran.cloud/%D8%AA%D9%88%D8%A7%D9%81%D9%82-%D9%86%D8%A7%D9%85%D9%87-%D8%A7%D9%85%D9%86%DB%8C%D8%AA%DB%8C/';
	const PRIVACY = 'https://hamkaran.cloud/privacy-policy/';

	public static function defaults() {
		return array(
			'enabled'             => 0,
			'widget_key'          => '',
			'service_consent'     => 0,
			'mode'                => 'all',
			'include_ids'         => array(),
			'exclude_ids'         => array(),
			'post_types'          => array(),
			'contexts'            => array(),
			'exclude_contexts'    => array(),
			'include_paths'       => '',
			'exclude_paths'       => '',
			'devices'             => 'all',
			'load_mode'           => 'idle',
			'delay'               => 3,
			'require_consent'     => 0,
			'admin_language'      => 'auto',
			'delete_on_uninstall' => 0,
		);
	}

	public static function get() {
		$value = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $value ) ? $value : array() );
	}

	private static function scalar_list( $value ) {
		return is_array( $value ) ? array_filter( $value, 'is_scalar' ) : array();
	}

	public static function valid_key( $key ) {
		return is_string( $key ) && 1 === preg_match( '/\Acw_[A-Za-z0-9]{32}\z/', $key );
	}

	public static function sanitize( $input ) {
		$input  = is_array( $input ) ? $input : array();
		$result = self::defaults();
		foreach ( array( 'enabled', 'service_consent', 'require_consent', 'delete_on_uninstall' ) as $field ) {
			$result[ $field ] = empty( $input[ $field ] ) ? 0 : 1;
		}
		$key = isset( $input['widget_key'] ) && is_string( $input['widget_key'] ) ? trim( $input['widget_key'] ) : '';
		if ( '' !== $key && ! self::valid_key( $key ) ) {
			add_settings_error( self::OPTION, 'invalid_key', __( 'Enter only the widget key: cw_ followed by 32 letters or numbers. Do not paste the installation scripts.', 'hamgoo-live-chat' ) );
			$key = self::get()['widget_key'];
		}
		$result['widget_key'] = $key;
		foreach ( array(
			'mode'           => array( 'all', 'selected' ),
			'devices'        => array( 'all', 'mobile', 'desktop' ),
			'load_mode'      => array( 'idle', 'delay', 'click' ),
			'admin_language' => array( 'auto', 'en_US', 'fa_IR', 'ar', 'ru_RU', 'ur' ),
		) as $field => $choices ) {
			$result[ $field ] = isset( $input[ $field ] ) && in_array( $input[ $field ], $choices, true ) ? $input[ $field ] : $result[ $field ];
		}
		$result['delay'] = isset( $input['delay'] ) && is_scalar( $input['delay'] ) ? min( 120, max( 0, absint( $input['delay'] ) ) ) : 3;
		foreach ( array( 'include_ids', 'exclude_ids' ) as $field ) {
			$result[ $field ] = array_slice( array_values( array_unique( array_filter( array_map( 'absint', self::scalar_list( $input[ $field ] ?? array() ) ) ) ) ), 0, 200 );
		}
		$result['post_types'] = array_values( array_intersect( self::scalar_list( $input['post_types'] ?? array() ), array_keys( get_post_types( array( 'public' => true ) ) ) ) );
		foreach ( array( 'contexts', 'exclude_contexts' ) as $field ) {
			$result[ $field ] = array_values( array_intersect( self::scalar_list( $input[ $field ] ?? array() ), array_keys( Rules::context_labels() ) ) );
		}
		foreach ( array( 'include_paths', 'exclude_paths' ) as $field ) {
			$result[ $field ] = self::sanitize_paths( $input[ $field ] ?? '' );
		}
		if ( $result['enabled'] && ( ! self::valid_key( $key ) || ! $result['service_consent'] ) ) {
			$result['enabled'] = 0;
			add_settings_error( self::OPTION, 'needs_connection', __( 'Chat stays disabled until a valid widget key is saved and the external service connection is accepted.', 'hamgoo-live-chat' ) );
		}
		return $result;
	}

	public static function sanitize_paths( $value ) {
		$paths = array();
		foreach ( array_slice( preg_split( '/\R/u', is_string( $value ) ? $value : '' ), 0, 100 ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( '/' !== substr( $line, 0, 1 ) || false !== strpos( $line, '//' ) || preg_match( '/[\s?#<>\\\\]/u', $line ) || strlen( $line ) > 500 ) {
				add_settings_error( self::OPTION, 'invalid_path', __( 'Paths must start with / and contain no domain, query string, spaces or fragment. Use * as a wildcard.', 'hamgoo-live-chat' ) );
				continue;
			}
			$paths[] = $line;
		}
		return implode( "\n", array_unique( $paths ) );
	}

	public static function privacy_content() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( 'Hamgoo Live Chat', wpautop( __( 'When Hamgoo chat loads, the visitor browser connects to the Hamkaran cloud service. The service receives network information, including the IP address. When a visitor starts or resumes a chat, page URL, page title, referrer, browser, operating system and session information can be sent. Names, phone numbers, email addresses, messages and attachments supplied by the visitor are processed by the service. The widget uses localStorage and sessionStorage to maintain the chat session. Microphone access is requested only when recording a voice message. Chat records are stored by Hamkaran, not in this plugin\'s WordPress database. Review the service privacy policy and adapt this text to your site\'s actual configuration and consent requirements.', 'hamgoo-live-chat' ) . ' <a href="' . esc_url( self::PRIVACY ) . '">' . esc_html__( 'Service privacy policy', 'hamgoo-live-chat' ) . '</a>' ) );
		}
	}
}
