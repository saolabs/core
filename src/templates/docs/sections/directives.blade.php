@verbatim
<!-- API Reference -->
<section id="directives" class="mb-5">
    <h2>Blade Directives</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">API Reference</span>
        <span class="breadcrumb-item">Blade Directives</span>
    </div>

    <p>One Laravel extends Blade with reactive directives:</p>

    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Directive</th>
                    <th>Description</th>
                    <th>Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>@useState</code></td>
                    <td>Declare reactive state</td>
                    <td><code>@useState(['count' => 0])</code></td>
                </tr>
                <tr>
                    <td><code>@fetch</code></td>
                    <td>Fetch data from API</td>
                    <td><code>@fetch('users', '/api/users')</code></td>
                </tr>
                <tr>
                    <td><code>@await</code></td>
                    <td>Handle async operations</td>
                    <td><code>@await('fetchUsers')</code></td>
                </tr>
                <tr>
                    <td><code>@subscribe</code></td>
                    <td>Subscribe to state changes</td>
                    <td><code>@subscribe('count', 'onCountChange')</code></td>
                </tr>
                <tr>
                    <td><code>@extends</code></td>
                    <td>Extend a layout view</td>
                    <td><code>@extends('web.layouts.base')</code></td>
                </tr>
                <tr>
                    <td><code>@include</code></td>
                    <td>Include a component</td>
                    <td><code>@include('web.components.header')</code></td>
                </tr>
                <tr>
                    <td><code>@block</code></td>
                    <td>Define a content block</td>
                    <td><code>
                            @block('content')
                                ...
                            @endblock
                        </code></td>
                </tr>
                <tr>
                    <td><code>@useBlock</code></td>
                    <td>Use a content block</td>
                    <td><code>@useBlock('content')</code></td>
                </tr>
                <tr>
                    <td><code>@register</code></td>
                    <td>Register resources</td>
                    <td><code>
                            @register('resources')
                                ...
                            @endRegister
                        </code></td>
                </tr>
                <tr>
                    <td><code>@section</code></td>
                    <td>Define a section</td>
                    <td><code>
                            @section('title', 'Page Title')
                        </code></td>
                </tr>
                <tr>
                    <td><code>@yield</code></td>
                    <td>Yield section content</td>
                    <td><code>@yield('title', 'Default')</code></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
@endverbatim

