<?php
/** Enqueue a local loader only on eligible pages. */

namespace Hamgoo\LiveChat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend {
	public static function enqueue() {
		$settings = Settings::get();
		if ( ! Rules::allows( $settings, Rules::current_context() ) ) {
			return;
		}
		wp_enqueue_script(
			'hamgoo-loader',
			HAMGOO_URL . 'assets/js/loader.js',
			array(),
			HAMGOO_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		$config = array(
			'widgetKey'      => $settings['widget_key'],
			'scriptUrl'      => 'https://cp.hamkaran.cloud/widget/chat.js',
			'mode'           => $settings['load_mode'],
			'delay'          => $settings['delay'],
			'devices'        => $settings['devices'],
			'requireConsent' => (bool) $settings['require_consent'],
			'label'          => __( 'Chat with us', 'hamgoo-live-chat' ),
			'loadingLabel'   => __( 'Connecting to chat…', 'hamgoo-live-chat' ),
			'errorLabel'     => __( 'Chat could not load. Try again.', 'hamgoo-live-chat' ),
		);
		wp_add_inline_script( 'hamgoo-loader', 'window.HamgooLiveChatLoader=' . wp_json_encode( $config ) . ';', 'before' );
		if ( 'click' === $settings['load_mode'] ) {
			wp_enqueue_style( 'hamgoo-launcher', HAMGOO_URL . 'assets/css/launcher.css', array(), HAMGOO_VERSION );
		}
	}
}
