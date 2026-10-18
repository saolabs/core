# Saola Core v1.0.3

Authentication models now share the system model capabilities used by ordinary Saola models. Applications can extend `Saola\Core\Models\Authenticatable` and keep Laravel authentication/password reset together with system metadata, UUID configuration, lifecycle hooks, file helpers, and BaseRepository integration.

## Changes

- Add `HasSystemModel` and `CommonMethods`, shared by `Model` and the new `Authenticatable` base class.
- Resolve the SoftDeletes/ModelEventMethods trait collision and let Eloquent boot model concerns once.
- Apply soft-delete scopes only when the model explicitly enables `$deleteMode = 'soft'` or `1`; ordinary authentication tables do not require `deleted_at`.
- Use Laravel's SoftDeletes implementation behind Saola lifecycle hooks and guards.
- Preserve model array/object casts during repository writes by reading `getCasts()`.
- Document the shared model protocol in `docs/MODELS.md` and add database-backed regression coverage.

## Compatibility

PHP ^8.2; Laravel ^12 or ^13 (the selected Laravel dependency may require a newer PHP).

## Validation

Composer validate and audit passed; 128 tests, 348 assertions. Consumer integration with Laravel 13 passed 48 tests and 228 assertions against this core source.

## Release scope

Applications using the new authentication base must install this version or newer. UUID and soft-delete columns remain application schema decisions; this release does not migrate consumer databases. Lifecycle `can*` methods are model guards and do not replace authorization by actor or policy. Existing release tags are preserved.
