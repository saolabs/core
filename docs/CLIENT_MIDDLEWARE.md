# Client middleware on module routes

Available in Core 2.9.0 and client 1.7.0. The optional `.sao` directive uses
compiler 3.5.0 and builder 1.5.0.

Core can attach client middleware aliases to the route table exported to the
SPA Router. Declare their JavaScript implementations as functions in
`defineBundle({middleware: {aliases: {...}}})` in project bootstrap. A larger
project can import a separate `MiddlewareProvider.ts` as the `middleware`
field. Registration happens automatically; no builder modification or manual
boot hook is required. `App.Router.useMiddleware(provider)` remains available
for manual registration.

```php
$module->get('/account', [AccountController::class, 'show'])
    ->name('show')
    ->view('web.account')
    ->middleware('auth:web')
    ->clientMiddleware('auth');
```

Use `clientMiddleware('auth', 'profile:complete')` or pass an array. Set it on
a context, module, nested module or individual route. Parent aliases run
before child aliases. Core exports the resulting list as
`meta.middleware`; only aliases and their string parameters are exported.

Laravel's `middleware()` remains independent. A client alias is not a PHP
middleware class and does not grant server access. Register the alias in the
client before Router start; missing aliases block client navigation and log
an error.

The client package documents provider registration, built-in `auth`/`guest`,
groups, custom classes, async checks and cancellation in
[`docs/CLIENT_MIDDLEWARE.md`](https://github.com/saolabs/client/blob/main/docs/CLIENT_MIDDLEWARE.md).
