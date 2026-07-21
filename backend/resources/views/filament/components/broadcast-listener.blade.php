@auth
<script>
document.addEventListener('DOMContentLoaded', () => {
    // If Reverb keys are present, initialize Echo
    const reverbKey = "{{ env('REVERB_APP_KEY') }}";
    const reverbHost = "{{ env('REVERB_HOST', 'localhost') }}";
    const reverbPort = "{{ env('REVERB_PORT', 8080) }}";
    const reverbScheme = "{{ env('REVERB_SCHEME', 'http') }}";
    const adminPath = "{{ env('FILAMENT_PATH', 'admin') }}";

    if (!reverbKey) return;

    // Load Pusher
    const loadScript = (src) => {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    };

    Promise.all([
        loadScript('https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js'),
        loadScript('https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js')
    ]).then(() => {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: parseInt(reverbPort),
            wssPort: parseInt(reverbPort),
            forceTLS: reverbScheme === 'https',
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
        });

        window.Echo.private('admin.orders')
            .listen('OrderPaid', (e) => {
                const message = `Nowe opłacone zamówienie ${e.number} od ${e.customer_name} na kwotę ${(e.total_amount / 100).toFixed(2)} ${e.currency}`;
                
                try {
                    // Try the official FilamentNotification JS class if defined
                    if (typeof FilamentNotification !== 'undefined') {
                        new FilamentNotification()
                            .title('Opłacono zamówienie!')
                            .body(message)
                            .success()
                            .duration(15000)
                            .actions([
                                {
                                    name: 'view',
                                    label: 'Zobacz',
                                    url: `/${adminPath}/orders/${e.order_id}`,
                                    color: 'primary'
                                }
                            ])
                            .send();
                        return;
                    }
                } catch (err) {
                    console.error('FilamentNotification helper error, falling back to dispatchEvent', err);
                }

                // Fallback to direct event dispatch
                window.dispatchEvent(new CustomEvent('filament-notification-sent', {
                    detail: {
                        notification: {
                            id: 'order-paid-' + e.order_id + '-' + Date.now(),
                            title: 'Opłacono zamówienie!',
                            body: message,
                            status: 'success',
                            duration: 15000,
                            icon: 'heroicon-o-check-circle',
                            iconColor: 'success',
                            actions: [
                                {
                                    name: 'view',
                                    label: 'Zobacz',
                                    url: `/${adminPath}/orders/${e.order_id}`,
                                    color: 'primary',
                                    shouldClose: true
                                }
                            ]
                        }
                    }
                }));
            });
    }).catch(err => {
        console.error('Failed to load WebSocket client libraries', err);
    });
});
</script>
@endauth
