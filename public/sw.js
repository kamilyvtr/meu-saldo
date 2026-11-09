/*
 * Meu Saldo Service Worker
 * ------------------------
 * Privacy first: financial routes are ALWAYS network-only (NetworkOnly strategy).
 * Only static assets (HTML, CSS, JS, images, fonts, icons, manifest) may be cached.
 */

const STATIC_CACHE = 'meu-saldo-static-v3';

const PRECACHE_URLS = [
    '/favicon.svg',
    '/favicon-32.png',
    '/favicon.ico',
    '/apple-touch-icon.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
    '/manifest.webmanifest',
];

// Financial routes: NEVER cached, always fetched from the network.
const NETWORK_ONLY_ROUTES = [
    '/api',
    '/transactions',
    '/dashboard',
    '/cards',
    '/credit-cards',
    '/invoices',
];

function isFinancialRoute(pathname) {
    return NETWORK_ONLY_ROUTES.some((route) => {
        return (
            pathname === route ||
            pathname.startsWith(route + '/') ||
            pathname.startsWith(route)
        );
    });
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(STATIC_CACHE);

            await Promise.all(
                PRECACHE_URLS.map(async (url) => {
                    try {
                        await cache.add(new Request(url, { cache: 'reload' }));
                    } catch {
                        // Static asset unavailable: ignore.
                    }
                }),
            );

            await self.skipWaiting();
        })(),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();

            await Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE)
                    .map((key) => caches.delete(key)),
            );

            await self.clients.claim();
        })(),
    );
});

function isCacheableStaticAsset(url, request) {
    if (url.origin !== self.location.origin) {
        return false;
    }

    if (url.search !== '') {
        return false;
    }

    if (request.headers.has('range')) {
        return false;
    }

    // Financial routes are NetworkOnly: never cached.
    if (isFinancialRoute(url.pathname)) {
        return false;
    }

    // Inertia XHR payloads contain sensitive data: never cached.
    if (request.headers.get('X-Inertia') === 'true') {
        return false;
    }

    const pathname = url.pathname;

    // Build assets (CSS, JS, fonts, images).
    if (pathname.startsWith('/build/assets/')) {
        return /\.(?:html|css|js|mjs|woff2?|ttf|otf|png|jpe?g|webp|svg|ico|webmanifest)$/i.test(
            pathname,
        );
    }

    // Icons.
    if (pathname.startsWith('/icons/')) {
        return /\.(?:png|svg|webp|ico)$/i.test(pathname);
    }

    // HTML documents (non-financial) may be cached.
    if (request.destination === 'document') {
        return true;
    }

    // Static files and documents.
    if (
        /\.(?:html|css|js|mjs|png|jpe?g|webp|svg|ico|woff2?|ttf|otf|webmanifest)$/i.test(
            pathname,
        )
    ) {
        return true;
    }

    return /^\/(?:favicon\.svg|favicon-32\.png|favicon\.ico|apple-touch-icon\.png|manifest\.webmanifest)$/.test(
        pathname,
    );
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // NetworkOnly for financial routes.
    if (isFinancialRoute(url.pathname)) {
        return;
    }

    // Navigations that are Inertia XHR or non-static are network-only.
    if (request.headers.get('X-Inertia') === 'true') {
        return;
    }

    if (!isCacheableStaticAsset(url, request)) {
        return;
    }

    event.respondWith(
        (async () => {
            const cached = await caches.match(request);

            if (cached) {
                return cached;
            }

            const response = await fetch(request);

            if (response.ok && response.type === 'basic') {
                const cache = await caches.open(STATIC_CACHE);
                await cache.put(request, response.clone());
            }

            return response;
        })(),
    );
});
