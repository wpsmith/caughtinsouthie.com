<?php
/**
 * Properties
 *
 * @package      CoreFunctionality
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

class EA_Properties {

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
			'name'               => 'Properties',
			'singular_name'      => 'Property',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Property',
			'edit_item'          => 'Edit Property',
			'new_item'           => 'New Property',
			'view_item'          => 'View Property',
			'search_items'       => 'Search Properties',
			'not_found'          => 'No Properties found',
			'not_found_in_trash' => 'No Properties found in Trash',
			'parent_item_colon'  => 'Parent Property:',
			'menu_name'          => 'Properties',
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'author', 'excerpt', 'revisions', 'genesis-cpt-archives-settings' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => array( 'slug' => 'properties', 'with_front' => false ),
			'menu_icon'           => 'dashicons-admin-home', // https://developer.wordpress.org/resource/dashicons/
		);

		register_post_type( 'property', $args );

	}

	/**
	 * Register the taxonomies
	 *
	 * @since 1.2.0
	 */
	function register_tax() {
		$labels = array(
			'name'                       => 'Listing Agent',
			'singular_name'              => 'Listing Agent',
			'search_items'               => 'Search Listing Agents',
			'popular_items'              => 'Popular Listing Agents',
			'all_items'                  => 'All Listing Agents',
			'parent_item'                => 'Parent Listing Agent',
			'parent_item_colon'          => 'Parent Listing Agent:',
			'edit_item'                  => 'Edit Listing Agent',
			'update_item'                => 'Update Listing Agent',
			'add_new_item'               => 'Add New Listing Agent',
			'new_item_name'              => 'New Listing Agent',
			'separate_items_with_commas' => 'Separate Listing Agents with commas',
			'add_or_remove_items'        => 'Add or remove Listing Agents',
			'choose_from_most_used'      => 'Choose from most used Listing Agents',
			'menu_name'                  => 'Listing Agents',
		);
		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_ui'           => true,
			'show_tagcloud'     => false,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'listing-agent', 'with_front' => false ),
			'query_var'         => true,
			'show_admin_column' => true,
			// 'meta_box_cb'    => false,
		);
		register_taxonomy( 'agent', array( 'property' ), $args );
	}
}
new EA_Properties();
