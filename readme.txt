=== Hamgoo Live Chat ===
Contributors:
Tags: live chat, customer support, hamgoo, woocommerce, chat
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect Hamgoo chat with a widget key. Control pages, WooCommerce rules and loading. Includes Persian, English, Arabic, Russian and Urdu.

== Description ==

Hamgoo Live Chat connects a WordPress website to the Hamkaran cloud chat service.
Your team handles conversations in the Hamkaran panel. The plugin manages installation, page rules and loading.

This is the integration for the Hamgoo service, not a general-purpose script editor.
It connects visitors to your Hamgoo team's conversations and replies, with WordPress page and WooCommerce rules, consent integration and five plugin languages.
All page rules, loading modes, languages and consent features included in this plugin are available without a plugin license or upgrade.
The widget key identifies a real hosted chat service; it is not used to unlock local plugin features.

Source code and development instructions: https://github.com/Hamika-Group/hamgoo-live-chat

An active Hamgoo service, a Hamkaran account and an active widget key are required for live chat.
The WordPress plugin itself is free; the connected cloud service may require payment.
Visit https://hamkaran.cloud/ for service information, purchase options and consultation.
Existing customers can reach support at https://cp.hamkaran.cloud/.

Features:

* Connect using only the widget key; no account password or private API token.
* A dedicated navy and blue administration page.
* Bundled English source strings and complete Persian, Arabic, Russian and Urdu translations.
* Plugin-only administration language selection, with RTL support.
* Include or exclude published pages and posts using content search.
* Select public content types, homepage, blog index, archives, search and 404 pages.
* Optional WooCommerce rules for shop, products, product archives, cart, checkout and account.
* Exact paths and wildcard paths. Exclusions always take precedence.
* Device choices checked in the browser before service loading.
* Load after the page and browser idle time, after a delay, or after a visitor click.
* Optional consent-manager integration. No remote widget request before permission.
* No analytics or automatic remote calls from the plugin administration screen.
* Optional manual configuration check; it does not verify end-to-end message delivery.
* Per-site settings in multisite. No custom database tables.
* Suggested privacy-policy text and optional settings removal on uninstall.

The plugin panel uses the Hamkaran brand palette. Actual chat colors, text, position, response hours and agent behavior are configured in Hamgoo.
Bundled translations cover the WordPress plugin; they do not translate the remote chat service.

== External services ==

This plugin relies on the Hamkaran cloud service to provide live chat. After the administrator saves a valid key, accepts the service connection and enables chat, an eligible page receives a small local loader.
The loader requests https://cp.hamkaran.cloud/widget/chat.js only after the configured loading condition and, when enabled, consent.
This fixed HTTPS script is the client of the documented hosted chat service.
The plugin cannot accept arbitrary JavaScript, PHP, script URLs or install executable code into WordPress.
The hosted widget can show a "powered by hamkaran.cloud" link inside the chat.
The service-connection checkbox explicitly asks the administrator's permission to show that branding link before chat is enabled.

The hosted widget uses:

* cp.hamkaran.cloud: widget JavaScript, widget styles, fonts, icons and service assets.
* api.hamkaran.cloud: configuration, visitor sessions, OTP, messages, files and conversation operations.
* storage.s1.hamkaran.cloud: service-managed attachments and images when used by a chat.
* js.pusher.com: the realtime browser client when the service initializes realtime chat.
* The realtime websocket endpoint supplied by the service configuration.

Network requests expose the visitor IP address and normal browser request metadata to the contacted service.
When a visitor starts or resumes a chat, the widget may send page URL, page title, referrer, browser, operating system and session information.
Names, telephone numbers, email addresses, messages, typing information, attachments and voice messages supplied by the visitor are processed by the service.
The hosted widget uses localStorage and sessionStorage to keep the conversation session. Recording a voice message requests microphone permission.
The plugin stores only connection and display settings in WordPress; cloud conversations are not stored in its WordPress database.

Opening the plugin settings does not automatically contact the service.
Clicking "Check saved widget configuration" explicitly sends the saved public widget key to the configuration endpoint from the administrator's browser.
The check may be blocked by browser privacy settings or CORS; a successful response alone does not verify allowed-domain behavior or message delivery.

