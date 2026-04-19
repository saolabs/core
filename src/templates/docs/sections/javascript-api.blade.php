<!-- JavaScript API -->
<section id="javascript-api" class="mb-5">
    <h2>JavaScript API</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">API Reference</span>
        <span class="breadcrumb-item">JavaScript API</span>
    </div>

    <h3>Application Instance</h3>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Property/Method</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>App.View</code></td>
                    <td>View system instance</td>
                </tr>
                <tr>
                    <td><code>App.Router</code></td>
                    <td>Router instance</td>
                </tr>
                <tr>
                    <td><code>App.API</code></td>
                    <td>API service</td>
                </tr>
                <tr>
                    <td><code>App.Helper</code></td>
                    <td>Helper utilities</td>
                </tr>
                <tr>
                    <td><code>App.http</code></td>
                    <td>HTTP service instance</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3>View API</h3>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>App.View.loadView(name, data, urlPath)</code></td>
                    <td>Load and render a view</td>
                </tr>
                <tr>
                    <td><code>App.View.scanView(name, route)</code></td>
                    <td>Scan and hydrate SSR view</td>
                </tr>
                <tr>
                    <td><code>App.View.mountView(name, params, route)</code></td>
                    <td>Mount a view</td>
                </tr>
                <tr>
                    <td><code>App.View.include(name, data)</code></td>
                    <td>Include a view</td>
                </tr>
                <tr>
                    <td><code>App.View.extendView(name, data)</code></td>
                    <td>Extend a layout view</td>
                </tr>
                <tr>
                    <td><code>App.View.section(name, content, type)</code></td>
                    <td>Define a section</td>
                </tr>
                <tr>
                    <td><code>App.View.yield(name, defaultValue)</code></td>
                    <td>Yield section content</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

