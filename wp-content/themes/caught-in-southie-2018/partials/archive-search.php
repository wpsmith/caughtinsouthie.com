<?php
/**
 * Archive partial
 *
 * @package      EAStarter
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

echo '<article class="search-result">';
	$pretty_url = remove_query_arg( array( 'swpmtx', 'swpmtxnonce' ), get_permalink() );
	echo '<h3 class="entry-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
	echo '<p class="small"><a href="' . get_permalink() . '">' . $pretty_url . '</a></p>';
	echo '<div class="entry-content">';
		the_excerpt();
	echo '</div>';
echo '</article>';
