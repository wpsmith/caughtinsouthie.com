<?php
/**
 * Business Listings
 *
 * @package      CoreFunctionality
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

class EA_Business_Listings {

	/**
	 * Initialize all the things
	 *
	 * @since 1.2.0
	 */
	function __construct() {

		// Actions
		add_action( 'init',              array( $this, 'register_cpt'      )    );
		add_action( 'init',              array( $this, 'register_tax'      )    );
	}

	/**
	 * Register the custom post type
	 *
	 * @since 1.2.0
	 */
	function register_cpt() {

		$labels = array(
			'name'               => 'Businesses',
			'singular_name'      => 'Business',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Business',
			'edit_item'          => 'Edit Business',
			'new_item'           => 'New Business',
			'view_item'          => 'View Business',
			'search_items'       => 'Search Businesses',
			'not_found'          => 'No Businesses found',
			'not_found_in_trash' => 'No Businesses found in Trash',
			'parent_item_colon'  => 'Parent Business:',
			'menu_name'          => 'Businesses',
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'genesis-cpt-archives-settings' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => array( 'slug' => 'businesses', 'with_front' => false ),
			'menu_icon'           => 'dashicons-index-card', // https://developer.wordpress.org/resource/dashicons/
		);

		register_post_type( 'business', $args );

	}

	/**
	 * Register the taxonomies
	 *
	 * @since 1.2.0
	 */
	function register_tax() {
		$labels = array(
			'name'                       => 'Business Type',
			'singular_name'              => 'Business Type',
			'search_items'               => 'Search Business Types',
			'popular_items'              => 'Popular Business Types',
			'all_items'                  => 'All Business Types',
			'parent_item'                => 'Parent Business Type',
			'parent_item_colon'          => 'Parent Business Type:',
			'edit_item'                  => 'Edit Business Type',
			'update_item'                => 'Update Business Type',
			'add_new_item'               => 'Add New Business Type',
			'new_item_name'              => 'New Business Type',
			'separate_items_with_commas' => 'Separate Business Types with commas',
			'add_or_remove_items'        => 'Add or remove Business Types',
			'choose_from_most_used'      => 'Choose from most used Business Types',
			'menu_name'                  => 'Business Types',
		);
		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_ui'           => true,
			'show_tagcloud'     => false,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'business-type', 'with_front' => false ),
			'query_var'         => true,
			'show_admin_column' => true,
			// 'meta_box_cb'    => false,
		);
		register_taxonomy( 'business_type', array( 'business' ), $args );
	}
}
new EA_Business_Listings();
