WordPress Search &amp; Filter
==================

Search &amp; Filter is a simple search and filtering plugin for WordPress.

It is an advancement of the WordPress search box, adding taxonomy filters to really refine your searches!

You can search by Category, Tag, Custom Taxonomy or any combination of these easily - you can even remove the search box and simply use it as a filtering system for your posts and pages.

## Demo
 - Coming Soon - see [this blog post](http://www.designsandcode.com/447/wordpress-search-filter-plugin-for-taxonomies/) for screenshots

## Setup
 - [Designs & Code Blog - Setup Search &amp; Filter](http://www.designsandcode.com/447/wordpress-search-filter-plugin-for-taxonomies/)

## Changelog

### 1.1.2
 - Added support for all public and custom post types (the attachment post type is excluded) - all post types can be user searchable or predfined and hidden from the user. This allows for users to add multiple search widgets to their site which work on specific post types independantly from eachother.
 - Added offical updated documentation, created and moved to Search &amp; Filter Docs

### 1.1.1
 - Fixed: when submitting an empty `search/filter`, `"?s="` now gets appended to the url (an empty search) to force load a results page, previously this was redirecting to the homepage which does not work for many use cases

### 1.1.0
 - Added support for checkboxes and radio buttons, with the option to control this for each individual taxonomy.
 - Added support to show or hide headings for each individual taxonomy.
 - Added support to pass a class name through to Search &amp; Filter widgets, this allows styling of different instances of Search &amp; Filter
 - Fixed problems with escaping output in search box
Notice: This update will automatically add headings to taxonomy dropdowns, refer to usage and examples on how to disable them.

### 1.0.3
 - Added some documention &amp; screenshots to plugin page

### 1.0.2
 - Version bump for WordPress plugins site

### 1.0.1
 - Updated to use `label->all_items` in taxonomy object for dropdowns before using `label->name`
 - Notice: This update may cause some labels to break, ensure you have set up your taxonomy properly including setting `label->all_items`

### 1.0.0
 - Initial Release


## License
 - Released under the [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html) License
