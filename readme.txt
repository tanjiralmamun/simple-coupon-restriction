=== Simple Coupon Restrictions ===
Contributors: tanjiralmamun
Tags: woocommerce, coupons, restrictions, customer management, guest customers, hpos
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track when customers use specific restricted coupons and block them from using other restricted coupons in future orders. Supports both registered and guest customers.

== Description ==

Simple Coupon Restrictions is a lightweight WordPress plugin that helps you control coupon usage across multiple orders. When a customer uses a specific restricted coupon, they are blocked from using other restricted coupons in future orders, while still being able to use non-restricted coupons.

**Key Features:**

* **Smart Coupon Restriction**: Only blocks restricted coupons for customers who have used restricted coupons before
* **Non-Restricted Coupons**: Customers can still use non-restricted coupons regardless of their restriction history
* **Guest Customer Support**: Tracks and restricts guest customers using email addresses
* **Registered Customer Support**: Full support for logged-in WordPress users
* **HPOS Compatible**: Full support for WooCommerce High-Performance Order Storage
* **Easy Management**: Simple admin interface to configure which coupons are restricted
* **AJAX Coupon Search**: Search coupons by both code and description with autocomplete
* **Customer Management**: View and reset restrictions for individual customers (with guest badges)
* **Modern UI**: Clean, responsive admin interface with SweetAlert2 notifications
* **Process Existing Orders**: Retroactively track customers from past orders
* **Database Migration**: Automatic updates for existing installations
* **Automatic Cleanup**: Removes expired guest restrictions after 30 days

**How it Works:**

1. Configure which coupon codes should be restricted using the search feature
2. When a customer (registered or guest) uses any of these coupons and their order is completed/processed, they are tracked
3. From that point forward, they cannot use other restricted coupons in future orders
4. Non-restricted coupons can still be used by all customers
5. Admins can view restricted customers and reset restrictions when needed

**Example Scenario:**
- Restricted coupons: `FIRST10`, `WELCOME10`, `DEAL10`
- Non-restricted coupons: `FREEGIFT`, `SHIPPING50`

Customer Journey:
1. Customer uses `FIRST10` coupon → ✅ Allowed (first use)
2. Customer tries `WELCOME10` coupon → ❌ Blocked (already used restricted coupon)
3. Customer tries `FREEGIFT` coupon → ✅ Allowed (non-restricted coupon)
4. Customer tries `SHIPPING50` coupon → ✅ Allowed (non-restricted coupon)

**Perfect for:**
* First-time customer discounts
* Welcome offers
* One-time promotional codes
* Preventing coupon abuse
* Loyalty program restrictions

== Installation ==

1. Upload the `simple-coupon-restrictions` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Coupon Restrictions to configure your restricted coupons

== Frequently Asked Questions ==

= Does this work with guest customers? =

Yes! As of version 1.1.0, the plugin tracks and restricts both registered and guest customers using email addresses.

= Is this compatible with WooCommerce HPOS? =

Yes! The plugin is fully compatible with WooCommerce High-Performance Order Storage (HPOS).

= Can I reset restrictions for a customer? =

Yes! In the admin panel, you can view all restricted customers (both registered and guest) and reset their restrictions with a single click.

= What happens if I change the restricted coupons list? =

Changes to the restricted coupons list only affect future orders. Existing restrictions remain in place unless manually reset.

= Can customers use non-restricted coupons after being restricted? =

Yes! As of version 1.1.1, customers who have used restricted coupons can still use non-restricted coupons. Only restricted coupons are blocked.

= Does the plugin come with pre-configured coupons? =

No, the plugin starts with a clean slate. You need to add your own restricted coupons through the admin interface.

= How are guest customers identified? =

Guest customers are identified by their email address used during checkout. The plugin stores this information securely in the database.

== Screenshots ==

1. Admin settings page for configuring restricted coupons with AJAX search
2. Customer management table showing restricted customers with guest badges
3. Modern SweetAlert2 confirmation dialogs
4. Customer profile showing restriction status

== Changelog ==

= 1.1.1 =
* Fixed: Issue where customers were blocked from using ALL coupons instead of just restricted ones
* Improved: Now only restricted coupons are blocked for customers with restriction history
* Enhanced: Error messages now clarify that non-restricted coupons can still be used
* Enhanced: Better user experience by allowing legitimate use of non-restricted coupons

= 1.1.0 =
* Added: Guest customer support with email-based tracking
* Added: Email-specific session storage to prevent cross-contamination
* Added: Guest customer identification in admin panel with "Guest" badges
* Added: Database migration system for existing installations
* Added: Automatic cleanup of expired guest restrictions (30 days)
* Added: "Process Existing Orders" functionality to retroactively track customers
* Added: HPOS compatibility enhancements
* Changed: Database structure updated to support guest customers
* Changed: Enhanced admin interface to display both registered and guest customers
* Changed: Updated reset functionality to handle both customer types

= 1.0.0 =
* Initial release
* HPOS compatibility for WooCommerce
* AJAX coupon search with autocomplete
* Modern admin interface with SweetAlert2
* Customer restriction management
* PSR-4 autoloading structure
* No default coupons on activation

== Upgrade Notice ==

= 1.1.1 =
Important fix: Customers can now use non-restricted coupons even after being restricted. Only restricted coupons are blocked. Recommended update for all users.

= 1.1.0 =
Major update: Added guest customer support, database migration system, and enhanced admin interface. Automatic database updates included.

= 1.0.0 =
Initial release of Simple Coupon Restrictions with HPOS compatibility and modern admin interface. 