<?php
/*
* Table rendering
*/

if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

function of_add_custom_box()
{//use add_meta_box to avoid errors with loading the table stuff
    $screens = array( 'post', 'page' );
    foreach ( $screens as $screen )
	{
        add_meta_box('myplugin_sectionid',__( 'My Post Section Title', 'myplugin_textdomain' ),'myplugin_inner_custom_box',$screen);
	}
}

add_action( 'add_meta_boxes', 'of_add_custom_box' );

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
			"options"		=> "<em>Comma seperated list of any taxonomy names found in the Public Taxonomies table below.</em>",
			"info"			=> "Example using all your taxonomies (copy &amp; paste!):<pre><code class='string'>[searchandfilter taxonomies=\"".$fulltaxonomylist."\"]</code></pre>"
		);
		$counter++;
		
		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "seach",
			"defaultval"	=> "1",
			"options"		=> "0 - hide the search box<br />1 - display search box",
			"info"			=> "The search box is shown by default, ommit from shortcode unless you specifically want to hide it - then set it with a value of 0."
		);
		$counter++;
		
		$this->taxonomy_data[] = array(
			"ID"			=> $counter,
			"name"			=> "submitlabel",
			"defaultval"	=> "Submit",
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