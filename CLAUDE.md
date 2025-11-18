# Kargo Takip Türkiye WordPress Plugin

## Plugin Overview

**Kargo Takip Türkiye** is a WooCommerce integration plugin for Turkish cargo/shipment tracking. It allows shop owners to manage shipment tracking information directly within WooCommerce order details and enables automated customer notifications via email and SMS.

### Key Information
- **Plugin Name**: Kargo Takip Türkiye (Cargo Tracking Turkey)
- **Current Version**: 0.2.0
- **Author**: Unbelievable.Digital
- **Repository**: https://github.com/unbelievable-digital/kargo-takip-turkiye
- **Language**: Turkish (UI and documentation)
- **WordPress Requirement**: 4.9+
- **PHP Requirement**: 7.1+
- **WooCommerce Tested**: 7.2.2+
- **License**: GPLv2 or later

---

## Directory Structure

```
kargo-takip-turkiye/
├── .github/
│   └── workflows/
│       └── main.yml                      # GitHub Actions deployment workflow
├── assets/
│   └── logos/                            # Cargo company logos (PNG images)
│       ├── aras.png
│       ├── carrtell.png
│       ├── dhl.png
│       ├── fedex.png
│       ├── filo.png
│       ├── hepsijet.png
│       ├── horoz.png
│       ├── iyi.png
│       ├── mng.png
│       ├── postman.png
│       ├── ptt.png
│       ├── sendeo.png
│       ├── surat.png
│       ├── tex.png
│       ├── tnt.png
│       ├── ups.png
│       └── yurtici.png
├── mail-template/                        # Email template files
│   ├── email-shipment-template.php       # Initial shipment notification
│   └── email-shipment-update-template.php # Shipment update notification
├── .gitignore
├── config.php                            # Cargo company configuration
├── index.php                             # Empty entry point
├── kargo-takip-turkiye.php              # Main plugin file
├── kargo-takip-helper.php               # Helper functions
├── kargo-takip-order-list.php           # Admin order list enhancements
├── kargo-takip-email-settings.php       # Email settings page
├── kargo-takip-sms-settings.php         # SMS settings page
├── kargo-takip-wc-api-helper.php        # WooCommerce REST API endpoint
├── kobikom-helper.php                   # Kobikom SMS provider integration
├── netgsm-helper.php                    # NetGSM SMS provider integration
├── readme.txt                            # WordPress plugin readme
└── LICENSE                               # GPLv2 License

```

---

## Plugin Architecture & Components

### 1. Main Plugin Entry Point
**File**: `kargo-takip-turkiye.php`

The main plugin file that:
- Includes all helper and settings files
- Registers the admin menu and submenus
- Manages WooCommerce hooks and filters
- Registers custom order status "Kargoya verildi" (Shipped via Cargo)
- Handles shipment tracking metadata on orders
- Triggers email and SMS notifications
- Adds shipment details to customer account pages and order confirmations

**Key Functions**:
- `kargoTR_register_admin_menu()` - Registers admin menu structure
- `kargoTR_register_settings()` - Registers plugin option settings
- `kargoTR_setting_page()` - Displays general settings page
- `kargoTR_register_shipment_shipped_order_status()` - Adds custom order status
- `kargoTR_general_shipment_details_for_admin()` - Admin order tracking form
- `kargoTR_tracking_save_general_details()` - Saves tracking data, triggers notifications
- `kargoTR_shipment_details()` - Displays tracking info on customer order page
- `kargoTR_add_kargo_button_in_order()` - Adds tracking button to order actions
- `kargoTR_kargo_bildirim_icerik()` - Renders email notification content
- `kargoTR_kargo_eposta_details()` - Sends email notification

### 2. Configuration
**File**: `config.php`

PHP array containing all supported cargo companies with:
- Company display name
- Tracking URL pattern (base URL + tracking code)
- Logo file path

