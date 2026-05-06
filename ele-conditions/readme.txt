=== Ele Conditions for Elementor ===
Contributors: dudaster
Tags: elementor, triggers, interactions, conditional, visibility
Donate link: https://www.paypal.me/dudaster
Requires at least: 5.9
Tested up to: 6.9
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add interactive triggers and conditional display logic to any Elementor element — show, hide, toggle on click, scroll, exit intent, and more.

== Description ==

**Ele Conditions for Elementor** turns static Elementor elements into interactive, context-aware components — without writing a single line of code.

There are two independent systems, both accessible from the **Advanced tab** of any widget, section, or container:

---

= ⚡ Triggers & Actions (client-side, no page reload) =

Attach one or more trigger → action pairs to any element.

**10 trigger types:**

* **Click** — user clicks the element
* **Hover** — user hovers over the element
* **Delay on load** — fires after X milliseconds from page load
* **Scroll into view** — fires once when the element enters the viewport
* **Time on page** — fires after X seconds the visitor has been on the page
* **Exit intent** — fires when the cursor moves toward closing the tab
* **First visit** — fires only on the visitor's very first page load (localStorage)
* **Nth visit** — fires on the Nth page load (visit counter in localStorage)
* **A/B Group A / B** — fires for visitors randomly assigned to group A or B (persists via localStorage)

**9 action types:**

* **Show** — make the target element visible
* **Hide** — hide the target element
* **Toggle** — toggle visibility on each trigger
* **Add / Remove / Toggle Class** — apply a CSS class (great for CSS animations)
* **Scroll To** — smooth-scroll the page to the target element
* **Focus** — set browser focus on the target (input fields, etc.)
* **Close Others in Group** — hide all siblings sharing a CSS class (accordion pattern)

**Target selector:** leave empty to act on the element itself, or enter any CSS selector to act on another element anywhere on the page.

**Hide Initially:** a dedicated switcher that hides the element on load independently of triggers — useful for elements revealed by a trigger.

---

= 🎛 Display Conditions (server-side, evaluated at render time) =

Show or hide elements based on variables, rules, and time windows. Multiple conditions per element with AND / OR logic.

**Built-in variables** include: `user_role`, `is_logged_in`, `user_id`, `post_type`, `post_status`, `post_age_days`, `post_word_count`, `post_has_thumbnail`, `cart_count`, `cart_total`, `current_hour`, `current_day`, `current_month`, `current_year`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, and more.

**Condition types:**

* **Simple** — compare a variable to a value using ==, !=, >, <, >=, <=, contains, starts_with, ends_with, empty, not_empty
* **Time interval** — show element only between two times of day (supports cross-midnight ranges)
* **Date/datetime interval** — show element only within a date or datetime window

**Custom variables** — register your own variables via the `eleconditions_vars` filter in your theme's `functions.php`.

**ACF support** — pick any ACF field from a searchable dropdown; the value is fetched at render time.

**User meta** — condition on any WordPress user meta key for the current visitor.

**Debug mode** — shows a red-bordered overlay with the evaluated variable values, visible only to editors and administrators.

---

= Developer-friendly =

* Zero dependencies beyond Elementor
* Fully filterable variable system
* No inline styles injected into saved content — everything is evaluated at render

