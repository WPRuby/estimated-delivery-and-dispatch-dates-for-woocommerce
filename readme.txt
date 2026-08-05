=== WPRuby Delivery Estimates for WooCommerce ===
Contributors: wprubyplugins
Donate link: https://wpruby.com/
Tags: woocommerce, delivery date, estimated delivery date, shipping date, delivery time
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Product page delivery estimates for WooCommerce using processing days, transit days, and working days.

== Description ==

WPRuby Delivery Estimates for WooCommerce shows simple delivery and dispatch estimates on WooCommerce product pages using your configured processing days, transit days, and working days.

Works with WooCommerce. Delivery estimates for WooCommerce product pages are calculated locally from your store settings — no external APIs and no license key.

WPRuby Delivery Estimates for WooCommerce is an independent plugin and is not affiliated with, endorsed by, or sponsored by WooCommerce, Automattic, or WordPress.

Example messages:

* Order today and get it between July 15 – July 18.
* Usually ships in 2–4 business days.

**Features:**

* Product page delivery estimate
* Processing and transit day settings
* Working days (Monday–Sunday)
* Optional cutoff time
* Up to 5 excluded dates
* Customizable message template with placeholders
* Plain or highlighted display style
* Placement options on the product page

**Message template placeholders:**

* `{earliest_date}` — earliest delivery date
* `{latest_date}` — latest delivery date
* `{processing_days}` — configured processing days
* `{min_transit_days}` / `{max_transit_days}` — transit day range

**Source code:** The Vue admin UI source is included under `assets/admin/vue/`. The public repository is https://github.com/WPRuby/estimated-delivery-and-dispatch-dates-for-woocommerce

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wpruby-delivery-estimates`, or install through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen.
3. Ensure WooCommerce is installed and active.
4. Go to **WooCommerce → Estimated Delivery** to configure settings.
5. Enable estimated delivery dates and set your processing days, transit days, and working days.
6. View an in-stock product page to see the estimate.

== Frequently Asked Questions ==

= Does this show delivery estimates on product pages? =

Yes. The plugin displays a simple delivery or dispatch estimate on WooCommerce product pages.

= Does this plugin support cart and checkout estimates? =

No. This version focuses on product page estimates only.

= Does this plugin include countdown timers? =

No. You can set an optional cutoff time that affects same-day dispatch calculations, but there is no countdown timer UI.

= Does this plugin use external APIs? =

No. The plugin calculates delivery estimates locally using your configured processing days, transit days, and working days.

= Does it work without WooCommerce? =

No. WooCommerce must be installed and active.

== Screenshots ==

1. Admin settings — General tab
2. Admin settings — Estimate tab
3. Admin settings — Display tab with preview
4. Product page delivery estimate

== Changelog ==

= 1.0.0 =
* Initial release for WordPress.org
* Product page delivery estimates with processing days, transit days, and working days
* Simple admin UI with General, Estimate, and Display tabs

== Upgrade Notice ==

= 1.0.0 =
Initial release for WordPress.org.
