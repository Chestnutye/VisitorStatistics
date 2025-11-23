(function () {
    // Configuration (CHANGE THIS to your actual collect.php URL)
    var endpoint = 'https://your-domain.com/analytics/collect.php';

    // Helper: Generate UUID v4
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // 1. Visitor ID (Persistent)
    var visitorId = localStorage.getItem('visitor_id');
    if (!visitorId) {
        visitorId = generateUUID();
        localStorage.setItem('visitor_id', visitorId);
    }

    // 2. Page View ID (Unique per load)
    var pageViewId = generateUUID();

    // 3. Time on Page Tracking
    var startTime = Date.now();

    function collect(type) {
        type = type || 'pageview';

        var data = {
            type: type,
            page_view_id: pageViewId,
            visitor_id: visitorId,
            url: window.location.href,
            referrer: document.referrer,
            screen: window.screen.width + 'x' + window.screen.height,
            viewport: window.innerWidth + 'x' + window.innerHeight, // Viewport Size
            language: navigator.language || navigator.userLanguage,
            platform: navigator.platform,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            device_memory: navigator.deviceMemory || 0,
            cpu_cores: navigator.hardwareConcurrency || 0,
            connection_type: (navigator.connection ? navigator.connection.effectiveType : ''),
            duration: Math.round((Date.now() - startTime) / 1000) // Duration in seconds
        };

        // Use sendBeacon if available (doesn't block unload)
        if (navigator.sendBeacon) {
            var blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
            navigator.sendBeacon(endpoint, blob);
        } else {
            // Fallback to fetch
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data),
                keepalive: true
            }).catch(function (e) {
                console.error('Tracker error:', e);
            });
        }
    }

    // Run after page load
    if (document.readyState === 'complete') {
        setTimeout(collect, 0);
    } else {
        window.addEventListener('load', function () {
            setTimeout(collect, 0);
        });
    }

    // Heartbeat / End of Session Tracking
    // Send update when user leaves or hides the page
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            collect('heartbeat');
        }
    });

    // Also capture pagehide for good measure (some browsers prefer this for unload)
    window.addEventListener('pagehide', function () {
        collect('heartbeat');
    });

})();
