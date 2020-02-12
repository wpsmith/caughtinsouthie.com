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
function ea_register_custom_fields() {

	Container::make( 'post_meta', 'Property Details' )
		->where( 'post_type', '=', 'property' )
		->add_fields( array(
			Field::make( 'text', 'ea_property_address', 'Address' ),
			Field::make( 'text', 'ea_property_listing_price', 'Listing Price' ),
			Field::make( 'text', 'ea_property_beds_baths', 'Bedrooms/Bathrooms' ),
			Field::make( 'text', 'ea_property_sqft', 'Square Footage' ),
			Field::make( 'textarea', 'ea_property_map', 'Google Maps embed code' ),
			Field::make( 'media_gallery', 'ea_property_gallery', 'Gallery' )
				->set_type( array( 'image' ) )
				->set_duplicates_allowed( false )
		));

	Container::make( 'term_meta', 'Agent Details' )
		->where( 'term_taxonomy', '=', 'agent' )
		->add_fields( array(
			Field::make( 'image', 'ea_agent_photo', 'Photo' ),
			Field::make( 'text', 'ea_company_line_1', 'Company Line 1' ),
			Field::make( 'text', 'ea_company_line_2', 'Company Line 2' )
		));

	Container::make( 'post_meta', 'Featured Video' )
		->where( 'post_type', '=', 'post' )
		->set_context( 'side' )
		->set_priority( 'low' )
		->add_fields( array(
			Field::make( 'text', 'ea_featured_video', 'Video URL' )
		));
}
add_action( 'carbon_fields_register_fields', 'ea_register_custom_fields' );
