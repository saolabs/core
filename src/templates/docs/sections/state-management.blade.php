@verbatim
<!-- State Management -->
<section id="state-management" class="mb-5">
    <h2>State Management</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Core Concepts</span>
        <span class="breadcrumb-item">State Management</span>
    </div>

    <p>One Laravel provides several ways to update component state:</p>

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
                    <td><code>updateStateByKey()</code></td>
                    <td>Update a single state property</td>
                    <td><code>this.updateStateByKey('count', 5)</code></td>
                </tr>
                <tr>
                    <td><code>updateRealState()</code></td>
                    <td>Update multiple state properties</td>
                    <td><code>this.updateRealState({count: 5, loading: false})</code></td>
                </tr>
                <tr>
                    <td><code>useState()</code></td>
                    <td>Get current state value</td>
                    <td><code>const [count, setCount] = useState(0)</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="alert alert-warning">
        <strong>Important:</strong> Always use the provided methods to update state. Direct assignment won't trigger reactivity.
    </div>
</section>
@endverbatim

