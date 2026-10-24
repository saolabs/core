# Saola Core v2.4.0

Adds the language primitives required by the first saola/admin alpha: registered locale-first seed sources, strict nested key trees/JSON, scoped DB-backed text resolution and allowlisted client catalogs. Laravel's translator remains independent. Prepared server snapshots enter page-data explicitly; BundleManifest supports context CSS including CSS imported by an entry.

Compatibility: PHP ^8.2, Laravel ^12|^13 (framework PHP requirements apply). No application migration, language synchronization, grant or dependency update runs automatically. Hosts select a LanguageStore; sources are catalogs/seeds, not runtime file fallbacks.

Validated in the release checkout with its own Composer dependencies and full core suite. New language/resolver/CSS/page-data regressions are included. Unrelated local routing work is excluded from this release.
