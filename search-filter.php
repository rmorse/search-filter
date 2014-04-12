<?php
/*
Plugin Name: Search & Filter
Plugin URI: http://www.designsandcode.com/447/wordpress-search-filter-plugin-for-taxonomies/
Description: Search and Filtering system for Pages, Posts, Categories, Tags and Taxonomies
Author: Designs & Code
Author URI: http://www.designsandcode.com/
Version: 1.2.5
Text Domain: searchandfilter
License: GPLv2
*/

// TO DO - i18n http://codex.wordpress.org/I18n_for_WordPress_Developers

/*
* Set up Plugin Globals
*/
if (!defined('SEARCHANDFILTER_VERSION_NUM'))
    define('SEARCHANDFILTER_VERSION_NUM', '1.2.5');

if (!defined('SEARCHANDFILTER_THEME_DIR'))
    define('SEARCHANDFILTER_THEME_DIR', ABSPATH . 'wp-content/themes/' . get_template());

if (!defined('SEARCHANDFILTER_PLUGIN_NAME'))
    define('SEARCHANDFILTER_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

if (!defined('SEARCHANDFILTER_PLUGIN_DIR'))
    define('SEARCHANDFILTER_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . SEARCHANDFILTER_PLUGIN_NAME);

if (!defined('SEARCHANDFILTER_PLUGIN_URL'))
    define('SEARCHANDFILTER_PLUGIN_URL', WP_PLUGIN_URL . '/' . SEARCHANDFILTER_PLUGIN_NAME);

if (!defined('SEARCHANDFILTER_BASENAME'))
    define('SEARCHANDFILTER_BASENAME', plugin_basename(__FILE__));

if (!defined('SEARCHANDFILTER_VERSION_KEY'))
    define('SEARCHANDFILTER_VERSION_KEY', 'searchandfilter_version');



//form prefix for plugin
if (!defined('SF_FPRE'))
    define('SF_FPRE', 'of');

add_option(SEARCHANDFILTER_VERSION_KEY, SEARCHANDFILTER_VERSION_NUM);

/*
* Set up Plugin Globals
*/
if ( ! class_exists( 'SearchAndFilter' ) )
{
	class SearchAndFilter
	{
		private $has_form_posted = false;
		private $hasqmark = false;
		private $hassearchquery = false;
		private $urlparams = "/";
		private $searchterm = "";
		private $tagid = 0;
		private $catid = 0;
		private $defaults = array();
		private $frmreserved = array();
		private $taxonomylist = array();

		public function __construct()
		{
			
			// Set up reserved fields
			$this->frmreserved = array(SF_FPRE."category", SF_FPRE."search", SF_FPRE."post_tag", SF_FPRE."submitted", SF_FPRE."post_date", SF_FPRE."post_types");
			$this->frmqreserved = array(SF_FPRE."category_name", SF_FPRE."s", SF_FPRE."tag", SF_FPRE."submitted", SF_FPRE."post_date", SF_FPRE."post_types"); //same as reserved
			
			//add query vars
			add_filter('query_vars', array($this,'add_queryvars') );
			
			//filter post type & date if it is set
			add_filter('pre_get_posts', array($this,'filter_query_post_types'));
			add_filter('pre_get_posts', array($this,'filter_query_post_date'));
			
			//add_filter('pre_get_posts',array($this, 'fix_blank_search')); //temporaril disabled
			
			// Add shortcode support for widgets
			add_shortcode('searchandfilter', array($this, 'shortcode'));
			add_filter('widget_text', 'do_shortcode');
			
			// Check the header to see if the form has been submitted
			add_action( 'get_header', array( $this, 'check_posts' ) );
			
			// Add styles
			add_action( 'wp_enqueue_scripts', array($this, 'of_enqueue_styles') );
			add_action( 'admin_enqueue_scripts', array($this, 'of_enqueue_admin_ss') );
			
		}
		
		public function of_enqueue_styles()
		{
			wp_enqueue_style( 'searchandfilter', SEARCHANDFILTER_PLUGIN_URL . '/style.css', false, 1.0, 'all' );
		}
		public function of_enqueue_admin_ss($hook)
		{
			if( 'toplevel_page_searchandfilter-settings' == $hook )
			{
				wp_enqueue_style( 'of_syntax_style', SEARCHANDFILTER_PLUGIN_URL.'/admin/github.css', false, 1.0, 'all' ); //more highlight styles http://softwaremaniacs.org/media/soft/highlight/test.html
				wp_enqueue_style( 'of_style', SEARCHANDFILTER_PLUGIN_URL.'/admin/style.css', false, 1.0, 'all' );
				wp_enqueue_script( 'of_syntax_script', SEARCHANDFILTER_PLUGIN_URL.'/admin/syntax.highlight.min.js' );
			}
		}
		
		public function shortcode($atts, $content = null)
		{
			// extract the attributes into variables
			extract(shortcode_atts(array(
			
				'fields' => null,
				'taxonomies' => null, //will be deprecated - use `fields` instead
				'submit_label' => null,
				'submitlabel' => null, //will be deprecated - use `submit_label` instead
				'search_placeholder' => "Search &hellip;",
				'types' => "",
				'type' => "", //will be deprecated - use `types` instead
				'headings' => "",
				'all_items_labels' => "",
				'class' => "",
				'post_types' => "",
				'hierarchical' => "",
				'hide_empty' => "",
				'order_by' => "",
				'show_count' => "",
				'order_dir' => "",
				'operators' => "",
				'add_search_param' => "0",
				'empty_search_url' => ""
				
			), $atts));

			//init `fields`
			if($fields!=null)
			{
				$fields = explode(",",$fields);
			}
			else
			{
				$fields = explode(",",$taxonomies);
			}	
			
			$this->taxonomylist = $fields;
			$nofields = count($fields);
			
			$add_search_param = (int)$add_search_param;
			
			
			//init `submitlabel`
			if($submitlabel!=null)
			{//then the old "submitlabel" has been supplied
				
				if($submit_label==null)
				{
					//then the new label has not been supplied so do nothing 
					$submit_label = $submitlabel;
				}
				else
				{
					//then the new label has been supplied so take the new label value
					//$submit_label = $submit_label;
				}
			}
			else if($submitlabel==null)
			{
				if($submit_label==null)
				{//default value
					$submit_label = "Submit"; 
				}
			}
			
			//init `post_types`
			if($post_types!="")
			{
				$post_types = explode(",",$post_types);
			}
			else
			{
				if(in_array("post_types", $fields))
				{
					$post_types = array("all");
				}
				
			}
			
			//init `hierarchical`
			if($hierarchical!="")
			{
				$hierarchical = explode(",",$hierarchical);
			}
			else
			{
				$hierarchical = array("");
			}
			
			//init `hide_empty`
			if($hide_empty!="")
			{
				$hide_empty = explode(",",$hide_empty);
			}
			else
			{
				$hide_empty = array("");
			}
			
			//init `show_count`
			if($show_count!="")
			{
				$show_count = explode(",",$show_count);
			}
			else
			{
				$show_count = array();
			}
			
			//init `order_by`
			if($order_by!="")
			{
				$order_by = explode(",",$order_by);
			}
			else
			{
				$order_by = array("");
			}
			
			//init `order_dir`
			if($order_dir!="")
			{
				$order_dir = explode(",",$order_dir);
			}
			else
			{
				$order_dir = array("");
			}
			
			//init `operators`
			if($operators!="")
			{
				$operators = explode(",",$operators);
			}
			else
			{
				$operators = array("");
			}
			
			
			//init `labels`
			$labels = explode(",",$headings);
			
			if(!is_array($labels))
			{
				$labels = array();
			}
			
			//init `all_items_labels`
			$all_items_labels = explode(",",$all_items_labels);
			
			if(!is_array($all_items_labels))
			{
				$all_items_labels = array();
			}
			
			//init `types`
			if($types!=null)
			{
				$types = explode(",",$types);
			}
			else
			{
				$types = explode(",",$type);
			}
			
			if(!is_array($types))
			{
				$types = array();
			}
			
			//init empty_search_url
			
			
			//Loop through Fields and set up default vars
			for($i=0; $i<$nofields; $i++)
			{//loop through all fields
				
				//set up types
				if(isset($types[$i]))
				{
					if($fields[$i] == 'post_date')
					{//check for post date field
					
						if(($types[$i]!="date")&&($types[$i]!="daterange"))
						{//if not expected value 
							
							$types[$i] = "date"; //use default
						}						
					}
					else
					{//everything else can use a standard form input - checkbox/radio/dropdown/list/multiselect
					
						if(($types[$i]!="select")&&($types[$i]!="checkbox")&&($types[$i]!="radio")&&($types[$i]!="list")&&($types[$i]!="multiselect"))
						{//no accepted type matched - non compatible type defined by user
						
							$types[$i] =  "select"; //use default
						}
					}
				}
				else
				{//omitted, so set default
					
					if($fields[$i] == 'post_date')
					{						
						$types[$i] =  "date";
					}
					else
					{
						$types[$i] =  "select";
					}
				}
				
				//setup labels
				if(!isset($labels[$i]))
				{
					$labels[$i] = "";
				}
				
				//setup all_items_labels
				if(!isset($all_items_labels[$i]))
				{
					$all_items_labels[$i] = "";
				}
				
				
				if(isset($order_by[$i]))
				{
					if(($order_by[$i]!="id")&&($order_by[$i]!="name")&&($order_by[$i]!="slug")&&($order_by[$i]!="count")&&($order_by[$i]!="term_group"))
					{
						$order_by[$i] =  "name"; //use default - possible typo or use of unknown value
					}
				}
				else
				{
					$order_by[$i] =  "name"; //use default
				}
				
				if(isset($order_dir[$i]))
				{
					if(($order_dir[$i]!="asc")&&($order_dir[$i]!="desc"))
					{//then order_dir is not a wanted value
						
						$order_dir[$i] =  "asc"; //set to default
					}
				}
				else
				{
					$order_dir[$i] =  "asc"; //use default
				}
				
				if(isset($operators[$i]))
				{
					if(($operators[$i]!="and")&&($operators[$i]!="or"))
					{
						$operators[$i] =  "and"; //else use default - possible typo or use of unknown value
					}
				}
				else
				{
					$operators[$i] =  "and"; //use default
				}
			
			}
			
			//set all form defaults / dropdowns etc
			$this->set_defaults();

			return $this->get_search_filter_form($submit_label, $search_placeholder, $fields, $types, $labels, $hierarchical, $hide_empty, $show_count, $post_types, $order_by, $order_dir, $operators, $all_items_labels, $empty_search_url, $add_search_param, $class);
		}


		function add_queryvars( $qvars )
		{
			$qvars[] = 'post_types';
			$qvars[] = 'post_date';
			return $qvars;
		}

		function filter_query_post_types($query)
		{
			global $wp_query;

			if(($query->is_main_query())&&(!is_admin()))
			{
				if(isset($wp_query->query['post_types']))
				{
					$search_all = false;

					$post_types = explode(",",esc_attr($wp_query->query['post_types']));
					if(isset($post_types[0]))
					{
						if(count($post_types)==1)
						{
							if($post_types[0]=="all")
							{
								$search_all = true;
							}
						}
					}
					if($search_all)
					{
						$post_types = get_post_types( '', 'names' );
						$query->set('post_type', $post_types); //here we set the post types that we want WP to search
					}
					else
					{
						$query->set('post_type', $post_types); //here we set the post types that we want WP to search
					}
				}
			}

			return $query;
		}
		
		
		function limit_date_range_query( $where )
		{
			global $wp_query;
		
			//get post dates into array
			$post_date = explode("+", esc_attr(urlencode($wp_query->query['post_date'])));
				
			if (count($post_date) > 1 && $post_date[0] != $post_date[1])
			{
				$date_query = array();
				
				if (!empty($post_date[0]))
				{
					$date_query['after'] = date('Y-m-d 00:00:00', strtotime($post_date[0]));
				}
				
				if (!empty($post_date[1]))
				{
					$date_query['before'] = date('Y-m-d 23:59:59', strtotime($post_date[1]));
				}
				
			}
			
			// Append fragment to WHERE clause to select posts newer than the past week.
			$where .= " AND post_date >='" . $date_query['after'] . "' AND post_date <='" . $date_query['before'] . "'";

			return $where;
		}

		/**
		 * Remove the filter limiting posts to the past week.
		 *
		 * Remove the filter after it runs so that it doesn't affect any other
		 * queries that might be performed on the same page (eg. Recent Posts
		 * widget).
		 */
		function remove_limit_date_range_query()
		{
			remove_filter( 'posts_where', 'limit_date_range_query' );
		}	
		
		function fix_blank_search($query)
		{//needs to be re-implemented
		
			if((isset($_GET['s'])) && (empty($_GET['s'])) && ($query->is_main_query()))
			{
				$query->is_search = true;
				$query->is_home = false;
			}
			
		}
		
		function filter_query_post_date($query)
		{
			global $wp_query;

			if(($query->is_main_query())&&(!is_admin()))
			{
				if(isset($wp_query->query['post_date']))
				{
					//get post dates into array
					$post_date = explode("+", esc_attr(urlencode($wp_query->query['post_date'])));
					
					if(!empty($post_date))
					{
						//if there is more than 1 post date and the dates are not the same
						if (count($post_date) > 1 && $post_date[0] != $post_date[1])
						{
							if((!empty($post_date[0]))&&(!empty($post_date[1])))
							{
								// Attach hook to filter WHERE clause.
								add_filter('posts_where', array($this,'limit_date_range_query'));
								
								// Remove the filter after it is executed.
								add_action('posts_selection', array($this,'remove_limit_date_range_query'));
							}
						}
						else
						{ //else we are dealing with one date or both dates are the same (so need to find posts for a single day)
						
							if (!empty($post_date[0]))
							{
								$post_time = strtotime($post_date[0]);
								$query->set('year', date('Y', $post_time));
								$query->set('monthnum', date('m', $post_time));
								$query->set('day', date('d', $post_time));
							}
						}
					}
				}
			}

			return $query;
		}

		/*
		 * check to set defaults - to be called after the shortcodes have been init so we can grab the wanted list of fields
		*/
		public function set_defaults()
		{
			global $wp_query;
			
			$categories = array();
			
			if(isset($wp_query->query['category_name']))
			{
				$category_params = (preg_split("/[,\+ ]/", esc_attr($wp_query->query['category_name']))); //explode with 2 delims
								
				//$category_params = explode("+",esc_attr($wp_query->query['category_name']));
				
				foreach($category_params as $category_param)
				{
					$category = get_category_by_slug( $category_param );
					if(isset($category->cat_ID))
					{
						$categories[] = $category->cat_ID;
					}
				}
			}
			
			$this->defaults[SF_FPRE.'category'] = $categories;


			//grab search term for prefilling search input
			if(isset($wp_query->query['s']))
			{//!"£$%^&*()
				$this->searchterm = trim(get_search_query());
			}

			//check to see if tag is set

			$tags = array();
			
			if(isset($wp_query->query['tag']))
			{
				$tag_params = (preg_split("/[,\+ ]/", esc_attr($wp_query->query['tag']))); //explode with 2 delims
				//$tag_params = explode("+",esc_attr($wp_query->query['tag']));

				foreach($tag_params as $tag_param)
				{
					$tag = get_term_by("slug",$tag_param, "post_tag");
					if(isset($tag->term_id))
					{
						$tags[] = $tag->term_id;
					}
				}
			}
			
			$this->defaults[SF_FPRE.'post_tag'] = $tags;

			$taxs = array();
			//loop through all the query vars
			foreach($wp_query->query as $key=>$val)
			{
				if(!in_array(SF_FPRE.$key, $this->frmqreserved))
				{//make sure the get is not a reserved get as they have already been handled above

					//now check it is a desired key
					if(in_array($key, $this->taxonomylist))
					{
						$taxslug = ($val);
						//$tax_params = explode("+",esc_attr($taxslug));
						
						$tax_params = (preg_split("/[,\+ ]/", esc_attr($taxslug))); //explode with 2 delims

						foreach($tax_params as $tax_param)
						{
							$tax = get_term_by("slug",$tax_param, $key);

							if(isset($tax->term_id))
							{
								$taxs[] = $tax->term_id;
							}
						}

						$this->defaults[SF_FPRE.$key] = $taxs;
					}
				}
			}

			$post_date = array("","");
			if(isset($wp_query->query['post_date']))
			{
				$post_date = explode("+", esc_attr(urlencode($wp_query->query['post_date'])));
				if(count($post_date)==1)
				{
					$post_date[1] = "";
				}
			}
			$this->defaults[SF_FPRE.'post_date'] = $post_date;
			
			
			$post_types = array();
			if(isset($wp_query->query['post_types']))
			{
				$post_types = explode(",",esc_attr($wp_query->query['post_types']));
			}
			$this->defaults[SF_FPRE.'post_types'] = $post_types;
			
		}

		/*
		 * check to see if form has been submitted and handle vars
		*/

		public function check_posts()
		{
			if(isset($_POST[SF_FPRE.'submitted']))
			{
				if($_POST[SF_FPRE.'submitted']==="1")
				{
					//set var to confirm the form was posted
					$this->has_form_posted = true;
				}
			}
			
			/* CATEGORIES */
			if((isset($_POST[SF_FPRE.'category']))&&($this->has_form_posted))
			{
				$the_post_cat = ($_POST[SF_FPRE.'category']);

				//make the post an array for easy looping
				if(!is_array($_POST[SF_FPRE.'category']))
				{
					$post_cat[] = $the_post_cat;
				}
				else
				{
					$post_cat = $the_post_cat;
				}
				$catarr = array();

				foreach ($post_cat as $cat)
				{
					$cat = esc_attr($cat);
					$catobj = get_category($cat);
					
					if(isset($catobj->slug))
					{
						$catarr[] = $catobj->slug;
						//$catarr[] = $catobj->term_id;
					}
				}

				if(count($catarr)>0)
				{
					$operator = "+"; //default behaviour
					
					//check to see if an operator has been specified - only applies with fields that use multiple selects such as checkboxes or multi selects
					if(isset($_POST[SF_FPRE.'category_operator']))
					{
						if($_POST[SF_FPRE.'category_operator']=="and")
						{
							$operator = "+";
						}
						else if($_POST[SF_FPRE.'category_operator']=="or")
						{
							$operator = ",";
						}
						else
						{
							$operator = "+";
						}
					}
					
					$categories = implode($operator,$catarr);

					if(get_option('permalink_structure'))
					{
						//$catrel = trim(str_replace(home_url(), "", get_category_link()), "/").$categories."/"; //get full category link, remvoe the home url to get relative, trim traling slashed, the append slash at the end
						$category_base = (get_option( 'category_base' )=="") ? "category" : get_option( 'category_base' );
						$category_path = $category_base."/".$categories."/";
						$this->urlparams .= $category_path;
					}
					else
					{
						if(!$this->hasqmark)
						{
							$this->urlparams .= "?";
							$this->hasqmark = true;
						}
						else
						{
							$this->urlparams .= "&";
						}
						$this->urlparams .= "category_name=".$categories;
					}
				}
			}

			/* SEARCH BOX */
			if((isset($_POST[SF_FPRE.'search']))&&($this->has_form_posted))
			{
				$this->searchterm = trim(stripslashes($_POST[SF_FPRE.'search']));

				if($this->searchterm!="")
				{
					if(!$this->hasqmark)
					{
						$this->urlparams .= "?";
						$this->hasqmark = true;
					}
					else
					{
						$this->urlparams .= "&";
					}
					$this->urlparams .= "s=".urlencode($this->searchterm);
					$this->hassearchquery = true;
				}
			}
			if(!$this->hassearchquery)
			{
				
				if((isset($_POST[SF_FPRE.'add_search_param']))&&($this->has_form_posted))
				{//this is only set when a search box is displayed - it tells S&F to append a blank search to the URL to indicate a search has been submitted with no terms, however, still load the search template
					
					
					
					if(!$this->hasqmark)
					{
						$this->urlparams .= "?";
						$this->hasqmark = true;
					}
					else
					{
						$this->urlparams .= "&";
					}
					$this->urlparams .= "s=";
				}
			}
			
			/* TAGS */
			if((isset($_POST[SF_FPRE.'post_tag']))&&($this->has_form_posted))
			{
				$the_post_tag = ($_POST[SF_FPRE.'post_tag']);

				//make the post an array for easy looping
				if(!is_array($_POST[SF_FPRE.'post_tag']))
				{
					$post_tag[] = $the_post_tag;
				}
				else
				{
					$post_tag = $the_post_tag;
				}
				
				$tagarr = array();

				foreach ($post_tag as $tag)
				{
					$tag = esc_attr($tag);
					$tagobj = get_tag($tag);

					if(isset($tagobj->slug))
					{
						$tagarr[] = $tagobj->slug;
					}
				}
				
				if(count($tagarr)>0)
				{
					$operator = "+"; //default behaviour
						
					//check to see if an operator has been specified - only applies with fields that use multiple selects such as checkboxes or multi selects
					if(isset($_POST[SF_FPRE.'post_tag_operator']))
					{
						if($_POST[SF_FPRE.'post_tag_operator']=="and")
						{
							$operator = "+";
						}
						else if($_POST[SF_FPRE.'post_tag_operator']=="or")
						{
							$operator = ",";
						}
						else
						{
							$operator = "+";
						}
					}
					
					$tags = implode($operator,$tagarr);

					if(!$this->hasqmark)
					{
						$this->urlparams .= "?";
						$this->hasqmark = true;
					}
					else
					{
						$this->urlparams .= "&";
					}
					$this->urlparams .= "tag=".$tags;

				}
			}
			
			
			/* POST TYPES */
			if((isset($_POST[SF_FPRE.'post_types']))&&($this->has_form_posted))
			{
				$the_post_types = ($_POST[SF_FPRE.'post_types']);

				//make the post an array for easy looping
				if(!is_array($the_post_types))
				{
					$post_types_arr[] = $the_post_types;
				}
				else
				{
					$post_types_arr = $the_post_types;
				}

				$num_post_types = count($post_types_arr);

				for($i=0; $i<$num_post_types; $i++)
				{
					if($post_types_arr[$i]=="0")
					{
						$post_types_arr[$i] = "all";
					}
				}

				if(count($post_types_arr)>0)
				{
					$operator = ","; //default behaviour
						
					//check to see if an operator has been specified - only applies with fields that use multiple selects such as checkboxes or multi selects
					/*if(isset($_POST[SF_FPRE.'post_types_operator']))
					{
						if($_POST[SF_FPRE.'post_types_operator']=="and")
						{
							$operator = "+";
						}
						else if($_POST[SF_FPRE.'post_types_operator']=="or")
						{
							$operator = ",";
						}
						else
						{
							$operator = "+";
						}
					}*/
					
					$post_types = implode($operator,$post_types_arr);
					
					if(!$this->hasqmark)
					{
						$this->urlparams .= "?";
						$this->hasqmark = true;
					}
					else
					{
						$this->urlparams .= "&";
					}
					$this->urlparams .= "post_types=".$post_types;

				}
			}
			
			
			/* POST DATE */
			if((isset($_POST[SF_FPRE.'post_date']))&&($this->has_form_posted))
			{
				$the_post_date = ($_POST[SF_FPRE.'post_date']);

				//make the post an array for easy looping
				if(!is_array($the_post_date))
				{
					$post_date_arr[] = $the_post_date;
				}
				else
				{
					$post_date_arr = $the_post_date;
				}

				$num_post_date = count($post_date_arr);

				for($i=0; $i<$num_post_date; $i++)
				{
					if($post_date_arr[$i]=="0")
					{
						$post_date_arr[$i] = "all";
					}
				}

				if(count($post_date_arr)>0)
				{
					$post_date_count = count($post_date_arr);
					
					if($post_date_count==2)
					{//see if there are 2 elements in arr (second date range selector)
					
						if(($post_date_arr[0]!="")&&($post_date_arr[1]==""))
						{
							$post_date = $post_date_arr[0];
						}
						else if($post_date_arr[1]=="")
						{//if second date range is blank then remove the array element - this remove the addition of a '+' by implode below and only use first element
							unset($post_date_arr[1]);
						}
						else if($post_date_arr[0]=="")
						{
							$post_date = "+".$post_date_arr[1];
						}
						else
						{
							$post_date = implode("+",array_filter($post_date_arr));
						}
					}
					else
					{
						$post_date = $post_date_arr[0];
					}
					
					if(isset($post_date))
					{
						if($post_date!="")
						{
							if(!$this->hasqmark)
							{
								$this->urlparams .= "?";
								$this->hasqmark = true;
							}
							else
							{
								$this->urlparams .= "&";
							}
							$this->urlparams .= "post_date=".$post_date;
						}
					}
				}
			}
			
			
			//now we have dealt with the all the special case fields - search, tags, categories, post_types, post_date

			//loop through the posts - double check that it is the search form that has been posted, otherwise we could be looping through the posts submitted from an entirely unrelated form
			if($this->has_form_posted)
			{
				foreach($_POST as $key=>$val)
				{
					if(!in_array($key, $this->frmreserved))
					{//if the key is not in the reserved array (ie, on a custom taxonomy - not tags, categories, search term, post type & post date)
						
						// strip off all prefixes for custom fields - we just want to do a redirect - no processing
						if (strpos($key, SF_FPRE) === 0)
						{
							$key = substr($key, strlen(SF_FPRE));
						}

						$the_post_tax = $val;

						//make the post an array for easy looping
						if(!is_array($val))
						{
							$post_tax[] = $the_post_tax;
						}
						else
						{
							$post_tax = $the_post_tax;
						}
						$taxarr = array();

						foreach ($post_tax as $tax)
						{
							$tax = esc_attr($tax);
							$taxobj = get_term_by('id',$tax,$key);

							if(isset($taxobj->slug))
							{
								$taxarr[] = $taxobj->slug;
							}
						}
						
						
						if(count($taxarr)>0)
						{
							$operator = "+"; //default behaviour
					
							//check to see if an operator has been specified - only applies with fields that use multiple selects such as checkboxes or multi selects
							if(isset($_POST[SF_FPRE.$key.'_operator']))
							{
								if($_POST[SF_FPRE.$key.'_operator']=="and")
								{
									$operator = "+";
								}
								else if($_POST[SF_FPRE.$key.'_operator']=="or")
								{
									$operator = ",";
								}
								else
								{
									$operator = "+";
								}
							}
						
							$tags = implode($operator,$taxarr);

							if(!$this->hasqmark)
							{
								$this->urlparams .= "?";
								$this->hasqmark = true;
							}
							else
							{
								$this->urlparams .= "&";
							}
							$this->urlparams .=  $key."=".$tags;

						}
					}
				}
			}
			
			
			if($this->has_form_posted)
			{//if the search has been posted, redirect to the newly formed url with all the right params
			
				if($this->urlparams=="/")
				{//check to see if url params are set, if not ("/") then add "?s=" to force load search results, without this it would redirect to the homepage, which may be a custom page with no blog items/results
					$this->urlparams .= "?s=";
				}
				
				if($this->urlparams=="/?s=")
				{//if a blank search was submitted - need to check for this string here in case `add_search_param` has already added a "?s=" to the url
				
					if(isset($_POST[SF_FPRE.'empty_search_url']))
					{//then redirect to the provided empty search url
						
						wp_redirect(esc_url($_POST[SF_FPRE.'empty_search_url']));
						exit;
					}				
				}
				
				wp_redirect((home_url().$this->urlparams));
			}
		}
	
		public function get_search_filter_form($submitlabel, $search_placeholder, $fields, $types, $labels, $hierarchical, $hide_empty, $show_count, $post_types, $order_by, $order_dir, $operators, $all_items_labels, $empty_search_url, $add_search_param, $class)
		{
			$returnvar = '';

			$addclass = "";
			if($class!="")
			{
				$addclass = ' '.$class;
			}

			$returnvar .= '
				<form action="" method="post" class="searchandfilter'.$addclass.'">
					<div>';

					if(!in_array("post_types", $fields))
					{//then the user has not added it to the fields list so the user does not want a post types drop down... so add (if any) the post types to a hidden attribute

						if(($post_types!="")&&(is_array($post_types)))
						{
							foreach($post_types as $post_type)
							{
								$returnvar .= "<input type=\"hidden\" name=\"".SF_FPRE."post_types[]\" value=\"".$post_type."\" />";
							}
						}
					}
					$returnvar .= '
						<ul>';

						$i = 0;
						
						foreach($fields as $field)
						{
							//special cases - post_types & post_date.. all others assumed regular wp taxonomy
							
							if($field == "search")
							{
								$returnvar .=  '<li>';
								if($labels[$i]!="")
								{
									$returnvar .= "<h4>".$labels[$i]."</h4>";
								}
								$clean_searchterm = (esc_attr($this->searchterm));
								$returnvar .=  '<input type="text" name="'.SF_FPRE.'search" placeholder="'.$search_placeholder.'" value="'.$clean_searchterm.'">';
								$returnvar .=  '</li>';
							}
							else if($field == "post_types") //a post can only every have 1 type, so checkboxes & multiselects will always be "OR"
							{//build field array
							
								//check to see if operator is set for this field
								/*if(isset($operators[$i]))
								{
									$operators[$i] = strtolower($operators[$i]);
									
									if(($operators[$i]=="and")||($operators[$i]=="or"))
									{
										$returnvar .= '<input type="hidden" name="'.SF_FPRE.$field.'_operator" value="'.$operators[$i].'" />';
									}
								}*/
								
								
								$returnvar .= $this->build_post_type_element($types, $labels, $post_types, $field, $all_items_labels, $i);

							}
							else if($field == 'post_date')
							{
								$returnvar .= $this->build_post_date_element($labels, $i, $types, $field);
							}
							else
							{	
								$returnvar .= $this->build_taxonomy_element($types, $labels, $field, $hierarchical, $hide_empty, $show_count, $order_by, $order_dir, $operators, $all_items_labels, $i);
							}
							$i++;

						}

						$returnvar .='<li>';
						
						if($add_search_param==1)
						{
							$returnvar .= "<input type=\"hidden\" name=\"".SF_FPRE."add_search_param\" value=\"1\" />";
						}
						
						if($empty_search_url!="")
						{
							$returnvar .= "<input type=\"hidden\" name=\"".SF_FPRE."empty_search_url\" value=\"".esc_url($empty_search_url)."\" />";
						}
						
						
						$returnvar .=
							'<input type="hidden" name="'.SF_FPRE.'submitted" value="1">
							<input type="submit" value="'.$submitlabel.'">
						</li>';

						$returnvar .= "</ul>";
					$returnvar .= '</div>
				</form>';

			return $returnvar;
		}
		
		///////////////////////////////////////////////////////////
		function build_post_date_element($labels, $i, $types, $field)
		{
			$returnvar = "";
			
			$taxonomychildren = array();

			$taxonomychildren = (object)$taxonomychildren;

			$returnvar .= "<li>";
			
			if($labels[$i]!="")
			{
				$returnvar .= "<h4>".$labels[$i]."</h4>";
			}

			$defaultval = "";

			if($types[$i]=="date")
			{
				$returnvar .= $this->generate_date($taxonomychildren, $field, $this->tagid);
			}
			if($types[$i]=="daterange")
			{
				$returnvar .= $this->generate_date($taxonomychildren, $field, 0);
				$returnvar .= "</li><li>";
				$returnvar .= $this->generate_date($taxonomychildren, $field, 1);
			}
			$returnvar .= "</li>";
			
			return $returnvar;
		}
		
		
		function build_post_type_element($types, $labels, $post_types, $field, $all_items_labels, $i)
		{
			$returnvar = "";
			$taxonomychildren = array();
			$post_type_count = count($post_types);

			//then check the post types array
			if(is_array($post_types))
			{
				if(($post_type_count==1)&&($post_types[0]=="all"))
				{
					$args = array('public'   => true);
					$output = 'object'; // names or objects, note names is the default
					$operator = 'and'; // 'and' or 'or'

					$post_types_objs = get_post_types( $args, $output, $operator );

					$post_types = array();

					foreach ( $post_types_objs  as $post_type )
					{
						if($post_type->name!="attachment")
						{
							$tempobject = array();
							$tempobject['term_id'] = $post_type->name;
							$tempobject['cat_name'] = $post_type->labels->name;

							$taxonomychildren[] = (object)$tempobject;

							$post_types[] = $post_type->name;

						}
					}
					$post_type_count = count($post_types_objs);

				}
				else
				{
					foreach($post_types as $post_type)
					{
						//var_dump(get_post_type_object( $post_type ));
						$post_type_data = get_post_type_object( $post_type );

						if($post_type_data)
						{
							$tempobject = array();
							$tempobject['term_id'] = $post_type;
							$tempobject['cat_name'] = $post_type_data->labels->name;

							$taxonomychildren[] = (object)$tempobject;
						}
					}
				}
			}
			$taxonomychildren = (object)$taxonomychildren;

			$returnvar .= "<li>";

			$post_type_labels = array();
			$post_type_labels['name'] = "Post Types";
			$post_type_labels['singular_name'] = "Post Type";
			$post_type_labels['search_items'] = "Search Post Types";
			
			if($all_items_labels[$i]!="")
			{
				$post_type_labels['all_items'] = $all_items_labels[$i];
			}
			else
			{
				$post_type_labels['all_items'] = "All Post Types";
			}

			$post_type_labels = (object)$post_type_labels;

			if($labels[$i]!="")
			{
				$returnvar .= "<h4>".$labels[$i]."</h4>";
			}
			
			if($post_type_count>0)
			{
				$defaultval = implode(",",$post_types);
			}
			else
			{
				$defaultval = "all";
			}

			if($types[$i]=="select")
			{
				$returnvar .= $this->generate_select($taxonomychildren, $field, $this->tagid, $post_type_labels, $defaultval);
			}
			else if($types[$i]=="checkbox")
			{
				
				$returnvar .= $this->generate_checkbox($taxonomychildren, $field, $this->tagid);
			}
			else if($types[$i]=="radio")
			{
				$returnvar .= $this->generate_radio($taxonomychildren, $field, $this->tagid, $post_type_labels, $defaultval);
			}
			$returnvar .= "</li>";
			
			return $returnvar;
		}
		
		//gets all the data for the taxonomy then display as form element
		function build_taxonomy_element($types, $labels, $taxonomy, $hierarchical, $hide_empty, $show_count, $order_by, $order_dir, $operators, $all_items_labels, $i)
		{
			$returnvar = "";
			
			$taxonomydata = get_taxonomy($taxonomy);

			if($taxonomydata)
			{
				$returnvar .= "<li>";
				
				if($labels[$i]!="")
				{
					$returnvar .= "<h4>".$labels[$i]."</h4>";
				}

				$args = array(
					'name' => SF_FPRE . $taxonomy,
					'taxonomy' => $taxonomy,
					'hierarchical' => false,
					'child_of' => 0,
					'echo' => false,
					'hide_if_empty' => false,
					'hide_empty' => true,
					'order' => $order_dir[$i],
					'orderby' => $order_by[$i],
					'show_option_none' => '',
					'show_count' => '0',
					'show_option_all' => '',
					'show_option_all_sf' => ''
				);
				
				if(isset($hierarchical[$i]))
				{
					if($hierarchical[$i]==1)
					{
						$args['hierarchical'] = true;
					}
				}
				
				if(isset($hide_empty[$i]))
				{
					if($hide_empty[$i]==0)
					{
						$args['hide_empty'] = false;
					}
				}
				
				if(isset($show_count[$i]))
				{
					if($show_count[$i]==1)
					{
						$args['show_count'] = true;
					}
				}
				
				if($all_items_labels[$i]!="")
				{
					$args['show_option_all_sf'] = $all_items_labels[$i];
				}
				
				
				
				$taxonomychildren = get_categories($args);

				if($types[$i]=="select")
				{
					$returnvar .= $this->generate_wp_dropdown($args, $taxonomy, $this->tagid, $taxonomydata->labels);
				}
				else if($types[$i]=="checkbox")
				{
					$args['title_li'] = '';
					$args['defaults'] = "";
					if(isset($this->defaults[$args['name']]))
					{
						$args['defaults'] = $this->defaults[$args['name']];
					}
					//$args['show_option_all'] = 0;
					
					$returnvar .= $this->generate_wp_checkbox($args, $taxonomy, $this->tagid, $taxonomydata->labels);
				}
				else if($types[$i]=="radio")
				{
					$args['title_li'] = '';
					$args['defaults'] = "";
					if(isset($this->defaults[$args['name']]))
					{
						$args['defaults'] = $this->defaults[$args['name']];
					}
					
					$returnvar .= $this->generate_wp_radio($args, $taxonomy, $this->tagid, $taxonomydata->labels);
				}
				else if($types[$i]=="multiselect")
				{
					$args['title_li'] = '';
					$args['defaults'] = "";
					if(isset($this->defaults[$args['name']]))
					{
						$args['defaults'] = $this->defaults[$args['name']];
					}
					
					$returnvar .= $this->generate_wp_multiselect($args, $taxonomy, $this->tagid, $taxonomydata->labels);
				}
				
				//check to see if operator is set for this field
				if(isset($operators[$i]))
				{
					$operators[$i] = strtolower($operators[$i]);
					
					if(($operators[$i]=="and")||($operators[$i]=="or"))
					{
						$returnvar .= '<input type="hidden" name="'.SF_FPRE.$taxonomy.'_operator" value="'.$operators[$i].'" />';
					}
				}
				
				$returnvar .= "</li>";
			}
			
			return $returnvar;
		}
		
		
		/*
		 * Display various forms
		*/

		//use wp array walker to enable hierarchical display
		public function generate_wp_dropdown($args, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = '';
			
			if($args['show_option_all_sf']=="")
			{
				$args['show_option_all'] = $labels->all_items != "" ? $labels->all_items : 'All ' . $labels->name;
			}
			else
			{
				$args['show_option_all'] = $args['show_option_all_sf'];
			}
			
			if(isset($this->defaults[SF_FPRE.$name]))
			{
				$defaults = $this->defaults[SF_FPRE . $name];
				if (is_array($defaults)) {
					if (count($defaults) == 1) {
						$args['selected'] = $defaults[0];
					}
				}
				else {
					$args['selected'] = $defaultval;
				}
			}

			$returnvar .= wp_dropdown_categories($args);

			return $returnvar;
		}
		
		//use wp array walker to enable hierarchical display
		public function generate_wp_multiselect($args, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = '<select multiple="multiple" name="'.$args['name'].'[]" class="postform">';
			$returnvar .= walk_taxonomy('multiselect', $args);
			$returnvar .= "</select>";
			
			return $returnvar;
		}
		
		//use wp array walker to enable hierarchical display
		public function generate_wp_checkbox($args, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = '<ul>';
			$returnvar .= walk_taxonomy('checkbox', $args);
			$returnvar .= "</ul>";
			
			return $returnvar;
		}
		
		//use wp array walker to enable hierarchical display
		public function generate_wp_radio($args, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			
			if($args['show_option_all_sf']=="")
			{
				$show_option_all = $labels->all_items != "" ? $labels->all_items : 'All ' . $labels->name;
			}
			else
			{
				$show_option_all = $args['show_option_all_sf'];
			}
			
			$checked = ($defaultval=="0") ? " checked='checked'" : "";
			$returnvar = '<ul>';
			$returnvar .= '<li>'."<label><input type='radio' name='".$args['name']."[]' value='0'$checked /> ".$show_option_all."</label>".'</li>';
			$returnvar .= walk_taxonomy('radio', $args);
			$returnvar .= "</ul>";
			
			return $returnvar;
		}
		
		//generate generic form inputs for use elsewhere, such as post types and non taxonomy fields
		public function generate_select($dropdata, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = "";

			$returnvar .= '<select class="postform" name="'.SF_FPRE.$name.'">';
			if(isset($labels))
			{
				if($labels->all_items!="")
				{//check to see if all items has been registered in field then use this label
					$returnvar .= '<option class="level-0" value="'.$defaultval.'">'.$labels->all_items.'</option>';
				}
				else
				{//check to see if all items has been registered in field then use this label with prefix of "All"
					$returnvar .= '<option class="level-0" value="'.$defaultval.'">All '.$labels->name.'</option>';
				}
			}

			foreach($dropdata as $dropdown)
			{
				$selected = "";

				if(isset($this->defaults[SF_FPRE.$name]))
				{
					$defaults = $this->defaults[SF_FPRE.$name];

					$noselected = count($defaults);

					if(($noselected==1)&&(is_array($defaults))) //there should never be more than 1 default in a select, if there are then don't set any, user is obviously searching multiple values, in the case of a select this must be "all"
					{
						foreach($defaults as $defaultid)
						{
							if($defaultid==$dropdown->term_id)
							{
								$selected = ' selected="selected"';
							}
						}
					}
				}
				$returnvar .= '<option class="level-0" value="'.$dropdown->term_id.'"'.$selected.'>'.$dropdown->cat_name.'</option>';

			}
			$returnvar .= "</select>";

			return $returnvar;
		}
		
		public function generate_checkbox($dropdata, $name, $currentid = 0, $labels = null, $defaultval = '')
		{
			$returnvar = '<ul>';
			
			foreach($dropdata as $dropdown)
			{
				$checked = "";
				
				//check a default has been set
				if(isset($this->defaults[SF_FPRE.$name]))
				{
					$defaults = $this->defaults[SF_FPRE.$name];
					
					$noselected = count($defaults);
					
					if(($noselected>0)&&(is_array($defaults)))
					{
						foreach($defaults as $defaultid)
						{
							if($defaultid==$dropdown->term_id)
							{
								$checked = ' checked="checked"';
							}
						}
					}
				}
				$returnvar .= '<li class="cat-item"><label><input class="postform cat-item" type="checkbox" name="'.SF_FPRE.$name.'[]" value="'.$dropdown->term_id.'"'.$checked.'> '.$dropdown->cat_name.'</label></li>';
			
			}
			
			$returnvar .= '</ul>';
			
			return $returnvar;
		}
		
		
		public function generate_radio($dropdata, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = '<ul>';
			
			if(isset($labels))
			{
				$checked = "";
				if(isset($this->defaults[SF_FPRE.$name]))
				{
					$defaults = $this->defaults[SF_FPRE.$name];
					$noselected = count($defaults);
					
					if($noselected==0)
					{
						$checked = ' checked="checked"';
					}
					else if($noselected==1)
					{
						if($this->defaults[SF_FPRE.$name][0]==$defaultval)
						{
							$checked = ' checked="checked"';
						}
					}
				}
				else
				{
					$checked = ' checked="checked"';
				}
				
				if(isset($this->defaults[SF_FPRE.$name]))
				{
					$defaults = $this->defaults[SF_FPRE.$name];
					if(count($defaults)>1)
					{//then we are dealing with multiple defaults - this means mutliple radios are selected, this is only possible with "ALL" so set as default.
						$checked = ' checked="checked"';
					}
				}
				
				$all_items_name = "";
				if($labels->all_items!="")
				{//check to see if all items has been registered in field then use this label
					$all_items_name = $labels->all_items;
				}
				else
				{//check to see if all items has been registered in field then use this label with prefix of "All"
					$all_items_name = "All ".$labels->name;
				}
				
				$returnvar .= '<li class="cat-item"><label><input class="postform" type="radio" name="'.SF_FPRE.$name.'[]" value="'.$defaultval.'"'.$checked.'> '.$all_items_name.'</label></li>';
			}
			
			foreach($dropdata as $dropdown)
			{
				$checked = "";
				
				//check a default has been set
				if(isset($this->defaults[SF_FPRE.$name]))
				{
					$defaults = $this->defaults[SF_FPRE.$name];
					
					$noselected = count($defaults);
					
					if(($noselected==1)&&(is_array($defaults)))
					{
						foreach($defaults as $defaultid)
						{
							if($defaultid==$dropdown->term_id)
							{
								$checked = ' checked="checked"';
							}
						}
					}
				}
				$returnvar .= '<li class="cat-item"><label><input class="postform" type="radio" name="'.SF_FPRE.$name.'[]" value="'.$dropdown->term_id.'"'.$checked.'> '.$dropdown->cat_name.'</label></li>';
			
			}
			
			$returnvar .= '</ul>';
			
			return $returnvar;
		}
		
		public function generate_date($dropdata, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = '';
			$current_date = '';
			//check a default has been set - upto two possible vars for array 
			
			if(isset($this->defaults[SF_FPRE.$name]))
			{
				$defaults = $this->defaults[SF_FPRE.$name];
				
				$noselected = count($defaults);
				
				if(($noselected>0)&&(is_array($defaults)))
				{
					$current_date = $defaults[$currentid];
				}
			}
			
			$returnvar .= '<input class="postform" type="date" name="'.SF_FPRE.$name.'[]" value="' . $current_date . '" />';

			return $returnvar;
		}
	}
}


function walk_taxonomy( $type = "checkbox", $args = array() ) {

	$args['walker'] = new Taxonomy_Walker($type, $args['name']);
	
	$output = wp_list_categories($args);
	if ( $output )
		return $output;
}




if ( class_exists( 'SearchAndFilter' ) )
{
	global $SearchAndFilter;
	$SearchAndFilter	= new SearchAndFilter();
}

/*
* Includes
*/

// classes
require_once(SEARCHANDFILTER_PLUGIN_DIR."/of-list-table.php");
require_once(SEARCHANDFILTER_PLUGIN_DIR."/of-taxonomy-walker.php");

// admin screens & plugin mods
require_once(SEARCHANDFILTER_PLUGIN_DIR."/of-admin.php");


?>