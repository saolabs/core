<!-- Directory Structure -->
<section id="directory-structure" class="mb-5">
    <h2>Directory Structure</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Getting Started</span>
        <span class="breadcrumb-item">Directory Structure</span>
    </div>

    <p>One Laravel follows a structured directory layout for organizing your SPA application:</p>

    <div class="example-container">
        <div class="example-header">Project Structure</div>
        <div class="example-code">
            <pre><code>onelaravel/
├── resources/
│   ├── js/
│   │   └── app/
│   │       ├── core/              # Core modules
│   │       │   ├── Router.js      # SPA Router
│   │       │   ├── ViewEngine.js  # View Engine
│   │       │   ├── View.js        # View System
│   │       │   ├── ViewState.js   # State Management
│   │       │   └── ...
│   │       ├── views/             # Compiled views
│   │       │   ├── WebPagesHome.js
│   │       │   ├── WebPagesDocs.js
│   │       │   └── ...
│   │       ├── components/        # Reusable components
│   │       ├── services/          # Services
│   │       └── utils/             # Utilities
│   └── views/
│       ├── web/
│       │   ├── layouts/          # Layout templates
│       │   │   └── base.blade.php
│       │   └── pages/            # Page templates
│       │       ├── home.blade.php
│       │       ├── docs.blade.php
│       │       └── ...
│       └── components/           # Component templates
├── public/
│   └── static/
│       └── app/                  # Compiled assets
│           └── main.js
└── scripts/
    └── compiler/                 # Blade to JS compiler
        └── main_compiler.py</code></pre>
        </div>
    </div>

    <h3>Key Directories</h3>
    <ul>
        <li><strong>resources/js/app/core/</strong> - Core framework modules</li>
        <li><strong>resources/js/app/views/</strong> - Compiled JavaScript views (auto-generated)</li>
        <li><strong>resources/views/web/</strong> - Blade templates for web pages</li>
        <li><strong>resources/views/components/</strong> - Reusable Blade components</li>
        <li><strong>public/static/app/</strong> - Compiled and minified JavaScript bundles</li>
    </ul>
</section>

