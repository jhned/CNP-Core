<?php

/**
 * Link to files in the theme safely (will account for HTTP/HTTPS)
 * @param  string $path Path relative to the theme to a file/directory
 * @return string       Absolute path to theme resource
 */
function cnp_theme_url($path) {
	$path = ltrim(trim($path), '/');
	$base = trailingslashit(get_stylesheet_directory_uri());
	return $base.$path;
}

/**
 * Get path to files in the theme safely
 * @param  string $path Path relative to the theme to a file/directory
 * @return string       Absolute path to theme resource
 */
function cnp_theme_path($path) {
	$path = ltrim(trim($path), '/');
	$base = trailingslashit(get_stylesheet_directory());
	return $base.$path;
}

function cnp_get_subdomain() {
	$http = $_SERVER['HTTP_HOST'];
	$domain_array = explode(".", $http);
	$subdomain = array_shift($domain_array);
	return $subdomain;
}

//-----------------------------------------------------------------------------
// STRINGS
//-----------------------------------------------------------------------------

/**
 * Take an icon name and return inline SVG for the icon.
 * @param  string $icon_name The ID of the icon in the defs list of the SVG file.
 * @param  string $viewbox   Optional viewbox size.
 * @param  string $echo      To output, or not to output. Set to 0 if using URL-query.
 */
function cnp_isvg($args) {

	$defaults = array(
		'icon-name'	=> ''
	,	'viewbox' 	=> '0 0 32 32'
	,	'echo'		=> true
	,	'path'      => cnp_theme_url('/img/icons.svg')
	);

	$vars = wp_parse_args( $args, $defaults );
	$icon = '<svg role="img" title="'. $vars['icon-name'] .'" class="icon '. $vars['icon-name'] .'" viewBox="'. $vars['viewbox'] .'"><use xlink:href="'. $vars['path'] .'#'. $vars['icon-name'] .'"></use></svg>';
	if ( $vars['echo'] == true ) {
		echo $icon;
	} else {
		return $icon;
	}
}

/**
 * Take a timestamp and turn it in to human timing.
 * @param  timestamp $time      To output, or not to output. Set to 0 if using URL-query.
 */
function cnp_human_timing ($time, $cutoff=2) {

	$current_time = current_time('timestamp');
    $time = $current_time - $time; // to get the time since that moment

    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }

}

/**
 * Get a file's extension
 * @param 	string 	$file	the filename, as a string
 */
function cnp_getExt($file) {
	if (is_string($file)) {
		$arr = explode('.',$file);
		end($arr);
		$ext = current($arr);
		return $ext;
	}
	return false;
}


//-----------------------------------------------------------------------------
// MENUS
//-----------------------------------------------------------------------------

/**
 * Convert <li><a></a></li> pattern to <a></a> pattern, transferring all <li> attributes to the <a>
 * @param  string $s String to manipulate. Duh.
 * @return string    Manipulated string
 */
function cnp_lia2a($string) {

	if (!is_string($string))
		return 'ERROR lia2a(): Not a string (CNP Core, functions/theme.php)';

	$find = array('><a','</a>','<li','</li');
	$replace = array('','','<a','</a');
	$return = str_replace($find, $replace, $string);

	return $return;

}

/**
 * Display requested nav menu, but strip out <ul>s and <li>s
 * @param  string $menu_name Same as 'menu' in wp_nav_menu arguments. Allows simple retrieval of menu with just the one argument
 * @param  array  $args      Passed directly to wp_nav_menu
 */
function cnp_nav_menu($menu_name='', $args=array()) {

	$defaults = array(
		'menu'            => $menu_name
	,	'container'       => 'nav'
	,	'container_class' => sanitize_title($menu_name)
	,	'depth'           => 1
	,	'fallback_cb'     => false
	,	'items_wrap'      => PHP_EOL.'%3$s'
	,	'echo'            => false // always false or else it'd echo on line 143.
	,	'echo_menu'       => true  // sometimes true, sometimes not. it depends.
	);
	$vars = wp_parse_args($args, $defaults);

	$menu = wp_nav_menu($vars);
	$menu = cnp_lia2a($menu);
	$menu = trim($menu);
	$menu = str_replace("\r", "", $menu);
	$menu = str_replace("\n", "", $menu);

	if ( $vars['echo_menu'] === true ) {
		echo $menu.PHP_EOL;
	}

	else {
		return $menu;
	}
}


