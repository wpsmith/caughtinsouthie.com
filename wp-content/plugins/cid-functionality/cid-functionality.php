<?php
/**
 * Plugin Name: Caught in Dot Functionality
 * Description: This plugin customizes the Caught in Southie theme for Caught in Dot
 * Version:     1.0.0
 * Author:      Bill Erickson
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package    CiDFunctionality
 * @since      1.0.0
 * @copyright  Copyright (c) 2014, Bill Erickson
 * @license    GPL-2.0+
 */

// Plugin directory
define( 'CID_DIR' , plugin_dir_path( __FILE__ ) );

// Change Login Logo
add_filter( 'ea_login_logo', function( $url ) {
	return plugins_url( 'assets/images/logo-tagline.svg', __FILE__ );
});

// Newsletter Form ID
add_filter( 'ea_newsletter_form_id', function() { return 16758; } );

/**
 * Caught in Dot Stylesheet
 *
 */
function cid_scripts() {
	wp_enqueue_style( 'cid', plugins_url( 'assets/css/cid.css', __FILE__ ), array( 'ea-style' ), filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/cid.css' ) );
}
add_action( 'wp_enqueue_scripts', 'cid_scripts' );
