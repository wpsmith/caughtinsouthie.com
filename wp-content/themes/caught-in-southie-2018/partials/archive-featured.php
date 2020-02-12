<?php
/**
 * Archive - Featured partial
 *
 * @package      EAStarter
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

echo '<div class="featured-post">';
	if( has_post_thumbnail() )
		echo '<a class="entry-image-link" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), 'ea_archive' ) . '</a>';
	echo '<div class="post-details">';
		ea_primary_category();
		echo '<h4 class="entry-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h4>';
		echo '<div class="entry-content">';
			the_excerpt();
		echo '</div>';
	echo '</div>';
echo '</div>';
