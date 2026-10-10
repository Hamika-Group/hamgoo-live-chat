<?php
/**
 * Plugin Name: Hamgoo Live Chat
 * Plugin URI: https://hamkaran.cloud/
 * Description: Connect Hamgoo live chat with a widget key, choose where it appears, and control when it loads.
 * Version: 1.0.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Hamkaran
 * Author URI: https://hamkaran.cloud/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hamgoo-live-chat
 * Domain Path: /languages
 */

namespace Hamgoo\LiveChat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HAMGOO_VERSION', '1.0.2' );
define( 'HAMGOO_FILE', __FILE__ );
define( 'HAMGOO_DIR', plugin_dir_path( __FILE__ ) );
define( 'HAMGOO_URL', plugin_dir_url( __FILE__ ) );

require_once HAMGOO_DIR . 'includes/class-settings.php';
require_once HAMGOO_DIR . 'includes/class-rules.php';
require_once HAMGOO_DIR . 'includes/class-frontend.php';
require_once HAMGOO_DIR . 'includes/class-admin.php';

/** Load only this plugin's translations without changing the site's language. */
function translations() {
	$locale   = determine_locale();
	$settings = Settings::get();
	if ( is_admin() && 'auto' !== $settings['admin_language'] ) {
		$locale = $settings['admin_language'];
	}
	$locales = array(
		'fa'    => 'fa_IR',
		'ur'    => 'ur',
		'ur_PK' => 'ur',
		'ru'    => 'ru_RU',
		'ar_SA' => 'ar',
	);
	$locale  = isset( $locales[ $locale ] ) ? $locales[ $locale ] : $locale;
	if ( 0 === strpos( $locale, 'ar_' ) ) {
		$locale = 'ar';
	}
	// Use bundled catalogs, including an explicit plugin-only language override.
	load_textdomain( 'hamgoo-live-chat', HAMGOO_DIR . 'languages/hamgoo-live-chat-' . $locale . '.mo', $locale );
}

add_action( 'init', __NAMESPACE__ . '\\translations', 0 );
add_action( 'admin_init', array( Settings::class, 'privacy_content' ) );
add_action( 'wp_enqueue_scripts', array( Frontend::class, 'enqueue' ) );
Admin::register();
