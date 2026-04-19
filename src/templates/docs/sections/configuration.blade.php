<!-- Configuration -->
<section id="configuration" class="mb-5">
    <h2>Configuration</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">API Reference</span>
        <span class="breadcrumb-item">Configuration</span>
    </div>

    <h3>APP_CONFIGS Structure</h3>
    <div class="example-container">
        <div class="example-header">Complete Configuration</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">window.APP_CONFIGS = {
    // Container selector
    container: '#app-root',
    
    // Environment
    env: {
        mode: 'web',
        debug: true,
        base_url: '/',
        csrf_token: '...',
        router_mode: 'history'
    },
    
    // Router configuration
    router: {
        mode: 'history', // or 'hash'
        base: '/',
        routes: [
            { path: '/', view: 'web.pages.home' },
            { path: '/about', view: 'web.pages.about' }
        ],
        beforeEach: function(to, from) { return true; },
        afterEach: function(to, from) {}
    },
    
    // View configuration
    view: {
        superView: 'web.layouts.base',
        ssrData: { /* SSR view data */ }
    },
    
    // API configuration
    api: {
        baseUrl: '/api',
        csrfToken: '...',
        timeout: 10000,
        defaultHeaders: {
            'Content-Type': 'application/json'
        }
    },
    
    // Application scope
    appScope: 'web',
    
    // Default route
    defaultRoute: '/',
    
    // Global data
    data: {
        siteName: 'One Laravel',
        version: '1.0.0'
    },
    
    // Language configuration
    lang: {
        locale: 'en',
        fallback: 'en'
    },
    
    // Custom initialization
    onInit: function(App, config) {
        // Custom setup
    }
};</code></pre>
        </div>
    </div>

    <h3>Configuration Options</h3>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Option</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>container</code></td>
                    <td>string|HTMLElement</td>
                    <td>Container selector or element</td>
                </tr>
                <tr>
                    <td><code>router.mode</code></td>
                    <td>string</td>
                    <td>'history' or 'hash'</td>
                </tr>
                <tr>
                    <td><code>router.routes</code></td>
                    <td>Array</td>
                    <td>Array of route definitions</td>
                </tr>
                <tr>
                    <td><code>view.superView</code></td>
                    <td>string</td>
                    <td>Default layout view</td>
                </tr>
                <tr>
                    <td><code>api.baseUrl</code></td>
                    <td>string</td>
                    <td>API base URL</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

