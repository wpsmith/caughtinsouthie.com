<?php
/**
 * Genesis Changes
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

// Theme Supports
add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
add_theme_support( 'genesis-responsive-viewport' );
remove_theme_support( 'genesis-footer-widgets' );
add_theme_support( 'genesis-structural-wraps', array( 'header', 'menu-secondary', 'site-inner', 'footer-widgets', 'footer' ) );
add_theme_support( 'genesis-menus', array( 'primary' => 'Primary Navigation Menu', 'site-footer' => 'Footer', 'mobile' => 'Mobile Menu' ) );
add_theme_support( 'genesis-inpost-layouts' );
add_theme_support( 'genesis-archive-layouts' );

// Remove admin bar styling
add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );

// Remove Edit link
add_filter( 'genesis_edit_post_link', '__return_false' );

// Remove Genesis Favicon (use site icon instead)
remove_action( 'wp_head', 'genesis_load_favicon' );

// Remove unused page layouts
genesis_unregister_layout( 'content-sidebar-sidebar' );
genesis_unregister_layout( 'sidebar-sidebar-content' );
genesis_unregister_layout( 'sidebar-content-sidebar' );

// Remove unused sidebars
unregister_sidebar( 'sidebar-alt' );

// Remove default site footer
remove_action( 'genesis_footer', 'genesis_do_footer' );

// Add New Sidebars
genesis_register_widget_area( array( 'id' => 'business-sidebar', 'name' => 'Business Listings Sidebar' ) );
genesis_register_widget_area( array( 'id' => 'mobile-header', 'name' => 'Mobile Header' ) );
genesis_register_widget_area( array( 'id' => 'after-page-content', 'name' => 'After Page Content' ) );
genesis_register_widget_area( array( 'id' => 'after-post-content', 'name' => 'After Post Content' ) );
genesis_register_widget_area( array( 'id' => 'archive-ad-1', 'name' => 'Archive Ad 1', 'description' => '300x250' ) );
genesis_register_widget_area( array( 'id' => 'archive-ad-2', 'name' => 'Archive Ad 2', 'description' => '300x250' ) );
genesis_register_widget_area( array( 'id' => 'archive-ad-3', 'name' => 'Archive Ad 3', 'description' => '300x250' ) );
genesis_register_widget_area( array( 'id' => 'weather', 'name' => 'Weather' ) );

/**
 * Sidebar Switcher
 *
 */
function ea_sidebar_switcher() {

	if( ! ( is_post_type_archive( 'business' ) || is_singular( 'business' ) || is_tax( 'business_type' ) ) )
		return;

	remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
	remove_action( 'genesis_sidebar', 'ss_do_sidebar' );
	add_action( 'genesis_sidebar', 'ea_business_listings_sidebar' );
}
add_action( 'genesis_meta', 'ea_sidebar_switcher' );

/**
 * Business Listings Sidebar
 *
 */
function ea_business_listings_sidebar() {
	dynamic_sidebar( 'business-sidebar' );
}

/**
 * Remove Genesis Templates
 *
 */
function ea_remove_genesis_templates( $page_templates ) {
	unset( $page_templates['page_archive.php'] );
	unset( $page_templates['page_blog.php'] );
	return $page_templates;
}
add_filter( 'theme_page_templates', 'ea_remove_genesis_templates' );

/**
 * Remove Breadcrumb Arguments
 *
 * @since 1.0.0
 * @param array $args
 * @return array
 */
function ea_breadcrumb_args( $args ) {

	// Remove labels
	foreach( $args['labels'] as $key => &$label )
		$label = '';

	return $args;
}
add_filter( 'genesis_breadcrumb_args', 'ea_breadcrumb_args', 5 );

/**
 * Wrap last breadcrumb in a span for styling
 * @author Gary Jones
 *
 * @param array $crumbs, existing HTML markup for breadcrumbs
 * @param array $args, breadcrumb arguments
 * @return array $crumbs, amended breadcrumbs
 */
