<?php
/*
Plugin Name: Search & Filter
Plugin URI: https://free.searchandfilter.com/
Description: Search and Filtering system for Pages, Posts, Categories, Tags and Taxonomies
Author: Code Amp
Author URI: https://codeamp.com
Version: 1.2.16
Text Domain: searchandfilter
License: GPLv2
*/

/*
* Set up Plugin Globals
*/
if ( ! defined( 'SEARCHANDFILTER_VERSION_NUM' ) ) {
	define( 'SEARCHANDFILTER_VERSION_NUM', '1.2.16' );
}

if ( ! defined( 'SEARCHANDFILTER_THEME_DIR' ) ) {
	define( 'SEARCHANDFILTER_THEME_DIR', ABSPATH . 'wp-content/themes/' . get_template() );
}

if ( ! defined( 'SEARCHANDFILTER_PLUGIN_NAME' ) ) {
	define( 'SEARCHANDFILTER_PLUGIN_NAME', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) );
}

if ( ! defined( 'SEARCHANDFILTER_PLUGIN_DIR' ) ) {
	define( 'SEARCHANDFILTER_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . SEARCHANDFILTER_PLUGIN_NAME );
}

if ( ! defined( 'SEARCHANDFILTER_PLUGIN_URL' ) ) {
	define( 'SEARCHANDFILTER_PLUGIN_URL', WP_PLUGIN_URL . '/' . SEARCHANDFILTER_PLUGIN_NAME );
}

if ( ! defined( 'SEARCHANDFILTER_BASENAME' ) ) {
	define( 'SEARCHANDFILTER_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'SEARCHANDFILTER_VERSION_KEY' ) ) {
	define( 'SEARCHANDFILTER_VERSION_KEY', 'searchandfilter_version' );
}

// Form prefix for plugin.
if ( ! defined( 'SF_FPRE' ) ) {
	define( 'SF_FPRE', 'of' );
}

add_option( SEARCHANDFILTER_VERSION_KEY, SEARCHANDFILTER_VERSION_NUM );

/*
* Set up Plugin Globals
*/
if ( ! class_exists( 'SearchAndFilter' ) ) {
	class SearchAndFilter {

		private $has_form_posted = false;
		private $hasqmark        = false;
		private $hassearchquery  = false;
		private $urlparams       = '/';
		private $searchterm      = '';
		private $tagid           = 0;
		private $catid           = 0;
		private $defaults        = array();
		private $frmreserved     = array();
		private $taxonomylist    = array();

		public function __construct() {
			// Set up reserved fields.
			$this->frmreserved  = array( SF_FPRE . 'category', SF_FPRE . 'search', SF_FPRE . 'post_tag', SF_FPRE . 'submitted', SF_FPRE . 'post_date', SF_FPRE . 'post_types' );
			$this->frmqreserved = array( SF_FPRE . 'category_name', SF_FPRE . 's', SF_FPRE . 'tag', SF_FPRE . 'submitted', SF_FPRE . 'post_date', SF_FPRE . 'post_types' ); // same as reserved

			// Add query vars.
			add_filter( 'query_vars', array( $this, 'add_queryvars' ) );

			// Filter post type & date if it is set.
			add_filter( 'pre_get_posts', array( $this, 'filter_query_post_types' ) );
			add_filter( 'pre_get_posts', array( $this, 'filter_query_post_date' ) );

			// Add shortcode support for widgets.
			add_shortcode( 'searchandfilter', array( $this, 'shortcode' ) );
			add_filter( 'widget_text', 'do_shortcode' );

			// Check the header to see if the form has been submitted.
			add_action( 'template_redirect', array( $this, 'check_posts' ) );

			// Add styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'of_enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'of_enqueue_admin_ss' ) );

		}

		public function of_enqueue_styles() {
			wp_enqueue_style( 'searchandfilter', SEARCHANDFILTER_PLUGIN_URL . '/style.css', false, 1.0, 'all' );
		}
		public function of_enqueue_admin_ss( $hook ) {
			if ( 'toplevel_page_searchandfilter-settings' == $hook ) {
				wp_enqueue_style( 'of_syntax_style', SEARCHANDFILTER_PLUGIN_URL . '/admin/github.css', false, 1.0, 'all' );
				wp_enqueue_style( 'of_style', SEARCHANDFILTER_PLUGIN_URL . '/admin/style.css', false, 1.0, 'all' );
			}
		}

		public function shortcode( $atts, $content = null ) {
			// Extract the attributes into variables.
			extract(
				shortcode_atts(
					array(

						'fields'             => null,
						'taxonomies'         => null, // Will be deprecated - use `fields` instead.
						'submit_label'       => null,
						'submitlabel'        => null, // Will be deprecated - use `submit_label` instead.
						'search_placeholder' => 'Search &hellip;',
						'types'              => '',
						'type'               => '', // Will be deprecated - use `types` instead.
						'headings'           => '',
						'all_items_labels'   => '',
						'class'              => '',
						'post_types'         => '',
						'hierarchical'       => '',
						'hide_empty'         => '',
						'order_by'           => '',
						'show_count'         => '',
						'order_dir'          => '',
						'operators'          => '',
						'add_search_param'   => '0',
						'empty_search_url'   => '',

					),
					$atts
				)
			);

			// Init `fields`.
			if ( $fields != null ) {
				$fields = explode( ',', $fields );
			} else {
				$fields = explode( ',', $taxonomies );
			}

			$this->taxonomylist = $fields;
			$nofields           = count( $fields );

			$add_search_param = (int) $add_search_param;

			// Init `submitlabel`.
			if ( $submitlabel !== null ) {
				// Then the old "submitlabel" has been supplied.
				if ( $submit_label === null ) {
					// Then the new label has not been supplied so do nothing.
					$submit_label = $submitlabel;
				}
			} elseif ( $submitlabel === null ) {
				if ( $submit_label === null ) {
					$submit_label = 'Submit';
				}
			}

			// Init `post_types`.
			if ( $post_types !== '' ) {
				$post_types = explode( ',', $post_types );
			} else {
				if ( in_array( 'post_types', $fields, true ) ) {
					$post_types = array( 'all' );
				}
			}

			// Init `hierarchical`.
			if ( $hierarchical != '' ) {
				$hierarchical = explode( ',', $hierarchical );
			} else {
				$hierarchical = array( '' );
			}

			// Init `hide_empty`.
			if ( $hide_empty != '' ) {
				$hide_empty = explode( ',', $hide_empty );
			} else {
				$hide_empty = array( '' );
			}

			// Init `show_count`.
			if ( $show_count !== '' ) {
				$show_count = explode( ',', $show_count );
			} else {
				$show_count = array();
			}

			// Init `order_by`.
			if ( $order_by !== '' ) {
				$order_by = explode( ',', $order_by );
			} else {
				$order_by = array( '' );
			}

			// Init `order_dir`.
			if ( $order_dir !== '' ) {
				$order_dir = explode( ',', $order_dir );
			} else {
				$order_dir = array( '' );
			}

			// Init `operators`.
			if ( $operators !== '' ) {
				$operators = explode( ',', $operators );
			} else {
				$operators = array( '' );
			}

			// Init `labels`.
			$labels = explode( ',', $headings );

			if ( ! is_array( $labels ) ) {
				$labels = array();
			}

			// Init `all_items_labels`.
			$all_items_labels = explode( ',', $all_items_labels );

			if ( ! is_array( $all_items_labels ) ) {
				$all_items_labels = array();
			}

			// Init `types`.
			if ( $types != null ) {
				$types = explode( ',', $types );
			} else {
				$types = explode( ',', $type );
			}

			if ( ! is_array( $types ) ) {
				$types = array();
			}

			// Loop through Fields and set up default vars.
			for ( $i = 0; $i < $nofields; $i++ ) {
				// Set up types.
				if ( isset( $types[ $i ] ) ) {
					// Check for post date field.
					if ( $fields[ $i ] == 'post_date' ) {
						if ( ( $types[ $i ] != 'date' ) && ( $types[ $i ] != 'daterange' ) ) {
							// If not expected value use default.
							$types[ $i ] = 'date';
						}
					} else {
						// Everything else can use a standard form input - checkbox/radio/dropdown/list/multiselect.
						if ( ( $types[ $i ] !== 'select' ) && ( $types[ $i ] !== 'checkbox' ) && ( $types[ $i ] !== 'radio' ) && ( $types[ $i ] !== 'list' ) && ( $types[ $i ] !== 'multiselect' ) ) {
							$types[ $i ] = 'select'; // Use default.
						}
					}
				} else {
					// Omitted, so set default.
					if ( $fields[ $i ] === 'post_date' ) {
						$types[ $i ] = 'date';
					} else {
						$types[ $i ] = 'select';
					}
				}

				// Setup labels.
				if ( ! isset( $labels[ $i ] ) ) {
					$labels[ $i ] = '';
				}

				// Setup all_items_labels.
				if ( ! isset( $all_items_labels[ $i ] ) ) {
					$all_items_labels[ $i ] = '';
				}

				if ( isset( $order_by[ $i ] ) ) {
					if ( ( $order_by[ $i ] !== 'id' ) && ( $order_by[ $i ] !== 'name' ) && ( $order_by[ $i ] !== 'slug' ) && ( $order_by[ $i ] !== 'count' ) && ( $order_by[ $i ] !== 'term_group' ) ) {
						$order_by[ $i ] = 'name'; // Use default - possible typo or use of unknown value.
					}
				} else {
					$order_by[ $i ] = 'name'; // Use default.
				}

				if ( isset( $order_dir[ $i ] ) ) {
					if ( ( $order_dir[ $i ] !== 'asc' ) && ( $order_dir[ $i ] !== 'desc' ) ) {
						// Then order_dir is not a wanted value, set to default.
						$order_dir[ $i ] = 'asc';
					}
				} else {
					$order_dir[ $i ] = 'asc'; // Use default value.
				}

				if ( isset( $operators[ $i ] ) ) {
					$operators[ $i ] = strtolower( $operators[ $i ] );

					if ( ( $operators[ $i ] !== 'and' ) && ( $operators[ $i ] !== 'or' ) ) {
						$operators[ $i ] = 'and'; // Else use default - possible typo or use of unknown value.
					}
				} else {
					$operators[ $i ] = 'and'; // Use default value.
				}
			}

			// Set all form defaults / dropdowns etc.
			$this->set_defaults();

			return $this->get_search_filter_form( $submit_label, $search_placeholder, $fields, $types, $labels, $hierarchical, $hide_empty, $show_count, $post_types, $order_by, $order_dir, $operators, $all_items_labels, $empty_search_url, $add_search_param, $class );
		}


		public function add_queryvars( $qvars ) {
			$qvars[] = 'post_types';
			$qvars[] = 'post_date';
			return $qvars;
		}

		public function filter_query_post_types( $query ) {
			global $wp_query;

			if ( ( $query->is_main_query() ) && ( ! is_admin() ) ) {
				if ( isset( $wp_query->query['post_types'] ) ) {
					$search_all = false;

					$post_types = explode( ',', esc_attr( $wp_query->query['post_types'] ) );
					if ( isset( $post_types[0] ) ) {
						if ( count( $post_types ) == 1 ) {
							if ( $post_types[0] == 'all' ) {
								$search_all = true;
							}
						}
					}
					if ( $search_all ) {
						$post_types = get_post_types( '', 'names' );
						$query->set( 'post_type', $post_types ); // Set the post types that we want WP to search.
					} else {
						$query->set( 'post_type', $post_types );
					}
				}
			}

			return $query;
		}

		public function limit_date_range_query( $where ) {
			global $wp_query;

			// Get post dates into array.
			$post_date = explode( '+', esc_attr( str_replace( ' ', '+', $wp_query->query['post_date'] ) ) );

			if ( count( $post_date ) > 1 && $post_date[0] !== $post_date[1] ) {
				$date_query = array();
				$from_date  = DateTime::createFromFormat( 'Y-m-d', $post_date[0] );
				$to_date    = DateTime::createFromFormat( 'Y-m-d', $post_date[1] );

				if ( ! empty( $post_date[0] ) ) {
					$date_query['after'] = $from_date->format( 'Y-m-d 00:00:00' );
				}

				if ( ! empty( $post_date[1] ) ) {
					$date_query['before'] = $to_date->format( 'Y-m-d 23:59:59' );
				}

				$where .= " AND post_date >='" . $date_query['after'] . "' AND post_date <='" . $date_query['before'] . "'";
			}

			return $where;
		}

		/**
		 * Remove the filter after it runs so that it doesn't affect any other
		 * queries that might be performed on the same page (eg. Recent Posts
		 * widget).
		 */
		public function remove_limit_date_range_query() {
			remove_filter( 'posts_where', 'limit_date_range_query' );
		}

		public function fix_blank_search( $query ) {
			// TODO - needs to be re-implemented.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( ( isset( $_GET['s'] ) ) && ( empty( $_GET['s'] ) ) && ( $query->is_main_query() ) ) {
				$query->is_search = true;
				$query->is_home   = false;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		public function filter_query_post_date( $query ) {
			global $wp_query;
			if ( ( $query->is_main_query() ) && ( ! is_admin() ) ) {
				if ( isset( $wp_query->query['post_date'] ) ) {
					// Get post dates into array
					$post_date = explode( '+', esc_attr( str_replace( ' ', '+', $wp_query->query['post_date'] ) ) );

					if ( ! empty( $post_date ) ) {
						// If there is more than 1 post date and the dates are not the same.
						if ( count( $post_date ) > 1 && $post_date[0] !== $post_date[1] ) {
							if ( ( ! empty( $post_date[0] ) ) && ( ! empty( $post_date[1] ) ) ) {
								// Attach hook to filter WHERE clause.
								add_filter( 'posts_where', array( $this, 'limit_date_range_query' ) );
								// Remove the filter after it is executed.
								add_action( 'posts_selection', array( $this, 'remove_limit_date_range_query' ) );
							}
						} else {
							// Else we are dealing with one date or both dates are the same (so need to find posts for a single day).
							if ( ! empty( $post_date[0] ) ) {
								$post_time = DateTime::createFromFormat( 'Y-m-d', $post_date[0] );
								$query->set( 'year', $post_time->format( 'Y' ) );
								$query->set( 'monthnum', $post_time->format( 'm' ) );
								$query->set( 'day', $post_time->format( 'd' ) );
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
		public function set_defaults() {
			global $wp_query;

			$categories = array();

			if ( isset( $wp_query->query['category_name'] ) ) {
				$category_params = ( preg_split( '/[,\+ ]/', esc_attr( $wp_query->query['category_name'] ) ) ); // explode with 2 delims

				foreach ( $category_params as $category_param ) {
					$category = get_category_by_slug( $category_param );
					if ( isset( $category->cat_ID ) ) {
						$categories[] = $category->cat_ID;
					}
				}
			}

			$this->defaults[ SF_FPRE . 'category' ] = $categories;

			// Grab search term for prefilling search input.
			if ( isset( $wp_query->query['s'] ) ) {
				$this->searchterm = trim( get_search_query() );
			}

			// Check to see if tag is set.
			$tags = array();

			if ( isset( $wp_query->query['tag'] ) ) {
				$tag_params = ( preg_split( '/[,\+ ]/', esc_attr( $wp_query->query['tag'] ) ) ); // Explode with 2 delims.

				foreach ( $tag_params as $tag_param ) {
					$tag = get_term_by( 'slug', $tag_param, 'post_tag' );
					if ( isset( $tag->term_id ) ) {
						$tags[] = $tag->term_id;
					}
				}
			}

			$this->defaults[ SF_FPRE . 'post_tag' ] = $tags;

			if ( isset( $wp_query->query ) && is_array( $wp_query->query ) ) {
				// Loop through all the query vars.
				foreach ( $wp_query->query as $key => $val ) {
					// Make sure the get is not a reserved get as they have already been handled above.
					if ( ! in_array( SF_FPRE . $key, $this->frmqreserved, true ) ) {
						// Now check it is a desired key.
						if ( in_array( $key, $this->taxonomylist, true ) ) {
							$taxslug    = $val;
							$tax_params = ( preg_split( '/[,\+ ]/', esc_attr( $taxslug ) ) );

							$taxs = array();
							foreach ( $tax_params as $tax_param ) {
								$tax = get_term_by( 'slug', $tax_param, $key );

								if ( isset( $tax->term_id ) ) {
									$taxs[] = $tax->term_id;
								}
							}
							$this->defaults[ SF_FPRE . $key ] = $taxs;
						}
					}
				}
			}
			$post_date = array( '', '' );
			if ( isset( $wp_query->query['post_date'] ) ) {
				$post_date = explode( '+', esc_attr( str_replace( ' ', '+', $wp_query->query['post_date'] ) ) );
				if ( count( $post_date ) === 1 ) {
					$post_date[1] = '';
				}
			}
			$this->defaults[ SF_FPRE . 'post_date' ] = $post_date;

			$post_types = array();
			if ( isset( $wp_query->query['post_types'] ) ) {
				$post_types = explode( ',', esc_attr( $wp_query->query['post_types'] ) );
			}
			$this->defaults[ SF_FPRE . 'post_types' ] = $post_types;

		}

		/*
		 * check to see if form has been submitted and handle vars
		*/
		public function check_posts() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST[ SF_FPRE . 'submitted' ] ) ) {
				if ( $_POST[ SF_FPRE . 'submitted' ] === '1' ) {
					// Set var to confirm the form was posted.
					$this->has_form_posted = true;
				}
			}

			$taxcount = 0;

			/* Categories */
			if ( ( isset( $_POST[ SF_FPRE . 'category' ] ) ) && ( $this->has_form_posted ) ) {

				$the_post_cat = wp_unslash( $_POST[ SF_FPRE . 'category' ] );

				// Make the cat an array consistency.
				if ( ! is_array( $the_post_cat ) ) {
					$post_cat[] = $the_post_cat;
				} else {
					$post_cat = $the_post_cat;
				}
				$catarr = array();

				foreach ( $post_cat as $cat ) {
					$cat    = sanitize_text_field( $cat );
					$catobj = get_category( $cat );

					if ( isset( $catobj->slug ) ) {
						$catarr[] = $catobj->slug;
					}
				}

				if ( count( $catarr ) > 0 ) {
					$operator = '+'; // Default behaviour.

					// check to see if an operator has been specified - only applies with fields that use multiple selects such as checkboxes or multi selects
					if ( isset( $_POST[ SF_FPRE . 'category_operator' ] ) ) {
						$post_cat_operator = wp_unslash( $_POST[ SF_FPRE . 'category_operator' ] );
						if ( strtolower( $post_cat_operator ) === 'and' ) {
							$operator = '+';
						} elseif ( strtolower( $post_cat_operator ) === 'or' ) {
							$operator = ',';
						} else {
							$operator = '+';
						}
					}

					$categories = implode( $operator, $catarr );

					if ( get_option( 'permalink_structure' ) && ( $taxcount == 0 ) ) {
						$category_base    = ( get_option( 'category_base' ) === '' ) ? 'category' : get_option( 'category_base' );
						$category_path    = $category_base . '/' . $categories . '/';
						$this->urlparams .= $category_path;
					} else {
						if ( ! $this->hasqmark ) {
							$this->urlparams .= '?';
							$this->hasqmark   = true;
						} else {
							$this->urlparams .= '&';
						}

						$this->urlparams .= 'category_name=' . $categories;
					}

					$taxcount++;
				}
			}

			/* Tags */
			if ( ( isset( $_POST[ SF_FPRE . 'post_tag' ] ) ) && ( $this->has_form_posted ) ) {
				$the_post_tag = wp_unslash( $_POST[ SF_FPRE . 'post_tag' ] );

				// Make the tag an array consistency.
				if ( ! is_array( $the_post_tag ) ) {
					$post_tag[] = $the_post_tag;
				} else {
					$post_tag = $the_post_tag;
				}

				$tagarr = array();

				foreach ( $post_tag as $tag ) {
					$tag    = sanitize_text_field( $tag );
					$tagobj = get_tag( $tag );

					if ( isset( $tagobj->slug ) ) {
						$tagarr[] = $tagobj->slug;
					}
				}

				if ( count( $tagarr ) > 0 ) {
					$operator = '+'; // Default behaviour.

					// Check to see if an operator has been specified - only applies with fields that use multiple selects such as checkboxes or multi selects
					if ( isset( $_POST[ SF_FPRE . 'post_tag_operator' ] ) ) {
						$post_tag_operator = wp_unslash( $_POST[ SF_FPRE . 'post_tag_operator' ] );
						if ( strtolower( $post_tag_operator ) === 'and' ) {
							$operator = '+';
						} elseif ( strtolower( $post_tag_operator ) === 'or' ) {
							$operator = ',';
						} else {
							$operator = '+';
						}
					}

					$tags = implode( $operator, $tagarr );

					if ( get_option( 'permalink_structure' ) && ( $taxcount === 0 ) ) {
						$tag_path         = 'tag/' . $tags . '/';
						$this->urlparams .= $tag_path;
					} else {
						if ( ! $this->hasqmark ) {
							$this->urlparams .= '?';
							$this->hasqmark   = true;
						} else {
							$this->urlparams .= '&';
						}
						$this->urlparams .= 'tag=' . $tags;
					}

					$taxcount++;
				}
			}

			// Now we have dealt with the all the special case fields - search, tags, categories, post_types, post_date.

			// Loop through the posted data - double check that it is the search form that has been posted, otherwise we
			// could be looping through the posts submitted from an entirely unrelated form.
			if ( $this->has_form_posted ) {
				foreach ( $_POST as $key => $val ) {

					if ( ! in_array( $key, $this->frmreserved, true ) ) {
						// if the key is not in the reserved array (ie, on a custom taxonomy - not tags, categories, search term, post type & post date)

						// Strip off all prefixes for custom fields - we just want to do a redirect - no processing.
						if ( strpos( $key, SF_FPRE ) === 0 ) {
							$key = substr( $key, strlen( SF_FPRE ) );
						}
						$prepped_val = wp_unslash( $val );
						$val_arr     = array();
						if ( ! is_array( $prepped_val ) ) {
							$val_arr[] = $prepped_val;
						} else {
							$val_arr = $prepped_val;
						}

						$taxarr = array();

						foreach ( $val_arr as $tax ) {
							$tax    = sanitize_text_field( $tax );
							$taxobj = get_term_by( 'id', $tax, $key );

							if ( isset( $taxobj->slug ) ) {
								$taxarr[] = $taxobj->slug;
							}
						}

						if ( count( $taxarr ) > 0 ) {
							$operator = '+'; // Default behaviour.

							// Check to see if an operator has been specified - only applies with fields that use multiple
							// selects such as checkboxes or multi selects.
							if ( isset( $_POST[ SF_FPRE . $key . '_operator' ] ) ) {
								if ( strtolower( $_POST[ SF_FPRE . $key . '_operator' ] ) === 'and' ) {
									$operator = '+';
								} elseif ( strtolower( $_POST[ SF_FPRE . $key . '_operator' ] ) === 'or' ) {
									$operator = ',';
								} else {
									$operator = '+';
								}
							}

							$taxs = implode( $operator, $taxarr );

							// *Due to some new wierd rewrite in WordPress, the first taxonomy which get rewritten
							// to /taxonomyname/taxonomyvalue only uses the first value of an array - so do it manually.
							if ( get_option( 'permalink_structure' ) && ( $taxcount === 0 ) ) {
								$key_taxonomy = get_taxonomy( $key );

								$tax_path = $key . '/' . $taxs . '/';
								if ( ( isset( $key_taxonomy->rewrite ) ) && ( isset( $key_taxonomy->rewrite['slug'] ) ) ) {
									$tax_path = $key_taxonomy->rewrite['slug'] . '/' . $taxs . '/';
								}

								$this->urlparams .= $tax_path;
							} else {
								if ( ! $this->hasqmark ) {
									$this->urlparams .= '?';
									$this->hasqmark   = true;
								} else {
									$this->urlparams .= '&';
								}
								$this->urlparams .= $key . '=' . $taxs;
							}
							$taxcount++;
						}
					}
				}
			}

			/* Search input */
			if ( ( isset( $_POST[ SF_FPRE . 'search' ] ) ) && ( $this->has_form_posted ) ) {
				$this->searchterm = trim( sanitize_text_field( wp_unslash( $_POST[ SF_FPRE . 'search' ] ) ) );
				if ( $this->searchterm !== '' ) {
					if ( ! $this->hasqmark ) {
						$this->urlparams .= '?';
						$this->hasqmark   = true;
					} else {
						$this->urlparams .= '&';
					}

					$this->urlparams     .= 's=' . rawurlencode( $this->searchterm );
					$this->hassearchquery = true;
				}
			}
			if ( ! $this->hassearchquery ) {
				if ( ( isset( $_POST[ SF_FPRE . 'add_search_param' ] ) ) && ( $this->has_form_posted ) ) {
					// This is only set when a search box is displayed - it tells S&F to append a blank search to the URL to indicate
					// a search has been submitted with no terms, however, still load the search template.
					if ( ! $this->hasqmark ) {
						$this->urlparams .= '?';
						$this->hasqmark   = true;
					} else {
						$this->urlparams .= '&';
					}
					$this->urlparams .= 's=';
				}
			}

			/* Post types */
			if ( ( isset( $_POST[ SF_FPRE . 'post_types' ] ) ) && ( $this->has_form_posted ) ) {
				$the_post_types = wp_unslash( $_POST[ SF_FPRE . 'post_types' ] );

				// Make the post an array consistency.
				if ( ! is_array( $the_post_types ) ) {
					$post_types_arr[] = $the_post_types;
				} else {
					$post_types_arr = $the_post_types;
				}

				$num_post_types = count( $post_types_arr );

				for ( $i = 0; $i < $num_post_types; $i++ ) {
					if ( $post_types_arr[ $i ] === '0' ) {
						$post_types_arr[ $i ] = 'all';
					} else {
						$post_types_arr[ $i ] = sanitize_text_field( $post_types_arr[ $i ] );
					}
				}

				if ( count( $post_types_arr ) > 0 ) {
					$operator   = ','; // Default behaviour.
					$post_types = implode( $operator, $post_types_arr );

					if ( ! $this->hasqmark ) {
						$this->urlparams .= '?';
						$this->hasqmark   = true;
					} else {
						$this->urlparams .= '&';
					}
					$this->urlparams .= 'post_types=' . $post_types;

				}
			}

			/* Post date */
			if ( ( isset( $_POST[ SF_FPRE . 'post_date' ] ) ) && ( $this->has_form_posted ) ) {
				$the_post_date = wp_unslash( $_POST[ SF_FPRE . 'post_date' ] );

				// Make the post an array consistency.
				if ( ! is_array( $the_post_date ) ) {
					$post_date_arr[] = $the_post_date;
				} else {
					$post_date_arr = $the_post_date;
				}

				$num_post_date = count( $post_date_arr );

				for ( $i = 0; $i < $num_post_date; $i++ ) {
					if ( $post_date_arr[ $i ] == '0' ) {
						$post_date_arr[ $i ] = 'all';
					} else {
						$post_date_arr[ $i ] = sanitize_text_field( $post_date_arr[ $i ] );
					}
				}

				if ( count( $post_date_arr ) > 0 ) {
					$post_date_count = count( $post_date_arr );

					if ( $post_date_count == 2 ) {
						// See if there are 2 elements in arr (second date range selector).
						if ( ( $post_date_arr[0] !== '' ) && ( $post_date_arr[1] === '' ) ) {
							$post_date = $post_date_arr[0];
						} elseif ( $post_date_arr[1] === '' ) {
							// If second date range is blank then remove the array element - this removes the addition of a '+' by implode below and only use first element.
							unset( $post_date_arr[1] );
						} elseif ( $post_date_arr[0] === '' ) {
							$post_date = '+' . $post_date_arr[1];
						} else {
							$post_date = implode( '+', array_filter( $post_date_arr ) );
						}
					} else {
						$post_date = $post_date_arr[0];
					}

					if ( isset( $post_date ) ) {
						if ( $post_date != '' ) {
							if ( ! $this->hasqmark ) {
								$this->urlparams .= '?';
								$this->hasqmark   = true;
							} else {
								$this->urlparams .= '&';
							}
							$this->urlparams .= 'post_date=' . $post_date;
						}
					}
				}
			}

			if ( $this->has_form_posted ) {
				// If the search has been posted, redirect to the newly formed url with all the right params.
				if ( $this->urlparams === '/' ) {
					// Check to see if url params are set, if not ("/") then add "?s=" to force load search results,
					// without this it would redirect to the homepage, which may be a custom page with no blog items/results.
					$this->urlparams .= '?s=';
				}

				if ( $this->urlparams == '/?s=' ) {
					// If a blank search was submitted - need to check for this string here in case `add_search_param`
					// has already added a "?s=" to the url.
					if ( isset( $_POST[ SF_FPRE . 'empty_search_url' ] ) ) {
						// Redirect to the provided empty search url.
						wp_redirect( esc_url( sanitize_text_field( wp_unslash( $_POST[ SF_FPRE . 'empty_search_url' ] ) ) ) );
						exit;
					}
				}
				wp_safe_redirect( home_url() . $this->urlparams );
				exit;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		public function get_search_filter_form( $submitlabel, $search_placeholder, $fields, $types, $labels, $hierarchical, $hide_empty, $show_count, $post_types, $order_by, $order_dir, $operators, $all_items_labels, $empty_search_url, $add_search_param, $class ) {
			$returnvar = '';

			$addclass = '';
			if ( $class != '' ) {
				$addclass = ' ' . $class;
			}

			$returnvar .= '
				<form action="" method="post" class="searchandfilter' . esc_attr( $addclass ) . '">
					<div>';

			if ( ! in_array( 'post_types', $fields, true ) ) {
				// Then the user has not added it to the fields list so the user does not want a post types
				// drop down... so add (if any) the post types to a hidden attribute.
				if ( ( $post_types !== '' ) && ( is_array( $post_types ) ) ) {
					foreach ( $post_types as $post_type ) {
						$returnvar .= '<input type="hidden" name="' . SF_FPRE . 'post_types[]" value="' . esc_attr( $post_type ) . '" />';
					}
				}
			}
			$returnvar .= '<ul>';
			$i          = 0;

			foreach ( $fields as $field ) {
				// Special cases - post_types & post_date, all others assumed regular wp taxonomy.
				if ( $field === 'search' ) {
					$returnvar .= '<li>';
					if ( $labels[ $i ] !== '' ) {
						$returnvar .= '<h4>' . wp_kses_post( $labels[ $i ] ) . '</h4>';
					}
					$clean_searchterm = esc_attr( $this->searchterm );
					$returnvar       .= '<input type="text" name="' . SF_FPRE . 'search" placeholder="' . esc_attr( $search_placeholder ) . '" value="' . esc_attr( $clean_searchterm ) . '">';
					$returnvar       .= '</li>';
				} elseif ( $field === 'post_types' ) {
					// Build field array.
					$returnvar .= $this->build_post_type_element( $types, $labels, $post_types, $field, $all_items_labels, $i );
				} elseif ( $field === 'post_date' ) {
					$returnvar .= $this->build_post_date_element( $labels, $i, $types, $field );
				} else {
					$returnvar .= $this->build_taxonomy_element( $types, $labels, $field, $hierarchical, $hide_empty, $show_count, $order_by, $order_dir, $operators, $all_items_labels, $i );
				}
				$i++;

			}

			$returnvar .= '<li>';

			if ( $add_search_param === 1 ) {
				$returnvar .= '<input type="hidden" name="' . SF_FPRE . 'add_search_param" value="1" />';
			}

			if ( $empty_search_url !== '' ) {
				$returnvar .= '<input type="hidden" name="' . SF_FPRE . 'empty_search_url" value="' . esc_url( html_entity_decode( $empty_search_url ) ) . '" />';
			}

			$returnvar .= '<input type="hidden" name="' . SF_FPRE . 'submitted" value="1"><input type="submit" value="' . esc_attr( $submitlabel ) . '"></li>';
			$returnvar .= '</ul>';
			$returnvar .= '</div></form>';

			return $returnvar;
		}

		public function build_post_date_element( $labels, $i, $types, $field ) {
			$returnvar = '';

			$taxonomychildren = array();

			$taxonomychildren = (object) $taxonomychildren;

			$returnvar .= '<li>';

			if ( $labels[ $i ] !== '' ) {
				$returnvar .= '<h4>' . wp_kses_post( $labels[ $i ] ) . '</h4>';
			}

			if ( $types[ $i ] === 'date' ) {
				$returnvar .= $this->generate_date( $taxonomychildren, $field, $this->tagid );
			}
			if ( $types[ $i ] === 'daterange' ) {
				$returnvar .= $this->generate_date( $taxonomychildren, $field, 0 );
				$returnvar .= '</li><li>';
				$returnvar .= $this->generate_date( $taxonomychildren, $field, 1 );
			}
			$returnvar .= '</li>';

			return $returnvar;
		}


		public function build_post_type_element( $types, $labels, $post_types, $field, $all_items_labels, $i ) {
			$returnvar        = '';
			$taxonomychildren = array();
			$post_type_count  = count( $post_types );

			// Then check the post types array.
			if ( is_array( $post_types ) ) {
				if ( ( $post_type_count === 1 ) && ( $post_types[0] === 'all' ) ) {
					$args     = array( 'public' => true );
					$output   = 'object';
					$operator = 'and';

					$post_types_objs = get_post_types( $args, $output, $operator );

					$post_types = array();

					foreach ( $post_types_objs  as $post_type ) {
						if ( $post_type->name !== 'attachment' ) {
							$tempobject             = array();
							$tempobject['term_id']  = $post_type->name;
							$tempobject['cat_name'] = $post_type->labels->name;

							$taxonomychildren[] = (object) $tempobject;

							$post_types[] = $post_type->name;

						}
					}
					$post_type_count = count( $post_types_objs );

				} else {
					foreach ( $post_types as $post_type ) {
						$post_type_data = get_post_type_object( $post_type );

						if ( $post_type_data ) {
							$tempobject             = array();
							$tempobject['term_id']  = $post_type;
							$tempobject['cat_name'] = $post_type_data->labels->name;

							$taxonomychildren[] = (object) $tempobject;
						}
					}
				}
			}
			$taxonomychildren = (object) $taxonomychildren;

			$returnvar .= '<li>';

			$post_type_labels                  = array();
			$post_type_labels['name']          = 'Post Types';
			$post_type_labels['singular_name'] = 'Post Type';
			$post_type_labels['search_items']  = 'Search Post Types';

			if ( $all_items_labels[ $i ] !== '' ) {
				$post_type_labels['all_items'] = $all_items_labels[ $i ];
			} else {
				$post_type_labels['all_items'] = 'All Post Types';
			}

			$post_type_labels = (object) $post_type_labels;

			if ( $labels[ $i ] != '' ) {
				$returnvar .= '<h4>' . wp_kses_post( $labels[ $i ] ) . '</h4>';
			}

			if ( $post_type_count > 0 ) {
				$defaultval = implode( ',', $post_types );
			} else {
				$defaultval = 'all';
			}

			if ( $types[ $i ] === 'select' ) {
				$returnvar .= $this->generate_select( $taxonomychildren, $field, $this->tagid, $post_type_labels, $defaultval );
			} elseif ( $types[ $i ] === 'checkbox' ) {
				$returnvar .= $this->generate_checkbox( $taxonomychildren, $field, $this->tagid );
			} elseif ( $types[ $i ] === 'radio' ) {
				$returnvar .= $this->generate_radio( $taxonomychildren, $field, $this->tagid, $post_type_labels, $defaultval );
			}
			$returnvar .= '</li>';

			return $returnvar;
		}

		// Gets all the data for the taxonomy then display as form element.
		public function build_taxonomy_element( $types, $labels, $taxonomy, $hierarchical, $hide_empty, $show_count, $order_by, $order_dir, $operators, $all_items_labels, $i ) {
			$returnvar = '';

			$taxonomydata = get_taxonomy( $taxonomy );

			if ( $taxonomydata ) {
				$returnvar .= '<li>';

				if ( $labels[ $i ] !== '' ) {
					$returnvar .= '<h4>' . wp_kses_post( $labels[ $i ] ) . '</h4>';
				}

				$args = array(
					'sf_name'            => SF_FPRE . $taxonomy,
					'taxonomy'           => $taxonomy,
					'hierarchical'       => false,
					'child_of'           => 0,
					'echo'               => false,
					'hide_if_empty'      => false,
					'hide_empty'         => true,
					'order'              => $order_dir[ $i ],
					'orderby'            => $order_by[ $i ],
					'show_option_none'   => '',
					'show_count'         => '0',
					'show_option_all'    => '',
					'show_option_all_sf' => '',
				);

				if ( isset( $hierarchical[ $i ] ) ) {
					if ( $hierarchical[ $i ] == 1 ) {
						$args['hierarchical'] = true;
					}
				}

				if ( isset( $hide_empty[ $i ] ) ) {
					if ( $hide_empty[ $i ] == 0 ) {
						$args['hide_empty'] = false;
					}
				}

				if ( isset( $show_count[ $i ] ) ) {
					if ( (int) $show_count[ $i ] === 1 ) {
						$args['show_count'] = true;
					}
				}

				if ( $all_items_labels[ $i ] !== '' ) {
					$args['show_option_all_sf'] = $all_items_labels[ $i ];
				}

				if ( $types[ $i ] === 'select' ) {
					$returnvar .= $this->generate_wp_dropdown( $args, $taxonomy, $this->tagid, $taxonomydata->labels );
				} elseif ( $types[ $i ] === 'checkbox' ) {
					$args['title_li'] = '';
					$args['defaults'] = '';
					if ( isset( $this->defaults[ $args['sf_name'] ] ) ) {
						$args['defaults'] = $this->defaults[ $args['sf_name'] ];
					}
					$returnvar .= $this->generate_wp_checkbox( $args, $taxonomy, $this->tagid, $taxonomydata->labels );
				} elseif ( $types[ $i ] === 'radio' ) {
					$args['title_li'] = '';
					$args['defaults'] = '';

					if ( isset( $this->defaults[ $args['sf_name'] ] ) ) {
						$args['defaults'] = $this->defaults[ $args['sf_name'] ];
					}
					$returnvar .= $this->generate_wp_radio( $args, $taxonomy, $this->tagid, $taxonomydata->labels );

				} elseif ( $types[ $i ] === 'multiselect' ) {
					$args['title_li'] = '';
					$args['defaults'] = '';

					if ( isset( $this->defaults[ $args['sf_name'] ] ) ) {
						$args['defaults'] = $this->defaults[ $args['sf_name'] ];
					}

					$returnvar .= $this->generate_wp_multiselect( $args, $taxonomy, $this->tagid, $taxonomydata->labels );
				}

				// Check to see if operator is set for this field.
				if ( isset( $operators[ $i ] ) ) {
					$operators[ $i ] = strtolower( $operators[ $i ] );
					if ( ( $operators[ $i ] === 'and' ) || ( $operators[ $i ] === 'or' ) ) {
						$returnvar .= '<input type="hidden" name="' . esc_attr( SF_FPRE . $taxonomy ) . '_operator" value="' . esc_attr( $operators[ $i ] ) . '" />';
					}
				}
				$returnvar .= '</li>';
			}
			return $returnvar;
		}


		/*
		 * Display various forms
		*/

		// Use wp array walker to enable hierarchical display.
		public function generate_wp_dropdown( $args, $name, $currentid = 0, $labels = null, $defaultval = '0' ) {
			$args['name'] = $args['sf_name'];

			$returnvar = '';

			if ( $args['show_option_all_sf'] == '' ) {
				$args['show_option_all'] = $labels->all_items !== '' ? $labels->all_items : 'All ' . $labels->name;
			} else {
				$args['show_option_all'] = wp_kses_post( $args['show_option_all_sf'] );
			}

			if ( isset( $this->defaults[ SF_FPRE . $name ] ) ) {
				$defaults = $this->defaults[ SF_FPRE . $name ];
				if ( is_array( $defaults ) ) {
					if ( count( $defaults ) === 1 ) {
						$args['selected'] = $defaults[0];
					}
				} else {
					$args['selected'] = $defaultval;
				}
			}

			$returnvar .= wp_dropdown_categories( $args );

			return $returnvar;
		}

		// use wp array walker to enable hierarchical display
		public function generate_wp_multiselect( $args, $name, $currentid = 0, $labels = null, $defaultval = '0' ) {
			$returnvar  = '<select multiple="multiple" name="' . esc_attr( $args['sf_name'] ) . '[]" class="postform">';
			$returnvar .= searchandfilter_walk_taxonomy( 'multiselect', $args );
			$returnvar .= '</select>';

			return $returnvar;
		}

		// use wp array walker to enable hierarchical display
		public function generate_wp_checkbox( $args, $name, $currentid = 0, $labels = null, $defaultval = '0' ) {
			$returnvar  = '<ul>';
			$returnvar .= searchandfilter_walk_taxonomy( 'checkbox', $args );
			$returnvar .= '</ul>';

			return $returnvar;
		}

		// use wp array walker to enable hierarchical display
		public function generate_wp_radio( $args, $name, $currentid = 0, $labels = null, $defaultval = '0' ) {
			if ( $args['show_option_all_sf'] === '' ) {
				$show_option_all = $labels->all_items !== '' ? $labels->all_items : 'All ' . $labels->name;
			} else {
				$show_option_all = $args['show_option_all_sf'];
			}

			$checked    = ( $defaultval === '0' ) ? " checked='checked'" : '';
			$returnvar  = '<ul>';
			$returnvar .= '<li>' . "<label><input type='radio' name='" . esc_attr( $args['sf_name'] ) . "[]' value='0'$checked /> " . wp_kses_post( $show_option_all ) . '</label></li>';
			$returnvar .= searchandfilter_walk_taxonomy( 'radio', $args );
			$returnvar .= '</ul>';

			return $returnvar;
		}

		// Generate generic form inputs for use elsewhere, such as post types and non taxonomy fields.
		public function generate_select( $dropdata, $name, $currentid = 0, $labels = null, $defaultval = '0' ) {
			$returnvar  = '';
			$returnvar .= '<select class="postform" name="' . esc_attr( SF_FPRE . $name ) . '">';
			if ( isset( $labels ) ) {
				if ( $labels->all_items !== '' ) {
					// Check to see if all items has been registered in field then use this label.
					$returnvar .= '<option class="level-0" value="' . esc_attr( $defaultval ) . '">' . esc_html( $labels->all_items ) . '</option>';
				} else {
					// Check to see if all items has been registered in field then use this label with prefix of "All".
					$returnvar .= '<option class="level-0" value="' . esc_attr( $defaultval ) . '">All ' . esc_html( $labels->name ) . '</option>';
				}
			}

			foreach ( $dropdata as $dropdown ) {
				$selected = '';

				if ( isset( $this->defaults[ SF_FPRE . $name ] ) ) {
					$defaults = $this->defaults[ SF_FPRE . $name ];

					$noselected = count( $defaults );

					if ( ( $noselected === 1 ) && ( is_array( $defaults ) ) ) {
						foreach ( $defaults as $defaultid ) {
							// TODO - check to see if we can use strict comparison here.
							if ( $defaultid == $dropdown->term_id ) {
								$selected = ' selected="selected"';
							}
						}
					}
				}
				$returnvar .= '<option class="level-0" value="' . esc_attr( $dropdown->term_id ) . '"' . $selected . '>' . esc_html( $dropdown->cat_name ) . '</option>';
			}
			$returnvar .= '</select>';
			return $returnvar;
		}

		public function generate_checkbox( $dropdata, $name, $currentid = 0, $labels = null, $defaultval = '' ) {
			$returnvar = '<ul>';

			foreach ( $dropdata as $dropdown ) {
				$checked = '';

				// Check a default has been set.
				if ( isset( $this->defaults[ SF_FPRE . $name ] ) ) {
					$defaults   = $this->defaults[ SF_FPRE . $name ];
					$noselected = count( $defaults );
					if ( ( $noselected > 0 ) && ( is_array( $defaults ) ) ) {
						foreach ( $defaults as $defaultid ) {
							// TODO - test changing this to strict equals.
							if ( $defaultid == $dropdown->term_id ) {
								$checked = ' checked="checked"';
							}
						}
					}
				}
				$returnvar .= '<li class="cat-item"><label><input class="postform cat-item" type="checkbox" name="' . esc_attr( SF_FPRE . $name ) . '[]" value="' . esc_attr( $dropdown->term_id ) . '"' . $checked . '> ' . esc_html( $dropdown->cat_name ) . '</label></li>';
			}
			$returnvar .= '</ul>';
			return $returnvar;
		}

		public function generate_radio( $dropdata, $name, $currentid = 0, $labels = null, $defaultval = '0' ) {
			$returnvar = '<ul>';

			if ( isset( $labels ) ) {
				$checked = '';
				if ( isset( $this->defaults[ SF_FPRE . $name ] ) ) {
					$defaults   = $this->defaults[ SF_FPRE . $name ];
					$noselected = count( $defaults );

					if ( $noselected === 0 ) {
						$checked = ' checked="checked"';
					} elseif ( $noselected === 1 ) {
						// TODO - test changing this to strict equals.
						if ( $this->defaults[ SF_FPRE . $name ][0] == $defaultval ) {
							$checked = ' checked="checked"';
						}
					}
				} else {
					$checked = ' checked="checked"';
				}

				if ( isset( $this->defaults[ SF_FPRE . $name ] ) ) {
					$defaults = $this->defaults[ SF_FPRE . $name ];
					if ( count( $defaults ) > 1 ) {// then we are dealing with multiple defaults - this means mutliple radios are selected, this is only possible with "ALL" so set as default.
						$checked = ' checked="checked"';
					}
				}

				$all_items_name = '';
				// Check to see if all items has been registered in field then use this label.
				if ( $labels->all_items !== '' ) {
					$all_items_name = $labels->all_items;
				} else { // check to see if all items has been registered in field then use this label with prefix of "All"
					$all_items_name = 'All ' . $labels->name;
				}
				$returnvar .= '<li class="cat-item"><label><input class="postform" type="radio" name="' . esc_attr( SF_FPRE . $name ) . '[]" value="' . esc_attr( $defaultval ) . '"' . $checked . '> ' . esc_html( $all_items_name ) . '</label></li>';
			}

			foreach ( $dropdata as $dropdown ) {
				$checked = '';

				// Check a default has been set.
				if ( isset( $this->defaults[ SF_FPRE . $name ] ) ) {
					$defaults   = $this->defaults[ SF_FPRE . $name ];
					$noselected = count( $defaults );

					if ( ( $noselected === 1 ) && ( is_array( $defaults ) ) ) {
						foreach ( $defaults as $defaultid ) {
							// TODO - test changing this to strict equals.
							if ( $defaultid == $dropdown->term_id ) {
								$checked = ' checked="checked"';
							}
						}
					}
				}
				$returnvar .= '<li class="cat-item"><label><input class="postform" type="radio" name="' . esc_attr( SF_FPRE . $name ) . '[]" value="' . esc_attr( $dropdown->term_id ) . '"' . $checked . '> ' . esc_html( $dropdown->cat_name ) . '</label></li>';

			}
			$returnvar .= '</ul>';
			return $returnvar;
		}

		public function generate_date( $dropdata, $name, $currentid = 0, $labels = null, $defaultval = '0' ) {
			$returnvar    = '';
			$current_date = '';

			// Check a default has been set - upto two possible vars for array.
			if ( isset( $this->defaults[ SF_FPRE . $name ] ) ) {
				$defaults = $this->defaults[ SF_FPRE . $name ];

				$noselected = count( $defaults );

				if ( ( $noselected > 0 ) && ( is_array( $defaults ) ) ) {
					$current_date = $defaults[ $currentid ];
				}
			}

			$returnvar .= '<input class="postform" type="date" name="' . esc_attr( SF_FPRE . $name ) . '[]" value="' . esc_attr( $current_date ) . '" />';

			return $returnvar;
		}
	}
}

function searchandfilter_walk_taxonomy( $type = 'checkbox', $args = array() ) {
	$args['walker'] = new SF_Taxonomy_Walker( $type, $args['sf_name'] );
	$output         = wp_list_categories( $args );
	if ( $output ) {
		return $output;
	}
}

if ( class_exists( 'SearchAndFilter' ) ) {
	global $SearchAndFilter;
	$SearchAndFilter = new SearchAndFilter();
}

// Classes.
require_once SEARCHANDFILTER_PLUGIN_DIR . '/of-list-table.php';
require_once SEARCHANDFILTER_PLUGIN_DIR . '/of-taxonomy-walker.php';

// Admin screens & plugin mods.
require_once SEARCHANDFILTER_PLUGIN_DIR . '/of-admin.php';
