# Changelog

All notable changes to `felixmuhoro/laravel-mpesa` will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- GitHub Actions CI matrix across PHP 8.1 – 8.4 and Laravel 10 – 13

## [1.2.1] - 2026-04-21

### Added
- "Quick start" section at the top of the README with a minimal STK Push example

## [1.2.0] - 2026-04-18

### Added
- STK Push lifecycle persistence to the `mpesa_transactions` table

## [1.1.0] - 2026-04-18

### Added
- Laravel 13 support

## [1.0.0] - 2026-04-18

### Added
- Initial release
- STK Push + STK Query with correctly-handled async pending state
- C2B register / simulate
- B2C send
- Account balance, transaction status, reversal
- Exhaustive `ResultCode` dictionary (15+ codes including the undocumented `4999`)
- Events: `StkPushInitiated`, `PaymentSuccessful`, `PaymentFailed`
- Callback controller + IP allow-list / shared-secret middleware
- Typed DTOs: `StkPushRequest`, `StkPushResponse`, `StkQueryResponse`, `CallbackPayload`
- `PhoneNumber` normalizer accepting every common Kenyan format
- PHPUnit test suite with `Http::fake()`-mocked Daraja
- Auto-published config + migration

[Unreleased]: https://github.com/felixmuhoro/laravel-mpesa/compare/v1.2.1...HEAD
[1.2.1]: https://github.com/felixmuhoro/laravel-mpesa/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/felixmuhoro/laravel-mpesa/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/felixmuhoro/laravel-mpesa/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/felixmuhoro/laravel-mpesa/releases/tag/v1.0.0
