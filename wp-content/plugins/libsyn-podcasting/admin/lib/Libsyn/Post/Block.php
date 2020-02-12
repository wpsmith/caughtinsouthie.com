<?php
namespace Libsyn\Post;

class Block extends \Libsyn\Post {

    /**
     * Calls init for block posts
	 *
	 * @since 1.2.1
     *
     * @return void
     */
	public static function initBlock() {
		$plugin = new \Libsyn\Service();
		$current_user_id = $plugin->getCurrentUserId();
		$api = $plugin->retrieveApiById($current_user_id, true);
		$hasApi = ( !empty($api) && $api instanceof \Libsyn\Api );
		if ( !$hasApi ) {//revert to backup for compatibility
			$api = $plugin->getApi();
			$hasApi = ( !empty($api) && $api instanceof \Libsyn\Api );
		}

		if ( $hasApi !== false ) {
			$user_id = $api->getUserId();
			if(!empty($user_id)) {
				register_block_type('libsyn-podcasting-gutenberg/block', array(
						'editor_script' => 'cgb/block-libsyn-podcasting-gutenberg',
						'render_callback' => '\Libsyn\Post\Block::shortcodeCallback',
						'attributes' => array(
							'currentPostId' => array(
								'type'		=> 'integer',
								'source'	=> 'html',
								'default'	=> 0
							),
							'libsynItemId' => array(
								'type'		=> 'integer',
								'source'	=> 'html',
								'default'	=> 0
							),
							'libsynEpisodeShortcode' => array(
								'type'		=> 'string',
								'source'	=> 'html',
								'default'	=> ''
							),
							'libsynEpisodeEmbedurl' => array(
								'type'		=> 'string',
								'source'	=> 'html',
								'default' => ''
							)
						)
				));
			}
		} else {
			if($plugin->hasLogger) $plugin->logger->error("Plugin:\t Libsyn User is not set");
		}
	}

	public static function blockAssets() {
		// Styles.
		wp_enqueue_style(
			'libsyn_podcasting_gutenberg-cgb-style-css', // Handle.
			plugins_url( LIBSYN_DIR . '/lib/libsyn-podcasting-gutenberg/dist/blocks.style.build.css' ), // Block style CSS.
			array( 'wp-blocks' ) // Dependency to include the CSS after it.
			// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.style.build.css' ) // Version: filemtime — Gets file modification time.
		);
		wp_enqueue_style(
			'react-widgets',
			plugins_url( LIBSYN_DIR . '/lib/libsyn-podcasting-gutenberg/node_modules/react_widgets/dist/css/react-widgets.css' ),
			array('wp-element')
		);
	}

