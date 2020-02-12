<?php
/**
 * Template Name: Modules
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

// Full width layout
add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );

// Remove after page widget area
remove_action( 'genesis_after_loop', 'ea_after_page_content' );


/**
 * Modules
 *
 */
function ea_modules() {

	$modules = ea_cf( 'ea_modules' );
	if( empty( $modules ) )
		return;

	foreach( $modules as $i => $module ) {

		ea_module_open( $module, $i );

		switch( $module['_type'] ) {

			case 'content':
				echo apply_filters( 'ea_the_content', $module['content'] );
				break;

			case 'featured_rotator':
				$loop = new WP_Query( array(
					'posts_per_page' => 6,
					'category_name'  => esc_attr( $module['category'] ),
					'tax_query' => array(
						array(
							'taxonomy' => 'featured_area',
							'field'    => 'slug',
							'terms'    => array( esc_attr( $module['featured_area'] ) )
						)
					)
				));

				// @see inc/helper-functions.php
				ea_featured_posts( $loop );
				break;

			case 'featured_pages':
				if( empty( $module['featured_pages'] ) )
					break;
				$ids = str_replace( ', ', ',', $module['featured_pages'] );
				$ids = array_filter( array_map( 'intval', explode( ',', $ids ) ) );
				$loop = new WP_Query( array(
					'post_type'	=> 'page',
					'post__in'	=> $ids,
					'orderby'	=> 'post__in',
				));
				ea_featured_posts( $loop );
				break;

			case 'post_listing':

				$title = '';
				if( !empty( $module['title_black'] ) )
					$title .= $module['title_black'];
				if( !empty( $module['title_grey'] ) )
					$title .= ' <span class="grey">' . $module['title_grey'] . '</span>';
				if( !empty( $module['title_green'] ) )
					$title .= ' <span class="green">' . $module['title_green'] . '</span>';
				if( !empty( $module['category'] ) )
					$title .= ' <a class="go" href="' . get_term_link( $module['category'], 'category' ) . '">' . ea_icon( 'arrow-right' ) . '</a>';

				$loop = new WP_Query( array(
					'posts_per_page' => 3,
					'category_name' => esc_attr( $module['category'] ),
					'post_type' => esc_attr( $module['post_type'] ),
				));
				if( $loop->have_posts() ):
					echo '<div class="post-listing">';
					if( !empty( $title ) )
						echo '<header><h3>' . $title . '</h3></header>';
					while( $loop->have_posts() ): $loop->the_post();
						get_template_part( 'partials/archive' );
					endwhile;
					echo '</div>';
				endif;
				wp_reset_postdata();

				break;

			case 'ad_trending_content':
				echo '<div class="left">' . apply_filters( 'ea_the_content', $module['ad'] ) . '</div>';
				echo '<div class="right">';
					if( !empty( $module['title'] ) )
						echo '<h4>' . esc_html( $module['title'] ) . '</h4>';
					$loop = new WP_Query( array(
						'posts_per_page' => 6,
						'orderby' => 'meta_value_num',
						'order' => 'DESC',
						'meta_key' => 'shared_counts_total',
						'date_query' => array(
							array(
								'after' => '1 month ago',
							)
						)
					));
					if( $loop->have_posts() ):
						echo '<ol>';
						while( $loop->have_posts() ): $loop->the_post();
							echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
						endwhile;
						echo '</ol>';
					endif;
					wp_reset_postdata();
				echo '</div>';
				break;

			case 'ad_content':
				echo '<div class="left">' . apply_filters( 'ea_the_content', $module['ad'] ) . '</div>';
				echo '<div class="right">';
					if( !empty( $module['title'] ) )
						echo '<h4>' . esc_html( $module['title'] ) . '</h4>';
					echo apply_filters( 'ea_the_content', $module['content'] );
				echo '</div>';
				break;

			case 'ad_weather':
				echo '<div class="left">' . apply_filters( 'ea_the_content', $module['ad'] ) . '</div>';
				echo '<div class="right">';
				dynamic_sidebar( 'weather' );
				echo '</div>';
				break;

			case 'ad_post_listing':
				$offset = !empty( $module['offset'] ) ? 3 : false;
				$loop = new WP_Query( array(
					'posts_per_page' => 6,
					'offset' => $offset,
					'post_type' => $module['post_type'],
					'category_name' => $module['category'],
				));

				echo '<div class="left">' . apply_filters( 'ea_the_content', $module['ad'] ) . '</div>';
				echo '<div class="right">';
					if( !empty( $module['title'] ) )
						echo '<h4>' . esc_html( $module['title'] ) . '</h4>';
					if( $loop->have_posts() ):
						echo '<ol>';
						while( $loop->have_posts() ): $loop->the_post();
							echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
						endwhile;
						echo '</ol>';
					endif;
					wp_reset_postdata();
				echo '</div>';
				break;

		}
		ea_module_close( $module, $i );
	}

}
add_action( 'genesis_loop', 'ea_modules' );
remove_action( 'genesis_loop', 'ea_archive_loop' );

/**
 * Module Open
 *
 */
function ea_module_open( $module, $i ) {

	$classes = array( 'module' );
	$type = 'type-' . str_replace( '_', '-', $module['_type'] );
	$type = str_replace( 'type-ad', 'type-cis', $type );
	$classes[] = $type;

	if( in_array( $module['_type'], array( 'ad_trending_content', 'ad_content', 'ad_weather', 'ad_post_listing' ) ) )
		$classes[] = 'two-col';


	echo '<div class="' . join( ' ', $classes ) . '">';

}

/**
 * Module Close
 *
 */
function ea_module_close( $module, $i ) {
	echo '</div>';
}

genesis();
