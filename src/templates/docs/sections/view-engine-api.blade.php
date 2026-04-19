<!-- ViewEngine API -->
<section id="view-engine-api" class="mb-5">
    <h2>ViewEngine API</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">API Reference</span>
        <span class="breadcrumb-item">ViewEngine API</span>
    </div>

    <h3>State Management</h3>
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
                    <td><code>this.updateStateByKey(key, value)</code></td>
                    <td>Update single state property</td>
                    <td><code>this.updateStateByKey('count', 5)</code></td>
                </tr>
                <tr>
                    <td><code>this.updateRealState(state)</code></td>
                    <td>Update multiple state properties</td>
                    <td><code>this.updateRealState({count: 5, loading: false})</code></td>
                </tr>
                <tr>
                    <td><code>this.states</code></td>
                    <td>Access state object</td>
                    <td><code>const count = this.states.count</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3>View Methods</h3>
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
                    <td><code>this.render()</code></td>
                    <td>Render view HTML</td>
                </tr>
                <tr>
                    <td><code>this.virtualRender()</code></td>
                    <td>Virtual render (SSR scan)</td>
                </tr>
                <tr>
                    <td><code>this.mounted()</code></td>
                    <td>Called when view is mounted</td>
                </tr>
                <tr>
                    <td><code>this.unmounted()</code></td>
                    <td>Called when view is unmounted</td>
                </tr>
                <tr>
                    <td><code>this.destroy()</code></td>
                    <td>Destroy view instance</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

