<?php
/**
 * Navigation
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

// Primary Nav in Header
remove_action( 'genesis_after_header', 'genesis_do_nav' );
add_action( 'genesis_header', 'genesis_do_nav', 11 );

/**
 * Primary Menu Extras
 *
 */
function ea_primary_menu_extras( $menu, $args ) {

	if( 'primary' !== $args->theme_location )
		return $menu;

	$extra = '<li class="menu-item extras">';
	$extra .= '<a href="#" class="search-toggle">' . ea_icon( 'search' ) . '</a>';
	$extra .= get_search_form( false );
	$extra .= ea_social_links_shortcode( array( 'wrapper' => false ) );
	$extra .= '<span class="label">Follow Us</span>';
	$extra .= '</li>';

	return $menu . $extra;
}
add_filter( 'wp_nav_menu_items', 'ea_primary_menu_extras', 10, 2 );

/**
 * Mobile Menu Toggle
 *
 */
function ea_mobile_menu_toggle() {

	if( ! has_nav_menu( 'mobile' ) )
		return;

    echo '<div class="nav-mobile">';
	echo '<a class="events-button" href="' . home_url( '/events/' ) . '">' . ea_icon( 'calendar' ) . '</a>';
	echo '<a class="search-toggle" href="#">' . ea_icon( 'search' ) . '</a>';
	echo '<a class="mobile-menu-toggle" href="#">' . ea_icon( 'menu' ) . '</a>';
	echo '</div>';
}
add_action( 'genesis_header', 'ea_mobile_menu_toggle', 12 );

/**
 * Mobile Menu
 *
 */
function ea_mobile_menu() {
  if( has_nav_menu( 'mobile' ) ) {
    echo '<div id="sidr-mobile-menu" class="sidr right">';
      echo '<div class="sidr-close-wrapper"><a class="sidr-menu-close" href="#">' . ea_icon( 'close' ) . '</a></div>';
	  echo '<div class="sidr-header"><a href="' . home_url() . '" class="site-logo">' . get_bloginfo( 'name' ) . '</a></div>';
      wp_nav_menu( array( 'theme_location' => 'mobile' ) );
	  echo '<div class="sidr-footer">';
	  	echo ea_social_links_shortcode();
		ea_site_copyright();
	  echo '</div>';
    echo '</div>';
  }
}
add_action( 'wp_footer', 'ea_mobile_menu' );

/**
 * Mobile Search
 *
 */
function ea_mobile_search() {
	echo '<div class="mobile-search">' . get_search_form( false ) . '</div>';
}
add_action( 'genesis_after_header', 'ea_mobile_search', 1 );

/**
 * Mobile Header Ad
 *
 */
function ea_mobile_header_ad() {
	if( is_active_sidebar( 'mobile-header' ) ) {
		echo '<div class="mobile-header"><div class="wrap">';
		dynamic_sidebar( 'mobile-header' );
		echo '</div></div>';
	}
}
add_action( 'genesis_after_header', 'ea_mobile_header_ad', 2 );

/**
 * Archive Navigation
 *
 * @author Bill Erickson
 * @see https://www.billerickson.net/custom-pagination-links/
 *
 */
function ea_archive_navigation() {

	if( is_singular() )
		return;

	$settings = array(
		'count' => 8,
		'prev_text' => ea_icon( 'arrow-left' ),
		'next_text' => ea_icon( 'arrow-right' )
	);

	global $wp_query;
	$current = max( 1, get_query_var( 'paged' ) );
	$total = $wp_query->max_num_pages;
	$links = array();

	if( 1 == $total )
		return;

	// Offset for next link
	if( $current < $total )
		$settings['count']--;

	// Previous
	if( $current > 1 ) {
		$settings['count']--;
		$links[] = ea_archive_navigation_link( $current - 1, 'prev', $settings['prev_text'] );
	}

	// Current
	$links[] = ea_archive_navigation_link( $current, 'current' );

	// Next Pages
	for( $i = 1; $i < $settings['count']; $i++ ) {
		$page = $current + $i;
		if( $page <= $total ) {
			$links[] = ea_archive_navigation_link( $page );
		}
	}

	// Next
	if( $current < $total ) {
		$links[] = ea_archive_navigation_link( $current + 1, 'next', $settings['next_text'] );
	}


	echo '<nav class="navigation posts-navigation" role="navigation">';
    	echo '<h2 class="screen-reader-text">Posts navigation</h2>';
    	echo '<div class="nav-links">' . join( '', $links ) . '</div>';
	echo '</nav>';
}
add_action( 'genesis_after_endwhile', 'ea_archive_navigation' );
remove_action( 'genesis_after_endwhile', 'genesis_posts_nav' );

/**
 * Archive Navigation Link
 *
 * @author Bill Erickson
 * @see https://www.billerickson.net/custom-pagination-links/
 *
 * @param int $page
 * @param string $class
 * @param string $label
 * @return string $link
 */
function ea_archive_navigation_link( $page = false, $class = '', $label = '' ) {

	if( ! $page )
		return;

	$classes = array( 'page-numbers' );
	if( !empty( $class ) )
		$classes[] = $class;
	$classes = array_map( 'sanitize_html_class', $classes );

	$label = $label ? $label : $page;
	$link = esc_url_raw( get_pagenum_link( $page ) );

	return '<a class="' . join ( ' ', $classes ) . '" href="' . $link . '">' . $label . '</a>';

}
