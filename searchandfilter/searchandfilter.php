<?php
/*
Plugin Name: Search & Filter
Plugin URI: http://www.designsandcode.com/447/wordpress-search-filter-plugin-for-taxonomies/
Description: Search and Filtering system for Pages, Posts, Categories, Tags and Taxonomies
Author: Designs & Code
Author URI: http://www.designsandcode.com/
Version: 1.0.1
Text Domain: searchandfilter
License: GPLv2
*/

// TO DO - i18n http://codex.wordpress.org/I18n_for_WordPress_Developers

/*
* Set up Plugin Globals
*/
if (!defined('SEARCHANDFILTER_VERSION_NUM'))
    define('SEARCHANDFILTER_VERSION_NUM', '1.0.1');
	
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
			$this->frmreserved = array(SEARCHANDFILTER_FPRE."category", SEARCHANDFILTER_FPRE."search", SEARCHANDFILTER_FPRE."post_tag", SEARCHANDFILTER_FPRE."submitted");
			
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
				'submitlabel' => "Submit"
			), $atts));
			$taxonomies = explode(",",$taxonomies);
			$this->taxonomylist = $taxonomies;
			
			//set all form defaults / dropdowns etc
			$this->set_defaults();
			
			return $this->get_search_filter_form($search, $submitlabel, $taxonomies);
		}
		
		/* 
		 * check to set defaults - to be called after the shortcodes have been init so we can grab the wanted list of taxonomies
		*/

		public function set_defaults()
		{
			//var_dump( $this->taxonomylist);
			
			if(is_category())
			{
				$category = get_category( get_query_var( 'cat' ) );
				$this->defaults[SEARCHANDFILTER_FPRE.'category'] = $category->cat_ID;
			}
			else
			{
				$this->defaults[SEARCHANDFILTER_FPRE.'category'] = 0;
			}
			
			//grab search term for prefilling search input
			if(isset($_GET['s']))
			{
				$this->searchterm = esc_attr($_GET['s']);
			}
			
			//check to see if tag is set
			if(isset($_GET['tag']))
			{//Else check the URL for the tag attribute
				$tagslug = esc_attr($_GET['tag']);
				$tagobj = get_term_by('slug',$tagslug,'post_tag');
				$this->defaults[SEARCHANDFILTER_FPRE.'post_tag'] = 0;
				if(isset($tagobj->term_id))
				{
					$this->defaults[SEARCHANDFILTER_FPRE.'post_tag'] = $tagobj->term_id;
				}
			}
			else
			{//else it is still possible that the URL could be website.com/tag/testtag - 
				if(is_tag())
				{
					$tag = get_term_by("slug",get_query_var( 'tag' ), "post_tag");
					$this->defaults[SEARCHANDFILTER_FPRE.'post_tag'] = $tag->term_id;
				}
				else
				{
					$this->defaults[SEARCHANDFILTER_FPRE.'post_tag'] = 0;
				}
			}
			
			//loop through all the gets
			foreach($_GET as $key=>$val)
			{
				if(!in_array(SEARCHANDFILTER_FPRE.$key, $this->frmreserved))
				{//make sure the get is not a reserved get as they have already been handled above
					
					//now check it is a desired key
					if(in_array($key, $this->taxonomylist))
					{
						$taxslug = esc_attr($val);
						$taxobj = get_term_by('slug',$taxslug,$key);
						$this->defaults[SEARCHANDFILTER_FPRE.$key] = 0;
						if(isset($taxobj->term_id))
						{
							$this->defaults[SEARCHANDFILTER_FPRE.$key] = $taxobj->term_id;
						}
					}
				}
			}
			
			//now we may be on a taxonomy page
			if(is_tax())
			{
				$taxobj = get_queried_object();
				$taxid = $taxobj->term_id;
				$this->defaults[SEARCHANDFILTER_FPRE.$taxobj->taxonomy] = $taxobj->term_id;
			}
			
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
				$this->defaults[SEARCHANDFILTER_FPRE.'category'] = esc_attr($_POST[SEARCHANDFILTER_FPRE.'category']);
				$catobj = get_category($this->defaults[SEARCHANDFILTER_FPRE.'category']);
				
				if(isset($catobj->slug))
				{
					// deal with category firstm so we can build a url like:
					// site.com/category/products/?tag=atag&s=searchterm
					// rather than:
					// site.com/?category_name=products&tag=atag&s=searchterm					
					
					if(get_option('permalink_structure'))
					{//if has permalinks use nice formatting as above
						
						$catrel = trim(str_replace(home_url(), "", get_category_link( $catobj->term_id )), "/")."/"; //get full category link, remvoe the home url to get relative, trim traling slashed, the append slash at the end
						$this->urlparams .= $catrel;// old - not reliable - "category/".$catobj->slug."/";
						
					}
					else
					{//otherwise stick everything in to the query string and let wp deal with it
					
						if(!$this->hasqmark)
						{
							$this->urlparams .= "?";
							$this->hasqmark = true;
						}
						else
						{
							$this->urlparams .= "&";
						}
						
						$this->urlparams .= "category_name=".$catobj->slug;
					}
				}
			}
			
			/* SEARCH BOX */
			if((isset($_POST[SEARCHANDFILTER_FPRE.'search']))&&($this->has_search_posted))
			{
				$this->searchterm = urlencode($_POST[SEARCHANDFILTER_FPRE.'search']);
				
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
					$this->urlparams .= "s=".$this->searchterm;
				}
			}
			
			/* TAGS */
			if((isset($_POST[SEARCHANDFILTER_FPRE.'post_tag']))&&($this->has_search_posted))
			{//If the search form has been submitted with a new tag filter
				$this->defaults[SEARCHANDFILTER_FPRE.'post_tag'] = esc_attr($_POST[SEARCHANDFILTER_FPRE.'post_tag']);
				$tagobj = get_tag($this->defaults[SEARCHANDFILTER_FPRE.'post_tag']);
				if(isset($tagobj->slug))
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
					$this->urlparams .= "tag=".$tagobj->slug;
				}
			}
			
			
			//now we have dealt with the all the special case variables - search, tags, categories
			
			/* TAXONOMIES */
			//loop through the posts - double check that it is the search form that has been posted, otherwise we could be looping through the posts submitted from an entirely unrelated form
			foreach($_POST as $key=>$val)
			{
				if(!in_array($key, $this->frmreserved))
				{//if the key is not in the reserved array (ie, on a custom taxonomy - not tags, categories, search term)
					//echo ($key).": ".$val."<br />";
					
					// strip off all prefixes for custom taxonomies - we just want to do a redirect - no processing
					if (strpos($key, SEARCHANDFILTER_FPRE) === 0)
					{
						$key = substr($key, strlen(SEARCHANDFILTER_FPRE));
					}
					
					$temptax = esc_attr($val);
					$taxobj = get_term_by('id',$temptax,$key);
					
					if(isset($taxobj->slug))
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
					
						$this->urlparams .= $key."=".$taxobj->slug;
					}
					
					
					//$this->defaults[$key] = $val;
					//echo $key.": ".$val;
					
				}
			}
			
			if($this->has_search_posted)
			{//if the search has been posted, redirect to the newly formed url with all the right params
			
				wp_redirect( site_url().$this->urlparams );
			}
		
		}
		
		public function get_search_filter_form($search, $submitlabel, $taxonomies)
		{ 
			//ob_start();
			$returnvar = '';
			
			$returnvar .= '
				<form action="" method="post" class="searchandfilter">
					<p>
						<ul>';
						
						if($search==1)
						{
							$returnvar .=  '<li><input type="text" name="ofsearch" placeholder="Search &hellip;" value="'.$this->searchterm.'"></li>';
						}
						
						foreach($taxonomies as $taxonomy)
						{
							
							$taxonomydata = get_taxonomy($taxonomy);
							//var_dump($taxonomydata);
							if($taxonomydata)
							{
								$returnvar .= "<li>";
								$taxonomychildren = get_categories('name=of'.$taxonomy.'&taxonomy='.$taxonomy);
								$returnvar .= $this->generate_dropdown($taxonomychildren, $taxonomy, $this->tagid, $taxonomydata->labels);
								$returnvar .= "</li>";
							}
							
						}
						
						$returnvar .= "</ul>";
					
						$returnvar .=
						'<p>
							<input type="hidden" name="'.SEARCHANDFILTER_FPRE.'submitted" value="1">
							<input type="submit" value="'.$submitlabel.'">
						</p>
					</p>
				</form>';
			
			return $returnvar;
		}
		public function generate_dropdown($dropdata, $name, $currentid = 0, $labels = null)
		{
			$returnvar = "";
			
			$returnvar .= '<select class="postform" name="'.SEARCHANDFILTER_FPRE.$name.'">';
			if(isset($labels))
			{
				if($labels->all_items!="")
				{
					$returnvar .= '<option class="level-0" value="0">'.$labels->all_items.'</option>';
				}
				else
				{
					$returnvar .= '<option class="level-0" value="0">All '.$labels->name.'</option>';
				}
			}
			
			foreach($dropdata as $dropdown)
			{
				$selected = "";
				if(isset($this->defaults[SEARCHANDFILTER_FPRE.$name]))
				{
					if($this->defaults[SEARCHANDFILTER_FPRE.$name]==$dropdown->term_id)
					{
						$selected = ' selected="selected"';
					}
				}
				$returnvar .= '<option class="level-0" value="'.$dropdown->term_id.'"'.$selected.'>'.$dropdown->cat_name.'</option>';
			
			}
			$returnvar .= "</select>";
			
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