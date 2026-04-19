@verbatim
<!-- SSR Hydration -->
<section id="ssr-hydration" class="mb-5">
    <h2>SSR Hydration</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Core Concepts</span>
        <span class="breadcrumb-item">SSR Hydration</span>
    </div>

    <p>One Laravel supports Server-Side Rendering (SSR) with automatic hydration. When a page is server-rendered, the framework automatically attaches JavaScript behavior without re-rendering.</p>

    <h3>How It Works</h3>
    <ol>
        <li><strong>Server renders HTML</strong> - Laravel renders the complete HTML with data</li>
        <li><strong>Client detects SSR</strong> - Framework detects <code>data-server-rendered="true"</code></li>
        <li><strong>Scan DOM</strong> - Framework scans existing HTML structure</li>
        <li><strong>Attach behavior</strong> - Event handlers and state subscriptions are attached</li>
        <li><strong>Mount views</strong> - Views are mounted in bottom-up order</li>
    </ol>

    <h3>Hydration Process</h3>
    <div class="example-container">
        <div class="example-header">Hydration Flow</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">1. Router.hydrateViews()
   ↓
2. View.scanView() - Parse SSR data
   ↓
3. ViewEngine.__scan() - Scan DOM elements
   ↓
4. Attach event handlers
   ↓
5. Setup state subscriptions
   ↓
6. Mount all views (bottom-up)</code></pre>
        </div>
    </div>

    <h3>Enabling SSR</h3>
    <div class="example-container">
        <div class="example-header">Blade Template</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">{{-- In your layout --}}
&lt;div id="app-root" data-server-rendered="true"&gt;
    {{-- Server-rendered content --}}
    @yield('content')
&lt;/div&gt;</code></pre>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Note:</strong> SSR hydration preserves the initial HTML structure, providing faster initial load and better SEO.
    </div>
</section>
@endverbatim

