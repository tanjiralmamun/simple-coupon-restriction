=== Simple Coupon Restrictions ===
Contributors: tanjiralmamun
Tags: woocommerce, coupons, restrictions, customer management, hpos
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track when customers use specific coupons and block them from using other coupons in future orders. HPOS compatible.

== Description ==

Simple Coupon Restrictions is a lightweight WordPress plugin that helps you control coupon usage across multiple orders. When a customer uses a specific coupon (like a first-time customer discount), they are permanently blocked from using any other coupons in future orders.

**Key Features:**

* **Permanent Restrictions**: Once a customer uses a restricted coupon, they cannot use ANY coupons in future orders
* **HPOS Compatible**: Full support for WooCommerce High-Performance Order Storage
* **Easy Management**: Simple admin interface to configure which coupons are restricted
* **AJAX Coupon Search**: Search coupons by both code and description with autocomplete
* **Customer Management**: View and reset restrictions for individual customers
* **Modern UI**: Clean, responsive admin interface with SweetAlert2 notifications
* **WooCommerce Integration**: Seamlessly integrates with WooCommerce
* **Admin Notifications**: Clear warnings when viewing restricted customers
* **No Default Coupons**: Clean installation with no pre-configured restrictions
* **PSR-4 Structure**: Well-organized codebase following modern PHP standards

**How it Works:**

1. Configure which coupon codes should be restricted using the search feature
2. When a customer uses any of these coupons and their order is completed/processed, they are marked as restricted
3. From that point forward, they cannot use any coupons in future orders
4. Admins can view restricted customers and reset restrictions when needed

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

The plugin only tracks restrictions for registered customers. Guest customers are not affected.

= Is this compatible with WooCommerce HPOS? =

Yes! The plugin is fully compatible with WooCommerce High-Performance Order Storage (HPOS).

= Can I reset restrictions for a customer? =

Yes! In the admin panel, you can view all restricted customers and reset their restrictions with a single click.

= What happens if I change the restricted coupons list? =

Changes to the restricted coupons list only affect future orders. Existing restrictions remain in place unless manually reset.

= Can customers use multiple restricted coupons? =

No, once a customer uses any restricted coupon, they are blocked from using any coupons in future orders.

= Does the plugin come with pre-configured coupons? =

No, the plugin starts with a clean slate. You need to add your own restricted coupons through the admin interface.

== Screenshots ==

1. Admin settings page for configuring restricted coupons with AJAX search
2. Customer management table showing restricted customers
3. Modern SweetAlert2 confirmation dialogs
4. Customer profile showing restriction status

== Changelog ==

= 1.0.0 =
* Initial release
* HPOS compatibility for WooCommerce
* AJAX coupon search with autocomplete
* Modern admin interface with SweetAlert2
* Customer restriction management
* PSR-4 autoloading structure
* No default coupons on activation

== Upgrade Notice ==

= 1.0.0 =
Initial release of Simple Coupon Restrictions with HPOS compatibility and modern admin interface. 