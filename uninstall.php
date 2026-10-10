<?php
/** Remove only this site's plugin setting when the administrator opted in. */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$hamgoo_settings = get_option( 'hamgoo_live_chat_settings', array() );
if ( is_array( $hamgoo_settings ) && ! empty( $hamgoo_settings['delete_on_uninstall'] ) ) {
	delete_option( 'hamgoo_live_chat_settings' );
}
