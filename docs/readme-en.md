# Hamgoo Live Chat

[فارسی](../README.md) · English · [العربية](readme-ar.md) · [Русский](readme-ru.md) · [اردو](readme-ur.md)

Connect WordPress to Hamgoo using a public widget key. A navy and blue administration panel controls where and when chat loads. Conversations, agents, chat appearance and response hours are managed in the Hamkaran cloud service.

[Service, purchase and consultation](https://hamkaran.cloud/) · [Account and support](https://cp.hamkaran.cloud/) · [Downloads](https://github.com/Hamika-Group/hamgoo-live-chat/releases) · [Plugin issues](https://github.com/Hamika-Group/hamgoo-live-chat/issues)

## Requirements and features

WordPress **6.5+**, PHP **7.4+**, a Hamkaran account and an active Hamgoo widget with your website's domain allowed. WooCommerce is optional. The plugin is free; the cloud service may require a paid subscription. Customer sites do not need Composer, Python or Node.js.

- Connect with the widget key; never provide passwords or private API tokens.
- English, Persian, Arabic, Russian and Urdu, with a plugin-only language choice and RTL support.
- All-pages or selected-pages mode; search published pages/posts and choose public content types.
- Homepage, blog index, archives, search, 404 and optional WooCommerce rules.
- Exact/wildcard paths and exclusions that always override inclusion rules.
- Mobile/desktop targeting; idle, delayed or click-based loading.
- Consent-manager API, setup guidance, manual configuration check and suggested privacy text.
- Per-site multisite settings and optional settings cleanup on uninstall.

## Installation

1. Download **hamgoo-live-chat-X.Y.Z.zip** from Releases. GitHub's automatic “Source code” archives are not the installable plugin package.
2. In WordPress, open Plugins → Add New → Upload Plugin, install the ZIP and activate it.
3. In the Hamkaran panel, enable Hamgoo, create a widget in Hamgoo Settings → Widget and configure allowed domains.
4. Open the Hamgoo WordPress menu. Enter only the `cw_` widget key, accept the service connection, enable chat and save.
5. Set page and loading rules. Remove earlier script installations from your theme, tag manager or other plugins.
6. Clear page/CDN caches. Send a message from an eligible page and verify both delivery and the operator's reply.

The manual configuration check explicitly retrieves configuration from the administrator's browser. A successful response does not prove domain authorization or message delivery.

## Display rules and WooCommerce

All-pages mode allows eligible public pages. Selected-pages mode requires at least one inclusion to match; an empty selection hides chat everywhere. Inclusions are combined with OR. **Any exclusion hides chat.**

Paths are relative to the WordPress home URL, one per line. Query strings are ignored. `/contact` matches with or without its trailing slash; `/services/*` matches children but needs a separate `/services` rule for the parent. `/*` matches all paths.

With WooCommerce active, target or exclude the shop, products, product archives, cart, checkout and account. Use page or path rules for custom pages. Other rules work without WooCommerce. The loader is omitted from administration, AJAX, REST, cron, feeds, embeds and previews.

## Loading and performance

Idle mode waits for the page and browser idle time. Delay mode waits for the page and the configured delay. Click mode loads the service after a visitor clicks the local button and, if enabled, grants consent. The real widget replaces the local button; automatic conversation opening depends on Hamgoo settings.

Device checks run in the browser for compatibility with page caches; widths up to 767 pixels are treated as mobile. The plugin controls loading and page eligibility. It does not change SEO metadata or guarantee rankings or performance scores.

## Consent and external services

Enable the consent-manager option and connect your existing manager on every page. The plugin has no cookie banner and does not persist consent.

```js
// Permission before the deferred loader runs:
window.HamgooLiveChatConsent = true;
// Permission after it runs:
if (window.HamgooLiveChat) window.HamgooLiveChat.grantConsent();
// Withdrawal:
window.HamgooLiveChatConsent = false;
if (window.HamgooLiveChat) window.HamgooLiveChat.revokeConsent();
// Reload without permission to stop a widget already running.
// Alternative after the loader runs:
window.dispatchEvent(new CustomEvent('hamgoo:consent', {
  detail: { granted: true }
}));
```

Permission cannot override page exclusions or device restrictions. Withdrawal stops future loading; completely stopping an already running third-party widget requires a page reload without granting consent.

The eligible frontend requests `cp.hamkaran.cloud/widget/chat.js` after its loading condition and required permission. The widget also uses `api.hamkaran.cloud`, assets from `cp.hamkaran.cloud`, attachments/images from `storage.s1.hamkaran.cloud`, `js.pusher.com` and the configured realtime websocket endpoint.

Requests expose IP addresses and ordinary browser metadata. The service may process page URL/title, referrer, browser/session information and visitor-provided names, phone numbers, email, messages, typing, attachments and audio. It uses localStorage/sessionStorage; voice recording requires microphone permission. WordPress stores only plugin settings, not cloud conversations. Opening the administration screen makes no automatic service request.

Read the [full external-service disclosure](../readme.txt), [privacy policy](https://hamkaran.cloud/privacy-policy/) and [service agreement](https://hamkaran.cloud/%D8%AA%D9%88%D8%A7%D9%81%D9%82-%D9%86%D8%A7%D9%85%D9%87-%D8%A7%D9%85%D9%86%DB%8C%D8%AA%DB%8C/).

## Languages, artwork and removal

The five translations apply to the plugin administration and local loading button. Configure cloud chat language, colors, position, welcome text and hours in Hamgoo.

The local SVG artwork is temporary. See the [Persian artwork brief](artwork-fa.md) for WebP replacements and marketplace assets. Marketplace screenshots must show the actual plugin.

Deactivation keeps settings. Uninstall removes this site's settings only when cleanup was enabled; cloud conversations remain. Each multisite site has its own key and settings.

## Troubleshooting

- Missing chat: check service status, key, acceptance, allowed domains, inclusion/exclusion rules, device, consent and loading conditions.
- Stale settings: clear page/CDN caches. The theme must call `wp_head()` and `wp_footer()`.
- Optimization conflict: if needed, exclude `hamgoo-loader`, `HamgooLiveChatLoader` and `cp.hamkaran.cloud/widget/chat.js` from combination/additional delay, preserving consent rules.
- Failed diagnostic: browser privacy settings or CORS may block configuration; also test a real conversation.
- Duplicate widget: remove previous installations.

Use the support panel for service questions and Issues for plugin defects. Do not put passwords, tokens, personal information or customer conversations in public issues.

## Development and releases

Development requires PHP 7.4+, Composer, Python 3.9+ and Node.js. From the repository root:

```sh
composer install --no-interaction
composer lint
php tests/run.php
node --check assets/js/admin.js
node --check assets/js/loader.js
node tests/loader.test.js
python3 scripts/translations.py
git diff --exit-code -- languages
python3 scripts/verify.py
php scripts/preview.php
python3 scripts/package.py --tag v1.0.1
```

Translation source: `languages/translations.json`. Generation produces PO/MO/POT and fails on incomplete translations. Static previews are written to `docs/*-preview.html`; real search and connectivity need WordPress.

Optional integration check: `wp eval-file tests/wordpress-smoke.php`, **only in a disposable WordPress installation** with this plugin active. It writes temporary posts/settings; never run it on a customer/live site.

Keep the main file's Version and HAMGOO_VERSION, readme.txt's Stable tag and release tag consistent. Update the changelog and checks, commit, then:

```sh
git tag v1.0.1
git push origin main
git push origin v1.0.1
```

[CI](../.github/workflows/ci.yml) checks code, translations and the packaged plugin. [Release](../.github/workflows/release.yml) publishes the installable ZIP and SHA256SUMS after checks pass. Actions also supports manual runs using an existing stable tag. A rerun updates assets on the same release; never move a published tag. Download both files together and run `sha256sum --check SHA256SUMS`. The ZIP excludes tests, vendor, development docs and workflows.

The WordPress.org publisher account/slug are pending. Contributors remains empty until the real username is supplied; normal CI exempts only that warning while empty. WordPress publication requires full checks. After approval, configure the username, artwork and SVN secrets using the [release guide](release-fa.md). The [manual SVN workflow](../.github/workflows/wordpress.yml) verifies the GitHub package checksum and publishes it to WordPress.org. Updates use WordPress.org; there is no custom GitHub updater.

Source and bundled SVGs: **GPL-2.0-or-later**, see [LICENSE](../LICENSE). Replacement artwork must have a compatible license. [readme.txt](../readme.txt) is the standard marketplace listing.

The Hamgoo administration page uses locally bundled **Vazirmatn v33.003**, without a font CDN. Its [SIL Open Font License 1.1](../assets/fonts/vazirmatn/OFL.txt) ships in the package; see [source and asset details](third-party.md).
