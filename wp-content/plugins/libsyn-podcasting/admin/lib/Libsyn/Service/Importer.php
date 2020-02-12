<?php
namespace Libsyn\Service;
/*
	This class is used to import 3rd party podcast feeds into the libsyn network.
	For other 3rd party integrations please see Integration class.
*/
class Importer extends \Libsyn\Service {

    /**
     * Handles creating post metadata for an singleton post
     *
     * @param <int> $api
     * @param <stdClass> $api
     * @param <Libsyn\Api> $api
     *
     * @return <mixed>
     */
	public function createMetadata( $post_id = null, $metaData = null, $api = false ) {
		//sanity check
		if(empty($post_id) && empty($metaData)) return false; //back out

		/* Get the posted data and sanitize it for use as an HTML class. */
		$meta_values = array();

		/* Core Post Meta */
		if(!empty($metaData->item_id)) {
			$meta_values['libsyn-item-id'] = $metaData->item_id;
		} elseif(!empty($metaData->id)) {
			//going to mess up updating post but has to be set.
			$meta_values['libsyn-item-id'] = $metaData->id;
		} else {
			//going to mess up updating post but has to be set.
			$meta_values['libsyn-item-id'] = 0;
		}
		$meta_values['libsyn-post-episode'] = "isLibsynPost";
		$meta_values['libsyn-post-update-release-date'] = "isLibsynUpdateReleaseDate";
		$meta_values['libsyn-is_draft'] = 'false';
		$meta_values['libsyn-release-date'] = (!empty($metaData->release_date)) ? $metaData->release_date : date("Y-m-d H:i:s");
		$meta_values['libsyn-destination-releases'] = (!empty($metaData->releases)) ? $metaData->releases : '';
		$meta_values['libsyn-new-media-media'] = (!empty($metaData->content_id)) ? 'libsyn-upload-'.$metaData->content_id : '';
		$meta_values['libsyn-post-episode-subtitle'] = (!empty($metaData->item_subtitle)) ? $metaData->item_subtitle : '';
		$meta_values['libsyn-post-episode-category-selection'] = (!empty($metaData->item_category)) ? $metaData->item_category : '';
		$meta_values['libsyn-new-media-image'] = (!empty($metaData->thumbnail_url)) ? $metaData->thumbnail_url : '';
		$meta_values['libsyn-post-episode-keywords'] = (!empty($metaData->item_keywords) && is_array($metaData->item_keywords)) ? implode(', ', $metaData->item_keywords) : '';
		$meta_values['libsyn-post-episode-itunes-explicit'] = (!empty($metaData->itunes_explicit)) ? $metaData->itunes_explicit : '';
		$meta_values['libsyn-post-episode-itunes-episode-number'] = (!empty($metaData->itunes_episode_number)) ? $metaData->itunes_episode_number : '';
		$meta_values['libsyn-post-episode-itunes-season-number'] = (!empty($metaData->itunes_season_type)) ? $metaData->itunes_season_number : '';
		$meta_values['libsyn-post-episode-itunes-episode-type'] = (!empty($metaData->itunes_episode_type)) ? $metaData->itunes_episode_type : '';
		$meta_values['libsyn-post-episode-itunes-episode-summary'] = (!empty($metaData->itunes_episode_summary)) ? $metaData->itunes_episode_summary : '';
		$meta_values['libsyn-post-episode-itunes-episode-title'] = (!empty($metaData->itunes_episode_title)) ? $metaData->itunes_episode_title : '';
		$meta_values['libsyn-post-episode-itunes-episode-author'] = (!empty($metaData->itunes_episode_author)) ? $metaData->itunes_episode_author : '';


		/* Player Settings */

		// custom color //
		$playerCustomColor = get_user_option('libsyn-podcasting-player_custom_color');
		if(!empty($playerCustomColor)) {
			$meta_values['libsyn-post-episode-player_custom_color'] =  $playerCustomColor;
		}

		// theme //
		$playerTheme	= get_user_option('libsyn-post-episode-player_use_theme');
		$playerTheme	= (!empty($playerTheme)) ? $playerTheme : "custom";
		$meta_values['libsyn-post-episode-player_use_theme'] = $playerTheme;

		// height //
		$playerHeight	= get_user_option('libsyn-post-episode-player_height');
		$playerHeight	= (!empty($playerHeight)) ? $playerHeight : 90;
		$meta_values['libsyn-post-episode-player_height'] = $playerHeight;

		// width //
		$playerWidth	= get_user_option('libsyn-post-episode-player_width');
		$playerWidth	= (!empty($playerWidth)) ? $playerWidth : 450;
		$meta_values['libsyn-post-episode-player_width'] = $playerWidth;

		// placement //
		$playerPlacement 	= get_user_option('libsyn-post-episode-player_placement');
		$playerPlacement 	= (!empty($playerPlacement) || $playerPlacement==='top') ? $playerPlacement : "bottom";
		$meta_values['libsyn-post-episode-player_placement'] = $playerPlacement;

		// download link //
		$playerUseDownloadLink = get_user_option('libsyn-post-episode-player_use_download_link');
		$playerUseDownloadLink = (!empty($playerUseDownloadLink)) ? $playerUseDownloadLink : false;
		$playerUseDownloadLink = ($playerUseDownloadLink==='use_download_link')?true:false;
		if($playerUseDownloadLink) {
			$meta_values['libsyn-post-episode-player_use_download_link'] = 'use_download_link';
		}

		// download link text //
		$playerUseDownloadLinkText = get_user_option('libsyn-post-episode-player_use_download_link_text');
		$playerUseDownloadLinkText = (!empty($playerUseDownloadLinkText)) ? $playerUseDownloadLinkText : '';
		if(!empty($playerUseDownloadLinkText)) {
			$meta_values['libsyn-post-episode-player_use_download_link_text'] = $playerUseDownloadLinkText;
		}


		/* Additional Meta Values */
		$meta_values['libsyn-post-episode-simple-download'] = 'available';
		$meta_values['libsyn-post-episode-advanced-destination-form-data-input-enabled'] = 'true';
		if(!empty($metaData->show_id)) {
			if(!empty($api) && $api instanceof \Libsyn\Api) {
				try {
					$show = $this->getShow($api, $metaData->show_id)->{'user-shows'};
					if(!empty($show)) {//matched show
						if(!empty($show->{'feed_url'})) {
							$meta_values['libsyn-show-feed_url'] = $show->{'feed_url'};
						}
						if(!empty($show->{'show_title'})) {
							$meta_values['libsyn-show-show_title'] = $show->{'show_title'};
						}
					}
				} catch(Exception $e) {
					//TODO log error
				}

			}
		}

		//Handle new Meta Values
		return self::handleMetaValueArray( $post_id, $meta_values );
	}