Note: Requires the free [Elementor](https://wordpress.org/plugins/elementor/) plugin.

== Installation ==

1. Install and activate [Elementor](https://wordpress.org/plugins/elementor/).
2. Upload the plugin through **Plugins → Add New → Upload Plugin**, or install directly from the WordPress.org directory.
3. Activate **Ele Conditions for Elementor**.
4. Open any widget, section, or container in Elementor → **Advanced tab** → look for **Ele Conditions** (display conditions) and **Triggers** (interactive triggers).

== Frequently Asked Questions ==

= Where do I find the Triggers panel? =

Open any widget or section in Elementor, go to the **Advanced** tab, and scroll to the **Triggers** section.

= Where do I find the Display Conditions panel? =

Same Advanced tab, scroll to the **Ele Conditions** section — just above Triggers.

= Can I trigger an action on a different element? =

Yes. Enter a CSS selector in the **Target** field (e.g. `.my-popup` or `#hero-button`). Leave it empty to target the element itself.

= How does A/B testing work? =

On first page load the visitor is randomly assigned to group A or B. That assignment is saved in `localStorage` and stays consistent across sessions. Use **A/B Group A** and **A/B Group B** triggers on different elements to show different content to each group.

= How do I add my own variables? =

Add a filter in your theme's `functions.php`:

`add_filter( 'eleconditions_vars', function( $vars ) { $vars['my_var'] = 'some_value'; return $vars; } );`

Then select **Custom** in the variable dropdown and type `my_var`.

= Does it work with Elementor containers (Flexbox)? =

Yes — triggers and conditions work on widgets, sections, and containers.

= Is there a performance impact? =

Display conditions are evaluated server-side at render time — no JavaScript needed for them. The triggers script (`triggers.js`) is a single lightweight IIFE loaded in the footer only when needed.

== Screenshots ==

1. /assets/screenshot-1.png

== Changelog ==

= 2.0.0 =
* Major release — Triggers system promoted to primary feature.
* Rename tab label from "Triggered" to "Triggers".
* Add 7 new trigger types: Scroll into view, Time on page, Exit intent, First visit, Nth visit, A/B Group A, A/B Group B.
* Add "Hide Initially" switcher (works independently of trigger list).
* Add UTM / query-string variables: utm_source, utm_medium, utm_campaign, utm_content, utm_term.
* Add post-relative variables: post_age_days, post_has_thumbnail, post_word_count.
* Add user meta condition type.
* localStorage wrapped in try/catch for Safari Private Browsing compatibility.
* A/B group assignments and visit counts persist via localStorage.
* Restructure project as monorepo (plugin/ → ele-conditions/ subfolder).

= 1.0.14 =
* Move Ele Conditions section from Style tab to Advanced tab.
* Rename section label from "Conditions" to "Ele Conditions".

= 1.0.13 =
* Add Triggers tab in Advanced panel: Click, Hover, Delay on load triggers; Show, Hide, Toggle, Add/Remove/Toggle Class, Scroll To, Focus, Close Others actions.

= 1.0.12 =
* Replace date/time text inputs with native datetime-local picker.

= 1.0.11 =
* Add ACF/meta field dropdown (SELECT2) populated from database and ACF field groups.
* Add content_length and excerpt_length variables.

= 1.0.10 =
* Add date/datetime interval condition type (inclusive, WordPress timezone).

= 1.0.9 =
* Add time interval condition type with cross-midnight support.
* Add built-in variables: comment_count, post_author, post_status, post_type, user_id, user_role, is_logged_in, current_hour/day/month/year/date, cart_count, cart_total.
* Add ACF field support.

= 1.0.8 =
* New visual conditions builder with REPEATER UI — no more manual text input.
* Add SELECT for variables and operators.
* Support multiple conditions with AND/OR logic.

= 1.0.7 =
* Security and compatibility fixes: ABSPATH guard, output escaping, gmdate(), license header.

= 1.0.6 =
* Rename plugin to comply with WordPress.org trademark guidelines.
* Fix text domain to match plugin slug.

= 1.0.5 =
* Coerce variable to 0 when compared against a number.

= 1.0.4 =
* Bug fixes.

= 1.0.3 =
* Fix PHP notices.
* Handle var == null when variable has no value.

= 1.0.2 =
* Fix sections with latest Elementor version.
* Debug mode shows semi-opaque red-bordered overlay for editors/admins.

= 1.0.1 =
* Add debug mode (admin/editor only).
* Fix sections support.

= 1.0.0 =
* Initial release.