**Supported Cargo Companies** (20 companies):
- PTT Kargo
- Yurtiçi Kargo
- Aras Kargo
- MNG Kargo
- Horoz Kargo
- UPS Kargo
- Sürat Kargo
- Filo Kargo
- TNT Kargo
- DHL Kargo
- FedEx Kargo
- FoodMan Kargo
- Postman Kargo
- İyi Kargo
- Trendyol Express
- HepsiJET
- Sendeo Kargo
- Carrtell Kargo

### 3. Helper Functions
**File**: `kargo-takip-helper.php`

Core utility functions for cargo data retrieval:
- `kargoTR_get_company_name()` - Get company name by key
- `kargoTR_getCargoTrack()` - Build tracking URL
- `kargoTR_getCargoName()` - Get cargo name (legacy)
- `kargoTR_cargo_company_list()` - Get all companies as associative array
- `kargoTR_get_order_cargo_logo()` - Get company logo for order
- `kargoTR_get_order_cargo_information()` - Get complete cargo info (logo, company, URL)
- `kargoTR_get_sms_template()` - Build SMS message from template with variables

**Template Variables**:
- `{customer_name}` - Customer name
- `{order_id}` - Order ID
- `{company_name}` - Cargo company name
- `{tracking_number}` - Tracking code
- `{tracking_url}` - Tracking URL

### 4. Settings Management

#### General Settings
**File**: `kargo-takip-turkiye.php` (main file)

Registered options:
- `kargo_hazirlaniyor_text` - Show "Preparing shipment" text
- `mail_send_general` - Auto-send email on tracking info
- `sms_provider` - Selected SMS provider (no, NetGSM, Kobikom)
- `sms_send_general` - Auto-send SMS on tracking info

#### Email Settings
**File**: `kargo-takip-email-settings.php`

- Manages email template customization via `kargoTr_email_template` option
- Users can customize email content with template variables
- Default template included in main plugin file

#### SMS Settings
**File**: `kargo-takip-sms-settings.php`

Settings page for SMS integration:
- Select SMS provider (NetGSM or Kobikom)
- Configure provider credentials and API keys
- View account balance and remaining credits
- Customize SMS template with template variables

### 5. SMS Integration

#### NetGSM Provider
**File**: `netgsm-helper.php`

Functions:
- `kargoTR_get_netgsm_headers()` - Fetch available SMS headers
- `kargoTR_get_netgsm_packet_info()` - Get packet information
- `kargoTR_get_netgsm_credit_info()` - Get account credit balance
- `kargoTR_SMS_gonder_netgsm()` - Send SMS via NetGSM API

**Credentials**:
- Subscriber code (without leading 0)
- Password

#### Kobikom Provider
**File**: `kobikom-helper.php`

Functions:
- `kargoTR_get_kobikom_headers()` - Fetch available SMS headers
- `kargoTR_get_kobikom_balance()` - Get account packages/balance
- `kargoTR_SMS_gonder_kobikom()` - Send SMS via Kobikom API

**Credentials**:
- API Token/Key

### 6. Admin Order List Enhancement
**File**: `kargo-takip-order-list.php`

Adds a "Kargo" column to the WooCommerce orders admin page:
- Shows cargo company logo (clickable)
- Links directly to cargo tracking page
- Integrated with `kargoTR_get_order_cargo_information()`

### 7. WooCommerce REST API Integration
**File**: `kargo-takip-wc-api-helper.php`

Provides REST API endpoint for external systems to add/update tracking info:
- **Endpoint**: `POST /wp-json/wc/v3/kargo_takip`
- **Authentication**: Requires logged-in user with `edit_shop_orders` capability
- **Parameters**:
  - `order_id` (required) - Order ID
  - `shipment_company` (required) - Cargo company key
  - `tracking_code` (required) - Tracking number

**Functions**:
- `kargoTR_api_add_tracking_code()` - Main API handler
- `kargoTR_is_valid_shipment_company()` - Validates company
- `kargoTR_is_valid_order_id()` - Validates order

**Features**:
- Adds/updates tracking metadata on orders
- Triggers email notification if enabled
- Triggers SMS notification if configured
- Adds order notes for audit trail

### 8. Email Templates
**Directory**: `mail-template/`