/**
 * Build contextually aware section navigation
 * @param  array  $options      Allows you to change the subnav header.
 * @param  array  $list_args    Modify the wp_list_pages or wp_list_categories arguments.
 */
function cnp_subnav($options=array(), $list_args=array()) {

	if (is_search() || is_404())
		return false;

	$defaults = array(
		'header'	=> '<h2 class="title">In this Section</h2>'
	);

	$vars = wp_parse_args( $options, $defaults );

	$list_defaults = array(
		'title_li'         => 0
	,	'show_option_none' => 0
	,   'echo'             => 0
	,	'manual_additions' => ''
	);

	$list_options = wp_parse_args( $list_args, $list_defaults );

	$before = '<nav class="section">'. $vars['header'] .'<ul>'.PHP_EOL;
	$after = '</ul></nav>'.PHP_EOL;
	$list = '';

	// Taxonomy archives
	// Includes categories, tags, custom taxonomies
	// Does not include date archives
	if (is_tax()) {

		$query_obj = get_queried_object();

		if (isset($options['list_options'][$query_obj->taxonomy]))
			$list_options = wp_parse_args($options['list_options'][$query_obj->taxonomy], $list_options);

		$list_options['taxonomy'] = $query_obj->taxonomy;
		$list = wp_list_categories($list_options);

	}

	// Post types
	else {
		global $post;

		// Only if we have a post
		if ($post) {

			// Hierarchical post types show sub post lists
			if (is_post_type_hierarchical($post->post_type)) {

				$list_options['post_type'] = $post->post_type;
				if ($post->post_type == 'page') {
					$ancestor = highest_ancestor();
					$list_options['child_of'] = $ancestor['id'];
				}
				$list = wp_list_pages($list_options);

			}

			// Non-hierarchical post types show specified taxonomy lists
			else {

				if (isset($options['list_options'][$post->post_type]))
					$list_options = wp_parse_args($options['list_options'][$post->post_type], $list_options);

				$list = wp_list_categories($list_options);

			}

		}
	}

	// Add Manual pages at the bottom of the subnav
	if ( !empty($list_options['manual_additions']) && $post->post_parent == 0 ) {

		$args = array(
			'include'   => $list_options['manual_additions']
		,	'post_type' => 'page'
		);
		$additional_posts = get_posts( $args );

		if (!empty($additional_posts))
			$list .= '<li class="spacer"></li>';

		foreach ($additional_posts as $key => $manual) {
			$list .= '<li class="manual"><a href="'. get_permalink( $manual->ID ) .'">'. $manual->post_title .'</a></li>';
		}
	}

	// If there's no text inside the tags, the list is empty
	if (!strip_tags($list))
		return false;

	return $before.$list.$after;

}

//-----------------------------------------------------------------------------
// DESCRIPTION / EXCERPT FUNCTIONS
//-----------------------------------------------------------------------------

/**
 * Returns an appropriate description for the current page. Can be modified
 * using the cnp_description filter.
 *
 * @access public
 */
function cnp_description() {
	return apply_filters('cnp_description', '');
}

function cnp_excerpt($post, $max_words = false, $truncate = false) {

	$max_words = $max_words ? $max_words : apply_filters('excerpt_length', 35);

	$excerpt = $post->post_excerpt;
	if (!$excerpt) $excerpt = strip_tags(strip_shortcodes($post->post_content));

	if (!$truncate) return $excerpt;

	$words = explode(' ', $excerpt);
	if (count($words) > $max_words) {
		array_splice($words, $max_words);
		$excerpt = implode(' ', $words).apply_filters('excerpt_more', '&hellip');
	}

	return $excerpt;
}

//-----------------------------------------------------------------------------
// SCHEMA.ORG HELPER FUNCTIONS
//-----------------------------------------------------------------------------

/**
 * Defines a schema item type
 * @access public
 * @param  string $type Upper CamelCase type name
 */
function cnp_schema_type($type) {
	printf(
		'itemscope itemtype="http://schema.org/%s"',
		trim($type)
	);
}

