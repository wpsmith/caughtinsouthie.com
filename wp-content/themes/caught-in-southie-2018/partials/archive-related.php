<?php
/**
 * Archive - Related partial
 *
 * @package      EAStarter
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

echo '<article class="post-summary">';
	echo '<a class="entry-image-link" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), 'ea_archive' ) . '</a>';
	echo '<div class="post-details">';
		ea_primary_category();
		echo '<h4 class="entry-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h4>';
		genesis_post_info();
	echo '</div>';
echo '</article>';
