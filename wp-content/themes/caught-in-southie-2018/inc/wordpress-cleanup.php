<?php
/**
 * WordPress Cleanup
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

 /**
  * Dont Update the Theme
  *
  * If there is a theme in the repo with the same name, this prevents WP from prompting an update.
  *
  * @since  1.0.0
  * @author Bill Erickson
  * @link   http://www.billerickson.net/excluding-theme-from-updates
  * @param  array $r Existing request arguments
  * @param  string $url Request URL
  * @return array Amended request arguments
  */
 function ea_dont_update_theme( $r, $url ) {
 	if ( 0 !== strpos( $url, 'https://api.wordpress.org/themes/update-check/1.1/' ) )
  		return $r; // Not a theme update request. Bail immediately.
  	$themes = json_decode( $r['body']['themes'] );
  	$child = get_option( 'stylesheet' );
 	unset( $themes->themes->$child );
  	$r['body']['themes'] = json_encode( $themes );
  	return $r;
  }
 add_filter( 'http_request_args', 'ea_dont_update_theme', 5, 2 );

/**
 * Dequeue jQuery Migrate
 *
 */
function ea_dequeue_jquery_migrate( &$scripts ){
	if( !is_admin() ) {
		$scripts->remove( 'jquery');
		$scripts->add( 'jquery', false, array( 'jquery-core' ), '1.10.2' );
	}
}
add_filter( 'wp_default_scripts', 'ea_dequeue_jquery_migrate' );

/**
 * Clean Nav Menu Classes
 *
 */
function ea_clean_nav_menu_classes( $classes, $item ) {

	if( ! is_array( $classes ) )
		return $classes;

	$remove_classes = array(
		'menu-item-type-custom',
		'menu-item-type-taxonomy',
		'menu-item-object-custom',
		'menu-item-object-category',
	);
	$classes = array_diff( $classes, $remove_classes );

	foreach( $classes as $i => $class ) {
		// Remove class with menu item id
		$id = strtok( $class, 'menu-item-' );
		if( 0 < intval( $id ) )
			unset( $classes[ $i ] );
	}

	// Mark current category active on single post
	if( in_array( 'current-post-ancestor', $classes ) )
		$classes[] = 'current-menu-ancestor';

	// Mark properties active on single property
	if( is_singular( 'property' ) && $item->url == get_post_type_archive_link( 'property' ) )
		$classes[] = 'current-menu-ancestor';

	// Remove extra -ancestor classes
	$remove_classes = array(
		'current-post-ancestor',
		'current-menu-parent',
		'current-post-parent'
	);
	$classes = array_diff( $classes, $remove_classes );

	return $classes;
}
add_filter( 'nav_menu_css_class', 'ea_clean_nav_menu_classes', 5, 2 );


/**
 * Clean Post Classes
 *
 */
function ea_clean_post_classes( $classes ) {

	if( ! is_array( $classes ) )
		return $classes;

    $allowed_classes = array(
  		'hentry',
  		'type-' . get_post_type(),
      'one-half',
      'one-third',
      'two-thirds',
      'one-fourth',
      'two-fourths',
      'three-fourths',
      'one-fifth',
      'two-fifths',
      'three-fifths',
      'four-fifths',
   	);

	return array_intersect( $classes, $allowed_classes );
}
add_filter( 'post_class', 'ea_clean_post_classes', 5 );

/**
 * Staff comment class
 *
 */
function ea_staff_comment_class( $classes, $class, $comment_id, $comment, $post_id ) {
	if( empty( $comment->user_id ) )
		return $classes;
	$staff_roles = array( 'comment_manager', 'author', 'editor', 'administrator' );
	$staff_roles = apply_filters( 'ea_staff_roles', $staff_roles );
	$user = get_userdata( $comment->user_id );
	if( !empty( array_intersect( $user->roles, $staff_roles ) ) )
		$classes[] = 'staff';
	return $classes;
}
add_filter( 'comment_class', 'ea_staff_comment_class', 10, 5 );

/**
 * Remove avatars from comment list
 *
 */
function ea_remove_avatars_from_comments( $avatar ) {
	global $in_comment_loop;
	return $in_comment_loop ? '' : $avatar;
}
add_filter( 'get_avatar', 'ea_remove_avatars_from_comments' );

/**
 * Excerpt More
 *
 */
function ea_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'ea_excerpt_more' );

/**
 * Excerpt Length
 *
 */
function ea_excerpt_length() {
	return is_search() ? 60 : 30;
}
add_filter( 'excerpt_length', 'ea_excerpt_length' );