    /**
     * Creates a WP Post from the passsed post object
     *
     * @param object $postData
     *
     * @return mixed
     */
	public function createPost ( $postData ) {
		$sanitize = new \Libsyn\Service\Sanitize();
		if(!empty($postData) && is_object($postData)) {
			$postArr = array();
			$postArr['post_author']		= $this->getCurrentUserId();
			$postArr['post_date']		= $postData->release_date;
			$postArr['post_date_gmt']	= (function_exists('get_gmt_from_date')) ? get_gmt_from_date($postData->release_date, "Y-m-d H:i:s") : gmdate("Y-m-d H:i:s", strtotime($postData->release_date));
			$postArr['post_content']	= (!empty($postData->item_body)) ? $postData->item_body : '';
			$postArr['post_content_filtered'] = $sanitize->text($postData->item_body);
			$postArr['post_title']		= (function_exists('wp_strip_all_tags')) ? wp_strip_all_tags($postData->item_title) : $postData->item_title;
			$postArr['post_excerpt']	= (!empty($postData->item_subtitle)) ? $postData->item_subtitle : '';
			$postArr['post_status']		= 'publish';
			$postArr['post_type']		= 'post';
			// $postArr['comment_status']	= get_option('default_comment_status');
			// $postArr['ping_status']		= get_option('default_ping_status');
			// $postArr['post_password']	= '';
			// $postArr['post_name']		= $sanitize->title($postData->item_title);
			// $postArr['to_ping']			= '';
			// $postArr['pinged']			= '';
			$postArr['post_modified']	= date('Y-m-d H:i:s');
			$postArr['post_modified_gmt']	= (function_exists('get_gmt_from_date')) ? get_gmt_from_date(date('Y-m-d H:i:s'), "Y-m-d H:i:s") : gmdate("Y-m-d H:i:s", time());
			// $postArr['post_parent']		= 0;
			// $postArr['menu_order']		= 0;
			// $postArr['post_mime_type']	= '';
			// $postArr['guid']				= '';

			//TODO: Add category based on Libsyn category in future release
			/* Item Category */
			/*
			if ( !empty($postData->item_category) ) {
				if ( empty(get_cat_ID($postData->item_category)) ) {
					$working_category = wp_create_category($postData->item_category, get_option('default_category'));
				} else {
					$working_category = get_cat_ID($postData->item_category);
				}
			} else {
				$working_category = false;
			}
			$postArr['post_category']	= ( !empty($working_category) ) ? array($working_category, get_option('default_category')) : array(get_option('default_category')); //default get_option('default_category')
			*/

			// $postArr['tags_input']			= '';
			// $postArr['tax_input']		= '';
			// $postArr['meta_input']			= array();
		} else {
			return false;
		}
		try {
			$data = (function_exists('wp_insert_post')) ? wp_insert_post(sanitize_post($postArr, 'db'), true) : false;
		} catch (Exception $e) {
			//TODO: Log error
		}
		return (isset($data)) ? $data : false;
	}

