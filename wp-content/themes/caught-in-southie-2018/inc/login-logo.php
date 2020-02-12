<?php
/**
 * Login Logo
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

/**
 * Login Logo URL
 *
 */
function ea_login_header_url( $url ) {
    return esc_url( home_url() );
}
add_filter( 'login_headerurl', 'ea_login_header_url' );
add_filter( 'login_headertitle', '__return_empty_string' );

/**
 * Login Logo
 *
 */
function ea_login_logo() {
	$logo = apply_filters( 'ea_login_logo', get_stylesheet_directory_uri() . '/assets/images/logo-tagline.svg' );
    ?>
    <style type="text/css">
    .login h1 a {
        background-image: url(<?php echo $logo;?>);
        background-size: contain;
        background-repeat: no-repeat;
		background-position: center;
        display: block;
        overflow: hidden;
        text-indent: -9999em;
        width: 312px;
        height: 62px;
    }
    </style>
    <?php
}
add_action( 'login_head', 'ea_login_logo' );
