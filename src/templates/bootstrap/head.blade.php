<script id="saola-context-bootstrap" defer src="{{ $bootstrap['url'] }}"></script>
<script>
    (() => {
        const initial = document.getElementById('saola-context-bootstrap');
        const source = initial.src;
        const waiters = new WeakMap();

        function waitFor(script) {
            return new Promise(resolve => {
                const timer = setTimeout(() => finish(null), 5000);
                const failed = () => finish(null);
                function finish(payload) {
                    clearTimeout(timer);
                    waiters.delete(script);
                    script.removeEventListener('load', failed);
                    script.removeEventListener('error', failed);
                    resolve(payload);
                }
                waiters.set(script, finish);
                script.addEventListener('load', failed);
                script.addEventListener('error', failed);
            });
        }

        window.SaolaBootstrap = {
            ready: waitFor(initial),
            payload: null,
            register(script, payload) {
                waiters.get(script)?.(payload);
            },
            async reload(revision) {
                const script = document.createElement('script');
                const url = new URL(source);
                url.searchParams.set('revision', revision);
                script.src = url.href;
                const ready = waitFor(script);
                document.head.append(script);
                const payload = await ready;
                script.remove();
                return payload;
            },
        };
    })();
</script>
