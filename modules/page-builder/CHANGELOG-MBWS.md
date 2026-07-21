# CHANGELOG-MBWS

All notable changes to the custom `mbws/laravel-pagebuilder` module will be documented in this file.

## [0.31.0-custom.1] - 2026-07-21
### Added
- Created `NOTICE.md`, `UPSTREAM.md`, and `CHANGELOG-MBWS.md` to trace local changes.
- Added path repository binding inside main `backend/composer.json`.

### Changed
- Renamed composer package to `mbws/laravel-pagebuilder`.
- Refactored `ServiceProvider.php` to lazy-load the `phpPageBuilder` singleton, eliminating any boot-time queries or PDO connection attempts.
- Changed default minimum PHP version requirement to `^8.2`.
