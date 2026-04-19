<!-- Router API -->
<section id="router-api" class="mb-5">
    <h2>Router API</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">API Reference</span>
        <span class="breadcrumb-item">Router API</span>
    </div>

    <h3>Navigation Methods</h3>
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
                    <td><code>Router.navigate(path)</code></td>
                    <td>Navigate to a path</td>
                    <td><code>App.Router.navigate('/about')</code></td>
                </tr>
                <tr>
                    <td><code>Router.getURL(name, params)</code></td>
                    <td>Generate URL from route name</td>
                    <td><code>App.Router.getURL('user', {id: 1})</code></td>
                </tr>
                <tr>
                    <td><code>Router.getActiveRoute()</code></td>
                    <td>Get current active route</td>
                    <td><code>const route = Router.getActiveRoute()</code></td>
                </tr>
                <tr>
                    <td><code>Router.getCurrentPath()</code></td>
                    <td>Get current pathname</td>
                    <td><code>Router.getCurrentPath()</code></td>
                </tr>
                <tr>
                    <td><code>Router.getCurrentQuery()</code></td>
                    <td>Get current query params</td>
                    <td><code>Router.getCurrentQuery()</code></td>
                </tr>
                <tr>
                    <td><code>Router.isCurrentPath(url, mode)</code></td>
                    <td>Check if URL matches current path</td>
                    <td><code>Router.isCurrentPath('/about')</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3>Route Hooks</h3>
    <div class="example-container">
        <div class="example-header">Navigation Guards</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">// Before navigation
App.Router.beforeEach(function(to, from) {
    // Return false to cancel navigation
    if (needsAuth && !isAuthenticated) {
        return false;
    }
    return true;
});

// After navigation
App.Router.afterEach(function(to, from) {
    // Analytics, logging, etc.
    console.log('Navigated to:', to.path);
});</code></pre>
        </div>
    </div>
</section>

