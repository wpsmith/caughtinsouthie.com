<?php
/**
 * Helper Functions
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

// Duplicate 'the_content' filters
global $wp_embed;
add_filter( 'ea_the_content', array( $wp_embed, 'run_shortcode' ), 8 );
add_filter( 'ea_the_content', array( $wp_embed, 'autoembed'     ), 8 );
add_filter( 'ea_the_content', 'wptexturize'        );
add_filter( 'ea_the_content', 'convert_chars'      );
add_filter( 'ea_the_content', 'wpautop'            );
add_filter( 'ea_the_content', 'shortcode_unautop'  );
add_filter( 'ea_the_content', 'do_shortcode'       );

/**
 * Get the first term attached to post
 *
 * @param string $taxonomy
 * @param string/int $field, pass false to return object
 * @param int $post_id
 * @return string/object
 */
function ea_first_term( $taxonomy = 'category', $field = false, $post_id = false ) {

	$post_id = $post_id ? $post_id : get_the_ID();
	$term = false;

	// Use WP SEO Primary Term
	// from https://github.com/Yoast/wordpress-seo/issues/4038
	if( class_exists( 'WPSEO_Primary_Term' ) ) {
		$term = get_term( ( new WPSEO_Primary_Term( $taxonomy,  $post_id ) )->get_primary_term(), $taxonomy );
	}

	// Fallback on term with highest post count
	if( ! $term || is_wp_error( $term ) ) {

		$terms = get_the_terms( $post_id, $taxonomy );

		if( empty( $terms ) || is_wp_error( $terms ) )
			return false;

		// If there's only one term, use that
		if( 1 == count( $terms ) ) {
			$term = array_shift( $terms );

		// If there's more than one...
		} else {

			// Sort by term order if available
			// @uses WP Term Order plugin
			if( isset( $terms[0]->order ) ) {
				$list = array();
				foreach( $terms as $term )
					$list[$term->order] = $term;
				ksort( $list, SORT_NUMERIC );

			// Or sort by post count
			} else {
				$list = array();
				foreach( $terms as $term )
					$list[$term->count] = $term;
				ksort( $list, SORT_NUMERIC );
				$list = array_reverse( $list );
			}

			$term = array_shift( $list );
		}
	}

	// Output
	if( $field && isset( $term->$field ) )
		return $term->$field;

	else
		return $term;
}

/**
 * Conditional CSS Classes
 *
 * @param string $base_classes, classes always applied
 * @param string $optional_class, additional class applied if $conditional is true
 * @param bool $conditional, whether to add $optional_class or not
 * @return string $classes
 */
function ea_class( $base_classes, $optional_class, $conditional ) {
	return $conditional ? $base_classes . ' ' . $optional_class : $base_classes;
}

/**
 * Column Classes
 *
 * Adds "-first" classes when appropriate for clearing float
 * @see /assets/scss/partials/layout.scss
 *
 * @param array $classes, bootstrap-style classes, ex: array( 'col-lg-4', 'col-md-6' )
 * @param int $current, current post in loop
 * @param bool $join, whether to join classes (return string) or not (return array)
 * @return string/array $classes
 */
function ea_column_class( $classes = array(), $current = false, $join = true ) {

	if( false === $current )
		return $classes;

	$columns = array( 2, 3, 4, 6 );
	foreach( $columns as $column ) {
		if( 0 == $current % $column ) {

			$col = 12 / $column;
			foreach( $classes as $class ) {
				if( false != strstr( $class, (string) $col ) && false == strstr( $class, '12' ) ) {
					$classes[] = str_replace( $col, 'first', $class );
				}
			}
		}
	}

	if( $join ) {
		return join( ' ', $classes );
	} else {
		return $classes;
	}
}


/**
 * Get Icon
 *
 */
function ea_icon( $slug = '' ) {
	$icon_path = get_stylesheet_directory() . '/assets/icons/' . $slug . '.svg';
	if( file_exists( $icon_path ) )
		return file_get_contents( $icon_path );
}

/**
 * Site Copyright
 *
 */
function ea_site_copyright() {
	echo '<p class="copyright">&copy; ' . get_bloginfo( 'name' ) . ' Copyright ' . date( 'Y' ) . '. All Rights Reserved.</p>';
}

/**
 * Featured Posts
 *
 */
function ea_featured_posts( $loop ) {

	if( $loop->have_posts() ):
		echo '<div class="featured-posts"><div class="slick-slider">';
		while( $loop->have_posts() ): $loop->the_post();
			get_template_part( 'partials/archive', 'featured' );
		endwhile;
		echo '</div>';
		//echo '<a class="carousel-prev" href="#">' . ea_icon( 'caret-left' ) . '</a>';
		//echo '<a class="carousel-next" href="#">' . ea_icon( 'caret-right' ) . '</a>';
		echo '</div>';
	endif;
	wp_reset_postdata();

}

/**
 * Primary Category
 *
 */
function ea_primary_category() {

	$section = false;
	if( 'post' == get_post_type() ) {
		$term = ea_first_term();
		if( !empty( $term ) && ! is_wp_error( $term ) )
			$section = '<a href="' . get_term_link( $term, 'category' ) . '">' . $term->name . '</a>';
	} elseif( 'property' == get_post_type() ) {
		$title = genesis_get_cpt_option( 'headline' );
		$section = '<a href="' . get_post_type_archive_link( 'property' ) . '">' . $title . '</a>';
	}

	if( !empty( $section ) )
		echo '<p class="entry-section">' . $section . '</p>';
}

/**
 * Share Summary
 *
 */
function ea_share_summary() {

	if( ! function_exists( 'shared_counts' ) )
		return;

	echo '<div class="share-summary">';
		echo '<a class="share-summary-toggle" href="#">' . ea_icon( 'share' ) . '</a>';
		shared_counts()->front->display( 'summary' );
	echo '</div>';
}

/*
 * Agent Details
 *
 */
function ea_agent_details( $agent = false ) {

	if( empty( $agent ) && is_tax( 'agent' ) )
		$agent = get_queried_object();

	$image = ea_cf( 'ea_agent_photo', $agent->term_id, array( 'type' => 'term_meta' ) );
	if( !empty( $image ) )
		$image = wp_get_attachment_image( $image, 'thumbnail' );
	$company_1 = ea_cf( 'ea_company_line_1', $agent->term_id, array( 'type' => 'term_meta' ) );
	$company_2 = ea_cf( 'ea_company_line_2', $agent->term_id, array( 'type' => 'term_meta' ) );
	$company = $company_1 && $company_2 ? $company_1 . '<br />' . $company_2 : $company_1 . $company_2;
	$heading = is_tax( 'agent' ) ? 'h1' : 'h4';
	$url = get_term_link( $agent, 'agent' );
	if( !empty( $image ) )
		$image = '<a href="' . $url . '">' . $image . '</a>';

	echo '<div class="' . ea_class( 'listing-agent', 'has-avatar', !empty( $image ) ) . '">' . $image . '<' . $heading . '><label>Listing Agent</label> ' . $agent->name . '</' . $heading . '>' . wpautop( $company ) . '</div>';

}