/**
 * Defines a schema item property
 * @access public
 * @param  string $prop Upper CamelCase property name
 */
function cnp_schema_prop($prop) {
	printf(
		'itemprop="%s"',
		trim($prop)
	);
}

/**
 * Defines a schema property that has no corresponding element on the page
 * as a meta element
 *
 * @access public
 * @param  string $prop    Upper CamelCase property name
 * @param  string $content Value of the property
 */
function cnp_schema_meta($prop, $content) {
	printf(
		'<meta itemprop="%s" content="%s" />',
		trim($prop),
		trim($content)
	);
}

//-----------------------------------------------------------------------------
// HIGHEST ANCESTOR
//-----------------------------------------------------------------------------

function get_highest_ancestor($args=0) {

	$d = array(
		'id'     => 0
	,	'title'  => ''
	,	'name'   => ''
	,	'object' => false
	);
	$posttype = get_post_type();

	// Default homepage
	if ( is_front_page() && is_home() ) {

		$ancestor = array(
			'id'     => 0
		,	'title'  => 'home'
		,	'name'   => 'Home'
		);

	// Static front page
	} elseif ( is_front_page() ) {

		$front_page = get_post(get_option('page_on_front'));
		$ancestor = array(
			'id'     => $front_page->ID
		,	'title'  => $front_page->post_title
		,	'name'   => $front_page->post_name
		,	'object' => $front_page
		);

	// Static posts page
	} elseif ( is_home() ) {

		$home = get_post(get_option('page_for_posts'));
		$ancestor = array(
			'id'     => $home->ID
		,	'title'  => $home->post_title
		,	'name'   => $home->post_name
		,	'object' => $home
		);

	} elseif (is_search()) {

		$ancestor = array(
			'title' => 'Search Results' // Want to add number of search results
		,	'name'  => 'search'
		);

	/*
	} elseif (is_404()) {

		$ancestor = array(
			'title' => 'Page Not Found'
		,	'name'  => 'error404'
		);

	} elseif (is_year()) {

	} elseif (is_month()) {

	} elseif (is_day()) {

	} elseif (is_author()) {

	} elseif (is_post_type_archive()) {

	} elseif (is_tax() || is_category() || is_tag()) {

	} elseif (is_attachment()) {

	} elseif (is_singular()) {
	*/

	} elseif ( is_page() ) {

		global $post;
		$page = $post;

		while ( $page->post_parent > 0 )
			$page = get_post($page->post_parent);

		$ancestor = array(
			'id'     => $page->ID
		,	'title'  => $page->post_title
		,	'name'   => $page->post_name
		,	'object' => $page
		);

	} elseif ( is_singular() ) {

		$pt_obj = get_post_type_object($posttype);
		$ancestor = array(
			'id'     => 0
		,	'title'  => $pt_obj->label
		,	'name'   => $posttype
		,	'object' => $pt_obj
		);

	} else {

		$ancestor = array(
			'title' => wp_title('', false)
		);

	}

	/*
	if ( is_tax() ) {

		global $wp_query;
		$tax = $wp_query->get_queried_object();
		$ancestor = array(
			'id'   => $tax->term_id
		,	'slug' => $tax->slug
		,	'name' => $tax->name
		);

	} elseif ( is_archive() || is_single() ) {

		if ( $posttype && $posttype!='post' && $posttype!='page' ) {

			global $wp_query;
			$archive = $wp_query->get_queried_object();
			if (is_singular()) {$archive = get_post_type_object($archive->post_type);}
			$ancestor = array(
				'slug'      => $archive->rewrite['slug']
			,	'name'      => $archive->labels->name
			,	'query_var' => $archive->query_var
			);

		} else {

			global $post;

			if ($post) {

				$archive = get_the_category($post->ID);
				$archive = $archive[0];

			} else {

				global $wp_query;
				$archive = $wp_query->get_queried_object();

			}

			while ($archive->parent != 0) {

				$archive = get_category($archive->parent);

			}

			$ancestor = array(
				'id'   => $archive->cat_ID
			,	'slug' => $archive->slug
			,	'name' => $archive->name
			,	'count' => $archive->count
			);

		}

	} elseif ( $posttype && $posttype!='post' && $posttype!='page' ) {

		$posttype = get_post_type_object($posttype);
		$ancestor = array(
			'slug' => sanitize_html_class(strtolower($posttype->labels->name))
		,	'name' => $posttype->labels->name
		);

	} else {

		$ancestor = array(
			'id'   => 0
		,	'slug' => '404'
		,	'name' => 'Page Not Found'
		);

	}
	*/

	$ancestor = wp_parse_args($ancestor, $d);
	return $ancestor;

}

