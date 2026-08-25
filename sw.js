// Registrador básico de Service Worker
self.addEventListener('fetch', function(event) {
  // Permite que las peticiones se procesen con normalidad
  event.respondWith(fetch(event.request));
});