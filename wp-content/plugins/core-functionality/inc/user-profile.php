<?php
/**
 * User Profile
 *
 * @package      CoreFunctionality
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Social Services
 *
 */
function ea_user_profile_social_services( $contactmethods ) {

    unset($contactmethods['googleplus']);

    $contactmethods['twitter'] = 'Twitter';
    $contactmethods['facebook'] = 'Facebook';
    $contactmethods['linkedin'] = 'LinkedIn';
    $contactmethods['instagram'] = 'Instagram';

	return $contactmethods;
}

add_filter( 'user_contactmethods','ea_user_profile_social_services', 10, 1 );

/**
 * Get User Socials
 *
 */
function ea_get_user_socials( $user_id = false ) {
	$user_id = $user_id ? intval( $user_id ) : false;

	$output = array();
	$socials = array( 'facebook', 'twitter', 'linkedin', 'instagram' );

	foreach( $socials as $social ) {
		$url = get_the_author_meta( $social, $user_id );
		if( 'twitter' == $social && !empty( $url ) )
			$url = 'https://twitter.com/' . $url;

		if( !empty( $url ) )
			$output[] = '<a href="' . esc_url_raw( $url ) . '">' . ea_cf_icon( $social ) . '</a>';
	}

	if( !empty( $output ) )
		return '<p class="social-links">' . join( ' ', $output ) . '</p>';
}
