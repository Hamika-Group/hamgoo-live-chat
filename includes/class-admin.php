<?php
/** Scoped administration page and authenticated content search. */

namespace Hamgoo\LiveChat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {
	public static function register() {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'wp_ajax_hamgoo_search_content', array( self::class, 'search' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( HAMGOO_FILE ), array( self::class, 'action_links' ) );
	}

	public static function menu() {
		add_menu_page( __( 'Hamgoo Live Chat', 'hamgoo-live-chat' ), __( 'Hamgoo', 'hamgoo-live-chat' ), 'manage_options', 'hamgoo-live-chat', array( self::class, 'render' ), 'dashicons-format-chat', 58 );
	}

	public static function settings() {
		register_setting(
			'hamgoo_live_chat',
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=hamgoo-live-chat' ) ) . '">' . esc_html__( 'Settings', 'hamgoo-live-chat' ) . '</a>' );
		return $links;
	}

	public static function assets( $hook ) {
		if ( 'toplevel_page_hamgoo-live-chat' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'hamgoo-admin', HAMGOO_URL . 'assets/css/admin.css', array( 'wp-admin' ), HAMGOO_VERSION );
		wp_enqueue_script( 'hamgoo-admin', HAMGOO_URL . 'assets/js/admin.js', array(), HAMGOO_VERSION, true );
		$settings = Settings::get();
		wp_localize_script(
			'hamgoo-admin',
			'hamgooAdmin',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'hamgoo_content_search' ),
				'widgetKey'    => $settings['service_consent'] ? $settings['widget_key'] : '',
				'checking'     => __( 'Checking service configuration…', 'hamgoo-live-chat' ),
				'connected'    => __( 'Configuration received. Open your website and send a test message to verify the full connection and allowed domain.', 'hamgoo-live-chat' ),
				'blocked'      => __( 'The service refused this request. Check the subscription, widget status and allowed domain in Hamgoo.', 'hamgoo-live-chat' ),
				'missing'      => __( 'Widget not found. Check the saved key and whether the widget is active.', 'hamgoo-live-chat' ),
				'networkError' => __( 'The check could not complete. Check your network, browser privacy settings and service availability.', 'hamgoo-live-chat' ),
				'noResults'    => __( 'No published content found.', 'hamgoo-live-chat' ),
				'searchError'  => __( 'Search failed. Reload the page and try again.', 'hamgoo-live-chat' ),
				'remove'       => __( 'Remove', 'hamgoo-live-chat' ),
				'unsaved'      => __( 'You have unsaved changes.', 'hamgoo-live-chat' ),
				'saved'        => __( 'All settings are saved.', 'hamgoo-live-chat' ),
			)
		);
	}

	public static function search() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		check_ajax_referer( 'hamgoo_content_search', 'nonce' );
		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		if ( ! preg_match( '/^.{2}/us', $term ) ) {
			wp_send_json_success( array() );
		}
		$query = new \WP_Query(
			array(
				'post_type'              => array_keys( get_post_types( array( 'public' => true ) ) ),
				'post_status'            => 'publish',
				's'                      => $term,
				'posts_per_page'         => 15,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		$items = array();
		foreach ( $query->posts as $post ) {
			$items[] = array(
				'id'    => $post->ID,
				'title' => html_entity_decode( get_the_title( $post ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'type'  => $post->post_type,
			);
		}
		wp_send_json_success( $items );
	}

	private static function field( $key ) {
		return Settings::OPTION . '[' . $key . ']';
	}

	private static function toggle( $key, $label, $settings, $help = '' ) {
		?>
		<label class="hg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::field( $key ) ); ?>" value="1" <?php checked( $settings[ $key ], 1 ); ?> />
			<span class="hg-toggle-track" aria-hidden="true"></span>
			<span><strong><?php echo esc_html( $label ); ?></strong>
			<?php
			if ( $help ) :
				?>
				<small><?php echo esc_html( $help ); ?></small><?php endif; ?></span>
		</label>
		<?php
	}

	private static function content_picker( $key, $label, $settings ) {
		?>
		<div class="hg-picker" data-field="<?php echo esc_attr( self::field( $key ) ); ?>">
			<label class="hg-label" for="hg-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input id="hg-<?php echo esc_attr( $key ); ?>" type="text" role="searchbox" autocomplete="off" placeholder="<?php esc_attr_e( 'Search by title (at least 2 characters)', 'hamgoo-live-chat' ); ?>" aria-describedby="hg-picker-help" />
			<div class="hg-search-results" aria-live="polite"></div>
			<div class="hg-chips">
			<?php
			foreach ( $settings[ $key ] as $id ) :
				$title = get_the_title( $id );
				?>
				<span class="hg-chip" data-id="<?php echo esc_attr( $id ); ?>">
					<span><?php echo esc_html( $title ? $title : '#' . $id ); ?></span>
					<input type="hidden" name="<?php echo esc_attr( self::field( $key ) ); ?>[]" value="<?php echo esc_attr( $id ); ?>" />
					<button type="button" aria-label="<?php esc_attr_e( 'Remove', 'hamgoo-live-chat' ); ?>">×</button>
				</span>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	private static function illustration( $name ) {
		$file = file_exists( HAMGOO_DIR . 'assets/images/' . $name . '.webp' ) ? $name . '.webp' : $name . '.svg';
		echo '<img class="hg-illustration" src="' . esc_url( HAMGOO_URL . 'assets/images/' . $file ) . '" width="480" height="320" alt="" loading="lazy" />';
	}

	private static function external_link( $url, $label, $css_class = 'hg-link' ) {
		echo '<a class="' . esc_attr( $css_class ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '<span aria-hidden="true"> ↗</span></a>';
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s          = Settings::get();
		$locale     = 'auto' === $s['admin_language'] ? determine_locale() : $s['admin_language'];
		$rtl        = (bool) preg_match( '/^(fa|ar|ur)(_|$)/', $locale );
		$configured = Settings::valid_key( $s['widget_key'] ) && $s['service_consent'];
		?>
		<div class="wrap hg-app" dir="<?php echo $rtl ? 'rtl' : 'ltr'; ?>" lang="<?php echo esc_attr( str_replace( '_', '-', $locale ) ); ?>">
			<header class="hg-header">
				<div class="hg-brand"><span class="hg-logo" aria-hidden="true"><span class="dashicons dashicons-format-chat"></span></span><div><span class="hg-eyebrow">HAMKARAN CLOUD</span><h1><?php esc_html_e( 'Hamgoo Live Chat', 'hamgoo-live-chat' ); ?></h1></div></div>
				<div class="hg-header-actions"><span class="hg-status <?php echo $s['enabled'] ? 'is-active' : ''; ?>"><?php echo $s['enabled'] ? esc_html__( 'Enabled on eligible pages', 'hamgoo-live-chat' ) : esc_html__( 'Chat is disabled', 'hamgoo-live-chat' ); ?></span><?php self::external_link( Settings::PANEL, __( 'Open Hamgoo panel', 'hamgoo-live-chat' ), 'hg-button hg-button-quiet' ); ?></div>
			</header>
			<?php settings_errors( Settings::OPTION ); ?>
			<form action="options.php" method="post" id="hg-settings-form">
				<?php settings_fields( 'hamgoo_live_chat' ); ?>
				<nav class="hg-tabs" aria-label="<?php esc_attr_e( 'Plugin sections', 'hamgoo-live-chat' ); ?>">
				<?php
				foreach ( array(
					'connection'  => array( 'admin-links', __( 'Connect & start', 'hamgoo-live-chat' ) ),
					'display'     => array( 'visibility', __( 'Display rules', 'hamgoo-live-chat' ) ),
					'performance' => array( 'performance', __( 'Loading & privacy', 'hamgoo-live-chat' ) ),
					'help'        => array( 'editor-help', __( 'Guide & support', 'hamgoo-live-chat' ) ),
				) as $tab => $info ) :
					?>
					<button type="button" class="hg-tab" data-tab="<?php echo esc_attr( $tab ); ?>" aria-controls="hg-section-<?php echo esc_attr( $tab ); ?>"><span class="dashicons dashicons-<?php echo esc_attr( $info[0] ); ?>" aria-hidden="true"></span><?php echo esc_html( $info[1] ); ?></button>
				<?php endforeach; ?>
				</nav>
				<div class="hg-layout">
				<main class="hg-main">
					<section class="hg-section" id="hg-section-connection" aria-labelledby="hg-connection-title">
						<div class="hg-hero">
							<div><span class="hg-eyebrow"><?php esc_html_e( 'A conversation starts here', 'hamgoo-live-chat' ); ?></span><h2 id="hg-connection-title"><?php esc_html_e( 'Be there when customers need you.', 'hamgoo-live-chat' ); ?></h2><p><?php esc_html_e( 'Connect your website to Hamgoo. Your team replies from the Hamkaran panel, and you decide where chat appears.', 'hamgoo-live-chat' ); ?></p><a class="hg-button hg-button-light" href="#hg-widget-key"><?php esc_html_e( 'I have a widget key', 'hamgoo-live-chat' ); ?></a></div>
							<?php self::illustration( 'welcome' ); ?>
						</div>
						<div class="hg-card">
							<div class="hg-card-heading"><span class="hg-step">01</span><div><h2><?php esc_html_e( 'Connect your widget', 'hamgoo-live-chat' ); ?></h2><p><?php esc_html_e( 'One key. No installation scripts or panel password.', 'hamgoo-live-chat' ); ?></p></div></div>
							<label class="hg-label" for="hg-widget-key"><?php esc_html_e( 'Widget key', 'hamgoo-live-chat' ); ?></label>
							<input class="hg-key" id="hg-widget-key" name="<?php echo esc_attr( self::field( 'widget_key' ) ); ?>" type="text" value="<?php echo esc_attr( $s['widget_key'] ); ?>" placeholder="cw_…" dir="ltr" autocomplete="off" spellcheck="false" maxlength="35" aria-describedby="hg-key-help" />
							<p id="hg-key-help" class="hg-hint"><?php esc_html_e( 'In Hamgoo: Settings → Widget. Copy the key shown beside your widget. Only the cw_… value belongs here.', 'hamgoo-live-chat' ); ?></p>
							<?php self::toggle( 'service_consent', __( 'I accept connecting this site to Hamkaran chat and displaying its branding link inside the chat widget.', 'hamgoo-live-chat' ), $s, __( 'The widget contacts an external service and processes visitor information when loaded. Read the privacy details before enabling.', 'hamgoo-live-chat' ) ); ?>
							<div class="hg-inline-links"><?php self::external_link( Settings::PRIVACY, __( 'Service privacy policy', 'hamgoo-live-chat' ) ); ?><?php self::external_link( Settings::TERMS, __( 'Service agreement', 'hamgoo-live-chat' ) ); ?></div>
							<hr />
							<?php self::toggle( 'enabled', __( 'Enable live chat', 'hamgoo-live-chat' ), $s, __( 'Requires a valid saved key and acceptance of the service connection.', 'hamgoo-live-chat' ) ); ?>
							<div class="hg-test-row"><button id="hg-test" class="hg-button hg-button-outline" type="button" <?php disabled( ! $configured ); ?>><?php esc_html_e( 'Check saved widget configuration', 'hamgoo-live-chat' ); ?></button><a class="hg-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open website to test', 'hamgoo-live-chat' ); ?> ↗</a></div>
							<p class="hg-hint"><?php esc_html_e( 'This check makes a request only when clicked, using the saved key. It checks configuration, not message delivery.', 'hamgoo-live-chat' ); ?></p><p id="hg-test-result" role="status" aria-live="polite"></p>
						</div>
						<?php if ( ! $configured ) : ?>
						<div class="hg-card hg-onboarding">
							<div><?php self::illustration( 'consultation' ); ?></div><div><span class="hg-eyebrow"><?php esc_html_e( 'New to Hamgoo?', 'hamgoo-live-chat' ); ?></span><h2><?php esc_html_e( 'Give your customers a direct line to your team.', 'hamgoo-live-chat' ); ?></h2><p><?php esc_html_e( 'An active Hamgoo service and a widget created in your account are required. Visit Hamkaran to learn about the service, purchase options and consultation.', 'hamgoo-live-chat' ); ?></p><div class="hg-inline-links"><?php self::external_link( Settings::HOME, __( 'Explore service & purchase', 'hamgoo-live-chat' ), 'hg-button' ); ?><?php self::external_link( Settings::HOME, __( 'Get consultation', 'hamgoo-live-chat' ), 'hg-button hg-button-outline' ); ?></div></div>
						</div>
						<?php endif; ?>
						<div class="hg-card">
							<h2><?php esc_html_e( 'Plugin language', 'hamgoo-live-chat' ); ?></h2>
							<label for="hg-language" class="hg-label"><?php esc_html_e( 'Administration language', 'hamgoo-live-chat' ); ?></label>
							<select id="hg-language" name="<?php echo esc_attr( self::field( 'admin_language' ) ); ?>">
							<?php
							foreach ( array(
								'auto'  => __( 'Follow WordPress user language', 'hamgoo-live-chat' ),
								'en_US' => 'English',
								'fa_IR' => 'فارسی',
								'ar'    => 'العربية',
								'ru_RU' => 'Русский',
								'ur'    => 'اردو',
							) as $value => $label ) :
								?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['admin_language'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
							</select><p class="hg-hint"><?php esc_html_e( 'Changes only this plugin panel. Save to apply. Chat appearance and messages are managed in Hamgoo.', 'hamgoo-live-chat' ); ?></p>
						</div>
					</section>
					<section class="hg-section" id="hg-section-display" aria-labelledby="hg-display-title">
						<div class="hg-section-intro"><div><span class="hg-eyebrow"><?php esc_html_e( 'The right pages, the right moment', 'hamgoo-live-chat' ); ?></span><h2 id="hg-display-title"><?php esc_html_e( 'Choose where chat appears.', 'hamgoo-live-chat' ); ?></h2><p><?php esc_html_e( 'Excluded pages never receive the chat loader. Exclusions always override inclusions.', 'hamgoo-live-chat' ); ?></p></div><?php self::illustration( 'display-rules' ); ?></div>
						<div class="hg-card">
							<h2><?php esc_html_e( 'Display scope', 'hamgoo-live-chat' ); ?></h2>
							<div class="hg-choice-grid">
								<label class="hg-choice"><input type="radio" name="<?php echo esc_attr( self::field( 'mode' ) ); ?>" value="all" <?php checked( $s['mode'], 'all' ); ?> /><span><strong><?php esc_html_e( 'All public pages', 'hamgoo-live-chat' ); ?></strong><small><?php esc_html_e( 'Show throughout the website, except your exclusions.', 'hamgoo-live-chat' ); ?></small></span></label>
								<label class="hg-choice"><input type="radio" name="<?php echo esc_attr( self::field( 'mode' ) ); ?>" value="selected" <?php checked( $s['mode'], 'selected' ); ?> /><span><strong><?php esc_html_e( 'Selected pages only', 'hamgoo-live-chat' ); ?></strong><small><?php esc_html_e( 'A page must match at least one inclusion below.', 'hamgoo-live-chat' ); ?></small></span></label>
							</div>
							<div id="hg-inclusions">
								<?php self::content_picker( 'include_ids', __( 'Include individual pages or posts', 'hamgoo-live-chat' ), $s ); ?>
								<p id="hg-picker-help" class="hg-hint"><?php esc_html_e( 'Search published content and select results. Selected items stay attached to their IDs even when their URLs change.', 'hamgoo-live-chat' ); ?></p>
								<h3><?php esc_html_e( 'Include content types', 'hamgoo-live-chat' ); ?></h3><div class="hg-check-grid">
								<?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( self::field( 'post_types' ) ); ?>[]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, $s['post_types'], true ) ); ?> /><?php echo esc_html( $type->labels->name ); ?></label>
								<?php endforeach; ?>
								</div>
								<?php self::contexts( 'contexts', __( 'Include special pages', 'hamgoo-live-chat' ), $s ); ?>
								<label class="hg-label" for="hg-include-paths"><?php esc_html_e( 'Include paths', 'hamgoo-live-chat' ); ?></label><textarea id="hg-include-paths" name="<?php echo esc_attr( self::field( 'include_paths' ) ); ?>" rows="4" dir="ltr" placeholder="/services/*"><?php echo esc_textarea( $s['include_paths'] ); ?></textarea>
							</div>
						</div>
						<div class="hg-card">
							<div class="hg-card-heading"><span class="dashicons dashicons-hidden" aria-hidden="true"></span><div><h2><?php esc_html_e( 'Always exclude', 'hamgoo-live-chat' ); ?></h2><p><?php esc_html_e( 'These rules apply in both display modes.', 'hamgoo-live-chat' ); ?></p></div></div>
							<?php self::content_picker( 'exclude_ids', __( 'Exclude individual pages or posts', 'hamgoo-live-chat' ), $s ); ?>
							<?php self::contexts( 'exclude_contexts', __( 'Exclude special pages', 'hamgoo-live-chat' ), $s ); ?>
							<label class="hg-label" for="hg-exclude-paths"><?php esc_html_e( 'Exclude paths', 'hamgoo-live-chat' ); ?></label><textarea id="hg-exclude-paths" name="<?php echo esc_attr( self::field( 'exclude_paths' ) ); ?>" rows="4" dir="ltr" placeholder="/checkout/*"><?php echo esc_textarea( $s['exclude_paths'] ); ?></textarea>
							<div class="hg-note"><?php esc_html_e( 'One path per line, relative to the WordPress home URL. /contact matches that page; /services/* matches paths inside services. Query strings are ignored and trailing slashes are equivalent. Use /* for every path. /services/* does not include /services itself.', 'hamgoo-live-chat' ); ?></div>
						</div>
						<div class="hg-card">
							<label class="hg-label" for="hg-devices"><?php esc_html_e( 'Devices', 'hamgoo-live-chat' ); ?></label>
							<select id="hg-devices" name="<?php echo esc_attr( self::field( 'devices' ) ); ?>">
							<?php
							foreach ( array(
								'all'     => __( 'Mobile & desktop', 'hamgoo-live-chat' ),
								'mobile'  => __( 'Mobile only', 'hamgoo-live-chat' ),
								'desktop' => __( 'Desktop only', 'hamgoo-live-chat' ),
							) as $value => $label ) :
								?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['devices'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
							</select><p class="hg-hint"><?php esc_html_e( 'Mobile means a viewport up to 767px wide. The browser checks this before connecting, so cached pages can be shared safely between devices.', 'hamgoo-live-chat' ); ?></p>
						</div>
					</section>
					<section class="hg-section" id="hg-section-performance" aria-labelledby="hg-performance-title">
						<div class="hg-section-intro"><div><span class="hg-eyebrow"><?php esc_html_e( 'Keep your pages focused', 'hamgoo-live-chat' ); ?></span><h2 id="hg-performance-title"><?php esc_html_e( 'Control when chat connects.', 'hamgoo-live-chat' ); ?></h2><p><?php esc_html_e( 'The remote widget is requested only when the selected loading condition is met.', 'hamgoo-live-chat' ); ?></p></div><?php self::illustration( 'connect' ); ?></div>
						<div class="hg-card">
							<h2><?php esc_html_e( 'Loading strategy', 'hamgoo-live-chat' ); ?></h2>
							<?php
							foreach ( array(
								'idle'  => array( __( 'After the page loads · Recommended', 'hamgoo-live-chat' ), __( 'Wait for the window load event and an idle browser moment, with a short fallback timeout.', 'hamgoo-live-chat' ) ),
								'delay' => array( __( 'After a delay', 'hamgoo-live-chat' ), __( 'Wait the selected number of seconds after the page loads.', 'hamgoo-live-chat' ) ),
								'click' => array( __( 'Only when the visitor clicks', 'hamgoo-live-chat' ), __( 'Show a lightweight local chat button. Load the service after it is clicked; the visitor can then open the Hamgoo widget.', 'hamgoo-live-chat' ) ),
							) as $value => $info ) :
								?>
								<label class="hg-choice hg-choice-wide"><input type="radio" name="<?php echo esc_attr( self::field( 'load_mode' ) ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $s['load_mode'], $value ); ?> /><span><strong><?php echo esc_html( $info[0] ); ?></strong><small><?php echo esc_html( $info[1] ); ?></small></span></label>
							<?php endforeach; ?>
							<label class="hg-label" for="hg-delay"><?php esc_html_e( 'Delay in seconds (0–120)', 'hamgoo-live-chat' ); ?></label><input id="hg-delay" name="<?php echo esc_attr( self::field( 'delay' ) ); ?>" type="number" min="0" max="120" value="<?php echo esc_attr( $s['delay'] ); ?>" />
							<p class="hg-hint"><?php esc_html_e( 'These options can reduce initial network work. Search ranking and performance scores also depend on your theme, content and the remote widget.', 'hamgoo-live-chat' ); ?></p>
						</div>
						<div class="hg-card">
							<h2><?php esc_html_e( 'Visitor privacy', 'hamgoo-live-chat' ); ?></h2>
							<?php self::toggle( 'require_consent', __( 'Wait for consent from my cookie manager', 'hamgoo-live-chat' ), $s, __( 'No remote connection or local chat button appears until your consent integration allows chat. This plugin does not provide a cookie banner.', 'hamgoo-live-chat' ) ); ?>
							<p><?php esc_html_e( 'When this option is enabled, your consent manager must call window.HamgooLiveChat.grantConsent() after approval and window.HamgooLiveChat.revokeConsent() after withdrawal. Existing chats are stopped on reload after withdrawal; an already loaded third-party widget cannot be fully unloaded by this plugin.', 'hamgoo-live-chat' ); ?></p>
							<div class="hg-note"><?php esc_html_e( 'Consent is kept in memory for this page only. Your consent manager should apply the visitor choice on every page. Until it grants consent, chat remains unavailable.', 'hamgoo-live-chat' ); ?></div>
							<p><?php esc_html_e( 'When loaded, the widget contacts Hamkaran and the realtime provider. Chat can send page URL, title, referrer, browser, operating system, session details and visitor-entered contact information, messages and files. Browser storage maintains the conversation. Voice recording requests microphone permission.', 'hamgoo-live-chat' ); ?></p>
							<?php self::external_link( Settings::PRIVACY, __( 'Read the service privacy policy', 'hamgoo-live-chat' ) ); ?>
							<p class="hg-hint"><?php esc_html_e( 'Suggested disclosure text is available in WordPress Settings → Privacy. Adapt it to your site and service configuration.', 'hamgoo-live-chat' ); ?></p>
							<hr /><?php self::toggle( 'delete_on_uninstall', __( 'Delete plugin settings when uninstalling', 'hamgoo-live-chat' ), $s, __( 'Deactivation keeps settings. Uninstalling with this option enabled removes settings only on this site; cloud conversations are unaffected.', 'hamgoo-live-chat' ) ); ?>
						</div>
					</section>
					<section class="hg-section" id="hg-section-help" aria-labelledby="hg-help-title">
						<div class="hg-section-intro"><div><span class="hg-eyebrow"><?php esc_html_e( 'A clear path to your first conversation', 'hamgoo-live-chat' ); ?></span><h2 id="hg-help-title"><?php esc_html_e( 'Setup guide & answers', 'hamgoo-live-chat' ); ?></h2><p><?php esc_html_e( 'Appearance and agent replies are managed in Hamgoo. WordPress manages installation and page rules.', 'hamgoo-live-chat' ); ?></p></div></div>
						<div class="hg-card"><h2><?php esc_html_e( 'Start in four steps', 'hamgoo-live-chat' ); ?></h2><ol class="hg-guide">
							<li><?php esc_html_e( 'Activate the Hamgoo service in your Hamkaran account. Open Settings → Widget, create an active widget and add your website to its allowed domains.', 'hamgoo-live-chat' ); ?></li>
							<li><?php esc_html_e( 'Copy the widget key shown beside the widget, enter it in Connect & start, accept the service connection and enable chat.', 'hamgoo-live-chat' ); ?></li>
							<li><?php esc_html_e( 'Choose display rules and a loading strategy, then save. Remove any older copy of the installation scripts from your theme, tag manager or code-insertion plugin.', 'hamgoo-live-chat' ); ?></li>
							<li><?php esc_html_e( 'Clear your page cache. Open an eligible page as a visitor, start a conversation and confirm that your team receives and can reply to the message in Hamgoo.', 'hamgoo-live-chat' ); ?></li>
						</ol><?php self::external_link( Settings::PANEL, __( 'Open Hamgoo panel', 'hamgoo-live-chat' ), 'hg-button' ); ?></div>
						<div class="hg-card">
							<?php foreach ( self::faq() as $question => $answer ) : ?>
								<details class="hg-faq"><summary><?php echo esc_html( $question ); ?></summary><p><?php echo esc_html( $answer ); ?></p></details>
							<?php endforeach; ?>
						</div>
						<div class="hg-card hg-support"><div><h2><?php esc_html_e( 'Need a hand?', 'hamgoo-live-chat' ); ?></h2><p><?php esc_html_e( 'Existing customers can contact support through the Hamkaran panel. For service information, purchase or consultation, visit the Hamkaran website.', 'hamgoo-live-chat' ); ?></p><div class="hg-inline-links"><?php self::external_link( Settings::PANEL, __( 'Support in your panel', 'hamgoo-live-chat' ), 'hg-button' ); ?><?php self::external_link( Settings::HOME, __( 'Service & consultation', 'hamgoo-live-chat' ), 'hg-button hg-button-outline' ); ?></div></div><?php self::illustration( 'consultation' ); ?></div>
					</section>
				</main>
				<aside class="hg-aside">
					<div class="hg-card hg-overview"><span class="hg-eyebrow"><?php esc_html_e( 'YOUR CHAT SETUP', 'hamgoo-live-chat' ); ?></span><h2><?php esc_html_e( 'Ready for a conversation?', 'hamgoo-live-chat' ); ?></h2><ul>
						<li><span class="hg-dot <?php echo $configured ? 'is-ok' : ''; ?>"></span><?php echo $configured ? esc_html__( 'Widget key saved', 'hamgoo-live-chat' ) : esc_html__( 'Add your widget key', 'hamgoo-live-chat' ); ?></li>
						<li><span class="hg-dot <?php echo $s['enabled'] ? 'is-ok' : ''; ?>"></span><?php echo $s['enabled'] ? esc_html__( 'Chat enabled', 'hamgoo-live-chat' ) : esc_html__( 'Enable chat after setup', 'hamgoo-live-chat' ); ?></li>
						<li><span class="hg-dot is-ok"></span><?php echo 'all' === $s['mode'] ? esc_html__( 'All pages, with exclusions', 'hamgoo-live-chat' ) : esc_html__( 'Only selected pages', 'hamgoo-live-chat' ); ?></li>
					</ul><div class="hg-mini-preview" aria-hidden="true"><div class="hg-preview-browser"><i></i><i></i><i></i></div><div class="hg-preview-line"></div><div class="hg-preview-line short"></div><span class="hg-preview-chat"><span class="dashicons dashicons-format-chat"></span></span></div><p class="hg-hint"><?php esc_html_e( 'Illustrative preview. Actual widget colors, position and messages come from your Hamgoo settings.', 'hamgoo-live-chat' ); ?></p></div>
					<div class="hg-card"><h3><?php esc_html_e( 'Your brand, your conversation', 'hamgoo-live-chat' ); ?></h3><p><?php esc_html_e( 'Change colors, welcome text, launcher position and response hours in the Hamgoo panel. Changes there are used by your connected widget.', 'hamgoo-live-chat' ); ?></p><?php self::external_link( Settings::PANEL, __( 'Manage widget appearance', 'hamgoo-live-chat' ) ); ?></div>
				</aside>
				</div>
				<footer class="hg-savebar"><span id="hg-save-status" role="status"><?php esc_html_e( 'All settings are saved.', 'hamgoo-live-chat' ); ?></span><button class="hg-button" type="submit"><?php esc_html_e( 'Save settings', 'hamgoo-live-chat' ); ?></button></footer>
			</form>
			<div class="hg-footer">Hamgoo Live Chat <span dir="ltr"><?php echo esc_html( HAMGOO_VERSION ); ?></span><div><?php self::external_link( Settings::PRIVACY, __( 'Privacy', 'hamgoo-live-chat' ) ); ?><?php self::external_link( Settings::TERMS, __( 'Service agreement', 'hamgoo-live-chat' ) ); ?></div></div>
		</div>
		<?php
	}

	private static function contexts( $key, $label, $settings ) {
		echo '<h3>' . esc_html( $label ) . '</h3><div class="hg-check-grid">';
		foreach ( Rules::context_labels() as $value => $text ) {
			if ( in_array( $value, array( 'shop', 'product', 'product_archive', 'cart', 'checkout', 'account' ), true ) && ! class_exists( 'WooCommerce' ) && ! in_array( $value, $settings[ $key ], true ) ) {
				continue;
			}
			?>
			<label><input type="checkbox" name="<?php echo esc_attr( self::field( $key ) ); ?>[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $settings[ $key ], true ) ); ?> /><?php echo esc_html( $text ); ?></label>
			<?php
		}
		echo '</div>';
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p class="hg-hint">' . esc_html__( 'WooCommerce page choices appear when WooCommerce is active.', 'hamgoo-live-chat' ) . '</p>';
		}
	}

	private static function faq() {
		return array(
			__( 'Why does chat not appear?', 'hamgoo-live-chat' ) => __( 'Check the saved key, enable switch, accepted service connection, active subscription and widget, allowed domain, display exclusions and device choice. With consent mode, your cookie manager must grant consent. In click mode, click the local button. Clear caches after saving.', 'hamgoo-live-chat' ),
			__( 'Where do I change colors and response hours?', 'hamgoo-live-chat' ) => __( 'In the Hamgoo panel: Settings → Widget for appearance and Settings → Response hours for availability. The WordPress plugin uses those settings.', 'hamgoo-live-chat' ),
			__( 'Can I use this without a Hamgoo account?', 'hamgoo-live-chat' ) => __( 'You can install and explore the settings, but live chat requires an active Hamgoo service and your own widget key. Visit Hamkaran for purchase and consultation.', 'hamgoo-live-chat' ),
			__( 'How do I avoid duplicate widgets?', 'hamgoo-live-chat' ) => __( 'Remove the earlier Hamkaran installation scripts from your theme, tag manager and other plugins. This loader avoids inserting the same script twice, but cannot reconcile different keys installed by another tool.', 'hamgoo-live-chat' ),
			__( 'What about cache and optimization plugins?', 'hamgoo-live-chat' ) => __( 'Clear page and CDN caches after changes. If JavaScript optimization breaks chat, exclude hamgoo-loader, HamgooLiveChatLoader and cp.hamkaran.cloud/widget/chat.js from combining or delayed execution. Selected loading and consent rules must still be preserved.', 'hamgoo-live-chat' ),
			__( 'Does this improve Google rankings?', 'hamgoo-live-chat' ) => __( 'Page exclusions and deferred loading reduce unnecessary work. The plugin does not change your SEO metadata or guarantee rankings. Test the final website performance, including the remote widget.', 'hamgoo-live-chat' ),
			__( 'Is the widget key a secret?', 'hamgoo-live-chat' ) => __( 'It is a public installation identifier visible in eligible website pages, not your panel password. Restrict allowed domains in Hamgoo. Never enter account passwords or private API tokens here.', 'hamgoo-live-chat' ),
			__( 'Where are chats stored?', 'hamgoo-live-chat' ) => __( 'Conversations are handled by the Hamkaran cloud service. This plugin stores connection and display settings in WordPress and does not copy chat messages into WordPress.', 'hamgoo-live-chat' ),
			__( 'Does it work with page builders and multisite?', 'hamgoo-live-chat' ) => __( 'It uses standard WordPress frontend hooks and requires the theme to call wp_head() and wp_footer(). Page builders do not need a separate integration. In multisite, each site has its own widget key and settings; configure sites individually.', 'hamgoo-live-chat' ),
		);
	}
}