    /**
     * Handles clearing post metadata for an singleton post
     *
     * @param <Libsyn\Service> $api
     *
     * @return <type>
     */
	public function clearMetadata( $post_id ) {
		/* Set emtpy meta values*/
		$meta_values = array();
		$meta_values['libsyn-post-episode']							= '';
		$meta_values['libsyn-post-update-release-date'] 			= '';
		$meta_values['libsyn-item-id']								= '';
		$meta_values['libsyn-is_draft']								= '';
		$meta_values['libsyn-release-date']							= '';
		$meta_values['libsyn-destination-releases']					= '';
		$meta_values['libsyn-new-media-media']						= '';
		$meta_values['libsyn-post-episode-subtitle']				= '';
		$meta_values['libsyn-post-episode-category-selection']		= '';
		$meta_values['libsyn-new-media-image']						= '';
		$meta_values['libsyn-post-episode-keywords']				= '';
		$meta_values['libsyn-post-episode-itunes-explicit']			= '';
		$meta_values['libsyn-post-episode-itunes-episode-number']	= '';
		$meta_values['libsyn-post-episode-itunes-season-number']	= '';
		$meta_values['libsyn-post-episode-itunes-episode-type']		= '';
		$meta_values['libsyn-post-episode-itunes-episode-summary'] 	= '';
		$meta_values['libsyn-post-episode-itunes-episode-title']	= '';
		$meta_values['libsyn-post-episode-itunes-episode-author']	= '';
		$meta_values['libsyn-post-episode-player_custom_color']		= '';
		$meta_values['libsyn-post-episode-player_use_theme']		= '';
		$meta_values['libsyn-post-episode-player_height']			= '';
		$meta_values['libsyn-post-episode-player_width']			= '';
		$meta_values['libsyn-post-episode-player_placement']		= '';
		$meta_values['libsyn-post-episode-player_use_download_link']		= '';
		$meta_values['libsyn-post-episode-player_use_download_link_text']	= '';
		$meta_values['libsyn-show-feed_url']						= '';
		$meta_value['libsyn-show-show_title']						= '';

		//Handle new Meta Values (if they are set they will be cleared)
		self::handleMetaValueArray( $post_id, $meta_values );
	}

