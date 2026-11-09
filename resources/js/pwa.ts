if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .catch((error) => {
                // PWA support is progressive; the app must keep working without it.
                console.warn(
                    'Meu Saldo: service worker registration failed.',
                    error,
                );
            });
    });
}
