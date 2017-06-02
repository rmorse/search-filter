=== Search & Filter ===
Contributors: DesignsAndCode
Donate link:
Tags: category, filter, taxonomy, search, wordpress, post type, post date
Requires at least: 3.5
Tested up to: 4.7
Stable tag: 1.2.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Search and Filtering for Custom Posts, Categories, Tags, Taxonomies, Post Dates and Post Types

== Installation ==

1. Upload the entire `search-filter` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

You will find 'Search & Filter' menu in your WordPress admin panel.

For basic usage, you can also have a look at the [plugin homepage](http://www.designsandcode.com/447/wordpress-search-filter-plugin-for-taxonomies/) or refer to the `Search & Filter` menu in your Wordpress admin panel.

== Frequently Asked Questions ==

= How can I xxxxx ? =

The documentation has been updated to include examples almost all configurable options with screenshots - please refer to the [Search & Filter Docs](http://docs.designsandcode.com/search-filter/).

== Screenshots ==

1. Full example of Search & Filter when used in a widget and with a combination of checkboxes, radio buttons and selects
2. Minimal example of Search & Filter embedded in the header
3. Minimal example of Search & Filter embedded in a widget
4. Example of Search & Filter using a post type filter

== Changelog ==

= 1.2.9 =
* Fixed - bugs with WP 4.4 compatibility
* Fixed - an issue with operators being case sensitive - they are no longer case sensitive

= 1.2.8 =
* Fixed - an issue with rewrites - thanks [@iohannis](https://wordpress.org/support/profile/iohannis)

= 1.2.7 =
* Fixed - fix for new taxonomy rewrites and problems with multiple selection when using checkboxes
* Fixed - added previously hidden `multiselect` field type

= 1.2.6 =
* Fixed - compatibility issues with WP 4.2.x

= 1.2.5 =
* Fixed a PHP error when setting defaults for taxonomies - many users did not see this but resulted in unexpected behaviour
* Fixed an error with post date sometimes being undefined for blank searches
* Added argument `empty_search_url` - when a users submits the search form without any search preferences selected they will be redirected to this URL
* Updated argument `add_search_param` - setting to `1` will force add a "?s=" to all urls generate by the plugin - this may help with the loading of search templates in some themes

= 1.2.4 =
* Fixed a bug created in 1.2.3 when doing an empty search

= 1.2.3 =
* Added arguement `all_items_labels` which allows for support for custom `all_items` labels in taxonomies, categories, post tags and post types when using `select` and `radio` types - the default text displaying "All Categories" for example can now be defined using `all_items_labels`
* Added `show_count` to arguments - this shows how many posts are in a particular term, in brackets after the term name - works only for categories, tags and taxonomies
* Fixed a bug when using when using "all post types" and it displaying no results
* Reverted behaviour from 1.2.2 - no longer force load search template when search is blank - let WP handle it again
* Added argument `add_search_param` - setting it to `1` will force a "?s=" or "&s=" to be added to the url even when the search is blank - in some circumstances this will force load the search template, instead of other WP templates, such as taxonomy or category templates

= 1.2.2 =
* Added support for multi selects - use `multiselect` as the type for your field
* Added support for AND & OR operators when using checkboxes or multiselects - use the `operators` argument with allowed values of `and` & `or`
* Force load search template when search is blank, don't include when search field is not included in shortcode
* Fixed an issue with navigation disappearing when using post_types

= 1.2.1 =
* Version Bump - bad commit

= 1.2.0 =
* WARNING - this update includes some major changes to shortcode construction,  do not upgrade until you have read how this will affect your setup - updating should be easy.
* Renamed the `taxonomies` argument to `fields` - `taxonomies` is now no longer appropriate as this list contains field types other than taxonomies - this list now contains taxonomies, `post_type`, `post_date` and `search` - `taxonomies` as an argument is still supported however will be deprecated
* Search box can now be positioned anywhere, simply include `search` in the fields list in the position desired.  Upgrading from previous versions will cause you to lose your search box, simply include `search` in the fields list to show it again
* Drop support for `search` argument as no longer relevant - control display of search input by adding it to the `fields` list
* Labels have been completely rewritten - `label` has been renamed to `headings` to avoid confusion with internal taxonomy labels - the `headings` argument now allows for any text to be added and displayed as a heading for each field - this allows for much more flexibility and no longer uses internal taxonomy labels - to hide a label simply leave blank
* Added support for hierarchical taxonomies for all input types - checkbox, radio & select
* Added support for ordering of taxonomies - use `order_by` argument - allowed values are `id`, `name`, `slug`, `count`, `term_group`
* Added support for ordering direction of taxonomies - use `order_dir` argument - allowed values are 'asc' or 'desc'
* Added support to show or hide empty taxonomies - use `hide_empty` argument
* Added support for `search_placeholder` 
* Updated `post_date` functionality to work with older versions of WP - can be displayed either as `date` or `daterange` - the `post_date` field uses the HTML 5 input type of `date` - browsers that do not support it will simply show a text box - a tutorial of integrating jquery for graceful degredation is in the works
* Renamed `submitlabel` to `submit_label` - `submitlabel` still works for now.
* Renamed `type` to `types` - `type` still works for now.
* Updated display of checkboxes and radio buttons, inputs are now wrapped in an unordered list which may affect your styling
* Various bug fixes
* Thanks to `bradaric` for help with hierarchical dropdown lists and date input types - https://github.com/bradaric

= 1.1.3 =
* Added support for post_date to be displayed either as `date` or `daterange` (WP 3.7+) type

= 1.1.2 =
* Added support for all public and custom post types (the `attachment` post type is excluded) - all post types can be user searchable or predfined and hidden from the user.  This allows for users to add multiple search widgets to their site which work on specific post types independantly from eachother.
* Added offical updated documentation, created and moved to [Search & Filter Docs](http://docs.designsandcode.com/search-filter/)

= 1.1.1 =
* Fixed: when submitting an empty search/filter, "?s=" now gets appended to the url (an empty search) to force load a results page, previously this was redirecting to the homepage which does not work for many use cases

= 1.1.0 =
* Added support for checkboxes and radio buttons, with the option to control this for each individual taxonomy.
* Added support to show or hide headings for each individual taxonomy.
* Added support to pass a class name through to Search & Filter widgets, this allows styling of different instances of Search & Filter
* Fixed problems with escaping output in search box
* Notice: This update will automatically add headings to taxonomy dropdowns, refer to usage and examples on how to disable them.

= 1.0.3 =
* Added some documention & screenshots to plugin page

= 1.0.2 =
* Version bump for WordPress plugins site

= 1.0.1 =
* Updated to use `label->all_items` in taxonomy object for dropdowns before using `label->name`
* Notice: This update may cause some labels to break, ensure you have set up your taxonomy properly including setting `label->all_items`

= 1.0.0 =
* Initial Release

== Upgrade Notice ==

* WARNING - this update includes some major changes to shortcode construction,  do not upgrade until you have read the changelog and how this will affect your setup - updating should be easy.

== Description ==

Search & Filter is a simple search and filtering plugin for WordPress - it is an advancement of the WordPress search box.

You can search by Category, Tag, Custom Taxonomy, Post Type, Post Date or any combination of these easily to really refine your searches - remove the search box and use it as a filtering system for your posts and pages.  Fields can be displayed as dropdowns, checkboxes, radio buttons or multi selects.

**Links:** [Search & Filter Documentation](http://docs.designsandcode.com/search-filter/) | [Search & Filter Discussion](http://www.designsandcode.com/447/wordpress-search-filter-plugin-for-taxonomies/)

= New: Search & Filter Pro =
 

* View live demo >> [demo 1](http://demo.designsandcode.com/sfpro-movie-reviews/) |  [demo 2](http://demo.designsandcode.com/sfpro-woo-mystile/product-search/)  |  [video](http://www.designsandcode.com/wordpress-plugins/search-filter-pro/) 
* Search **Custom Fields**, **Post Meta**, **Authors**, Post Types, Post Dates, Taxonomies, Tags, Categories
* Use **AJAX** to display results  - no more page reloading!
* Search **Post Meta/Custom Fields** with checkboxes, radio buttons, dropdowns, multiselects or comboboxes
* jQuery range slider, date pickers and **auto-complete comboboxes** for selects and multiselects
* Order Results Field - users can order results by meta value, Post ID, author, title, name, date, date modified, parent ID, random, comment count and menu order
* Drag & Drop editor
* Use custom templates
* Create as many fields and different search forms as you like
* Use for blogs, reviews sites, news sites, property sites and more.
* Use for your online shop - tested and compatible with **WooCommerce**, **WP eCommerce**, **Easy Digital Downloads**
* Place anywhere in your themes and posts using shortcodes and widgets
* Works with **WPML**
* Works with **Advanced Custom Fields**
* Extremely easy to use admin UI, fully integrated with WP 3.8+
* **Dedicated Support**
* [More info >>](http://www.designsandcode.com/wordpress-plugins/search-filter-pro/)



