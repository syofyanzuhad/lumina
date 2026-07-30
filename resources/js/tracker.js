(function () {
  'use strict';

  var scriptEl = document.currentScript || document.querySelector('script[data-domain]');
  if (!scriptEl) return;

  var domain = scriptEl.getAttribute('data-domain');
  var apiEndpoint = scriptEl.getAttribute('data-api') || '/api/collect';
  if (!domain) return;

  var lastPath = '';

  function sendEvent(eventName, props) {
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

      if (navigator.sendBeacon) {
        navigator.sendBeacon(apiEndpoint, new Blob([data], { type: 'application/json' }));
      } else if (window.fetch) {
        fetch(apiEndpoint, {
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
    if (currentPath === lastPath) return;
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