function highest_ancestor($echo=0) {

	$ancestor = get_highest_ancestor();

	if ($echo)
		echo $ancestor[$echo];
	else
		echo $ancestor['title'];

}

function is_highest_ancestor() {

	global $post;

	if ( is_page() && $post->post_parent == 0)
		return true;

	if ( is_post_type_archive() )
		return true;

	return false;

}

//-----------------------------------------------------------------------------
// PAGINATION
//-----------------------------------------------------------------------------

function pagination($args=0) {

	global $wp_query;
	$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;
	$defaults = array(
		'base'      => @add_query_arg('paged','%#%')
	,	'format'    => ''
	,	'total'     => $wp_query->max_num_pages
	,	'current'   => $current
	,	'end_size'  => 1
	,	'mid_size'  => 2
	,	'prev_text' => '&larr; Back'
	,	'next_text' => 'More &rarr;'
	,	'type'      => 'plain'
	);
	$pagination = wp_parse_args($args, $defaults);

	$links = paginate_links($pagination);
	if ( $pagination['type'] == 'array' ) {
		return $links;
	}
	else {
		echo $links
			? '<p class="pagination">'.$links.'</p>'
			: '';
	}
}


//-----------------------------------------------------------------------------
// FEATURED IMAGE
//--

/**
* 	Added to make getting the featured image easier
*
*	@param 	int 	$post_or_image_id 	The page/post to get the featured image for
*	@since 	1.3.3
*/
function cnp_featured_image($post_or_image_id) {
	$return = FALSE;
	if ( wp_attachment_is_image($post_or_image_id) ) {
		$id = $post_or_image_id;
	}
	else {
		$id = get_post_thumbnail_id( $post_or_image_id );
	}
	if ( $id && $id !== '' ) {

		$attachment = get_post( $id );
		$meta = wp_get_attachment_metadata($id);

		$sizes = array();

		if ( is_array($meta) && count($meta) > 0 ) {
			foreach( $meta['sizes'] as $key=>$size ) {
				$sizes[$key] = array(
					'file'		=> $size['file']
				,	'url'		=> wp_get_attachment_image_src($id,$key)[0]
				,	'width' 	=> $size['width']
				,	'height' 	=> $size['height']
				,	'mime-type' => $size['mime-type']
				);
			}
		}

		$sizes['full'] = array(
			'file'   => $meta['file']
		,	'url'    => $attachment->guid
		,	'width'  => $meta['width']
		,	'height' => $meta['height']
		);

		if ( is_object($attachment) && count($attachment) > 0 ) {

			$return = array(
				'ID' 			=> $id
			,	'alt' 			=> get_post_meta($id,'_wp_attachment_image_alt',true)
			,	'title' 		=> $attachment->post_title
			,	'caption' 		=> $attachment->post_excerpt
			,	'description' 	=> $attachment->post_content
			,	'file'			=> $meta['file']
			,	'url'			=> $attachment->guid
			,	'width'			=> $meta['width']
			,	'height'		=> $meta['height']
			,	'sizes'			=> $sizes
			,	'aperture'		=> $meta['image_meta']['aperture']
			,	'credit' 		=> $meta['image_meta']['credit']
			,	'camera'		=> $meta['image_meta']['camera']
			,	'created'		=> $meta['image_meta']['created_timestamp']
			,	'copyright'		=> $meta['image_meta']['copyright']
			,	'focal_length'	=> $meta['image_meta']['focal_length']
			,	'iso'			=> $meta['image_meta']['iso']
			,	'shutter_speed'	=> $meta['image_meta']['shutter_speed']
			,	'orientation'	=> $meta['image_meta']['orientation']
			);
		}
	}
	return $return;
}