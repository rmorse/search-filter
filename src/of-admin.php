<?php

/*
* Set up Admin menus & pages
*/

add_action( 'admin_menu', 'searchandfilter_menu_pages' );

function searchandfilter_menu_pages() {
	// Add the top-level admin menu.
	$page_title = 'Search &amp; Filter Settings';
	$menu_title = 'Search &amp; Filter';
	$capability = 'manage_options';
	$menu_slug  = 'searchandfilter-settings';
	$function   = 'searchandfilter_settings';
	$icon_url   = SEARCHANDFILTER_PLUGIN_URL . '/admin/icon.png';
	$icon       = 'data:image/svg+xml;base64,' . base64_encode(
		'<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 20 20"
	 >
	   <path
		  style="fill-opacity:1;fill-rule:nonzero;stroke:none;stroke-width:0.31579"
		  d="M 9.9999995,0.9473685 C 2,5.4736845 2,5.4736845 2,5.4736845 2,14.526315 2,14.526315 2,14.526315 9.9999995,19.052631 9.9999995,19.052631 9.9999995,19.052631 18,14.526315 18,14.526315 18,14.526315 18,5.4736845 18,5.4736845 18,5.4736845 Z m 0,15.0526305 c -3.3684207,0 -5.9999989,-2.631578 -5.9999989,-5.9999995 0,-3.368421 2.6315782,-6 5.9999989,-6 3.3684205,0 6.0000005,2.631579 6.0000005,6 0,3.3684215 -2.63158,5.9999995 -6.0000005,5.9999995 z"
		  id="path17" />
	   <path
		  style="fill-opacity:1;fill-rule:nonzero;stroke:none;stroke-width:0.350878"
		  d="m 13.62573,10.052631 c 0,1.988305 -1.637426,3.62573 -3.6257305,3.62573 -1.9883036,0 -3.6257309,-1.637425 -3.6257309,-3.62573 0,-1.988304 1.6374273,-3.625731 3.6257309,-3.625731 1.9883045,0 3.6257305,1.637427 3.6257305,3.625731 z"
		  id="path19" />
	 </svg>'
	);

	add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon );
}

function searchandfilter_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}
	?>
		<div class="notice">
			<h3><?php echo esc_html__( 'Big changes are coming - Search & Filter 3.0 is around the corner. ', 'search-filter' ); ?></h3>
			<p><?php echo sprintf( esc_html__( 'Hi! We need to tell you, %s.', 'search-filter' ), '<strong>' . esc_html__( 'a new version is coming with some big changes', 'textdomain' ) ) . '</strong>'; ?></p>
			<p><?php echo esc_html__( 'We\'ve been hard at work over the past few years working on the pro edition of Search & Filter, and now the time has come to share some of the features we\'ve built.', 'search-filter' ); ?></p>
			<p><?php echo sprintf( esc_html__( 'This means %1$s, and %2$s.', 'search-filter' ), '<strong>' . esc_html__( 'significant changes to how you build search forms', 'search-filter' ) . '</strong>', '<strong>' . esc_html__( 'more features, filters, flexibility and integration options', 'search-filter' ) . '</strong>' ); ?></p>
			<p><?php echo esc_html__( 'This is all part of a huge undertaking, bringing more features to Search & Filter free and pro, while also bringing a new interface and architecture to both editions.', 'search-filter' ); ?></p>
			<p><span class="dashicons dashicons-megaphone"></span> <a href="https://searchandfilter.com/search-filter-3-0-free/" target="_blank"><?php echo esc_html__( 'Read the blog post', 'search-filter' ); ?></a></p>
			<p><strong><span class="dashicons dashicons-hammer"></span> <a class="" href="https://searchandfilter.com/version-3/" target="_blank"><?php echo esc_html__( 'Test version 3', 'search-filter' ); ?></a></strong></p>
			<p></p>
		</div>
		<?php
		echo '<div class="wrap"><div id="icon-plugins" class="icon32"></div>';
		echo '<h2>Search &amp; Filter</h2>';
		echo '<h3>About</h3>';
		echo '<div class="of-caption">
                Search &amp; Filter is a simple search and filtering plugin for WordPress brought to you by <a href="https://codeamp.com" target="_blank">Code Amp</a> (formerly known as Designs &amp; Code).<br /><br />
				It is an advancement of the WordPress search box, adding taxonomy and post type filters to really refine your searches.<br /><br />
				You can search by Category, Tag, Custom Taxonomy, Post Type or any combination of these easily - you can even remove the search box and simply use it as a filtering system for your posts and pages.  Taxonomies and Post Types can be displayed as dropdown selects, checkboxes, radio buttons or multiselects.
			</div>';
		echo '<h3>Documentation</h3>';
		echo '<div class="of-caption">
				Advanced documentation and examples has now moved - find it on our <a href="https://free.searchandfilter.com/" target="_blank">Search &amp; Filter Plugin Documentation</a>.<br /><br />
				Please find below limited documentation to get you started.
			</div>';

		echo '<h3>How To Use</h3>';
		echo '<div class="of-caption">
				To display Search &amp; Filter all you need to do is a use a shortcode:<br />
				
				<pre><code class="string">[searchandfilter fields="search,category,post_tag"]</code></pre>
				
				This will display a search box, a category dropdown and a tag dropdown.  You can use the shortcode within posts/pages and widget areas.<br /><br />
				
				To use this within a theme file you simple need to call the `do_shortcode` function with the shortcode above within the theme file:<br />
				
				<pre><code class="php">&lt;?php echo do_shortcode( \'[searchandfilter fields="search,category,post_tag"]\' ); ?&gt;</code></pre>
			</div>';

		echo '<h3>Arguments</h3>';
		echo '<div class="of-caption">Examples for most of the arguments below can be found over on the <a href="https://free.searchandfilter.com/#examples" target="_blank">Search &amp; Filter Plugin Documentation</a>.</div>';

		// Display table.
		$of_var_list_table = new OF_Variable_List_Table();
		$of_var_list_table->prepare_items();
		$of_var_list_table->display();

		echo '<h3>Your Public Taxonomies</h3>';

		// Prepare Taxonomy elements.
		$of_tax_list_table = new OF_Taxonomy_List_Table();
		$of_tax_list_table->prepare_items();
		$of_tax_list_table->display();

		echo '<h3>Your Public Post Types</h3>';
		echo '<div class="of-caption"><strong>Note:</strong> the <code>attachment</code> post type is not available in this list.</div>';
		// Prepare Taxonomy elements
		$of_post_list_table = new OF_Post_Type_Table();
		$of_post_list_table->prepare_items();
		$of_post_list_table->display();
		echo '<h3>Styling</h3>';
		echo '<div class="of-caption">
				Search &amp; Filter uses standard inputs and selects, form elements are contained in an unordered list - styling should be easy.  <a href="' . SEARCHANDFILTER_PLUGIN_URL . '/style.css' . '" target="_blank">Please see CSS file for base styles used.</a>
			</div>';

		echo '<h3>Search &amp; Filter URLs</h3>';
		echo '<div class="of-caption">
				If any fields are submitted that have blank values they do not get added to the URL, for example, if the search box is empty when submitting, you will not find a `?s=` in the URL.<br /><br />
				
				In addition to this, if permalinks are enabled, when you submit a search, Search &amp; Filter will try to remove `category_name` from the url and instead rewrites the URL to first obey a clean category URL with the rest of the query string following.<br /><br />
				
				This url:<br />
				<pre><code class="of-url">www.yourdomain.com/?s=searchterm&amp;category_name=uncategorized&amp;tag=shoes&ampcustomtaxonomy=customvalue</code></pre><br />
				
				Becomes:<br />
				<pre><code class="of-url">www.yourdomain.com/category/uncategorized/?s=searchterm&amp;tag=shoes&amp;customtaxonomy=customvalue</code></pre><br />
				
			</div>';

		echo '<h3>Links</h3>';
		echo '<div class="of-caption">
				<ul>
					<li><a href="https://free.searchandfilter.com/" target="_blank">Plugin Documentation</a></li>
					<li><a href="http://wordpress.org/plugins/search-filter" target="_blank">Plugin on WordPress.org</a></li>
					<li><a href="https://github.com/rmorse/search-filter" target="_blank">Plugin on Github</a></li>
					<li><a href="https://twitter.com/SearchAndFilter" target="_blank">Follow us on Twitter for updates and news</a></li>
				</ul>
			</div>';

		echo '</div>';
}

