# Simple Coupon Restrictions

A WordPress plugin that tracks when customers use specific restricted coupons and blocks them from using other restricted coupons in future orders. Supports both registered and guest customers.

## Version 1.1.2

### Key Features

- **Smart Coupon Restriction**: Only blocks restricted coupons for customers who have used restricted coupons before
- **Prefix-Based Restrictions**: NEW! Block coupons based on prefixes (e.g., "exchange" blocks "exchange45645fd")
- **Non-Restricted Coupons**: Customers can still use non-restricted coupons regardless of their restriction history
- **Guest Customer Support**: Tracks and restricts guest customers using email addresses
- **Registered Customer Support**: Full support for logged-in WordPress users
- **Professional Admin Interface**: Easy-to-use admin panel with real-time validation and responsive design
- **Database Tracking**: Comprehensive tracking of coupon usage with proper database versioning
- **WooCommerce Integration**: Seamless integration with WooCommerce checkout process

### How It Works

#### Customer-Based Restrictions
1. **Configure Restricted Coupons**: Set up which coupons should be restricted in the admin panel
2. **Customer Uses Restricted Coupon**: When a customer (registered or guest) uses a restricted coupon, they are tracked
3. **Future Restriction**: If the same customer tries to use another restricted coupon, they are blocked
4. **Non-Restricted Coupons**: Customers can still use non-restricted coupons without any limitations

#### Prefix-Based Restrictions (NEW in v1.1.2)
1. **Set Prefix Restrictions**: In any coupon's "Usage Restrictions" tab, add prefixes to exclude
2. **Automatic Validation**: System prevents coupons with conflicting prefixes from being used together
3. **Bidirectional Checking**: Works both ways - prevents conflicts from either direction
4. **Case-Insensitive**: "EXCHANGE123" and "exchange123" are treated the same

### Example Scenarios

#### Customer Restrictions:
- Restricted coupons: `FIRST10`, `WELCOME10`, `DEAL10`
- Non-restricted coupons: `FREEGIFT`, `SHIPPING50`

**Customer Journey:**
1. Customer uses `FIRST10` coupon → ✅ Allowed (first use)
2. Customer tries `WELCOME10` coupon → ❌ Blocked (already used restricted coupon)
3. Customer tries `FREEGIFT` coupon → ✅ Allowed (non-restricted coupon)
4. Customer tries `SHIPPING50` coupon → ✅ Allowed (non-restricted coupon)

#### Prefix Restrictions:
- Promotional coupon with prefix restriction: "exchange"
- Exchange coupons: `exchange45645fd`, `EXCHANGE123`, `exchangeVIP`

**Prefix Journey:**
1. Customer applies promotional coupon → ✅ Allowed
2. Customer tries `exchange45645fd` → ❌ Blocked (conflicts with promotional coupon's prefix restriction)
3. Customer removes promotional coupon and applies `exchange45645fd` → ✅ Allowed
4. Customer then tries promotional coupon → ❌ Blocked (exchange coupon blocks promotional coupon)

### Installation

1. Upload the plugin files to `/wp-content/plugins/simple-coupon-restriction/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **WooCommerce > Coupon Restrictions** to configure your restricted coupons
4. For prefix restrictions, edit any coupon and find "Exclude coupons with prefixes" in Usage Restrictions tab

### Code Organization

The plugin follows professional WordPress development standards with a modular architecture:

#### Directory Structure
```
simple-coupon-restriction/
├── includes/
│   ├── Admin/
│   │   ├── AdminController.php     # Main admin interface
│   │   └── PrefixAdmin.php         # Prefix restrictions admin
│   ├── Ajax/
│   │   └── AjaxHandler.php         # AJAX request handlers
│   └── Core/
│       ├── Plugin.php              # Main plugin orchestrator
│       ├── CouponTracker.php       # Coupon usage tracking
│       ├── RestrictionChecker.php  # Customer restriction validation
│       ├── PrefixRestriction.php   # Prefix-based restrictions
│       ├── GuestHelper.php         # Guest customer utilities
│       └── Activator.php           # Plugin activation logic
├── assets/
│   ├── css/
│   │   ├── prefix-admin.css        # Development styles
│   │   ├── prefix-admin.min.css    # Production styles
│   │   └── prefix-admin-rtl.css    # RTL language support
│   └── js/
│       └── prefix-admin.js         # Interactive functionality
└── languages/                     # Translation files
```

#### Key Features
- **PSR-4 Autoloading**: Automatic class loading following PHP standards
- **Separation of Concerns**: Each class has a single, clear responsibility
- **WordPress Standards**: Follows WordPress Coding Standards and best practices
- **Security**: Nonce verification, capability checks, input sanitization
- **Performance**: Conditional asset loading, minified production files
- **Accessibility**: WCAG compliant admin interface
- **Internationalization**: Full translation support

### Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

### Admin Features

- **Restricted Coupons Management**: Add/remove coupons from the restricted list
- **Customer Tracking**: View all customers who have used restricted coupons
- **Guest Customer Support**: See guest customers with "Guest" badges
- **Reset Functionality**: Reset restrictions for specific customers
- **Process Existing Orders**: Retroactively track customers from past orders

### Database Structure

The plugin creates a `wp_scr_restricted_customers` table to track:
- Customer ID (for registered users) or NULL (for guests)
- Customer email address
- Customer type (registered/guest)
- Coupon code used
- Order ID
- Restriction timestamp

### Changelog

#### Version 1.1.2 (2025-01-04)
- **Added**: Prefix-based coupon restrictions with real-time validation
- **Enhanced**: Professional code organization with dedicated classes
- **Improved**: Asset management with separate CSS/JS files and proper WordPress enqueuing
- **Added**: RTL language support and accessibility features
- **Enhanced**: Mobile-responsive admin interface with dark mode support

#### Version 1.1.1 (2025-01-04)
- **Fixed**: Issue where customers were blocked from using ALL coupons instead of just restricted ones
- **Improved**: Now only restricted coupons are blocked for customers with restriction history
- **Enhanced**: Error messages now clarify that non-restricted coupons can still be used

#### Version 1.1.0 (2025-01-03)
- **Added**: Guest customer support with email-based tracking
- **Added**: Admin interface improvements with guest customer identification
- **Added**: Database migration system for existing installations
- **Added**: Automatic cleanup of expired guest restrictions

#### Version 1.0.0
- Initial release with basic restriction functionality for registered customers

For detailed changelog, see [CHANGELOG.md](CHANGELOG.md)

### Support

For support and feature requests, please contact the plugin author.

### License

This plugin is licensed under the GPL v2 or later.

---

**Author**: Tanjir Al Mamun  
**Website**: https://tanjirsdev.com  
**Plugin Version**: 1.1.2 