function ea_wrap_last_breadcrumb( $crumbs, $args ) {
	// Don't run on home or front page
	if( is_home() || is_front_page() )
		return $crumbs;
	// Ensure duplicate and empty crumb entries are handled.
	$crumbs = array_filter( array_unique( $crumbs ) );
	$last_crumb_index = count( $crumbs ) - 1;
	// Some "crumbs" actually contain multiple separated crumbs (i.e. sub-pages)
	// so make sure we're really only getting the last separated crumb
	$crumb_parts = explode( $args['sep'], $crumbs[ $last_crumb_index ] );
	if ( count( $crumb_parts ) > 1 ) {
		$last_crumb_part_index = count( $crumb_parts ) - 1;
		$crumb_parts[ $last_crumb_part_index ] = '<span class="last-breadcrumb">' . $crumb_parts[ $last_crumb_part_index ] . '</span>';
		$crumbs[ $last_crumb_index ] = join( $args['sep'], $crumb_parts );
	} else {
		$crumbs[ $last_crumb_index ] = '<span class="last-breadcrumb">' . $crumbs[ $last_crumb_index ] . '</span>';
	}
	return $crumbs;
}
add_filter( 'genesis_build_crumbs', 'ea_wrap_last_breadcrumb', 10, 2 );

/**
 * Removes Unused Genesis user settings
 *
 * @since 1.0.0
 */
function ea_remove_user_profile_fields() {
//	remove_action( 'show_user_profile', 'genesis_user_options_fields' );
//	remove_action( 'edit_user_profile', 'genesis_user_options_fields' );
	remove_action( 'show_user_profile', 'genesis_user_archive_fields' );
	remove_action( 'edit_user_profile', 'genesis_user_archive_fields' );
	remove_action( 'show_user_profile', 'genesis_user_seo_fields'     );
	remove_action( 'edit_user_profile', 'genesis_user_seo_fields'     );
	remove_action( 'show_user_profile', 'genesis_user_layout_fields'  );
	remove_action( 'edit_user_profile', 'genesis_user_layout_fields'  );
}
add_action( 'admin_init', 'ea_remove_user_profile_fields' );

remove_action( 'admin_menu', 'genesis_add_inpost_seo_box' );
/**
 * Re-prioritise Genesis SEO metabox from high to default.
 *
 * Copied and amended from /lib/admin/inpost-metaboxes.php, version 2.0.0.
 *
 * @since 1.0.0
 */
function ea_add_inpost_seo_box() {

	if ( genesis_detect_seo_plugins() )
		return;

	foreach ( (array) get_post_types( array( 'public' => true ) ) as $type ) {
		if ( post_type_supports( $type, 'genesis-seo' ) )
			add_meta_box( 'genesis_inpost_seo_box', __( 'Theme SEO Settings', 'genesis' ), 'genesis_inpost_seo_box', $type, 'normal', 'default' );
	}

}
add_action( 'admin_menu', 'ea_add_inpost_seo_box' );

remove_action( 'admin_menu', 'genesis_add_inpost_layout_box' );
/**
 * Re-prioritise layout metabox from high to default.
 *
 * Copied and amended from /lib/admin/inpost-metaboxes.php, version 2.0.0.
 *
 * @since 1.0.0
 */
function ea_add_inpost_layout_box() {

	if ( ! current_theme_supports( 'genesis-inpost-layouts' ) )
		return;

	foreach ( (array) get_post_types( array( 'public' => true ) ) as $type ) {
		if ( post_type_supports( $type, 'genesis-layouts' ) )
			add_meta_box( 'genesis_inpost_layout_box', __( 'Layout Settings', 'genesis' ), 'genesis_inpost_layout_box', $type, 'normal', 'default' );
	}

}
add_action( 'admin_menu', 'ea_add_inpost_layout_box' );

/**
 * Remove Genesis widgets.
 *
 * @since 1.0.0
 */