function searchandfilter_help() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	// Render the HTML for the Help page or include a file that does
}

/*
* Add `settings` link on plugin page next to `activate`
*/
add_filter( 'plugin_action_links_' . SEARCHANDFILTER_BASENAME, 'searchandfilter_plugin_action_links', 10, 2 );

function searchandfilter_plugin_action_links( $links, $file ) {

	$settings_link = '<a href="' . esc_url( get_admin_url() . 'admin.php?page=searchandfilter-settings' ) . '">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

function searchandfilter_plugin_admin_notice() {

	global $current_user;

	$user_id = $current_user->ID;

	if ( ! get_user_meta( $user_id, 'search_filter_v3_coming_soon_ignore' ) ) {

		?>
			<div class="notice notice-warning is-dismissible search-filter-notice-v3-coming-soon">
				<p>
					<strong>
					<?php echo esc_html__( 'Search & Filter 3.0 is almost here - make sure you\'re ready for the update', 'search-filter' ); ?>
						- <a href="<?php echo esc_url( admin_url( 'admin.php?page=searchandfilter-settings' ) ); ?>"><?php echo esc_html__( 'continue reading...', 'search-filter' ); ?></a>
					</strong>
				</p>
			</div>
			<?php
	}
}

	add_action( 'admin_notices', 'searchandfilter_plugin_admin_notice' );

	add_action( 'wp_ajax_dismiss_search_filter_v3_coming_soon', 'search_filter_dismiss_notice_search_filter_v3' );

function search_filter_dismiss_notice_search_filter_v3() {
	update_user_meta( get_current_user_id(), 'search_filter_v3_coming_soon_ignore', 1 );
}


	add_action( 'admin_print_footer_scripts', 'search_filter_admin_notice_script' );

function search_filter_admin_notice_script() {
	?>
			<script>
				// shorthand no-conflict safe document-ready function
				jQuery(function($) {
					// Hook into the "notice-my-class" class we added to the notice, so
					// Only listen to YOUR notices being dismissed
					$( document ).on( 'click', '.search-filter-notice-v3-coming-soon .notice-dismiss', function () {
						$.ajax( ajaxurl,
						{
							type: 'GET',
							data: {
								action: 'dismiss_search_filter_v3_coming_soon'
							}
						} );
					} );
				});
			</script>
	<?php
}
