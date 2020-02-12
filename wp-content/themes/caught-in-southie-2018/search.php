<?php
/**
 * Search Results
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Search Header
 *
 */
function ea_search_header() {
	echo '<div class="search-header">';
	echo '<h1 class="archive-title">Search Results</h1>';
	get_search_form();
	echo '</div>';
}
add_action( 'genesis_before_loop', 'ea_search_header' );

genesis();
