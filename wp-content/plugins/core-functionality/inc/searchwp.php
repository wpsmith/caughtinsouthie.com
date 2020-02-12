<?php
/**
 * Custom Fields
 *
 * @package      CoreFunctionality
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Add search weight to more recently published entries
 * @see https://searchwp.com/docs/kb/add-weight-recently-published-entries/
 */
function be_swp_weight_newer_posts( $sql ) {
	global $wpdb;
	// Adjust how much weight is given to newer publish dates. There
	// is no science here as it correlates with the other weights
	// in your engine configuration, and the content of your site.
	// Experiment until results are returned as you want them to be.
	$modifier = 30;
	$sql .= " + ( 100 * EXP( ( 1 - ABS( ( UNIX_TIMESTAMP( {$wpdb->prefix}posts.post_date ) - UNIX_TIMESTAMP( NOW() ) ) / 86400 ) ) / 1 ) * {$modifier} )";
	return $sql;
}
add_filter( 'searchwp_weight_mods', 'be_swp_weight_newer_posts' );