Service: https://hamkaran.cloud/
Service privacy policy: https://hamkaran.cloud/privacy-policy/
Service agreement: https://hamkaran.cloud/%D8%AA%D9%88%D8%A7%D9%81%D9%82-%D9%86%D8%A7%D9%85%D9%87-%D8%A7%D9%85%D9%86%DB%8C%D8%AA%DB%8C/
Realtime client privacy information: https://pusher.com/legal/privacy-policy/

== Installation ==

1. Upload the hamgoo-live-chat folder to /wp-content/plugins/, or install the release ZIP through Plugins → Add New → Upload Plugin.
2. Activate Hamgoo Live Chat.
3. In the Hamkaran panel, activate your Hamgoo service, create an active widget and configure its allowed domains.
4. Open Hamgoo in the WordPress administration menu.
5. Enter only the widget key shown beside your widget, accept the service connection and enable live chat.
6. Choose display rules and loading settings, then save.
7. Remove older copies of the widget installation scripts from your theme, tag manager or other plugins.
8. Clear page/CDN caches, open an eligible page and send a test message. Verify receipt and the reply in Hamgoo.

== Frequently Asked Questions ==

= Where do I get a widget key? =

Open the Hamkaran panel at https://cp.hamkaran.cloud/. In Hamgoo Settings → Widget, create a widget and copy its cw_… key. Do not paste the two installation script tags into the plugin.

= What do path rules mean? =

Paths are relative to the WordPress home URL. Use one per line.
/contact matches /contact and /contact/. /services/* matches children of services, but not /services itself.
Query strings are ignored. /* matches every path. Exclusions always override inclusions.
In selected-pages mode, at least one inclusion must match. If none is selected, chat stays hidden everywhere.

= Do I need WooCommerce? =

No. WooCommerce-specific choices appear when WooCommerce is active. Other rules work without it.

= Can the WordPress plugin change the chat design or translate the chat? =

Chat appearance, welcome text, position, opening behavior, response hours and remote language capabilities are managed by Hamgoo.
The plugin translations apply to its settings, guidance and local loading button.

= Does click mode open the conversation immediately? =

Clicking the local button loads the Hamgoo service. Once loaded, the local button disappears and the visitor uses the real widget. Automatic opening depends on the settings in Hamgoo.

= How does consent integration work? =

Enable "Wait for consent from my cookie manager". Your consent manager must grant permission on each page.
After permission, call window.HamgooLiveChat.grantConsent(). If approval occurs before the deferred loader runs, set window.HamgooLiveChatConsent = true.
After withdrawal, clear that flag and call window.HamgooLiveChat.revokeConsent(). Reload the page without granting permission to stop an already loaded third-party widget.
The plugin does not provide a cookie banner and does not persist consent.
JavaScript can also dispatch the window event hamgoo:consent with detail.granted set to true or false.

= Is the widget key private? =

No. It is a public installation identifier and is visible on eligible frontend pages. Restrict allowed domains in Hamgoo. Never enter your password or private API tokens.

= Does it change SEO metadata or guarantee rankings? =

No. It controls page eligibility and loading time. Website performance and search ranking also depend on the theme, page content, optimization configuration and the hosted widget.

= What happens on uninstall or multisite? =

Deactivation retains settings. Uninstall removes this site's settings only when the administrator opted in. Cloud conversations are unchanged.
Each site in multisite has its own key and settings, including after network activation. Configure and uninstall sites individually.

= What if optimization or caching breaks chat? =

Clear page and CDN caches after saving. If needed, exclude hamgoo-loader, HamgooLiveChatLoader and cp.hamkaran.cloud/widget/chat.js from script combination or delayed execution.
Preserve the configured loading and consent rules. The theme must use wp_head() and wp_footer().

== Changelog ==

= 1.0.0 =

* Initial release: widget-key connection, page and WooCommerce rules, device targeting and three loading modes.
* Navy/blue administration interface with English, Persian, Arabic, Russian and Urdu.
* Optional consent integration, setup guide and privacy disclosure.

== Upgrade Notice ==

= 1.0.0 =

Initial release. Configure your own widget key and clear page caches after enabling.
