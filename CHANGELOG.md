# Changelog

All notable changes to this project will be documented in this file.

## [1.1.2] - 2025-01-04

### Added
- **Prefix-Based Coupon Restrictions**: New feature to restrict coupons based on prefixes of other applied coupons
- Admin field "Exclude coupons with prefixes" in coupon Usage Restrictions tab
- Real-time validation and formatting for prefix input fields
- Bidirectional prefix checking (works both ways)
- Case-insensitive prefix matching (e.g., "EXCHANGE123" matches "exchange" prefix)
- Support for multiple comma-separated prefixes per coupon

### Enhanced
- **Professional Code Organization**: Restructured codebase with dedicated classes
  - `PrefixRestriction` class for core prefix validation logic
  - `PrefixAdmin` class for admin interface and field management
  - Proper separation of concerns and maintainable architecture
- **Asset Management**: Moved CSS and JavaScript to separate files with proper WordPress enqueuing
  - Conditional loading (only on coupon edit pages)
  - Minified CSS for production environments
  - RTL (Right-to-Left) language support
  - Responsive design with dark mode support
  - Accessibility features (WCAG compliant)
- **Internationalization**: Full localization support for all user-facing strings
- **Performance Optimizations**: Smart asset loading and caching strategies

### Technical Improvements
- Added comprehensive input validation and sanitization
- Implemented proper WordPress coding standards
- Enhanced security with nonce verification and capability checks
- Added extensive inline documentation and code comments
- Browser compatibility with graceful degradation
- Mobile-responsive admin interface

### User Experience
- Real-time prefix validation with visual feedback
- Auto-formatting and duplicate removal
- Clear error messages explaining conflicts
- Help tooltips with usage examples
- Loading states and visual indicators

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