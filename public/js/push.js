/* UniHup PWA + web push bootstrap.
 * Registers the service worker and exposes window.UniHupPush for the
 * "Notifications" control in the profile page. Safe to load on every page;
 * does nothing unless the browser supports service workers + push. */
(function () {
  'use strict';

  var SW_URL = '/sw.js';
  var supported =
    'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

  var keyMeta = document.querySelector('meta[name="webpush-public-key"]');
  var vapidKey = keyMeta ? keyMeta.getAttribute('content') : '';

  var readyReg = null;

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; ++i) output[i] = raw.charCodeAt(i);
    return output;
  }

  function csrfHeaders() {
    var headers = { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) headers['X-CSRF-TOKEN'] = meta.getAttribute('content');
    var m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    if (m) headers['X-XSRF-TOKEN'] = decodeURIComponent(m[1]);
    return headers;
  }

  function register() {
    if (!('serviceWorker' in navigator)) return Promise.reject('unsupported');
    return navigator.serviceWorker
      .register(SW_URL)
      .then(function () {
        return navigator.serviceWorker.ready;
      })
      .then(function (reg) {
        readyReg = reg;
        return reg;
      });
  }

  function currentState() {
    if (!supported) return Promise.resolve('unsupported');
    if (!vapidKey) return Promise.resolve('unconfigured');
    return (readyReg ? Promise.resolve(readyReg) : register())
      .then(function (reg) {
        return reg.pushManager.getSubscription();
      })
      .then(function (sub) {
        if (sub) return 'subscribed';
        return Notification.permission === 'denied' ? 'denied' : 'unsubscribed';
      })
      .catch(function () {
        return 'error';
      });
  }

  function enable() {
    if (!supported) return Promise.reject('unsupported');
    if (!vapidKey) return Promise.reject('unconfigured');

    return register()
      .then(function (reg) {
        return Notification.requestPermission().then(function (perm) {
          if (perm !== 'granted') throw 'denied';
          return reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
          });
        });
      })
      .then(function (sub) {
        var json = sub.toJSON();
        return fetch('/push/subscription', {
          method: 'POST',
          credentials: 'same-origin',
          headers: csrfHeaders(),
          body: JSON.stringify({
            endpoint: sub.endpoint,
            keys: json.keys,
            contentEncoding:
              (window.PushManager && PushManager.supportedContentEncodings &&
                PushManager.supportedContentEncodings[0]) || null,
          }),
        });
      })
      .then(function () {
        return 'subscribed';
      });
  }

  function disable() {
    if (!supported) return Promise.resolve('unsupported');
    return navigator.serviceWorker.ready
      .then(function (reg) {
        return reg.pushManager.getSubscription();
      })
      .then(function (sub) {
        if (!sub) return null;
        var endpoint = sub.endpoint;
        return sub.unsubscribe().then(function () {
          return fetch('/push/subscription', {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: csrfHeaders(),
            body: JSON.stringify({ endpoint: endpoint }),
          });
        });
      })
      .then(function () {
        return 'unsubscribed';
      });
  }

  window.UniHupPush = { supported: supported, state: currentState, enable: enable, disable: disable };

  if (supported) {
    if (document.readyState === 'complete') register().catch(function () {});
    else window.addEventListener('load', function () { register().catch(function () {}); });
  }
})();
