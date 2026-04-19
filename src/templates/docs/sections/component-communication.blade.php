@verbatim
<!-- Component Communication -->
<section id="component-communication" class="mb-5">
    <h2>Component Communication</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Advanced Topics</span>
        <span class="breadcrumb-item">Component Communication</span>
    </div>

    <p>One Laravel provides several ways for components to communicate:</p>

    <h3>Parent-Child Communication</h3>
    <div class="example-container">
        <div class="example-header">Passing Data to Children</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">{{-- Parent view --}}
@include('components.child', ['message' => 'Hello from parent'])

{{-- Child component receives data --}}
&lt;div&gt;{{ $message }}&lt;/div&gt;</code></pre>
        </div>
    </div>

    <h3>State Sharing</h3>
    <div class="example-container">
        <div class="example-header">Shared State</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">{{-- Subscribe to state changes --}}
@subscribe(['count', 'user'])

{{-- State updates trigger re-render --}}
&lt;script&gt;
function increment() {
    this.updateStateByKey('count', this.states.count + 1);
}
&lt;/script&gt;</code></pre>
        </div>
    </div>

    <h3>Event System</h3>
    <div class="example-container">
        <div class="example-header">View Events</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">&lt;script&gt;
mounted: function() {
    // Listen to view events
    App.View.on('view:rendered', function(viewName) {
        console.log('View rendered:', viewName);
    });
}

// Emit custom events
App.View.emit('custom:event', { data: 'value' });
&lt;/script&gt;</code></pre>
        </div>
    </div>
</section>
@endverbatim

