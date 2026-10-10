/* Fast loader tests using Node's built-in VM and small browser doubles. */
'use strict';
const assert = require('assert').strict;
const vm = require('vm');
const fs = require('fs');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/loader.js'), 'utf8');
let assertions = 0;
function equal(actual, expected, label) {
  assert.equal(actual, expected, label);
  assertions++;
}
function browser(overrides) {
  const nodes = [];
  const events = {};
  const timers = [];
  const idle = [];
  const mediaEvents = {};
  const media = {
    matches: false,
    addEventListener: (name, handler) => { mediaEvents[name] = handler; }
  };
  function node(tag) {
    return {
      tagName: tag, id: '', src: '', disabled: false, textContent: '', parentNode: null,
      listeners: {},
      addEventListener(name, callback) { this.listeners[name] = callback; }
    };
  }
  const body = {
    appendChild(element) { element.parentNode = body; nodes.push(element); },
    removeChild(element) { nodes.splice(nodes.indexOf(element), 1); element.parentNode = null; }
  };
  const document = {
    readyState: 'complete', body,
    createElement: node,
    getElementById: id => nodes.find(item => item.id === id),
    getElementsByTagName: tag => nodes.filter(item => item.tagName === tag),
    dispatchEvent: event => { events['document:' + event.type] = event; }
  };
  const window = {
    HamgooLiveChatLoader: Object.assign({
      widgetKey: 'cw_' + 'A'.repeat(32), devices: 'all', mode: 'idle',
      delay: 3, requireConsent: false, label: 'Chat', loadingLabel: 'Loading', errorLabel: 'Retry'
    }, overrides),
    matchMedia: () => media,
    addEventListener: (name, callback) => { (events[name] || (events[name] = [])).push(callback); },
    setTimeout: (callback, ms) => { timers.push({ callback, ms }); },
    requestIdleCallback: callback => { idle.push(callback); }
  };
  function CustomEvent(type, options) { this.type = type; this.detail = options.detail; }
  const context = vm.createContext({ window, document, CustomEvent });
  return {
    window, document, nodes, events, timers, idle, media,
    run: () => vm.runInContext(source, context),
    flush: () => { while (idle.length) idle.shift()(); while (timers.length) timers.shift().callback(); },
    emit: (name, event) => (events[name] || []).forEach(callback => callback(event)),
    resize: value => { media.matches = value; if (mediaEvents.change) mediaEvents.change(); },
    scripts: () => nodes.filter(item => item.tagName === 'script'),
    button: () => nodes.find(item => item.tagName === 'button')
  };
}

let b = browser(); b.run();
equal(b.scripts().length, 0, 'No immediate remote request.');
b.flush();
equal(b.scripts().length, 1, 'Idle schedules exactly one script.');
equal(b.scripts()[0].src, 'https://cp.hamkaran.cloud/widget/chat.js', 'Fixed HTTPS script URL.');
equal(b.window.HamkaranChatWidget.widgetKey, 'cw_' + 'A'.repeat(32), 'Exact bootstrap contract.');
equal(b.scripts()[0].async, true, 'Async remote script.');
b.window.HamgooLiveChat.grantConsent(); b.run(); b.flush();
equal(b.scripts().length, 1, 'Pending requests and repeated loader execution do not duplicate.');
b.scripts()[0].onload();
equal(b.window.HamgooLiveChat.getState().loaded, true, 'Completion is recorded.');

b = browser({ requireConsent: true }); b.run(); b.flush();
equal(b.scripts().length, 0, 'Consent blocks remote requests.');
equal(b.button(), undefined, 'Consent blocks launcher.');
b.window.HamgooLiveChat.grantConsent(); b.window.HamgooLiveChat.revokeConsent(); b.flush();
equal(b.scripts().length, 0, 'Withdrawal prevents scheduled request.');
b.window.HamgooLiveChat.grantConsent(); b.flush();
equal(b.scripts().length, 1, 'Consent grant releases loader.');

b = browser({ requireConsent: true }); b.window.HamgooLiveChatConsent = true; b.run(); b.flush();
equal(b.scripts().length, 1, 'Preloader consent is respected.');
b = browser({ requireConsent: true }); b.run();
b.emit('hamgoo:consent', { detail: { granted: true } }); b.flush();
equal(b.scripts().length, 1, 'Consent window event works.');
b = browser({ requireConsent: true, mode: 'click' }); b.run();
b.window.HamgooLiveChat.grantConsent();
equal(Boolean(b.button()), true, 'Click launcher waits for consent.');
b.window.HamgooLiveChat.revokeConsent();
equal(b.button(), undefined, 'Withdrawal removes local button.');

b = browser({ mode: 'click' }); b.run(); b.flush();
equal(b.scripts().length, 0, 'Click mode loads no widget before click.');
equal(b.button().textContent, 'Chat', 'Local translated button.');
b.button().listeners.click();
equal(b.scripts().length, 1, 'Click requests widget.');
equal(b.button().disabled, true, 'Pending button disabled.');
b.scripts()[0].onerror();
equal(b.scripts().length, 0, 'Failed script removed for retry.');
equal(b.button().textContent, 'Retry', 'Visible retry message.');
equal(b.button().disabled, false, 'Retry enabled.');
b.button().listeners.click(); b.scripts()[0].onload();
equal(b.button(), undefined, 'Successful widget replaces local button.');

b = browser({ mode: 'delay', delay: 6 }); b.run();
equal(b.timers[0].ms, 6000, 'Configured delay.');
equal(b.scripts().length, 0, 'No premature delayed request.');
b.flush(); equal(b.scripts().length, 1, 'Delayed request.');
b = browser(); b.document.readyState = 'loading'; b.run(); b.flush();
equal(b.scripts().length, 0, 'Wait for window load.');
b.document.readyState = 'complete'; b.emit('load'); b.flush();
equal(b.scripts().length, 1, 'Start after window load.');
b = browser(); delete b.window.requestIdleCallback; b.run();
equal(b.timers[0].ms, 800, 'Idle API fallback.');

b = browser({ devices: 'mobile' }); b.run(); b.flush();
equal(b.scripts().length, 0, 'Mobile-only does not connect on desktop.');
b.resize(true); b.flush();
equal(b.scripts().length, 1, 'Eligible resize starts widget.');
b = browser({ devices: 'desktop', mode: 'click' }); b.media.matches = true; b.run();
equal(b.button(), undefined, 'Desktop-only has no mobile launcher.');
b.resize(false); equal(Boolean(b.button()), true, 'Desktop launcher on matching viewport.');
b.resize(true); equal(b.button(), undefined, 'Launcher removed on ineligible resize.');

b = browser(); b.window.__HamkaranChatWidgetLoaded = true; b.run(); b.flush();
equal(b.scripts().length, 0, 'Existing widget flag respected.');
b = browser(); b.document.body.appendChild(Object.assign(b.document.createElement('script'), { src: 'https://cp.hamkaran.cloud/widget/chat.js?version=1' }));
b.run(); b.flush(); equal(b.scripts().length, 1, 'Existing external installation respected.');
b = browser({ widgetKey: '<script>' }); b.run(); b.flush();
equal(b.scripts().length, 0, 'Invalid key cannot start loader.');
b = browser(); delete b.window.HamgooLiveChatLoader; b.run();
equal(b.scripts().length, 0, 'Missing config is harmless.');
console.log('PASS: ' + assertions + ' loader assertions');
