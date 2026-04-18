# Changelog

All notable changes to `felixmuhoro/laravel-mpesa` will be documented here.

## [Unreleased]

### Added
- Initial release
- STK Push + STK Query with correctly-handled async pending state
- C2B register / simulate
- B2C send
- Account balance, transaction status, reversal
- Exhaustive `ResultCode` dictionary (15+ codes incl. undocumented `4999`)
- Events: `StkPushInitiated`, `PaymentSuccessful`, `PaymentFailed`
- Callback controller + IP allow-list / shared-secret middleware
- Typed DTOs: `StkPushRequest`, `StkPushResponse`, `StkQueryResponse`, `CallbackPayload`
- `PhoneNumber` normalizer accepting every common Kenyan format
- PHPUnit test suite with `Http::fake()`-mocked Daraja
- Auto-published config + migration
