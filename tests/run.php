<?php
require __DIR__ . '/wp-stubs.php';
use Hamgoo\LiveChat\Settings;
use Hamgoo\LiveChat\Rules;
use Hamgoo\LiveChat\Frontend;
use Hamgoo\LiveChat\Admin;

$count = 0;
function expect( $condition, $message ) {
	global $count;
	$count++;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
$key = 'cw_' . str_repeat( 'A', 32 );
$s = array_merge( Settings::defaults(), array( 'enabled' => 1, 'service_consent' => 1, 'widget_key' => $key ) );
$c = array( 'private' => false, 'id' => 12, 'contexts' => array(), 'post_type' => 'page', 'path' => '/contact/' );
expect( ! Rules::allows( Settings::defaults(), $c ), 'Fresh installation must not load.' );
expect( Settings::valid_key( $key ), 'Current key contract.' );
foreach ( array( '', 'cw_' . str_repeat( 'A', 31 ), $key . '<script>', 'prefix' . $key, $key . "\n", 'cw_' . str_repeat( '_', 32 ) ) as $bad ) {
	expect( ! Settings::valid_key( $bad ), 'Reject malformed key.' );
}
expect( Rules::allows( $s, $c ), 'All public pages.' );
foreach ( array( 'enabled', 'service_consent' ) as $flag ) {
	$copy = $s; $copy[$flag] = 0;
	expect( ! Rules::allows( $copy, $c ), 'Require enable and service acceptance.' );
}
$copy = $c; $copy['private'] = true;
expect( ! Rules::allows( $s, $copy ), 'Private contexts never load.' );
$selected = $s; $selected['mode'] = 'selected';
expect( ! Rules::allows( $selected, $c ), 'Empty selected scope shows nowhere.' );
foreach ( array( 'include_ids' => array(12), 'post_types' => array('page'), 'include_paths' => '/contact' ) as $field => $value ) {
	$copy = $selected; $copy[$field] = $value;
	expect( Rules::allows( $copy, $c ), 'Each independent inclusion works.' );
	$copy['exclude_ids'] = array(12);
	expect( ! Rules::allows( $copy, $c ), 'Exclusion wins against each inclusion.' );
}
foreach ( array( '/contact', '/contact/' ) as $path ) {
	expect( Rules::matches_path( $path, '/contact' ), 'Trailing slash equivalence.' );
}
expect( Rules::matches_path( '/services/detail', '/services/*' ), 'Wildcard matches child.' );
expect( ! Rules::matches_path( '/services', '/services/*' ), 'Wildcard child does not match parent.' );
expect( ! Rules::matches_path( '/contact-extra', '/contact' ), 'Exact path boundary.' );
expect( Rules::matches_path( '/', '/*' ), 'Wildcard root.' );
expect( ! Rules::matches_path( '/anything', '' ), 'Empty paths match nothing.' );
expect( Rules::matches_path( '/%D8%AA%D9%85%D8%A7%D8%B3/', '/تماس' ), 'Unicode slug.' );
expect( ! Rules::matches_path( '/axb', '/a.b' ), 'Regex syntax treated literally.' );
foreach ( array( 'front', 'home', 'archive', 'search', '404', 'shop', 'product', 'product_archive', 'cart', 'checkout', 'account' ) as $context ) {
	$copy = $selected; $copy['contexts'] = array($context);
	$ctx = $c; $ctx['contexts'] = array($context);
	expect( Rules::allows( $copy, $ctx ), 'Include special/WooCommerce context.' );
	$copy['exclude_contexts'] = array($context);
	expect( ! Rules::allows( $copy, $ctx ), 'Special context exclusion wins.' );
}
$copy = $s; $copy['exclude_paths'] = '/contact';
expect( ! Rules::allows( $copy, $c ), 'Path exclusion.' );
$GLOBALS['hg_options'][Settings::OPTION] = $s;
$clean = Settings::sanitize( array(
	'widget_key' => $key, 'enabled' => 1, 'service_consent' => 1, 'mode' => 'evil',
	'load_mode' => 'evil', 'admin_language' => '../../evil', 'delay' => 500,
	'include_ids' => array(12, '12', 0, array(55)), 'post_types' => array('page', 'secret', array('post')),
	'contexts' => array('checkout', 'evil', array('cart')),
	'include_paths' => "/safe\nhttps://evil.test/\n/x?key=1\n/a#b\n<script>\n/path/*\n/safe",
	'unknown' => 'must not persist',
) );
expect( $clean['mode'] === 'all' && $clean['load_mode'] === 'idle', 'Enum allowlists.' );
expect( $clean['admin_language'] === 'auto', 'Locale allowlist.' );
expect( $clean['delay'] === 120, 'Bounded delay.' );
expect( $clean['include_ids'] === array(12), 'Unique nonzero scalar IDs.' );
expect( $clean['post_types'] === array('page'), 'Public content type allowlist.' );
expect( $clean['contexts'] === array('checkout'), 'Context allowlist.' );
expect( $clean['include_paths'] === "/safe\n/path/*", 'Reject full URLs and unsafe paths.' );
expect( ! isset($clean['unknown']), 'Unknown settings discarded.' );
$invalid = Settings::sanitize( array('widget_key' => '<script>bad</script>', 'enabled' => 1) );
expect( $invalid['widget_key'] === $key && $invalid['enabled'] === 0, 'Invalid input preserves saved key and disables without consent.' );
expect( Settings::sanitize( array('enabled' => 1) )['enabled'] === 0, 'Cannot enable without key.' );
expect( Settings::sanitize( array('delay' => array()) )['delay'] === 3, 'Malformed numeric field.' );

$_SERVER['REQUEST_URI'] = '/wordpress/contact/?utm_source=test';
$GLOBALS['hg_home'] = 'https://example.test/wordpress/';
$GLOBALS['hg_flags'] = array('singular' => true);
$ctx = Rules::current_context();
expect( $ctx['path'] === '/contact/' && $ctx['id'] === 12, 'Home subdirectory and ignored query.' );
$GLOBALS['hg_flags'] = array('home' => true);
expect( Rules::current_context()['id'] === 12, 'Posts page retains page ID.' );
foreach ( array('admin', 'ajax', 'cron', 'feed', 'embed', 'preview') as $flag ) {
	$GLOBALS['hg_flags'] = array($flag => true);
	expect( Rules::current_context()['private'], 'Exclude nonpublic output.' );
}
$GLOBALS['hg_flags'] = array('singular' => true);
$GLOBALS['hg_home'] = 'https://example.test/';
$_SERVER['REQUEST_URI'] = '/contact';
$GLOBALS['hg_options'][Settings::OPTION] = array_merge($s, array('exclude_paths' => '/contact'));
Frontend::enqueue();
expect( !$GLOBALS['hg_scripts'] && !$GLOBALS['hg_styles'] && !$GLOBALS['hg_inline'], 'Excluded pages enqueue no files or config.' );
$GLOBALS['hg_options'][Settings::OPTION] = $s;
Frontend::enqueue();
expect( isset($GLOBALS['hg_scripts']['hamgoo-loader']) && count($GLOBALS['hg_scripts']) === 1, 'Only local loader enqueued.' );
expect( strpos($GLOBALS['hg_scripts']['hamgoo-loader']['url'], 'https://') !== 0, 'No remote script enqueued eagerly.' );
expect( !$GLOBALS['hg_styles'], 'Automatic mode needs no extra frontend stylesheet.' );
$GLOBALS['hg_options'][Settings::OPTION]['load_mode'] = 'click';
Frontend::enqueue();
expect( isset($GLOBALS['hg_styles']['hamgoo-launcher']), 'Click mode includes local button style.' );

$translations = json_decode(file_get_contents(HAMGOO_DIR . 'languages/translations.json'), true);
foreach ( array('en_US', 'fa_IR', 'ar', 'ru_RU', 'ur') as $locale ) {
	$GLOBALS['hg_catalog'] = array();
	if ($locale !== 'en_US') foreach ($translations as $source => $locales) $GLOBALS['hg_catalog'][$source] = $locales[$locale];
	$GLOBALS['hg_locale'] = $locale;
	$GLOBALS['hg_options'][Settings::OPTION] = Settings::defaults();
	ob_start(); Admin::render(); $html = ob_get_clean();
	expect( strpos($html, 'id="hg-section-help"') !== false, 'Full admin renders for every locale.' );
	expect( strpos($html, 'dir="' . (in_array($locale, array('fa_IR', 'ar', 'ur')) ? 'rtl' : 'ltr') . '"') !== false, 'Correct direction for each locale.' );
	expect( strpos($html, 'assets/images/welcome.svg') !== false, 'Bundled illustration fallback.' );
	expect( strpos($html, 'https://cp.hamkaran.cloud/widget/chat.js') === false, 'Admin never embeds remote widget.' );
}
$GLOBALS['hg_catalog'] = array();
$GLOBALS['hg_posts'][12] = '<img src=x onerror=alert(1)>';
$GLOBALS['hg_options'][Settings::OPTION]['include_ids'] = array(12);
ob_start(); Admin::render(); $html = ob_get_clean();
expect( strpos($html, '<img src=x') === false && strpos($html, '&lt;img src=x') !== false, 'Saved content title is escaped.' );
$GLOBALS['hg_capability'] = false;
ob_start(); Admin::render(); $html = ob_get_clean();
expect( $html === '', 'Admin page requires manage_options.' );
echo "PASS: $count PHP assertions\n";
