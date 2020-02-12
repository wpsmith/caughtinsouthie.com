<?php
namespace Libsyn;

class Post extends \Libsyn {

	protected $libsyn_wp_post_id;

	/**
	 * Adds the actions for the Post Page
	 * @since 1.0.1.1
	 * @return void
	 */
	public static function actionsAndFilters() {
		//filters
		add_filter('attachment_fields_to_save', array('\Libsyn\Post', 'updateAttachmentMeta'), 4);
		add_action('wp_ajax_save-attachment-compat', array('\Libsyn\Post', 'mediaExtraFields'), 0, 1);

		//actions
		add_action( 'media_upload_libsyn_ftp_unreleased', array('\Libsyn\Post', 'libsynFtpUnreleasedContent') ); // ftp-unreleased (adds external media content)
		add_action( 'admin_enqueue_scripts', array( '\Libsyn\Post', 'addAssets' ) ); // ftp-unreleased (adds primary ftp/unreleased selection asset)
		add_action( 'admin_enqueue_scripts', array('\Libsyn\Post', 'addLocalizedAssets'), 0, 1 );
		add_action( 'enqueue_block_editor_assets', array('\Libsyn\Post', 'addLocalizedAssets'), 0, 1 );
		add_action( 'wp_ajax__libsyn_ajax_fetch_custom_list', array('\Libsyn\Post', '_libsyn_ajax_fetch_custom_list_callback') ); // (handles ajax destinations calls)
		add_action( 'init', array('\Libsyn\Post', 'loadBlockMeta'), 0, 1 );
	}

    /**
     * Handles registering meta data values.
	 *
	 * @since 1.0.1.4
     *
     * @return <type>
     */
	public static function loadBlockMeta () {

		$sanitize = new \Libsyn\Service\Sanitize();

		register_meta( 'post', 'libsyn-item-id', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'integer',
			'sanitize_callback'	=> array($sanitize, 'numeric')
		) );

