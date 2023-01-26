<?php

class SF_Taxonomy_Walker extends Walker_Category {

	private $type                 = '';
	private $defaults             = array();
	private $multidepth           = 0; // manually calculate depth on multiselects
	private $multilastid          = 0; // manually calculate depth on multiselects
	private $multilastdepthchange = 0; // manually calculate depth on multiselects

	public function __construct( $type = 'checkbox', $defaults = array() ) {
		$this->type     = $type;
		$this->defaults = $defaults;
	}

	public function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args = array(), &$output = '' ) {
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}


	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		if ( $this->type === 'list' ) {
			extract( $args );
			$cat_name = esc_attr( $sf_name );
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );
			$link     = '<a href="' . esc_url( get_term_link( $category ) ) . '" ';
			// TODO - see if we can change for strict comparison.
			if ( $use_desc_for_title == 0 || empty( $category->description ) ) {
				$link .= 'title="' . esc_attr( sprintf( __( 'View all posts filed under %s' ), $cat_name ) ) . '"';
			} else {
				$link .= 'title="' . esc_attr( wp_strip_all_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
			}
			$link .= '>';
			$link .= $cat_name . '</a>';

			if ( ! empty( $feed_image ) || ! empty( $feed ) ) {
				$link .= ' ';

				if ( empty( $feed_image ) ) {
					$link .= '(';
				}

				$link .= '<a href="' . esc_url( get_term_feed_link( $category->term_id, $category->taxonomy, $feed_type ) ) . '"';

				if ( empty( $feed ) ) {
					$alt = ' alt="' . esc_attr( sprintf( __( 'Feed for all posts filed under %s' ), $cat_name ) ) . '"';
				} else {
					$title = ' title="' . esc_attr( $feed ) . '"';
					$alt   = ' alt="' . esc_attr( $feed ) . '"';
					$name  = $feed;
					$link .= $title;
				}

				$link .= '>';

				if ( empty( $feed_image ) ) {
					$link .= $name;
				} else {
					$link .= "<img src='$feed_image'$alt$title" . ' />';
				}

				$link .= '</a>';

				if ( empty( $feed_image ) ) {
					$link .= ')';
				}
			}

			if ( ! empty( $show_count ) ) {
				$link .= ' (' . intval( $category->count ) . ')';
			}

			if ( 'list' === $args['style'] ) {
				$output .= "\t<li";
				$class   = 'cat-item cat-item-' . $category->term_id;
				if ( ! empty( $current_category ) ) {
					$_current_category = get_term( $current_category, $category->taxonomy );
					if ( $category->term_id == $current_category ) {
						$class .= ' current-cat';
					} elseif ( $category->term_id == $_current_category->parent ) {
						$class .= ' current-cat-parent';
					}
				}
				$output .= ' class="' . esc_attr( $class ) . '"';
				$output .= ">$link\n";
			} else {
				$output .= "\t$link<br />\n";
			}
		} elseif ( ( $this->type === 'checkbox' ) || ( $this->type === 'radio' ) ) {
			extract( $args );

			$cat_name = esc_attr( $category->name );
			$cat_id   = esc_attr( $category->term_id );
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );

			// Check a default has been set.
			$checked = '';

			if ( $defaults ) {
				if ( is_array( $defaults ) ) {
					$noselected = count( $defaults );

					if ( $noselected > 0 ) {
						foreach ( $defaults as $defaultid ) {
							if ( $defaultid == $cat_id ) {
								$checked = ' checked="checked"';
							}
						}
					}
				}
			}

			$link = "<label><input type='" . esc_attr( $this->type ) . "' name='" . esc_attr( $sf_name ) . "[]' value='" . esc_attr( $cat_id ) . "'" . $checked . ' /> ' . $cat_name;
			if ( ! empty( $show_count ) ) {
				$link .= ' (' . intval( $category->count ) . ')';
			}

			$link .= '</label>';

			if ( 'list' === $args['style'] ) {
				$output .= "\t<li";
				$class   = 'cat-item cat-item-' . $category->term_id;
				if ( ! empty( $current_category ) ) {
					$_current_category = get_term( $current_category, $category->taxonomy );
					if ( $category->term_id == $current_category ) {
						$class .= ' current-cat';
					} elseif ( $category->term_id == $_current_category->parent ) {
						$class .= ' current-cat-parent';
					}
				}
				$output .= ' class="' . esc_attr( $class ) . '"';
				$output .= ">$link\n";
			} else {
				$output .= "\t$link<br />\n";
			}
		} elseif ( $this->type === 'multiselect' ) {
			extract( $args );

			$cat_name = esc_attr( $category->name );
			$cat_id   = esc_attr( $category->term_id );
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );

			// Check a default has been set.
			$checked = '';
			if ( $defaults ) {
				if ( is_array( $defaults ) ) {
					$noselected = count( $defaults );

					if ( $noselected > 0 ) {
						foreach ( $defaults as $defaultid ) {
							// TODO - test changing this to a strict equality check.
							if ( $defaultid == $cat_id ) {
								$checked = ' selected="selected"';
							}
						}
					}
				}
			}

			$catogory_parent = absint( $category->parent );

			// Custom  depth calculations.
			if ( $catogory_parent === 0 ) {
				// Then this has no parent so reset depth.
				$this->multidepth = 0;
			} elseif ( $catogory_parent === absint( $this->multilastid ) ) {
				$this->multidepth++;
				$this->multilastdepthchange = $this->multilastid;
			} elseif ( $catogory_parent === absint( $this->multilastdepthchange ) ) {
				// Then this is also a child with the same parent so don't change depth.

			} else {
				// Then this has a different parent so must be lower depth.
				if ( $this->multidepth > 0 ) {
					$this->multidepth--;
				}
			}

			$pad  = str_repeat( '&nbsp;', $this->multidepth * 3 );
			$link = '<option class="level-' . esc_attr( $this->multidepth ) . "\" value='" . $cat_id . "'$checked >" . esc_html( $pad . $cat_name );

			if ( ! empty( $show_count ) ) {
				$link .= '&nbsp;&nbsp;(' . intval( $category->count ) . ')';
			}

			$link   .= '</option>';
			$output .= "\t$link\n";

			$this->multilastid = $cat_id;
		}
	}

	public function end_el( &$output, $page, $depth = 0, $args = array() ) {
		if ( $this->type === 'list' ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}
			$output .= "</li>\n";
		} elseif ( ( $this->type === 'checkbox' ) || ( $this->type === 'radio' ) ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}
			$output .= "</li>\n";
		} elseif ( $this->type === 'multiselect' ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}
			$output .= "</option>\n";
		}
	}

	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		if ( $this->type === 'list' ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}

			$indent  = str_repeat( "\t", $depth );
			$output .= "$indent<ul class='children'>\n";
		} elseif ( ( $this->type === 'checkbox' ) || ( $this->type === 'radio' ) ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}

			$indent  = str_repeat( "\t", $depth );
			$output .= "$indent<ul class='children'>\n";
		}
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		if ( $this->type === 'list' ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}
			$indent  = str_repeat( "\t", $depth );
			$output .= "$indent</ul>\n";
		} elseif ( ( $this->type === 'checkbox' ) || ( $this->type === 'radio' ) ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}

			$indent  = str_repeat( "\t", $depth );
			$output .= "$indent</ul>\n";
		}
	}
}


