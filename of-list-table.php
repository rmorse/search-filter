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
			"name"			=> "fields",
			"defaultval"	=> "&nbsp;",
			"options"		=> "<em>Comma seperated list of any field names and Public Taxonomies:</em><br /><br />search<br />post_date<br />post_types<br /><em>*public taxonomy names</em>",
			"info"			=> "Example using all your public taxonomies (copy &amp; paste!):<pre><code class='string'>[searchandfilter taxonomies=\"search,".$fulltaxonomylist."\"]</code></pre>"
		);
		$counter++;


		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "types",
			"defaultval"	=> "<code class='string large'>select</code>",
			"options"		=> "<em>Comma seperated list of any of the types found below:</em><br /><br />select<br />checkbox<br />radio<br /><br />
								<em>These types should only be used when the field is `post_date`:</em><br /><br />date<br />daterange",
			"info"			=> "The order of values in this comma seperated list needs to match the fields list."
		);
		$counter++;

		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "headings",
			"defaultval"	=> "&nbsp;",
			"options"		=> "<em>Comma seperated list containing any string value.  Blank values are ommited completely and the heading will not display.</em>",
			"info"			=> "The order of values in this comma seperated list needs to match the fields list."
		);
		$counter++;
		
		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "hierarchical",
			"defaultval"	=> "<code class='string large'>0</code>",
			"options"		=> "<em>Comma seperated list.</em><br /><br />
									1 - display as hierarchical<br />
									<em>*Any other value is ignored</em>",
			"info"			=> "The order of values in this comma seperated list needs to match the fields list."
		);
		$counter++;
		
		
		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "hide_empty",
			"defaultval"	=> "<code class='string large'>1</code>",
			"options"		=> "<em>Comma seperated list.</em><br /><br />
									0 - Shows empty taxonomies<br />
									1 - Hides empty taxonomies<br />
									<em>*Any other value is ignored</em>",
			"info"			=> "The order of values in this comma seperated list needs to match the fields list."
		);
		$counter++;
		
		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "order_by",
			"defaultval"	=> "<code class='string large'>name</code>",
			"options"		=> "<em>Comma seperated list of the following possible values:</em><br /><br />
									ID<br />
									name<br />
									slug<br />
									count<br />
									term_group<br /><br />
									
									<em>This uses the values of \"orderby\" as <a href=\"http://codex.wordpress.org/Template_Tags/wp_list_categories\" target=\"_blank\">defined on the WordPress site</a></em>.",
			"info"			=> "The order of values in this comma seperated list needs to match the fields list."
		);
		$counter++;
				
		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "order_dir",
			"defaultval"	=> "<code class='string large'>ASC</code>",
			"options"		=> "<em>Comma seperated list containing:</em><br /><br />
									ASC - ascending<br />
									DESC - descending<br /><br />
									<em>This sets the order of taxonomies terms for a given taxonomy and can be used in conjunction with `order_by`.</em>",
			"info"			=> "The order of values in this comma seperated list needs to match the fields list."
		);
		$counter++;
				
		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "post_types",
			"defaultval"	=> "&nbsp;",
			"options"		=> "<em>Comma seperated list of any post types (names) in the Public Post Types table below.</em><br /><br /> or set to: <br /><br /><code class='string large'>all</code>",
			"info"			=> "This can be used with or without `post_type` appearing in the field list.<br /><br />When `post_type` appears in the field list, the post types listed here will be selectable in the `post_type` field.<br /><br />When `post_type` does not appear in the field list, then all searches are retricted to the post types here."
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
			"name"			=> "submit_label",
			"defaultval"	=> "<code class='string large'>Submit</code>",
			"options"		=> "<em>Any string</em>",
			"info"			=> "This is the text label on the submit button."
		);
		$counter++;

		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "search_placeholder",
			"defaultval"	=> "<code class='string large'>Search &hellip;</code>",
			"options"		=> "<em>Any string</em>",
			"info"			=> "This is the placeholder text that appears when no search term has been entered in the search field."
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