#### email-shipment-template.php
- Initial shipment notification template
- Uses WooCommerce email hooks for consistent styling
- Displays: company name, tracking code, tracking link
- Includes order details via WooCommerce hooks

#### email-shipment-update-template.php
- Similar to shipment template (appears to be duplicate or for updates)

Both templates use:
- `kargoTR_get_company_name()` - Display company
- `kargoTR_getCargoTrack()` - Generate tracking URL
- WooCommerce email hooks for standard content

---

## WordPress Hooks & Actions

### Hooks Used by Plugin

**Custom Actions (defined by plugin)**:
- `order_ship_mail` - Triggers email notification
- `order_send_sms` - Triggers NetGSM SMS notification
- `order_send_sms_kobikom` - Triggers Kobikom SMS notification

**WordPress Actions (hooked by plugin)**:
- `admin_menu` - Register admin menu
- `admin_init` - Register settings
- `init` - Register custom order status
- `admin_head` - Add CSS for tooltips
- `woocommerce_admin_order_data_after_order_details` - Display tracking form
- `woocommerce_process_shop_order_meta` - Save tracking data
- `woocommerce_after_order_details` - Display tracking on customer page
- `rest_api_init` - Register REST API endpoint

**WooCommerce Filters**:
- `wc_order_statuses` - Add custom order status
- `woocommerce_my_account_my_orders_actions` - Add tracking button

**WooCommerce Email Hooks** (in templates):
- `woocommerce_email_header()`
- `woocommerce_email_order_details()`
- `woocommerce_email_order_meta()`
- `woocommerce_email_customer_details()`
- `woocommerce_email_footer()`

---

## Data Storage

### WordPress Post Meta
Stored on WooCommerce orders (posts):
- `tracking_company` - Cargo company key (from config)
- `tracking_code` - Shipment tracking number

### WordPress Options
Settings stored in wp_options table:
- `kargo_hazirlaniyor_text` - (yes/no)
- `mail_send_general` - (yes/no)
- `sms_provider` - (no/NetGSM/Kobikom)
- `sms_send_general` - (yes/no)
- `NetGsm_UserName` - NetGSM subscriber code
- `NetGsm_Password` - NetGSM password
- `NetGsm_Header` - Selected NetGSM SMS header
- `NetGsm_sms_url_send` - Include tracking URL in SMS
- `Kobikom_ApiKey` - Kobikom API token
- `Kobikom_Header` - Selected Kobikom SMS header
- `kargoTr_sms_template` - SMS message template
- `kargoTr_email_template` - Email message template

### Custom Order Status
- **Status Key**: `wc-kargo-verildi`
- **Display Label**: Kargoya Verildi (Shipped via Cargo)

---

## External API Integrations

### NetGSM API
- **Base URL**: https://api.netgsm.com.tr/
- **Endpoints Used**:
  - `/sms/header/` - Get SMS headers
  - `/balance/list/get/` - Get account info
  - `/sms/send/get/` - Send SMS messages
- **Authentication**: Usercode + Password

### Kobikom API
- **Base URL**: https://sms.kobikom.com.tr/api/
- **Endpoints Used**:
  - `/subscription` - Get SMS headers
  - `/balance` - Get account packages
  - `/message/send` - Send SMS messages
- **Authentication**: API Token

---

## Admin Pages Structure

### Menu
```
Kargo Takip (main menu)
├── Genel Ayarlar (General Settings)
├── E-Mail Ayarlari (Email Settings)
└── SMS Ayarlari (SMS Settings)
```

### General Settings Page
- Show "Preparing shipment" text option
- Auto-send email option
- SMS provider selection

### Email Settings Page
- Email template editor (textarea)
- Template variable documentation
- Examples of usage

### SMS Settings Page
- SMS provider selection (NetGSM or Kobikom)
- Provider-specific configuration
- Account balance display
- SMS template editor
- Template variable documentation

---

## Admin Order Editing

When editing a WooCommerce order:
1. Select2 dropdown for cargo company selection
2. Text input for tracking code
3. Automatic order status change to "Kargoya verildi"
4. Triggered notifications (email/SMS based on settings)
5. Automatic order note creation

