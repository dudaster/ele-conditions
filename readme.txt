=== Ele Conditions for Elementor ===
Contributors: dudaster
Tags: page-builder, elementor, condition, conditional, conditional elements
Donate link: https://www.paypal.me/dudaster
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.14
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add conditional display logic to Elementor elements and widgets based on custom conditions.

You need Elementor plugin to use this plugin.

== Description ==

This plugin adds the ability to add conditions to sections and widgets in order to be shown or not.

You can add your own values in your function.php using add_filter().

This plugin is currently pretty basic, you can only use only 2 operands (variables) and one operator (the stuff beetwen to values in a condition). No brackets and no AND / OR operators for now.

The *Custom Fields* are preloaded in plugin, so all you need is to enter the slug and it will work. If you're not sure they work please check *Debug mode* and the variable should be replaced with the value.

The Condition field can be found in last section in *Style tab* of a widget.

For more details and examples check our official site https://www.eletemplator.com/

Note: This plugin is an addon of Elementor Page Builder (https://wordpress.org/plugins/elementor/) and will only work with Elementor Page Builder installed.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress


== Frequently Asked Questions ==

= I quit is too complicated! Is there a documentation for this? =

Indeed the plugin is more dev oriented, that means it has a learning curve to it. On the other side you can achieve pretty much everything you need with it. For questions and examples please refer to https://www.eletemplator.com/ and search for the plugin.

= Where can I find the Condition field? =

The Condition field can be found in last section in Style tab of a widget.

= Can I use complex conditions? =

No. For the moment you can only do a basic condition with 2 operands and 1 comparison operator.

== Screenshots ==

1. /assets/screenshot-1.png

== Changelog ==

= 1.0.14 =
* Move Ele Conditions section from Style tab to Advanced tab.
* Rename section label from "Conditions" to "Ele Conditions" to avoid confusion with Elementor's built-in conditions.

= 1.0.13 =
* Add "Triggered" tab in Elementor Advanced panel with Triggers (Click, Hover, Delay on load) and Actions (Show, Hide, Toggle, Add/Remove/Toggle Class, Scroll To, Focus, Close Others in Group).

= 1.0.12 =
* Replace date/time text inputs with native datetime-local picker control.

= 1.0.11 =
* Add ACF/meta field dropdown (SELECT2) populated from database and ACF field groups.
* Add content_length and excerpt_length variables (character count).

= 1.0.10 =
* Add date/time interval condition type (YYYY-MM-DD or YYYY-MM-DD HH:MM, inclusive, WordPress timezone).

= 1.0.9 =
* Add time interval condition type with cross-midnight support.
* Add built-in variables: comment_count, post_author, post_status, post_type, user_id, user_role, is_logged_in, current_hour, current_day, current_month, current_year, current_date, cart_count, cart_total.
* Add ACF field support for posts (get_field on post ID).
* Use WordPress timezone for all date/time variables.

= 1.0.8 =
* New visual conditions builder with REPEATER UI — no more manual text input.
* Add SELECT for variables (ID, name, post_excerpt, description, permalink, content, now, custom).
* Add SELECT for operators with labels.
* Support multiple conditions with AND/OR logic per row.

= 1.0.7 =
* Add ABSPATH guard to inc/parse_conditions.php.
* Escape all output in debug function (esc_html/esc_attr).
* Fix date() to gmdate() for timezone consistency.
* Fix license header to match readme (GPL-2.0-or-later).
* Fix plugin name mismatch between header and readme.
* Shorten short description.

= 1.0.6 =
* Rename plugin display name to comply with WordPress.org trademark guidelines.
* Fix text domain to match plugin slug (ele-conditions).
* Update Tested up to WordPress 6.9.
* Add direct file access protection to inc/controls.php.

= 1.0.5 =
* Now it set the value of variabile to 0 if is compared to a number.

= 1.0.4 =
* Fixed bugs.

= 1.0.3 =
* Fix notice errors.
* Works with var == null when variable doesn't have value.

= 1.0.2 =
* Fix sections with the latest Elementor version.
* Hide the widget wrapper.
* Debug mode will show a semi-opaque, bordered in red, content for editors and administrators.

= 1.0.1 =
* Added debug mode - seen only by admin and editor
* Fix by @hakkow to work with sections

= 1.0.0 =
* Initial Launch