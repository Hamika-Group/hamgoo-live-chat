<?php
/** Render static design previews; does not run WordPress or contact the service. */
require dirname( __DIR__ ) . '/tests/wp-stubs.php';
use Hamgoo\LiveChat\Admin;
use Hamgoo\LiveChat\Settings;

$translations = json_decode( file_get_contents( HAMGOO_DIR . 'languages/translations.json' ), true );
$locales = array( 'en_US', 'fa_IR', 'ar', 'ru_RU', 'ur' );
foreach ( $locales as $locale ) {
	$GLOBALS['hg_locale'] = $locale;
	$GLOBALS['hg_catalog'] = array();
	if ( 'en_US' !== $locale ) {
		foreach ( $translations as $source => $translated ) {
			$GLOBALS['hg_catalog'][ $source ] = $translated[ $locale ];
		}
	}
	$GLOBALS['hg_options'][ Settings::OPTION ] = Settings::defaults();
	$GLOBALS['hg_options'][ Settings::OPTION ]['admin_language'] = $locale;
	Admin::assets( 'toplevel_page_hamgoo-live-chat' );
	ob_start();
	Admin::render();
	$body = ob_get_clean();
	$direction = in_array( $locale, array( 'fa_IR', 'ar', 'ur' ), true ) ? 'rtl' : 'ltr';
	$html = '<!doctype html><html lang="' . str_replace( '_', '-', $locale ) . '" dir="' . $direction . '"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hamgoo · ' . $locale . '</title>';
	$html .= '<link rel="stylesheet" href="../assets/css/admin.css"><style>body{margin:0;background:#f0f3f7;padding:25px}.hg-app{margin:0 auto}.dashicons{display:none}input[type=radio],input[type=checkbox]{accent-color:#1b5c9e}.preview-note{max-width:1460px;margin:0 auto 15px;color:#53687c;font:13px system-ui}</style>';
	$html .= '<body><p class="preview-note">Static design preview · No settings are saved and no service connection is made.</p>' . $body;
	$html .= '<script>window.hamgooAdmin=' . json_encode( $GLOBALS['hg_localized']['hamgooAdmin'], JSON_HEX_TAG | JSON_HEX_AMP ) . ';document.getElementById("hg-settings-form").addEventListener("submit",function(e){e.preventDefault();});</script><script src="../assets/js/admin.js"></script></body></html>';
	$file = HAMGOO_DIR . 'docs/' . $locale . '-preview.html';
	file_put_contents( $file, $html );
	echo $file . "\n";
}
