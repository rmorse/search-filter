<?php

class Taxonomy_Walker extends Walker_Category {

	
	private $type = '';
	private $defaults = array();
	private $multidepth = 0; //manually calculate depth on multiselects
	private $multilastid = 0; //manually calculate depth on multiselects
	private $multilastdepthchange = 0; //manually calculate depth on multiselects

	function __construct($type = 'checkbox', $defaults = array())  {
		// fetch the list of term ids for the given post
		//$this->term_ids = wp_get_post_terms( $post_id, $taxonomy, 'fields=ids' );
		//var_dump($this->term_ids);
		
		$this->type = $type;
		$this->defaults = $defaults;
	}

	function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {
		/*$display = false;
		
		$id = $element->term_id;

		$display = true;
		if ( isset( $children_elements[ $id ] ) ) {
			// the current term has children
			foreach ( $children_elements[ $id ] as $child ) {
				if ( in_array( $child->term_id, $this->term_ids ) ) {
					// one of the term's children is in the list
					$display = true;
					// can stop searching now
					break;
				}
			}
		}

		if ( $display )*/
			parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}
	
	
	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 )
	{
	
		if($this->type=="list")
		{
			extract($args);

			$cat_name = esc_attr( $category->name );
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );
			$link = '<a href="' . esc_url( get_term_link($category) ) . '" ';
			if ( $use_desc_for_title == 0 || empty($category->description) )
				$link .= 'title="' . esc_attr( sprintf(__( 'View all posts filed under %s' ), $cat_name) ) . '"';
			else
				$link .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
			$link .= '>';
			$link .= $cat_name . '</a>';

			if ( !empty($feed_image) || !empty($feed) ) {
				$link .= ' ';

				if ( empty($feed_image) )
					$link .= '(';

				$link .= '<a href="' . esc_url( get_term_feed_link( $category->term_id, $category->taxonomy, $feed_type ) ) . '"';

				if ( empty($feed) ) {
					$alt = ' alt="' . sprintf(__( 'Feed for all posts filed under %s' ), $cat_name ) . '"';
				} else {
					$title = ' title="' . $feed . '"';
					$alt = ' alt="' . $feed . '"';
					$name = $feed;
					$link .= $title;
				}

				$link .= '>';

				if ( empty($feed_image) )
					$link .= $name;
				else
					$link .= "<img src='$feed_image'$alt$title" . ' />';

				$link .= '</a>';

				if ( empty($feed_image) )
					$link .= ')';
			}

			if ( !empty($show_count) )
				$link .= ' (' . intval($category->count) . ')';

			if ( 'list' == $args['style'] ) {
				$output .= "\t<li";
				$class = 'cat-item cat-item-' . $category->term_id;
				if ( !empty($current_category) ) {
					$_current_category = get_term( $current_category, $category->taxonomy );
					if ( $category->term_id == $current_category )
						$class .=  ' current-cat';
					elseif ( $category->term_id == $_current_category->parent )
						$class .=  ' current-cat-parent';
				}
				$output .=  ' class="' . $class . '"';
				$output .= ">$link\n";
			} else {
				$output .= "\t$link<br />\n";
			}
		}
		else if(($this->type=="checkbox")||($this->type=="radio"))
		{
			extract($args);

			$cat_name = esc_attr( $category->name );
			$cat_id = esc_attr( $category->term_id );
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );
			
			//check a default has been set
			$checked = "";
			if($defaults)
			{
				$noselected = count($this->defaults);

				if(($noselected>0)&&(is_array($defaults)))
				{
					foreach($defaults as $defaultid)
					{
						if($defaultid==$cat_id)
						{
							$checked = ' checked="checked"';
						}
					}
				}
			}
			
			$link = "<label><input type='".$this->type."' name='".$name."[]' value='".$cat_id."'".$checked." /> ".$cat_name;

			
			if ( !empty($show_count) )
				$link .= ' (' . intval($category->count) . ')';
				
			
			$link .= "</label>";
			
			if ( 'list' == $args['style'] ) {
				$output .= "\t<li";
				$class = 'cat-item cat-item-' . $category->term_id;
				if ( !empty($current_category) ) {
					$_current_category = get_term( $current_category, $category->taxonomy );
					if ( $category->term_id == $current_category )
						$class .=  ' current-cat';
					elseif ( $category->term_id == $_current_category->parent )
						$class .=  ' current-cat-parent';
				}
				$output .=  ' class="' . $class . '"';
				$output .= ">$link\n";
			} else {
				$output .= "\t$link<br />\n";
			}
		}
		else if($this->type=="multiselect")
		{
			extract($args);

			$cat_name = esc_attr( $category->name );
			$cat_id = esc_attr( $category->term_id );
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );
			
			//check a default has been set
			$checked = "";
			if($defaults)
			{
				$noselected = count($this->defaults);

				if(($noselected>0)&&(is_array($defaults)))
				{
					foreach($defaults as $defaultid)
					{
						if($defaultid==$cat_id)
						{
							$checked = ' selected="selected"';
						}
					}
				}
			}
			
			
			/* Custom  depth calculations! :/ */
			if($category->parent == 0)
			{//then this has no parent so reset depth
				$this->multidepth = 0;
			}
			else if($category->parent == $this->multilastid)
			{
				$this->multidepth++;
				$this->multilastdepthchange = $this->multilastid;
			}
			else if($category->parent == $this->multilastdepthchange)
			{//then this is also a child with the same parent so don't change depth
				
			}
			else
			{//then this has a different parent so must be lower depth
				if($this->multidepth>0)
				{
					$this->multidepth--;
				}
			}
			
			$pad = str_repeat('&nbsp;', $this->multidepth * 3);
			$link = "<option class=\"level-".$this->multidepth."\" value='".$cat_id."'$checked />".$pad.$cat_name;

			if ( !empty($show_count) )
				$link .= '&nbsp;&nbsp;(' . intval($category->count) . ')';
				
			
			$link .= "</option>";
			$output .= "\t$link\n";
			
			
			$this->multilastid = $cat_id;
			
			
			/*
			$pad = str_repeat('&nbsp;', $depth * 3);

			$output .= "\t<option class=\"level-$depth\" value=\"".$category->term_id."\"";
			$cat_name = apply_filters('list_cats', $category->name, $category);
			if ( $category->term_id == $args['selected'] )
				$output .= ' selected="selected"';
			$output .= '>';
			$output .= $pad.$cat_name;
			if ( $args['show_count'] )
				$output .= '&nbsp;&nbsp;('. $category->count .')';
			$output .= "</option>\n";*/
		}
		
		
	}
	
	function end_el( &$output, $page, $depth = 0, $args = array() )
	{
		if($this->type=="list")
		{
			if ( 'list' != $args['style'] )
				return;

			$output .= "</li>\n";
		}
		else if(($this->type=="checkbox")||($this->type=="radio"))
		{
			if ( 'list' != $args['style'] )
				return;

			$output .= "</li>\n";
		}
		else if($this->type=="multiselect")
		{
			if ( 'list' != $args['style'] )
				return;

			$output .= "</option>\n";
		}
	}
	
	function start_lvl( &$output, $depth = 0, $args = array() )
	{
	
		if($this->type=="list")
		{
			if ( 'list' != $args['style'] )
				return;

			$indent = str_repeat("\t", $depth);
			$output .= "$indent<ul class='children'>\n";
		}
		else if(($this->type=="checkbox")||($this->type=="radio"))
		{
			if ( 'list' != $args['style'] )
				return;

			$indent = str_repeat("\t", $depth);
			$output .= "$indent<ul class='children'>\n";
		}
		else if($this->type=="multiselect")
		{
			/*if ( 'list' != $args['style'] )
				return;

			$indent = str_repeat("\t", $depth);
			$output .= "$indent<ul class='children'>\n";*/
		}
	}
	
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		if($this->type=="list")
		{
			if ( 'list' != $args['style'] )
				return;

			$indent = str_repeat("\t", $depth);
			$output .= "$indent</ul>\n";
		}
		else if(($this->type=="checkbox")||($this->type=="radio"))
		{
			if ( 'list' != $args['style'] )
				return;

			$indent = str_repeat("\t", $depth);
			$output .= "$indent</ul>\n";
		}
		else if($this->type=="multiselect")
		{
			
		}
	}
}

?>