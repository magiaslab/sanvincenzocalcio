// Aggiungi meta tags PWA dinamicamente
(function() {
    const head = document.head || document.getElementsByTagName('head')[0];
    
    // Manifest
    if (!document.querySelector('link[rel="manifest"]')) {
        const manifestLink = document.createElement('link');
        manifestLink.rel = 'manifest';
        manifestLink.href = '/manifest.json';
        head.appendChild(manifestLink);
    }
    
    // Theme Color
    if (!document.querySelector('meta[name="theme-color"]')) {
        const themeColor = document.createElement('meta');
        themeColor.name = 'theme-color';
        themeColor.content = '#f59e0b';
        head.appendChild(themeColor);
    }
    
    // Apple Meta Tags
    const appleTags = [
        { name: 'apple-mobile-web-app-capable', content: 'yes' },
        { name: 'apple-mobile-web-app-status-bar-style', content: 'default' },
        { name: 'apple-mobile-web-app-title', content: 'San Vincenzo Calcio' },
        { name: 'mobile-web-app-capable', content: 'yes' },
    ];
    
    appleTags.forEach(tag => {
        if (!document.querySelector(`meta[name="${tag.name}"]`)) {
            const meta = document.createElement('meta');
            meta.name = tag.name;
            meta.content = tag.content;
            head.appendChild(meta);
        }
    });
})();

// Registrazione Service Worker per PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('✅ Service Worker registrato con successo:', registration.scope);
                
                // Controlla aggiornamenti ogni ora
                setInterval(() => {
                    registration.update();
                }, 3600000); // 1 ora
            })
            .catch((error) => {
                console.error('❌ Errore nella registrazione del Service Worker:', error);
            });
        
        // Gestione aggiornamenti
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            window.location.reload();
        });
    });
}

// Gestione installazione PWA
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    // Previeni il prompt automatico
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostra un banner personalizzato per l'installazione (opzionale)
    console.log('PWA può essere installata');
    
    // Puoi aggiungere qui un banner personalizzato
    // showInstallBanner();
});

// Funzione per mostrare il prompt di installazione (opzionale)
window.showInstallPrompt = () => {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('Utente ha accettato l\'installazione');
            } else {
                console.log('Utente ha rifiutato l\'installazione');
            }
            deferredPrompt = null;
        });
    }
};