	/**
	 * Handle meta values based on the way they are setup in array.
	 * see (array) $meta_values
	 *
	 * @param <array> $meta_values
	 *
	 * @return <mixed>
	 */
	public static function handleMetaValueArray( $post_id, $meta_values ) {
		/* If a new meta value was added and there was no previous value, add it. */
		foreach ($meta_values as $key => $val) {
			$meta_value = get_post_meta($post_id, $key, true);
			$sanitize = new \Libsyn\Service\Sanitize();
			if(!isset($url)) $url = '';

			//sanitize value
			if($key === 'libsyn-destination-releases') {
				$clean_val = $val;
			} else {
				switch($key) {
					case 'libsyn-item-id':
						$clean_val = $sanitize->numeric($val);
						break;
					case 'libsyn-new-media-image':
						$clean_val = $sanitize->url_raw($val);
						break;
					case 'libsyn-new-media-media':
					case 'libsyn-show-feed_url':
						if( strpos($val, 'libsyn-ftp-')===false || strpos($url, 'libsyn-upload-')===false) {
							 $clean_val = $sanitize->url_raw($val);
						} else {
							$clean_val = $sanitize->text($val);
						}
						break;
					case 'libsyn-post-episode-advanced-destination-form-data':
						$clean_val = $sanitize->json($val);
						break;
					case 'libsyn-release-date':
						$clean_val = $sanitize->mysqlDate($val);
						break;
					default:
						$clean_val = $sanitize->text($val);

				}
			}
			if (!empty($clean_val) && empty($meta_value)) // no meta_value so create
				add_post_meta($post_id, $key, $clean_val, true);
			elseif (!empty($clean_val) && $clean_val!==$meta_value) //doesn't match old value so update
				update_post_meta($post_id, $key, $clean_val);
			elseif (empty($clean_val) && !empty($meta_value)) //old value doesn't exist, delete it
				delete_post_meta($post_id, $key, $meta_value);
		}
	}

    /**
     * Clears a podcast shortcode from an individual post
     *
     * @param int $post_id
     *
     * @return bool
     */
	public function clearPlayer($post_id) {
		global $wpdb;
		$post = get_post($post_id);
		if(empty($post)) return false; //cannot find post.. back out

		try {
			$wpdb->update(
				$wpdb->prefix . 'posts',
				array(
					'post_content' => wp_kses_post(self::stripShortcode('podcast', $post->post_content)),
					'post_modified' => date("Y-m-d H:i:s"),
					'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
				),
				array('ID' => $post->ID)
			);
		} catch (Exception $e) {
			//TODO: LOG ERROR
		}
		return true;
	}

