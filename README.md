# Hamgoo Live Chat

WordPress integration for the Hamkaran cloud live-chat service.

The plugin accepts a public widget key and controls page eligibility and loading. Chat design, response hours and conversations remain in the Hamgoo service.

The navy/blue administration interface includes English, Persian, Arabic, Russian and Urdu. A plugin-only language choice supports RTL and LTR. The remote chat's language is controlled by the service.

Features include published-content search, public content types, URL paths, exclusions, optional WooCommerce contexts, device targeting, idle/delay/click loading, a consent API, a manual configuration check and opt-in settings cleanup.

## Development

Requires PHP 7.4+, Python 3.9+, Node.js and Composer for development. WordPress runtime requires WordPress 6.5+ and PHP 7.4+; no Composer, Python or Node installation is required on customer sites.

    composer install
    composer lint
    php tests/run.php
    node tests/loader.test.js
    python3 scripts/translations.py
    python3 scripts/verify.py
    php scripts/preview.php

Optional integration check, only in a separate disposable WordPress installation with this plugin active:

    wp eval-file tests/wordpress-smoke.php

This check writes temporary posts and plugin settings. Never run it on a live/customer site.

Static design previews are generated in docs/ for each language. They use local assets and save nothing; content search and live service tests require WordPress.

    python3 scripts/package.py --tag v1.0.0

The installable artifact is dist/hamgoo-live-chat-1.0.0.zip.

## Consent API

With consent-manager mode enabled, no remote widget is loaded until permission. The plugin does not implement a cookie banner or persist consent. Integrate the site's existing consent manager on each page.

    // Before the deferred local loader runs:
    window.HamgooLiveChatConsent = true;

    // After it runs:
    window.HamgooLiveChat.grantConsent();

    // Withdraw:
    window.HamgooLiveChatConsent = false;
    if (window.HamgooLiveChat) window.HamgooLiveChat.revokeConsent();
    // Reload without granting consent to stop a widget already running.

Alternatively, dispatch the window event hamgoo:consent with detail.granted equal to true or false. A blocked device still cannot load after permission. Consent does not override server-side page exclusions.

## Resources

* [Persian release guide](docs/release-fa.md)
* [Persian artwork brief](docs/artwork-fa.md)
* [WordPress listing and service disclosure](readme.txt)
* [Service](https://hamkaran.cloud/)
* [Customer support panel](https://cp.hamkaran.cloud/)

All source code and bundled SVG illustrations are GPL-2.0-or-later. Additional artwork must have a compatible license.
