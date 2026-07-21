# UPSTREAM METADATA

This directory contains a fork of `HansSchouten/Laravel-Pagebuilder` which has been customized for this workspace.

*   **Upstream Repository**: https://github.com/HansSchouten/Laravel-Pagebuilder
*   **Upstream Core Dependency**: HansSchouten/PHPageBuilder (https://github.com/HansSchouten/PHPageBuilder)
*   **Fork Version/Tag**: `v0.31.0`
*   **Date Forked**: 2026-07-21
*   **Target branch/commit**: `v0.31.0` tag reference.

## Modifications Made
1. Rename composer package name to `mbws/laravel-pagebuilder` to avoid registry conflicts.
2. Remove database checks on boot phase in `ServiceProvider.php` (lazy load singleton `phpPageBuilder` to prevent PDO connection attempts during artisan CLI tasks).
3. Disable default Website Manager UI / Routes (`website_manager.use_website_manager => false`) and catch-all public storefront routing.
4. Integrate with the project's own custom authentication middleware, authorization gates, and policies.
5. Create standalone database migrations with prefix `pagebuilder_` and database tables support for GrapesJS draft/published revisions.
6. Bypass the default upload system and route assets directly through the main application's Media Library.

## Sync Instructions
To merge upstream updates:
1. Fetch from the upstream repository:
   ```bash
   git remote add upstream https://github.com/HansSchouten/Laravel-Pagebuilder.git
   git fetch upstream
   ```
2. Merge the target tag/branch:
   ```bash
   git merge upstream/master --allow-unrelated-histories
   ```
3. Resolve any conflicts in `ServiceProvider.php`, configurations, and migrations, keeping the local prefix modifications intact.
