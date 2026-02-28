const staticCacheName = 'cache-v1.6.1';
const dynamicCacheName = 'runtimeCache-v1.6.1';

// Assets to pre-cache
const precacheAssets = [
    '/fonts/MaterialIcons-Regular.woff2',
    'manifest.json',
];

// Install Event: Caching static assets
/*self.addEventListener('install', event => {
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
});*/

self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    return caches.delete(cacheName);
                })
            );
        })
    );
});

// Fetch Event: Responding to network requests
self.addEventListener('fetch', event => {
    // Пропускаем не-GET запросы
    if (event.request.method !== 'GET') return;

    // Пропускаем запросы с заголовком range (для видео/аудио)
    if (event.request.headers.has('range')) {
        return;
    }

    // Ограничиваем домены
    if (!event.request.url.startsWith(self.location.origin) &&
        !event.request.url.includes('monitoring.msr.mosreg.ru')) {
        return;
    }

    // Обрабатываем запрос
    event.respondWith(
        (async () => {
            try {
                // Сначала пробуем загрузить из сети с no-store
                const networkResponse = await fetch(event.request, {
                    cache: 'no-store',
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                });

                // Если успешно, кэшируем в dynamic cache
                if (networkResponse.ok) {
                    const cache = await caches.open(dynamicCacheName);
                    await cache.put(event.request, networkResponse.clone());
                }

                return networkResponse;
            } catch (error) {
                // Если сеть не доступна, пробуем кэш
                const cachedResponse = await caches.match(event.request);
                if (cachedResponse) {
                    return cachedResponse;
                }

                // Если в кэше нет, возвращаем fallback
                return caches.match('/');
            }
        })()
    );
});

self.addEventListener('message', async (event) => {
    if (event.data.type === 'SHOW_NOTIFICATION') {
        const { title, options } = event.data;
console.log(options);
        // Показываем уведомление с действиями
        await self.registration.showNotification(title, {
            body: options.body,
            icon: options.icon || '/favicons/favicon-96x96.png',
            url: options.url,
            vibrate: [200, 100, 200],
            silent: false,
            requireInteraction: true,
            tag: 'monitoring',
            actions: [
                {
                    action: 'accept',
                    title: '✅ Принять'
                },
                {
                    action: 'reject',
                    title: '❌ Отклонить'
                }
            ],
            data: options.data || {} // Передаем дополнительные данные
        });
    }
});

// Обработка push событий
self.addEventListener('push', (event) => {
    //if (!event.data) return;

    const payload = event.data.json();

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: payload.icon || '/favicons/favicon-96x96.png',
            badge: '/favicons/favicon-192x192.png',
            timestamp: payload.timestamp,
            silent: false,
            vibrate: [200, 100, 200],
            requireInteraction: true,
            tag: 'monitoring',
            data: payload.data
        })
    );
});

// Обработка кликов по действиям
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const action = event.action;
    const notificationData = event.notification.data;

    if (action === 'accept') {
        // Отправляем сообщение обратно в основное приложение
        clients.matchAll().then((clientList) => {
            if (clientList.length === 0) {
                // Создаем новое окно если нет открытых вкладок
                clients.openWindow('/').then((windowClient) => {
                    // Ждем пока окно загрузится и отправляем сообщение
                    windowClient.postMessage({
                        type: 'NOTIFICATION_ACTION',
                        action: action,
                        data: notificationData
                    });
                });
            } else {
                // Используем существующую вкладку
                clientList[0].focus(); // Фокусируем вкладку
                clientList[0].postMessage({
                    type: 'NOTIFICATION_ACTION',
                    action: action,
                    data: notificationData
                });
            }
        });
    } else if (action === 'reject') {
        clients.matchAll().then((clientList) => {
            if (clientList.length > 0) {
                clientList[0].postMessage({
                    type: 'NOTIFICATION_ACTION',
                    action: 'reject',
                    data: notificationData
                });
            }
        });
    } else {
        // Клик по самому уведомлению (не по кнопке)
        clients.openWindow(notificationData.url || '/');
    }
});