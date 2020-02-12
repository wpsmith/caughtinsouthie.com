<?php
/**
 * Single Property
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Property Details
 *
 */
function ea_single_property_details() {

	$details = array(
		'Address' => ea_cf( 'ea_property_address' ),
		'Listing Price' => ea_cf( 'ea_property_listing_price' ),
		'Bedrooms/Bathrooms' => ea_cf( 'ea_property_beds_baths' ),
		'Square Footage' => ea_cf( 'ea_property_sqft' )
	);

	echo '<div class="property-details">';
	echo '<h4>Property Details</h4>';
	foreach( $details as $label => $value ) {
		if( !empty( $value ) )
			echo '<p><label>' . $label . '</label> '.  $value . '</p>';
	}

	$map = ea_cf( 'ea_property_map' );
	if( !empty( $map ) )
		echo $map;
	echo '</div>';
}
add_action( 'genesis_entry_content', 'ea_single_property_details', 9 );

/**
 * Property Form
 *
 */
function ea_single_property_form() {

	// Listing Agent
	$agents = get_the_terms( get_the_ID(), 'agent' );
	if( !empty( $agents ) && ! is_wp_error( $agents ) ) {

		foreach( $agents as $agent ) {
			ea_agent_details( $agent );
		}
	}

	if( ! function_exists( 'wpforms_display' ) )
		return;

	echo '<div class="property-details">';
	echo '<h3><label>Learn More About</label> ' . ea_cf( 'ea_property_address' ) . '</h3>';
	echo '</div>';

	wpforms_display( ea_property_form_id() );
}
add_action( 'genesis_entry_content', 'ea_single_property_form', 12 );


// Build page using single template
require get_stylesheet_directory() . '/single.php';
