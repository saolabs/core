@verbatim
<!-- Reactive Components -->
<section id="reactive-components" class="mb-5">
    <h2>Reactive Components</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Core Concepts</span>
        <span class="breadcrumb-item">Reactive Components</span>
    </div>

    <p>One Laravel components automatically update when their state changes. This is achieved through a reactive system similar to Vue.js or React.</p>

    <h3>State Declaration</h3>
    <p>Use the <code class="code-inline">@useState</code> directive to declare reactive state:</p>

    <div class="example-container">
        <div class="example-header">State Declaration Examples</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">

@const([$message, $setMessage] = useState('Hello World'))
@useState(['message' => 'Hello World'])

{{-- Complex state --}}

@const(
    [$user, $setUser] = useState([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ])
)

@const([$todos, $setTodos] = useState([]))
@const([$loading, $setLoading] = useState(false))
@const([$count, $setCount] = useState(0))
                                </code></pre>
        </div>
    </div>

    <h3>Computed Properties</h3>
    <p>Create computed values that automatically update when dependencies change:</p>

    <div class="example-container">
        <div class="example-header">Computed Properties</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">

@const([$firstName, $setFirstName] = useState('John'))
@const([$lastName, $setLastName] = useState('Doe'))

&lt;div&gt;
    &lt;p&gt;Full Name: {{ $firstName . ' ' . $lastName }}&lt;/p&gt;
    &lt;p&gt;Initials: {{ substr($firstName, 0, 1) . substr($lastName, 0, 1) }}&lt;/p&gt;
&lt;/div&gt;
                            </code></pre>
        </div>
    </div>
</section>
@endverbatim

