<!-- SPA Routing -->
<section id="spa-routing" class="mb-5">
    <h2>SPA Routing</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Advanced Topics</span>
        <span class="breadcrumb-item">SPA Routing</span>
    </div>

    <p>One Laravel includes a powerful client-side router that handles navigation without full page reloads.</p>

    <h3>Route Configuration</h3>
    <div class="example-container">
        <div class="example-header">Defining Routes</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">// In APP_CONFIGS
router: {
    mode: 'history', // or 'hash'
    base: '/',
    routes: [
        { path: '/', view: 'web.pages.home' },
        { path: '/about', view: 'web.pages.about' },
        { path: '/docs', view: 'web.pages.docs' },
        { path: '/users/{id}', view: 'web.pages.user-detail' },
        { path: '/posts/{slug}', view: 'web.pages.post' }
    ],
    beforeEach: function(to, from) {
        // Navigation guard
        return true; // or false to cancel
    },
    afterEach: function(to, from) {
        // Post-navigation hook
    }
}</code></pre>
        </div>
    </div>

    <h3>Route Parameters</h3>
    <div class="example-container">
        <div class="example-header">Dynamic Routes</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">{{-- Route definition --}}
{ path: '/users/{id}', view: 'web.pages.user-detail' }

{{-- Accessing parameters in view --}}
&lt;script&gt;
mounted: function() {
    const route = App.Router.getActiveRoute();
    const userId = route.getParam('id');
    console.log('User ID:', userId);
}
&lt;/script&gt;</code></pre>
        </div>
    </div>

    <h3>Navigation</h3>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Description</th>
                    <th>Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>data-navigate</code></td>
                    <td>Auto-navigation attribute</td>
                    <td><code>&lt;a data-navigate="/about"&gt;About&lt;/a&gt;</code></td>
                </tr>
                <tr>
                    <td><code>data-nav-link</code></td>
                    <td>Alternative navigation attribute</td>
                    <td><code>&lt;button data-nav-link="/docs"&gt;Docs&lt;/button&gt;</code></td>
                </tr>
                <tr>
                    <td><code>App.Router.navigate()</code></td>
                    <td>Programmatic navigation</td>
                    <td><code>App.Router.navigate('/about')</code></td>
                </tr>
                <tr>
                    <td><code>App.Router.getURL()</code></td>
                    <td>Generate route URL</td>
                    <td><code>App.Router.getURL('user', {id: 1})</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3>Router Modes</h3>
    <ul>
        <li><strong>History Mode</strong> - Uses HTML5 History API (default)</li>
        <li><strong>Hash Mode</strong> - Uses URL hash (#) for routing</li>
    </ul>
</section>

