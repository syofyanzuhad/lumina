(function () {
  'use strict';

  var scriptEl = document.currentScript || document.querySelector('script[data-domain]');

  if (!scriptEl) {
    return;
  }

  var domain = scriptEl.getAttribute('data-domain');
  var apiEndpoint = scriptEl.getAttribute('data-api');

  if (!apiEndpoint) {
    try {
      apiEndpoint = new URL('/api/collect', scriptEl.src).href;
    } catch (e) {
      apiEndpoint = '/api/collect';
    }
  }

  if (!domain) {
    return;
  }

  var excludePattern = scriptEl.getAttribute('data-exclude');

  function isExcluded() {
    try {
      if (window.localStorage && window.localStorage.getItem('lumina_ignore') === 'true') {
        return true;
      }
    } catch (e) {}

    if (excludePattern) {
      var currentPath = window.location.pathname;
      var patterns = excludePattern.split(',');

      for (var i = 0; i < patterns.length; i++) {
        var p = patterns[i].trim();

        if (!p) {
          continue;
        }

        if (p.indexOf('*') !== -1) {
          var regex = new RegExp('^' + p.replace(/[-[\]{}()+\/.,\\^$|#\s]/g, '\\$&').replace(/\*/g, '.*') + '$');

          if (regex.test(currentPath)) {
            return true;
          }
        } else if (currentPath === p || currentPath.indexOf(p) === 0) {
          return true;
        }
      }
    }

    return false;
  }

  // Privacy-first identity: opaque random IDs kept in localStorage /
  // sessionStorage. No cookies are set, so no consent banner is required.
  function generateId(prefix) {
    if (window.crypto && window.crypto.randomUUID) {
      return window.crypto.randomUUID();
    }

    return prefix + Math.random().toString(36).slice(2) + Date.now().toString(36);
  }

  function getVisitorId() {
    try {
      var id = window.localStorage.getItem('lumina_visitor_id');

      if (!id) {
        id = generateId('v_');
        window.localStorage.setItem('lumina_visitor_id', id);
      }

      return id;
    } catch (e) {
      return null;
    }
  }

  function getSessionId() {
    try {
      var now = Date.now();
      var id = window.sessionStorage.getItem('lumina_session_id');
      var lastSeen = parseInt(window.sessionStorage.getItem('lumina_session_ts') || '0', 10);

      // New session after 30 minutes of inactivity or on a fresh tab.
      if (!id || now - lastSeen > 30 * 60 * 1000) {
        id = generateId('s_');
      }

      window.sessionStorage.setItem('lumina_session_id', id);
      window.sessionStorage.setItem('lumina_session_ts', String(now));

      return id;
    } catch (e) {
      return null;
    }
  }

  function buildEndpoint() {
    var visitorId = getVisitorId();
    var sessionId = getSessionId();
    var sep = apiEndpoint.indexOf('?') === -1 ? '?' : '&';
    var qs = 'visitor=' + encodeURIComponent(visitorId || '') + '&session=' + encodeURIComponent(sessionId || '');

    return apiEndpoint + sep + qs;
  }

  var lastPath = '';

  function sendEvent(eventName, props) {
    if (isExcluded()) {
      return;
    }

    try {
      var currentPath = window.location.pathname + window.location.search;
      var payload = {
        domain: domain,
        path: currentPath,
        referrer: document.referrer || null,
        screen_width: window.innerWidth || null,
        name: eventName || null,
        metadata: props || null
      };

      var data = JSON.stringify(payload);
      var url = buildEndpoint();

      if (navigator.sendBeacon) {
        navigator.sendBeacon(url, new Blob([data], { type: 'application/json' }));
      } else if (window.fetch) {
        fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: data,
          keepalive: true
        }).catch(function () {});
      }
    } catch (e) {}
  }

  function trackPageview() {
    var currentPath = window.location.pathname + window.location.search;

    if (currentPath === lastPath) {
      return;
    }

    lastPath = currentPath;
    sendEvent(null, null);
  }

  function wrapHistory(type) {
    var orig = history[type];

    return function () {
      var rv = orig.apply(this, arguments);
      trackPageview();

      return rv;
    };
  }

  history.pushState = wrapHistory('pushState');
  history.replaceState = wrapHistory('replaceState');
  window.addEventListener('popstate', trackPageview);
  document.addEventListener('inertia:navigate', trackPageview);

  var queue = (window.lumina && window.lumina.q) || [];
  window.lumina = function (eventName, props) {
    sendEvent(eventName, props);
  };

  for (var i = 0; i < queue.length; i++) {
    window.lumina.apply(null, queue[i]);
  }

  trackPageview();
})();
