/* UniHup service worker — web push + a minimal offline fallback.
 * Deliberately does NOT cache app assets (the panel is authenticated and
 * changes often); it only gives navigations a friendly offline page. */

const OFFLINE_URL = '/offline.html';
const CACHE = 'unihup-offline-v1';

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.add(OFFLINE_URL)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.mode !== 'navigate') return;

  event.respondWith(
    fetch(request).catch(() => caches.match(OFFLINE_URL))
  );
});

self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = { title: 'UniHup', body: event.data && event.data.text() };
  }

  const title = data.title || 'UniHup';
  const options = {
    body: data.body || '',
    icon: data.icon || '/icons/unihup-icon.svg',
    badge: data.badge || '/icons/unihup-icon.svg',
    tag: data.tag || 'unihup',
    data: { url: data.url || '/' },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      for (const client of clients) {
        if (client.url.includes(url) && 'focus' in client) return client.focus();
      }
      if (self.clients.openWindow) return self.clients.openWindow(url);
    })
  );
});
