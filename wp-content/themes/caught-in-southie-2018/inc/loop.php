<?php
/**
 * Navigation
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Archive Loop
 *
 */
function ea_archive_loop() {

	if( is_singular() ) {
		genesis_do_loop();

	} else {

		if ( have_posts() ) {

			do_action( 'genesis_before_while' );

			while ( have_posts() ) {

				do_action( 'genesis_before_entry' );

				the_post();
				$context = apply_filters( 'ea_loop_partial_context', is_search() ? 'search' : get_post_type() );
				get_template_part( 'partials/archive', $context );

				do_action( 'genesis_after_entry' );

			}

			do_action( 'genesis_after_endwhile' );

		} else {

			do_action( 'genesis_loop_else' );

		}
	}
}
add_action( 'genesis_loop', 'ea_archive_loop' );
remove_action( 'genesis_loop', 'genesis_do_loop' );
