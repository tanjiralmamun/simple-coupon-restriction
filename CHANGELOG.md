# Changelog

All notable changes to this project will be documented in this file.

## [1.1.1] - 2025-01-04

### Fixed
- Fixed issue where customers who had previously used restricted coupons were blocked from using ALL coupons, including non-restricted ones
- Now only restricted coupons are blocked for customers who have used restricted coupons before
- Non-restricted coupons can now be used by all customers regardless of their restriction history
- Updated error message to clarify that only restricted coupons are blocked and non-restricted coupons can still be used

### Changed
- Improved coupon validation logic to be more selective about which coupons to block
- Enhanced user experience by allowing legitimate use of non-restricted coupons

## [1.1.0] - 2025-01-03

### Added
- Guest customer support - now tracks and restricts guest customers using email addresses
- Email-specific session storage to prevent cross-contamination between different guest users
- Guest customer identification in admin panel with "Guest" badges
- Database migration support for existing installations
- Automatic cleanup of expired guest restrictions (30 days)
- "Process Existing Orders" functionality to retroactively track customers from past orders
- HPOS (High-Performance Order Storage) compatibility

### Changed
- Database structure updated to support guest customers with `customer_email` and `customer_type` fields
- Made `customer_id` nullable to accommodate guest customers
- Enhanced admin interface to display both registered and guest customers
- Updated reset functionality to handle both customer types
- Plugin version updated to 1.1.0

### Technical
- Added `GuestHelper` class for guest customer management
- Updated `CouponTracker`, `RestrictionChecker`, `AdminView`, and `AdminController` classes
- Implemented proper database versioning and migration system
- Added comprehensive error handling and logging

## [1.0.0] - 2024-12-XX

### Added
- Initial release
- Basic coupon restriction functionality for registered customers
- Admin interface for managing restricted coupons and viewing restricted customers
- Database tracking of coupon usage
- WooCommerce integration for coupon validation 