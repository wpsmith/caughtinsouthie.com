<?php
namespace Libsyn\Post;

class Classic extends \Libsyn\Post {

	/**
	 * Adds Meta box html
	 *
	 * @since 1.2.1
	 * @param WP_Post $object
	 * @param mixed $box
	 *
	 * @return void
	 */
	public static function addLibsynPostMeta( $object, $box ) {
		$sanitize = new \Libsyn\Service\Sanitize();
		$plugin = new \Libsyn\Service();
		$current_user_id = $plugin->getCurrentUserId();
		$api = $plugin->retrieveApiById($current_user_id, true);

		/* Handle saved api */
		$render = false; //default rendering to false
		$refreshTokenProblem = false;
		if ( $api instanceof \Libsyn\Api ) {
			$isRefreshExpired = $api->isRefreshExpired();
			if ( $isRefreshExpired ) { //refresh has expired
				if ( $plugin->hasLogger ) $plugin->logger->info("Post:\tAPI Refresh Expired");
				if ( current_user_can( 'upload_files' ) === false || current_user_can( 'edit_posts' ) === false ) $render = false; //check logged in user privileges.
				$refreshApi = $api->refreshToken(); //attempt to refresh before not rendering
				if ( $refreshApi ) { //successfully refreshed
					$api = $plugin->retrieveApiById($current_user_id);
					$render = true;
				}
			}
			$showId = $api->getShowId();
			if ( empty($showId) ) { //show_id has not been set in settings
				if ( empty($message) && !is_array($messages) ) { //make sure message has not been set yet
					$messages = array('post' => array('error', 'notice'));
				}
				$messages['post']['error'][] = "Show Id has not been set yet in the settings.  Please go to the Libsyn Podcasting <strong><a href=\"" . $plugin->admin_url('admin.php') . "?page=LibsynSettings\">Settings Page</a></strong> to correct this.";
				$render = false;
				$showIdProblem = true;
			}
		}
		if ( !empty($api) && $api instanceof \Libsyn\Api && !is_null($api->getShowId()) ) { $render = (!isset($showIdProblem)) ? true : false; } else { $render = false; $refreshTokenProblem = true; }
		$isPowerpress = \Libsyn\Service\Integration::getInstance()->checkPlugin('powerpress');
		?>
		<?php /* Loading Spinner */ ?>
		<div class="loading-libsyn-form" style="background: url(<?php echo plugins_url(LIBSYN_DIR.'/lib/images/spinner.gif'); ?>);background-repeat: no-repeat;background-position: left center;display: none;"><br><br><br><br>Loading...</div>
		<div class="configuration-problem" style="display: none;">
			<p>Please configure your <a href="<?php echo $plugin->admin_url('admin.php'); ?>?page=LibsynSettings">Libsyn Podcast Plugin</a> with your Libsyn Hosting account to use this feature.</p>
		</div>
		<div class="api-problem-box" style="display: none;">
			<p> We encountered a problem with the Libsyn API.  Please Check your <a href="<?php echo $plugin->admin_url('admin.php'); ?>?page=LibsynSettings">settings</a> and try again.</p>
		</div>
		<?php /* Render Main Box */ ?>
		<?php IF ( $render ): ?>
		<?php wp_enqueue_script( 'jquery-filestyle', plugins_url(LIBSYN_DIR . '/lib/js/jquery-filestyle.min.js'), array('jquery')); ?>
		<?php wp_enqueue_style( 'jquery-filestyle', plugins_url(LIBSYN_DIR . '/lib/css/jquery-filestyle.min.css')); ?>
		<?php wp_enqueue_style( 'jquery-simplecombobox', plugins_url(LIBSYN_DIR . '/lib/css/jquery.libsyn-scombobox.min.css')); ?>
		<?php wp_enqueue_script( 'jquery-simplecombobox', plugins_url(LIBSYN_DIR . '/lib/js/jquery.libsyn-scombobox.min.js'), array('jquery')); ?>
		<?php wp_enqueue_style( 'libsyn-meta-form', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_form.css')); ?>
		<?php wp_enqueue_style( 'libsyn-meta-boxes', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_boxes.css' )); ?>
		<?php wp_enqueue_style( 'libsyn-dashicons', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/dashicons.css' )); ?>
		<?php wp_enqueue_script( 'iris'); ?>
		<?php wp_enqueue_style( 'iris' ); ?>

		<?php //handle admin notices
			if ( !isset($messages) || !is_array($messages) ) { //make sure messages has not been set yet
				$messages = array('post' => array('error', 'notice'));
			}
			//remove post error if any
			$libsyn_error_post = get_post_meta($object->ID, 'libsyn-post-error', true);
			if ( $libsyn_error_post == 'true' ) {
				$messages['post']['error'][] = "There was an error posting content, please check settings and try again.";
			}
			delete_post_meta($object->ID, 'libsyn-post-error', 'true', true);

			//remove post error if any
			$libsyn_error_post_type = get_post_meta($object->ID, 'libsyn-post-error_post-type', true);
			if ( $libsyn_error_post_type == 'true' ) {
				$messages['post']['error'][] = "There was an error when creating the Libsyn Post, looks like you may have a custom post type setup in Wordpress.";
			}
			delete_post_meta($object->ID, 'libsyn-post-error_post-type', 'true', true);

			//remove post error if any
			$libsyn_error_post_permissions = get_post_meta($object->ID, 'libsyn-post-error_post-permissions', true);
			if ( $libsyn_error_post_permissions == 'true' ) {
				$messages['post']['error'][] = "There was an error when creating the Libsyn Post, looks like your user does not have permissions to post in Wordpress.";
			}
			delete_post_meta($object->ID, 'libsyn-post-error_post-permissions', 'true', true);

			$libsyn_error_api = get_post_meta($object->ID, 'libsyn-post-error_api', true);
			if ( $libsyn_error_api == 'true' ) {
				$messages['post']['error'][] = "There was an error with your Libsyn configuration, please check the settings page and try again.";
			}
			delete_post_meta($object->ID, 'libsyn-post-error_api', 'true', true);

			//render error messages
			if ( !empty($messages['post']) ) {
				if ( !empty($messages['post']['error']) && is_array($messages['post']['error']) ) { //display error messages
					foreach ($messages['post']['error'] as $post_message) {
						if ( $plugin->hasLogger ) $plugin->logger->error("Post:\t".$post_message);
						?><div class="error is-dismissible"><p><?php _e( $post_message, $plugin->text_dom ) ?></p></div><?php
					}
				}

				if ( !empty($messages['post']['notice']) && is_array($messages['post']['notice']) ) { //display admin messages
					foreach ($messages['post']['notice'] as $post_message) {
						if ( $plugin->hasLogger ) $plugin->logger->info("Post:\t".$post_message);
						?><div class="updated is-dismissible"><p><?php _e( $post_message, $plugin->text_dom ) ?></p></div><?php
					}
				}
			}

			/* Set Notifications */
			global $libsyn_notifications;
			do_action('libsyn_notifications');

			//handle nonce
			wp_nonce_field( basename( __FILE__ ), 'libsyn_post_episode_nonce' );
		?>

		<?php /* Playlist Page Dialog */?>
		<div id="libsyn-playlist-page-dialog" class="hidden" title="Create Podcast Playlist">
				<span style="font-weight:bold;">Playlist Type:</span><br>
				<input type="radio" name="playlist-media-type" value="audio" id="playlist-media-type-audio" checked="checked"></input>Audio
				<input type="radio" name="playlist-media-type" value="video" id="playlist-media-type-video"></input>Video
				<div style="padding:5px;display:none;" id="playlist-dimensions-div">
					<label for="playlist-video-width">Width </label>
					<input name="playlist-video-width" id="playlist-video-width" type="text" value="640"></input>
					<br>
					<label for="playlist-video-height">Height</label>
					<input name="playlist-video-height" id="playlist-video-height" type="text" value="360"></input>
				</div>
				<br><span style="font-weight:bold;">Playlist Source:</span><br>
				<input type="radio" name="playlist-feed-type" value="<?php if ( isset($api) && ( $api !== false ) ) { echo "libsyn-podcast-" . $api->getShowId(); } else { echo "my-podcast"; } ?>" id="my-podcast" checked="checked"></input>My Libsyn Podcast
				<br>
				<input type="radio" name="playlist-feed-type" value="other-podcast" id="other-podcast"></input>Other Podcast
				<label for="podcast-url"><?php _e( 'Podcast Url:', $plugin->text_dom ); ?></label>
				<input class="widefat" id="podcast-url" name="podcast-url" type="text" value="<?php echo esc_attr( get_post_meta( $object->ID, 'playlist-podcast-url', true ) ); ?>" type="url" style="display:none;" class="other-url" placeholder="http://www.your-wordpress-site.com/rss"></input>
			</p>
		</div>
		<div id="libsyn-player-settings-page-dialog" class="hidden" title="Player Settings"></div>
		<script type="text/javascript">
			(function ($){
				$(document).ready(function() {
					//set form up
					var data = '<?php if (!empty($object->ID) ) { echo $object->ID; } ?>';
					$('.loading-libsyn-form').fadeIn('normal');
					$('.libsyn-post-form').hide();

					setOverlays = function() {
						//make sure overlays are not over dialogs
						$('.ui-widget-overlay').each(function() {
							$(this).css('z-index', 999);
							$(this).attr('style', 'z-index:999 !important;');
							if(($(this).css("z-index") != typeof 'undefined') && $(this).css("z-index") >= 1000) {
								//worse case scenario hide the overlays
								$(this).fadeOut('fast');
							}
						});
						$('.ui-dialog-title').each(function() {
							$(this).css('z-index', 1002);
						});
						$('.ui-dialog').each(function() {
							$(this).css('z-index', 1002);
						});
					}

					//run ajax
					$.ajax({
						url: '<?php  echo $plugin->admin_url(); ?>' + '<?php  echo "?action=load_libsyn_media&load_libsyn_media=1"; ?>',
						type: 'POST',
						data: data,
						cache: false,
						dataType: 'json',
						processData: false, // Don't process the files
						contentType: false, // Set content type to false as jQuery will tell the server its a query string request
						success: function(data, textStatus, jqXHR) {
							 if(!data) {
								//Handle errors here
								$('.loading-libsyn-form').hide();
								$('.api-problem-box').fadeIn('normal');
							 } else if(typeof data.error == 'undefined') { //Successful response

								//remove ftp/unreleased
								$.ajax({
									url : '<?php  echo $plugin->admin_url(); ?>' + '<?php echo "?action=remove_ftp_unreleased&remove_ftp_unreleased=1"; ?>',
									type: 'POST',
									data: data,
									cache: false,
									dataType: 'json',
									processData: false, // Don't process the files
									contentType: false, // Set content type to false as jQuery will tell the server its a query string request
									success : function(data) {
										//do nothing
									},
									error : function(request,error)
									{
										//error
										//alert("Request: "+JSON.stringify(request));
									}
								});

								//show div & hide spinner
								$('.loading-libsyn-form').hide();
								$('.libsyn-post-form').fadeIn('normal');
								$("#libsyn-categories").empty();

								//handle categories section
								if(typeof data != 'undefined' && data != false && data.length > 0) {
									for(i = 0; i < data.length; i++) {
										if(i==0) { var firstValue = data[i]; }
										$("#libsyn-categories").append("<option value=\"" + data[i] + "\">" + data[i] + "</option>");
									}
								}
								<?php $savedPostCategory = esc_attr( get_post_meta( $object->ID, 'libsyn-post-episode-category-selection', true) ); ?>
								if(typeof libsynSavedCategory == 'undefined') {
									libsynSavedCategory = "<?php if ( !empty($savedPostCategory) ) { echo $savedPostCategory; }?>";
								}

								if ( libsynSavedCategory.length > 0 ) {
									var firstValue = libsynSavedCategory;
									$("#libsyn-post-episode-category-selection").val(libsynSavedCategory);
								}
								$("#libsyn-categories").scombobox({
									highlight: true,
									highlightInvalid: false,
									easing: 'easeOutBack'
								});
								var libsynCategoryComboBox = true;
								$("#libsyn-post-episode-category-selection").addClass('scombobox-value').appendTo($("#libsyn-categories"));
								$("#libsyn-categories > input.scombobox-display").val(firstValue);
								$('#libsyn-categories > .scombobox-value[name=libsyn-post-episode-category]').val(firstValue);
								$("#libsyn-categories").scombobox('change', function() {
									$("#libsyn-post-episode-category-selection").val($("#libsyn-categories .scombobox-display").val());
								});

								$('#libsyn-categories').children('.scombobox-display').focus(function(){
									$(this).css({'border': '1px solid #60a135'});
									$('.scombobox-dropdown-background').css({'border-color': '#60a135 #60a135 #60a135 -moz-use-text-color', 'border': '1px solid #60a135'});
								}).on("blur", function() {
									$(this).css({'border': '1px solid #CCC'});
									$('.scombobox-dropdown-background').css({'border': '1px solid #CCC', 'border-color': '#ccc #ccc #ccc -moz-use-text-color'});
									var currVal = $("#libsyn-categories > .scombobox-display").val();
									var sel = $('#libsyn-categories select');
									var opt = $('<option>').attr('value', currVal).html(currVal);
									sel.append(opt);
								});

							 } else {
								//Handle errors here
								$('.loading-libsyn-form').hide();
								$('.libsyn-post-form').fadeIn('normal');
								$('.options-error').fadeIn('normal');
								//$('.api-problem-box').fadeIn('normal');
							 }
						},
						error: function(jqXHR, textStatus, errorThrown) {
							// Handle errors here
							$('.loading-libsyn-form').hide();
							$('.configuration-problem').fadeIn('normal');
						}
					});

					//Load Player Settings
					$("#libsyn-player-settings-page-dialog").load('<?php  echo $plugin->admin_url(); ?>' + '?action=load_player_settings&load_player_settings=1', function() {
						//add stuff to ajax box
						$("#player_use_theme_standard_image").append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/player-preview-standard.jpg'); ?>" style="max-width:95%;" />');
						$("#player_use_theme_mini_image").append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/player-preview-standard-mini.jpg'); ?>" style="max-width:95%;" />');
						$("#player_use_theme_custom_image").append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/custom-player-preview.jpg'); ?>" style="max-width:95%;" />');
						$(".post-position-shape-top").append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/player_position.png'); ?>" style="vertical-align:top;" />');
						$(".post-position-shape-bottom").append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/player_position.png'); ?>" style="vertical-align:top;" />');

						//validate button
						$('<a>').text('Validate').attr({
							'class': 'button'
						}).click( function() {
							var current_feed_redirect_input = validator_url + encodeURIComponent($("#feed_redirect_input").attr('value'));
							window.open(current_feed_redirect_input);
						}).insertAfter("#feed_redirect_input");

						//set default value for player use thumbnail
						<?php
							$postPlayerUseThumbnail = get_post_meta( $object->ID, 'libsyn-post-episode-player_use_thumbnail', true );
							$playerUseThumbnail = ( !empty($postPlayerUseThumbnail) && $postPlayerUseThumbnail !== 'none') ? $postPlayerUseThumbnail : get_user_option('libsyn-podcasting-player_use_thumbnail');
						?>
						var playerUseThumbnail = '<?php if ( !empty($playerUseThumbnail) ) { echo $playerUseThumbnail; } ?>';
						if(playerUseThumbnail == 'use_thumbnail') {
							$('#player_use_thumbnail').prop('checked', true);
						}

						//set default value of player theme
						<?php
							$postPlayerTheme = get_post_meta( $object->ID, 'libsyn-post-episode-player_use_theme', true );
							$playerTheme = ( !empty($postPlayerTheme) ) ? $postPlayerTheme : get_user_option('libsyn-podcasting-player_use_theme');
						?>
						var playerTheme = '<?php if ( !empty($playerTheme) ) { echo $playerTheme; } ?>';
						if(playerTheme == 'standard') {
							$('#player_use_theme_standard').prop('checked', true);
							//check if player_use_thumbnail is checked
							if($('#player_use_thumbnail').is(':checked')) {
								if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
							} else {
								$('#player_height').attr({"min": "45"});
								if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
							}
						} else if(playerTheme == 'mini') {
							$('#player_use_theme_mini').prop('checked', true);
							//check if player_use_thumbnail is checked
							if($('#player_use_thumbnail').is(':checked')) {
								if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
							} else {
								$('#player_height').attr({"min": "26"});
								if(parseInt($('#player_height').val()) < 26) $('#player_height').val(26);
							}
						} else if(playerTheme == 'custom') {
							$('#player_use_theme_custom').prop('checked', true);
							$('#player_width_tr').fadeOut('fast', function() {
								$('#player_custom_color_picker').fadeIn('normal');
							});
							//check if player_use_thumbnail is checked
							if($('#player_use_thumbnail').is(':checked')) {
								if(parseInt($('#player_height').val()) < 90) $('#player_height').val(90);
								if(parseInt($('#player_width').val()) < 450) $('#player_height').val(450);
							} else {
								$('#player_height').attr({"min": "90"});
								if(parseInt($('#player_height').val()) < 90) $('#player_height').val(90);
							}
						} else { //default: getPlayerTheme is not set
							//set default value of player theme to standard if not saved
							$('#player_use_theme_standard').prop('checked', true);

							//check if player_use_thumbnail is checked
							if($('#player_use_thumbnail').is(':checked')) {
								if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
							} else {
								$('#player_height').attr({"min": "45"});
								if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
							}
						}

						//player theme checkbox settings
						$('#player_use_theme_standard').change(function() {
							if($('#player_use_theme_standard').is(':checked')) {
								//check if player_use_thumbnail is checked
								if($('#player_use_thumbnail').is(':checked')) {
									if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
								} else {
									$('#player_height').attr({"min": "45"});
									if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
								}
								$('#player_custom_color_picker').fadeOut('fast', function() {
									//$('#player_width_tr').fadeIn('normal');
								});
							} else if($('#player_use_theme_mini').is(':checked')) {
								//check if player_use_thumbnail is checked
								if($('#player_use_thumbnail').is(':checked')) {
									if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
								} else {
									$('#player_height').attr({"min": "26"});
									if(parseInt($('#player_height').val()) < 26) $('#player_height').val(26);
								}
								$('#player_custom_color_picker').fadeOut('fast', function() {
									//$('#player_width_tr').fadeIn('normal');
								});
							} else if($('#player_use_theme_custom').is(':checked')) {
								$('#player_height').attr({"min": "90"});
								$('#player_width').attr({"min": "450"});
								if(parseInt($('#player_height').val()) > 90) $('#player_height').val(90);
								$('#player_width_tr').fadeOut('fast', function() {
									$('#player_custom_color_picker').fadeIn('normal');
								});
							}
						});
						$('#player_use_theme_mini').change(function() {
							if($('#player_use_theme_standard').is(':checked')) {
								//check if player_use_thumbnail is checked
								if($('#player_use_thumbnail').is(':checked')) {
									if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
								} else {
									$('#player_height').attr({"min": "45"});
									if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
								}
								$('#player_custom_color_picker').fadeOut('fast', function() {
									//$('#player_width_tr').fadeIn('normal');
								});
							} else if($('#player_use_theme_mini').is(':checked')) {
								//check if player_use_thumbnail is checked
								if($('#player_use_thumbnail').is(':checked')) {
									if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
								} else {
									$('#player_height').attr({"min": "26"});
									if(parseInt($('#player_height').val()) < 26) $('#player_height').val(26);
								}
								$('#player_custom_color_picker').fadeOut('fast', function() {
									//$('#player_width_tr').fadeIn('normal');
								});
							} else if($('#player_use_theme_custom').is(':checked')) {
								$('#player_height').attr({"min": "90"});
								$('#player_width').attr({"min": "450"});
								if(parseInt($('#player_height').val()) > 90) $('#player_height').val(90);
								$('#player_width_tr').fadeOut('fast', function() {
									$('#player_custom_color_picker').fadeIn('normal');
								});
							}
						});
						$('#player_use_theme_custom').change(function() {
							if($('#player_use_theme_standard').is(':checked')) {
								//check if player_use_thumbnail is checked
								if($('#player_use_thumbnail').is(':checked')) {
									if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
								} else {
									$('#player_height').attr({"min": "45"});
									if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
								}
								$('#player_custom_color_picker').fadeOut('fast', function() {
									//$('#player_width_tr').fadeIn('normal');
								});
							} else if($('#player_use_theme_mini').is(':checked')) {
								//check if player_use_thumbnail is checked
								if($('#player_use_thumbnail').is(':checked')) {
									if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
								} else {
									$('#player_height').attr({"min": "26"});
									if(parseInt($('#player_height').val()) < 26) $('#player_height').val(26);
								}
								$('#player_custom_color_picker').fadeOut('fast', function() {
									//$('#player_width_tr').fadeIn('normal');
								});
							} else if($('#player_use_theme_custom').is(':checked')) {
								$('#player_height').attr({"min": "90"});
								$('#player_width').attr({"min": "450"});
								if(parseInt($('#player_height').val()) > 90) $('#player_height').val(90);
								$('#player_width_tr').fadeOut('fast', function() {
									$('#player_custom_color_picker').fadeIn('normal');
								});
							}
						});

						//player values height & width
						<?php
							$postPlayerHeight = get_post_meta( $object->ID, 'libsyn-post-episode-player_height', true );
							$playerHeight = ( !empty($postPlayerHeight) ) ? $postPlayerHeight : get_user_option('libsyn-podcasting-player_height');
						?>
						<?php
							$postPlayerWidth = get_post_meta( $object->ID, 'libsyn-post-episode-player_width', true );
							$playerWidth = ( !empty($postPlayerWidth) ) ? $postPlayerWidth : get_user_option('libsyn-podcasting-player_width');
						?>
						var playerHeight = parseInt('<?php if ( !empty($playerHeight) ) { echo $playerHeight; } ?>');
						var playerWidth = parseInt('<?php if ( !empty($playerWidth) ) { echo $playerWidth; } ?>');

						//height
						if(isNaN(playerHeight)) {
							$('#player_height').val(360);
						} else {
							if($('#player_use_theme_standard').is(':checked')) {
								if(playerHeight >= 45) $('#player_height').val(playerHeight);
									else $('#player_height').val(45);
							} else if($('#player_use_theme_mini').is(':checked')) {
								if(playerHeight >= 26) $('#player_height').val(playerHeight);
									else $('#player_height').val(26);
							} else if($('#player_use_theme_custom').is(':checked')) {
								if(playerHeight >= 90) $('#player_height').val(playerHeight);
									else $('#player_height').val(90);
							} else {
								$('#player_height').val(360);
							}
						}

						//width
						if(isNaN(playerWidth)) {
							$('#player_width').val(450);
						} else {
							if($('#player_use_theme_standard').is(':checked')) {
								if(playerWidth >= 200) $('#player_width').val(playerWidth);
									else $('#player_width').val(200);
							} else if($('#player_use_theme_mini').is(':checked')) {
								if(playerWidth >= 100) $('#player_width').val(playerWidth);
									else $('#player_width').val(100);
							} else if($('#player_use_theme_custom').is(':checked')) {
								if(playerWidth >= 450) $('#player_width').val(playerWidth);
									else $('#player_width').val(450);
							} else {
								$('#player_width').val(450);
							}
						}

						//player use thumbnail checkbox settings
						$('#player_use_thumbnail').change(function() {
							if($(this).is(':checked')) {
								//TODO: Add playlist support here
								if($('#player_use_theme_custom').is(':checked')) {
									if($('#player_width').val() == '' || parseInt($('#player_width').val()) <= 450) { //below min width
										$('#player_width').val("450");
										$('#player_width').attr({"min": "450"});
									}
								} else {
									if($('#player_height').val() == '' || parseInt($('#player_height').val()) <= 200) { //below min height
										$('#player_height').val("200");
										$('#player_height').attr({"min": "200"});
									}
								}
							} else {
								if($('#player_use_theme_standard').is(':checked')) {
									$('#player_height').attr({"min": "45"});
								} else if($('#player_use_theme_mini').is(':checked')){
									$('#player_height').attr({"min": "26"});
								} else if($('#player_use_theme_custom').is(':checked')){
									$('#player_height').attr({"min": "90"});
									$('#player_width').attr({"min": "450"});
								}

							}
						});

						//player placement checkbox settings
						<?php
							$postPlayerPlacement = get_post_meta( $object->ID, 'libsyn-post-episode-player_placement', true );
							$playerPlacement = ( !empty($postPlayerPlacement) ) ? $postPlayerPlacement : get_user_option('libsyn-podcasting-player_placement');
						?>
						var playerPlacement = '<?php if ( !empty($playerPlacement) ) { echo $playerPlacement; } ?>';
						if(playerPlacement == 'top') {
							$('#player_placement_top').prop('checked', true);
						} else if(playerPlacement == 'bottom') {
							$('#player_placement_bottom').prop('checked', true);
						} else { //player placement is not set
							$('#player_placement_top').prop('checked', true);
						}

						<?php
						$postUseDownloadLink = get_post_meta( $object->ID, 'libsyn-post-episode-player_use_download_link', true );
						$playerUseDownloadLink = ( !empty($postUseDownloadLink) ) ? $postUseDownloadLink : get_user_option('libsyn-podcasting-player_use_download_link');
						?>
						var playerUseDownloadLink = '<?php if ( !empty($playerUseDownloadLink) ) { echo $playerUseDownloadLink; } ?>';
						<?php
						$postUseDownloadLinkText = get_post_meta( $object->ID, 'libsyn-post-episode-player_use_download_link_text', true );
						$playerUseDownloadLinkText = ( !empty($postUseDownloadLinkText) ) ? $postUseDownloadLinkText : get_user_option('libsyn-podcasting-player_use_download_link_text');
						?>
						var playerUseDownloadLinkText = '<?php if ( !empty($playerUseDownloadLinkText) ) { echo $playerUseDownloadLinkText; } ?>';
						if(playerUseDownloadLink == 'use_download_link') {
							$('#player_use_download_link').prop('checked', true);
							if(playerUseDownloadLinkText == '') {
								$('#player_use_download_link_text').val('');
							} else if(playerUseDownloadLinkText.length >= 1) {
								$('#player_use_download_link_text').val(playerUseDownloadLinkText);
							}
							$('#player_use_download_link_text_div').fadeIn('normal');
						}

						//player theme checkbox settings
						$('#player_use_download_link').change(function() {
							if($(this).is(':checked')) {
								$('#player_use_download_link_text_div').fadeIn('normal');
							} else {
								$('#player_use_download_link_text_div').hide('fast');
								$('#player_use_download_link_text').val('Download Episode!');
							}
						});

						<?php
						$postCustomColor = get_post_meta( $object->ID, 'libsyn-post-episode-player_custom_color', true );
						$playerCustomColor = ( !empty($postCustomColor) ) ? $postCustomColor : get_user_option('libsyn-podcasting-player_custom_color', $current_user_id);
						?>
						<?php if( empty($playerCustomColor) ) { ?>
						var playerCustomColor = '87a93a';
						<?php } else { ?>
						var playerCustomColor = '<?php if ( !empty($playerCustomColor) ) { echo $playerCustomColor; } ?>';
						<?php } ?>

						//color picker settings

						$('#player_custom_color').attr('value', playerCustomColor);
						$('#player_custom_color').css('background-color', "#" + playerCustomColor);

						libsyn_player_color_picker = $('#player_custom_color').iris({
							palettes: ['#125', '#459', '#78b', '#ab0', '#de3', '#f0f'],
							hide: true,
							border: false,
							target: $('#player_custom_color_picker_container'),
							change: function(event, ui) {
								$('#player_custom_color').css('background-color', ui.color.toString() );
							}
						});
						libsyn_player_color_picker.click(function(e) {
							if(typeof libsyn_player_color_picker !== 'undefined') {
								libsyn_player_color_picker.iris('show');
								libsyn_player_color_picker.data('isOpen', 'show');
							}
						});

						$('#player_custom_color_picker_button').click(function(e) {
							if(typeof libsyn_player_color_picker !== 'undefined') {

								if ( typeof libsyn_player_color_picker.data('isOpen') === 'undefined' ) {
									libsyn_player_color_picker.iris('show');
									libsyn_player_color_picker.data('isOpen', 'show');
								} else {
									if ( libsyn_player_color_picker.data('isOpen') === 'show' ) {
										libsyn_player_color_picker.iris('hide');
										libsyn_player_color_picker.data('isOpen', 'hide');
									} else if ( libsyn_player_color_picker.data('isOpen') === 'hide' ) {
										libsyn_player_color_picker.iris('show');
										libsyn_player_color_picker.data('isOpen', 'show');
									}
									libsyn_player_color_picker.iris('toggle');
								}

							}
						});
					});

					$( "#libsyn-upload-asset-dialog" ).dialog({
						autoOpen: false,
						draggable: true,
						height: 'auto',
						width: 'auto',
						modal: true,
						resizable: false,
						open: function(){
							setOverlays();
							$(".ui-widget-overlay").bind("click",function(){
								$("#libsyn-upload-asset-dialog").dialog("close");
							})
						},
						buttons: [
							{
								id: "dialog-button-cancel",
								text: "Cancel",
								click: function(){
									$('#libsyn-upload-asset-dialog').dialog("close");
								}
							}
						]
					});

					$("#libsyn-upload-media").each(function(event) {
						$(this).click(function(event) {
							event.preventDefault();
							document.getElementById('libsyn-new-media-media').setAttribute('type', 'text');
							$("#libsyn-upload-media-dialog").dialog( "open" );
						});
					});

					function libsynClearPrimaryMedia() {
						$("#libsyn-new-media-media").val("").attr({ "readonly": false, "data-show-id": "" });
						$("#libsyn-upload-media-preview").empty();
						$("#libsyn-upload-media-error").hide();
						$("#dialog-button-upload").attr("disabled", false);
						$(".upload-error-dialog").empty();
						$("#libsyn-new-media-media")
					}

					$('#libsyn-clear-media-button').click(function(event) {
						event.preventDefault();
						libsynClearPrimaryMedia();
					});

					$("#libsyn-clear-image-button").click(function(event) {
						event.preventDefault();
						$("#libsyn-new-media-image").val("").attr("readonly", false);
					});

					<?php //Set Meta Vars to check
					$libsynPostEpisodeItunesExplicit	= get_post_meta( $object->ID, 'libsyn-post-episode-itunes-explicit', true );
					$libsynPostEpisode					= get_post_meta( $object->ID, 'libsyn-post-episode', true );
					$libsynPostEpisodeUpdateId3			= get_post_meta( $object->ID, 'libsyn-post-episode-update-id3', true );
					$libsynPostUpdateReleaseDate		= get_post_meta( $object->ID, 'libsyn-post-update-release-date', true );
					$libsynPostEpisodeSimpleDownload	= get_post_meta( $object->ID, 'libsyn-post-episode-simple-download', true );
					?>

					if("<?php if ( !empty($libsynPostEpisodeItunesExplicit) ) { echo esc_attr( $libsynPostEpisodeItunesExplicit ); } ?>" != "") {
						$("#libsyn-post-episode-itunes-explicit").val("<?php echo esc_attr( get_post_meta( $object->ID, 'libsyn-post-episode-itunes-explicit', true ) ); ?>");
					}
					if("<?php if ( !empty($libsynPostEpisode) ) { echo esc_attr( $libsynPostEpisode ); } ?>" == "isLibsynPost") {
						$("#libsyn-post-episode").prop("checked", true);
					}
					if("<?php if ( !empty($libsynPostEpisodeUpdateId3) ) { echo esc_attr( $libsynPostEpisodeUpdateId3 ); } ?>" == "isLibsynUpdateId3") {
						$("#libsyn-post-episode-update-id3").prop("checked", true);
					}
					if("<?php if ( !empty($libsynPostUpdateReleaseDate) ) { echo esc_attr( $libsynPostUpdateReleaseDate ); } ?>" == "isLibsynUpdateReleaseDate") {
						$("#libsyn-post-update-release-date").prop("checked", true);
					}
					if("<?php if ( !empty($libsynPostEpisodeSimpleDownload) ) { echo esc_attr( $libsynPostEpisodeSimpleDownload ); } ?>" == "release_date") {
						$("#libsyn-post-episode-simple-download-release_date").prop("checked", true);
					} else {
						$("#libsyn-post-episode-simple-download-available").prop("checked", true);
					}

					<?php
						//release date
						$libsyn_release_date = $sanitize->mysqlDate(get_post_meta( $object->ID, 'libsyn-release-date', true ));
						$isLibsynUpdateReleaseDateChecked = ( get_post_meta($object->ID, 'libsyn-post-update-release-date', true) )?' checked="checked"':'';
						$isLibsynUpdateId3Checked = ( get_post_meta($object->ID, 'libsyn-post-episode-update-id3', true) )?' checked="checked"':'';
						$libsynItemId = get_post_meta($object->ID, 'libsyn-item-id', true);
						$hasLibsynReleaseDate = $sanitize->validateMysqlDate($libsyn_release_date);
						if ( !$hasLibsynReleaseDate && !empty($libsynItemId) ) {
							//check if the post is being edited (from posts page)
							$hasLibsynReleaseDate = true;
						}
						if ( $hasLibsynReleaseDate ) {
					?>
							$('#libsyn-post-status').fadeIn('normal');
							$('#libsyn-post-update-release-date').fadeIn('normal');
					<?php } ?>

						if ( typeof libsyn_nmp_data.libsyn_edit_item !== 'undefined' ) {
							if(!$.isEmptyObject(libsyn_nmp_data.libsyn_edit_item)) {
								//set vals
								$('#libsyn-post-episode').prop('checked', true);
								$('#libsyn-new-media-media').prop('readonly', true);
								$('#libsyn-new-media-media').val('http://libsyn-upload-' + libsyn_nmp_data.libsyn_edit_item.primary_content.content_id);
								$('#libsyn-post-episode-subtitle').val(libsyn_nmp_data.libsyn_edit_item.item_subtitle);

								var firstValue = libsyn_nmp_data.libsyn_edit_item.category;
								if (firstValue.length > 0) {

									libsynSavedCategory = firstValue;
									$("#libsyn-categories > input.scombobox-display").val(firstValue);
								}

								$('#libsyn-categories > .scombobox-value[name=libsyn-post-episode-category]').val(firstValue);
								$('#libsyn-categories .scombobox-display input').val(libsyn_nmp_data.libsyn_edit_item.category);
								$('#libsyn-new-media-image').val(libsyn_nmp_data.libsyn_edit_item.thumbnail.url);
								$('#libsyn-new-media-image').prop('readonly', true);
								$('#libsyn-post-episode-keywords').val(libsyn_nmp_data.libsyn_edit_item.extra_rss_tags);
								$('#libsyn-post-episode-itunes-explicit').val(libsyn_nmp_data.libsyn_edit_item.itunes_explicit);
								$('#libsyn-post-episode-itunes-episode-number').val(libsyn_nmp_data.libsyn_edit_item.itunes_episode_number);
								$('#libsyn-post-episode-itunes-season-number').val(libsyn_nmp_data.libsyn_edit_item.itunes_season_number);
								$('#libsyn-post-episode-itunes-episode-type').val(libsyn_nmp_data.libsyn_edit_item.itunes_episode_type);
								$('#libsyn-post-episode-itunes-episode-summary').val(libsyn_nmp_data.libsyn_edit_item.itunes_episode_summary);
								$('#libsyn-post-episode-itunes-episode-title').val(libsyn_nmp_data.libsyn_edit_item.itunes_episode_title);
								$('#libsyn-post-episode-itunes-episode-author').val(libsyn_nmp_data.libsyn_edit_item.itunes_episode_author);
								$('#libsyn-post-episode-update-id3').val(libsyn_nmp_data.libsyn_edit_item.update_id3);

								<?php
									global $current_screen;
									$current_screen = get_current_screen();
									if ( ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) ||
										 ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) ) {
								?>
									$(window).bind("load", function() {
										wp.data.dispatch( 'core/editor' ).editPost( { title: libsyn_nmp_data.libsyn_edit_item.item_title } );
										var el = wp.element.createElement;
										var libsynContentBlock = wp.blocks.createBlock('core/paragraph', {
											content: libsyn_nmp_data.libsyn_edit_item.body,
										});
										wp.data.dispatch('core/editor').insertBlocks(libsynContentBlock);
								   });
								<?php } else { ?>
									$('#titlewrap input[id=title]').val(libsyn_nmp_data.libsyn_edit_item.item_title);
									$('#wp-content-editor-container').find('textarea').val(libsyn_nmp_data.libsyn_edit_item.body);
								<?php } ?>
							}
						}
					<?php if ( !empty($_GET['isLibsynPost']) && ( $_GET['isLibsynPost'] == "true" ) )  { ?>
						$('#libsyn-post-episode').prop('checked', true);
					<?php } ?>

					<?php //Check for published destinations
						// $published_destinations = get_post_meta($object->ID, 'libsyn-destination-releases', true);
						if ( !empty($libsyn_release_date) ) { ?>
							$('#libsyn-advanced-destination-form-container').find('table').css({'margin-left':'-116px', 'overflow-x': 'scroll'});
					<?php } ?>
				});
			}) (jQuery);
		</script>
				<?php ENDIF; //$render?>

		<script type="text/javascript">
			(function ($){
				$(document).ready(function() {
					//check for API errors
					<?php if ( $refreshTokenProblem ) {?>
						$('.libsyn-post-form').hide();
						$('.loading-libsyn-form').hide();
						$('.api-problem-box').fadeIn('normal');
					<?php } elseif ( !$render ) { ?>
						$('.libsyn-post-form').hide();
						$('.loading-libsyn-form').hide();
						$('.configuration-problem').fadeIn('normal');
					<?php } ?>
				});
			}) (jQuery);
		</script>

		<?php if ( $isPowerpress ) { ?>
		<div class="configuration-problem-powerpress" style="border: 1px solid red;">
			<p style="color:red;font-weight:bold;padding-left:10px;">You Currently have 'Powerpress Plugin' installed.
			<br>Please visit the <a href="<?php echo $plugin->admin_url('admin.php'); ?>?page=LibsynSettings">settings</a> and make any configuration changes before posting.  (note: The Libsyn plugin will conflict with this plugin)</p>
		</div>
		<?php } ?>
		<div class="libsyn-post-form libsyn-meta-box">
			<table class="form-table">
				<tr valign="top">
					<p><strong><?php echo __( 'The post title and post body above will be used for your podcast episode.', $plugin->text_dom ); ?></strong></p>
				</tr>
				<tr valign="top" id="libsyn-post-status" style="display:none;">
					  <th><label for="libsyn-post-episode-status"><?php _e( "Post Status", $plugin->text_dom ); ?></label></th>
					  <td>
					  	<?php //Setup Re-Release header text
							$isDraft = (get_post_meta($object->ID, 'libsyn-is_draft', true) === "true") ? true : false;
							$currentTime = (function_exists('current_time')) ? strtotime(current_time('mysql')) : time();
							if ( !empty($libsyn_release_date) && ($currentTime <= strtotime($libsyn_release_date)) ) {
								$release_text = __("Scheduled to release", $plugin->text_dom);
							} elseif ( $isDraft ) {
								$release_text = __("Draft saved", $plugin->text_dom);
							} else {
								$release_text = __("Released", $plugin->text_dom);
							}
					  	?>
						<?php IF ( !empty($libsyn_release_date) ): ?>
						<?php
						if ( function_exists('get_date_from_gmt') ) {
							?><p id="libsyn-post-episode-status"><strong><?php if ( !empty($release_text) ) { echo $release_text; } ?> on <?php echo date("F j, Y, g:i a", strtotime(get_date_from_gmt($libsyn_release_date))); ?></strong></p><?php
						} else {
							?><p id="libsyn-post-episode-status"><strong><?php if ( !empty($release_text) ) { echo $release_text; } ?> on <?php echo date("F j, Y, g:i a", strtotime($libsyn_release_date))." GMT"; ?></strong></p><?php
						} ?>

						<?php ELSEIF ( !empty($release_text) ): ?>
							<p id="libsyn-post-episode-status"><strong><?php if ( !empty($release_text) ) { echo $release_text; } ?></strong></p>
						<?php ENDIF; ?>
					  </td>
				</tr>
				<tr valign="top" id="libsyn-post-update-release-date" style="display:none;">
					  <th><label for="libsyn-post-update-release-date"><?php _e( "Update Release Date", $plugin->text_dom ); ?></label></th>
					  <td>
						<div class="titlediv">
							<div class="titlewrap">
								<input type="checkbox" name="libsyn-post-update-release-date" id="libsyn-post-update-release-date" value="isLibsynUpdateReleaseDate" <?php if ( !empty($isLibsynUpdateReleaseDateChecked) ) { echo $isLibsynUpdateReleaseDateChecked; } ?>></input>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <?php $isLibsynPostChecked = ( get_post_meta($object->ID, '_isLibsynPost', true) || get_post_meta($object->ID, 'isLibsynPost', true) ) ? ' checked="checked"' : ''; ?>
					  <th><label for="libsyn-post-episode"><?php _e( "Post Libsyn Episode<span style='color:red;'>*</span>", $plugin->text_dom ); ?></label></th>
					  <td>
						<div class="titlediv">
							<div class="titlewrap">
								<input type="checkbox" name="libsyn-post-episode" id="libsyn-post-episode" value="isLibsynPost" <?php if ( !empty($isLibsynPostChecked) ) { echo $isLibsynPostChecked; } ?>></input>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e( "Episode Media<span style='color:red;'>*</span>", $plugin->text_dom ); ?></th>
					  <td>
						<div id="libsyn-primary-media-settings">
							<div id="libsyn-new-media-settings">
								<div class="upload-error" style="display:none;color:red;font-weight:bold;">There was an error uploading media, please check settings and try again.</div>
								<p><?php echo __( 'Select Primary Media for Episode by clicking the button below.', $plugin->text_dom ); ?></p>
								<p>
									<button class="button button-primary libsyn-dashicions-upload" id="libsyn-upload-media" title="<?php echo esc_attr__( 'Click here to upload media for episode', $plugin->text_dom ); ?>"><?php echo __( 'Upload Media', $plugin->text_dom ); ?></button>
									<button class="button button-primary libsyn-dashicions-wordpress" id="libsyn-open-media" title="<?php echo esc_attr__( 'Click Here to Open the Media Manager', $plugin->text_dom ); ?>"><?php echo __( 'Select Wordpress Media', $plugin->text_dom ); ?></button>
									<button class="button button-primary libsyn-dashicions-cloud" id="libsyn-open-ftp_unreleased" title="<?php echo esc_attr__( 'Click Here to Open the Media Manager', $plugin->text_dom ); ?>"><?php echo __( 'Select ftp/unreleased', $plugin->text_dom ); ?></button>
								</p>
								<p>
									<?php $libsyn_media_media = get_post_meta( $object->ID, 'libsyn-new-media-media', true ); ?>
									<label for="libsyn-new-media-media"><?php echo __( 'Media Url', $plugin->text_dom ); ?></label>&nbsp;<input type="url" id="libsyn-new-media-media" name="libsyn-new-media-media" value="<?php if ( !empty($libsyn_media_media) ) { echo esc_attr( $libsyn_media_media ); } ?>" pattern="https?://.+" <?php if ( isset($libsyn_media_media) && !empty($libsyn_media_media) ) echo 'readonly'; ?>></input>
									<button class="button libsyn-dashicions-close" id="libsyn-clear-media-button" title="<?php echo esc_attr__( 'Clear primary media', $plugin->text_dom ); ?>"><?php echo __( 'Clear', $plugin->text_dom ); ?></button>
								</p>
							</div>
							<div id="libsyn-upload-media-dialog" class="hidden" title="Upload Media">
								<h3>Select Media to upload:</h3>
								<input id="libsyn-media-file-upload" type="file" name="upload" class="jfilestyle" data-theme="asphalt" data-dragdrop="false" data-buttonBefore="true" data-placeholder="Audio or Video file" data-inputSize="300px" data-text="Select Media" data-buttonText="Select Media" data-size="300px"></input>
								<div id="libsyn-media-progressbox-area" style="display:none;">
									<div class="libsyn-dots"></div>
									<div id="libsyn-media-progressbox">
										<div id="libsyn-media-progressbar"></div>
										<div id="libsyn-media-statustxt">0%</div>
									</div>
								</div>
								<div class="upload-error-dialog" style="display:none;color:red;font-weight:bold;"></div>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top" style="display:none;" id="player_settings_button_bottom_tr">
					<th></th>
					<td>
						<div id="player_settings_button_bottom"></div>
					</td>
				</tr>
				<tr id="libsyn-upload-media-preview-area">
					<th scope="row"></th>
					<td id="libsyn-upload-media-preview">
					</td>
				</tr>
				<tr id="libsyn-upload-media-error" style="display:none;">
					<th scope="row"></th>
					<td>
						<div class="libsyn-media-error">
							 <p id="libsyn-upload-media-error-text"></p>
						</div>
					</td>
				</tr>
				<tr valign="top">
					  <th><?php _e( "Episode Subtitle", $plugin->text_dom ); ?></th>
					  <td>
						<div class="titlediv">
							<div class="titlewrap">
								<?php $libsynEpisodeSubtitle = get_post_meta( $object->ID, 'libsyn-post-episode-subtitle', true ); ?>
								<input id="libsyn-post-episode-subtitle" type="text" autocomplete="off" value="<?php if ( !empty($libsynEpisodeSubtitle) ) { echo $libsynEpisodeSubtitle; } ?>" name="libsyn-post-episode-subtitle" style="width:100%;" maxlength="255"></input>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e( "Episode Category<span style='color:red;'>*</span>", $plugin->text_dom ); ?></th>
					  <td>
						<div class="titlediv">
							<div class="titlewrap">
								<div class="options-error" style="display:none;color:red;font-weight:bold;">Could not populate categories, manually enter category.</div>
								<select id="libsyn-categories" name="libsyn-post-episode-category">
									<option value="general">general</option>
								</select>
								<?php $libsynEpisodeCategorySelection = get_post_meta( $object->ID, 'libsyn-post-episode-category-selection', true ); ?>
								<input type="hidden" value="<?php if ( !empty($libsynEpisodeCategorySelection) ) { echo $libsynEpisodeCategorySelection; } ?>" name="libsyn-post-episode-category-selection" id="libsyn-post-episode-category-selection"></input>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e( "Episode Thumbnail", $plugin->text_dom ); ?></th>
					  <td>
						<div id="libsyn-primary-media-settings">
							<div id="libsyn-new-media-settings">
								<p><?php echo __( 'Select image for episode thumbnail by clicking the button below.', $plugin->text_dom ); ?></p>
								<p>
								<?php $libsyn_episode_thumbnail = esc_attr( get_post_meta( $object->ID, 'libsyn-new-media-image', true ) ); ?>
								<button class="button button-primary libsyn-dashicions-image" id="libsyn-open-image" title="<?php echo esc_attr__( 'Click Here to Open the Image Manager', $plugin->text_dom ); ?>"><?php echo __( 'Select Episode Thumbnail', $plugin->text_dom ); ?></button></p>
								<p>
									<label for="libsyn-new-media-image"><?php echo __( 'Media Url', $plugin->text_dom ); ?></label>&nbsp;<input type="url" id="libsyn-new-media-image" name="libsyn-new-media-image" value="<?php echo ( !empty($libsyn_episode_thumbnail) ) ? $libsyn_episode_thumbnail : ''; ?>" pattern="https?://.+" <?php if ( !empty($libsyn_episode_thumbnail) ) echo 'readonly';?>></input>
									<button class="button libsyn-dashicions-close" id="libsyn-clear-image-button" title="<?php echo esc_attr__( 'Clear image url', $plugin->text_dom ); ?>"><?php echo __( 'Clear', $plugin->text_dom ); ?></button>
								</p>
							</div>
							<div id="libsyn-upload-asset-dialog" class="hidden" title="Upload Image">
								<p>Select Image to upload:</p>
								<br>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e( "Tags/Keywords", $plugin->text_dom ); ?></th>
					  <td>
						<div class="titlediv">
							<div class="titlewrap">
								<?php $libsynPostEpisodeKeywords = get_post_meta( $object->ID, 'libsyn-post-episode-keywords', true ); ?>
								<input id="libsyn-post-episode-keywords" type="text" autocomplete="off" value="<?php if ( !empty($libsynPostEpisodeKeywords) ) { echo $libsynPostEpisodeKeywords; } ?>" name="libsyn-post-episode-keywords" style="width:100%;" maxlength="255" placeholder="keyword1, keyword2, keyword3"></input>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><label for="libsyn-post-update-id3"><?php _e( "Update Id3 Tags", $plugin->text_dom ); ?></label></th>
					  <td>
						<div class="titlediv">
							<div class="titlewrap">
								<input type="checkbox" name="libsyn-post-episode-update-id3" id="libsyn-post-episode-update-id3" value="isLibsynUpdateId3" <?php if ( isset($isLibsynUpdateId3Checked) ) echo $isLibsynUpdateId3Checked; ?>></input>&nbsp;&nbsp;Allow Libsyn to update id3 tags with post data. <em>(mp3 files only)</em>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					<?php /* Apple Podcasts Settings */
						$libsyn_post_itunes = ( get_post_meta($object->ID, 'libsyn-post-itunes', true) ) ? ' checked="checked"' : '';
						$libsyn_itunes_episode_number = get_post_meta($object->ID, 'libsyn-post-episode-itunes-episode-number', true);
						$libsyn_itunes_season_number = get_post_meta($object->ID, 'libsyn-post-episode-itunes-season-number', true);
						$libsyn_itunes_episode_type = get_post_meta($object->ID, 'libsyn-post-episode-itunes-episode-type', true);
						$libsyn_itunes_episode_summary = get_post_meta($object->ID, 'libsyn-post-episode-itunes-episode-summary', true);
						$libsyn_itunes_episode_title = get_post_meta($object->ID, 'libsyn-post-episode-itunes-episode-title', true);
						$libsyn_itunes_episode_author = get_post_meta($object->ID, 'libsyn-post-episode-itunes-episode-author', true);
					?>
					  <th><label for="libsyn-post-itunes"><?php _e( "Apple Podcasts Optimization Tags", $plugin->text_dom ); ?></label></th>
					  <td>
						<div class="titlediv">
							<button class="button libsyn-dashicions-menu" id="libsyn-itunes-optimization-form-button" title="<?php echo esc_attr__( 'Apple Podcasts Optimization', $plugin->text_dom ); ?>" data-libsyn-wp-post-id="<?php if ( !empty($object->ID) ) { echo $object->ID; } ?>" value="false"><?php echo __( 'Apple Podcasts Optimization (Optional)', $plugin->text_dom ); ?></button>
							<div class="titlewrap">
								<br />
								<div id="libsyn-itunes-optimization-container" style="display:none;">
									<fieldset>
										<legend class="screen-reader-text"><?php _e( "Apple Podcasts Optimization", $plugin->text_dom ); ?></legend>
										<p style="padding-bottom:4px;">
											<label for="libsyn-post-episode-itunes-explicit" style="width:20%;max-width:120px;font-weight:600;">
												<?php _e( "Explicit Content", $plugin->text_dom ); ?>
											</label>
											<select id="libsyn-post-episode-itunes-explicit" name="libsyn-post-episode-itunes-explicit">
												<option value="no">Not Set</option>
												<option value="clean">Clean</option>
												<option value="yes">Explicit</option>
											</select>
										</p>
										<p style="padding-bottom:4px;">
											<label for="libsyn-post-episode-itunes-episode-number" style="width:20%;max-width:120px;font-weight:600;"><?php echo __( 'Episode Number', $plugin->text_dom ); ?></label>
											<input type="number" id="libsyn-post-episode-itunes-episode-number" name="libsyn-post-episode-itunes-episode-number" value="<?php echo (!empty($libsyn_itunes_episode_number))?$libsyn_itunes_episode_number:''; ?>" min="1" max="99999"></input>
										</p>
										<p style="padding-bottom:4px;">
											<label for="libsyn-post-episode-itunes-season-number" style="width:20%;max-width:120px;font-weight:600;"><?php echo __( 'Season Number', $plugin->text_dom ); ?></label>
											<input type="number" id="libsyn-post-episode-itunes-season-number" name="libsyn-post-episode-itunes-season-number" value="<?php echo (!empty($libsyn_itunes_season_number))?$libsyn_itunes_season_number:''; ?>" min="1" max="99999"></input>
										</p>
										<p style="padding-bottom:4px;">
											<label for="libsyn-post-episode-itunes-episode-type" style="width:20%;max-width:120px;font-weight:600;"><?php echo __( 'Episode Type', $plugin->text_dom ); ?></label>
											<select id="libsyn-post-episode-itunes-episode-type" style="max-width:330px;" name="libsyn-post-episode-itunes-episode-type" value="<?php echo ( !empty($libsyn_itunes_episode_type) ) ? $libsyn_itunes_episode_type : ''; ?>">
												<option name="none" value="" <?php echo ( empty($libsyn_itunes_episode_type) || $libsyn_itunes_episode_type == "null" ) ? 'selected ' : ''; ?>>--Select an option--></option>
												<option name="none" value="full" <?php echo ( !empty($libsyn_itunes_episode_type) && $libsyn_itunes_episode_type == "full" ) ? 'selected ' : ''; ?>>Full</option>
												<option name="none" value="trailer" <?php echo ( !empty($libsyn_itunes_episode_type) && $libsyn_itunes_episode_type == "trailer" ) ? 'selected ' : ''; ?>>Trailer</option>
												<option name="none" value="bonus" <?php echo ( !empty($libsyn_itunes_episode_type) && $libsyn_itunes_episode_type == "bonus" ) ? 'selected ' : ''; ?>>Bonus</option>
											</select>
										</p>
										<p style="padding-bottom:4px;">
											<label for="libsyn-post-episode-itunes-episode-summary" style="width:20%;max-width:120px;font-weight:600;"><?php echo __( 'Episode Summary', $plugin->text_dom ); ?></label>
											<textarea wrap="hard" maxlength="4000" rows="8" cols="50" id="libsyn-post-episode-itunes-episode-summary" name="libsyn-post-episode-itunes-episode-summary"><?php echo ( !empty($libsyn_itunes_episode_summary) ) ? $libsyn_itunes_episode_summary : ''; ?></textarea>
										</p>
										<p style="padding-bottom:4px;">
											<label for="libsyn-post-episode-itunes-episode-title" style="width:20%;max-width:120px;font-weight:600;"><?php echo __( 'Episode Title', $plugin->text_dom ); ?></label>
											<input type="text" id="libsyn-post-episode-itunes-episode-title" style="max-width:330px;" name="libsyn-post-episode-itunes-episode-title" value="<?php echo ( !empty($libsyn_itunes_episode_title) ) ? $libsyn_itunes_episode_title : ''; ?>"></input>
										</p>
										<p style="padding-bottom:4px;">
											<label for="libsyn-post-episode-itunes-episode-author" style="width:20%;max-width:120px;font-weight:600;"><?php echo __( 'Episode Author', $plugin->text_dom ); ?></label>
											<input type="text" id="libsyn-post-episode-itunes-episode-author" style="max-width:330px;" name="libsyn-post-episode-itunes-episode-author" value="<?php echo ( !empty($libsyn_itunes_episode_author) ) ? $libsyn_itunes_episode_author : ''; ?>"></input>
										</p>
									</fieldset>
								</div>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e( "Destinations", $plugin->text_dom ); ?></th>
					  <td>
						<div class="titlediv">
							<button class="button libsyn-dashicions-menu" id="libsyn-advanced-destination-form-button" title="<?php echo esc_attr__( 'Advanced Destination Publishing', $plugin->text_dom ); ?>" data-libsyn-wp-post-id="<?php if ( !empty($object->ID) ) { echo $object->ID; } ?>" value="false"><?php echo __( 'Advanced Destination Publishing (Optional)', $plugin->text_dom ); ?></button>
							<div class="titlewrap">
								<br />
								<div id="libsyn-advanced-destination-form-container" style="display:none;">
								<!--<div id="libsyn-advanced-destination-form-container" >-->
								<?php
									$destination = new \Libsyn\Service\Destination();
									if ( !$api ) {
										$libsyn_error = true;
										$destinations = false;
										update_post_meta($object->ID, 'libsyn-post-error_api', 'true');
										if ( $plugin->hasLogger ) $plugin->logger->error( "Post:\tApi is false (likely refresh token has expired)" );
									} else {
										$destinations = $plugin->getDestinations($api);
										if ( $destinations ) {
											$destination_args = array(
												'singular'=> 'libsyn_destination' //Singular label
												,'plural' => 'libsyn_destinations' //plural label, also this well be one of the table css class
												,'ajax'   => true //We won't support Ajax for this table
												,'screen' => get_current_screen()
											);
											//remove Wordpress Destination
											foreach($destinations->destinations as $key => $working_destination)
												if ( $working_destination->destination_type === 'WordPress' ) unset($destinations->destinations->{$key});

											$published_destinations = get_post_meta($object->ID, 'libsyn-destination-releases', true);
											//Prepare Table of elements
											$libsyn_destination_wp_list_table = new \Libsyn\Service\Table($destination_args, $destinations->destinations);
											if ( !empty($published_destinations) ) {
												$libsyn_destination_wp_list_table->item_headers = array(
													'cb' => '<input type=\"checkbox\"></input>'
													,'id' => 'destination_id'
													,'destination_name' => 'Destination Name'
													,'published_status' => 'Published Status'
													// ,'destination_type' => 'Destination Type'
													,'release_date' => 'Release Date'
													,'expiration_date' => 'Expiration Date'
													// ,'creation_date' => 'Creation Date'
												);
											} else {
												$libsyn_destination_wp_list_table->item_headers = array(
													'cb' => '<input type=\"checkbox\"></input>'
													,'id' => 'destination_id'
													,'destination_name' => 'Destination Name'
													// ,'destination_type' => 'Destination Type'
													,'release_date' => 'Release Date'
													,'expiration_date' => 'Expiration Date'
													// ,'creation_date' => 'Creation Date'
												);
											}
											$libsyn_destination_wp_list_table->prepare_items();
											$destination->formatDestinationsTableData($destinations, $object->ID);
										}
									}
									?>
									<br />
									<div id="libsyn-post-episode-simple-download-div">
										<strong>Download Availability:</strong><br />
										<input type="radio" name="libsyn-post-episode-simple-download" id="libsyn-post-episode-simple-download-available" value="available"></input>&nbsp;Media Files are always available<br />
										<input type="radio" name="libsyn-post-episode-simple-download" id="libsyn-post-episode-simple-download-release_date"  value="release_date"></input>&nbsp;Media Files are available based on release schedule<br />
									</div>
									<?php
										echo "<pre>";
										$libsyn_advanced_destination_form_data = $destination->formatDestinationFormData($destinations, $object->ID);
										echo "</pre>";
									?>

									<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
									<form id="destinations-table" method="get">
										<!-- Now we can render the completed list table -->
										<?php if ( !empty($libsyn_destination_wp_list_table) ) $libsyn_destination_wp_list_table->display(); ?>
										<!-- Destination page-specific Form Data -->
										<?php $libsyn_advanced_destination_form_data = get_post_meta( $object->ID, 'libsyn-post-episode-advanced-destination-form-data', true ); ?>
										<?php if ( empty($libsyn_advanced_destination_form_data) ) $libsyn_advanced_destination_form_data = $destination->formatDestinationFormData($destinations, $object->ID); ?>
										<?php $libsyn_advanced_destination_form_data = get_post_meta( $object->ID, 'libsyn-post-episode-advanced-destination-form-data', true ); ?>
										<?php $libsyn_advanced_destination_form_data_enabled = get_post_meta( $object->ID, 'libsyn-post-episode-advanced-destination-form-data-enabled', true ); ?>
										<input id="libsyn-post-episode-advanced-destination-form-data-input" name="libsyn-post-episode-advanced-destination-form-data-input" type="hidden"></input>
										<input id="libsyn-post-episode-advanced-destination-form-data-input-enabled" name="libsyn-post-episode-advanced-destination-form-data-input-enabled" type="hidden" value="<?php if ( isset($libsyn_advanced_destination_form_data_enabled) && !empty($libsyn_advanced_destination_form_data_enabled) && ( $libsyn_advanced_destination_form_data_enabled === 'true' ) ) echo $libsyn_advanced_destination_form_data_enabled; ?>"></input>
										<script id="libsyn-post-episode-advanced-destination-form-data" type="application/json"><?php if ( !empty($libsyn_advanced_destination_form_data) ) {  echo $libsyn_advanced_destination_form_data; } ?></script>
									</form>
								</div>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php /* Footer Area */ ?></th>
					  <td>
						<div class="titlediv">
							<div class="titlewrap">
								<br />
								<p class="smalltext" style="font-style:italic;"><span style='color:red;'>*</span>&nbsp;Indicates required fields.</p>
							</div>
						</div>
					  </td>
				</tr>
			</table>
		</div>
		<?php

	}

}
?>