---

## Development & Deployment

### GitHub Actions Workflow
**File**: `.github/workflows/main.yml`

Automated deployment process:
- **Trigger**: Git tag creation
- **Action**: 10up WordPress Plugin Deploy action
- **Target**: WordPress.org SVN repository
- **Slug**: kargo-takip-turkiye
- **Authentication**: SVN_USERNAME and SVN_PASSWORD secrets

### Version Information
Maintained in `kargo-takip-turkiye.php` header and `readme.txt`

---

## Code Quality & Standards

### Security Practices
- Uses WordPress sanitization: `wc_clean()`, `wc_sanitize_textarea()`
- Uses WordPress escaping: `esc_attr()`, `esc_html__()`
- Capability checks: `current_user_can('edit_shop_orders')`
- Input validation in REST API handler

### WordPress Standards
- Uses WooCommerce plugin functions and classes
- Follows WooCommerce template structure
- Uses appropriate hooks and filters
- Option registration and management

### Coding Patterns
- Functional approach (no classes)
- Include-based module loading
- Configuration-driven cargo list
- Action-based notification system

---

## Known Issues & Notes

### From Version History (readme.txt)

**v0.2.0** (Latest)
- SMS and Email templates now editable from admin panel
- API added for external cargo data input via WooCommerce REST
- Added Carrtell cargo company
- Code structure improvements
- Aras Kargo URL updated

**Previous Improvements**:
- Select2 for company dropdown
- Kobikom SMS provider support
- NetGSM SMS provider support
- Email notification feature
- Custom order status
- Auto SMS/Email on tracking info
- Multiple cargo companies support

---

## Dependencies

### Required
- WordPress 4.9+
- WooCommerce 7.2.2+
- PHP 7.1+

### WordPress Libraries Used
- `wp_remote_get()` - HTTP requests
- `wp_enqueue_script()` - Script loading
- `wp_add_inline_script()` - Inline scripts
- `wc_get_order()` - Order handling
- `get_post_meta()` / `update_post_meta()` - Order metadata
- `get_option()` / `update_option()` - Settings management
- `register_post_status()` - Custom order status
- `register_rest_route()` - REST API
- `register_setting()` - Settings registration

### Frontend Libraries
- Select2 (enqueued via WordPress)
- jQuery (WooCommerce core)

---

## File Dependencies

```
kargo-takip-turkiye.php (main)
├── includes: netgsm-helper.php
├── includes: kargo-takip-helper.php
├── includes: kargo-takip-order-list.php
├── includes: kargo-takip-email-settings.php
├── includes: kargo-takip-sms-settings.php
│   └── includes: kobikom-helper.php
└── includes: kargo-takip-wc-api-helper.php
    └── uses: config.php
```

Helper files also include config.php independently to get cargo data.

---

## Extension Points

### Adding New Cargo Companies
1. Add entry to `config.php` in "cargoes" array
2. Add logo PNG to `assets/logos/`
3. Entry format:
```php
"key" => array(
    "company" => "Company Name",
    "url" => "https://tracking.example.com/?code=",
    "logo" => "assets/logos/key.png"
)
```

### Adding New SMS Providers
1. Create new helper file (e.g., `newprovider-helper.php`)
2. Implement functions:
   - `kargoTR_SMS_gonder_newprovider($order_id)` - Main sender
   - Additional API functions as needed
3. Add action: `add_action('order_send_sms_newprovider', ...)`
4. Update `kargo-takip-sms-settings.php` for UI
5. Update `kargo-takip-turkiye.php` to trigger action

### Customizing Email/SMS Templates
- Use WordPress options to store templates
- Use template variables in curly braces
- Template rendering in `kargoTR_get_sms_template()`
- Email template via `kargoTR_kargo_bildirim_icerik()`

---

## Testing Considerations

### Manual Testing Points
1. **Cargo Selection**: Test Select2 dropdown with multiple companies
2. **Tracking Data**: Verify metadata saved correctly
3. **Email Notification**: Check email sent with correct variables
4. **SMS Notifications**: Test both NetGSM and Kobikom providers
5. **API Endpoint**: Test POST request with valid/invalid data
6. **Order Status**: Verify "Kargoya verildi" status applied
7. **Admin Pages**: Test all three settings pages
8. **Customer View**: Verify tracking info displays on customer account

