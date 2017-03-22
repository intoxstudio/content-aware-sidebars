=== Plugin Name ===
Contributors: intoxstudio, devinstitute, freemius
Donate link:
Tags: sidebar, sidebars, custom sidebars, page sidebars, replace sidebar, create sidebars, replace widget area, bbpress, buddypress, polylang, pods, conditional
Requires at least: 3.9
Tested up to: 4.7
Stable tag: 3.4.3
License: GPLv3

Display custom sidebars on any post, page, category etc. Supports bbPress, BuddyPress, WooCommerce, Easy Digital Downloads and more.

== Description ==

#### The Best Sidebar Plugin for WordPress

With Content Aware Sidebars you can create post sidebars, page sidebars, category sidebars, or any custom sidebar you need. Use it to boost on-site SEO, upsell your products or get better-converting Call-to-Actions by displaying different sidebars on different conditions.

The sidebar manager makes it incredibly easy for you to create tailored widget areas in any theme. Developed with scalability and performance in mind, Content Aware Sidebars is the only plugin of its kind that will never slow down your site no matter the size.

No coding required!

####Create Unlimited Sidebars and Widget Areas

* Easy-to-use Sidebar Manager
* Create or select sidebars directly when editing a post or page
* Merge with, replace and hide sidebars in any theme
* Activate and deactivate sidebars on schedule
* Sidebar Visibility for All or Logged-in Users
* Enhanced Widgets Admin Screen
* Automatic support for Custom Post Types and Taxonomies
* Optional Template Tag to display custom sidebars anywhere in your theme
* Optional Shortcode to display custom sidebars anywhere in your content
* Multilingual and Translation Ready ([help translate!](https://translate.wordpress.org/projects/wp-plugins/content-aware-sidebars))

####Display Different Sidebars on Any Content

* Singulars, eg. each post, page, or custom post type
* Content with select taxonomies, eg. categories or tags
* Content written by a select author
* Page Templates
* Post Type Archives
* Author Archives
* (Custom) Taxonomy Archives
* Date Archives
* Search Results
* 404 Not Found Page
* Front Page
* Blog Page
* bbPress User Profiles
* BuddyPress Member Pages
* Languages (qTranslate X, Polylang, Transposh, WPML)
* Pods Pages

Content Aware Sidebars is the only plugin that allows you to combine conditions in any way you like, so you can display a custom sidebar on all posts in Category X written by author Y. It is also possible to negate the conditions, e.g. to display a sidebar on all pages except Page X.

####Plugin Integrations and Support

* [bbPress](https://dev.institute/wordpress/sidebars-pro/bbpress/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)
* [BuddyPress](https://dev.institute/wordpress/sidebars-pro/buddypress/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)
* [Easy Digital Downloads](https://dev.institute/wordpress/sidebars-pro/easy-digital-downloads/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)
* [qTranslate X](https://dev.institute/wordpress/sidebars-pro/multilingual-plugins/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)
* [Pods](https://dev.institute/wordpress/sidebars-pro/pods/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)
* [Polylang](https://dev.institute/wordpress/sidebars-pro/multilingual-plugins/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)
* [Transposh Translation Filter](https://dev.institute/wordpress/sidebars-pro/multilingual-plugins/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)
* [WooCommerce](https://dev.institute/wordpress/sidebars-pro/woocommerce/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)
* [WPML](https://dev.institute/wordpress/sidebars-pro/multilingual-plugins/?utm_source=readme&utm_medium=referral&utm_content=integration&utm_campaign=cas)

> ####Content Aware Sidebars Pro
>
> [Complete control for your custom sidebars](https://dev.institute/wordpress/sidebars-pro/?utm_source=readme&utm_medium=referral&utm_content=title&utm_campaign=cas):
>
> * Priority Email Support
> * Automatic Widgets Backup
> * Display sidebars on URLs + wildcards
> * Display sidebars on content from any day, month, year
> * Display sidebars in select time ranges on given days
> * Sidebar Visibility for Roles and Guests
> * 1-click Sidebar Activation
> * Sync Widgets on Theme Switch
> * [and more...](https://dev.institute/wordpress/sidebars-pro/features/?utm_source=readme&utm_medium=referral&utm_content=more&utm_campaign=cas)
>
> You can upgrade at any time directly and securely from your Admin Dashboard via [Freemius](http://freemius.com/)!

####More Information

* [Documentation](https://dev.institute/docs/content-aware-sidebars/?utm_source=readme&utm_medium=referral&utm_content=info&utm_campaign=cas)
* [Github](https://github.com/intoxstudio/content-aware-sidebars)
* [Twitter](https://twitter.com/intoxstudio)

== Installation ==

1. Unzip and upload the `content-aware-sidebars` folder to your `/wp-content/plugins/` directory via FTP or install the plugin through *Plugins* in the administration 
1. Activate the plugin through *Plugins* in the administration
1. Create your first sidebar under the menu *Sidebars > Add New*
1. Add widgets to the sidebar like any other sidebar

[Click here to get started with Content Aware Sidebars.](https://dev.institute/docs/content-aware-sidebars/getting-started/?utm_source=readme&utm_medium=referral&utm_content=install&utm_campaign=cas)

* Optional: Insert Template Tag `<?php ca_display_sidebar( $args ); ?>` in your theme for manually handled sidebars
* Optional: Insert Shortcode `[ca-sidebar id=]` in a post or page for manually handled sidebars

== Frequently Asked Questions ==

[Click here to view the FAQs for Content Aware Sidebars.](https://dev.institute/docs/content-aware-sidebars/faq/?utm_source=readme&utm_medium=referral&utm_content=faq&utm_campaign=cas)

== Screenshots ==

[Click here to view the latest screenshots and examples of Content Aware Sidebars.](https://dev.institute/wordpress/sidebars-pro/?utm_source=readme&utm_medium=referral&utm_content=screenshots&utm_campaign=cas)

== Upgrade Notice ==

= 3.4 = 

* Content Aware Sidebars data in your database will be updated automatically. It is highly recommended to backup this data before updating the plugin.
* Data from version 0.8 and below will not be updated during this process.

== Changelog ==

[View development on GitHub](https://github.com/intoxstudio/content-aware-sidebars)

= 3.4.3 =

* Added: preparation for automatic translation packages
* Added: prevent adding duplicate sidebar titles
* Fixed: sidebar quick select would in rare cases not show sidebars
* Fixed: UI improvements

= 3.4.2 =

* Added: freemius sdk updated
* Fixed: sidebar editor now works properly in IE browser
* Fixed: UI improvements

= 3.4.1 =

* Added: ability to target all buddypress profile sections
* Added: freemius opt-in message made more clear
* Added: links to docs
* Added: wordpress 4.7 support
* Fixed: sidebar order not being saved
* Fixed: "Automatically add new children of a selected ancestor" not working for post types
* Fixed: sidebar edit links on widgets screen

**Pro Plan:**

* Fixed: buddypress groups condition not selectable on new sidebars

= 3.4 =

* Added: sidebar list and editor screens completely rewritten for performance and extensibility
* Added: ability to schedule sidebar deactivation
* Added: exposure moved to condition groups, now called singulars or archives
* Added: freemius sdk updated
* Added: data update process will no longer be triggered on new installs
* Added: sidebar status now active/inactive instead of publish/draft
* Added: always load latest version of wp-content-aware-engine
* Fixed: sidebar quick select compatibility with other sidebar managers
* Removed: deprecated function display_ca_sidebar (use ca_display_sidebar)

**Pro Plan:**

* Added: display sidebars in time ranges on given days
* Fixed: initial widget revision could in some cases be malformed
* Fixed: bug when adding url and date conditions
* Fixed: bug with license activation if user had opted out of freemius

= 3.3.3 =

* Added: counter-measure against plugins that add buggy scripts
* Fixed: saving sidebars in quick select would in some cases trigger warning

**Pro Plan:**

* Fixed: include draft sidebars when syncing widgets across themes

= 3.3.2 =

* Fixed: markup in quick select could in some cases be malformed
* Fixed: warning when saving sidebar

= 3.3.1 =

* Added: ux design improvements
* Added: ability to add more sidebars in quick select
* Added: toggle to display more than 3 sidebar input fields in quick select
* Added: select2 dropdowns updated to 4.0.3 on sidebar edit screen
* Added: re-enabled info box on sidebar edit screen
* Added: freemius sdk updated
* Fixed: decoding of taxonomy term names in conditions
* Fixed: order of content in conditions dropdowns
* Fixed: yoast seo compatibility on post edit screens
* Fixed: negated post conditions were included in sidebar quick select
* Removed: upgrade box on sidebar edit screen

**Pro Plan:**

* Fixed: improved widget revision ux
* Fixed: select dates would in some cases not be displayed correct in conditions
* Fixed: widget revisions could in some cases contain wrong or malformed data
* Fixed: backwards compat in widget revision ui for versions before wp4.5

= 3.3 =

* Added: manage widgets for draft sidebars
* Added: quick select and create sidebars on post type editor screen
* Added: ability to add widgets to draft sidebars
* Added: expand/collapse all sidebars on widgets screen
* Added: view sidebar status on widgets screen
* Added: order sidebars by title on widgets screen
* Added: dialog on unsaved condition changes in sidebar editor
* Added: ux design improvements
* Added: more focus on pro features, upgrade box moved to bottom
* Fixed: error in wpml config (props Chouby)
* Fixed: updated review notice description
* Fixed: select2 dropdowns styling more robust to external changes
* Fixed: minor performance improvements

**Pro Plan:**

* Added: set sidebars to published or draft on widgets screen

= 3.2.4 =

* Added: infinite scroll for content in sidebar editor
* Added: guard when activating both free and pro version, uninstall cleanup not run if one is active
* Added: support for buddypress 2.6 members
* Added: wordpress 4.6 support
* Fixed: option to select all authors and bbpress profiles
* Fixed: simplified introduction tour
* Fixed: uninstall cleanup for users not on freemius

**Pro Plan:**

* Fixed: load buddypress group module correctly
* Fixed: search for buddypress groups

= 3.2.3 =

* Fixed: wp function is_user_logged_in would in some cases not be defined in time

= 3.2.2 =

* Fixed: pages with no custom sidebars could cause malformed sql query

= 3.2.1 =

* Fixed: quick edit link removed from other post types

= 3.2 =

* Added: performance improvements
* Added: drastically reduced database queries when checking taxonomies
* Added: visibility option for all and logged-in users
* Added: visibility column in sidebar overview
* Added: combined handle and merge position columns
* Added: freemius integration
* Added: wp filters to add and populate metadata
* Added: minimum requirement wp3.9
* Fixed: improved sidebar editor ux
* Fixed: display correct template tag for manual handled sidebars in editor
* Fixed: wpml config
* Removed: sidebar quick edit
* Removed: ability to set private post status (in favor of visibility option)
* Removed: donation link from readme

**Pro Plan:**

* Added: sidebar and widget revisions
* Added: extended visibility for roles and guests
* Added: condition for URLs + wildcards
* Added: condition for content from any day, month, year
* Added: condition for buddypress groups
* Added: sidebar meta box for post types
* Added: sidebar column for post type overviews
* Added: widget synchronization across themes
* Added: white label admin screens

= 3.1.2 =

* Added: wordpress 4.5 support
* Added: module javascript more extensible
* Fixed: styling for autocomplete input fields
* Fixed: updated translations

= 3.1.1 =

* Added: pods pages module, props @sc0ttkclark @herold
* Fixed: better compat when other themes or plugins load breaking scripts

= 3.1 =

* Added: completely rewritten sidebar editor ui
* Added: refactored plugin into more logical abstractions
* Added: wp-db-updater now handles database updates
* Added: tour reflects new sidebar editor ui
* Added: review notice after 2 weeks use
* Added: admin footer text on relevant pages
* Added: minified some scripts
* Added: qtranslate x module
* Fixed: sidebar and widget filtering for wp4.4+
* Fixed: bug making attachments not selectable
* Fixed: bumped versions for integrated plugins
* Removed: qtranslate module

See changelog.txt for previous changes.