function ea_remove_genesis_widgets() {
    //unregister_widget( 'Genesis_Featured_Page' );
    //unregister_widget( 'Genesis_Featured_Post' );
    unregister_widget( 'Genesis_User_Profile_Widget' );
}
add_action( 'widgets_init', 'ea_remove_genesis_widgets', 20 );

/**
 * Remove Genesis theme settings metaboxes.
 *
 * @since 1.0.0
 * @param string $_genesis_theme_settings_pagehook
 */
function ea_remove_genesis_metaboxes( $_genesis_theme_settings_pagehook ) {
	//remove_meta_box( 'genesis-theme-settings-feeds',      $_genesis_theme_settings_pagehook, 'main' );
	remove_meta_box( 'genesis-theme-settings-header',     $_genesis_theme_settings_pagehook, 'main' );
	remove_meta_box( 'genesis-theme-settings-nav',        $_genesis_theme_settings_pagehook, 'main' );
	//remove_meta_box( 'genesis-theme-settings-breadcrumb', $_genesis_theme_settings_pagehook, 'main' );
	//remove_meta_box( 'genesis-theme-settings-comments',   $_genesis_theme_settings_pagehook, 'main' );
	//remove_meta_box( 'genesis-theme-settings-posts',      $_genesis_theme_settings_pagehook, 'main' );
	remove_meta_box( 'genesis-theme-settings-blogpage',   $_genesis_theme_settings_pagehook, 'main' );
	//remove_meta_box( 'genesis-theme-settings-layout', $_genesis_theme_settings_pagehook, 'main' );
	//remove_meta_box( 'genesis-theme-settings-scripts',    $_genesis_theme_settings_pagehook, 'main' );
}
add_action( 'genesis_theme_settings_metaboxes', 'ea_remove_genesis_metaboxes' );

/**
 * Remove Genesis Customizer Settings
 *
 * @since  1.0.0
 * @param object $wp_customize
 */
function ea_remove_genesis_customizer( $wp_customize ) {
    $wp_customize->remove_section( 'static_front_page'    );
    $wp_customize->remove_section( 'title_tagline'        );
    $wp_customize->remove_section( 'nav'                  );
    $wp_customize->remove_section( 'genesis_layout'       );
    $wp_customize->remove_section( 'genesis_comments'     );
    $wp_customize->remove_section( 'genesis_breadcrumbs'  );
    $wp_customize->remove_section( 'genesis_archives'     );
    $wp_customize->remove_section( 'genesis_color_scheme' );
}
//add_action( 'customize_register', 'ea_remove_genesis_customizer', 30 );

/**
 * Default Titles for Term Archives
 *
 * @author Bill Erickson
 * @url http://www.billerickson.net/default-category-and-tag-titles
 *
 * @param string $headline
 * @param object $term
 * @return string $headline
 */
function ea_default_term_title( $value, $term_id, $meta_key, $single ) {

	if( ( is_category() || is_tag() || is_tax() ) && 'headline' == $meta_key && ! is_admin() ) {

		// Grab the current value, be sure to remove and re-add the hook to avoid infinite loops
		remove_action( 'get_term_metadata', 'ea_default_term_title', 10 );
		$value = get_term_meta( $term_id, 'headline', true );
		add_action( 'get_term_metadata', 'ea_default_term_title', 10, 4 );

		// Use term name if empty
		if( empty( $value ) ) {
			$term = get_term_by( 'term_taxonomy_id', $term_id );
			$value = $term->name;
		}

	}

	return $value;
}
add_filter( 'get_term_metadata', 'ea_default_term_title', 10, 4 );

/**
 * Default Description for Term Archives
 *
 * @author Bill Erickson
 * @see http://www.billerickson.net/default-category-and-tag-titles
 *
 * @param string $headline
 * @param object $term
 * @return string $headline
 */
