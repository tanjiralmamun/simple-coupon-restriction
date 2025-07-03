# Simple Coupon Restrictions

A WordPress plugin for WooCommerce that restricts customers from using any coupons after they've used specific "restricted" coupons.

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)
![HPOS](https://img.shields.io/badge/HPOS-Compatible-orange.svg)

## 🚀 Features

- **Permanent Restrictions**: Once a customer uses a restricted coupon, they cannot use ANY coupons in future orders
- **HPOS Compatible**: Full support for WooCommerce High-Performance Order Storage
- **AJAX Coupon Search**: Search coupons by both code and description with autocomplete
- **Modern Admin Interface**: Clean, responsive design with SweetAlert2 notifications
- **Customer Management**: View and reset restrictions for individual customers
- **No Default Coupons**: Clean installation with no pre-configured restrictions
- **PSR-4 Structure**: Well-organized codebase following modern PHP standards
- **Security First**: Proper nonce verification and input sanitization

## 📋 Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## 🛠️ Installation

### From WordPress Admin

1. Download the plugin zip file
2. Go to **Plugins > Add New** in your WordPress admin
3. Click **Upload Plugin** and select the zip file
4. Activate the plugin
5. Go to **WooCommerce > Coupon Restrictions** to configure

### Manual Installation

1. Upload the `simple-coupon-restrictions` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Configure your restricted coupons in **WooCommerce > Coupon Restrictions**

## 🎯 How It Works

1. **Configure Restrictions**: Use the AJAX search to find and add coupons to your restricted list
2. **Automatic Tracking**: When customers use restricted coupons and complete orders, they're automatically flagged
3. **Future Blocking**: Restricted customers cannot use any coupons in future orders
4. **Admin Management**: View restricted customers and reset restrictions when needed

## 🔧 Configuration

### Adding Restricted Coupons

1. Navigate to **WooCommerce > Coupon Restrictions**
2. Use the search box to find coupons by code or description
3. Click **Add Selected Coupon** to add to restricted list
4. Save settings

### Managing Restricted Customers

- View all customers who have used restricted coupons
- Reset restrictions for individual customers
- See which coupon triggered the restriction

## 🏗️ Technical Details

### File Structure

```
simple-coupon-restrictions/
├── simple-coupon-restrictions.php    # Main plugin file
├── readme.txt                        # WordPress plugin readme
├── README.md                         # This file
├── .cursorrules                      # Development rules
├── assets/
│   ├── css/admin.css                 # Admin styles
│   └── js/admin.js                   # Admin JavaScript
└── includes/
    ├── Core/
    │   ├── Plugin.php                # Main plugin class
    │   ├── Activator.php             # Plugin activation
    │   ├── CouponTracker.php         # Order tracking
    │   └── RestrictionChecker.php    # Coupon validation
    ├── Admin/
    │   ├── AdminController.php       # Admin functionality
    │   └── AdminView.php             # Admin interface
    └── Ajax/
        └── AjaxHandler.php           # AJAX endpoints
```

### Database Schema

The plugin creates a custom table `wp_scr_restricted_customers`:

```sql
CREATE TABLE wp_scr_restricted_customers (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    customer_id bigint(20) NOT NULL,
    coupon_code varchar(255) NOT NULL,
    order_id bigint(20) NOT NULL,
    restricted_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY customer_id (customer_id),
    KEY coupon_code (coupon_code),
    KEY order_id (order_id)
);
```

### HPOS Compatibility

The plugin is fully compatible with WooCommerce High-Performance Order Storage:

- Uses `wc_get_order()` for order retrieval
- Hooks into `woocommerce_order_status_changed`
- Declares compatibility with `custom_order_tables`
- Supports both legacy and HPOS storage methods

### Hooks and Filters

#### Actions
- `woocommerce_order_status_changed` - Tracks coupon usage
- `woocommerce_order_status_completed` - HPOS compatibility
- `woocommerce_order_status_processing` - HPOS compatibility

#### Filters
- `woocommerce_coupon_is_valid` - Validates coupon restrictions

## 🔒 Security

- All admin actions use WordPress nonces
- User input is sanitized and validated
- Database queries use prepared statements
- Proper capability checks for admin functions

## 🎨 UI/UX Features

- **SweetAlert2**: Modern, responsive alert dialogs
- **AJAX Search**: Real-time coupon search with autocomplete
- **Responsive Design**: Works on all device sizes
- **WordPress Standards**: Follows WordPress admin design patterns

## 🐛 Troubleshooting

### Common Issues

**Plugin doesn't track restrictions**
- Ensure WooCommerce is active
- Check that orders are reaching "completed" or "processing" status
- Verify customer is logged in during checkout

**AJAX search not working**
- Check browser console for JavaScript errors
- Ensure jQuery and jQuery UI are loaded
- Verify admin-ajax.php is accessible

**Database errors**
- Check WordPress database permissions
- Ensure plugin activation completed successfully
- Try deactivating and reactivating the plugin

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow WordPress coding standards
- Use PSR-4 autoloading
- Include proper documentation
- Test with both legacy and HPOS order storage
- Ensure backward compatibility

## 📝 License

This project is licensed under the GPLv2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, please:

1. Check the [FAQ section](#-faq)
2. Search existing [GitHub issues](https://github.com/yourusername/simple-coupon-restrictions/issues)
3. Create a new issue if needed

## 📚 FAQ

### Does this work with guest customers?

No, the plugin only tracks restrictions for registered customers. Guest customers are not affected.

### Can I reset restrictions for a customer?

Yes! In the admin panel, you can view all restricted customers and reset their restrictions with a single click.

### What happens if I change the restricted coupons list?

Changes only affect future orders. Existing restrictions remain in place unless manually reset.

### Does the plugin come with pre-configured coupons?

No, the plugin starts with a clean slate. You need to add your own restricted coupons through the admin interface.

---

**Made with ❤️ for the WordPress community** 