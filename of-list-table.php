<?php
/*
* Table rendering
*/

if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class OF_Taxonomy_List_Table extends WP_List_Table {

	private $taxonomy_data = array();

	function __construct()
	{
		global $status, $page;
		parent::__construct(array(
			'singular'=> 'wp_list_of_taxonomy', //Singular label
			'plural' => 'wp_list_of_taxonomies', //plural label, also this well be one of the table css class
			'ajax'	=> false //We won't support Ajax for this table
		));

		$args = array(
		  'public'   => true,
		);
		$output = 'object'; // or objects
		$operator = 'and'; // 'and' or 'or'
		$taxonomies = get_taxonomies( $args, $output, $operator );

		//var_dump($taxonomies['post_tag']['labels']['all_items']); - all items should be used in the drop downs

		$counter = 0;
		if ( $taxonomies )
		{
			foreach ( $taxonomies  as $taxonomy )
			{
				$ttaxonomydata = array(
					"ID"			=>	$counter,
					"name"			=>	$taxonomy->name,
					"label"		=>	$taxonomy->labels->name,
					"posttypes"		=>	implode(', ', $taxonomy->object_type)
				);

				$this->taxonomy_data[] = $ttaxonomydata;
			}
		}
	}

	function get_columns(){
		$columns = array(
			'name'			=> 'Name',
			'label'			=> 'Label',
			'posttypes'		=> 'Post Types'
		);
		return $columns;
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->taxonomy_data;
	}

	function column_default( $item, $column_name ) {
		switch( $column_name )
		{
			case 'name':
			case 'label':
			case 'posttypes':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}


	function get_sortable_columns()
	{
		$sortable_columns = array(
		);
		return $sortable_columns;
	}
}

class OF_Post_Type_Table extends WP_List_Table {

	private $post_types = array();

	function __construct()
	{
		global $status, $page;
		parent::__construct(array(
			'singular'=> 'wp_list_of_post_type', //Singular label
			'plural' => 'wp_list_of_post_types', //plural label, also this well be one of the table css class
			'ajax'	=> false //We won't support Ajax for this table
		));

		$args = array('public'   => true);
		$output = 'object'; // names or objects, note names is the default
		$operator = 'and'; // 'and' or 'or'

		$post_types_objs = get_post_types( $args, $output, $operator );



		if($post_types_objs)
		{
			$counter = 0;

			foreach ( $post_types_objs  as $post_type )
			{
				if($post_type->name!="attachment")
				{
					$tempobject = array(
						"ID"			=>	$counter,
						"name"			=>	$post_type->name,
						"label"			=>  $post_type->labels->name
					);

					$this->post_types[] = $tempobject;

				}
			}
		}
	}

	function get_columns(){
		$columns = array(
			'name'			=> 'Name',
			'label'			=> 'Label'
		);
		return $columns;
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->post_types;
	}

	function column_default( $item, $column_name ) {
		switch( $column_name )
		{
			case 'name':
			case 'label':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}


	function get_sortable_columns()
	{
		$sortable_columns = array(
		);
		return $sortable_columns;
	}
}



class OF_Variable_List_Table extends WP_List_Table {

	private $taxonomy_data = array();

	function __construct()
	{
		parent::__construct(array(
			'singular'=> 'wp_list_of_variable', //Singular label
			'plural' => 'wp_list_of_variables', //plural label, also this well be one of the table css class
			'ajax'	=> false //We won't support Ajax for this table
		));

		//var_dump($taxonomies['post_tag']['labels']['all_items']); - all items should be used in the drop downs
		$counter = 0;
		$args = array(
		  'public'   => true,
		);
		$output = 'names'; // or objects
		$taxonomies = get_taxonomies( $args, $output );
		$fulltaxonomylist = implode(",",$taxonomies);

		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "taxonomies",
			"defaultval"	=> "&nbsp;",
			"options"		=> "<em>Comma seperated list of any taxonomy names found in the Public Taxonomies table below.</em><br /><br /><strong>Update:</strong> You can now also add <code class='string'>post_type</code> to this list to display options for post types and <code class='string'>post_date</code> for a post date field.",
			"info"			=> "Example using all your public taxonomies (copy &amp; paste!):<pre><code class='string'>[searchandfilter taxonomies=\"".$fulltaxonomylist."\"]</code></pre>"
		);
		$counter++;


		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "type",
			"defaultval"	=> "<code class='string large'>select</code>",
			"options"		=> "<em>Comma seperated list of any of the types found below:</em><br /><br /><code class='string large'>select</code><br /><code class='string large'>checkbox</code><br /><code class='string large'>radio</code><br /><code class='string large'>date</code><br /><code class='string large'>daterange</code>&nbsp;<small>(WP 3.7+)</small>",
			"info"			=> "The order of values in this comma seperated list needs to match the taxonomies list. <br /><br />To display categories, tags and post formats, as a `select` dropdown, radio buttons and checkboxes, we must put them in the order we need:
			<br /><pre><code class='string'>[searchandfilter taxonomies=\"category,post_tag,post_format\" type=\"select,checkbox,radio\"]</code></pre>
			If any taxonomies are left unspecified they well default to `select` dropdowns:
			<br /><pre><code class='string'>[searchandfilter taxonomies=\"category,post_tag,post_format\" type=\"select,checkbox\"]</code></pre>
			With this example using just \"select,checkbox\", the post format (being the third, not provided parameter) will be displayed as a `select` dropdown.<br /><br />

			If the `type` argument is ommited completely all taxonomies will be displayed as `select` dropdowns, except `post_date` which will default to `date`."
		);
		$counter++;

		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "label",
			"defaultval"	=> "<code class='string large'>name</code>",
			"options"		=> "<code class='string large'>0</code> - hide all labels<br /><br /> or <br /><br /><em>Comma seperated list of any of the types found below:</em><br /><br /><code class='string large'>name</code><br /><code class='string large'>singular_name</code><br /><code class='string large'>search_items</code><br /><code class='string large'>all_items</code><br /><em><code class='string large'>*blank value</code></em>",
			"info"			=> "This list works the same as the `type` example above.<br /><br />
			The different values that can be used are taken directly from the labels within a taxonomy object - so make sure you set these in your taxonomies if you wish to use them below.
			<br /><br />Examples:<br /><br />
			<strong>Hide all labels:</strong>
			<pre><code class='string'>[searchandfilter taxonomies=\"category,post_tag,post_format\" label=\"0\"]</code></pre>
			<strong>Mixture of different label types:</strong>
			<pre><code class='string'>[searchandfilter taxonomies=\"category,post_tag,post_format\" label=\"singular_name,search_items,all_items\"]</code></pre>
			<strong>Hiding the label for category and tag, and set `name` for the post format:</strong>
			<pre><code class='string'>[searchandfilter taxonomies=\"category,post_tag,post_format\" label=\",,name\"]</code></pre>
			*In this last example, a blank value (ie, comma's with no data in between) tells Search &amp; Filter to hide the label for the particular taxonomy.<br /><br />
			If the `label` argument is ommited completely all labels will be shown by default and will be set to use the `name` label for a taxonomy.
			"
		);
		$counter++;

		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "post_types",
			"defaultval"	=> "&nbsp;",
			"options"		=> "<em>Comma seperated list of any post types (names) in the Public Post Types table below.</em><br /><br /> or set to: <br /><br /><code class='string large'>all</code>",
			"info"			=> "List all post types you want the widget to search. Leave blank for default behavious without any post type restrictions.  This will use the default setting for post types you have in place which is often just <code>post</code> and <code>page</code><br /><br />

			All searches will be constrained to the post types you add here.<br /><br />
			If <code>post_type</code> has been added to <code>taxonomies</code> list above, then it will pull its data from this list, a user will be able to choose from all post types listed here."
		);
		$counter++;

		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "search",
			"defaultval"	=> "<code class='string large'>1</code>",
			"options"		=> "<code class='string large'>0</code> - hide the search box<br /><code class='string large'>1</code> - display search box",
			"info"			=> "The search box is shown by default, ommit from shortcode unless you specifically want to hide it - then set it with a value of 0."
		);
		$counter++;

		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "class",
			"defaultval"	=> "",
			"options"		=> "<em>Any string</em>",
			"info"			=> "Enter a class name here, or class names seperated by spaces to have them added to Search &amp; Filter form. This allows individual styling of each Search &amp; Filter instance.<br /><br />Ommit to ignore."
		);
		$counter++;


		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "submitlabel",
			"defaultval"	=> "<code class='string large'>Submit",
			"options"		=> "<em>Any string</em>",
			"info"			=> "This is the text label on the submit button."
		);
		$counter++;

	}

	function get_columns(){
		$columns = array(
			'name'			=> 'Name',
			'defaultval'		=> 'Default Value',
			'options'		=> 'Options',
			'info'		=> 'Additonal Information'
		);
		return $columns;
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->taxonomy_data;
	}
	function column_default( $item, $column_name )
	{
		switch( $column_name )
		{
			case 'name':
			case 'defaultval':
			case 'options':
			case 'info':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}



}


?>