		register_meta( 'post', 'libsyn-show-id', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'integer',
			'sanitize_callback'	=> array($sanitize, 'showId')
		) );

		register_meta( 'post', 'libsyn-post-error', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-error_post-type', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-error_post-permissions', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-error_api', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'playlist-podcast-url', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-episode-thumbnail', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-episode-widescreen_image', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-episode-blog_image', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-episode-background_image', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-category-selection', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-player_use_thumbnail', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-player_use_theme', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-player_height', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-player_width', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-player_placement', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-player_use_download_link', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-player_use_download_link_text', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-player_custom_color', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-itunes-explicit', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string'
		) );

		register_meta( 'post', 'libsyn-post-episode-update-id3', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-release-date', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-simple-download', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-release-date', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-update-release-date', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-is_draft', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'isLibsynPost', array(
			'show_in_rest'		=> true,
			'single'      		=> true,
			'type'         		=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-new-media-media', array(
			'show_in_rest' 		=> true,
			'single'      		=> true,
			'type'         		=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-subtitle', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-new-media-image', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'url_raw')
		) );

		register_meta( 'post', 'libsyn-post-episode-keywords', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-itunes', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-itunes-episode-number', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'numeric')
		) );

		register_meta( 'post', 'libsyn-post-episode-itunes-season-number', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'numeric')
		) );

		register_meta( 'post', 'libsyn-post-episode-itunes-episode-type', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-itunes-episode-summary', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-itunes-episode-title', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-itunes-episode-author', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-destination-releases', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-advanced-destination-form-data', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'json')
		) );

		register_meta( 'post', 'libsyn-post-episode-advanced-destination-form-data-enabled', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-post-episode-advanced-destination-form-data-input-enabled', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'boolean'
		) );

		register_meta( 'post', 'libsyn-post-episode-premium_state', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'text')
		) );

		register_meta( 'post', 'libsyn-episode-shortcode', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string'
		) );

		register_meta( 'post', 'libsyn-episode-embedurl', array(
			'show_in_rest'		=> true,
			'single'			=> true,
			'type'				=> 'string',
			'sanitize_callback'	=> array($sanitize, 'url_raw')
		) );
	}

    /**
     * Adds scripts in use for post page
     *
	 * @since 1.2.1
     *
     * @return void
     */
	public static function addAssets() {
		/* Scripts */
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		self::mediaSelectAssets('image');// Image asset
		self::mediaSelectAssets('media');// FTP/Unreleased
		self::mediaSelectAssets('ftp');// Primary Media

		wp_enqueue_script( 'jquery_validate', plugins_url(LIBSYN_DIR.'/lib/js/jquery.validate.min.js'), array('jquery') );
		wp_enqueue_script( 'libsyn-meta-form', plugins_url(LIBSYN_DIR.'/lib/js/libsyn/meta_form.js'), array('jquery') );
		wp_enqueue_script( 'jquery-filestyle', plugins_url(LIBSYN_DIR . '/lib/js/jquery-filestyle.min.js'), array('jquery'));
		wp_enqueue_script( 'iris' );

		/* Styles */
		wp_enqueue_style( 'libsyn-jquery-ui', plugins_url(LIBSYN_DIR . '/lib/css/jquery-ui-theme/jquery-ui-1.10.0.custom.css'));
		wp_enqueue_style( 'jquery-filestyle', plugins_url(LIBSYN_DIR . '/lib/css/jquery-filestyle.min.css'));
		wp_enqueue_style( 'libsyn-meta-form', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_form.css'));
		wp_enqueue_style( 'libsyn-meta-boxes', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_boxes.css' ));
		wp_enqueue_style( 'libsyn-dashicons', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/dashicons.css' ));
		wp_enqueue_style( 'jquery-colorpicker', plugins_url(LIBSYN_DIR . '/lib/css/jquery.colorpicker.css' ));
		wp_enqueue_style( 'iris' );
	}

    /**
     * Adds scripts in use for post page
	 * passing localized data.
     *
	 * @since 1.2.1
     *
     * @return void
     */
	public static function addLocalizedAssets() {
		$plugin = new \Libsyn\Service();
		$current_user_id = $plugin->getCurrentUserId();
		$api = $plugin->retrieveApiById($current_user_id);
		$render = false;
		$refreshTokenProblem = false;
		$utilities = new \Libsyn\Utilities();

		//check for Gutenberg
		$is_block_editor = $utilities->is_gutenberg_editor_active();
		$classic_editor_override = get_option('libsyn-podcasting-settings_use_classic_editor');
		$classic_editor_override = ( !empty($classic_editor_override) && $classic_editor_override == 'use_classic_editor' ) ? true : false;

		if ( $is_block_editor && !$classic_editor_override) {
			if ( current_filter() === 'admin_enqueue_scripts' ) {
				return; //back out since we are calling this again when block editor is enqueued
			}
			$postEditorType = 'block';
		} else {
			if ( ( function_exists('current_filter')  && ( current_filter() === 'enqueue_block_editor_assets' ) ) && !$classic_editor_override) {
				$postEditorType = 'block';
			} else {
				$postEditorType = 'classic';
			}
		}

		if ($api instanceof \Libsyn\Api ) {

			//Build Localization Array
			$localization = array(
				'url'	=> $plugin->getApiBaseUri() . '/media',
				'api'	=> array(
					'access_token'	=> $api->getAccessToken(),
					'show_id'		=> $api->getShowId(),
				),
			);
			if ( function_exists('admin_url') ) {
				$localization['admin_ajax_url'] = admin_url( 'admin-ajax.php' );
			}
			if ( function_exists('get_the_ID') ) {
				$current_post_id = get_the_ID();
				if ( !empty($current_post_id) ) {
					$localization['post_id'] = $current_post_id;
				}
			}

			//get shows
			$shows = $plugin->getShows($api);
			if ( !empty($shows->{'user-shows'}) ) {
				$shows = $shows->{'user-shows'};
				foreach ( $shows as $show ) {
					$localization['shows'][$show->{'show_id'}] = array(
						'label' => $show->{'show_title'},
						'value' => $show->{'show_id'},
						'is_premium' => $show->{'is_premium'}
					);
					// if ( !empty($show->{'is_premium'}) )
				}
			} else {
				$shows = new \stdClass(); //empty shows list
			}

			$current_show_id = get_post_meta($current_post_id, 'libsyn-show-id', true);
			if ( empty($current_show_id) ) {
				$current_show_id = $api->getShowId();
			}

			/* TESTING */
			//TODO: Url and Attributes array ( atts array optional) below generates the player shortcode in json simply json_decode on successful response
/* 			$attributes = array( 'attributes' =>
				array (
					'src' => 'https://www.sandbox2.tonycast.com/blah',
					'theme' => 'custom'
				)
			);
			$shortcode = admin_url( 'admin-ajax.php' ) . '?action=libsyn_player_shortcode&libsyn_player_shortcode=1&post_id=' . $current_post_id . '&' . http_build_query($attributes);
			if ( !empty($shortcode) ) {
				$shortcode = json_decode($shortcode);
			} */

			switch ( $postEditorType ) {
				case 'block':
					//categories
					$categories = self::loadFormData( array('action' => 'load_libsyn_media', 'load_libsyn_media' => '1') );
					if ( !empty($categories) && is_array($categories) ) {
						$localization['categories'] = $categories;
					}

					//error types
					$localization['error'] = array();
					$localization['error']['media_show_mismatch'] = false;

					//show settings
					$localization['show_id'] = $current_show_id;
					$localization['selected_category'] = null;
					$localization['player_use_download_link_text'] = '';
					$localization['libsyn-upload-media-preview-inner-html'] = '';
					$localization['libsyn_new_media_media'] = '';
					$localization['libsyn_new_media_image'] = '';
					$localization['edit_mode_enabled'] = true;
					$localization['simple_download'] = ( !empty(get_post_meta($current_post_id, 'libsyn-post-episode-simple-download', true)) ) ? get_post_meta($current_post_id, 'libsyn-post-episode-simple-download', true) : '';
					$localization['update_id3'] = ( !empty(get_post_meta($current_post_id, 'libsyn-post-episode-update-id3', true)) ) ? get_post_meta($current_post_id, 'libsyn-post-episode-update-id3', true) : '';

					//player settings
					$playerSettings = array();
					$playerSettings['player_use_theme'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_use_theme', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_use_theme', true) : get_user_option('libsyn-podcasting-player_use_theme');
					if ( empty($playerSettings['player_use_theme']) ) {//default to custom theme
						$playerSettings['player_use_theme'] = 'custom';
					}
					$playerSettings['player_width'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_width', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_width', true) : get_user_option('libsyn-podcasting-player_width');
					$playerSettings['player_height'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_height', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_height', true) : get_user_option('libsyn-podcasting-player_height');
					$playerSettings['player_placement'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_placement', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_placement', true) : get_user_option('libsyn-podcasting-player_placement');
					$playerSettings['player_use_download_link'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_use_download_link', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_use_download_link', true) : get_user_option('libsyn-podcasting-player_use_download_link');
					$playerSettings['player_use_download_link_text'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_use_download_link_text', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_use_download_link_text', true) : get_user_option('libsyn-podcasting-player_use_download_link_text');
					$playerSettings['player_custom_color'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_custom_color', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_custom_color', true) : get_user_option('libsyn-podcasting-player_custom_color');
					$playerSettings['player_use_thumbnail'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_use_thumbnail', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_use_thumbnail', true) : get_user_option('libsyn-podcasting-player_use_thumbnail');
					//Also update post meta with the post/user defined settings first
					foreach ($playerSettings as $key => $val) {
						update_post_meta($current_post_id, 'libsyn-podcasting-' . $key, $val);
					}
					$playerSettings['images'] = array(
							'standard'	=> plugins_url( LIBSYN_DIR . '/lib/images/player-preview-standard.jpg'),
							'mini'		=> plugins_url( LIBSYN_DIR . '/lib/images/player-preview-standard-mini.jpg'),
							'custom'	=> plugins_url( LIBSYN_DIR . '/lib/images/custom-player-preview.jpg'),
					);

					$localization['player_settings'] = $playerSettings;

					$destinations = $plugin->getDestinations($api);
					if ( !empty($destinations->destinations) ) {
						$working_destinations = array();
						foreach($destinations->destinations as $key => $val ) {
							$working_destinations[$val->destination_id] = $val;
						}
						usort($working_destinations, function ($a, $b) {
							return strcmp($a->destination_name, $b->destination_name);
						});
						$working_destinations_class = (object) $working_destinations;
						if ( !empty($working_destinations_class) && is_object($working_destinations_class) ) {
							$localization['destinations'] = $working_destinations_class;
						} else {
							$localization['destinations'] = $destinations->destinations;
						}
					} else {
						$localization['destinations'] = array();
					}

					break;
				case 'classic':
					break;
			}

			//handle edit post
			if ( !empty($_GET['libsyn_edit_post_id']) ) {
				$editedPostId = $plugin->sanitize->itemId($_GET['libsyn_edit_post_id']);
				$editedPostId = ( !empty($editedPostId) ) ? intval($editedPostId) : 0;
				if ( !empty($editedPostId) ) {
					$duplicateEditPost = $plugin->checkEditPostDuplicate($editedPostId);
					if ( $duplicateEditPost ) {//duplicate post found
						wp_redirect( $plugin->admin_url('post.php') . '?post=' . $duplicateEditPost->post_id . '&action=edit', 302, $plugin->text_dom);
					}

					//get edit post data
					$editedItem = $plugin->getEpisode(
						array(
							'show_id'	=> $api->getShowId(),
							'item_id'	=> $editedPostId
						)
					);

					//update edited meta
					if ( !empty($editedItem->_embedded->post) ) {
						$localization['libsyn_edit_item'] = $editedItem->_embedded->post;
						$handleMeta = \Libsyn\Post\Block::savePreviouslyPublishedMeta($editedItem->_embedded->post, $current_post_id);
						wp_redirect( $plugin->admin_url('post.php') . '?post=' . $current_post_id . '&action=edit', 302, $plugin->text_dom);
					}
				}
			}

			// Media Upload Dialog Box
			global $pagenow; //disables scripts from loading on pages other than post editor pages
			if ( function_exists( 'wp_script_is' ) && ( !empty($pagenow) && ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) ) ) {
				if ( !wp_script_is( 'libsyn-media-upload-dialog', 'enqueued' ) ) {
					wp_register_script( 'libsyn-media-upload-dialog', plugins_url( LIBSYN_DIR . '/lib/js/libsyn/media_upload.' . $postEditorType . '.js'), array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog' ) );
					wp_localize_script( 'libsyn-media-upload-dialog', 'libsyn_nmp_data', $localization);
					wp_enqueue_script( 'libsyn-media-upload-dialog' );
				}
				if ( !wp_script_is( 'libsyn-player-settings', 'enqueued') ) {
					wp_enqueue_script( 'libsyn-player-settings', plugins_url(LIBSYN_DIR . '/lib/js/libsyn/player_settings.' . $postEditorType . '.js'), array('jquery', 'jquery-ui-core', 'jquery-ui-dialog') );
				}
				if ( !wp_script_is( 'libsyn-advanced-destination', 'enqueued' ) ) {
					wp_enqueue_script( 'libsyn-advanced-destination', plugins_url(LIBSYN_DIR . '/lib/js/libsyn/advanced_destination.' . $postEditorType . '.js'), array('jquery'));
				}
			}
		}
	}

    /**
     * Handles FTP Unreleased retrieval of ftp type media
     *
	 * @since 1.0.1.1
     *
     * @return void
     */
	public static function libsynFtpUnreleasedContent() {
		$libsyn_error = false;
		$plugin = new \Libsyn\Service();
		$current_user_id = $plugin->getCurrentUserId();
		$api = $plugin->retrieveApiById($current_user_id);
		if ($api instanceof \Libsyn\Api ) {
			$isRefreshExpired = $api->isRefreshExpired();
			if ( $isRefreshExpired ) { //expired attempt to refresh
				$refreshApi = $api->refreshToken();
			} else {
				$refreshApi = true;
			}
			if ( $refreshApi ) { //successfully refreshed
				$ftp_unreleased = $plugin->getFtpUnreleased($api)->{'ftp-unreleased'};
			} else { $libsyn_error = true; }
		}

		if ( $libsyn_error ) echo "<p>Oops, you do not have your Libsyn Account setup properly to use this feature, please go to Settings and try again.</p>";
	}

    /**
     * Simple worker method to dynamically load script filenames
     * @since 1.0.1.1
     * @param string $attachment
     * @return void
     */
	public static function mediaExtraFields($attachment){
		  global $post;
		  update_post_meta($post->ID, 'meta_link', $attachment['attachments'][$post->ID]['meta_link']);
	}

    /**
     * Adds meta form validation
	 * @deprecated 1.2.1 Combined with \Libsyn\Post::addAssets()
     * @since 1.0.1.1
     * @return void
     */
	public static function enqueueValidation(){
		wp_enqueue_script('jquery_validate', plugins_url(LIBSYN_DIR.'/lib/js/jquery.validate.min.js'), array('jquery'));
	}

    /**
     * Post meta updater for attachment data
     * @since 1.0.1.1
     * @param string $attachment
     * @return void
     */
	public static function updateAttachmentMeta($attachment){
		global $post;
		if ( !empty($post->ID) && !empty($attachment['attachments']) && !empty($attachment['attachments'][$post->ID]) ) {
			update_post_meta($post->ID, 'meta_link', $attachment['attachments'][$post->ID]['meta_link']);
		}
	}

    /**
     * Simple method to check the current page
     * @since 1.0.1.1
     * @return void
     */
	public static function getCurrentPageUrl() {
		global $wp;
		return add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) );
	}

	/**
	 * Sets up Media select button
	 *
	 * @since 1.0.1.1
	 * @param string $type
	 *
	 * @return mixed
	 */
	public static function mediaSelectAssets( $type ) {
		wp_enqueue_media();
		wp_register_script( 'libsyn_nmp_' . strtolower($type), plugins_url( LIBSYN_DIR.'/lib/js/libsyn/media.' . strtolower($type) . '.min.js'), array( 'jquery' ), '1.0.0', true );
		$localization = array(
			'title'     => __( 'Upload or Choose Your Custom ' . str_replace('ftp', 'Libsyn FTP/Unreleased', ucfirst($type)) . ' File', LIBSYN_TEXT_DOMAIN ),
			'button'    => __( 'Insert into Input Field', LIBSYN_TEXT_DOMAIN )
		);
		if ( function_exists('admin_url') ) {
			$localization['admin_ajax_url'] =  admin_url( 'admin-ajax.php' );
		}
		if ( function_exists('get_the_ID') ) {
			$current_post_id = get_the_ID();
			if ( !empty($current_post_id) ) {
				$localization['post_id'] = $current_post_id;
			}
		}
		wp_localize_script( 'libsyn_nmp_' . strtolower($type), 'libsyn_nmp_' . strtolower($type), $localization);
		wp_enqueue_script( 'libsyn_nmp_' . strtolower($type) );

	}

    /**
	 * Callback for 'wp_ajax_fetch_custom_list' action hook.
	 *
	 * Loads the Custom List Table Class and calls ajax_response method
     * @since 1.0.1.1
     *
     * @return void
     */
	public static function _libsyn_ajax_fetch_custom_list_callback() {
		$destination = new Service\Destination();
		// $wp_list_table = new TT_Example_List_Table();
		// $wp_list_table->ajax_response();

		//Have to get the post id from url query
		$url = wp_get_referer();
		$ajax_post_page_query = parse_url( $url, PHP_URL_QUERY );
		// parse the query args
		$post_page_args  = array();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str( $ajax_post_page_query, $post_page_args );
		} else {
			parse_str( $ajax_post_page_query, $post_page_args );
		}

		// make sure we are editing a post and that post ID is an INT
		if ( isset( $post_page_args[ 'post' ] ) && is_numeric( $post_page_args[ 'post' ] ) && isset( $post_page_args[ 'action' ] ) && $post_page_args[ 'action' ] === 'edit' )
			if ( $id = intval( $post_page_args[ 'post' ] ) ) $post_id = $id;
		if ( isset($post_id) && is_int($post_id) ) $published_destinations = get_post_meta($post_id, 'libsyn-destination-releases', true);
			else $published_destinations = '';

		$plugin = new Service();
		$current_user_id = $plugin->getCurrentUserId();
		$api = $plugin->retrieveApiById($current_user_id);
		if ( $api instanceof \Libsyn\Api ) {
			$destinations = $plugin->getDestinations($api);
		} else {
			$destinations = false;
		}

		if ( $destinations ) {
			$destinations = $destination->formatDestinationsTableData($destinations, $post_id);
			$destination_args = array(
				'singular'=> 'libsyn_destination' //Singular label
				,'plural' => 'libsyn_destinations' //plural label, also this well be one of the table css class
				,'ajax'   => true //We won't support Ajax for this table

			);
			//Prepare Table of elements
			$wp_list_table = new \Libsyn\Service\Table($destination_args, $destinations->destinations);
			if ( !empty($published_destinations) ) {
				$wp_list_table->item_headers = array(
					'cb' => '<input type=\"checkbox\"></input>'
					,'id' => 'destination_id'
					,'published_status' => 'Published Status'
					,'destination_name' => 'Destination Name'
					// ,'destination_type' => 'Destination Type'
					,'release_date' => 'Release Date'
					,'expiration_date' => 'Expiration Date'
					// ,'creation_date' => 'Creation Date'
				);
			} else {
				$wp_list_table->item_headers = array(
					'cb' => '<input type=\"checkbox\"></input>'
					,'id' => 'destination_id'
					,'destination_name' => 'Destination Name'
					// ,'destination_type' => 'Destination Type'
					,'release_date' => 'Release Date'
					,'expiration_date' => 'Expiration Date'
					// ,'creation_date' => 'Creation Date'
				);
			}
			// $wp_list_table->prepare_items();
			$wp_list_table->ajax_response();
			// $wp_list_table->items = $plugin->getDestinations($api);
		}
	}

    /**
	 * Callback for 'wp_ajax__ajax_fetch_player_settings' action hook.
	 *
	 * Loads the Custom List Table Class and calls ajax_response method
     * @since 1.0.1.1
     *
     * @return void
     */
	public static function loadPlayerSettings() {
		$libsyn_error = true;
		$checkUrl  = self::getCurrentPageUrl();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}
		if ( intval($urlParams['load_player_settings']) === 1 ) {
			echo '
				<h3 id="player_settings_title">
					<label>' . __('Player Settings', 'libsyn-podcasting') . '</label>
				</h3>
				<div class="inside">
					<p id="player-description-text"><em>' . __('Below are the default player settings.  You may also modify the size on each individual post on the post page.', 'libsyn-podcasting') . '</em></p>
					<div class="box_clear"></div>
					<table class="form-table">
						<tr valign="top">
							<th>' . __('Player Theme', 'libsyn-podcasting') . '</th>
							<td>
								<div>
									<div>
										<input id="player_use_theme_standard" type="radio" value="standard" name="player_use_theme"></input><span style="margin-left:16px;"><strong>'. __('Standard', 'libsyn-podcasting') . '</strong>&nbsp;&nbsp;<em style="font-weight:300;">' . __('(minimum height 45px)', 'libsyn-podcasting') . '</em></span>
									</div>
									<div style="margin-left:36px;" id="player_use_theme_standard_image">
									</div>
									<br />
									<div>
										<input id="player_use_theme_mini" type="radio" value="mini" name="player_use_theme"></input><span style="margin-left:16px;"><strong>' . __('Mini', 'libsyn-podcasting') . '</strong>&nbsp;&nbsp;<em style="font-weight:300;">' . __('(minimum height 26px)', 'libsyn-podcasting') . '</em></span>
									</div>
									<div style="margin-left:36px;" id="player_use_theme_mini_image">
									</div>
									<br />
									<div>
										<input id="player_use_theme_custom" type="radio" value="custom" name="player_use_theme"></input><span style="margin-left:16px;"><strong>' . __('Custom', 'libsyn-podcasting') . '</strong>&nbsp;&nbsp;<em style="font-weight:300;">' . __('(minimum height 90px or 300px(video), width 450px)', 'libsyn-podcasting') . '</em></span>
									</div>
									<div style="margin-left:36px;" id="player_use_theme_custom_image">
									</div>
								</div>
							</td>
						</tr>
						<tr id="player_custom_color_picker" style="display:none;">
							<th>' . __('Custom Color', 'libsyn-podcasting') . '</th>
							<td>
								<div>
									<div style="margin-left:36px;">
										<input type="text" id="player_custom_color" class="color-picker" name="player_custom_color" value=""></input><button type="button" class="button" data-editor="content" font="400 18px/1 dashicons" id="player_custom_color_picker_button"><span class="dashicons dashicons-art" style="padding-top: 4px;"></span>' . __('Pick Color', 'libsyn-podcasting') . '</button>
										<div id="player_custom_color_picker_container" style="padding: 0px 0px 0px 0px; width:100%;"></div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr valign="top">
							<th colspan="2"><input style="margin-left: 2px;" id="player_use_thumbnail" type="checkbox" value="use_thumbnail" name="player_use_thumbnail"></input>&nbsp;' . __('Display episode/show artwork on the player?', 'libsyn-podcasting') . '&nbsp;&nbsp;<em style="font-weight:300;">' . __('(minimum height 200px)', 'libsyn-podcasting') . '</em></th>
							<td>
							</td>
						</tr>
						<tr id="player_width_tr" valign="top" style="display:none;">
							<th>' . __('Player Width:', 'libsyn-podcasting') . '</th>
							<td>
								<input id="player_width" type="number" value="" name="player_width" maxlength="4" autocomplete="on" min="200" step="1" style="display:none;"></input>
							</td>
						</tr>
						<tr valign="top">
							<th>' . __('Player Height:', 'libsyn-podcasting') . '</th>
							<td>
								<input id="player_height" type="number" value="" name="player_height" autocomplete="on" min="45" step="1"></input>
							</td>
						</tr>
						<tr valign="top">
							<th>' . __('Player Placement', 'libsyn-podcasting') . '</th>
							<td>
								<div>
									<div>
										<input id="player_placement_top" type="radio" value="top" name="player_placement"></input><span style="margin-left:16px;"><strong>' . __('Top', 'libsyn-podcasting') . '</strong>&nbsp;&nbsp;<em style="font-weight:300;">' . __('(Before Post)', 'libsyn-podcasting') . '</em></span>
									</div>
									<div style="margin-left:36px;" class="post-position-image-box">
										<div class="post-position-shape-top"></div>
									</div>
									<br />
									<div>
										<input id="player_placement_bottom" type="radio" value="bottom" name="player_placement"></input><span style="margin-left:16px;"><strong>' . __('Bottom', 'libsyn-podcasting') . '</strong>&nbsp;&nbsp;<em style="font-weight:300;">' . __('(After Post)', 'libsyn-podcasting') . '</em></span>
									</div>
									<div style="margin-left:36px;" class="post-position-image-box">
										<div class="post-position-shape-bottom"></div>
									</div>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<th colspan="2"><input style="margin-left: 2px;" id="player_use_download_link" type="checkbox" value="use_download_link" name="player_use_download_link"></input>&nbsp;' . __('Display download link below the player?', 'libsyn-podcasting') . '</th>
							<td>
							</td>
						</tr>
						<tr valign="top" style="display:none;" id="player_use_download_link_text_div">
							<th></th>
							<td>
								' . __('Download Link Text:', 'libsyn-podcasting') . '&nbsp;&nbsp;<input id="player_use_download_link_text" type="text" value="" name="player_use_download_link_text" maxlength="256" min="200"></input>
							</td>
						</tr>
						<tr valign="bottom">
							<th></th>
							<td>
								<br />
									<input type="submit" value="Save Player Settings" class="button button-primary" id="player-settings-submit" name="submit"></input>
							</td>
						</tr>
						<tr valign="bottom">
							<th style="font-size:.8em;font-weight:200;">**<em>' . __('height and width in Pixels', 'libsyn-podcasting') . ' (px)</em></th>
							<td></td>
						</tr>
					</table>
					<br />
				</div>';
		} else {
			echo __("Could not load player settings.", 'libsyn-podcasting');
		}
		exit;
	}


	/**
	 * Simple function checks the camel case of a form name prefix "libsyn-post-episode"
	 *
	 * @since 1.0.1.1
	 * @pram  int $id  WP post id ($object->ID)
	 * @param string $prefix
	 * @param string $camelCaseName
	 *
	 * @return bool
	 */
	public static function checkFormItem( $id, $prefix, $camelCaseName ) {
		$cc_text = preg_replace(array('/(?<=[^A-Z])([A-Z])/', '/(?<=[^0-9])([0-9])/'), ' $0', $camelCaseName);
		$cc_text = ucwords($cc_text);
		$check = esc_attr( get_post_meta( $id, 'libsyn-post-episode-'.$prefix.'-'.$camelCaseName, true ) );
		if ( !empty($check) && ( $check == $cc_text ) ) return true; else return false;
	}

    /**
     * Gets the post editor type
     * @since 1.2.1
     *
     * @return string (block|classic)
     */
	public static function getPostEditorType() {
		//check for Gutenberg
		$utilities = new \Libsyn\Utilities();
		$is_block_editor = $utilities->is_gutenberg_editor_active();
		$classic_editor_override = get_option('libsyn-podcasting-settings_use_classic_editor');
		$classic_editor_override = ( !empty($classic_editor_override) && $classic_editor_override == 'use_classic_editor' ) ? true : false;
		if ( $is_block_editor && !$classic_editor_override) {
			$postEditorType = 'block';
		} else {
			if ( ( function_exists('current_filter')  && ( current_filter() === 'enqueue_block_editor_assets' ) ) && !$classic_editor_override) {
				$postEditorType = 'block';
			} else {
				$postEditorType = 'classic';
			}
		}
		return $postEditorType;
	}

	/**
	 * Handles the post data fields from addLibsynPostMeta
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 *
	 * @return mixed
	 */
	public static function handlePost( $post_id, $post ) {

		/* Check Sanity */
		if ( empty($post) ) return false;
		if ( is_array($post) ) $post = (object) $post;

		if ( isset($post->post_status) && 'auto-draft' == $post->post_status) return false;

		/* Verify the nonce before proceeding. */
		if( isset($_POST['libsyn_post_episode_nonce']) ) {
			$verify_nonce = wp_verify_nonce($_POST['libsyn_post_episode_nonce'], 'Classic.php');
		}

		if ( isset($verify_nonce) && ( $verify_nonce === false ) ) return $post_id;

		/* Check if the current post type is 'post' or 'revision' (currently do not support custom post types) */
		// if ( $post->post_type !== 'post' && $post->post_type !== 'revision' ) {
			// update_post_meta($post->ID, 'libsyn-post-error_post-type', 'true');
		// }

		/* Get the post type object. */
		$post_type = get_post_type_object($post->post_type);

		/* Check if the current user has permission to edit the post. */
		if ( !current_user_can($post_type->cap->edit_post, $post_id) ) {
			update_post_meta($post->ID, 'libsyn-post-error_post-permissions', 'true');
			return $post_id;
		}

		/* Get the posted data and sanitize it for use as an HTML class. */
		$postEditorType = self::getPostEditorType();
		if ( $postEditorType == 'classic' ) {
			if ( empty($_POST) ) return $post_id; //no post back out.
			$new_meta_values = array();
			$new_meta_values['libsyn-post-episode'] = (isset($_POST['libsyn-post-episode'])) ? $_POST['libsyn-post-episode'] : '';
			$new_meta_values['libsyn-post-update-release-date'] = (isset($_POST['libsyn-post-update-release-date'])) ? $_POST['libsyn-post-update-release-date'] : '';
			$new_meta_values['libsyn-new-media-media'] = (isset($_POST['libsyn-new-media-media'])) ? $_POST['libsyn-new-media-media'] : '';
			$new_meta_values['libsyn-post-episode-subtitle'] = (isset($_POST['libsyn-post-episode-subtitle'])) ? $_POST['libsyn-post-episode-subtitle'] : '';
			$new_meta_values['libsyn-post-episode-category-selection'] = (isset($_POST['libsyn-post-episode-category-selection'])) ? $_POST['libsyn-post-episode-category-selection'] : '';
			$new_meta_values['libsyn-new-media-image'] = (isset($_POST['libsyn-new-media-image'])) ? $_POST['libsyn-new-media-image'] : '';
			$new_meta_values['libsyn-post-episode-keywords'] = (isset($_POST['libsyn-post-episode-keywords'])) ? $_POST['libsyn-post-episode-keywords'] : '';
			$new_meta_values['libsyn-post-episode-itunes-explicit'] = (isset($_POST['libsyn-post-episode-itunes-explicit'])) ? $_POST['libsyn-post-episode-itunes-explicit'] : '';
			$new_meta_values['libsyn-post-episode-itunes-episode-number'] = (isset($_POST['libsyn-post-episode-itunes-episode-number'])) ? intval($_POST['libsyn-post-episode-itunes-episode-number']) : '';
			$new_meta_values['libsyn-post-episode-itunes-season-number'] = (isset($_POST['libsyn-post-episode-itunes-season-number'])) ? intval($_POST['libsyn-post-episode-itunes-season-number']) : '';
			$new_meta_values['libsyn-post-episode-itunes-episode-type'] = (isset($_POST['libsyn-post-episode-itunes-episode-type'])) ? $_POST['libsyn-post-episode-itunes-episode-type'] : '';
			$new_meta_values['libsyn-post-episode-itunes-episode-summary'] = (isset($_POST['libsyn-post-episode-itunes-episode-summary'])) ? $_POST['libsyn-post-episode-itunes-episode-summary'] : '';
			$new_meta_values['libsyn-post-episode-itunes-episode-title'] = (isset($_POST['libsyn-post-episode-itunes-episode-title'])) ? $_POST['libsyn-post-episode-itunes-episode-title'] : '';
			$new_meta_values['libsyn-post-episode-itunes-episode-author'] = (isset($_POST['libsyn-post-episode-itunes-episode-author'])) ? $_POST['libsyn-post-episode-itunes-episode-author'] : '';
			$new_meta_values['libsyn-post-episode-update-id3'] = (isset($_POST['libsyn-post-episode-update-id3'])) ? $_POST['libsyn-post-episode-update-id3'] : '';

			//Handle player settings
			if ( isset($_POST['player_use_thumbnail']) ) {
				if ( !empty($_POST['player_use_thumbnail']) && $_POST['player_use_thumbnail'] === 'use_thumbnail' ) {
					$new_meta_values['libsyn-post-episode-player_use_thumbnail'] = $_POST['player_use_thumbnail'];
				} elseif ( empty($_POST['player_use_thumbnail']) ) {
					$new_meta_values['libsyn-post-episode-player_use_thumbnail'] = 'none';
				}
			} else {
				$new_meta_values['libsyn-post-episode-player_use_thumbnail'] = get_user_option('libsyn-podcasting-player_use_thumbnail');
				if ( empty($new_meta_values['libsyn-post-episode-player_use_thumbnail']) ) $new_meta_values['libsyn-post-episode-player_use_thumbnail'] = 'none';
			}
			$new_meta_values['libsyn-post-episode-player_use_theme'] = (isset($_POST['player_use_theme'])) ? $_POST['player_use_theme'] : get_user_option('libsyn-podcasting-player_use_theme');
			$new_meta_values['libsyn-post-episode-player_width'] = (isset($_POST['player_width'])) ? $_POST['player_width'] : get_user_option('libsyn-podcasting-player_width');
			$new_meta_values['libsyn-post-episode-player_height'] = (isset($_POST['player_height'])) ? $_POST['player_height'] : get_user_option('libsyn-podcasting-player_height');
			$new_meta_values['libsyn-post-episode-player_placement'] = (isset($_POST['player_placement'])) ? $_POST['player_placement'] : get_user_option('libsyn-podcasting-player_placement');
			$new_meta_values['libsyn-post-episode-player_use_download_link'] = (isset($_POST['player_use_download_link'])) ? $_POST['player_use_download_link'] : get_user_option('libsyn-podcasting-player_use_download_link');
			$new_meta_values['libsyn-post-episode-player_use_download_link_text'] = (isset($_POST['player_use_download_link_text'])) ? $_POST['player_use_download_link_text'] : get_user_option('libsyn-podcasting-player_use_download_link_text');
			$new_meta_values['libsyn-post-episode-player_custom_color'] = (isset($_POST['player_custom_color'])) ? $_POST['player_custom_color'] : get_user_option('libsyn-podcasting-player_custom_color');
			$new_meta_values['libsyn-post-episode-advanced-destination-form-data'] = (isset($_POST['libsyn-post-episode-advanced-destination-form-data-input'])) ? $_POST['libsyn-post-episode-advanced-destination-form-data-input'] : get_user_option('libsyn-post-episode-advanced-destination-form-data');
			$new_meta_values['libsyn-post-episode-advanced-destination-form-data-input-enabled'] = (isset($_POST['libsyn-post-episode-advanced-destination-form-data-input-enabled'])) ? $_POST['libsyn-post-episode-advanced-destination-form-data-input-enabled'] : get_user_option('libsyn-post-episode-advanced-destination-form-data-input-enabled');
			$new_meta_values['libsyn-post-episode-simple-download'] = isset($_POST['libsyn-post-episode-simple-download']) ? $_POST['libsyn-post-episode-simple-download'] : '';
			$new_meta_values['libsyn-show-id'] = isset($_POST['libsyn-show-select']) ? $_POST['libsyn-show-select'] : 0;

			//Handle new meta values
			self::handleMetaValueArray( $post_id, $new_meta_values );
		}

		/* Call Post to Libsyn based on post_status */
		try{
			if ( $postEditorType == 'classic' ) {
				self::postHandler($post, $_POST);
			} elseif( $postEditorType == 'block' ) {
				if ( !empty($post->post_type) ) {
					add_action('rest_after_insert_'.$post->post_type, '\Libsyn\Post::postHandler', 10, 2);
				} else {
					add_action('rest_after_insert_post', '\Libsyn\Post::postHandler', 10, 2);
				}
			}

		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 *  @brief Post Handler
	 *  @since 1.2.1
	 *
	 *  @param int $post_id WP Post Id
	 *  @param WP_Post $post WP Post Object
	 *  @return void
	 *
	 *  @details This is intermediarray handler for posts (supports block editor)
	 */
	public static function postHandler($post, $request) {
		try{
			if ( empty($post->ID) && !empty($post_id) ) {
				$post->ID = $post_id;
			}
			switch($post->post_status) {
				case 'future':
					self::postEpisode($post, true);
					break;

				case 'draft':
					self::postEpisode($post, false, true);
					break;

				case 'pending':
					//echo("Pending, not sure where to do here");exit;
					break;

				case 'private':
					//echo("We do not handle private");exit;
					break;

				case 'publish':
					self::postEpisode($post);
					break;

				default:
					return false;
			}
		} catch (Exception $e) {
			$plugin = new Service();
			if ( $plugin->hasLogger ) $plugin->logger->error( "Post:\tpostHandler:\t" . $e->getMessage() );
		}
	}


	/**
	 * Handle meta values based on the way they are setup in array.
	 * see (array) $new_meta_values
	 *
	 * @since 1.0.1.1
	 * @param array $new_meta_values
	 *
	 * @return void
	 */
	public static function handleMetaValueArray( $post_id, $new_meta_values ) {
		/* If a new meta value was added and there was no previous value, add it. */
		foreach ($new_meta_values as $key => $val) {
			$meta_value = get_post_meta($post_id, $key, true);
			$sanitize = new \Libsyn\Service\Sanitize();
			if ( !isset($url) ) $url = '';

			//sanitize value
			if ( $key === 'libsyn-new-media-image' ) {
				$clean_val = $sanitize->url_raw($val);
			} elseif ( $key === 'libsyn-new-media-media' && ( strpos($val, 'libsyn-ftp-') === false || strpos($url, 'libsyn-upload-') === false ) ) {
				$clean_val = $sanitize->url_raw($val);
			} elseif ( $key === 'libsyn-post-episode-advanced-destination-form-data') {
				$clean_val = $sanitize->json($val);
			} elseif ( $key === 'libsyn-post-episode-itunes-episode-number' || $key === 'libsyn-post-episode-itunes-season-number' || $key === 'libsyn-show-id' ) {
				$clean_val = $sanitize->numeric($val);
			} else {
				$clean_val = $sanitize->text($val);
			}

			//setup post meta
			if ( !empty($clean_val) && empty($meta_value) ) // no meta_value so create
				add_post_meta($post_id, $key, $clean_val, true);
			elseif ( !empty($clean_val) && $clean_val !== $meta_value) //doesn't match old value so update
				update_post_meta($post_id, $key, $clean_val);
			elseif ( empty($clean_val) && !empty($meta_value) ) //old value doesn't exist, delete it
				delete_post_meta($post_id, $key, $meta_value);
		}
	}



	/**
	 * Attaches form field values
	 *
	 * @since 1.0.1.1
	 * @param array $form_fields
	 * @param WP_Post $post
	 *
	 * @return mixed
	 */
	public static function attachFieldsToEdit( $form_fields, $post ) {
		$field_value = get_post_meta($post->ID, 'location', true);
		$form_fields['location'] = array(
			'value' => $field_value ? $field_value : '',
			'label' => __( 'Location' , LIBSYN_TEXT_DOMAIN),
			'helps' => __( 'Set a location for this attachment', LIBSYN_TEXT_DOMAIN ),
		);
		return $form_fields;
	}

	/**
	 * Handles the Meta post box classes
	 *
	 * @since 1.0.1.1
	 * @param mixed $classes
	 *
	 * @return mixed
	 */
	public static function metaPostClasses( $classes ) {
		/* Get the current post ID. */
		$post_id = get_the_ID();

		/* If we have a post ID, proceed. */
		if ( !empty( $post_id ) ) {
			$post_class = get_post_meta( $post_id, 'libsyn_post_episode', true );
			if ( !empty( $post_class ) ) $classes[] = sanitize_html_class( $post_class );
		}
		return $classes;

	}

	/**
	 * Main Post script which handles Libsyn API posting. Used for post scheduled/immediate post.
	 *
	 * @since 1.0.1.1
	 * @param WP_Post $post
	 * @param int $post_id
	 * @param bool $schedule
	 * @param bool $draft
	 *
	 * @return mixed Libsyn_Item or boolean
	 */
	public static function postEpisode( $post, $isSchedule=false, $isDraft=false ) {
		/* Back out quickly if the post to libsyn is not checked */
		$postMetaCheck = get_post_meta($post->ID, 'libsyn-post-episode', true);
		if ( !in_array( $postMetaCheck, array('_isLibsynPost', 'isLibsynPost') ) ) return;

		/* Begin Post Process */
		$plugin = new Service();
		$sanitize = new \Libsyn\Service\Sanitize();
		$current_user_id = $plugin->getCurrentUserId();
		$api = $plugin->retrieveApiById($current_user_id);

		//Create item API array
		$item = array();
		$working_show_id = get_post_meta($post->ID, 'libsyn-show-id', true);
		if ( !empty($working_show_id) ) {
			$item['show_id'] = $working_show_id;
		} else {
			$item['show_id'] = ( $api instanceof \Libsyn\Api ) ? $api->getShowId() : null;
		}
		$item['item_title'] = $post->post_title;
		$item['item_subtitle'] = get_post_meta($post->ID, 'libsyn-post-episode-subtitle', true);
		$item['thumbnail_url'] = get_post_meta($post->ID, 'libsyn-new-media-image', true);
		if ( function_exists('strip_shortcodes') ) {
			$content = wpautop(wp_kses_post(strip_shortcodes(self::stripShortcode('smart_track_player', self::stripShortcode('podcast', self::stripShortcode('podcast', $post->post_content))))));
		} else {
			$content = wpautop(wp_kses_post(self::stripShortcode('smart_track_player', (self::stripShortcode('podcast', self::stripShortcode('podcast', $post->post_content))))));
		}
		$item['item_body'] = $content;
		$item['item_category'] = get_post_meta($post->ID, 'libsyn-post-episode-category-selection', true);
		$item['itunes_explicit'] = get_post_meta($post->ID, 'libsyn-post-episode-itunes-explicit', true);
		if ( $item['itunes_explicit'] === 'explicit' ) $item['itunes_explicit'] = 'yes';
		$item['itunes_episode_number'] = get_post_meta($post->ID, 'libsyn-post-episode-itunes-episode-number', true);
		$item['itunes_episode_number'] = (!empty($item['itunes_episode_number'])) ? intval($item['itunes_episode_number']) : null; //set as int
		$item['itunes_season_number'] = get_post_meta($post->ID, 'libsyn-post-episode-itunes-season-number', true);
		$item['itunes_season_number'] = (!empty($item['itunes_season_number'])) ? intval($item['itunes_season_number']) : null; //set as int
		$item['itunes_episode_type'] = get_post_meta($post->ID, 'libsyn-post-episode-itunes-episode-type', true);
		$item['itunes_episode_summary'] = get_post_meta($post->ID, 'libsyn-post-episode-itunes-episode-summary', true);
		$item['itunes_episode_title'] = get_post_meta($post->ID, 'libsyn-post-episode-itunes-episode-title', true);
		$item['itunes_episode_author'] = get_post_meta($post->ID, 'libsyn-post-episode-itunes-episode-author', true);
		$item['update_id3'] = get_post_meta($post->ID, 'libsyn-post-episode-update-id3', true);
		$item['item_keywords'] = get_post_meta($post->ID, 'libsyn-post-episode-keywords', true);

		//player settings //post params are height(int),theme(standard,mini),width(int)
		$item['height'] = get_post_meta($post->ID, 'libsyn-post-episode-player_height', true);
		$item['width'] = get_post_meta($post->ID, 'libsyn-post-episode-player_width', true);
		$item['theme'] = get_post_meta($post->ID, 'libsyn-post-episode-player_use_theme', true);
		$item['custom_color'] = get_post_meta($post->ID, 'libsyn-post-episode-player_custom_color', true);
		$item['custom_color'] = ( !empty($item['custom_color']) && ( substr($item['custom_color'], 0, 1) === '#' ) ) ? substr($item['custom_color'], 1) : $item['custom_color'];
		$item['player_use_thumbnail'] = get_post_meta($post->ID, 'libsyn-post-episode-player_use_thumbnail', true);

		//Because Gutenberg's meta is broken..
		if ( empty($item['height']) ) {
			$item['height'] = get_user_option('libsyn-podcasting-player_height');
			if ( empty($item['height']) ) {
				unset($item['height']);
			}
		}
		if ( empty($item['width']) ) {
			$item['width'] = get_user_option('libsyn-podcasting-player_width');
			if ( empty($item['width']) ) {
				unset($item['width']);
			}
		}
		if ( empty($item['theme']) ) {
			$item['theme'] = get_user_option('libsyn-podcasting-player_use_theme');
			if ( empty($item['theme']) ) {
				unset($item['theme']);
			}
		}
		if ( empty($item['custom_color']) ) {
			$item['custom_color'] = get_user_option('libsyn-podcasting-player_custom_color');
			if ( empty($item['custom_color']) ) {
				unset($item['custom_color']);
			}
		}
		if ( empty($item['player_use_thumbnail']) ) {
			$item['player_use_thumbnail'] = get_user_option('player_use_thumbnail');
			if ( empty($item['player_use_thumbnail']) ) {
				unset($item['player_use_thumbnail']);
			}
		}

		//handle primary content
		$url = get_post_meta($post->ID, 'libsyn-new-media-media', true);
		if ( strpos($url, 'libsyn-ftp-') !== false ) $content_id = str_replace('http:', '', str_replace('https:', '', str_replace('/', '', str_replace('libsyn-ftp-', '', $url))));
		if ( strpos($url, 'libsyn-upload-') !== false ) $content_id = str_replace('http:', '', str_replace('https:', '', str_replace('/', '', str_replace('libsyn-upload-', '', $url))));
		if ( isset($content_id) && is_numeric($content_id) ) { //then is ftp/unreleased
			$item['primary_content_id'] = intval($content_id);
		} elseif ( !empty($url) ) { //is regular
			$item['primary_content_url'] = $sanitize->url_raw($url);
		} else {
			//throw new Exception('Primary media error, please check your Libsyn settings.');
		}

		//handle simple download
		$simple_download =  get_post_meta($post->ID, 'libsyn-post-episode-simple-download', true);
		if ( empty($simple_download) || $simple_download === "available" ) {
			$simple_download = 'available';
			$item['always_available'] = 'true';
			update_post_meta($post->ID, 'libsyn-post-episode-simple-download', $simple_download);
		} elseif ( $simple_download === "release_date" ) {
			$item['always_available'] = 'false';
			update_post_meta($post->ID, 'libsyn-post-episode-simple-download', $simple_download);
		}

		//handle is draft
		if ( $isDraft ) {
			$item['is_draft'] = 'true';
		} else {
			$item['is_draft'] = 'false';
		}
		update_post_meta($post->ID, 'libsyn-is_draft', $item['is_draft']);

		//is this post an update or new?
		$wp_libsyn_item_id = get_post_meta( $post->ID, 'libsyn-item-id', true );
		$isUpdatePost = (empty($wp_libsyn_item_id)) ? false : true;
		$update_release =  get_post_meta($post->ID, 'libsyn-post-update-release-date', true);
		$isReRelease = ($update_release === 'isLibsynUpdateReleaseDate');


		if ( $isUpdatePost ) { //update post
			$item['item_id'] = $wp_libsyn_item_id;
			if ( $isSchedule ) {
				$releaseDate = $post->post_date_gmt;
			} else {
				if ( $isReRelease ) {
					$releaseDate = 'now';
				} else {
					$releaseDate =  $sanitize->mysqlDate(get_post_meta($post->ID, 'libsyn-release-date', true));
				}
			}
		} else { //new release
			if ( $isSchedule ) {
				$releaseDate = $post->post_date_gmt;
				$item['always_available'] = 'true';
			} else {
				$releaseDate = $sanitize->mysqlDate(get_post_meta($post->ID, 'libsyn-release-date', true));
				if ( $isReRelease) {
					$releaseDate = 'now';
				} elseif ( !empty($releaseDate) ) {
					$releaseDate = $releaseDate;
				} else {
					$releaseDate = 'now';
				}

			}
		}

		//handle update id3
		$update_id3 =  get_post_meta($post->ID, 'libsyn-post-episode-update-id3', true);
		if ( !empty($update_id3) && $update_id3 == "isLibsynUpdateId3" ) {
			$item['update_id3'] = 'true';
		} else {
			$item['update_id3'] = 'false';
		}

		//handle edit item
		$wp_libsyn_edit_item_id = get_post_meta( $post->ID, 'libsyn-edit-item-id', true );
		if ( !empty($wp_libsyn_edit_item_id) ) {
			$item['item_id'] = intval($wp_libsyn_edit_item_id);
			$isUpdatePost = true;
		}
		//set custom_permalink_url
		$item['custom_permalink_url'] = get_permalink( $post->ID );

		//get destinations & handle destinations
		if ( $api instanceof \Libsyn\Api ) {
			$destinations = $plugin->getDestinations($api);
		} else {
			$destinations = false;
		}

		$item['releases'] = array();
		if ( !empty($destinations->destinations) ) {
			foreach($destinations->destinations as $destination) {
				if ( $destination->destination_type!=='WordPress' ) {
					$item['releases'][] = array(
						'destination_id'	=>	$destination->destination_id,
						'release_date'		=>	$releaseDate,
						//'expiration_date'			=> $expiresDate, //TODO: Perhaps add expires for posts eventually (optional feature)
					);
				}
			}
		}

		//handle saved destination releases
		if ( $isUpdatePost ) {
			$libsynPost = $plugin->getEpisode(array( 'show_id' => $item['show_id'], 'item_id' => $item['item_id']));
			if ( !empty($libsynPost->_embedded->post->releases) ) {
				update_post_meta($post->ID, 'libsyn-destination-releases', $libsynPost->_embedded->post->releases);
			}
			$savedDestinations = get_post_meta($post->ID, 'libsyn-destination-releases', true);
		} else {
			$savedDestinations = false;
		}
		//handle advanced destinations
		$advanced_destinations = get_post_meta($post->ID, 'libsyn-post-episode-advanced-destination-form-data', true );
		$advanced_destinations_enabled = get_post_meta($post->ID, 'libsyn-post-episode-advanced-destination-form-data-input-enabled', true );
		if ( !empty($advanced_destinations_enabled) && ( $advanced_destinations_enabled == 'true' || $advanced_destinations_enabled == true || $advanced_destinations_enabled == 1 ) && !empty($advanced_destinations) && ( $advanced_destinations !== '[]' ) ) {
			$advanced_destinations = json_decode($advanced_destinations);
			if ( is_object($advanced_destinations) || is_array($advanced_destinations) ) {
				unset($item['releases']); //we have data unset current set releases
				$item['releases'] = array();

				//First loop: set the release elements to catch data for.
				foreach($advanced_destinations as $property => $value) {
					if ( ( ( function_exists('mb_strpos') && mb_strpos($property, 'libsyn-advanced-destination-checkbox-') !== false ) || ( strpos($property, 'libsyn-advanced-destination-checkbox-') !== false ) ) && $value === 'checked' ) {//use only checked elements
						$destination_id = intval(str_replace('libsyn-advanced-destination-checkbox-', '', $property));
						$working_release = array();
						$working_release['destination_id'] = $destination_id;
						//Second loop: fill in the release elements which are checked.
						foreach($advanced_destinations as $prop => $val) {
							//handle form-table elements
							switch($prop) {
								case 'set_release_scheduler_advanced_release_lc__'.$destination_id.'-1':
									if ( $val === 'checked' ) {
										//release_date publish with the previous release date
										// $working_release['release_date'] = null; //set default
										if ( $val === 'checked' && !empty($savedDestinations) && ($isUpdatePost && $isReRelease) ) {
											foreach($savedDestinations as $working_savedDestination) {
												if ( !empty($working_savedDestination->destination_id) && $working_savedDestination->destination_id == $destination_id ) {//found saved destination
													$working_release['release_date'] = $working_savedDestination->release_date;
												}
											}
										}
									}
								case 'set_release_scheduler_advanced_release_lc__'.$destination_id.'-0':
									//release_date publish immediately checkbox
									$working_release['release_date'] = $releaseDate;
									break;
								case 'set_release_scheduler_advanced_release_lc__'.$destination_id.'-0':
									if ( $val === 'checked' ) {
										//release_date publish immediately checkbox
										$working_release['release_date'] = $releaseDate;
									}
								case 'set_release_scheduler_advanced_release_lc__'.$destination_id.'-2':
									//release_date set new release date
									if ( $val === 'checked' ) {
										if ( is_array($advanced_destinations) ) {
											if ( isset($advanced_destinations['release_scheduler_advanced_release_lc__'.$destination_id.'_date']) && isset($advanced_destinations['release_scheduler_advanced_release_lc__'.$destination_id.'_time_select_select-element']) ) {
												$time_of_day = date('H:i:s', strtotime($advanced_destinations['release_scheduler_advanced_release_lc__'.$destination_id.'_time_select_select-element']));
												$working_release['release_date'] = date('Y-m-d H:i:s', strtotime($advanced_destinations['release_scheduler_advanced_release_lc__'.$destination_id.'_date'].' '.$time_of_day));
												$working_release['release_date'] = ( !empty($working_release['release_date']) ) ? get_gmt_from_date($working_release['release_date']) : null;
											}
										} elseif ( is_object($advanced_destinations) ) {
											if ( isset($advanced_destinations->{'release_scheduler_advanced_release_lc__'.$destination_id.'_date'}) && isset($advanced_destinations->{'release_scheduler_advanced_release_lc__'.$destination_id.'_time_select_select-element'}) ) {
												$time_of_day = date('H:i:s', strtotime($advanced_destinations->{'release_scheduler_advanced_release_lc__'.$destination_id.'_time_select_select-element'}));
												$working_release['release_date'] = date('Y-m-d H:i:s', strtotime($advanced_destinations->{'release_scheduler_advanced_release_lc__'.$destination_id.'_date'}.' '.$time_of_day));
												$working_release['release_date'] = ( !empty($working_release['release_date']) ) ? get_gmt_from_date($working_release['release_date']) : null;
											}
										}
									}
									break;
								case 'set_expiration_scheduler_advanced_release_lc__'.$destination_id.'-0':
									//release_date publish immediately checkbox
									//do nothing will never expire
									break;
								case 'set_expiration_scheduler_advanced_release_lc__'.$destination_id.'-1':
									if ( $val === 'checked' && !empty($savedDestinations) ) {
										foreach($savedDestinations as $working_savedDestination) {
											if ( !empty($working_savedDestination->destination_id) && $working_savedDestination->destination_id == $destination_id ) {//found saved destination
												if ( !empty($working_savedDestination->expiration_date) ) {
													$working_release['expiration_date'] = $working_savedDestination->expiration_date;
												}
											}
										}
									}
									break;
								case 'set_expiration_scheduler_advanced_release_lc__'.$destination_id.'-2':
									if ( $val === 'checked' ) {
										if ( is_array($advanced_destinations) ) {
											if ( isset($advanced_destinations['expiration_scheduler_advanced_release_lc__'.$destination_id.'_date']) && isset($advanced_destinations['expiration_scheduler_advanced_release_lc__'.$destination_id.'_time_select_select-element']) ) {
												$time_of_day = date('H:i:s', strtotime($advanced_destinations['expiration_scheduler_advanced_release_lc__'.$destination_id.'_time_select_select-element']));
												$working_release['expiration_date'] = date('Y-m-d H:i:s', strtotime($advanced_destinations['expiration_scheduler_advanced_release_lc__'.$destination_id.'_date'].' '.$time_of_day));
												$working_release['expiration_date'] = ( !empty($working_release['expiration_date']) ) ? get_gmt_from_date($working_release['expiration_date']) : null;
											}
										} elseif ( is_object($advanced_destinations) ) {
											if ( isset($advanced_destinations->{'expiration_scheduler_advanced_release_lc__'.$destination_id.'_date'}) && isset($advanced_destinations->{'expiration_scheduler_advanced_release_lc__'.$destination_id.'_time_select_select-element'}) ) {
												$time_of_day = date('H:i:s', strtotime($advanced_destinations->{'expiration_scheduler_advanced_release_lc__'.$destination_id.'_time_select_select-element'}));
												$working_release['expiration_date'] = date('Y-m-d H:i:s', strtotime($advanced_destinations->{'expiration_scheduler_advanced_release_lc__'.$destination_id.'_date'}.' '.$time_of_day));
												$working_release['expiration_date'] = ( !empty($working_release['expiration_date']) ) ? get_gmt_from_date($working_release['expiration_date']) : null;
											}
										}
									}
									break;
								default:
									//do nothing
							}
						}
						if ( !empty($working_release) && is_array($working_release) && !empty($working_release['release_date']) ) {
							$item['releases'][] = $working_release;
						}
						unset($working_release);
					}
				}
			}
		} elseif ( $isUpdatePost && $isReRelease ) {
			$item['releases'] = self::getSavedReleases($post->ID);
		}

		//check to make sure release_date is set or releases
		if ( empty($item['releases']) && empty($item['release_date']) ) {
			$item['release_date'] = $releaseDate;
		} elseif ( !empty($item['releases']) ) {
			if ( $isUpdatePost && $isReRelease ) {
				$savedReleases = self::getSavedReleases($post->ID);
				$savedReleaseDestinationIds = array();
				if ( !empty($savedReleases) ) {
					for($x=0; $x < count($savedReleases); $x++) {
						if ( !empty($savedReleases[$x]['destination_id']) && !empty($savedReleases[$x]['release_date']) ) {
							$savedReleaseDestinationIds[] = $savedReleases[$x]['destination_id'];
							foreach($item['releases'] as $working_release) {
								if ( $savedReleases[$x]['destination_id'] == $working_release['destination_id'] ) {
									$savedReleases[$x]['release_date'] = $working_release['release_date'];
									if ( !empty($working_release['expiration_date']) ) {
										$savedReleases[$x]['expiration_date'] = $working_release['expiration_date'];
									}
								}
							}
						}

					}

					$unmatchedReleases = array();
					if ( !empty($savedReleaseDestinationIds) ) {
						foreach($item['releases'] as $working_release) {
							if ( !in_array($working_release['destination_id'], $savedReleaseDestinationIds) ) {
								$unmatchedReleases[] = $working_release;
							}
						}
						if ( !empty($unmatchedReleases) && is_array($unmatchedReleases) ) {
								$savedReleases = $savedReleases + $unmatchedReleases;
						}
					}

					$item['releases'] = $savedReleases;
				}
			}
		}
		//run post
		if ( $plugin->hasLogger ) $plugin->logger->info( "Post:\tSubmitting Post to API" );
		if ( $api instanceof \Libsyn\Api ) {
			//bug fix for required fields
			if ( empty($item['item_title']) ) {
				$item['item_title'] = ' ';
			}
			if ( empty($item['item_body']) ) {
				$item['item_body'] = ' ';
			}
			$libsyn_post = $plugin->postPost($api, array_filter($item));
		} else {
			$libsyn_post = false;
		}
		if ( $libsyn_post !== false ) {
			self::updatePost($post, $libsyn_post, $isUpdatePost);
		} else { add_post_meta($post->ID, 'libsyn-post-error', 'true', true); }
	}

	/**
	 * Temp change global state of WP to fool shortcode
	 *
	 * @since 1.0.1.1
	 * @param string $code name of the shortcode
	 * @param string $content
	 *
	 * @return string content with shortcode striped
	 */
	public static function stripShortcode( $code, $content ) {
		global $shortcode_tags;

		$stack = $shortcode_tags;
		if ( $code == "all" ) $shortcode_tags = array();
			else $shortcode_tags = array($code => 1);

		$content = strip_shortcodes($content);

		$shortcode_tags = $stack;
		return $content;
	}

	/**
	 * Just updates the WP_Post after a successful Libsyn Episode Post
	 *
	 * @since 1.0.1.1
	 * @param WP_Post $post
	 * @param object $libsyn_post
	 *
	 * @return bool
	 */
	public static function updatePost( $post, $libsyn_post, $isUpdatePost ) {
		/* Set Vars */
		global $wpdb;
		$postEditorType = self::getPostEditorType();
		if ( empty($post->ID) ) return false; //post id should never be empty back out quick

		/* Update Post Meta */
		if ( !empty($libsyn_post->show_id) ) {
			update_post_meta($post->ID, 'libsyn-show-id', intval($libsyn_post->show_id));
		}
		update_post_meta($post->ID, 'libsyn-item-id', $libsyn_post->id);
		update_post_meta($post->ID, 'libsyn-release-date', $libsyn_post->release_date);
		update_post_meta($post->ID, 'libsyn-destination-releases', $libsyn_post->releases);
		update_post_meta($post->ID, 'libsyn-episode-embedurl', $libsyn_post->url);
		update_post_meta($post->ID, 'libsyn-post-episode-primary_content_url', $libsyn_post->primary_content_url);

		/* Get Player Shortcode and handle save post shortcode */
		if ( !empty($libsyn_post->url) ) {
			$shortcode_atts = array(
				'src' => $libsyn_post->url
			);
			if ( !empty($libsyn_post->id) ) $shortcode_atts['libsyn-item-id'] = $libsyn_post->id;
			if ( !empty($libsyn_post->release_date) ) $shortcode_atts['libsyn-release-date'] = $libsyn_post->release_date;
			if ( !empty($libsyn_post->releases) ) $shortcode_atts['libsyn-destination-releases'] = $libsyn_post->releases;
			if ( !empty($libsyn_post->primary_content_url) ) $shortcode_atts['libsyn-post-episode-primary_content_url'] = $libsyn_post->primary_content_url;
		} else {
			$shortcode_atts = array();
		}
		$podcast_shortcode_text = self::getPlayerShortcode($post->ID, $shortcode_atts);

		//parse download link out of shortcode
		$download_link = ''; //default
		$playerPlacement = 'bottom'; //default
		if ( function_exists('shortcode_parse_atts') ) {
			$shortcodeAtts = shortcode_parse_atts($podcast_shortcode_text);
			if ( !empty($shortcodeAtts['use_download_link']) && $shortcodeAtts['use_download_link'] == "true" ) {
				if ( !empty($shortcodeAtts['download_link_text']) ) {
					$download_link = '<br /><br /><a class="libsyn-download-link" href ="' . $libsyn_post->primary_content_url . '" target="_blank">' . $shortcodeAtts['download_link_text'] . '</a><br />';
				}
			}

			if ( !empty($shortcodeAtts['placement']) ) {
				$playerPlacement = $shortcodeAtts['placement'];
			}
		}

		//check integration
		$prefixBlock = '';
		$suffixBlock = '';
		$integration = new Service\Integration();
		if ( $integration instanceof \Libsyn\Service\Integration ) {
			$checkDivi = $integration->checkPlugin('divi-builder');
			if ( $checkDivi ) {
				//divi builder found add shortcode block container
				$prefixBlock = '[et_pb_section bb_built="1" fullwidth="on" specialty="off" prev_background_color="#000000"][et_pb_fullwidth_code admin_label="Libsyn Podcast Post"]';
				$suffixBlock = '[/et_pb_fullwidth_code][/et_pb_section]';
			}
		}

		//update post db
		if ( !$isUpdatePost ) {
			if ( $playerPlacement === 'top' ) {
				$postBlock = $prefixBlock . $podcast_shortcode_text . $download_link . $suffixBlock . wp_kses_post(self::stripShortcode('podcast', $post->post_content));

				if ( $postEditorType == 'classic' ) {
					$wpdb->update(
						$wpdb->prefix . 'posts',
						array(
							'post_content' => $postBlock,
							'post_modified' => date("Y-m-d H:i:s"),
							'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
						),
						array('ID' => $post->ID)
					);
				}
			} else {
				$postBlock = wp_kses_post(self::stripShortcode('podcast', $post->post_content)) . $prefixBlock . $podcast_shortcode_text . $download_link . $suffixBlock;

				if ( $postEditorType == 'classic' ) {
					$wpdb->update(
						$wpdb->prefix . 'posts',
						array(
							'post_content' => $postBlock,
							'post_modified' => date("Y-m-d H:i:s"),
							'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
						),
						array('ID' => $post->ID)
					);
				}
			}
		} else {
			//shortcode stuff
			$shortcode_pattern = get_shortcode_regex();
			preg_match('/'.$shortcode_pattern.'/s', $post->post_content, $matches);
			if ( is_array($matches) ) {
				if ( isset($matches[2]) && $matches[2] == 'podcast' ) { // matches (has player shortcode)
					$post_content_text = $post->post_content;
					$post_content_text = preg_replace('#<a class="libsyn-download-link"(.*?)</a>#', '', str_replace('<br /><br /><a class="libsyn-download-link"', '<a class="libsyn-download-link"', $post_content_text));

					$postBlock = preg_replace('#(?<=[podcast).+(?=])#s', '', $post_content_text);
					$postBlock = str_replace($matches[0], $prefixBlock . $podcast_shortcode_text . $download_link . $suffixBlock, $postBlock);

					if ( $postEditorType == 'classic' ) {
						$wpdb->update(
							$wpdb->prefix . 'posts',
							array(
								'post_content' => $postBlock,
								'post_modified' => date("Y-m-d H:i:s"),
								'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
							),
							array('ID' => $post->ID)
						);
					}
				} elseif ( !isset($matches[2]) ) { //somehow doesn't have the player shortcode and is update
					$postBlock = preg_replace('/' . preg_quote('<!-- START '.$post->ID.' Download Link -->').'.*?' .preg_quote('<!-- END '.$post->ID.' Download Link -->') . '/', '', $post->post_content);
					$postBlock = $prefixBlock . $podcast_shortcode_text . $download_link . wp_kses_post(self::stripShortcode('podcast', $postBlock)) . $suffixBlock;

					if ( $playerPlacement === 'top' ) {
						if ( $postEditorType == 'classic' ) {
							$wpdb->update(
								$wpdb->prefix . 'posts',
								array(
									'post_content' => $postBlock,
									'post_modified' => date("Y-m-d H:i:s"),
									'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
								),
								array('ID' => $post->ID)
							);
						}
					} else {
						$postBlock = wp_kses_post(self::stripShortcode('podcast', $postBlock)) . $prefixBlock . $podcast_shortcode_text . $download_link . $suffixBlock;

						if ( $postEditorType == 'classic' ) {
							$wpdb->update(
								$wpdb->prefix . 'posts',
								array(
									'post_content' => $postBlock,
									'post_modified' => date("Y-m-d H:i:s"),
									'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
								),
								array('ID' => $post->ID)
							);
						}
					}
				}
			}
		}

		$plugin = new Service();
		$current_user_id = $plugin->getCurrentUserId();
		$api = $plugin->retrieveApiById($current_user_id);
		if ( !empty($libsyn_post->show_id) ) {
			if ( $api instanceof \Libsyn\Api ) {
				$show = $plugin->getShow($api, $libsyn_post->show_id)->{'user-shows'};
				if ( !empty($show) ) {//matched show
					if ( !empty($show->{'feed_url'}) ) {
						update_post_meta($post->ID, 'libsyn-show-feed_url', $show->{'feed_url'});
					}
					if ( !empty($show->{'show_title'}) ) {
						update_post_meta($post->ID, 'libsyn-show-show_title', $show->{'show_title'});
					}
				} else {//log error
					if ( $plugin->hasLogger ) $plugin->logger->error("Post:\tProblem saving libsyn-show-feed_url\t");
				}
			}
		}
		//TODO: Add social tags and additional post meta here
	}

	/**
	 *  @brief Gets Player Shortcode into text form
	 *  generated from post meta or by passing $attributes
	 *
	 *  @since 1.2.1
	 *  @param int $postId Post Id
	 *  @return string Player Shortcode
	 *
	 *  @details You may pass attributes or grabs post meta (default)
	 * 	Also updates post meta with built shortcode
	 *  Note: $atts matches the libsyn_unqprfx_embed_shortcode keys
	 */
	public static function getPlayerShortcode($postId, $attributes = array()) {
		//TODO: Handle error for below
		if ( empty($postId) ) return ''; //backout quick

		//get required $atts
		$atts = array();

		// Handles either: libsyn-item-id, libsyn_item_id
		if ( isset($attributes['libsyn-item-id']) ) {
			$atts['libsyn_item_id'] = $attributes['libsyn-item-id'];
		} elseif ( isset($attributes['libsyn_item_id']) ) {
			$atts['libsyn_item_id'] = $attributes['libsyn_item_id'];
		} else {
			$atts['libsyn_item_id'] = get_post_meta($postId, 'libsyn-item-id', true);
		}

		// Handles either: libsyn-episode-embedurl, libsyn_episode_embedurl, src
		if ( isset($attributes['libsyn-episode-embedurl']) ) {
			$atts['src'] = $attributes['libsyn-episode-embedurl'];
		} elseif( isset($attributes['libsyn_episode_embedurl']) ) {
			$atts['src'] = $attributes['libsyn_episode_embedurl'];
		} elseif( isset($attributes['src']) ) {//NOTE: this could cause potential problems
			$atts['src'] = $attributes['src'];
		} else {
			$atts['src'] = get_post_meta($postId, 'libsyn-episode-embedurl', true);
		}

		// Handles either: libsyn-post-episode-primary_content_url, primary_content_url
		if ( isset($attributes['libsyn-post-episode-primary_content_url']) ) {
			$atts['primary_content_url'] = $attributes['libsyn-post-episode-primary_content_url'];
		} elseif ( isset($attributes['primary_content_url']) ) {
			$atts['primary_content_url'] = $attributes['primary_content_url'];
		} else {
			$atts['primary_content_url'] = get_post_meta($postId, 'libsyn-post-episode-primary_content_url', true);
		}

		if ( !empty($atts['src']) ) {//src should never be empty at this point
			$utilities = new \Libsyn\Utilities();
			$libsynPostUrlParams = $utilities->getDispatch($atts['src']);
		} else {
			//TODO: Log error
		}

		//grab player settings
		$atts['theme']	= ( isset($attributes['libsyn-post-episode-player_use_theme']) ) ? $attributes['libsyn-post-episode-player_use_theme'] : '';
		$atts['theme']	= ( empty($atts['theme']) ) ? get_post_meta($postId, 'libsyn-post-episode-player_use_theme', true) : $atts['theme'];
		$atts['theme']	= ( empty($atts['theme']) && !empty($libsynPostUrlParams['theme']) ) ? $libsynPostUrlParams['theme'] : $atts['theme'];
		$atts['height']	= ( isset($attributes['libsyn-post-episode-player_height']) ) ? $attributes['libsyn-post-episode-player_height'] : '';
		$atts['height']	= ( empty($atts['height']) ) ? get_post_meta($postId, 'libsyn-post-episode-player_height', true) : $atts['height'];
		$atts['height']	= ( empty($atts['height']) && !empty($libsynPostUrlParams['height']) ) ? $libsynPostUrlParams['height'] : $atts['height'];
		$atts['width']	= '100%'; //always 100%
		$atts['placement'] = ( isset($attributes['libsyn-post-episode-player_placement']) ) ? $attributes['libsyn-post-episode-player_placement'] : '';
		$atts['placement'] = ( empty($atts['placement']) ) ? get_post_meta($postId, 'libsyn-post-episode-player_placement', true) : $atts['placement'];
		if ( empty($atts['placement']) ) $placementUserOption = get_user_option('libsyn-podcasting-player_placement');
		$atts['placement'] = ( empty($atts['placement']) && !empty($placementUserOption) ) ? $placementUserOption : $atts['placement'];
		$atts['custom_color']	= ( isset($attributes['libsyn-post-episode-player_custom_color']) ) ? $attributes['libsyn-post-episode-player_custom_color'] : '';
		$atts['custom_color']	= ( empty($atts['custom_color']) ) ? get_post_meta($postId, 'libsyn-post-episode-player_custom_color', true) : $atts['custom_color'];
		$atts['custom_color']	= ( empty($atts['custom_color']) && !empty($libsynPostUrlParams['custom-color']) ) ? $libsynPostUrlParams['custom-color'] : $atts['custom_color'];

		//check for download link
		$atts['use_download_link'] = ( isset($attributes['libsyn-post-episode-player_use_download_link']) ) ? $attributes['libsyn-post-episode-player_use_download_link'] : '';
		$atts['use_download_link'] = ( empty($atts['use_download_link']) ) ? get_post_meta($postId, 'libsyn-post-episode-player_use_download_link', true) : $atts['use_download_link'];
		$playerUseDownloadLink = ( !empty($atts['use_download_link']) && ( $atts['use_download_link'] === 'use_download_link' || $atts['use_download_link'] == 'true' || $atts['use_download_link'] === true ) ) ? true : false;
		$atts['download_link_text'] = ( isset($attributes['libsyn-post-episode-player_use_download_link_text']) ) ? $attributes['libsyn-post-episode-player_use_download_link_text'] : '';
		$atts['download_link_text'] = ( empty($atts['download_link_text']) ) ? get_post_meta($postId, 'libsyn-post-episode-player_use_download_link_text', true) : '';

		//build output
		$defaults = libsyn_unqprfx_shortcode_defaults();
		if ( function_exists('shortcode_atts') && ( !empty($defaults) && is_array($defaults) ) ) {
			$shortcodeAtts = shortcode_atts($defaults, array_filter($atts));
			if ( !empty($shortcodeAtts) && is_array($shortcodeAtts) ) {
				$podcast_shortcode_text = '[podcast ';
				foreach ( $shortcodeAtts as $key => $val ) {
					$podcast_shortcode_text .= $key . '="' . $val . '" ';
				}
				$podcast_shortcode_text .= '/]';
			} else {
				$podcast_shortcode_text = '';
			}
		}

		if( empty($podcast_shortcode_text) ) {//just for good measure default to manual build
			$blockExtraAtts = '';
			if ( !empty($atts['custom_color']) ) {
				$blockExtraAtts .= ' custom_color="' . $atts['custom_color'] . '"';
			}
			$blockExtraAtts .= ' primary_content_url="' . $atts['primary_content_url'] . '"';
			$blockExtraAtts .= ' placement="' . $atts['placement'] . '"';
			if ( !empty($atts['libsyn_item_id']) ) {
				$blockExtraAtts .= ' libsyn_item_id="' . $atts['libsyn_item_id'] . '"';
			}
			if ( $playerUseDownloadLink ) {
				if ( !empty($atts['download_link_text']) ) {
					//do nothing
				} else {
					$atts['download_link_text'] = 'Click here to download the Episode!';
				}
				$blockExtraAtts .= ' download_link_text="' . $atts['download_link_text'] . '"';
				$blockExtraAtts .= ' use_download_link="true"';
				$atts['use_download_link'] = "true";
			}

			$podcast_shortcode_text = '[podcast src="' . $atts['src'] . '" height="' . $atts['height'] . '" width="' . $atts['height'] . '" placement="' . $atts['placement'] . '" theme="' .$atts['theme'] . '"' . $blockExtraAtts. ' /]';
		}

		//update post meta
		update_post_meta($postId, 'libsyn-episode-shortcode', $podcast_shortcode_text);

		//return the shortcode text
		return $podcast_shortcode_text;
	}

	/**
	 * Handles WP callback to send variable to trigger AJAX response.
	 *
	 * @since 1.2.1
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function plugin_add_trigger_update_libsyn_postmeta($vars) {
		$vars[] = 'update_libsyn_postmeta';
		return $vars;
	}

	/**
	 * Handles WP callback to send variable to trigger AJAX response.
	 *
	 * @since 1.0.1.1
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function plugin_add_trigger_load_form_data($vars) {
		$vars[] = 'load_libsyn_media';
		return $vars;
	}

	/**
	 * Handles WP callback to send variable to trigger AJAX response.
	 *
	 * @since 1.0.1.1
	 * @param array $vars
	 *
	 * @return mixed
	 */
	public static function plugin_add_trigger_remove_ftp_unreleased( $vars ) {
		$vars[] = 'remove_ftp_unreleased';
		return $vars;
	}

	/**
	 * Handles WP callback to send variable to trigger AJAX response.
	 *
	 * @since 1.0.1.1
	 * @param array $vars
	 *
	 * @return mixed
	 */
	public static function plugin_add_trigger_load_player_settings( $vars ) {
		$vars[] = 'load_player_settings';
		return $vars;
	}


	/**
	 * Handles retrieval of player shortcode via ajax
	 *
	 * @since 1.2.1
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function plugin_add_trigger_libsyn_player_shortcode($vars) {
		$vars[] = 'libsyn_player_shortcode';
		return $vars;
	}

	/**
	 *  @brief Retrieves Player Shortcode via ajax
	 *
	 *  @return JSON encoded response with player shortcode
	 *
	 *  @details Required to pass post id
	 *  Optional to pass $attributes array
	 *  (will load post meta by default)
	 */
	public static function getPlayerShortcodeAjax() {
		$error = false;
		$checkUrl  = self::getCurrentPageUrl();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}
		if ( intval($urlParams['libsyn_player_shortcode']) === 1 ) {
			if ( !empty($urlParams['post_id']) ) {
				$current_post_id = $urlParams['post_id'];
			} elseif ( !empty($urlParams['current_post_id']) ) {
				$current_post_id = $urlParams['current_post_id'];
			} elseif ( function_exists('get_the_ID') ) {
				$current_post_id = get_the_ID();
			}

			if ( !empty($urlParams['attributes']) ) {
				$attributes = $urlParams['attributes'];
			} else {
				$attributes = array();
			}

			if ( !empty($current_post_id) ) {
				$shortcode = self::getPlayerShortcode($current_post_id, $attributes);
			} else {
				//TODO: handle log error
				$error = true;
			}

			//set output
			header('Content-Type: application/json');
			if ( !$error ) echo json_encode($shortcode);
				else echo json_encode(array());
			exit;
		}
	}

	/**
	 * Handle ajax page for loading post page form data
	 *
	 * @since 1.0.1.1
	 * @return mixed
	 */
	public static function loadFormData( $params = array() ) {
		$libsyn_error = true;
		if ( empty($params) ) {
			$checkUrl  = self::getCurrentPageUrl();
			if ( function_exists('wp_parse_str') ) {
				wp_parse_str($checkUrl, $urlParams);
			} else {
				parse_str($checkUrl, $urlParams);
			}
		} else {
			$urlParams = $params;
		}

		if ( intval($urlParams['load_libsyn_media']) == 1 && ( current_user_can( 'upload_files' ) ) && ( current_user_can( 'edit_posts' ) ) ) {
			global $wpdb;
			$libsyn_error = false;
			$plugin = new \Libsyn\Service();
			$current_user_id = $plugin->getCurrentUserId();
			$api = $plugin->retrieveApiById($current_user_id);
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'libsyn/ftp-unreleased'));
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'audio/ftp-unreleased'));
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'video/ftp-unreleased'));

			$wpdb->get_results($wpdb->prepare("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_value LIKE %s", "/libsyn/ftp-unreleased%"));
			if ( $api instanceof \Libsyn\Api ) {
				$isRefreshExpired = $api->isRefreshExpired();
				if ( $isRefreshExpired ) { //refresh expired attempt to refresh
					$refreshApi = $api->refreshToken();
				} else { //nothing to do token looks good
					$refreshApi = true;
				}
				if ( $refreshApi ) { //successfully refreshed
					/* Remove and add FTP/Unreleased Media */
					$get_ftpunreleased = $plugin->getFtpUnreleased($api);
					if ( !empty($get_ftpunreleased) ) {
						$ftp_unreleased = $get_ftpunreleased->{'ftp-unreleased'};
					}
					if ( !empty($ftp_unreleased) ) {
						foreach($ftp_unreleased as $media) {
							// We need to make sure we are working with only audio/video files...
							if ( ( strpos($media->mime_type, 'audio') !== false ) || ( strpos($media->mime_type, 'video') !== false ) ) {

								//for new versions of wordpress - handle media info in metadata
								if ( strpos($media->mime_type, 'video') !== false ) {

								} elseif ( strpos($media->mime_type, 'audio') !== false ) {

								} else {
									//neither audio or video
								}
								$date = ( function_exists('get_the_date') ) ? get_the_date('Y-m-d H:i:s') : date('Y-m-d H:i:s');
								$file_name = explode('.', $media->file_name);
								$mime_type = explode('/', $media->mime_type);
								$data = array(
										'post_author'			=>	$plugin->getCurrentUserId(),
										'post_date'				=>	$date,
										'post_date_gmt'			=>	get_gmt_from_date($date),
										'post_content'			=>	'Libsyn FTP/Unreleased Media: '.$media->file_name,
										'post_title'			=>	$file_name[0],
										'post_excerpt'			=>	'',
										'post_status'			=>	'inherit',
										'comment_status'		=>	'open',
										'ping_status'			=>	'closed',
										'post_password'			=>	'',
										'post_name'				=>	'libsyn-ftp-'.$media->content_id,
										'to_ping'				=>	'',
										'pinged'				=>	'',
										'post_modified'			=>	$date,
										'post_modified_gmt'		=>	get_gmt_from_date($date),
										'post_content_filtered'	=>	'',
										'post_parent'			=>	0,
										'guid'					=>	$media->file_name,
										'menu_order'			=>	0,
										'post_type'				=>	'attachment',
										'post_mime_type'		=>	'libsyn/ftp-unreleased',
										'comment_count'			=>	0,
								);
								//$wpdb->insert($wpdb->prefix . 'posts', $data);
								$post_id = wp_insert_post($data, false);

								//add post meta
								add_post_meta($post_id, '_wp_attached_file', '/libsyn/ftp-unreleased/'.$media->file_name);
							}
						}
					}
					/* Get categories and send output on success */
					$get_categories = $plugin->getCategories($api);
					if ( !empty($get_categories) ) {
						$categories = $get_categories->{'categories'};
					} else {
						$categories = array();
					}
					if ( !is_array($categories) ) $categories = array($categories);
					$json = array();
					foreach($categories as $category)
						if ( isset($category->item_category_name) ) $json[] = $category->item_category_name;
				} else { $libsyn_error = true; }
			} else { $libsyn_error = true; }

			if ( empty($params) ) {
				//set output
				header('Content-Type: application/json');
				if ( !$libsyn_error ) echo json_encode($json);
					else echo json_encode(array());
				exit;
			} else {
				if ( !$libsyn_error ) {
					return $json;
				} else {
					return array();
				}
			}

		}
	}

    /**
     * Clears post meta and posts for ftp/unreleased data.
     * @since 1.0.1.1
     *
     * @return void
     */
	public static function removeFTPUnreleased() {
		global $wpdb;
		$libsyn_error = true;
		$checkUrl  = self::getCurrentPageUrl();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}
		if ( intval($urlParams['remove_ftp_unreleased']) === 1 ) {
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'libsyn/ftp-unreleased'));
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'audio/ftp-unreleased'));
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'video/ftp-unreleased'));
			$wpdb->get_results($wpdb->prepare("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_value LIKE %s", "/libsyn/ftp-unreleased%"));
			$libsyn_error = false;

			//set output
			header('Content-Type: application/json');
			if ( !$libsyn_error ) echo json_encode(true);
				else echo json_encode(false);
			exit;
		}
		return;
	}

    /**
     * Ajax add post meta vars (currently used for updating media urls for block editor)
     * @since 1.2.1
     *
     * @return void
     */
	public static function updateLibsynPostmeta() {
		$libsyn_error = true;
		$checkUrl  = self::getCurrentPageUrl();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}
		if ( intval($urlParams['update_libsyn_postmeta']) === 1 ) {
			if ( !empty($urlParams['post_id']) ) {
				$sanitize = new \Libsyn\Service\Sanitize();
				if ( !empty($urlParams['meta_key']) && !empty($urlParams['meta_value']) ) {
					$metaKey = $sanitize->text($urlParams['meta_key']);
					$postId = $sanitize->numeric($urlParams['post_id']);
					if ( !empty($metaKey) && !empty($postId) ) {
						switch ( $urlParams['meta_key'] ) {
							case 'libsyn-new-media-media':
								$val = $sanitize->text($urlParams['meta_value']);
								break;

							case 'libsyn-new-media-image':
								$val = $sanitize->url_raw($urlParams['meta_value']);
								break;
						}
						//looks good update postmeta
						if ( !empty($val) ) {
							$libsyn_error = false;
							update_post_meta($postId, $metaKey, $val);
						}
					}
				}
			}
			//set output
			header('Content-Type: application/json');
			if ( !$libsyn_error ) echo json_encode(true);
				else echo json_encode(false);
			exit;
		}
		return;
	}

    /**
     * Loads the saved destinations data to set the releases based
	 * on Previously published post data
	 *
     * @since 1.0.1.8
     *
	 * @param mixed $postId (numeric) post ID, will try to evaluate the ID if in "the loop"
	 *
     * @return mixed
     */
	public static function getSavedReleases($postId = null) {
		if ( empty($postId) ) {
			$postId = ( function_exists('get_the_ID' ) ) ? get_the_ID() : false;
			if ( empty($postId) ) return false;//back out
		}
		$savedDestinations = get_post_meta($postId, 'libsyn-destination-releases', true);
		if ( !empty($savedDestinations) && is_array($savedDestinations) ) {
			$returnArray = array();
			foreach ($savedDestinations as $working_destination) {
				$working_release = array();
				$working_release['destination_id'] = ( !empty($working_destination->destination_id) ) ? $working_destination->destination_id : null;
				if ( !empty($working_destination->release_date) ) {
					$working_release['release_date'] = $working_destination->release_date;
				} else {
					$working_release['release_date'] = 'now';
				}
				if ( !empty($working_destination->expiration_date) ) {
					$working_release['expiration_date'] = $working_destination->expiration_date;
				}
				if ( !empty($working_destination->destination_type_slug) && $working_destination->destination_type_slug !== 'wordpress' ) {
					$returnArray[] = $working_release;
				}
			}
		}
		if(!empty($returnArray)) return $returnArray; else return false; //default
	}

	//TODO: FINISH THIS
	public function libsyn_register_template() {
		$post_type_object = get_post_type_object('post');
		$post_type_object->template = array(
			array( 'myguten/meta-block'),
		);
	}
}
?>
