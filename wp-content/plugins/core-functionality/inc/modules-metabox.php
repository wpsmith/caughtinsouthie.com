<?php
/**
 * Custom Fields
 *
 * @package      CoreFunctionality
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Register Fields
 *
 */
function ea_register_modules_metabox() {

	Container::make( 'post_meta', 'Modules' )
		->where( 'post_type', '=', 'page' )
		->where( 'post_template', '=', 'templates/modules.php' )
		->add_fields( array(
			Field::make( 'complex', 'ea_modules', 'Modules' )
				->setup_labels( array( 'singular_name' => 'Module', 'plural_name' => 'Modules' ) )
				->set_collapsed( true )
				->set_layout( 'tabbed-vertical' )

				->add_fields( 'content', 'Content', array(
					Field::make( 'rich_text', 'content' )
				))

				->add_fields( 'featured_rotator', 'Featured Rotator', array(
					Field::make( 'select', 'featured_area', 'Featured Area' )
						->set_options( 'ea_metabox_featured_area' ),
					Field::make( 'select', 'category', 'Limit to Category' )
						->set_options( 'ea_metabox_category' )
				))

				->add_fields( 'featured_pages', 'Featured Pages', array(
					Field::make( 'text', 'featured_pages', 'Page IDs' )
						->set_help_text( 'Comma separated list' )
				))

				->add_fields( 'post_listing', 'Post Listing', array(
					Field::make( 'text', 'title_black', 'Title (Black)' )->set_width(33),
					Field::make( 'text', 'title_grey', 'Title (Grey)' )->set_width(33),
					Field::make( 'text', 'title_green', 'Title (Green)' )->set_width(33),
					Field::make( 'select', 'post_type', 'Content Type' )
						->set_options( 'ea_metabox_post_type' ),
					Field::make( 'select', 'category', 'Limit to Category' )
						->set_options( 'ea_metabox_category' )
						->set_conditional_logic( array(
							array(
								'field' => 'post_type',
								'value' => 'post'
							)
						))
				))

				->add_fields( 'ad_trending_content', 'Ad & Trending Content', array(
					Field::make( 'textarea', 'ad' ),
					Field::make( 'text', 'title' )
				))

				->add_fields( 'ad_content', 'Ad & Content', array(
					Field::make( 'textarea', 'ad' ),
					Field::make( 'text', 'title' ),
					Field::make( 'rich_text', 'content' )
				))

				->add_fields( 'ad_weather', 'Ad & Weather', array(
					Field::make( 'textarea', 'ad' ),
				))

				->add_fields( 'ad_post_listing', 'Ad & Post Listing', array(
					Field::make( 'textarea', 'ad' ),
					Field::make( 'text', 'title' ),
					Field::make( 'select', 'post_type', 'Content Type' )
						->set_options( 'ea_metabox_post_type' ),
					Field::make( 'select', 'category', 'Limit to Category' )
						->set_options( 'ea_metabox_category' )
						->set_conditional_logic( array(
							array(
								'field' => 'post_type',
								'value' => 'post'
							)
						)),
					Field::make( 'checkbox', 'offset', 'Offset by 3' )

				))
		));
}
add_action( 'carbon_fields_register_fields', 'ea_register_modules_metabox' );
