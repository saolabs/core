<!-- Installation -->
<section id="installation" class="mb-5">
    <h2>Installation</h2>
    <div class="breadcrumb">
        <span class="breadcrumb-item">Documentation</span>
        <span class="breadcrumb-item">Getting Started</span>
        <span class="breadcrumb-item">Installation</span>
    </div>

    <p>One Laravel can be installed via Composer. Make sure you have PHP 8.1 or higher installed.</p>

    <div class="alert alert-info">
        <strong>Prerequisites:</strong> PHP 8.1+, Composer, Node.js 16+ (for asset compilation)
    </div>

    <div class="example-container">
        <div class="example-header">Create New Project</div>
        <div class="example-code">
            <pre><code># Create a new One Laravel project
composer create-project one-laravel/laravel my-spa-app

# Navigate to the project directory
cd my-spa-app

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Start development server
php artisan serve</code></pre>
        </div>
    </div>

    <h3>Add to Existing Laravel Project</h3>
    <p>You can also add One Laravel to an existing Laravel application:</p>

    <div class="example-container">
        <div class="example-header">Install Package</div>
        <div class="example-code">
            <pre><code># Install One Laravel package
composer require one-laravel/framework

# Publish configuration
php artisan vendor:publish --provider="OneLaravel\ServiceProvider"

# Compile assets
php artisan one:compile</code></pre>
        </div>
    </div>
</section>