    /**
     * Adds the player to an individual post
     *
     * @param int $post_id
     * @param string $url
     *
     * @return bool
     */
	public function addPlayer($post_id, $url) {
		global $wpdb;
		$post = get_post($post_id);
		if(empty($post)) return false; //cannot find post.. back out

		//Set Player Settings and/or Defualts
		$playerTheme	= get_user_option('libsyn-post-episode-player_use_theme');
		$playerTheme	= (!empty($playerTheme)) ? $playerTheme : "custom";
		$playerHeight	= get_user_option('libsyn-post-episode-player_height');
		$playerHeight	= (!empty($playerHeight)) ? $playerHeight : 90;
		// $playerWidth	= get_user_option('libsyn-post-episode-player_width');
		// $playerWidth	= (!empty($playerWidth) || $playerTheme == "custom") ? $playerWidth : "100%";
		$playerCustomColor	= get_user_option('libsyn-podcasting-player_custom_color');
		$playerWidth		= "100%"; //player width always 100% now
		$playerPlacement 	= get_user_option('libsyn-post-episode-player_placement');
		$playerPlacement 	= (!empty($playerPlacement) || $playerPlacement==='top') ? $playerPlacement : "bottom";
		$playerUseDownloadLink = get_user_option('libsyn-post-episode-player_use_download_link');
		$playerUseDownloadLink = (!empty($playerUseDownloadLink)) ? $playerUseDownloadLink : false;
		$playerUseDownloadLink = ($playerUseDownloadLink==='use_download_link')?true:false;
		$playerUseDownloadLinkText = get_user_option('libsyn-post-episode-player_use_download_link_text');
		$playerUseDownloadLinkText = (!empty($playerUseDownloadLinkText)) ? $playerUseDownloadLinkText : "Click here to download the episode!";

		//Modify Player url based on saved settings
		switch($playerTheme) {
			case "custom":
				$url = str_replace('/theme/standard', '/theme/'.$playerTheme, $url);
				$url = str_replace('/theme/standard-mini', '/theme/'.$playerTheme, $url);
				$url = str_replace('/theme/legacy', '/theme/'.$playerTheme, $url);
				if(!empty($playerCustomColor)) {
					$url = (substr($url, -1) === '/') ? $url.'custom-color/'.$playerCustomColor : $url.'/custom-color/'.$playerCustomColor;
				}
				break;
			case "standard":
				$url = str_replace('/theme/custom', '/theme/'.$playerTheme, $url);
				$url = str_replace('/theme/standard-mini', '/theme/'.$playerTheme, $url);
				$url = str_replace('/theme/legacy', '/theme/'.$playerTheme, $url);
				break;
			case "standard-mini":
				$url = str_replace('/theme/standard', '/theme/'.$playerTheme, $url);
				$url = str_replace('/theme/custom', '/theme/'.$playerTheme, $url);
				$url = str_replace('/theme/legacy', '/theme/'.$playerTheme, $url);
				break;
			case "legacy"://legacy not supported by plugin but add for good measure
				$url = str_replace('/theme/standard', '/theme/'.$playerTheme, $url);
				$url = str_replace('/theme/standard-mini', '/theme/'.$playerTheme, $url);
				$url = str_replace('/theme/custom', '/theme/'.$playerTheme, $url);
				break;
		}

		if(!empty($playerHeight)) {//change original height parameter name and add height parameter
			$url = str_replace('/height', '/height-orig', $url);
			$url = (substr($url, -1) === '/') ? $url.'height/'.$playerHeight :  $url.'/height/'.$playerHeight;
		}


		if(($playerUseDownloadLink)  && !empty($playerUseDownloadLinkText)) {
			$download_link = '<br /><br /><a class="libsyn-download-link" href ="'.$url.'" target="_blank">'.$playerUseDownloadLinkText.'</a><br />';
		} else $download_link = '';
		try {

			$wpdb->update(
				$wpdb->prefix . 'posts',
				array(
					'post_content' => wp_kses_post(self::stripShortcode('podcast', $post->post_content)).'[podcast src="'.$url.'" height="'.$playerHeight.'" width="'.$playerWidth.'" placement="'.$playerPlacement.'" theme="'.$playerTheme.'"]'.$download_link,
					'post_modified' => date("Y-m-d H:i:s"),
					'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
				),
				array('ID' => $post->ID)
			);
		} catch (Exception $e) {
			//TODO: LOG ERROR
		}
		return true;
	}

	/**
	 * Temp change global state of WP to fool shortcode
	 *
	 * @param <string> $code name of the shortcode
	 * @param <string> $content
	 *
	 * @return <string> content with shortcode striped
	 */
	public static function stripShortcode( $code, $content ) {
		global $shortcode_tags;

		$stack = $shortcode_tags;
		if($code=="all") $shortcode_tags = array();
			else $shortcode_tags = array($code => 1);

		$content = strip_shortcodes($content);

		$shortcode_tags = $stack;
		return $content;
	}


}

?>
