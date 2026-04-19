@verbatim
<!-- Resources Management -->
<section id="resources-management" class="mb-5">
    <h2>Resources Management</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Advanced Topics</span>
        <span class="breadcrumb-item">Resources Management</span>
    </div>

    <p>One Laravel automatically manages CSS and JavaScript resources, preventing duplicates and ensuring proper cleanup.</p>

    <h3>Registering Resources</h3>
    <div class="example-container">
        <div class="example-header">Styles and Scripts</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">@register('resources')
    {{-- External stylesheet --}}
    &lt;link rel="stylesheet" href="/css/custom.css"&gt;
    
    {{-- Inline styles --}}
    &lt;style&gt;
        .custom-class { color: red; }
    &lt;/style&gt;
    
    {{-- External script --}}
    &lt;script src="/js/library.js"&gt;&lt;/script&gt;
    
    {{-- Inline script --}}
    &lt;script&gt;
        function myFunction() {
            console.log('Hello');
        }
    &lt;/script&gt;
@endRegister</code></pre>
        </div>
    </div>

    <h3>Resource Lifecycle</h3>
    <ul>
        <li><strong>Styles</strong> - Inserted in <code>created()</code>, removed in <code>destroy()</code></li>
        <li><strong>Scripts</strong> - Inserted in <code>mounted()</code>, removed in <code>unmounted()</code></li>
        <li><strong>Deduplication</strong> - Resources are tracked globally to prevent duplicates</li>
    </ul>

    <h3>Function Wrapper Scripts</h3>
    <div class="example-container">
        <div class="example-header">Registering Script Functions</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">// In compiled view
ViewEngine.registerScript('view.name', 'functionName', function() {
    // This function executes only once
    console.log('Script executed');
});

// In Blade template
@register('resources')
    &lt;script&gt;
        // Function will be wrapped and executed once
    &lt;/script&gt;
@endRegister</code></pre>
        </div>
    </div>
</section>
@endverbatim

