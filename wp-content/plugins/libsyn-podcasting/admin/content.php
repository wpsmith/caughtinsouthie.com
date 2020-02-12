<?php
$plugin = new Libsyn\Service();
$sanitize = new Libsyn\Service\Sanitize();
$current_user_id = $plugin->getCurrentUserId();
$api = $plugin->retrieveApiById($current_user_id);
$render = true;
$error = false;
$libsyn_text_dom = $plugin->getTextDom();

/* Handle saved api */
if ($api instanceof \Libsyn\Api && !$api->isRefreshExpired()){
	$refreshApi = $api->refreshToken();
	if($refreshApi) { //successfully refreshed
		$api = $plugin->retrieveApiById($current_user_id);
	} else { //in case of a api call error...
		$handleApi = true;
		$clientId = (!isset($clientId))?$api->getClientId():$clientId;
		$clientSecret = (!isset($clientSecret))?$api->getClientSecret():$clientSecret;
		$api = false;
		if(isset($showSelect)) unset($showSelect);
	}
}

if(isset($_POST['msg'])) $msg = $_POST['msg'];
if(isset($_POST['error'])) $error = ($_POST['error']==='true')?true:false;

/* Handle Form Submit */
if (isset($_POST['submit'])||isset($_POST['libsyn_settings_submit'])) { //has showSelect on form.
	if($api instanceof \Libsyn\Api) { //Brand new setup or changes?
		if($_POST['submit']==='Save Player Settings') { //has Player Settings Update
			if(isset($_POST['clear-settings-data'])) {
				$check = $sanitize->clear_settings($_POST['clear-settings-data']);
				if($check === true) {
					$plugin->removeSettings($api);
					$msg = "Settings Cleared";
					echo $plugin->redirectUrlScript(admin_url().'?page=LibsynSettings&msg='.$msg);
				} else {
					$msg = "There was a problem when trying to remove settings.";
					$error = true;
				}
			}
			//sanitize player_settings
			$playerSettings = array();
			if(!isset($_POST['player_use_thumbnail'])) $playerSettings['player_use_thumbnail'] = '';
				else $playerSettings['player_use_thumbnail'] = $_POST['player_use_thumbnail'];
			$playerSettings['player_use_theme'] = $_POST['player_use_theme'];
			$playerSettings['player_height'] = $_POST['player_height'];
			$playerSettings['player_width'] = $_POST['player_width'];
			$playerSettings['player_placement'] = $_POST['player_placement'];
			$playerSettings['player_custom_color'] = $_POST['player_custom_color'];

			if(!isset($_POST['player_use_download_link'])) $playerSettings['player_use_download_link'] = '';
				else $playerSettings['player_use_download_link'] = $_POST['player_use_download_link'];
			$playerSettings['player_use_download_link_text'] = $_POST['player_use_download_link_text'];
			$playerSettings_clean = $sanitize->player_settings($playerSettings);

			if(!$playerSettings_clean||empty($playerSettings_clean)) { //malformed data
				$error =  true; $msg = __('Something wrong with player input settings, please try again.', $libsyn_text_dom);
			} elseif(is_array($playerSettings_clean)) { //looks good update options
				foreach ($playerSettings_clean as $key => $val) {
					update_user_option($current_user_id, 'libsyn-podcasting-'.$key, $val, false);
				}
			}

		} elseif ($_POST['submit']==='Save Changes' || $_POST['libsyn_settings_submit']==='Save Changes') { //has config changes or update
			if(!is_null($api->getClientId())) { //check for cleared data
				if (isset($_POST['showSelect'])) $api->setShowId($_POST['showSelect']);
				if($api->getClientSecret()!==$sanitize->clientSecret($_POST['clientSecret'])) $api->setClientSecret($sanitize->clientSecret($_POST['clientSecret']));
				if($api->getClientId()!==$sanitize->clientId($_POST['clientId'])) $api->setClientId($sanitize->clientId($_POST['clientId']));
				$update = $plugin->updateSettings($api);
				if($update!==false) $msg = __('Settings Updated',$libsyn_text_dom);
			} else { //doesn't have client id data saved (must be cleared data update)
				if(isset($_POST['clientId'])&&isset($_POST['clientSecret'])) {
					update_user_option($current_user_id, 'libsyn-podcasting-client', array('id' => $sanitize->clientId($_POST['clientId']), 'secret' => $sanitize->clientSecret($_POST['clientSecret'])), false);
					$clientId = $_POST['clientId'];
				}
			}
		}
	} else { // for brand new setup just store in session through redirects.
		if(isset($_POST['clientId'])&&isset($_POST['clientSecret'])) {
			update_user_option($current_user_id, 'libsyn-podcasting-client', array('id' => $sanitize->clientId($_POST['clientId']), 'secret' => $sanitize->clientSecret($_POST['clientSecret'])), false);
			$clientId = $_POST['clientId'];
		}
	}
}

