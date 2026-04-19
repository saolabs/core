@verbatim
<!-- View System -->
<section id="view-system" class="mb-5">
    <h2>View System Architecture</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Core Concepts</span>
        <span class="breadcrumb-item">View System</span>
    </div>

    <p>One Laravel's view system manages the complete lifecycle of views, from rendering to hydration:</p>

    <h3>View Hierarchy</h3>
    <ul>
        <li><strong>Layout Views</strong> - Base templates that wrap page content</li>
        <li><strong>Page Views</strong> - Individual page components</li>
        <li><strong>Component Views</strong> - Reusable UI components</li>
        <li><strong>Partial Views</strong> - Small reusable snippets</li>
    </ul>

    <h3>View Relationships</h3>
    <div class="example-container">
        <div class="example-header">Extends and Includes</div>
        <div class="example-code">
            <pre><code class="syntax-highlight">{{-- Page extends layout --}}
@extends('web.layouts.base')

{{-- Include components --}}
@include('web.components.header')
@include('web.components.footer')

{{-- Conditional includes --}}
@includeIf('web.components.banner', ['show' => true])
@includeWhen($user, 'web.components.user-menu')</code></pre>
        </div>
    </div>

    <h3>View Rendering Modes</h3>
    <ul>
        <li><strong>CSR (Client-Side Rendering)</strong> - Views rendered in browser</li>
        <li><strong>SSR (Server-Side Rendering)</strong> - Views pre-rendered on server</li>
        <li><strong>Hybrid</strong> - Initial SSR, subsequent CSR</li>
    </ul>
</section>
@endverbatim

