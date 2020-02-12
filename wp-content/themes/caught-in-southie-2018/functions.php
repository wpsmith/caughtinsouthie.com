<?php
/**
 * Functions
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/*
BEFORE MODIFYING THIS THEME:
Please read the instructions here (private repo): https://github.com/billerickson/Caught-in-Southie/wiki
Devs, contact me if you need access
*/

/**
 * Set up the content width value based on the theme's design.
 *
 */
if ( ! isset( $content_width ) )
    $content_width = 1024;

/**
 * Theme setup.
 *
 * Attach all of the site-wide functions to the correct hooks and filters. All
 * the functions themselves are defined below this setup function.
 *
 * @since 1.0.0
 */
function ea_child_theme_setup() {

	define( 'CHILD_THEME_VERSION', filemtime( get_stylesheet_directory() . '/assets/css/main.css' ) );

	// Includes
	include_once( get_stylesheet_directory() . '/inc/wordpress-cleanup.php' );
	include_once( get_stylesheet_directory() . '/inc/genesis-changes.php' );
	include_once( get_stylesheet_directory() . '/inc/login-logo.php' );
    include_once( get_stylesheet_directory() . '/inc/tinymce.php' );
	include_once( get_stylesheet_directory() . '/inc/disable-editor.php' );
	include_once( get_stylesheet_directory() . '/inc/helper-functions.php' );
	include_once( get_stylesheet_directory() . '/inc/loop.php' );
	include_once( get_stylesheet_directory() . '/inc/comments.php' );
	include_once( get_stylesheet_directory() . '/inc/navigation.php' );
	include_once( get_stylesheet_directory() . '/inc/shared-counts.php' );
	include_once( get_stylesheet_directory() . '/inc/display-posts-shortcode.php' );

	// Editor Styles
	add_editor_style( 'assets/css/editor-style.css' );

	// Image Sizes
	add_image_size( 'ea_archive', 400, 400, true );


}
add_action( 'genesis_setup', 'ea_child_theme_setup', 15 );

/**
 * Change the comment area text
 *
 * @since  1.0.0
 * @param  array $args
 * @return array
 */
function ea_comment_text( $args ) {
	$args['title_reply']          = __( 'Leave A Reply', 'ea_genesis_child' );
	$args['label_submit']         = __( 'Post Comment',  'ea_genesis_child' );
	$args['comment_notes_before'] = '';
	$args['comment_notes_after']  = '';
	return $args;
}
add_filter( 'comment_form_defaults', 'ea_comment_text' );

/**
 * Global enqueues
 *
 * @since  1.0.0
 * @global array $wp_styles
 */
function ea_global_enqueues() {

	// javascript
	wp_enqueue_script( 'ea-global', get_stylesheet_directory_uri() . '/assets/js/global-min.js', array( 'jquery' ), filemtime( get_stylesheet_directory() . '/assets/js/global-min.js' ), true );

	// css
    wp_dequeue_style( 'child-theme' );
	wp_enqueue_style( 'ea-fonts', ea_theme_fonts_url() );
    wp_enqueue_style( 'ea-style', get_stylesheet_directory_uri() . '/assets/css/main.css', array(), CHILD_THEME_VERSION );

	// Move jQuery to footer
	if( ! is_admin() ) {
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.js' ), false, NULL, true );
		wp_enqueue_script( 'jquery' );
	}
}
add_action( 'wp_enqueue_scripts', 'ea_global_enqueues' );

/**
 * Gutenberg scripts and styles
 *
 */
function ea_gutenberg_scripts() {
	wp_enqueue_style( 'ea-fonts', ea_theme_fonts_url() );
	wp_enqueue_style( 'ea', get_stylesheet_directory_uri() . '/assets/css/gutenberg.css', array(), filemtime( get_stylesheet_directory() . '/assets/css/gutenberg.css' ) );
}
add_action( 'enqueue_block_editor_assets', 'ea_gutenberg_scripts' );


/**
 * Theme Fonts URL
 *
 */
function ea_theme_fonts_url() {
	$font_families = apply_filters( 'ea_theme_fonts', array( 'Fira-Sans:400,600', 'Fira+Sans+Extra+Condensed' ) );
	$query_args = array(
		'family' => implode( '|', $font_families ),
		'subset' => 'latin,latin-ext',
	);
	$fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
	return esc_url_raw( $fonts_url );
}

/**
 * Blog Template
 *
 */
function ea_blog_template( $template ) {
	if( is_home() )
		$template = get_query_template( 'archive' );
	if( is_tax( 'business_type' ) )
		$template = get_query_template( 'archive-business' );
	return $template;
}
add_filter( 'template_include', 'ea_blog_template' );

/**
 * After Page Content widget area
 *
 */
function ea_after_page_content() {

	if( ! ( is_page() || is_search() ) || ! is_active_sidebar( 'after-page-content' ) )
		return;

	echo '<section class="after-page-content widget-area">';
	dynamic_sidebar( 'after-page-content' );
	echo '</section>';
}
add_action( 'genesis_after_loop', 'ea_after_page_content' );

/**
 * Site Footer
 *
 */
function ea_site_footer() {

	// Newsletter Signup
	// @see wp-content/plugins/core-functionality/inc/shortcodes.php
	if( function_exists( 'ea_newsletter_signup' ) )
		ea_newsletter_signup();

	// Back to top
	echo '<p><a class="back-to-top" href="#main-content"><span class="arrow">' . ea_icon( 'arrow-up' ) . '</span> Back to top</a></p>';

	// @see wp-content/plugins/core-functionality/inc/shortcodes.php
	if( function_exists( 'ea_social_links_shortcode' ) )
		echo ea_social_links_shortcode();

	// Copyright
	// @see inc/helper-functions.php
	ea_site_copyright();

	// Footer menu
	if( has_nav_menu( 'site-footer' ) ) {
		wp_nav_menu( array( 'theme_location' => 'site-footer', 'depth' => '1' ) );
	}

}
add_action( 'genesis_footer', 'ea_site_footer' );
