<?php
/**
 * Comments
 *
 * @package      CaughtInSouthie2018
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

// Remove WP comment form
remove_action( 'genesis_comment_form', 'genesis_do_comment_form' );

/**
 * Facebook SDK
 *
 */
function ea_facebook_sdk() {
	?>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.1&appId=233347487372730';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
	<?php
}
add_action( 'wp_footer', 'ea_facebook_sdk' );

/**
 * Facebook Comments
 *
 */
function ea_facebook_comments() {

	if( ! is_single() )
		return;

	$url = function_exists( 'ea_production_url' ) ? ea_production_url( get_permalink() ) : get_permalink();
	echo '<div class="fb-comments" data-href="' . $url . '" data-numposts="5" data-width="100%"></div>';
}
add_action( 'genesis_after_entry', 'ea_facebook_comments', 9 );
