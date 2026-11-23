<?php
/**
 * Targeted integration checks in an isolated WordPress installation.
 *
 * Run with: wp eval-file tests/wordpress-smoke.php
 * Requires Hamgoo Live Chat active; never run on a customer's/live site.
 */

use Hamgoo\LiveChat\Admin;
use Hamgoo\LiveChat\Frontend;
use Hamgoo\LiveChat\Rules;
use Hamgoo\LiveChat\Settings;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'Use WP-CLI in an isolated development installation.' );
}
if ( ! class_exists( Settings::class ) ) {
	WP_CLI::error( 'Activate Hamgoo Live Chat first.' );
}
if ( ! WP_DEBUG ) {
	WP_CLI::error( 'Enable WP_DEBUG in the disposable WordPress installation.' );
}

$GLOBALS['hamgoo_smoke_checks'] = 0;
function hamgoo_assert( $value, $message ) {
	++$GLOBALS['hamgoo_smoke_checks'];
	if ( ! $value ) {
		WP_CLI::error( $message );
	}
}

require_once ABSPATH . 'wp-admin/includes/admin.php';
wp_set_current_user( 1 );
set_current_screen( 'toplevel_page_hamgoo-live-chat' );
$catalog = json_decode( file_get_contents( __DIR__ . '/../languages/translations.json' ), true );
$domain_hook = 'gettext_hamgoo-live-chat';
$pack_directory = WP_LANG_DIR . '/plugins';
$locales = array( 'fa_IR', 'ar', 'ru_RU', 'ur' );
$source = 'Connect & start';
$wordpress_locale = WP_Translation_Controller::get_instance()->get_locale();

// Start without any plugin language packs, as on a fresh installation.
$options = Settings::defaults();
$options['admin_language'] = 'fa_IR';
update_option( Settings::OPTION, $options );
Admin::load_language();
hamgoo_assert( __( $source, 'hamgoo-live-chat' ) === $source, 'Missing language pack falls back to English.' );
remove_all_filters( $domain_hook );

wp_mkdir_p( $pack_directory );
foreach ( $locales as $locale ) {
	$name = 'hamgoo-live-chat-' . $locale . '.mo';
	$destination = $pack_directory . '/' . $name;
	hamgoo_assert( ! file_exists( $destination ) && copy( __DIR__ . '/../languages/' . $name, $destination ), 'Stage language pack: ' . $locale );
}

foreach ( array_merge( array( 'en_US' ), $locales ) as $locale ) {
	$options                   = Settings::defaults();
	$options['admin_language'] = $locale;
	update_option( Settings::OPTION, $options );
	Admin::load_language();
	$expected = 'en_US' === $locale ? $source : $catalog[ $source ][ $locale ];
	hamgoo_assert( __( $source, 'hamgoo-live-chat' ) === $expected, 'Real WordPress translation: ' . $locale );
	hamgoo_assert( WP_Translation_Controller::get_instance()->get_locale() === $wordpress_locale, 'The admin choice preserves the WordPress locale.' );
	ob_start();
	Admin::render();
	$html = ob_get_clean();
	hamgoo_assert( false !== strpos( $html, 'id="hg-settings-form"' ), 'Real admin render: ' . $locale );
	hamgoo_assert( false !== strpos( $html, esc_html( $expected ) ), 'Translated admin render: ' . $locale );
	hamgoo_assert( false !== strpos( $html, 'wpnonce' ), 'Settings API nonce is present.' );
	remove_all_filters( $domain_hook );
}

Admin::settings();
do_action( 'admin_init' );
$policy = WP_Privacy_Policy_Content::get_suggested_policy_text();
hamgoo_assert( in_array( 'Hamgoo Live Chat', array_column( $policy, 'plugin_name' ), true ), 'Privacy text is registered on admin_init.' );
set_current_screen( 'front' );
$GLOBALS['current_screen'] = null;
$page = wp_insert_post( array( 'post_title' => 'Hamgoo Smoke Contact', 'post_type' => 'page', 'post_status' => 'publish' ) );
$post = wp_insert_post( array( 'post_title' => 'Hamgoo Smoke Post', 'post_type' => 'post', 'post_status' => 'publish' ) );
$s = array_merge( Settings::defaults(), array( 'widget_key' => 'cw_' . str_repeat( 'A', 32 ), 'enabled' => 1, 'service_consent' => 1 ) );

$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $page ) );
$_SERVER['REQUEST_URI'] = '/hamgoo-smoke-contact/?utm_source=test';
update_option( Settings::OPTION, $s );
Frontend::enqueue();
hamgoo_assert( wp_script_is( 'hamgoo-loader', 'enqueued' ), 'Real enqueue on allowed page.' );
$scripts = wp_scripts();
hamgoo_assert( false !== strpos( $scripts->registered['hamgoo-loader']->src, '/assets/js/loader.js' ), 'Only the local loader is enqueued.' );
hamgoo_assert( false !== strpos( implode( '', $scripts->get_data( 'hamgoo-loader', 'before' ) ), '"widgetKey"' ), 'Bootstrap precedes local loader.' );
wp_dequeue_script( 'hamgoo-loader' );
$s['exclude_ids'] = array( $page );
update_option( Settings::OPTION, $s );
Frontend::enqueue();
hamgoo_assert( ! wp_script_is( 'hamgoo-loader', 'enqueued' ), 'Excluded page has no loader.' );

$s['exclude_ids'] = array();
$s['mode'] = 'selected';
$s['post_types'] = array( 'post' );
update_option( Settings::OPTION, $s );
hamgoo_assert( ! Rules::allows( $s, Rules::current_context() ), 'Page rejected by post-only scope.' );
$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $post ) );
hamgoo_assert( Rules::allows( $s, Rules::current_context() ), 'Published post accepted by scope.' );
wp_delete_post( $page, true );
wp_delete_post( $post, true );
delete_option( Settings::OPTION );
foreach ( $locales as $locale ) {
	unlink( $pack_directory . '/hamgoo-live-chat-' . $locale . '.mo' );
}
WP_CLI::success( $GLOBALS['hamgoo_smoke_checks'] . ' real WordPress assertions.' );
