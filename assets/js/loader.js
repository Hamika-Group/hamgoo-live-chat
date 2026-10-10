/* Hamgoo Live Chat — local, consent-aware, duplicate-safe loader. GPL-2.0-or-later. */
(function () {
  'use strict';
  var config = window.HamgooLiveChatLoader;
  if (!config || window.__HamgooWordPressLoader || !/^cw_[A-Za-z0-9]{32}$/.test(config.widgetKey || '')) return;
  window.__HamgooWordPressLoader = true;

  var consent = !config.requireConsent || window.HamgooLiveChatConsent === true;
  var loaded = false;
  var pending = false;
  var scheduled = false;
  var launcher = null;
  var media = window.matchMedia('(max-width: 767px)');
  var widgetUrl = 'https://cp.hamkaran.cloud/widget/chat.js';

  function eligible() {
    return consent && (config.devices === 'all' || (config.devices === 'mobile' && media.matches) || (config.devices === 'desktop' && !media.matches));
  }

  function removeLauncher() {
    if (launcher && launcher.parentNode) launcher.parentNode.removeChild(launcher);
    launcher = null;
  }

  function existingWidget() {
    if (window.__HamkaranChatWidgetLoaded || document.getElementById('hamkaran-chat-widget-frame')) return true;
    var scripts = document.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
      if ((scripts[i].src || '').split('?')[0] === widgetUrl) return true;
    }
    return false;
  }

  function load() {
    if (!eligible() || loaded || pending) return;
    if (existingWidget()) {
      loaded = true;
      removeLauncher();
      return;
    }
    pending = true;
    if (launcher) {
      launcher.disabled = true;
      launcher.textContent = config.loadingLabel;
    }
    window.HamkaranChatWidget = { widgetKey: config.widgetKey };
    var script = document.createElement('script');
    script.id = 'hamgoo-remote-widget';
    script.src = widgetUrl;
    script.async = true;
    script.onload = function () {
      loaded = true;
      pending = false;
      removeLauncher();
    };
    script.onerror = function () {
      pending = false;
      if (script.parentNode) script.parentNode.removeChild(script);
      if (launcher) {
        launcher.disabled = false;
        launcher.textContent = config.errorLabel;
      }
      document.dispatchEvent(new CustomEvent('hamgoo:error', { detail: { reason: 'network' } }));
    };
    document.body.appendChild(script);
  }

  function button() {
    if (launcher || !eligible() || loaded || pending) return;
    if (existingWidget()) {
      loaded = true;
      return;
    }
    launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'hamgoo-launcher';
    launcher.id = 'hamgoo-local-launcher';
    launcher.textContent = config.label;
    launcher.addEventListener('click', load);
    document.body.appendChild(launcher);
  }

  function schedule() {
    if (!eligible() || loaded || pending || scheduled) return;
    if (config.mode === 'click') {
      button();
      return;
    }
    scheduled = true;
    var run = function () {
      scheduled = false;
      load();
    };
    if (config.mode === 'delay') {
      window.setTimeout(run, Math.max(0, Math.min(120, Number(config.delay) || 0)) * 1000);
    } else if (window.requestIdleCallback) {
      window.requestIdleCallback(run, { timeout: 2000 });
    } else {
      window.setTimeout(run, 800);
    }
  }

  function start() {
    if (document.readyState === 'complete') schedule();
    else window.addEventListener('load', schedule, { once: true });
  }

  window.HamgooLiveChat = {
    grantConsent: function () {
      consent = true;
      start();
    },
    revokeConsent: function () {
      consent = false;
      removeLauncher();
    },
    getState: function () {
      return { consent: consent, loaded: loaded, loading: pending, eligible: eligible() };
    }
  };
  // This flag also allows cookie managers that run before the deferred loader.
  window.addEventListener('hamgoo:consent', function (event) {
    if (event.detail && event.detail.granted === true) window.HamgooLiveChat.grantConsent();
    else window.HamgooLiveChat.revokeConsent();
  });
  var onResize = function () {
    if (!eligible()) removeLauncher();
    else start();
  };
  if (media.addEventListener) media.addEventListener('change', onResize);
  else media.addListener(onResize);
  start();
})();