	public static function editorAssets() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.3', '>=' ) ) {
			// WordPress version is greater than 5.3
			$build_type = '';
		} else {
			$build_type = '.deprecated';
		}

		// Scripts.
		wp_enqueue_script(
			'libsyn_podcasting_gutenberg-cgb-block-js', // Handle.
			plugins_url( LIBSYN_DIR . '/lib/libsyn-podcasting-gutenberg/dist/blocks.build' . $build_type . '.js' ), // Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-editor', 'wp-components', 'wp-element' ) // Dependencies, defined above.
			// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
		);

		// Styles.
		wp_enqueue_style(
			'libsyn_podcasting_gutenberg-cgb-block-editor-css', // Handle.
			plugins_url( LIBSYN_DIR . '/lib/libsyn-podcasting-gutenberg/dist/blocks.editor.build' . $build_type . '.css' ), // Block editor CSS.
			array( 'wp-edit-blocks' ) // Dependency to include the CSS after it.
			// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) // Version: filemtime — Gets file modification time.
		);
	}

	public static function shortcodeCallback( $attributes ) {
		$atts = array();
		$current_post_id = 0;
		if ( !empty($attributes['currentPostId']) ) {
			$current_post_id = $attributes['currentPostId'];
		} else {
			global $post;
			if (!empty($post->ID)) {
				$current_post_id = $post->ID;
			} elseif ( !empty($_REQUEST['post']) ) {
				$current_post_id = $_REQUEST['post'];
			}
		}

		//build player shortcode atts
		$atts['src'] = ( !empty($attributes['libsynEpisodeEmbedurl']) ) ? $attributes['libsynEpisodeEmbedurl'] : get_post_meta($current_post_id, 'libsyn-episode-embedurl', true);
		$heightMeta = get_post_meta($current_post_id, 'libsyn-post-episode-player_height', true);
		$atts['height'] = (!empty($heightMeta)) ? $heightMeta : '200';
		$themeMeta = get_post_meta($current_post_id, 'libsyn-post-episode-player_use_theme', true);
		$atts['theme'] = ( !empty($themeMeta) ) ? $themeMeta : 'custom';
		$placementMeta = get_post_meta($current_post_id, 'libsyn-post-episode-player_placement', true);
		$atts['placement'] = ( !empty($placementMeta) ) ? $placementMeta : 'bottom';
		$customColorMeta = get_post_meta($current_post_id, 'libsyn-post-episode-player_custom_color', true);
		$atts['custom_color'] = ( !empty($customColorMeta) ) ? $customColorMeta : '';
		$downloadLinkMeta = get_post_meta($current_post_id, 'libsyn-post-episode-player_use_download_link', true);
		$atts['use_download_link'] = ( !empty($downloadLinkMeta) && ( $downloadLinkMeta === "use_download_link" ) ) ? true : false;
		$downloadLinkTextMeta = get_post_meta($current_post_id, 'libsyn-post-episode-player_use_download_link_text', true);
		$atts['download_link_text'] = ( !empty($downloadLinkTextMeta) ) ? $downloadLinkTextMeta : '';
		$primaryContentUrlMeta = get_post_meta($current_post_id, 'libsyn-post-episode-primary_content_url', true);
		$atts['primary_content_url'] = ( !empty($primaryContentUrlMeta) ) ? $primaryContentUrlMeta : '';
		$atts['libsyn-item-id'] = ( !empty($attributes['libsynItemId']) ) ? $attributes['libsynItemId'] : get_post_meta($current_post_id, 'libsyn-item-id', true);

		//return shortcode rendered
		return libsyn_unqprfx_embed_shortcode($atts);
	}

    /**
     * Adds Block Editor Scripts
     *
	 * @since 1.2.1
     *
     * @return void
     */
	public static function addAssets() {
		//Build Editor Assets
		self::editorAssets();
	}

	public static function addBlockServerRender() {
		//TODO: Make this work with block editor as it needs to check for the edited post data
	?>
	<?php if ( isset($_GET['libsyn_edit_post_id']) && !empty($_GET['libsyn_edit_post_id']) ) { ?>
		/* Handle Edit Item */
		<?php
		//check post duplicate

		$duplicateEditPost = $plugin->checkEditPostDuplicate($sanitize->itemId($_GET['libsyn_edit_post_id']));
		if ( $duplicateEditPost ) { ?>
		<?php echo "<script type=\"text/javascript\">"; ?>
		jQuery(document).ready(function($){
			var post_redirect_url = '<?php echo $plugin->admin_url('post.php').'?post='.$duplicateEditPost->post_id.'&action=edit'; ?>';
			if (typeof window.top.location.href == 'string') window.top.location.href = post_redirect_url;
				else if(typeof document.location.href == 'string') document.location.href = post_redirect_url;
					else if(typeof window.location.href == 'string') window.location.href = post_redirect_url;
						else alert('Unknown javascript error 1025.  Please report this error to support@libsyn.com and help us improve this plugin!');
			<?php } ?>
			var libsyn_edit_post_id = parseInt(<?php if( !empty($_GET['libsyn_edit_post_id']) ) { echo $sanitize->itemId($_GET['libsyn_edit_post_id']); } ?>);
			<?php
				$temp_show_id = (isset($api) && $api instanceof \Libsyn\Api) ? $api->getShowId() : null;
				$item = $plugin->getEpisode(array('show_id'=>$temp_show_id,'item_id' => $sanitize->itemId($_GET['libsyn_edit_post_id'])));
				update_post_meta($object->ID, 'libsyn-edit-item-id', $sanitize->itemId($_GET['libsyn_edit_post_id']));
				if ( !empty($item->_embedded->post->release_date) ) {
					$libsyn_release_date = $sanitize->mysqlDate($item->_embedded->post->release_date);
					update_post_meta($object->ID, 'libsyn-release-date', $sanitize->mysqlDate($item->_embedded->post->release_date));
				}
			?>
		});
		<?php echo "</script>"; ?>
		<?php
		}
	}

	public static function savePreviouslyPublishedMeta( $libsynPost, $current_post_id ) {
		if ( empty($libsynPost) ) return false;
		if ( !empty($libsynPost->release_date) ) {
			$post_release_date = $libsynPost->release_date;
		} else {
			$post_release_date = '';
		}
		if ( !empty($libsynPost->expiration_date) ) {
			$post_expiration_date = $libsynPost->expiration_date;
		} else {
			$post_expiration_date = '';
		}
		update_post_meta($current_post_id, 'libsyn-edit-item-id', $libsynPost->id);
		update_post_meta($current_post_id, 'libsyn-item-id', $libsynPost->id);
		if ( !empty($libsynPost->release_date) ) {
			update_post_meta($current_post_id, 'libsyn-release-date', $post_release_date );
		}
		if ( !empty($libsynPost->primary_content->content_id) ) {
			update_post_meta($current_post_id, 'libsyn-new-media-media','http://libsyn-upload-' . $libsynPost->primary_content->content_id);
		}
		if ( !empty($libsynPost->primary_content->secure_url) ) {
			$sslurl = true;
			update_post_meta($current_post_id, 'libsyn-post-episode-primary_content_url', $libsynPost->primary_content->secure_url);
		} else {
			$sslurl = false;
		}
		if ( !empty($libsynPost->primary_content->url) && ( !$sslurl ) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-primary_content_url', $libsynPost->primary_content->url);
		}
		if ( !empty($libsynPost->item_subtitle) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-subtitle', $libsynPost->item_subtitle);
		}
		if ( !empty($libsynPost->category) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-category-selection', $libsynPost->category);
		}
		if ( !empty($libsynPost->thumbnail) && !empty($libsynPost->thumbnail->url) ) {
			update_post_meta($current_post_id, 'libsyn-new-media-image', $libsynPost->thumbnail->url);
		}
		if ( !empty($libsynPost->blog_image) && !empty($libsynPost->blog_image->url) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-blog_image', $libsynPost->blog_image->url);
		}
		if ( !empty($libsynPost->background_image) && !empty($libsynPost->background_image->url) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-background_image', $libsynPost->background_image->url);
		}
		if ( !empty($libsynPost->widescreen_image) && !empty($libsynPost->widescreen_image->url) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-widescreen_image', $libsynPost->widescreen_image->url);
		}
		if ( !empty($libsynPost->item_keywords) ) {
			if ( is_array($libsynPost->item_keywords) ) {
				$keywords = implode(',', $libsynPost->item_keywords);
			} else {
				$keywords = $libsynPost->item_keywords;
			}
			update_post_meta($current_post_id, 'libsyn-post-episode-keywords', $keywords);
		}
		if ( !empty($libsynPost->itunes_explicit) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-itunes-explicit', $libsynPost->itunes_explicit);
		}
		if ( !empty($libsynPost->itunes_episode_number) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-itunes-episode-number', $libsynPost->itunes_episode_number);
		}
		if ( !empty($libsynPost->itunes_season_number) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-itunes-season-number', $libsynPost->itunes_season_number);
		}
		if ( !empty($libsynPost->itunes_episode_type) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-itunes-episode-type', $libsynPost->itunes_episode_type);
		}
		if ( !empty($libsynPost->itunes_episode_summary) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-itunes-episode-summary', $libsynPost->itunes_episode_summary);
		}
		if ( !empty($libsynPost->itunes_episode_title) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-itunes-episode-title', $libsynPost->itunes_episode_title);
		}
		if ( !empty($libsynPost->itunes_episode_author) ) {
			update_post_meta($current_post_id, 'libsyn-post-episode-itunes-episode-author', $libsynPost->itunes_episode_author);
		}
		if ( !empty($libsynPost->update_id3) ) {
			//TODO: this is not present on API return data currently
			update_post_meta($current_post_id, 'libsyn-post-episode-update-id3', $libsynPost->update_id3);
		}
		if ( !empty($libsynPost->releases) && !empty($libsynPost->release_date) ) {
			if ( !empty($libsynPost->releases) && is_array($libsynPost->releases) ) {
				//sort releases
				$working_releases = $libsynPost->releases;
				usort($working_releases, function ($a, $b) {
					return strcmp($a->destination_name, $b->destination_name);
				});
				$destinations_output = array();
				foreach ( $working_releases as $release ) {
					if ( !empty($release->destination_id) && ( $release->destination_type_slug !== 'wordpress' ) ) {
						$destinations_output["libsyn-advanced-destination-checkbox-" . $release->destination_id] = "checked";
						if ( $release->release_date !== $libsynPost->release_date ) {
							$destinations_output["set_release_scheduler_advanced_release_lc__" . $release->destination_id . "-0"] = "";
							$destinations_output["set_release_scheduler_advanced_release_lc__" . $release->destination_id . "-2"] = "checked";
							//$working_release_date = new \DateTime($release->release_date);
							$working_release_date = $release->release_date;
						} else {
							$destinations_output["set_release_scheduler_advanced_release_lc__" . $release->destination_id . "-0"] = "checked";
							$destinations_output["set_release_scheduler_advanced_release_lc__" . $release->destination_id . "-2"] = "";
							//$working_release_date = new \DateTime($post_release_date);
							$working_release_date = $post_release_date;
						}
						$destinations_output["release_scheduler_advanced_release_lc__" . $release->destination_id] = "";
						if ( !empty($working_release_date) ) {
							$destinations_output["release_scheduler_advanced_release_lc__" . $release->destination_id . "_date"] = get_date_from_gmt($working_release_date, "Y-m-d");
							$destinations_output["release_scheduler_advanced_release_lc__" . $release->destination_id . "_time_select_select-element"] = get_date_from_gmt($working_release_date, "h:i:s A");
							$destinations_output["libsyn-post-episode-advanced-destination-" . $release->destination_id . "-release-time"] = get_date_from_gmt($working_release_date, "h:i A");
						}
						if ( !empty($release->expiration_date) ) {
							$destinations_output["set_expiration_scheduler_advanced_release_lc__" . $release->destination_id . "-0"] = "";
							$destinations_output["set_expiration_scheduler_advanced_release_lc__" . $release->destination_id . "-2"] = "checked";
							//$working_expiration_date = new \DateTime($release->expiration_date);
							$working_expiration_date = $release->expiration_date;
						} else {
							if ( empty($release->expiration_date) ) {
								$destinations_output["set_expiration_scheduler_advanced_release_lc__" . $release->destination_id . "-0"] = "checked";
								$destinations_output["set_expiration_scheduler_advanced_release_lc__" . $release->destination_id . "-2"] = "";
								$working_expiration_date = '';
							} elseif ( empty($post_expiration_date) || ( $post_expiration_date == 'never' ) ) {
								$destinations_output["set_expiration_scheduler_advanced_release_lc__" . $release->destination_id . "-0"] = "checked";
								$destinations_output["set_expiration_scheduler_advanced_release_lc__" . $release->destination_id . "-2"] = "";
								$working_expiration_date = '';
							} else {
								$destinations_output["set_expiration_scheduler_advanced_release_lc__" . $release->destination_id . "-0"] = "";
								$destinations_output["set_expiration_scheduler_advanced_release_lc__" . $release->destination_id . "-2"] = "checked";
								//$working_expiration_date = new \DateTime($post_expiration_date);
								$working_expiration_date = $post_expiration_date;
							}
						}
						$destinations_output["expiration_scheduler_advanced_release_lc__" . $release->destination_id] = "";
						if ( !empty($working_expiration_date) ) {
							$destinations_output["expiration_scheduler_advanced_release_lc__" . $release->destination_id . "_date"] = get_date_from_gmt($working_expiration_date, "Y-m-d");
							$destinations_output["expiration_scheduler_advanced_release_lc__" . $release->destination_id . "_time_select_select-element"] = get_date_from_gmt($working_expiration_date, "h:i:s A");
							$destinations_output["libsyn-post-episode-advanced-destination-" . $release->destination_id . "-expiration-time"] = get_date_from_gmt($working_expiration_date, "h:i A");
						} else {
							$destinations_output["expiration_scheduler_advanced_release_lc__" . $release->destination_id . "_date"] = '';
							$destinations_output["expiration_scheduler_advanced_release_lc__" . $release->destination_id . "_time_select_select-element"] = '';
							$destinations_output["libsyn-post-episode-advanced-destination-" . $release->destination_id . "-expiration-time"] = '';
						}
					}
					if ( isset($working_release_date) ) unset($working_release_date);
					if ( isset($working_expiration_date) ) unset($working_expiration_date);
				}
			} else {
				$destinations_output = array();
			}

			if ( !empty($destinations_output) ) {
				update_post_meta($current_post_id, 'libsyn-post-episode-advanced-destination-form-data', $destinations_output);
			}
			update_post_meta($current_post_id, 'libsyn-destination-releases', $libsynPost->releases);
		}
		$embed_url = \Libsyn\Utilities::getEmbedUrlGeneric($current_post_id);
		if ( !empty($embed_url) ) {
			update_post_meta($current_post_id, 'libsyn-episode-embedurl', $embed_url);
		}
		$player_shortcode = parent::getPlayerShortcode($current_post_id);

		//add post title
		if ( !empty($libsynPost->item_title) ) {
			if ( function_exists('wp_update_post') ) {
				$post_content = wpautop(wp_kses_post(strip_shortcodes(parent::stripShortcode('smart_track_player', parent::stripShortcode('podcast', parent::stripShortcode('podcast', $libsynPost->body))))));
				$post_content .= '<!-- wp:cgb/block-libsyn-podcasting-gutenberg --><div class="wp-block-cgb-block-libsyn-podcasting-gutenberg"><div class="libsyn-shortcode"></div></div><!-- /wp:cgb/block-libsyn-podcasting-gutenberg -->';
				wp_update_post( array( 'ID' => $current_post_id, 'post_title' => $libsynPost->item_title, 'post_content' => $post_content, 'post_status' => 'draft'), false );
			}
		}

	}

}
?>
