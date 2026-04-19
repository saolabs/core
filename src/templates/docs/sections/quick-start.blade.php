<!-- Quick Start -->
<section id="quick-start" class="mb-5">
    <h2>Quick Start</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Getting Started</span>
        <span class="breadcrumb-item">Quick Start</span>
    </div>

    <p>Let's create your first reactive component in One Laravel. We'll build a simple counter.</p>

    <div class="example-container">
        <div class="example-header">resources/views/components/counter.blade.php</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">{{-- Define reactive state --}}
@verbatim
@useState(0, $count, $setCount)

&lt;div class="counter-component"&gt;
    &lt;h3&gt;Counter: {{ $count }}&lt;/h3&gt;
    
    &lt;div&gt;
        &lt;button data-click="decrement"&gt;-&lt;/button&gt;
        &lt;button data-click="increment"&gt;+&lt;/button&gt;
        &lt;button data-click="reset"&gt;Reset&lt;/button&gt;
    &lt;/div&gt;
    
    &lt;p&gt;
        @if ($count > 0)
Positive number!
@elseif($count < 0)
Negative number!
@else
Zero!
@endif
    &lt;/p&gt;
&lt;/div&gt;

&lt;script&gt;
function increment() {
    this.updateStateByKey('count', count + 1);
}

function decrement() {
    this.updateStateByKey('count', count - 1);
}

function reset() {
    this.updateStateByKey('count', 0);
}
&lt;/script&gt;
@endverbatim
</code></pre>
        </div>
        <div class="example-preview">
            <strong>Result:</strong> A fully reactive counter that updates the DOM automatically when state changes.
        </div>
    </div>

    <h3>Using the Component</h3>
    <div class="example-container">
        <div class="example-header">resources/views/welcome.blade.php</div>
        <div class="example-code">
            <pre><code>
@verbatim
@extends('layouts.app')

@section('content')
    &lt;div class="container"&gt;
        &lt;h1&gt;Welcome to One Laravel&lt;/h1&gt;
        
        {{-- Include our reactive component --}}
        @include('components.counter')
    &lt;/div&gt;
@endsection

@endverbatim
                                </code></pre>
        </div>
    </div>
</section>

