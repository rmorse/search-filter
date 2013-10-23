<?php
/*
Plugin Name: Search & Filter
Plugin URI: http://www.designsandcode.com/447/wordpress-search-filter-plugin-for-taxonomies/
Description: Search and Filtering system for Pages, Posts, Categories, Tags and Taxonomies
Author: Designs & Code
Author URI: http://www.designsandcode.com/
Version: 1.1.3-dev1
Text Domain: searchandfilter
License: GPLv2
*/

// TO DO - i18n http://codex.wordpress.org/I18n_for_WordPress_Developers

/*
* Set up Plugin Globals
*/
if (!defined('SEARCHANDFILTER_VERSION_NUM'))
    define('SEARCHANDFILTER_VERSION_NUM', '1.1.3-dev1');

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
if (!defined('SEARCHANDFILTER_FPRE'))
    define('SEARCHANDFILTER_FPRE', 'of');

add_option(SEARCHANDFILTER_VERSION_KEY, SEARCHANDFILTER_VERSION_NUM);

/*
* Set up Plugin Globals
*/
if ( ! class_exists( 'SearchAndFilter' ) )
{
	class SearchAndFilter
	{
		private $has_search_posted = false;
		private $hasqmark = false;
		private $urlparams = "/";
		private $searchterm = "";
		private $tagid = 0;
		private $catid = 0;
		private $defaults = array();
		private $frmreserved = array();
		private $taxonomylist = array();

		public function __construct()
		{
			// Set up reserved taxonomies
			$this->frmreserved = array(SEARCHANDFILTER_FPRE."category", SEARCHANDFILTER_FPRE."search", SEARCHANDFILTER_FPRE."post_tag", SEARCHANDFILTER_FPRE."submitted", SEARCHANDFILTER_FPRE."post_date", SEARCHANDFILTER_FPRE."post_types");
			$this->frmqreserved = array(SEARCHANDFILTER_FPRE."category_name", SEARCHANDFILTER_FPRE."s", SEARCHANDFILTER_FPRE."tag", SEARCHANDFILTER_FPRE."submitted", SEARCHANDFILTER_FPRE."post_date", SEARCHANDFILTER_FPRE."post_types"); //same as reserved

			//add query vars
			add_filter('query_vars', array($this,'add_queryvars') );

			//filter post type & date if it is set
			add_filter('pre_get_posts', array($this,'filter_query_post_types'));
			add_filter('pre_get_posts', array($this,'filter_query_post_date'));

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
				'search' => 1,
				'taxonomies' => null,
				'submitlabel' => "Submit",
				'type' => "",
				'label' => "default",
				'class' => "",
				'post_types' => "",
				'show_post_types' => "0"
			), $atts));


			$taxonomies = explode(",",$taxonomies);

			if($post_types!="")
			{
				$post_types = explode(",",$post_types);
			}

			$this->taxonomylist = $taxonomies;
			$notaxonomies = count($taxonomies);


			//set default types for each taxonomy
			$types = explode(",",$type);
			if(!is_array($types))
			{
				$types = array();
			}

			$labels = explode(",",$label);
			$showlabels = true;
			$labeldefault = "name";

			if(!is_array($labels))
			{
				$labels = array("default");
			}

			for($i=0; $i<$notaxonomies; $i++)
			{//loop through all taxonomies

				//set up types
				if(isset($types[$i]))
				{

					if(($types[$i]=="select")||($types[$i]=="checkbox")||($types[$i]=="radio")||($types[$i]=="datepicker"))
					{
						$types[$i] =  $types[$i];
					}
					else
					{
						$types[$i] =  "select";
					}
				}
				else
				{
					$types[$i] =  "select";
				}



				if(isset($labels[0]))
				{//these means at least one option has been set

					if(($labels[0]=="0")||($labels[0]=="none"))
					{
						if($i!=0)
						{
							$labels[$i] = ""; //then set all fields to blank ("no label").
						}
					}
					else if($labels[0]=="default")
					{
						$labels[$i] =  $labeldefault;
					}
					else
					{//then one or more options were passed, and the value wasn't "0" or "none"

						if(isset($labels[$i]))
						{
							if(($labels[$i]=="name")||($labels[$i]=="singular_name")||($labels[$i]=="search_items")||($labels[$i]=="all_items"))
							{//enforce the use of only above mentioned labels, don't support the other ones or just any data supplied by the user

								$labels[$i] =  $labels[$i];
							}
							else if($labels[$i]=="")
							{//if it is blank no label should be shown for this taxonomy

								$labels[$i] =  "";
							}
							else
							{//this normally means a typos or unrecognised type, so use default

								$labels[$i] =  $labeldefault;
							}
						}
						else
						{
							$labels[$i] =  $labeldefault;
						}
					}
				}
				else
				{//then it has been completely omitted so use default display
					$labels[$i] =  $labeldefault;
				}


			}

			//if we have a value of 0 or "none" in teh first part of the array, the we need to set it to blanks so it doesn't display
			if(isset($labels[0]))
			{
				if(($labels[0]=="0")||($labels[0]=="none"))
				{
					$labels[0] = ""; //then set all fields to blank ("no label").
				}
			}

			//set all form defaults / dropdowns etc
			$this->set_defaults();

			return $this->get_search_filter_form($search, $submitlabel, $taxonomies, $types, $labels, $post_types, $class);
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

			if(isset($wp_query->query['post_types']))
			{
				$search_all = false;

				$post_types = explode("+",esc_attr(urlencode($wp_query->query['post_types'])));
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

			return $query;
		}

		function filter_query_post_date($query)
		{
			global $wp_query;

			if(isset($wp_query->query['post_date']))
			{
				$post_date = esc_attr(urlencode($wp_query->query['post_date']));
				if(!empty($post_date)) {
					$post_time = strtotime($post_date);
					$query->set('year', date('Y', $post_time));
					$query->set('nummonth', date('m', $post_time));
					$query->set('day', date('d', $post_time));
				}
			}

			return $query;
		}

		/*
		 * check to set defaults - to be called after the shortcodes have been init so we can grab the wanted list of taxonomies
		*/
		public function set_defaults()
		{
			global $wp_query;

			/*var_dump($wp_query->query['category_name']);
			var_dump($wp_query->query['tag']);*/
			/*echo "<pre>";
			var_dump($wp_query->query);
			echo "</pre>";*/

			$categories = array();
			//if(is_category())
			//{
				if(isset($wp_query->query['category_name']))
				{

					$category_params = explode("+",esc_attr($wp_query->query['category_name']));

					foreach($category_params as $category_param)
					{
						$category = get_category_by_slug( $category_param );
						if(isset($category->cat_ID))
						{
							$categories[] = $category->cat_ID;
						}
					}
				}
			//}
			$this->defaults[SEARCHANDFILTER_FPRE.'category'] = $categories;


			//grab search term for prefilling search input
			if(isset($wp_query->query['s']))
			{//!"£$%^&*()
				$this->searchterm = get_search_query();
			}

			//check to see if tag is set

			$tags = array();
			//if(is_tag())
			//{
				if(isset($wp_query->query['tag']))
				{
					$tag_params = explode("+",esc_attr($wp_query->query['tag']));

					foreach($tag_params as $tag_param)
					{
						$tag = get_term_by("slug",$tag_param, "post_tag");
						if(isset($tag->term_id))
						{
							$tags[] = $tag->term_id;
						}
					}
				}
			//}
			$this->defaults[SEARCHANDFILTER_FPRE.'post_tag'] = $tags;

			$taxs = array();
			//loop through all the query vars
			foreach($wp_query->query as $key=>$val)
			{
				if(!in_array(SEARCHANDFILTER_FPRE.$key, $this->frmqreserved))
				{//make sure the get is not a reserved get as they have already been handled above

					//now check it is a desired key
					if(in_array($key, $this->taxonomylist))
					{
						$taxslug = ($val);
						$tax_params = explode("+",esc_attr($taxslug));

						foreach($tax_params as $tax_param)
						{
							$tax = get_term_by("slug",$tax_param, $key);

							if(isset($tax->term_id))
							{
								$taxs[] = $tax->term_id;
							}
						}

						$this->defaults[SEARCHANDFILTER_FPRE.$key] = $taxs;
					}
				}
			}

			$post_date = '';
			if(isset($wp_query->query['post_date']))
			{
				$post_date = esc_attr(urlencode($wp_query->query['post_date']));
			}
			$this->defaults[SEARCHANDFILTER_FPRE.'post_date'] = $post_date;

			$post_types = array();
			if(isset($wp_query->query['post_types']))
			{
				$post_types = explode("+",esc_attr(urlencode($wp_query->query['post_types'])));
			}
			$this->defaults[SEARCHANDFILTER_FPRE.'post_types'] = $post_types;

			//now we may be on a taxonomy page
			/*if(is_tax())
			{
				$taxobj = get_queried_object();
				$taxid = $taxobj->term_id;
				$this->defaults[SEARCHANDFILTER_FPRE.$taxobj->taxonomy] = $taxobj->term_id;
			}*/

		}

		/*
		 * check to see if form has been submitted and handle vars
		*/

		public function check_posts()
		{
			if(isset($_POST[SEARCHANDFILTER_FPRE.'submitted']))
			{
				if($_POST[SEARCHANDFILTER_FPRE.'submitted']==="1")
				{
					//set var to confirm the form was posted
					$this->has_search_posted = true;
				}
			}

			/* CATEGORIES */
			if((isset($_POST[SEARCHANDFILTER_FPRE.'category']))&&($this->has_search_posted))
			{
				$the_post_cat = ($_POST[SEARCHANDFILTER_FPRE.'category']);

				//make the post an array for easy looping
				if(!is_array($_POST[SEARCHANDFILTER_FPRE.'category']))
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
					$categories = implode("+",$catarr);

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
			if((isset($_POST[SEARCHANDFILTER_FPRE.'search']))&&($this->has_search_posted))
			{
				$this->searchterm = stripslashes($_POST[SEARCHANDFILTER_FPRE.'search']);

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
				}
			}

			/* TAGS */
			if((isset($_POST[SEARCHANDFILTER_FPRE.'post_tag']))&&($this->has_search_posted))
			{
				$the_post_tag = ($_POST[SEARCHANDFILTER_FPRE.'post_tag']);

				//make the post an array for easy looping
				if(!is_array($_POST[SEARCHANDFILTER_FPRE.'post_tag']))
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
					$tags = implode("+",$tagarr);

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

			//now we have dealt with the all the special case variables - search, tags, categories

			/* TAXONOMIES */
			//loop through the posts - double check that it is the search form that has been posted, otherwise we could be looping through the posts submitted from an entirely unrelated form
			if($this->has_search_posted)
			{
				foreach($_POST as $key=>$val)
				{
					if(!in_array($key, $this->frmreserved))
					{//if the key is not in the reserved array (ie, on a custom taxonomy - not tags, categories, search term)

						// strip off all prefixes for custom taxonomies - we just want to do a redirect - no processing
						if (strpos($key, SEARCHANDFILTER_FPRE) === 0)
						{
							$key = substr($key, strlen(SEARCHANDFILTER_FPRE));
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
							$tags = implode("+",$taxarr);

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

			/* POST DATE */
			if((isset($_POST[SEARCHANDFILTER_FPRE.'post_date']))&&($this->has_search_posted))
			{
				$the_post_date = ($_POST[SEARCHANDFILTER_FPRE.'post_date']);

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
					$post_date = implode("+",$post_date_arr);

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

			/* POST TYPES */
			if((isset($_POST[SEARCHANDFILTER_FPRE.'post_types']))&&($this->has_search_posted))
			{

				$the_post_types = ($_POST[SEARCHANDFILTER_FPRE.'post_types']);

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
					$post_types = implode("+",$post_types_arr);

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

			if($this->has_search_posted)
			{//if the search has been posted, redirect to the newly formed url with all the right params

				if($this->urlparams=="/")
				{//check to see if url params are set, if not ("/") then add "?s=" to force load search results, without this it would redirect to the homepage, which may be a custom page with no blog items/results
					$this->urlparams .= "?s=";
				}
				wp_redirect( (home_url().$this->urlparams) );
			}

		}

		public function get_search_filter_form($search, $submitlabel, $taxonomies, $types, $labels, $post_types, $class)
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

					if(!in_array("post_types", $taxonomies))
					{//then the user has not added it to the taxonomies list so the user does not want a post types drop down... so add (if any) the post types to a hidden attribute

						if(($post_types!="")&&(is_array($post_types)))
						{
							foreach($post_types as $post_type)
							{
								$returnvar .= "<input type=\"hidden\" name=\"ofpost_types[]\" value=\"".$post_type."\" />";
							}
						}
					}
					$returnvar .= '
						<ul>';

						if($search==1)
						{

							$clean_searchterm = (esc_attr($this->searchterm));
							$returnvar .=  '<li><input type="text" name="ofsearch" placeholder="Search &hellip;" value="'.$clean_searchterm.'"></li>';
						}

						$i = 0;

						foreach($taxonomies as $taxonomy)
						{

							if($taxonomy == "post_types")
							{//build taxonomy array

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
								$post_type_labels['all_items'] = "All Post Types";

								$post_type_labels = (object)$post_type_labels;

								if($labels[$i]!="")
								{
									$returnvar .= "<h4>".$post_type_labels->name."</h4>";
								}

								if($post_type_count>0)
								{
									$defaultval = implode("+",$post_types);
								}
								else
								{
									$defaultval = "all";
								}

								if($types[$i]=="select")
								{
									$returnvar .= $this->generate_select($taxonomychildren, $taxonomy, $this->tagid, $post_type_labels, $defaultval);
								}
								else if($types[$i]=="checkbox")
								{
									$returnvar .= $this->generate_checkbox($taxonomychildren, $taxonomy, $this->tagid);
								}
								else if($types[$i]=="radio")
								{
									$returnvar .= $this->generate_radio($taxonomychildren, $taxonomy, $this->tagid, $post_type_labels, $defaultval);
								}
								$returnvar .= "</li>";

							}
							else if($taxonomy == 'post_date') {

								$taxonomychildren[] = array();

								$taxonomychildren = (object)$taxonomychildren;

								$returnvar .= "<li>";

								$post_date_labels = array();
								$post_date_labels['name'] = "Post Date";
								$post_date_labels['singular_name'] = "Post Date";
								$post_date_labels['search_items'] = "Search Post Dates";
								$post_date_labels['all_items'] = "All Post Dates";

								$post_date_labels = (object)$post_date_labels;

								if($labels[$i]!="")
								{
									$returnvar .= "<h4>".$post_date_labels->name."</h4>";
								}

								$defaultval = "";

								if($types[$i]=="datepicker")
								{
									$returnvar .= $this->generate_datepicker($taxonomychildren, $taxonomy, $this->tagid);
								}
								$returnvar .= "</li>";
							}
							else
							{
								$taxonomydata = get_taxonomy($taxonomy);

								if($taxonomydata)
								{
									$returnvar .= "<li>";

									if($labels[$i]!="")
									{
										$returnvar .= "<h4>".$taxonomydata->labels->{$labels[$i]}."</h4>";
									}

									$taxonomychildren = get_categories('name=of'.$taxonomy.'&taxonomy='.$taxonomy);

									if($types[$i]=="select")
									{
										$returnvar .= $this->generate_select($taxonomychildren, $taxonomy, $this->tagid, $taxonomydata->labels);
									}
									else if($types[$i]=="checkbox")
									{
										$returnvar .= $this->generate_checkbox($taxonomychildren, $taxonomy, $this->tagid);
									}
									else if($types[$i]=="radio")
									{
										$returnvar .= $this->generate_radio($taxonomychildren, $taxonomy, $this->tagid, $taxonomydata->labels);
									}
									$returnvar .= "</li>";
								}
							}
							$i++;

						}

						$returnvar .=
						'<li>
							<input type="hidden" name="'.SEARCHANDFILTER_FPRE.'submitted" value="1">
							<input type="submit" value="'.$submitlabel.'">
						</li>';

						$returnvar .= "</ul>";
					$returnvar .= '</div>
				</form>';

			return $returnvar;
		}
		public function generate_select($dropdata, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = "";

			$returnvar .= '<select class="postform" name="'.SEARCHANDFILTER_FPRE.$name.'">';
			if(isset($labels))
			{
				if($labels->all_items!="")
				{//check to see if all items has been registered in taxonomy then use this label
					$returnvar .= '<option class="level-0" value="'.$defaultval.'">'.$labels->all_items.'</option>';
				}
				else
				{//check to see if all items has been registered in taxonomy then use this label with prefix of "All"
					$returnvar .= '<option class="level-0" value="'.$defaultval.'">All '.$labels->name.'</option>';
				}
			}

			foreach($dropdata as $dropdown)
			{
				$selected = "";

				if(isset($this->defaults[SEARCHANDFILTER_FPRE.$name]))
				{
					$defaults = $this->defaults[SEARCHANDFILTER_FPRE.$name];

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
			$returnvar = "";
			foreach($dropdata as $dropdown)
			{
				$checked = "";

				//check a default has been set
				if(isset($this->defaults[SEARCHANDFILTER_FPRE.$name]))
				{
					$defaults = $this->defaults[SEARCHANDFILTER_FPRE.$name];

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
				$returnvar .= '<label><input class="postform" type="checkbox" name="'.SEARCHANDFILTER_FPRE.$name.'[]" value="'.$dropdown->term_id.'"'.$checked.'> '.$dropdown->cat_name.'</label>';

			}

			return $returnvar;
		}

		public function generate_radio($dropdata, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = "";

			if(isset($labels))
			{
				$checked = "";
				if(isset($this->defaults[SEARCHANDFILTER_FPRE.$name]))
				{
					$defaults = $this->defaults[SEARCHANDFILTER_FPRE.$name];
					$noselected = count($defaults);

					if($noselected==0)
					{
						$checked = ' checked="checked"';
					}
					else if($noselected==1)
					{
						if($this->defaults[SEARCHANDFILTER_FPRE.$name][0]==$defaultval)
						{
							$checked = ' checked="checked"';
						}
					}
				}
				else
				{
					$checked = ' checked="checked"';
				}

				if(isset($this->defaults[SEARCHANDFILTER_FPRE.$name]))
				{
					$defaults = $this->defaults[SEARCHANDFILTER_FPRE.$name];
					if(count($defaults)>1)
					{//then we are dealing with multiple defaults - this means mutliple radios are selected, this is only possible with "ALL" so set as default.
						$checked = ' checked="checked"';
					}
				}

				if($labels->all_items!="")
				{//check to see if all items has been registered in taxonomy then use this label
					$returnvar .= '<label><input class="postform" type="radio" name="'.SEARCHANDFILTER_FPRE.$name.'[]" value="'.$defaultval.'"'.$checked.'> '.$labels->all_items.'</label>';
				}
				else
				{//check to see if all items has been registered in taxonomy then use this label with prefix of "All"
					$returnvar .= '<label><input class="postform" type="radio" name="'.SEARCHANDFILTER_FPRE.$name.'[]" value="'.$defaultval.'"'.$checked.'> '.$labels->name.'</label>';
				}
			}

			foreach($dropdata as $dropdown)
			{
				$checked = "";

				//check a default has been set
				if(isset($this->defaults[SEARCHANDFILTER_FPRE.$name]))
				{
					$defaults = $this->defaults[SEARCHANDFILTER_FPRE.$name];

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
				$returnvar .= '<label><input class="postform" type="radio" name="'.SEARCHANDFILTER_FPRE.$name.'[]" value="'.$dropdown->term_id.'"'.$checked.'> '.$dropdown->cat_name.'</label>';

			}

			return $returnvar;
		}

		public function generate_datepicker($dropdata, $name, $currentid = 0, $labels = null, $defaultval = "0")
		{
			$returnvar = '';

			$defaults = $this->defaults[SEARCHANDFILTER_FPRE.$name];
			$returnvar .= '<label><input class="postform" type="text" name="'.SEARCHANDFILTER_FPRE.$name.'[]" value="'.$defaults.'" /></label>';

			return $returnvar;
		}
	}
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

// admin screens & plugin mods
require_once(SEARCHANDFILTER_PLUGIN_DIR."/of-admin.php");


?>