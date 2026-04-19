@verbatim
<!-- Event Handling -->
<section id="event-handling" class="mb-5">
    <h2>Event Handling</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Core Concepts</span>
        <span class="breadcrumb-item">Event Handling</span>
    </div>

    <p>Handle user interactions with data attributes:</p>

    <div class="example-container">
        <div class="example-header">Event Handling Examples</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">&lt;!-- Click events --&gt;
&lt;button data-click="handleClick"&gt;Click Me&lt;/button&gt;

&lt;!-- Form events --&gt;
&lt;input type="text" data-input="handleInput" data-change="handleChange"&gt;
&lt;form data-submit="handleSubmit"&gt;...&lt;/form&gt;

&lt;!-- Mouse events --&gt;
&lt;div data-mouseenter="showTooltip" data-mouseleave="hideTooltip"&gt;
    Hover me
&lt;/div&gt;

&lt;!-- Keyboard events --&gt;
&lt;input data-keydown="handleKeyDown" data-keyup="handleKeyUp"&gt;

&lt;script&gt;
function handleClick(event) {
    console.log('Button clicked!', event);
}

function handleInput(event) {
    this.updateStateByKey('inputValue', event.target.value);
}

function handleSubmit(event) {
    event.preventDefault();
    // Handle form submission
}
&lt;/script&gt;</code></pre>
        </div>
    </div>
</section>
@endverbatim

