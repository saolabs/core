<!-- Lifecycle Methods -->
<section id="lifecycle-methods" class="mb-5">
    <h2>Lifecycle Methods</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Core Concepts</span>
        <span class="breadcrumb-item">Lifecycle Methods</span>
    </div>

    <p>One Laravel views have a complete lifecycle with hooks that allow you to execute code at specific stages:</p>

    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Hook</th>
                    <th>When Called</th>
                    <th>Purpose</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>beforeCreate()</code></td>
                    <td>Before view is created</td>
                    <td>Setup initial state, validate config</td>
                </tr>
                <tr>
                    <td><code>created()</code></td>
                    <td>After view is created</td>
                    <td>Initialize services, insert styles</td>
                </tr>
                <tr>
                    <td><code>beforeInit()</code></td>
                    <td>Before initialization</td>
                    <td>Validate dependencies</td>
                </tr>
                <tr>
                    <td><code>init()</code></td>
                    <td>During initialization</td>
                    <td>Setup event listeners, initialize libraries</td>
                </tr>
                <tr>
                    <td><code>afterInit()</code></td>
                    <td>After initialization</td>
                    <td>Post-initialization tasks</td>
                </tr>
                <tr>
                    <td><code>beforeMount()</code></td>
                    <td>Before DOM mounting</td>
                    <td>Pre-mount setup</td>
                </tr>
                <tr>
                    <td><code>mounted()</code></td>
                    <td>After DOM is ready</td>
                    <td>Query DOM, insert scripts, start listeners</td>
                </tr>
                <tr>
                    <td><code>beforeUpdate()</code></td>
                    <td>Before state update</td>
                    <td>Validate data before update</td>
                </tr>
                <tr>
                    <td><code>updated()</code></td>
                    <td>After state update</td>
                    <td>Post-update tasks</td>
                </tr>
                <tr>
                    <td><code>beforeUnmount()</code></td>
                    <td>Before unmounting</td>
                    <td>Pre-unmount cleanup</td>
                </tr>
                <tr>
                    <td><code>unmounted()</code></td>
                    <td>After unmounting</td>
                    <td>Remove listeners, clear timers, remove scripts</td>
                </tr>
                <tr>
                    <td><code>beforeDestroy()</code></td>
                    <td>Before destruction</td>
                    <td>Save state, final cleanup</td>
                </tr>
                <tr>
                    <td><code>destroyed()</code></td>
                    <td>After destruction</td>
                    <td>Post-destroy logging, remove styles</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3>Lifecycle Flow</h3>
    <div class="example-container">
        <div class="example-header">Complete Lifecycle</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">1. CREATION
   Constructor → beforeCreate() → created() → initialize()

2. INITIALIZATION
   beforeInit() → init() → afterInit()

3. RENDERING
   render() / virtualRender() / prerender()

4. MOUNTING
   beforeMount() → mounted() [DOM READY]

5. UPDATE (Reactive)
   beforeUpdate() → updated()

6. UNMOUNTING
   beforeUnmount() → unmounted()

7. DESTRUCTION
   beforeDestroy() → destroy() → destroyed()</code></pre>
        </div>
    </div>

    <h3>Example Usage</h3>
    <div class="example-container">
        <div class="example-header">Lifecycle Hooks Example</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">@register('resources')
&lt;script&gt;
{
    created: function() {
        // Insert styles here
        console.log('View created');
    },
    
    init: function() {
        // Setup initial state
        this.data.count = 0;
    },
    
    mounted: function() {
        // DOM is ready - query elements, setup listeners
        const button = document.getElementById('my-button');
        button.addEventListener('click', this.handleClick);
        
        // Insert scripts here
    },
    
    unmounted: function() {
        // Cleanup: remove listeners, clear timers
        const button = document.getElementById('my-button');
        button.removeEventListener('click', this.handleClick);
    },
    
    destroyed: function() {
        // Final cleanup - styles removed automatically
        console.log('View destroyed');
    }
}
&lt;/script&gt;
@endRegister</code></pre>
        </div>
    </div>
</section>

