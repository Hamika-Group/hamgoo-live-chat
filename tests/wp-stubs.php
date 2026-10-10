<?php
/** Small WordPress boundary doubles for fast, dependency-free unit checks. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'HAMGOO_VERSION', '1.0.0' );
define( 'HAMGOO_DIR', dirname( __DIR__ ) . '/' );
define( 'HAMGOO_FILE', HAMGOO_DIR . 'hamgoo-live-chat.php' );
define( 'HAMGOO_URL', '../' );
$GLOBALS['hg_options'] = array();
$GLOBALS['hg_errors'] = array();
$GLOBALS['hg_scripts'] = array();
$GLOBALS['hg_styles'] = array();
$GLOBALS['hg_inline'] = array();
$GLOBALS['hg_flags'] = array();
$GLOBALS['hg_posts'] = array( 12 => 'Contact', 14 => 'Services' );
$GLOBALS['hg_id'] = 12;
$GLOBALS['hg_type'] = 'page';
$GLOBALS['hg_home'] = 'https://example.test/';
$GLOBALS['hg_catalog'] = array();
$GLOBALS['hg_capability'] = true;

function __( $text, $domain = '' ) { return $GLOBALS['hg_catalog'][$text] ?? $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_textarea( $text ) { return esc_html( $text ); }
function esc_html__( $text, $domain = '' ) { return esc_html( __( $text, $domain ) ); }
function esc_attr__( $text, $domain = '' ) { return esc_attr( __( $text, $domain ) ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html__( $text, $domain ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr__( $text, $domain ); }
function esc_url( $url ) { return esc_attr( $url ); }
function absint( $value ) { return abs( (int) $value ); }
function get_option( $name, $default = false ) { return $GLOBALS['hg_options'][$name] ?? $default; }
function delete_option( $name ) { unset( $GLOBALS['hg_options'][$name] ); }
function get_post_types( $args = array(), $output = 'names' ) {
	$types = array( 'post' => 'Posts', 'page' => 'Pages', 'product' => 'Products' );
	if ( 'names' === $output ) { return array_combine( array_keys( $types ), array_keys( $types ) ); }
	$result = array();
	foreach ( $types as $key => $name ) { $result[$key] = (object) array( 'name' => $key, 'labels' => (object) array( 'name' => $name ) ); }
	return $result;
}
function add_settings_error( $setting, $code, $message ) { $GLOBALS['hg_errors'][] = $code; }
function sanitize_text_field( $text ) { return is_scalar( $text ) ? strip_tags( (string) $text ) : ''; }
function wp_unslash( $text ) { return stripslashes( $text ); }
function wp_parse_url( $text, $component = -1 ) { return parse_url( $text, $component ); }
function home_url( $path = '' ) { return rtrim( $GLOBALS['hg_home'], '/' ) . $path; }
function admin_url( $path ) { return '../' . $path; }
function determine_locale() { return $GLOBALS['hg_locale'] ?? 'en_US'; }
function is_admin() { return ! empty( $GLOBALS['hg_flags']['admin'] ); }
function is_singular() { return ! empty( $GLOBALS['hg_flags']['singular'] ); }
function is_front_page() { return ! empty( $GLOBALS['hg_flags']['front'] ); }
function is_home() { return ! empty( $GLOBALS['hg_flags']['home'] ); }
function is_archive() { return ! empty( $GLOBALS['hg_flags']['archive'] ); }
function is_search() { return ! empty( $GLOBALS['hg_flags']['search'] ); }
function is_404() { return ! empty( $GLOBALS['hg_flags']['404'] ); }
function is_feed() { return ! empty( $GLOBALS['hg_flags']['feed'] ); }
function is_embed() { return ! empty( $GLOBALS['hg_flags']['embed'] ); }
function is_preview() { return ! empty( $GLOBALS['hg_flags']['preview'] ); }
function wp_doing_ajax() { return ! empty( $GLOBALS['hg_flags']['ajax'] ); }
function wp_doing_cron() { return ! empty( $GLOBALS['hg_flags']['cron'] ); }
function is_shop() { return ! empty( $GLOBALS['hg_flags']['shop'] ); }
function is_product() { return ! empty( $GLOBALS['hg_flags']['product'] ); }
function is_product_taxonomy() { return ! empty( $GLOBALS['hg_flags']['product_archive'] ); }
function is_cart() { return ! empty( $GLOBALS['hg_flags']['cart'] ); }
function is_checkout() { return ! empty( $GLOBALS['hg_flags']['checkout'] ); }
function is_account_page() { return ! empty( $GLOBALS['hg_flags']['account'] ); }
function get_queried_object_id() { return $GLOBALS['hg_id']; }
function get_post_type( $id = 0 ) { return $GLOBALS['hg_type']; }
function get_the_title( $id ) { return $GLOBALS['hg_posts'][$id] ?? ''; }
function get_bloginfo( $name ) { return 'UTF-8'; }
function wp_json_encode( $value ) { return json_encode( $value, JSON_HEX_TAG | JSON_HEX_AMP ); }
function wp_enqueue_script( $handle, $url, $dependencies = array(), $version = '', $args = null ) { $GLOBALS['hg_scripts'][$handle] = compact( 'url', 'args' ); }
function wp_enqueue_style( $handle, $url, $dependencies = array(), $version = '' ) { $GLOBALS['hg_styles'][$handle] = $url; }
function wp_add_inline_script( $handle, $script, $position ) { $GLOBALS['hg_inline'][$handle] = $script; }
function wp_localize_script( $handle, $name, $value ) { $GLOBALS['hg_localized'][$name] = $value; }
function wp_create_nonce( $action ) { return 'preview-nonce'; }
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function plugin_basename( $file ) { return basename( $file ); }
function current_user_can( $cap ) { return $GLOBALS['hg_capability']; }
function checked( $value, $expected = true, $echo = true ) {
	$result = (string) $value === (string) $expected ? 'checked="checked"' : '';
	if ( $echo ) echo $result;
	return $result;
}
function selected( $value, $expected, $echo = true ) {
	$result = (string) $value === (string) $expected ? 'selected="selected"' : '';
	if ( $echo ) echo $result;
	return $result;
}
function disabled( $value, $expected = true, $echo = true ) {
	$result = (string) $value === (string) $expected ? 'disabled="disabled"' : '';
	if ( $echo ) echo $result;
	return $result;
}
function settings_fields( $group ) { echo '<input type="hidden" name="option_page" value="' . esc_attr( $group ) . '">'; }
function settings_errors( $group ) {}

require HAMGOO_DIR . 'includes/class-settings.php';
require HAMGOO_DIR . 'includes/class-rules.php';
require HAMGOO_DIR . 'includes/class-frontend.php';
require HAMGOO_DIR . 'includes/class-admin.php';
