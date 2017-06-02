<?php

/*
* Set up Admin menus & pages
*/

	add_action('admin_menu', 'searchandfilter_menu_pages');
	
	function searchandfilter_menu_pages()
	{
		// Add the top-level admin menu
		$page_title = 'Search &amp; Filter Settings';
		$menu_title = 'Search &amp; Filter';
		$capability = 'manage_options';
		$menu_slug = 'searchandfilter-settings';
		$function = 'searchandfilter_settings';
		$icon_url = SEARCHANDFILTER_PLUGIN_URL.'/admin/icon.png';
		add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url);

		// Add submenu page with same slug as parent to ensure no duplicates
		$sub_menu_title = 'Settings';
		//add_submenu_page($menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $function);

		// Now add the submenu page for Help
		$submenu_page_title = 'Search &amp; Filter Help';
		$submenu_title = 'Help';
		$submenu_slug = 'searchandfilter-help';
		$submenu_function = 'searchandfilter_help';
		//add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
	}

	function searchandfilter_settings()
	{
		if (!current_user_can('manage_options')) {
			wp_die('You do not have sufficient permissions to access this page.');
		}
		echo '
			<script type="text/javascript">
			jQuery(function() {
			  jQuery(\'pre code\').each(function(i, e) {hljs.highlightBlock(e)});
			});
			</script>
		';
		echo '<div class="wrap"><div id="icon-plugins" class="icon32"></div>';
		echo '<h2>Search &amp; Filter</h2>';
		echo "<h3>About</h3>";
		echo '<div class="of-caption">
				Search &amp; Filter is a simple search and filtering plugin for Wordpress brought to you by <a href="http://www.designsandcode.com" target="_blank">Designs &amp; Code</a>.<br /><br />
				It is essentially an advancement of the WordPress search box, adding taxonomy and post type filters to really refine your searches.<br /><br />
				You can search by Category, Tag, Custom Taxonomy, Post Type or any combination of these easily - you can even remove the search box and simply use it as a filtering system for your posts and pages.  Taxonomies and Post Types can be displayed as dropdown selects, checkboxes, radio buttons or multiselects.
			</div>';
		echo "<h3>Documentation</h3>";
		echo '<div class="of-caption">
				Advanced documentation and examples has now moved - find it on our <a href="http://docs.designsandcode.com/search-filter/" target="_blank">Search &amp; Filter Plugin Documentation</a>.<br /><br />
				Please find below limited documentation to get you started.
			</div>';

		echo "<h3>How To Use</h3>";
		echo '<div class="of-caption">
				To display Search &amp; Filter all you need to do is a use a shortcode:<br />
				
				<pre><code class="string">[searchandfilter taxonomies="category,post_tag"]</code></pre>
				
				This will display a search box, a category dropdown and a tag dropdown.  You can use the shortcode within posts/pages and widget areas.<br /><br />
				
				To use this within a theme file you simple need to call the `do_shorcode` function with the shortcode above within the theme file:<br />
				
				<pre><code class="php">&lt;?php echo do_shortcode( \'[searchandfilter taxonomies="category,post_tag"]\' ); ?&gt;</code></pre>
			</div>';
		
		
		echo "<h3>Arguments</h3>";
		echo '<div class="of-caption">Examples for most of the arguments below can be found over on the <a href="http://docs.designsandcode.com/search-filter/#examples" target="_blank">Search &amp; Filter Plugin Documentation</a>.</div>';
		//display table
		$ofVarListTable = new OF_Variable_List_Table();
		$ofVarListTable->prepare_items();
		$ofVarListTable->display();
		
		echo "<h3>Your Public Taxonomies</h3>";
		
		//Prepare Taxonomy elements
		$ofTaxListTable = new OF_Taxonomy_List_Table();
		$ofTaxListTable->prepare_items();
		$ofTaxListTable->display();
		
		echo "<h3>Your Public Post Types</h3>";
		echo '<div class="of-caption"><strong>Note:</strong> the <code>attachment</code> post type is not available in this list.</div>';
		//Prepare Taxonomy elements
		$ofPostTypeTable = new OF_Post_Type_Table();
		$ofPostTypeTable->prepare_items();
		$ofPostTypeTable->display();
		
		echo "<h3>Styling</h3>";
		echo '<div class="of-caption">
				Search &amp; Filter uses standard inputs and selects, form elements are contained in an unordered list - styling should be easy.  <a href="'.SEARCHANDFILTER_PLUGIN_URL . '/style.css'.'" target="_blank">Please see CSS file for base styles used.</a>
			</div>';
			
		echo "<h3>Search &amp; Filter Prefers Clean URLs!</h3>";
		echo '<div class="of-caption">
				If any fields are submitted that have blank values they do not get added to the URL, for example, if the search box is empty when submitting, you will not find a `?s=` in the URL.<br /><br />
				
				In addition to this, if permalinks are enabled, when you submit a search, Search &amp; Filter will try to remove `category_name` from the url and instead rewrites the URL to first obey a clean category URL with the rest of the query string following.<br /><br />
				
				This url:<br />
				<pre><code class="of-url">www.yourdomain.com/?s=searchterm&amp;category_name=uncategorized&amp;tag=shoes&ampcustomtaxonomy=customvalue</code></pre><br />
				
				Becomes:<br />
				<pre><code class="of-url">www.yourdomain.com/category/uncategorized/?s=searchterm&amp;tag=shoes&amp;customtaxonomy=customvalue</code></pre><br />
				
				The built in Wordpress rewrites wouldn\'t normally handle this.
				
				
			</div>';
		
		echo "<h3>Links</h3>";
		echo '<div class="of-caption">
				<ul>
					<li><a href="http://www.designsandcode.com/447/wordpress-search-filter-plugin-for-taxonomies/" target="_blank">Plugin Support Page &amp; Discussion</a></li>
					<li><a href="http://docs.designsandcode.com/search-filter/" target="_blank">Plugin Documentation</a></li>
					<li><a href="http://wordpress.org/plugins/search-filter" target="_blank">Plugin on WordPress.org</a></li>
					<li><a href="https://github.com/rmorse/wp-search-filter" target="_blank">Plugin on Github</a></li>
				</ul>
			</div>';
		
		echo '</div>';
	}

	function searchandfilter_help()
	{
		if (!current_user_can('manage_options')) {
			wp_die('You do not have sufficient permissions to access this page.');
		}

		// Render the HTML for the Help page or include a file that does
	}
	
/*
* Add `settings` link on plugin page next to `activate`
*/

	add_filter('plugin_action_links_'.SEARCHANDFILTER_BASENAME, 'searchandfilter_plugin_action_links', 10, 2);

	function searchandfilter_plugin_action_links($links, $file)
	{
		static $this_plugin;

		if (!$this_plugin)
		{
			$this_plugin = SEARCHANDFILTER_BASENAME;
		}
		//var_dump($this_plugin);
		if ($file == $this_plugin)
		{
			// The "page" query string value must be equal to the slug
			// of the Settings admin page we defined earlier, which in
			// this case equals "myplugin-settings".
			$settings_link = '<a href="' . get_admin_url() . 'admin.php?page=searchandfilter-settings">Settings</a>';
			array_unshift($links, $settings_link);
		}

		return $links;
	}

?>