/* Handle API Creation/Update*/
if((!$api)||($api->isRefreshExpired())) { //does not have $api setup yet in WP
	$render = false;
}

/* Set Notifications */
global $libsyn_notifications;
do_action('libsyn_notifications');
?>

<?php wp_enqueue_style( 'libysn-meta-form', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_boxes.css' )); ?>
<?php wp_enqueue_style( 'libsyn-meta-form', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_form.css' )); ?>
<?php wp_enqueue_style( 'libsyn-dashicons', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/dashicons.css' )); ?>
<?php wp_enqueue_style( 'animate', plugins_url(LIBSYN_DIR . '/lib/css/animate.min.css')); ?>
<?php wp_enqueue_script( 'jquery-easing', plugins_url(LIBSYN_DIR . '/lib/js/jquery.easing.min.js')); ?>

	<style media="screen" type="text/css">
	.code { font-family:'Courier New', Courier, monospace; }
	.code-bold {
		font-family:'Courier New', Courier, monospace;
		font-weight: bold;
	}
	</style>

	<div class="wrap">
	  <?php if (isset($msg)) echo $plugin->createNotification($msg, $error); ?>
	  <h2><?php _e("Publisher Hub - Content", $libsyn_text_dom); ?><span style="float:right;"><a href="https://www.libsyn.com/"><img src="<?php _e(plugins_url( LIBSYN_DIR . '/lib/images/libsyn_dark-small.png'), $libsyn_text_dom); ?>" title="Libsyn Podcasting" height="28px"></a></span></h2>
	  <form name="<?php echo LIBSYN_NS . "form" ?>" id="<?php echo LIBSYN_NS . "form" ?>" method="post" action="javascript:void(0);">
		 <div id="poststuff">
		  <div id="post-body">
			<div id="post-body-content">
			<?php if((isset($api) && ($api !== false)) || $render) { ?>

			<!-- BOS Existing API -->
			  <div class="stuffbox libsyn-meta-box" style="width:93.5%">
				<h3 class="hndle"><span><?php _e("Post Actions", $libsyn_text_dom); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <table class="form-table">
					<tr valign="top">
					  <th>Create New Episode</th>
					  <td valign="top">
						<div class="libsyn-help-tip-container" style="position:relative;">
							<?php submit_button(__('Create New Episode', $libsyn_text_dom), 'button-primary libsyn-dashicions-plus-alt', 'libsyn_create_post', false, array('id' => 'submit_create_post_top')); ?>
							&nbsp;
							<div class="libsyn-help-tip" style="display:inline;">
								<p>Creates a new Wordpress post, including the Libsyn HTML5 Media Player, and creates an episode in Libsyn that releases to your destinations.</p>
							</div>
							<br />
							<br />
						</div>
					  </td>
					</tr>
					<tr valign="top">
					  <th><?php _e("Previously Published", $libsyn_text_dom); ?></th>
					  <td>
						<div style="padding-top:4px;">
							<select name="showSelect" id="showSelect" class="icons" autofocus>
								<?php
									$episodes = $plugin->getEpisodes(array('show_id'=>$api->getShowId(),'limit' => 5000, 'page'=>1));
									if(!empty($episodes->_embedded->item) && is_array($episodes->_embedded->item)) {
										echo "<option value=\"\" disabled selected>Select show to load preview</option>";
										foreach($episodes->_embedded->item as $episode) {
											if(!empty($episode->{'show_id'}) && $api->getShowId()==$episode->{'show_id'}) {
												if(!empty($episode->{'thumbnail'}) && !empty($episode->{'thumbnail'}->{'url'})) {
													echo  "<option value=\"".$sanitize->itemId($episode->{'id'})."\" data-icon=\"".$sanitize->url_raw($episode->{'thumbnail'}->{'url'})."\" class=\"left\">".$episode->{'item_title'}."</option>";
												} else {
													echo  "<option value=\"".$sanitize->itemId($episode->{'id'})."\">".$episode->{'item_title'}."</option>";
												}
											}
										}
									}
								?>
							</select>
							<label><i>Select Episode from list</i></label>
						</div>
					  </td>
					</tr>
					<?php if(is_int($api->getShowId())) { ?>
					<tr valign="top">
						<th></th>
						<td>
							<div class="inside" style="margin: 15px;">Libsyn is connected to your Wordpress account successfully.</div>
						</td>
					</tr>
					<?php } ?>
				  </table>
				</div>
			  </div>
			<!-- EOS Existing API -->

			<?php } else { ?>
			<!-- BOS Existing API -->
			  <div class="stuffbox libsyn-meta-box" style="width:93.5%">
				<h3 class="hndle"><span><?php _e("Plugin needs configured", $libsyn_text_dom); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <p style="font-size: 1.8em;"><?php _e("The Libsyn Publisher Hub is either not setup or something is wrong with the configuration, please visit the <a href='".admin_url('admin.php?page=LibsynSettings')."'>settings page</a>.", $libsyn_text_dom); ?></p>
				</div>
			  </div>
			<!-- EOS Existing API -->
			<?php } ?>
			<!-- BOS Libsyn WP Post Page -->
			<div class="stuffbox libsyn-meta-box" id="libsyn-wp-post-page" style="display:none;width:93.5%;overflow-x:auto;">

			</div>
			<!-- EOS Libsyn WP Post Page -->
			</div>
		  </div>
		</div>
	  </form>
	</div>
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				//check ajax
				var check_ajax_url = "<?php echo $sanitize->text($plugin->admin_url() . '?action=libsyn_check_url&libsyn_check_url=1'); ?>";
				var ajax_error_message = "<?php __('Something went wrong when trying to load your site\'s base url.
						Please make sure your "Site Address (URL)" in Wordpress settings is correct.', LIBSYN_DIR); ?>";
				$.getJSON( check_ajax_url).done(function(json) {
					if(json){
						//success do nothing
					} else {
						//redirect to error out
						var ajax_error_url = "<?php echo $plugin->admin_url('admin.php').'?page=LibsynContent&error=true&msg='; ?>" + ajax_error_message;
						if (typeof window.top.location.href == "string") window.top.location.href = ajax_error_url;
								else if(typeof document.location.href == "string") document.location.href = ajax_error_url;
									else if(typeof window.location.href == "string") window.location.href = ajax_error_url;
										else alert("Unknown javascript error 1028.  Please report this error to support@libsyn.com and help us improve this plugin!");
					}
				}).fail(function(jqxhr, textStatus, error) {
						//redirect to error out
						var ajax_error_url = "<?php echo $plugin->admin_url('admin.php').'?page=LibsynContent&error=true&msg='; ?>" + ajax_error_message;
						if (typeof window.top.location.href == "string") window.top.location.href = ajax_error_url;
								else if(typeof document.location.href == "string") document.location.href = ajax_error_url;
									else if(typeof window.location.href == "string") window.location.href = ajax_error_url;
										else alert("Unknown javascript error 1029.  Please report this error to support@libsyn.com and help us improve this plugin!");
				});
			});
		})(jQuery);
	</script>
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				$("*[id*='submit_create_post_top']").each(function() {
					$(this).click(function(event){
						// event.preventDefault();
						var new_post_url = "<?php echo $plugin->admin_url('post-new.php'); ?>" + "?isLibsynPost=true";
						if (typeof window.top.location.href == 'string') window.top.location.href = new_post_url;
							else if(typeof document.location.href == 'string') document.location.href = new_post_url;
								else if(typeof window.location.href == 'string') window.location.href = new_post_url;
									else alert('Unknown javascript error 1023.  Please report this error to support@libsyn.com and help us improve this plugin!');

					});
				});

				//create html table
				function createTable(tableData) {
					var table = document.createElement('table');
					table.className = "wp-list-table widefat striped posts libsyn-posts-table";
					table.setAttribute("cellspacing", 0);
					table.setAttribute("width", "68%");
					table.setAttribute("style", "overflow-x:auto;");

					//Modify Attributes
					if(typeof tableData[0] !== 'undefined') {
						// if(typeof tableData[0].primary_content != 'undefined') delete tableData[0].primary_content;
						if(typeof tableData[0]._links != 'undefined') delete tableData[0]._links;
						if(typeof tableData[0].expiration_date != 'undefined') delete tableData[0].expiration_date;
						if(typeof tableData[0].extra_rss_tags != 'undefined') delete tableData[0].extra_rss_tags;
						if(typeof tableData[0].id != 'undefined') delete tableData[0].id;
						if(typeof tableData[0].show_id != 'undefined') delete tableData[0].show_id;
						if(typeof tableData[0].item_slug != 'undefined') delete tableData[0].item_slug;
						if(typeof tableData[0].is_draft != 'undefined') delete tableData[0].is_draft;
						if(typeof tableData[0].qc_state != 'undefined') delete tableData[0].qc_state;
						if(typeof tableData[0].itunes_explicit != 'undefined') delete tableData[0].itunes_explicit;
						if(typeof tableData[0].permalink_type != 'undefined') delete tableData[0].permalink_type;
						if(typeof tableData[0].custom_permalink_url != 'undefined') delete tableData[0].custom_permalink_url;
						if(typeof tableData[0].guid != 'undefined') delete tableData[0].guid;
						if(typeof tableData[0].release_date != 'undefined') tableData[0].release_date = new Date(tableData[0].release_date);
						if(typeof tableData[0].creation_date != 'undefined') tableData[0].creation_date = new Date(tableData[0].creation_date);


						//set Header
						var thead = document.createElement('thead');
						var row = document.createElement('tr');
						var head = document.createElement('th');
						var head2 = document.createElement('th');
						head.setAttribute("scope", "col");
						head2.setAttribute("scope", "col");
						head.appendChild(document.createTextNode("Attribute"));
						head.className = "column-primary sortable desc column-attribute";
						head2.appendChild(document.createTextNode("Description"));
						head2.className = "manage-column column-description";
						row.appendChild(head);
						row.appendChild(head2);
						thead.appendChild(row);
						table.appendChild(thead);

						//set Footer
						var tfoot = document.createElement('tfoot');
						var row = document.createElement('tr');
						var foot = document.createElement('th');
						var foot2 = document.createElement('th');
						foot.setAttribute("scope", "col");
						foot2.setAttribute("scope", "col");
						foot.appendChild(document.createTextNode("Attribute"));
						foot.className = "column-primary sortable desc column-attribute";
						foot2.appendChild(document.createTextNode("Description"));
						foot2.className = "manage-column column-description";
						row.appendChild(foot);
						row.appendChild(foot2);
						tfoot.appendChild(row);
						table.appendChild(tfoot);

						// Set Body
						var tableBody = document.createElement('tbody');
						tableBody.className = "the-list";

						$.each(tableData[0], function(key, value) {
							var row = document.createElement('tr');
							row.className = "format-standard";
							var head = document.createElement('th');
							head.className = "column-attribute";
							head.setAttribute("scope", "col");
							head.appendChild(document.createTextNode(ucwords(key.replace('_', ' ').replace('item ', ''))));
							var cell = document.createElement('td');
							cell.className = "column-description";
							if(value instanceof Date) {
								cell.appendChild(document.createTextNode(julianDateTime(value)));
							} else {
								if(value == null) {
									cell.appendChild(document.createTextNode("None"));
								} else if (value == false) {
									cell.appendChild(document.createTextNode("No"));
								} else if (value == true) {
									cell.appendChild(document.createTextNode("Yes"));
								} else if (typeof value === 'object') {
									//add preview
									var preview = document.createElement('div');
									preview.setAttribute("id", "libsyn-upload-media-preview");
									var mime_type = value.mime_type;
									if((mime_type !== typeof "undefined") && (typeof mime_type === "function")) {
										mime_type = mime_type.replace("x-","");
									}
									var media_url = value.secure_url;
									if((preview_url !== typeof "undefined") && (typeof preview_url === "function")) {
										var preview_url = media_url.replace("libsyn.com/","libsyn.com/preview/");
									} else if(typeof media_url !== "undefined") {
										preview_url = media_url;
									}

									if(key == "thumbnail") {
										var thumbnail = value.url;
										if((thumbnail !== typeof "undefined") && (typeof thumbnail === "string")) {
											thumbnail = thumbnail.replace("x-","");
											preview.innerHTML = '<image src="' + thumbnail + '" style=\"max-width: 360px;\"/>';
										}
									}

									if(value.audio_codec != null || value.video_codec != null){
										if(value.video_codec != null && value.video_codec != 'undefined' && value.video_codec.length > 0) {
											var file_class = 'video';
										} else if(value.audio_codec != null && value.audio_codec != 'undefined'  && value.audio_codec.length > 0) {
											var file_class = 'audio';
										} else {
											var file_class = 'frame';
										}
										if(mime_type != 'undefined' && preview_url != 'undefined'){
											if(file_class == 'video') {

												var previewHTML = '<'+file_class+' width="640" controls>';
												previewHTML += '<source src="'+preview_url+'" type="'+mime_type+'">';
											} else if(file_class == 'audio') {
												var previewHTML = '<'+file_class+' width="640" controls>';
												previewHTML += '<source src="'+preview_url+'" type="'+mime_type+'">';
											} else {
												var previewHTML = '<'+file_class+' width="640" controls>';
												previewHTML += '<source src="'+preview_url+'" type="'+mime_type+'">';
											}
											previewHTML += 'Your browser does not support HTML5 audio/video </'+file_class+'>';
											preview.innerHTML = '';
											// $("#libsyn-upload-media-preview").empty().html(previewHTML);
											preview.innerHTML = previewHTML;
											preview = preview.firstChild;
										}
									}
									cell.appendChild(preview);
								} else {
									cell.appendChild(document.createTextNode(stripTags(value)));
								}
							}
							row.appendChild(head);
							row.appendChild(cell);
							tableBody.appendChild(row);
						});
						table.appendChild(tableBody);
						$("#libsyn-wp-post-page").empty().append('<h3 class="hndle"><span>Episode Information:  ' + tableData[0].item_title + '</span></h3><div class="inside" id="' + tableData[0].id +'-info-box"></div>').fadeIn('fast');
						var editEpisodeButton = '<div style="padding:12px;"><button type="button" name="libsyn_edit_post" id="submit_edit_post" onClock="document.getElementById(\'submit_edit_post\').value=\'Edit Episode\';" class="button libsyn-dashicions-edit">Edit Episode</button>&nbsp;&nbsp;<button type="button" name="libsyn_create_post" id="submit_create_post" class="button libsyn-dashicions-add">Create New Episode</button></div>';
						$("#" + tableData[0].id + "-info-box").empty().append(table).append(editEpisodeButton);
					}

					$(".libsyn-posts-table > thead > tr > th").css("padding-left", "4px");
					$(".libsyn-posts-table > tfoot > tr > th").css("padding-left", "4px");
					$(".libsyn-posts-table > thead > tr > th").css("font-weight", "600");
					$(".libsyn-posts-table > tfoot > tr > th").css("font-weight", "600");
					$(".column-attribute").css("width", "4%");
					$(".column-description").css("width", "22%");

					$("*[id*='submit_create_post']").each(function() {
						$(this).click(function(event){
							// event.preventDefault();
							var new_post_url = "<?php echo $plugin->admin_url('post-new.php'); ?>" + "?isLibsynPost=true";
							if (typeof window.top.location.href == 'string') window.top.location.href = new_post_url;
								else if(typeof document.location.href == 'string') document.location.href = new_post_url;
									else if(typeof window.location.href == 'string') window.location.href = new_post_url;
										else alert('Unknown javascript error 1023.  Please report this error to support@libsyn.com and help us improve this plugin!');

						});
					});
					$("*[id*='submit_edit_post']").each(function() {
						$(this).click(function(event){
							// event.preventDefault();
							var edit_post_url = "<?php echo $plugin->admin_url('post-new.php'); ?>?libsyn_edit_post_id=" + $("#showSelect :selected").val();
							if (typeof window.top.location.href == 'string') window.top.location.href = edit_post_url;
								else if(typeof document.location.href == 'string') document.location.href = edit_post_url;
									else if(typeof window.location.href == 'string') window.location.href = edit_post_url;
										else alert('Unknown javascript error 1024.  Please report this error to support@libsyn.com and help us improve this plugin!');

						});

					});


				}

				$("#showSelect").change(function(){
					<?php if(!empty($episodes->_embedded->item)) { ?>
						var libsyn_posts = [<?php echo json_encode($episodes->_embedded->item);?>];
					<?php } else { ?>
						var libsyn_posts = [];
					<?php } ?>
					var item_id = parseInt($(this).val());
					var selectedPostObj = $.grep(libsyn_posts[0], function(el, idx) {
						return el.id == item_id;
					});
					createTable(selectedPostObj);
				});
				function ucwords (str) {
				  return (str + '')
					.replace(/^(.)|\s+(.)/g, function ($1) {
					  return $1.toUpperCase()
					})
				}
				function julianDateTime(date) {
					var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
					var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
					var day = days[ date.getDay() ];
					var month = months[ date.getMonth() ];
					var hours = date.getHours();
					var minutes = date.getMinutes();
					var ampm = hours >= 12 ? 'pm' : 'am';
					hours = hours % 12;
					hours = hours ? hours : 12; // the hour '0' should be '12'
					minutes = minutes < 10 ? '0'+minutes : minutes;
					var strTime = hours + ':' + minutes + ' ' + ampm;
					return month + ' ' + getOrdinal(date.getDate().toString()) + ', ' + date.getFullYear().toString() + '  ' + strTime;
				}
				function getOrdinal(n) {
					var s=["th","st","nd","rd"],
					v=n%100;
					return n+(s[(v-20)%10]||s[v]||s[0]);
				}
				function stripTags(html) {
					var tmp = document.createElement("DIV");
					tmp.innerHTML = html;
					return tmp.textContent || tmp.innerText || "";
				}
			});
		})(jQuery);
	</script>
