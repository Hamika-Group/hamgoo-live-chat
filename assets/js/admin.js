/* Administration UI. No remote requests until the explicit configuration check. */
(function () {
  'use strict';
  var root = document.querySelector('.hg-app');
  if (!root) return;
  var settings = window.hamgooAdmin;
  var form = root.querySelector('#hg-settings-form');
  var tabs = Array.from(root.querySelectorAll('.hg-tab'));
  var sections = Array.from(root.querySelectorAll('.hg-section'));
  var dirty = false;
  var status = root.querySelector('#hg-save-status');

  root.classList.add('has-js');
  root.querySelector('.hg-tabs').setAttribute('role', 'tablist');
  tabs.forEach(function (tab) { tab.setAttribute('role', 'tab'); });
  sections.forEach(function (section) { section.setAttribute('role', 'tabpanel'); });
  function activate(name) {
    if (!tabs.some(function (tab) { return tab.dataset.tab === name; })) name = 'connection';
    tabs.forEach(function (tab) {
      var active = tab.dataset.tab === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.setAttribute('tabindex', active ? '0' : '-1');
    });
    sections.forEach(function (section) { section.hidden = section.id !== 'hg-section-' + name; });
  }
  tabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () {
      activate(tab.dataset.tab);
      window.history.replaceState(null, '', '#hg-' + tab.dataset.tab);
    });
    tab.addEventListener('keydown', function (event) {
      var next = index;
      var rtl = root.getAttribute('dir') === 'rtl';
      if (event.key === 'ArrowRight') next += rtl ? -1 : 1;
      else if (event.key === 'ArrowLeft') next += rtl ? 1 : -1;
      else if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = tabs.length - 1;
      else return;
      event.preventDefault();
      next = (next + tabs.length) % tabs.length;
      tabs[next].focus();
      tabs[next].click();
    });
  });
  activate(window.location.hash.replace('#hg-', ''));

  function scope() {
    var selected = root.querySelector('input[name="hamgoo_live_chat_settings[mode]"]:checked');
    root.querySelector('#hg-inclusions').hidden = !selected || selected.value !== 'selected';
    var mode = root.querySelector('input[name="hamgoo_live_chat_settings[load_mode]"]:checked');
    root.querySelector('#hg-delay').disabled = !mode || mode.value !== 'delay';
  }
  function changed() {
    dirty = true;
    status.textContent = settings.unsaved;
    status.classList.add('is-dirty');
    scope();
  }
  form.addEventListener('input', changed);
  form.addEventListener('change', changed);
  form.addEventListener('submit', function () {
    dirty = false;
    // Disabled presentation controls must still submit their current saved value.
    root.querySelector('#hg-delay').disabled = false;
  });
  window.addEventListener('beforeunload', function (event) {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });
  scope();

  root.querySelectorAll('.hg-picker').forEach(function (picker) {
    var input = picker.querySelector('input[role="searchbox"]');
    var results = picker.querySelector('.hg-search-results');
    var chips = picker.querySelector('.hg-chips');
    var timer;
    var abort;
    var requestId = 0;
    function remove(event) {
      var target = event.target.closest('.hg-chip button');
      if (!target) return;
      target.closest('.hg-chip').remove();
      changed();
    }
    chips.addEventListener('click', remove);
    input.addEventListener('input', function () {
      clearTimeout(timer);
      requestId++;
      if (abort) abort.abort();
      results.replaceChildren();
      if (Array.from(input.value.trim()).length < 2) return;
      var currentId = requestId;
      timer = setTimeout(function () {
        abort = new AbortController();
        var url = new URL(settings.ajaxUrl, window.location.href);
        url.searchParams.set('action', 'hamgoo_search_content');
        url.searchParams.set('nonce', settings.nonce);
        url.searchParams.set('term', input.value.trim());
        fetch(url.toString(), { credentials: 'same-origin', signal: abort.signal })
          .then(function (response) { if (!response.ok) throw new Error('search'); return response.json(); })
          .then(function (response) {
            if (currentId !== requestId) return;
            results.replaceChildren();
            if (!response.success || !Array.isArray(response.data)) throw new Error('search');
            if (!response.data.length) results.textContent = settings.noResults;
            response.data.forEach(function (item) {
              var button = document.createElement('button');
              button.type = 'button';
              button.textContent = item.title + ' · ' + item.type + ' #' + item.id;
              button.addEventListener('click', function () {
                if (!Array.from(chips.children).some(function (chip) { return chip.dataset.id === String(item.id); })) {
                  var chip = document.createElement('span');
                  chip.className = 'hg-chip';
                  chip.dataset.id = String(item.id);
                  var title = document.createElement('span');
                  title.textContent = item.title || '#' + item.id;
                  var hidden = document.createElement('input');
                  hidden.type = 'hidden';
                  hidden.name = picker.dataset.field + '[]';
                  hidden.value = String(item.id);
                  var close = document.createElement('button');
                  close.type = 'button';
                  close.textContent = '×';
                  close.setAttribute('aria-label', settings.remove + ': ' + title.textContent);
                  chip.append(title, hidden, close);
                  chips.appendChild(chip);
                  changed();
                }
                results.replaceChildren();
                input.value = '';
                input.focus();
              });
              results.appendChild(button);
            });
          })
          .catch(function (error) {
            if (error.name !== 'AbortError' && currentId === requestId) results.textContent = settings.searchError;
          });
      }, 300);
    });
  });

  var test = root.querySelector('#hg-test');
  test.addEventListener('click', function () {
    if (!/^cw_[A-Za-z0-9]{32}$/.test(settings.widgetKey || '')) return;
    var result = root.querySelector('#hg-test-result');
    test.disabled = true;
    result.textContent = settings.checking;
    result.className = '';
    var controller = new AbortController();
    var timeout = setTimeout(function () { controller.abort(); }, 12000);
    fetch('https://api.hamkaran.cloud/api/widget/' + encodeURIComponent(settings.widgetKey) + '/config', {
      method: 'GET', credentials: 'omit', headers: { Accept: 'application/json' }, signal: controller.signal
    }).then(function (response) {
      if (response.status === 403) throw new Error(settings.blocked);
      if (response.status === 404) throw new Error(settings.missing);
      if (!response.ok) throw new Error(settings.networkError);
      return response.json();
    }).then(function (data) {
      if (data.widget_key !== settings.widgetKey) throw new Error(settings.networkError);
      result.textContent = settings.connected;
      result.className = 'hg-test-success';
    }).catch(function (error) {
      result.textContent = error.name === 'AbortError' || error instanceof TypeError ? settings.networkError : error.message;
      result.className = 'hg-test-error';
    }).finally(function () {
      clearTimeout(timeout);
      test.disabled = false;
    });
  });
})();
