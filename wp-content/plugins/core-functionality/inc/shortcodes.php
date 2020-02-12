<?php
/**
 * Shortcodes
 *
 * @package      CoreFunctionality
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Social Links
 * Uses Social URLs specified in Yoast SEO. See SEO > Social
 *
 */
function ea_social_links_shortcode( $atts = array() ) {

	$atts = shortcode_atts( array(
		'wrapper' => true,
	), $atts, 'social_links' );

	$options = array(
		'facebook' => array(
			'key' => 'facebook_site',
		),
		'twitter' => array(
			'key' => 'twitter_site',
			'prepend' => 'https://twitter.com/',
		),
		'instagram' => array(
			'key' => 'instagram_url',
		),
/*
		'linkedin' => array(
			'key' => 'linkedin_url',
		),
		'myspace' => array(
			'key' => 'myspace_url',
		),
		'pinterest' => array(
			'key' => 'pinterest_url',
		),
		'youtube' => array(
			'key' => 'youtube_url',
		),
		'googleplus' => array(
			'key' => 'google_plus_url',
		)
*/
	);

	$output = array();

	$seo_data = get_option( 'wpseo_social' );
	foreach( $options as $social => $settings ) {

		$url = !empty( $seo_data[ $settings['key'] ] ) ? $seo_data[ $settings['key'] ] : false;
		if( !empty( $url ) && !empty( $settings['prepend'] ) )
			$url = $settings['prepend'] . $url;

		$icon = ea_cf_icon( $social );
		if( $url && $icon )
			$output[] = '<a href="' . esc_url_raw( $url ) . '" target="_blank" alt="' . $social . '">' . $icon . '<span class="screen-reader-text">' . $social . '</span></a>';
	}

	if( empty( $output ) )
		return;

	$output = join( ' ', $output );

	if( $atts['wrapper'] )
		$output = '<p class="social-links">' . $output . '</p>';

	return $output;
}
add_shortcode( 'social_links', 'ea_social_links_shortcode' );

/**
 * Newsletter Signup
 *
 */
function ea_newsletter_signup() {
	if( ! function_exists( 'wpforms_display' ) )
		return;

	wpforms_display( ea_newsletter_form_id(), $display_title = true, $display_description = true );
}