### API Testing
```bash
POST /wp-json/wc/v3/kargo_takip
Content-Type: application/x-www-form-urlencoded

order_id=123&shipment_company=ptt&tracking_code=XXXXX
```

---

## Recent Changes (v0.2.0)

- **Email/SMS Templates**: Now editable from admin panel instead of hardcoded
- **REST API**: Added WooCommerce REST API endpoint for external integration
- **Carrtell Integration**: New cargo company added
- **Code Refactoring**: Improved code organization and readability
- **URL Updates**: Aras Kargo tracking URL changed

---

## License

GPL v2 or later - See LICENSE file

---

## Support & Contribution

**Repository**: https://github.com/unbelievable-digital/kargo-takip-turkiye  
**Author**: Unbelievable.Digital  
**WordPress.org Page**: https://wordpress.org/plugins/kargo-takip-turkiye/

---

**Last Updated**: November 2024
**Document Version**: Based on Plugin v0.2.0

## Rules

  You are an expert in WordPress, PHP, and related web development technologies.
  
  Key Principles
  - Write concise, technical responses with accurate PHP examples.
  - Follow WordPress coding standards and best practices.
  - Use object-oriented programming when appropriate, focusing on modularity.
  - Prefer iteration and modularization over duplication.
  - Use descriptive function, variable, and file names.
  - Use lowercase with hyphens for directories (e.g., wp-content/themes/my-theme).
  - Favor hooks (actions and filters) for extending functionality.
  
  PHP/WordPress
  - Use PHP 7.4+ features when appropriate (e.g., typed properties, arrow functions).
  - Follow WordPress PHP Coding Standards.
  - Use strict typing when possible: declare(strict_types=1);
  - Utilize WordPress core functions and APIs when available.
  - File structure: Follow WordPress theme and plugin directory structures and naming conventions.
  - Implement proper error handling and logging:
    - Use WordPress debug logging features.
    - Create custom error handlers when necessary.
    - Use try-catch blocks for expected exceptions.
  - Use WordPress's built-in functions for data validation and sanitization.
  - Implement proper nonce verification for form submissions.
  - Utilize WordPress's database abstraction layer (wpdb) for database interactions.
  - Use prepare() statements for secure database queries.
  - Implement proper database schema changes using dbDelta() function.
  
  Dependencies
  - WordPress (latest stable version)
  - Composer for dependency management (when building advanced plugins or themes)
  
  WordPress Best Practices
  - Use WordPress hooks (actions and filters) instead of modifying core files.
  - Implement proper theme functions using functions.php.
  - Use WordPress's built-in user roles and capabilities system.
  - Utilize WordPress's transients API for caching.
  - Implement background processing for long-running tasks using wp_cron().
  - Use WordPress's built-in testing tools (WP_UnitTestCase) for unit tests.
  - Implement proper internationalization and localization using WordPress i18n functions.
  - Implement proper security measures (nonces, data escaping, input sanitization).
  - Use wp_enqueue_script() and wp_enqueue_style() for proper asset management.
  - Implement custom post types and taxonomies when appropriate.
  - Use WordPress's built-in options API for storing configuration data.
  - Implement proper pagination using functions like paginate_links().
  
  Key Conventions
  1. Follow WordPress's plugin API for extending functionality.
  2. Use WordPress's template hierarchy for theme development.
  3. Implement proper data sanitization and validation using WordPress functions.
  4. Use WordPress's template tags and conditional tags in themes.
  5. Implement proper database queries using $wpdb or WP_Query.
  6. Use WordPress's authentication and authorization functions.
  7. Implement proper AJAX handling using admin-ajax.php or REST API.
  8. Use WordPress's hook system for modular and extensible code.
  9. Implement proper database operations using WordPress transactional functions.
  10. Use WordPress's WP_Cron API for scheduling tasks.
  