@verbatim
<!-- Performance -->
<section id="performance" class="mb-5">
    <h2>Performance Optimization</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Advanced Topics</span>
        <span class="breadcrumb-item">Performance</span>
    </div>

    <h3>Best Practices</h3>
    <ul>
        <li><strong>Use SSR</strong> - Server-side rendering for faster initial load</li>
        <li><strong>Lazy Loading</strong> - Load components on demand</li>
        <li><strong>Resource Deduplication</strong> - Framework automatically prevents duplicate resources</li>
        <li><strong>State Batching</strong> - Multiple state updates are batched automatically</li>
        <li><strong>Virtual Rendering</strong> - Use virtual render for SSR scanning (no HTML generation)</li>
    </ul>

    <h3>State Update Batching</h3>
    <div class="example-container">
        <div class="example-header">Efficient State Updates</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">&lt;script&gt;
// Multiple updates are batched automatically
function updateMultiple() {
    this.updateStateByKey('count', 1);
    this.updateStateByKey('name', 'John');
    this.updateStateByKey('email', 'john@example.com');
    // All updates processed in single batch
}
&lt;/script&gt;</code></pre>
        </div>
    </div>

    <h3>View Caching</h3>
    <p>Views are cached automatically to improve performance. Cache can be configured in view configuration:</p>
    <div class="example-container">
        <div class="example-code">
            <pre><code class="syntax-highlight">// View configuration
App.View.cachedTimes = 600; // Cache for 10 minutes</code></pre>
        </div>
    </div>
</section>
@endverbatim

