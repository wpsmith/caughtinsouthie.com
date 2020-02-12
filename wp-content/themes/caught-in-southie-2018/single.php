<?php
/**
 * Single Post
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Category in header
 * @see inc/genesis-changes.php
 */
add_action( 'genesis_entry_header', 'ea_primary_category', 9 );

/**
 * Featured Image
 *
 */
function ea_single_featured_image() {

	$video = ea_cf( 'ea_featured_video' );
	if( $video ) {
		echo '<figure class="featured-video">' . wp_oembed_get( $video ) . '</figure>';
		return;
	}

	if( ! has_post_thumbnail() )
		return;

	$output = '<figure class="wp-caption alignleft featured-image">';
	$output .= get_the_post_thumbnail( get_the_ID(), 'large' );

	$caption = get_post( get_post_thumbnail_id() )->post_excerpt;
	if( !empty( $caption ) )
		$output .= '<figcaption class="wp-caption-text">' . $caption . '</figcaption>';

	if( function_exists( 'shared_counts' ) && ea_shared_counts_in_featured_image() )
		$output .= shared_counts()->front->display( 'before_content', false );

	$output .= '</figure>';

	// Properties can use a gallery instead
	if( 'property' == get_post_type() ) {
		$gallery = ea_cf( 'ea_property_gallery' );
		if( !empty( $gallery ) ) {

			$output = '<div class="property-gallery">';
				$output .= '<div class="slick-slider">';
				foreach( $gallery as $image_id )
					$output .= '<div class="slick-slide">' . wp_get_attachment_image( $image_id, 'large' ) . '</div>';
				$output .= '</div>';
				$output .= '<div class="property-gallery-footer">';
					$output .= '<span class="wp-caption-text">Photo gallery — swipe to view more property pictures</span>';
					if( function_exists( 'shared_counts' ) )
						$output .= shared_counts()->front->display( 'before_content', false );
				$output .= '</div>';
			$output .= '</div>';
		}
	}
	echo $output;

}
add_action( 'genesis_entry_content', 'ea_single_featured_image', 8 );

/**
 * Related Posts
 *
 */
function ea_single_related_posts() {

	$posts_per_page = 3;

	// Use SeachWP Related if available
	if( class_exists( 'SearchWP_Related' ) ) {

		// Instantiate SearchWP Related
		$searchwp_related = new SearchWP_Related();

		// Use the keywords as defined in the SearchWP Related meta box
		$keywords = get_post_meta( get_the_ID(), $searchwp_related->meta_key, true );

		$args = array(
		  's'				=> $keywords,  // The stored keywords to use
		  'engine'			=> 'default',  // the SearchWP engine to use
		  'posts_per_page' 	=> $posts_per_page,
		  'post_type' 		=> get_post_type(),
		  'post__not_in'	=> array( get_the_ID() ),
		);

		// Retrieve Related content for the current post
		$related = $searchwp_related->get( $args );
		$loop = new WP_Query( array(
			'post__in' => $related,
			'post_type' => get_post_type(),
			'orderby' => 'post__in',
			'posts_per_page' => $posts_per_page,
		));

	// Fallback, use primary category
	} else {

		$loop = new WP_Query( array(
			'posts_per_page' => $posts_per_page,
			'cat' => ea_first_term( 'category', 'term_id' ),
			'post__not_in' => $ea_displayed_posts,
			'post_type' => get_post_type(),
		));
	}

	$title = 'post' == get_post_type() ? 'You\'ll <span class="grey">Also</span> <span class="green">Like</span>' : 'More <span class="grey">You\'ll</span> <span class="green">Love</span>';

	if( $loop->have_posts() ):
		echo '<section class="post-listing related">';
		echo '<header><h3>' . $title . '</h3></header>';
		while( $loop->have_posts() ): $loop->the_post();
			get_template_part( 'partials/archive', 'related' );
		endwhile;
		echo '</section>';
	endif;
	wp_reset_postdata();

}
$priority = 'post' == get_post_type() ? 7 : 9;
add_action( 'genesis_after_entry', 'ea_single_related_posts', $priority );

/**
 * After Post Content widget area
 *
 */
function ea_after_post_content() {

	if( ! is_active_sidebar( 'after-post-content' ) )
		return;

	echo '<section class="after-post-content widget-area">';
	dynamic_sidebar( 'after-post-content' );
	echo '</section>';
}
add_action( 'genesis_after_entry', 'ea_after_post_content', 7 );

genesis();
