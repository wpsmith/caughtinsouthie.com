<?php
namespace Libsyn\Service;

/*
	This class handles all the post page
	and page related hooks

*/
class Page extends \Libsyn\Service {

	public static function addMeta() {

		/* Google Podcast Feed */
		$addPodcastMetadata = get_option('libsyn-podcasting-settings_add_podcast_metadata');
		//
		$plugin = new \Libsyn\Service();
		$showDefault = true;
		if($plugin instanceof \Libsyn\Service) {
				$showDefault = false;
				//get_post_type() returns post, page, or custom post type
				if ( function_exists('is_front_page') && function_exists('is_single') && !is_front_page() && is_single()) {//blog post page
					if(function_exists('get_the_id')) {
						$postId = get_the_id();
						if(!empty($postId)) {
							$postMeta = get_post_meta($postId);
							if(!empty($postMeta) && is_array($postMeta)) {
								//build new postMeta array with only Libsyn data
								$libsynMeta = array();
								foreach($postMeta as $key => $val) {
									if ( strpos($key, 'libsyn') !== false ) {
										if(is_array($val)) {
											if(count($val) === 1) {
												$libsynMeta[$key] = array_shift($val);
											} else {
												$libsynMeta[$key] = $val;
											}
										} else {
											$libsynMeta[$key] = $val;
										}
									}
								}
								if ( !empty($libsynMeta['libsyn-show-show_title']) && !empty($libsynMeta['libsyn-show-feed_url']) ) {
									//Default meta
									$showDefault = false;
									if($addPodcastMetadata === 'add_podcast_metadata') {//Add Metadata Active
										?><link rel="alternate" type="application/rss+xml" title="<?php echo __($libsynMeta['libsyn-show-show_title'], $plugin->text_dom); ?>" href="<?php echo $libsynMeta['libsyn-show-feed_url']; ?>" /><?php
									}
								}
							}
						}
					}
				} elseif(function_exists('is_page') && is_page()) {//Blog page
					//TODO: Handle getting page meta if applicable
				} else {//everything else (Homepage, search results, Admin)
					//do stuff
					//NOTE: get_post_type() will return false here (possible fallout if can be a post type of some sort?)
					// if(function_exists('is_front_page') && is_front_page()) {
						$showDefault = true;
					// }
				}

			//check API and display default meta if applicable
			$api = $plugin->getApi();
			if(!empty($api) && $api instanceof \Libsyn\Api) {//Api Found
				if($showDefault) {//NOTE: This may not be accurate since it will only chose the currently active show in the plugin
					//Default meta
					if($addPodcastMetadata === 'add_podcast_metadata') {//Add Metadata Active
						?><link rel="alternate" type="application/rss+xml" title="<?php echo __($api->getShowTitle(), $plugin->text_dom); ?>" href="<?php echo $api->getFeedUrl(); ?>" /><?php
					}
				}
			}

			if( !empty($libsynMeta['libsyn-episode-shortcode']) && function_exists('do_shortcode') ) {
				$jsOutput = '';
				$jsOutput .= 'document.addEventListener("DOMContentLoaded", function(event) {
					if ( typeof document.getElementsByClassName("libsyn-shortcode") !== "undefined" && document.getElementsByClassName("libsyn-shortcode").length > 0) {
						let libsyn_shortcode_elements = document.getElementsByClassName("libsyn-shortcode");
						while (libsyn_shortcode_elements[0].hasChildNodes()) {
							libsyn_shortcode_elements[0].removeChild(libsyn_shortcode_elements[0].lastChild);
						}
						libsyn_shortcode_elements[0].innerHTML = \'' . do_shortcode($libsynMeta['libsyn-episode-shortcode']) . '\';
					}

				});';
				?><script type="text/javascript">
					<?php echo $jsOutput; ?>
				</script><?php
			}

			//TODO: add more meta here

		}

	}

}
