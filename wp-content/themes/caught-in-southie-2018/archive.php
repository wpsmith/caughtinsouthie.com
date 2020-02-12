<?php
/**
 * Archive
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

// Full Width
add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );

/**
 * Agent Intro
 *
 */
function ea_archive_agent_intro() {
	if( is_tax( 'agent' ) ) {
		ea_agent_details();
		echo apply_filters( 'ea_the_content', get_term_meta( get_queried_object_id(), 'intro_text', true ) );

		remove_action( 'genesis_before_loop', 'genesis_do_taxonomy_title_description', 15 );
	}
}
add_action( 'genesis_before_loop', 'ea_archive_agent_intro' );

/**
 * Category Featured Posts
 *
 */
function ea_category_featured_posts() {

	if( ! is_category() || get_query_var( 'paged' ) )
		return;

	$loop = new WP_Query( array(
		'posts_per_page' => 6,
		'category_name'  => get_query_var( 'category_name' ),
		'tax_query' => array(
			array(
				'taxonomy' => 'featured_area',
				'field'    => 'slug',
				'terms'    => array( 'featured-category' )
			)
		)
	));

	// @see inc/helper-functions.php
	ea_featured_posts( $loop );
}
add_action( 'genesis_before_loop', 'ea_category_featured_posts', 5 );

/**
 * Featured Properties
 *
 */
function ea_featured_properties() {

	if( ! ( is_post_type_archive( 'property' ) || is_tax( 'agent' ) ) || get_query_var( 'paged' ) )
		return;

	$loop = new WP_Query( array(
		'posts_per_page' => 6,
		'post_type'      => 'property',
		'tax_query' => array(
			array(
				'taxonomy' => 'featured_area',
				'field'    => 'slug',
				'terms'    => array( 'featured-property' )
			)
		)
	));

	// @see inc/helper-functions.php
	ea_featured_posts( $loop );
}
add_action( 'genesis_before_loop', 'ea_featured_properties', 5 );

/**
 * Archive Loop Ads
 *
 */
function ea_archive_loop_ads() {

	// Key = ad area, Value = post count
	$ad_areas = array(
		1 => -1,
		2 => 4,
	);
	global $wp_query;


	foreach( $ad_areas as $ad_area => $ad_area_post_id ) {
		if( $ad_area_post_id == $wp_query->current_post && is_active_sidebar( 'archive-ad-' . $ad_area ) ) {
			echo '<article class="post-summary sponsor">';
			dynamic_sidebar( 'archive-ad-' . $ad_area );
			echo '</article>';
		}
	}
}
add_action( 'genesis_before_entry', 'ea_archive_loop_ads' );

/**
 * Archive After Loop Ads
 *
 */
function ea_archive_after_loop_ad() {

	if( is_active_sidebar( 'archive-ad-3' ) ) {
		echo '<article class="post-summary sponsor after-loop">';
		dynamic_sidebar( 'archive-ad-3' );
		echo '</artice>';
	}
}
add_action( 'genesis_after_endwhile', 'ea_archive_after_loop_ad', 20 );

genesis();
