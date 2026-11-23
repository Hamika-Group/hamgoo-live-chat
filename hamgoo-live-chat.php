<?php
/**
 * Plugin Name: Hamgoo Live Chat
 * Plugin URI: https://github.com/Hamika-Group/hamgoo-live-chat
 * Description: Connect Hamgoo live chat with a widget key, choose where it appears, and control when it loads.
 * Version: 1.0.4
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Hamkaran
 * Author URI: https://hamkaran.cloud/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hamgoo-live-chat
 */

namespace Hamgoo\LiveChat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HAMGOO_VERSION', '1.0.4' );
define( 'HAMGOO_FILE', __FILE__ );
define( 'HAMGOO_DIR', plugin_dir_path( __FILE__ ) );
define( 'HAMGOO_URL', plugin_dir_url( __FILE__ ) );

require_once HAMGOO_DIR . 'includes/class-settings.php';
require_once HAMGOO_DIR . 'includes/class-rules.php';
require_once HAMGOO_DIR . 'includes/class-frontend.php';
require_once HAMGOO_DIR . 'includes/class-admin.php';

add_action( 'admin_init', array( Settings::class, 'privacy_content' ) );
add_action( 'wp_enqueue_scripts', array( Frontend::class, 'enqueue' ) );
Admin::register();
