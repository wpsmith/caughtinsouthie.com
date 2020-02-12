<?php
/**
 * Customizations to Shared Counts plugin
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Change email icon
 *
 */
function ea_share_email_icon( $link, $id, $style ) {
	if( 'email' == $link['type'] )
		$link['icon'] = ea_icon( 'email' );
	return $link;
}
add_filter( 'shared_counts_link', 'ea_share_email_icon', 10, 3 );

/**
 * Shared Counts Location
 * Single posts with featured images have social icons in image caption
 *
 */
function ea_shared_counts_location( $locations ) {
	if( ea_shared_counts_in_featured_image() )
		$locations['before']['hook'] = false;
	return $locations;
}
add_filter( 'shared_counts_theme_locations', 'ea_shared_counts_location' );

/**
 * Conditional, Shared Counts in Featured Image
 *
 */
function ea_shared_counts_in_featured_image() {

	if( ! ( is_single() && has_post_thumbnail() ) )
		return false;

	$image_attr = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
	$image_width = $image_attr[1];

	return $image_width > 700;

}

/**
 * Production URL
 * @author Bill Erickson
 * @link http://www.billerickson.net/code/use-production-url-in-shared-counts/
 *
 * @param string $url (optional), URL to convert to production.
 * @return string $url, converted to production. Uses home_url() if no url provided
 *
 */
function ea_production_url( $url = false ) {

	$url = $url ? esc_url( $url ) : home_url();
	$production = 'https://caughtinsouthie.com';
	$url = str_replace( home_url(), $production, $url );

	return esc_url( $url );

}

/**
 * Use Production URL for Share Count API
 * @author Bill Erickson
 * @link http://www.billerickson.net/code/use-production-url-in-shared-counts/
 *
 * @param array $params, API parameters used when fetching share counts
 * @return array
 */
function ea_production_url_share_count_api( $params ) {

	$params['url'] = ea_production_url( $params['url'] );
	return $params;

}
add_filter( 'shared_counts_api_params', 'ea_production_url_share_count_api' );

/**
 * Use Production URL for Share Count link
 * @author Bill Erickson
 * @link http://www.billerickson.net/code/use-production-url-in-shared-counts/
 *
 * @param array $link, elements of the link
 * @return array
 */
function ea_production_url_share_count_link( $link ) {

	$link['link'] = ea_production_url( $link['link'] );
	return $link;

}
add_filter( 'shared_counts_link', 'ea_production_url_share_count_link' );

/**
 * Only update share counts on single post
 *
 * @param array $update_queue
 * @return array
 */
function be_update_share_count_on_single( $update_queue ) {

  if( is_singular() && !empty( $update_queue ) )
    $update_queue = array_key_exists( get_queried_object_id(), $update_queue ) ? array( get_queried_object_id() => $update_queue[get_queried_object_id()] ) : array();
  else
    $update_queue = array();

  return $update_queue;
}
add_filter( 'shared_counts_update_queue', 'be_update_share_count_on_single' );
