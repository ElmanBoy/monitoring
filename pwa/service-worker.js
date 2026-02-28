const staticCacheName = 'cache-v1.6.0';
const dynamicCacheName = 'runtimeCache-v1.6.0';

// Assets to pre-cache
const precacheAssets = [
    '/',
    'js/pwa.js',
    'manifest.json',
    'fallback.html'
];

// Install Event: Caching static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(staticCacheName).then(cache => {
            return cache.addAll(precacheAssets);
        })
    );
});

// Activate Event: Clearing old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== staticCacheName && key !== dynamicCacheName)
                    .map(key => caches.delete(key))
            );
        })
    );
});

// Fetch Event: Responding to network requests
self.addEventListener('fetch', event => {
    if((event.request.url.indexOf('http') === 0)){
        return false;
    }
    event.respondWith(
        caches.match(event.request).then(cacheRes => {
            // Return cached response or fetch from network
            return cacheRes || fetch(event.request).then(fetchRes => {
                return caches.open(dynamicCacheName).then(cache => {
                    cache.put(event.request, fetchRes.clone());
                    return fetchRes;
                });
            });
        }).catch(() => {
            // Return fallback page when offline
            return caches.match('fallback.html');
        })
    );
});