function ea_default_term_description( $value, $term_id, $meta_key, $single ) {

    if( ( is_category() || is_tag() || is_tax() ) && 'intro_text' == $meta_key && ! is_admin() ) {

        // Grab the current value, be sure to remove and re-add the hook to avoid infinite loops
        remove_action( 'get_term_metadata', 'ea_default_term_description', 10 );
        $value = get_term_meta( $term_id, 'intro_text', true );
        add_action( 'get_term_metadata', 'ea_default_term_description', 10, 4 );

        // Use term name if empty
        if( empty( $value ) ) {
            $term = get_term_by( 'term_taxonomy_id', $term_id );
            $value = $term->description;
        }

    }

    return $value;
}
add_filter( 'get_term_metadata', 'ea_default_term_description', 10, 4 );

/**
 * Add '.nav-menu' class to nav menus
 *
 * @param string $open, opening markup
 * @param array $args, markup args
 * @return string
 */
function ea_nav_menu_class( $open, $args ) {
	$open = str_replace( $args['context'], $args['context'] . ' nav-menu', $open );
	return $open;
}
add_filter( 'genesis_markup_nav-primary_open', 'ea_nav_menu_class', 10, 2 );
add_filter( 'genesis_markup_nav-secondary_open', 'ea_nav_menu_class', 10, 2 );

/**
 * Change '.content-sidebar-wrap' to '.content-area'
 *
 * @param string $open, opening markup
 * @param array $args, markup args
 * @return string
 */
function ea_change_content_sidebar_wrap( $open, $args ) {
	return str_replace( 'content-sidebar-wrap', 'content-area', $open );
}
add_filter( 'genesis_markup_content-sidebar-wrap_open', 'ea_change_content_sidebar_wrap', 10, 2 );

/**
 * Change '.content' to '.site-main'
 *
 * @param string $open, opening markup
 * @param array $args, markup args
 * @return string
 */
function ea_change_content( $open, $args ) {
	return str_replace( 'content', 'site-main', $open );
}
add_filter( 'genesis_markup_content_open', 'ea_change_content', 10, 2 );

/**
 * Add #main-content to .site-inner
 *
 */
function ea_main_content_id( $open, $args ) {
	return str_replace ('>', ' id="main-content">', $open );
}
add_filter( 'genesis_markup_site-inner_open', 'ea_main_content_id', 10, 2 );

/**
 * Custom search form
 *
 */
function ea_search_form() {
	ob_start();
	get_template_part( 'searchform' );
	return ob_get_clean();
}
add_filter( 'genesis_search_form', 'ea_search_form' );

/**
 * Post Info
 *
 */
function ea_post_info( $info ) {
	return '[post_date] [post_author_posts_link before="By "]';
}
add_filter( 'genesis_post_info', 'ea_post_info' );

/**
 * Post Meta
 *
 */
function ea_post_meta( $meta ) {
	return '[post_categories before="Posted in "]';
}
add_filter( 'genesis_post_meta', 'ea_post_meta' );

/**
 * Author Box
 *
 */
function ea_author_box( $output ) {

	$id = get_the_author_meta( 'ID' );

	$output = '<section class="author-box">';

		$output .= '<header>';
		$output .= '<a href="' . get_author_posts_url( $id ) . '">' . get_avatar( $id, 80 ) . '</a>';

		if( is_author() ) {
			$output .= '<h1>' . get_the_author() . '</h1>';
		} else {
			$output .= '<p class="label">About the Author</p>';
			$output .= '<h4>' . get_the_author() . '</h4>';
		}

		// @see wp-content/plugins/core-functionality/inc/user-profile.php
		if( function_exists( 'ea_get_user_socials' ) )
			$output .= ea_get_user_socials();
		$output .= '</header>';

		$output .= '<div class="author-box-content">' . wpautop( get_the_author_meta( 'description' ) ) . '</div>';

	$output .= '</section>';
	return $output;
}
add_filter( 'genesis_author_box', 'ea_author_box' );

// Automatically display author box
add_filter( 'get_the_author_genesis_author_box_single', '__return_true' );
add_filter( 'get_the_author_genesis_author_box_archive', '__return_true' );
