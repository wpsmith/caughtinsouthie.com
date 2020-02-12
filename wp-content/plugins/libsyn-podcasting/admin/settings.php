<?php

$authorized = false;
$plugin = new Libsyn\Service();
$sanitize = new Libsyn\Service\Sanitize();
$current_user_id = $plugin->getCurrentUserId();
$api = $plugin->retrieveApiById($current_user_id, true);

$render = true;
$error = false;
$libsyn_text_dom = $plugin->getTextDom();

//Grabs needed params
if(!empty($_GET['libsyn_code'])) $libsyn_code = $_GET['libsyn_code'];
if(!empty($_GET['libsyn_authorized'])) $authorized = $_GET['libsyn_authorized'];
if(isset($_REQUEST['msg'])) $msg = $_REQUEST['msg'];
if(isset($_REQUEST['error'])) $error = ($_REQUEST['error']==='true')?true:false;
if(!isset($_REQUEST['redirect_url'])) {
	if(isset($_GET)) $redirectUri = $plugin->admin_url('admin.php').'?'.http_build_query($_GET);
		else $redirectUri = $plugin->admin_url('admin.php');
} else {
	$redirectUri = $_REQUEST['redirect_url']; 
}
if(!empty($redirectUri)) { //bug fix for passing settings cleared message
	$redirectUri = $sanitize->cleanRedirectUrl($redirectUri);
}

/* Handle saved api */
if ($api instanceof \Libsyn\Api) {
	if($plugin->hasLogger) $plugin->logger->info("Settings:\tLibsyn\Api Set");
	$isRefreshExpired = $api->isRefreshExpired();
	if($isRefreshExpired) { //refresh has expired
		if($plugin->hasLogger) $plugin->logger->info("Settings:\tAPI Refresh Expired");
		$refreshApi = $api->refreshToken(); 
	} else {
		$refreshApi = $api->refreshToken(); 
	}
	//check refresh
	if($refreshApi) { //successfully refreshed
		if($plugin->hasLogger) $plugin->logger->info("Settings:\trefreshAPI:\t".$refreshApi);
		$api = $plugin->retrieveApiById($current_user_id); 
	} else { //in case of a api call error...
		$handleApi = true; 
		$clientId = (!isset($clientId))?$api->getClientId():$clientId; 
		$clientSecret = (!isset($clientSecret))?$api->getClientSecret():$clientSecret; 
		$api = false;
		if(isset($showSelect)) unset($showSelect);
	}
}

/* Handle Form Submit */
if (isset($_REQUEST['submit']) || isset($_REQUEST['libsyn_settings_submit'])) { //has showSelect on form.
	if($plugin->hasLogger) $plugin->logger->info("Post Submit");
	if($api instanceof \Libsyn\Api) { //Brand new setup or changes?
		if(!empty($_REQUEST['submit']) && (($_REQUEST['submit']==='Save Player Settings' || $_REQUEST['submit']==='Save+Player+Settings') || ($_REQUEST['submit']==='Save Settings' || $_REQUEST['submit']==='Save+Settings'))) { //has Player Settings Update
			
			//sanitize clear_settings
			if(!empty($_REQUEST['clear-settings-data'])) {
				$check = $sanitize->clear_settings($_REQUEST['clear-settings-data']);
				if($check === true) {
					$remove_settings = $plugin->removeSettings($api);
					$error_message = "";
					if($remove_settings) {
						$msg = __("Settings Cleared", $libsyn_text_dom);
					} else {
						$msg = __("Something went wrong when trying to clear settings", $libsyn_text_dom);
						$error_message = "&error=true";
					}
					echo $plugin->redirectUrlScript($plugin->admin_url('admin.php').'?page=LibsynSettings&msg='.$msg.$error_message); 
				} else {
					$msg = __("There was a problem when trying to clear settings", $libsyn_text_dom);
					if($plugin->hasLogger) $plugin->logger->error("Settings:\t".$msg);
					$error = true;
				}
			}
			
			//sanitize player_settings
			$playerSettings = array();
			if(!isset($_REQUEST['player_use_thumbnail'])) $playerSettings['player_use_thumbnail'] = '';
				else $playerSettings['player_use_thumbnail'] = $_REQUEST['player_use_thumbnail'];
			$playerSettings['player_use_theme'] = $_REQUEST['player_use_theme'];
			$playerSettings['player_height'] = $_REQUEST['player_height'];
			$playerSettings['player_width'] = $_REQUEST['player_width'];
			$playerSettings['player_placement'] = $_REQUEST['player_placement'];
			$playerSettings['player_custom_color'] = $_REQUEST['player_custom_color'];
			if(!isset($_REQUEST['player_use_download_link'])) $playerSettings['player_use_download_link'] = '';
				else $playerSettings['player_use_download_link'] = $_REQUEST['player_use_download_link'];
			$playerSettings['player_use_download_link_text'] = $_REQUEST['player_use_download_link_text'];
			$playerSettings_clean = $sanitize->player_settings($playerSettings);
			if(empty($playerSettings_clean)) { //malformed data
				$error =  true; $msg = __('Something wrong with player input settings, please try again.', $libsyn_text_dom);
				if($plugin->hasLogger) $plugin->logger->error("Settings:\tSomething went wrong with player input settings.");
			} elseif(is_array($playerSettings_clean)) { //looks good update options
				if($plugin->hasLogger) $plugin->logger->info("Settings:\tUpdating Player Settings");
				foreach ($playerSettings_clean as $key => $val) {
					if($plugin->hasLogger) $plugin->logger->info("Settings:\tlibsyn-podcasting-".$key.":\t".$val);
					try {
						update_user_option($current_user_id, 'libsyn-podcasting-'.$key, $val, false);
					} catch (Exception $e) {
						if($plugin->hasLogger) $plugin->logger->error("Settings:\tProblem updating:".$key.":\t".$val."\tError:\t".$e);
					}
					
				}
			}

			//sanitize additional_settings
			$additionalSettings = array();
			if ( !isset($_REQUEST['settings_add_podcast_metadata']) ) {
				$additionalSettings['settings_add_podcast_metadata'] = '';
			} else {
				$additionalSettings['settings_add_podcast_metadata'] = $_REQUEST['settings_add_podcast_metadata'];
			}
			if ( !isset($_REQUEST['settings_use_classic_editor']) ) {
				$additionalSettings['settings_use_classic_editor'] = '';
			} else {
				$additionalSettings['settings_use_classic_editor'] = $_REQUEST['settings_use_classic_editor'];
			}
			$additionalSettings_clean = $sanitize->additional_settings($additionalSettings);
			if ( empty($additionalSettings_clean) ) { //malformed data
				$error =  true; $msg = __('Something wrong with additional input settings, please try again.', $libsyn_text_dom);
				if ( $plugin->hasLogger ) $plugin->logger->error("Settings:\tSomething went wrong with additional settings input.");
			} elseif ( is_array($additionalSettings_clean) ) { //looks good update options
				if ( $plugin->hasLogger ) $plugin->logger->info("Settings:\tUpdating Additional Settings");
				foreach ($additionalSettings_clean as $key => $val) {
					if ( $plugin->hasLogger ) $plugin->logger->info("Settings:\tlibsyn-podcasting-".$key.":\t".$val);
					try {
						//Note: These are using the (site) global update_option since metadata settings are here and handle usage without getting a user id
						update_option('libsyn-podcasting-'.$key, $val, true);
					} catch (Exception $e) {
						if ( $plugin->hasLogger ) $plugin->logger->error("Settings:\tProblem updating:".$key.":\t".$val."\tError:\t".$e);
					}
					
				}
			}
			
		} elseif ((!empty($_REQUEST['submit']) && $_REQUEST['submit']==='Save Changes') || ($_REQUEST['libsyn_settings_submit']==='Save Changes')) { //has config changes or update
			if(!is_null($api->getClientId())) { //check for cleared data
				if (!empty($_REQUEST['showSelect'])) {
					$show = $plugin->getShow($api, $_REQUEST['showSelect'])->{'user-shows'};
					if(!empty($show)) {//matched show
						if(!empty($show->{'feed_url'})) {
							$api->setFeedUrl($show->{'feed_url'});
						}
						if(!empty($show->{'show_title'})) {
							$api->setShowTitle($show->{'show_title'});
						}
						$api->setShowId($_REQUEST['showSelect']);
						$api->save();
					} else {//throw error
						if($plugin->hasLogger) $plugin->logger->error("Settings:\tProblem updating: showSelect:\t");
						$msg = "Could not save show selection.";
						$error = true;
					}
				}
				if(!empty($_REQUEST['clientSecret']) && $api->getClientSecret() !== $sanitize->clientSecret($_REQUEST['clientSecret'])) $api->setClientSecret($sanitize->clientSecret($_REQUEST['clientSecret']));
				if(!empty($_REQUEST['clientId']) && $api->getClientId() !== $sanitize->clientId($_REQUEST['clientId'])) $api->setClientId($sanitize->clientId($_REQUEST['clientId']));
				$update = $plugin->updateSettings($api);
				if($update!==false) $msg = __('Settings Updated', $libsyn_text_dom);
			} else { //doesn't have client id data saved (must be cleared data update)
				if(!empty($_REQUEST['clientId']) && !empty($_REQUEST['clientSecret'])) { 
					update_user_option($current_user_id, 'libsyn-podcasting-client', array('id' => $sanitize->clientId($_REQUEST['clientId']), 'secret' => $sanitize->clientSecret($_REQUEST['clientSecret'])), false); 
					$clientId = $_REQUEST['clientId']; 
				}
			}
		} elseif(!empty($_REQUEST['submit']) && ($_REQUEST['submit']==='Clear Settings' || $_REQUEST['submit']==='Clear+Settings')) {
			//sanitize clear_settings
			if(!empty($_REQUEST['clear-settings-data'])) {
				$check = $sanitize->clear_settings($_REQUEST['clear-settings-data']);
				if($check === true) {
					$ultilities = new Libsyn\Utilities();
					$remove_settings = $ultilities::uninstallSettings(); //clear settings
					$error_message = "";
					if($remove_settings) {
						$msg = __("Settings Cleared", $libsyn_text_dom);
					} else {
						$msg = __("Something went wrong when trying to clear settings", $libsyn_text_dom);
						$error_message = "&error=true";
					}
					echo $plugin->redirectUrlScript($plugin->admin_url('admin.php').'?page=LibsynSettings&msg='.$msg.$error_message); 
				} else {
					$msg = __("There was a problem when trying to clear settings", $libsyn_text_dom);
					if($plugin->hasLogger) $plugin->logger->error("Settings:\t".$msg);
					$error = true;
				}
			}
		}
	} else { // for brand new setup just store in session through redirects.
		if(!empty($_REQUEST['submit']) && ($_REQUEST['submit']==='Clear Settings' || $_REQUEST['submit']==='Clear+Settings')) {
			//sanitize clear_settings
			if(!empty($_REQUEST['clear-settings-data'])) {
				$check = $sanitize->clear_settings($_REQUEST['clear-settings-data']);
				if($check === true) {
					$ultilities = new Libsyn\Utilities();
					$remove_settings = $ultilities::uninstallSettings(); //clear settings
					$error_message = "";
					if($remove_settings) {
						$msg = __("Settings Cleared", $libsyn_text_dom);
					} else {
						$msg = __("Something went wrong when trying to clear settings", $libsyn_text_dom);
						$error_message = "&error=true";
					}
					echo $plugin->redirectUrlScript($plugin->admin_url('admin.php').'?page=LibsynSettings&msg='.$msg.$error_message); 
				} else {
					$msg = __("There was a problem when trying to clear settings", $libsyn_text_dom);
					if($plugin->hasLogger) $plugin->logger->error("Settings:\t".$msg);
					$error = true;
				}
			}
		} elseif(!empty($_REQUEST['clientId']) && !empty($_REQUEST['clientSecret'])) {
			update_user_option($current_user_id, 'libsyn-podcasting-client', array('id' => $sanitize->clientId($_REQUEST['clientId']), 'secret' => $sanitize->clientSecret($_REQUEST['clientSecret'])), false);
			$clientId = $_REQUEST['clientId']; 
		}
	}
}

/* Handle API Creation/Update*/
if((!$api) || ($api instanceof \Libsyn\Api && $api->isRefreshExpired())) { //does not have $api setup yet in WP
	$render = false;
	/* Handle login and auth. */
	if(!$authorized) {
		if(isset($libsyn_code)) { //handle auth callback $_REQUEST['libsyn_code']
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\tCode Set");
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\tcode:\t".$libsyn_code);
			// (THIS FIRES WHEN YOU APPROVE API)
			$url = $redirectUri."&libsyn_code=".$libsyn_code."&libsyn_authorized=true";
			$client = get_user_option('libsyn-podcasting-client');
			if (isset($client['id'])) {
				$url .= "&clientId=".$sanitize->clientId($sanitize->clientId($client['id']));
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\tClient Set");
				$isSet = (!empty($client['id']))?'true':'false';
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\tclientId:\t".$isSet);
			}
			if(isset($client['secret'])) {
				$url .= "&clientSecret=".$sanitize->clientSecret($client['secret']);
				$isSet = (!empty($client['secret']))?'true':'false';
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\tclientId:\t".$isSet);
			}
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\tRedirecting to:\t".$url);
			echo $plugin->redirectUrlScript($url);
		} elseif(isset($clientId)) { //doesn't have api yet
			if(empty($clientId)) //try to grab client if for some reason it is empty at this point.
				$clientId = (!empty($_REQUEST['clientId']))?$_REQUEST['clientId']:$clientId;
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\toauthAuthorize");
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\tclientId:\t".$redirectUri);
			$html = $plugin->oauthAuthorize($clientId, $redirectUri);
			if(!empty($html)) {
				//do nothing
			} else {
				$html = '
					<h3 style="color:red;">' . __('Something went wrong with your authentication process.', $libsyn_text_dom) . '</h3>
					<p>' . __('Your client information may not be valid or something is wrong with the Libsyn authentication servers.', $libsyn_text_dom) . '</p>
					<p>' . __('It is recommended that you clear your settings and enter new client id and secret.', $libsyn_text_dom) . '</p>
					<div style="height: 124px;">
						<div style="float: left; width: 60%;">
							<form name="' . LIBSYN_NS . 'form' . '" id="' . LIBSYN_NS . 'form' . '" method="post" action="">
							<div>
								' . __('Clear Settings button below to continue.', $libsyn_text_dom) . '
								<div style="margin-top:1rem;">
									<input type="hidden" name="clear-settings-data" value="' . time() . '" />
									<input type="submit" value="Clear Settings" class="button button-primary libsyn-dashicons-trash" id="clear-settings-button" name="submit" />
								</div>
							</div>
							</form>
					</div>
				';
				$render = true;
				$render_scripts_only = true;
			}
			echo '<div id="oauth-dialog">'.$html.'</div>';
		} elseif ($api instanceof \Libsyn\Api) { //either update or cleared data
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\t api instanceof Libsyn Api");
			if(!isset($clientId)||is_null($clientId)) $clientId = $api->getClientId();
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\tclientId:\t".$redirectUri);
			if(is_null($clientId)) {
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\t is_null clientId");
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\toauthAuthorize");
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\tclientId:\t".$redirectUri);
				$html = $plugin->oauthAuthorize($clientId, $redirectUri);//has api (update)
				$setup_new = true;
				$api = false;
			} else {
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\t !is_null clientId");
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\toauthAuthorize");
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\tclientId:\t".$redirectUri);
				$html = $plugin->oauthAuthorize($clientId, $redirectUri);//has api (update)
				echo '<div id="oauth-dialog">'.$html.'</div>';
			}
		}
	} elseif( $authorized !== false ) { //has auth token
		if($plugin->hasLogger) $plugin->logger->info("Authorization:\t has auth token");
		if ($api instanceof \Libsyn\Api) {
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\t has api instance");
			if(!is_null($api->getClientId())) {
				if(!isset($clientId)) $clientId = $api->getClientId();
				if(!isset($clientSecret)) $clientSecret = $api->getClientSecret();
			} else {
				$client = get_user_option('libsyn-podcasting-client');
				if(!isset($clientId)) $clientId = $sanitize->clientId($client['id']);
				if(!isset($clientSecret)) $clientSecret = $sanitize->clientSecret($client['secret']);		
			}
		} else {
			$client = get_user_option('libsyn-podcasting-client');
			if(!isset($clientId)) $clientId = $sanitize->clientId($client['id']);
			if(!isset($clientSecret)) $clientSecret = $sanitize->clientSecret($client['secret']);
		}
		/* Auth login */
		if(!empty($libsyn_code)) {
			//sanity checks
			if(!empty($clientId)) {
				$clientId = $clientId;
			} elseif($client = get_user_option('libsyn-podcasting-client') && !empty($client['id'])) {
				$clientId = $client['id'];
			} else {
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\t clientId sanity check empty.");
			}
			if(!empty($clientSecret)) {
				$clientSecret = $clientSecret;
			} elseif($client = get_user_option('libsyn-podcasting-client') && !empty($client['secret'])) {
				$clientSecret = $client['secret'];
			} else {
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\t clientSecret sanity check empty.");
			}
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\t requestBearer");
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\tredirectURI:\t".$sanitize->url_raw(urldecode($redirectUri)));
			$bearer = $plugin->requestBearer(
				$sanitize->clientId($clientId),
				$sanitize->clientSecret($clientSecret),
				$sanitize->text($libsyn_code),
				$sanitize->url_raw(urldecode($redirectUri))
			);
		} else {
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\t requestBearer");
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\tcode:\t");
			if($plugin->hasLogger) $plugin->logger->error("Authorization:\t Code Not set. Something may be wrong with request.");
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\tredirectURI:\t".$sanitize->url_raw(urldecode($redirectUri)));			
			$bearer = null;
		}
		$check = (!empty($bearer))?$plugin->checkResponse($bearer):false;
		$response = (!empty($bearer->body)) ? json_decode($bearer->body, true) : false;
		if(!$check) {
			if(is_array($response)) {
				foreach($response as $res) {
					if($plugin->hasLogger) $plugin->logger->error("Authorization:\t " . $res);
				}
				if(!empty($response['type'])) unset($response['type']);
				if(!empty($response['title'])) unset($response['title']);
				if(!empty($response) && is_array($response)) $implodeResponse = implode(" ", $response);
			} else {
				if(!empty($bearer) && is_wp_error($bearer)) { //check if wp error
					$implodeResponse = $bearer->get_error_messages();
					$implodeResponse = (!empty($implodeResponse) && is_array($implodeResponse))?"Failed Authentication\nWordpress Error:\t".implode(" ", $implodeResponse):"Failed Authentication\nWordpress Error:\t".'unknown';
					if($plugin->hasLogger) $plugin->logger->error("Authorization:\t " . $implodeResponse);
				} else {
					$implodeResponse = (!empty($response) && is_array($response)) ? implode(" ", $response) : "Failed authentication.";
					if($plugin->hasLogger) $plugin->logger->error("Authorization:\t " . $implodeResponse);
				}
			}
			$implodeResponse = (!empty($implodeResponse)) ? $implodeResponse : "Failed authentication.";
			if($plugin->hasLogger) $plugin->logger->error("Authorization:\tcode:\t".$sanitize->text($libsyn_code));
			if($plugin->hasLogger) $plugin->logger->error("Authorization:\tredirectURI:\t".$sanitize->url_raw(urldecode($redirectUri)));
			echo "<div class\"updated\"><span style=\"font-weight:bold;\">".$implodeResponse."</span>"; 
		} elseif($check) {
			$response = $response + array(
				'client_id' => $sanitize->clientId($clientId),
				'client_secret' => $sanitize->clientSecret($clientSecret),
			);
			if($plugin->hasLogger) $plugin->logger->info("Authorization:\t Redirecting Success");
			if($api instanceof \Libsyn\Api && $api->isRefreshExpired() && !is_null($api->getClientId())) {
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\t Has API and refesh is expired");
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\t Updating API");
				$api = $api->update($response);
			} else {
				$url = $plugin->admin_url('admin.php').'?page=LibsynSettings';
				$libsyn_api = $plugin->createApi(array_merge($response, array('user_id' => $current_user_id)));
				if(!empty($libsyn_api) & $libsyn_api instanceof \Libsyn\Api) {
					$libsyn_api->refreshToken();
					$api = $libsyn_api;
				}
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\t Redirect to:\t" . $url);
				echo "<script type=\"text/javascript\">
						(function($){
							$(document).ready(function(){
								if (typeof window.top.location.href == 'string') window.top.location.href = \"".$url."\";
									else if(typeof document.location.href == 'string') document.location.href = \"".$url."\";
										else if(typeof window.location.href == 'string') window.location.href = \"".$url."\";
											else alert('Unknown Libsyn Plugin error 1022.  Please report this error to support@libsyn.com and help us improve this plugin!');
							});
						})(jQuery);
					 </script>";
				//Redirect wp
				if($plugin->hasLogger) $plugin->logger->info("Authorization:\t Calling wp_safe_redirect:\t" . $url);
				echo "<div class\"updated\"><span style=\"font-weight:bold;\">" . __("Plugin Authentication Successful!", $libsyn_text_dom) . "</div>";
				exit;
			}
			if(empty($api)) { //api false
				echo "<div class\"updated\"><span style=\"font-weight:bold;\">" . __("Problem with the API connection, please check settings or try again.", $libsyn_text_dom) . "<span></div>";
			}
		}
	}
}

/* Form Stuff */
if(!empty($api) && $api instanceof Libsyn\Api) {
	$api_showId = $api->getShowId();
	if($api_showId === null || $api_showId === '') {
		$msg = __("You must select a show to publish to.", $libsyn_text_dom);
		$requireShowSelect = true;
		$error = true;	
	}
} elseif(empty($api) && empty($clientId) && (is_null($api) || $api === false)) { $render = true; }


/* Set Notifications */
global $libsyn_notifications;
do_action('libsyn_notifications');
?>

<?php wp_enqueue_script( 'jquery' ); ?>
<?php wp_enqueue_script( 'jquery-ui-core', array('jquery')); ?>
<?php wp_enqueue_script( 'jquery-ui-tabs', array('jquery')); ?>
<?php wp_enqueue_script( 'jquery-ui-dialog', array('jquery')); ?>
<?php wp_enqueue_style( 'wp-jquery-ui-dialog'); ?>
<?php wp_enqueue_script('jquery_validate', plugins_url(LIBSYN_DIR . '/lib/js/jquery.validate.min.js'), array('jquery')); ?>
<?php wp_enqueue_script('libsyn-meta-form', plugins_url(LIBSYN_DIR . '/lib/js/libsyn/meta_form.js'), array('jquery')); ?>
<?php wp_enqueue_script( 'iris', array('jquery')); ?>
<?php wp_enqueue_style( 'iris' ); ?>
<?php IF($render): ?>
<?php wp_enqueue_style( 'libsyn-meta-boxes', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_boxes.css' )); ?>
<?php wp_enqueue_style( 'libsyn-meta-form', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_form.css' )); ?>
<?php wp_enqueue_style( 'libsyn-dashicons', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/dashicons.css' )); ?>
<?php wp_enqueue_style( 'animate', plugins_url(LIBSYN_DIR . '/lib/css/animate.min.css')); ?>
<?php wp_enqueue_script( 'jquery-easing', plugins_url(LIBSYN_DIR . '/lib/js/jquery.easing.min.js')); ?>
	<script>window.jQuery || document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js">\x3C/script>')</script>
	<style media="screen" type="text/css">
	.code { font-family:'Courier New', Courier, monospace; }
	.code-bold {
		font-family:'Courier New', Courier, monospace; 
		font-weight: bold;
	}
	</style>
	<?php if(empty($render_scripts_only)) {?>
	<div class="wrap">
	  <?php if (isset($msg)) echo $plugin->createNotification($msg, $error); ?>
	  <h2><?php _e("Publisher Hub - Settings", $libsyn_text_dom); ?><span style="float:right;"><a href="http://www.libsyn.com/"><img src="<?php _e(plugins_url( LIBSYN_DIR . '/lib/images/libsyn_dark-small.png'), $libsyn_text_dom); ?>" title="Libsyn Podcasting" height="28px"></a></span></h2>
	  <form name="<?php echo LIBSYN_NS . "form" ?>" id="<?php echo LIBSYN_NS . "form" ?>" method="post" action="">
		 <div id="poststuff">
		  <div id="post-body">
			<div id="post-body-content">
			<?php if(isset($api) && ($api !== false)) {
				$shows = $plugin->getShows($api);
				if(!empty($shows->{'user-shows'})) {
					$shows = $shows->{'user-shows'};
				} else {
					$shows = array(); //empty shows list
				}
			?>

			<!-- BOS Existing API -->
			  <div class="stuffbox" style="width:93.5%">
				<div class="inside hndle">
				<h3><label><?php _e("Modify Api", $libsyn_text_dom); ?></label></h3>
				<div class="inside" style="margin: 15px;">
				  <p><em><?php echo __("Libsyn account application settings can be setup and viewed by logging into your ", $libsyn_text_dom) . "<a href=\"https://www.libsyn.com/\" target=\"_blank\">" . __("Libsyn Account", $libsyn_text_dom) . ".</a>" . __(" then click the Arrow in the top right and clicking \"Manage Wordpress Plugins\"", $libsyn_text_dom); ?></em></p>
				  <table class="form-table">
					<tr valign="top">
					  <th><?php _e("Client ID:", $libsyn_text_dom); ?></th>
					  <td>
						<input id="clientId" type="text" value="<?php echo $api->getClientId(); ?>" name="clientId" maxlength="12" pattern="[a-zA-Z0-9]{12}" <?=(!is_null($api->getClientId()))?'readonly="readonly" ':'';?>required /> 
					  </td>
					</tr>
					<tr valign="top">
					  <th><?php _e("Client Secret:", $libsyn_text_dom); ?></th>
					  <td>
						<input id="clientSecret" value="<?php echo $api->getClientSecret(); ?>" type="password" name="clientSecret" maxlength="20" pattern="[a-zA-Z0-9]{20}" <?=(!is_null($api->getClientSecret()))?'readonly="readonly" ':'';?>required />
						<input type="hidden" name="handleApi" id="handleApi" />
					  </td>
					</tr>
					<tr valign="top">
					  <th><?php _e("Select Show:", $libsyn_text_dom); ?></th>
					  <td>
						<select name="showSelect" autofocus required>
							<?php 
								if( isset($requireShowSelect) && ($requireShowSelect) ) echo  "<option value=\"\">" . __("None", $libsyn_text_dom) . "</option>";
								foreach($shows as $show) {
									if($api->getShowId()==$show->{'show_id'}||count($shows)===1)
										echo  "<option value=\"" . $sanitize->showId($show->{'show_id'}) . "\" selected>" . $show->{'show_title'} . " (" . $show->{'show_slug'} . ")</option>";
									else
										echo  "<option value=\"" . $sanitize->showId($show->{'show_id'}) . "\">".$show->{'show_title'} . " (" . $show->{'show_slug'} . ")</option>";
								}
							?>
						</select>
					  </td>
					</tr>
					<?php if(is_int($api->getShowId())) { ?>
					<tr valign="top">
						<th></th>
						<td>
							<div class="inside" style="margin: 15px;"><?php _e("Libsyn is connected to your Wordpress account successfully.", $libsyn_text_dom); ?></div>
						</td>
					</tr>					
					<?php } ?>
					<tr valign="top">
					  <th></th>
					  <td>
						<?php submit_button(__('Save Changes', $libsyn_text_dom), 'primary', 'libsyn_settings_submit', true, array('id' => 'submit_save', 'onClick' => "document.getElementById('submit_save').value='Save Changes';")); ?>
					  </td>
					</tr>
				  </table>
				</div>
				</div>
			  </div>
			  <!-- EOS Existing API -->
			<?php } else { //new?>
			<?php if(!isset($setup_new)) $setup_new = true; ?>
			<!-- BOS Add new API -->
			  <div class="stuffbox">
				<h3 class="hndle"><span><?php _e("Add New Api", $libsyn_text_dom); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <p><em><?php echo __("Enter settings provided by logging into your ", $libsyn_text_dom) . "<a href=\"https://youtu.be/mMPSkd7YlRc\" target=\"_blank\"><strong><em>" . __("Libsyn account", $libsyn_text_dom) . "</em></strong></a>". __(" then selecting the arrow in the top right and clicking \"Manage Wordpress Plugins\"", $libsyn_text_dom); ?></em></p>
				  <table class="form-table">
					<tr valign="top">
					  <th><?php _e("Client ID:", $libsyn_text_dom); ?></th>
					  <td>
						<input id="clientId" type="text" value="" name="clientId" pattern="[a-zA-Z0-9]{12}" required/> 
					  </td>
					</tr>
					<tr valign="top">
					  <th><?php _e("Client Secret:", $libsyn_text_dom); ?></th>
					  <td>
						<input id="clientSecret" type="text" value="" name="clientSecret" pattern="[a-zA-Z0-9]{20}" required/> 
					  </td>
					</tr>
					<tr valign="top">
					  <th></th>
					  <td>
						<?php submit_button(__('Authorize Plugin', $libsyn_text_dom), 'primary', 'button', true, array('id' => 'submit_authorization', 'onClick' => "document.getElementById('submit_save').value='Authorize Plugin';")); ?>
					  </td>
					</tr>
				  </table>
				</div>
				<?php //<div id="oauth-dialog"><iframe id="oauthBox" src="" scrolling="no" style="height:498px;display:none;"></iframe></div> ?>
				<div id="oauth-dialog"><div id="oauthBox" style="height:498px;display:none;"></div></div>
				<script type="text/javascript">
					(function($){
						$(document).ready(function(){
							//check ajax
							var check_ajax_url = "<?php echo $sanitize->text($plugin->admin_url() . '?action=libsyn_check_url&libsyn_check_url=1'); ?>";
							var ajax_error_message = "<?php _e('Something went wrong when trying to load your site base url.  Please make sure your Site Address (URL) in Wordpress settings is correct.', $libsyn_text_dom); ?>";		
							$.getJSON(check_ajax_url).done(function(json) {
								if(json){
									//success do nothing
								} else {
									//redirect to error out
									var ajax_error_url = "<?php echo $plugin->admin_url('admin.php').'?page=LibsynSettings&error=true&msg='; ?>" + ajax_error_message;
									if (typeof window.top.location.href == "string") window.top.location.href = ajax_error_url;
											else if(typeof document.location.href == "string") document.location.href = ajax_error_url;
												else if(typeof window.location.href == "string") window.location.href = ajax_error_url;
													else alert(" <?php _e("Unknown Libsyn Plugin error 1028.  Please report this error to support@libsyn.com and help us improve this plugin!", $libsyn_text_dom); ?>");
								}
							}).fail(function(jqxhr, textStatus, error) {
									//redirect to error out
									var ajax_error_url = "<?php echo $plugin->admin_url('admin.php').'?page=LibsynSettings&error=true&msg='; ?>" + ajax_error_message;
									if (typeof window.top.location.href == "string") window.top.location.href = ajax_error_url;
											else if(typeof document.location.href == "string") document.location.href = ajax_error_url;
												else if(typeof window.location.href == "string") window.location.href = ajax_error_url;
													else alert("<?php _e("Unknown Libsyn Plugin error 1029.  Please report this error to support@libsyn.com and help us improve this plugin!", $libsyn_text_dom); ?>");
							});
						});
					})(jQuery);
				</script>
			  </div>
			  <!-- EOS Add new API -->
			<?php } ?>
			<?php if(!isset($setup_new)) { ?>
			  <!-- BOS Bottom L/R Boxes -->
			  <div class="box_left_column" id="libsyn_tabbed_settings_box">
				<div class="stuffbox" id="libsyn_player_settings">
				  <div class="inside box_left_content" style="display:none;">
					<h3>
						<label><a href="#libsyn_player_settings" style="color:black;text-decoration:none;" class="libsyn_player_settings_anchor libsyn-text-underline"><?php _e("Player Settings", $libsyn_text_dom); ?></a>&nbsp;&nbsp;&nbsp;<a href="#libsyn_additional_settings" style="color:darkgray;text-decoration:none;" class="libsyn_additional_settings_anchor"><?php _e("Additional Settings", $libsyn_text_dom); ?></a></label>
					</h3>
					<div class="inside">
						<p id="player-description-text"><em><?php _e("Below are the default player settings.  You may also modify the size on each individual post on the post page.", $libsyn_text_dom); ?></em></p>
						<div class="box_clear"></div>
						<table class="form-table">
							<tr valign="top">
								<th>Player Theme</th>
								<td>
									<div>
										<div>
											<input id="player_use_theme_standard" type="radio" value="standard" name="player_use_theme" /><span style="margin-left:16px;"><strong><?php _e("Standard", $libsyn_text_dom); ?></strong>&nbsp;&nbsp;<em style="font-weight:300;"><?php echo '(' . __("minimum height", $libsyn_text_dom) . ' 45px)'; ?></em></span>
											<div style="margin-left:36px;" id="player_use_theme_standard_image"></div>
										</div>
										<br />
										<div>
											<input id="player_use_theme_mini" type="radio" value="mini" name="player_use_theme" /><span style="margin-left:16px;"><strong>Mini</strong>&nbsp;&nbsp;<em style="font-weight:300;"><?php echo '(' . __("minimum height", $libsyn_text_dom) . ' 26px)'; ?></em></span>
											<div style="margin-left:36px;" id="player_use_theme_mini_image"></div>
										</div>
										<br />
										<div>
											<input id="player_use_theme_custom" type="radio" value="custom" name="player_use_theme" /><span style="margin-left:16px;"><strong><?php _e('Custom', $libsyn_text_dom); ?></strong>&nbsp;&nbsp;<em style="font-weight:300;"><?php echo __('(minimum height 90px or 300px(video), width 100% fixed)', $libsyn_text_dom); ?></em></span>
											<div style="margin-left:36px;" id="player_use_theme_custom_image"></div>
										</div>
									</div>
								</td>
							</tr>
							<tr id="player_custom_color_picker" style="display:none;">
								<th><?php _e('Custom Color', $libsyn_text_dom); ?></th>
								<td>
									<div>
										<div style="margin-left:36px;">
											<input id="player_custom_color" class="color-picker" name="player_custom_color" value=""/><button type="button" id="player_custom_color_picker_button" class="button libsyn-dashicons-art"><?php _e('Pick Color', $libsyn_text_dom); ?></button>
										</div>
										<div id="player_custom_color_picker_container" style="padding: 0px 0px 0px 0px; width:100%; margin-left:40px;"></div>
									</div>
								</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
							</tr>
							<tr valign="top">
								<th colspan="2"><input style="margin-left: 2px;" id="player_use_thumbnail" type="checkbox" value="use_thumbnail" name="player_use_thumbnail" />&nbsp;<?php _e('Display episode/show artwork on the player?', $libsyn_text_dom); ?>&nbsp;&nbsp;<em style="font-weight:300;"><?php echo __('(minimum height 200px)', $libsyn_text_dom); ?></em></th>
								<td>
								</td>
							</tr>
							<tr id="player_width_tr" valign="top" style="display:none;">
								<th><?php _e("Player Width:", $libsyn_text_dom); ?></th>
								<td>
									<input id="player_width" type="number" value="" name="player_width" maxlength="4" autocomplete="on" min="200" step="1" style="display:none;" />
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e("Player Height:", $libsyn_text_dom); ?></th>
								<td>
									<input id="player_height" type="number" value="" name="player_height" autocomplete="on" min="45" step="1" />
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e("Player Placement:", $libsyn_text_dom); ?></th>
								<td>
									<div>
										<div>
											<input id="player_placement_top" type="radio" value="top" name="player_placement" /><span style="margin-left:16px;"><strong><?php _e("Top", $libsyn_text_dom); ?></strong>&nbsp;&nbsp;<em style="font-weight:300;"><?php echo __('(Before Post)', $libsyn_text_dom); ?></em></span>
										</div>
										<div style="margin-left:36px;" class="post-position-image-box">
											<div class="post-position-shape-top"></div>
										</div>
										<br />
										<div>
											<input id="player_placement_bottom" type="radio" value="bottom" name="player_placement" /><span style="margin-left:16px;"><strong><?php _e("Bottom", $libsyn_text_dom); ?></strong>&nbsp;&nbsp;<em style="font-weight:300;"><?php echo __('(After Post)'); ?></em></span>
										</div>
										<div style="margin-left:36px;" class="post-position-image-box">
											<div class="post-position-shape-bottom"></div>
										</div>
									</div>
								</td>
							</tr>
							<tr valign="top">
								<th colspan="2"><input style="margin-left: 2px;" id="player_use_download_link" type="checkbox" value="use_download_link" name="player_use_download_link" />&nbsp;<?php _e("Display download link below the player?", $libsyn_text_dom); ?></th>
								<td>
								</td>
							</tr>
							<tr valign="top" style="display:none;" id="player_use_download_link_text_div">
								<th></th>
								<td>
									<?php _e("Download Link Text:", $libsyn_text_dom); ?>&nbsp;&nbsp;<input id="player_use_download_link_text" type="text" value="" name="player_use_download_link_text" maxlength="256" min="200"  />
								</td>
							</tr>
							<tr valign="bottom">
								<th></th>
								<td>
									<br />
										<input type="submit" value="Save Player Settings" class="button button-primary libsyn-dashicons-check" id="player-settings-submit" name="submit">
								</td>
							</tr>
							<tr valign="bottom">
								<th style="font-size:.8em;font-weight:200;">**<em><?php _e("height and width in Pixels (px)", $libsyn_text_dom); ?></em></th>
								<td></td>
							</tr>
						</table>
						<br />
					</div>			  
				  </div>
				</div>
				<div class="stuffbox" id="libsyn_additional_settings" style="display:none;">
				  <div class="inside box_left_content" style="display:none;">
					<h3>
						<label><a href="#libsyn_player_settings" style="color:darkgray;text-decoration:none;" class="libsyn_player_settings_anchor"><?php _e("Player Settings", $libsyn_text_dom); ?></a>&nbsp;&nbsp;&nbsp;<a href="#libsyn_additional_settings" style="color:black;text-decoration:none;" class="libsyn_additional_settings_anchor libsyn-text-underline"><?php _e("Additional Settings", $libsyn_text_dom); ?></a></label>
					</h3>
					<div class="inside">
						<p id="player-description-text"><em><?php _e("Extra settings that are optional in most podcast configurations.", $libsyn_text_dom); ?></em></p>
						<div class="box_clear"></div>
						<table class="form-table">
							<tr valign="top">
								<th>
								<?php _e("Pages and Posts", $libsyn_text_dom); ?>
								</th>
								<td colspan="2">
									<div class="libsyn-help-tip-container" style="display:inline-grid;">
										<input style="margin-left: 2px;grid-column-start: 1;margin-top: 2px;" id="settings_add_podcast_metadata" type="checkbox" value="add_podcast_metadata" name="settings_add_podcast_metadata" />&nbsp;<?php _e("Add Podcast Feed Headers?", $libsyn_text_dom); ?>&nbsp;
										<div style="position:relative;grid-column-start:3;margin-top:-6px;">
											<div class="libsyn-help-tip">
												<p><?php _e("This will add your Podcast information to the HTML Metadata on Pages/Posts", $libsyn_text_dom); ?></p>
											</div>
										</div>
									</div>
								</td>
							</tr>
							<tr valign="top">
								<th>
								<?php _e("Post Editor Type", $libsyn_text_dom); ?>
								</th>
								<td colspan="2">
									<div class="libsyn-help-tip-container" style="display:inline-grid;">
										<input style="margin-left: 2px;grid-column-start: 1;margin-top: 2px;" id="settings_use_classic_editor" type="checkbox" value="use_classic_editor" name="settings_use_classic_editor" />&nbsp;<?php _e("Use Classic Editor?", $libsyn_text_dom); ?>&nbsp;
										<div style="position:relative;grid-column-start:3;margin-top:-6px;">
											<div class="libsyn-help-tip">
												<p><?php _e("Checking this box will disable the Block Type Editor and enable the useage of the Classic Libsyn Post Editor.", $libsyn_text_dom); ?></p>
											</div>
										</div>
									</div>
								</td>
							</tr>
							<tr valign="bottom">
								<th></th>
								<td>
									<br />
									<br />
									<br />
									<br />
									<br />
										<input type="submit" value="Save Settings" class="button button-primary libsyn-dashicons-check" id="additional-settings-submit" name="submit" />
								</td>
							</tr>
							<tr valign="bottom">
								<th style="font-size:.8em;font-weight:200;"></th>
								<td></td>
							</tr>
						</table>
					</div>			  
				  </div>
				</div>
			  </div>
			  <div class="box_right_column">
				  <div class="stuffbox">
					<div class="inside box_right_content_1">
						<h3>
							<label>About</label>
							<label style="float:right" id="version"></label>
						</h3>
						<div class="inside">
							<p><?php _e("Libsyn is dedicated to providing the most reliable podcast hosting and support.  Our plugin provides the tools to post new episodes and content directly through Wordpress!  Please ", $libsyn_text_dom); ?><a href="mailto:support@libsyn.com"><?php _e("email support", $libsyn_text_dom); ?></a><?php _e(" and let us know what you think about this plugin.", $libsyn_text_dom); ?></p>
						</div>
					</div>
				  </div>
				  <div class="stuffbox">
					<div class="inside box_right_content_2" style="display:none;">
					<h3>
						<label>Support</label>
					</h3>
					<div class="inside">
						<div class="box_clear"></div>
						<div style="height:24px;">
							<div style="float: left;margin-right: 36px;width:25%;"><strong><?php _e("Rate 5-star", $libsyn_text_dom); ?></strong></div>
							<div style="float: left; width: 60%;">
								<a target="_blank" href="//wordpress.org/support/view/plugin-reviews/libsyn-podcasting" style="text-decoration: none">
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								</a>
							</div>
						</div>
						<div class="box_clear"></div>
						<div style="height: 24px;">
							<div style="float: left;margin-right: 36px;width:25%;"><strong><?php _e("Facebook Page", $libsyn_text_dom); ?></strong></div>
							<div style="float: left; width: 60%;">
								<div id="fb-root"></div>
								<script>
									(function(d, s, id) {
									  var js, fjs = d.getElementsByTagName(s)[0];
									  if (d.getElementById(id)) return;
									  js = d.createElement(s); js.id = id;
									  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.4";
									  fjs.parentNode.insertBefore(js, fjs);
									}(document, 'script', 'facebook-jssdk'));
								</script>
								<div class="fb-like" data-href="https://www.facebook.com/libsyn" data-layout="button" data-action="like" data-show-faces="false" data-share="true"></div>
							</div>
						</div>
						<div class="box_clear"></div>
						<div style="height: 24px;">
							<div style="float: left;margin-right: 36px;width:25%;"><strong><?php _e("Follow on Twitter", $libsyn_text_dom); ?></strong></div>
							<div style="float: left; width: 60%;">
								<a href="https://twitter.com/libsyn" class="twitter-follow-button" data-show-count="false"><?php _e("Follow", $libsyn_text_dom); ?>&nbsp;@libsyn</a>
								<script>
									!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');
								</script>
							</div>
						</div>
						<div class="box_clear"></div>
					</div>
					<br>
					</div>
				  </div>
				  <div class="stuffbox">
					<div class="inside box_right_content_3" style="display:none">
						<h3>
							<label><?php _e("Clear Settings / Debug", $libsyn_text_dom); ?></label>
						</h3>
						<div class="inside">	
							<div class="box_clear"></div>
							<div style="height: 74px;" id="libsyn-download-log-containter">
								<div style="float: left;margin-right: 36px;width:25%;"><strong><?php _e("Download Plugin Log", $libsyn_text_dom); ?></strong></div>
								<div style="float: left; width: 60%;">
									<div class="libsyn-help-tip-container" style="display:-webkit-box;">
										<div style="margin-top:2px;margin-right:4px;">
											<button type="button" id="libsyn-download-log-button" class="button libsyn-dashicions-download"><?php _e("Download Log", $libsyn_text_dom); ?></button>
										</div>
										<div class="libsyn-help-tip">
											<p><?php _e("If you are having trouble with the plugin please download the log file to submit to our developers.", $libsyn_text_dom); ?></p>
										</div>
									</div>
								</div>
							</div>
							<div class="box_clear"></div>
							<div style="height: 74px;">
								<div style="float: left;margin-right: 36px;width:25%;"><strong>Reset Settings</strong></div>
								<div style="float: left; width: 60%;">
									<div class="libsyn-help-tip-container" style="display:-webkit-box;">
										<div style="margin-top:2px;margin-right:4px;">
											<button type="button" id="clear-settings-button" value="Clear Settings" class="button libsyn-dashicions-trash"><?php _e("Clear Settings", $libsyn_text_dom); ?></button>
										</div>
										<div class="libsyn-help-tip">
											<p><?php _e("This will clear all the current plugin settings.", $libsyn_text_dom); ?></p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				  </div>
			  </div>
			  <!-- Dialogs -->
			  <div id="accept-dialog" class="hidden" title="Confirm Integration">
				<p><span style="color:red;font-weight:600;"><?php _e("Warning!", $libsyn_text_dom); ?></span>&nbsp;<?php _e("By accepting you will modifying your Libsyn Account & Wordpress Posts for usage with the Podcast Plugin.  We suggest backing up your Wordpress database before proceeding.", $libsyn_text_dom); ?></p>
				<br>
			  </div>
			  <div id="clear-settings-dialog" class="hidden" title="Confirm Clear Settings">
				<p><span style="color:red;font-weight:600;"><?php _e("Warning!", $libsyn_text_dom); ?></span>&nbsp;<?php _e("By accepting you will be removing all your Libsyn Publisher Hub settings.  Click yes to continue.", $libsyn_text_dom); ?></p>
				<br>
			  </div>

			  <!-- EOS Bottom L/R Boxes -->
			<?php } ?>
			</div>
		  </div>
		</div>
	  </form>
	</div>
	<?php } ?>
	<?php 
	// TODO: Test and remove prior to release
	// $feed_redirect_url = (isset($api)&&$api!==false)?$api->getFeedRedirectUrl():''; 
	?>	
	
	<?php //PP check goes here ?>
	
	<?php IF(!ISSET($setup_new) || !EMPTY($render_scripts_only)): ?>
	
	<?php //PP box goes here ?>
	
	<!-- BOS Handle PlayerSettings -->
	<?php 
		//handle adding settings fields for player-setings
		register_setting('general', 'libsyn-podcasting-player_use_thumbnail');
		register_setting('general', 'libsyn-podcasting-player_use_theme');
		register_setting('general', 'libsyn-podcasting-player_height');
		register_setting('general', 'libsyn-podcasting-player_width');
		register_setting('general', 'libsyn-podcasting-player_placement');
		register_setting('general', 'libsyn-podcasting-player_custom_color');
	?>
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				$(".settings-error").click(function() {
					$(this).fadeOut("slow");
				});
				// $("#libsyn_tabbed_settings_box").tabs();
				$(".libsyn_player_settings_anchor").each(function() {
					$(this).click(function(e) {
						e.preventDefault();
						$("#libsyn_player_settings").toggle();
						$("#libsyn_additional_settings").toggle();
					});
				});
				$(".libsyn_additional_settings_anchor").each(function() {
					$(this).click(function(e) {
						e.preventDefault();
						$("#libsyn_additional_settings").toggle();
						$("#libsyn_player_settings").toggle();
					});
				});
				// $(".box_left_content").load("<?php _e(plugins_url( LIBSYN_DIR . '/admin/views/box_playersettings.php'), $libsyn_text_dom); ?>", function() {
				$(".box_left_content").fadeIn('fast', function() {
					
						//add stuff to ajax box
						$("#player_use_theme_standard_image").empty().append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/player-preview-standard.jpg'); ?>" style="max-width:95%;" />');
						$("#player_use_theme_mini_image").empty().append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/player-preview-standard-mini.jpg'); ?>" style="max-width:95%;" />');
						$("#player_use_theme_custom_image").empty().append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/custom-player-preview.jpg'); ?>" style="max-width:95%;" />');
						$(".box_left_column > div").css('overflow-x', 'hidden');
						$(".post-position-shape-top").empty().append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/player_position.png'); ?>" style="vertical-align:top;" />');
						$(".post-position-shape-bottom").empty().append('<img src="<?php echo plugins_url( LIBSYN_DIR . '/lib/images/player_position.png'); ?>" style="vertical-align:top;" />');
					
					//validate button
					$('<a>').text('Validate').attr({
						class: 'button'
					}).click( function() {
						var current_feed_redirect_input = validator_url + encodeURIComponent($("#feed_redirect_input").attr('value'));
						window.open(current_feed_redirect_input);
					}).insertAfter("#feed_redirect_input");
					
					//set default value for player use thumbnail
					<?php $playerUseThumbnail = get_user_option('libsyn-podcasting-player_use_thumbnail'); ?>
					var playerUseThumbnail = '<?php if ( !empty($playerUseThumbnail) ) { echo $playerUseThumbnail; } ?>';
					if(playerUseThumbnail == 'use_thumbnail') {
						$('#player_use_thumbnail').prop('checked', true);
					}
					
					//set default value of player theme
					<?php $playerTheme = get_user_option('libsyn-podcasting-player_use_theme'); ?>
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
							$('#player_width_tr').fadeOut('fast', function() {
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
							$('#player_width_tr').fadeOut('fast', function() {
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
					<?php $playerHeight = get_user_option('libsyn-podcasting-player_height'); ?>
					<?php $playerWidth = get_user_option('libsyn-podcasting-player_width'); ?>
					var playerHeight = parseInt('<?php if ( !empty($playerHeight) ) { echo $playerHeight; }?>');
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
									$('#player_width').hide();
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
								$('#player_width').hide();
							}
							
						}
					});
					
					//player placement checkbox settings
					<?php $playerPlacement = get_user_option('libsyn-podcasting-player_placement'); ?>
					var playerPlacement = '<?php if ( !empty($playerPlacement) ) { echo $playerPlacement; } ?>';
					if(playerPlacement == 'top') {
						$('#player_placement_top').prop('checked', true);
					} else if(playerPlacement == 'bottom') {
						$('#player_placement_bottom').prop('checked', true);
					} else { //player placement is not set
						$('#player_placement_top').prop('checked', true);
					}
					
					<?php $playerUseDownloadLink = get_user_option('libsyn-podcasting-player_use_download_link'); ?>
					var playerUseDownloadLink = '<?php if ( !empty($playerUseDownloadLink) ) { echo $playerUseDownloadLink; } ?>';
					<?php $playerUseDownloadLinkText = get_user_option('libsyn-podcasting-player_use_download_link_text'); ?>
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
					
					<?php $playerCustomColor = get_user_option('libsyn-podcasting-player_custom_color'); ?>
					<?php if(empty($playerCustomColor)) { ?>
					var playerCustomColor = '87a93a';
					<?php } else { ?>
					var playerCustomColor = '<?php if ( !empty($playerCustomColor) ) { echo $playerCustomColor; } ?>';

					<?php } ?>
					libsyn_player_color_picker = $('#player_custom_color').iris({
						palettes: ['#125', '#459', '#78b', '#ab0', '#de3', '#f0f'],
						hide: true,
						border: false,
						target: $('#player_custom_color_picker_container'),
						change: function(event, ui) {
							$('#player_custom_color').css('background-color', ui.color.toString() );
						}
					});
					$('#player_custom_color').attr('value', playerCustomColor);
					$('#player_custom_color').css('background-color', "#" + playerCustomColor);
					
					libsyn_player_color_picker.click(function(e) {
						e.preventDefault();
						if(typeof libsyn_player_color_picker !== 'undefined') {
							libsyn_player_color_picker.iris('show');
							libsyn_player_color_picker.data('isOpen', 'show');
						}
					});
					
					$('#player_custom_color_picker_button').click(function(e) {
						e.preventDefault();
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
						// libsyn_player_color_picker.iris('show');
						// libsyn_player_color_picker.iris('toggle');
					});
					
					//Add Podcast Metadata
					<?php $settingsAddPodcastMetadata = get_option('libsyn-podcasting-settings_add_podcast_metadata'); ?>
					var settingsAddPodcastMetadata = '<?php if ( !empty($settingsAddPodcastMetadata) ) { echo $settingsAddPodcastMetadata; } ?>';
					if(settingsAddPodcastMetadata == 'add_podcast_metadata') {
						$('#settings_add_podcast_metadata').prop('checked', true);
					}
					//Use Classic Editor
					<?php $settingsUseClassicEditor = get_option('libsyn-podcasting-settings_use_classic_editor'); ?>
					var settingsUseClassicEditor = '<?php if ( !empty($settingsUseClassicEditor) ) { echo $settingsUseClassicEditor; } ?>';
					if(settingsUseClassicEditor == 'use_classic_editor') {
						$('#settings_use_classic_editor').prop('checked', true);
					}
				});
			});
		})(jQuery);
	</script>
	<!-- EOS Handle PlayerSettings -->
	<!-- BOS Handle About -->
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				$(".box_right_content_1").fadeIn('fast', function() {
					$("#version").text('Version <?php echo $plugin->getPluginVersion(); ?>');
				});
			});
		})(jQuery);
	</script>
	<!-- EOS Handle About -->
	<!-- BOS Handle Support -->
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				$(".box_right_content_2").fadeIn('fast');
			});
		})(jQuery);
	</script>
	<!-- EOS Handle Support -->
	<!-- BOS Handle Clear-Settings -->
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				var setOverlays = function() {
					//make sure overlays are not over dialogs
					$('.ui-widget-overlay').each(function() {
						$(this).css('z-index', 999);
						$(this).attr('style', 'z-index:999;');
					});
					$('.ui-dialog-title').each(function() {
						$(this).css('z-index', 1002);
					});
					$('.ui-dialog').each(function() {
						$(this).css('z-index', 1002);
					});
					$('.ui-colorpicker').each(function() {
						$(this).css('z-index', 1003);
					});
				}
				$(".box_right_content_3").fadeIn('fast', function() {
					$("#clear-settings-button").click(function() {
						//handle submission & dialog
						$( "#clear-settings-dialog" ).dialog({
							autoOpen: false,
							draggable: false,
							height: 'auto',
							width: 'auto',
							modal: true,
							resizable: false,
							open: function(event,ui){
								setOverlays();
								$('.ui-widget-overlay').bind('click',function(){
									$('#clear-settings-dialog').dialog('close');
								});
							},
							buttons: [
								{
									id: "clear-settings-dialog-button-confirm",
									text: "Clear Settings",
									click: function(){
										$("select[name^='showSelect']").removeAttr('required');
										$('#<?php echo LIBSYN_NS . 'form'; ?>').append('<input type="hidden" name="clear-settings-data" value="<?php echo time(); ?>" />');
										$('#clear-settings-dialog').dialog('close');
										$( "#player-settings-submit" ).trigger( "click" );									
									}
								},
								{
									id: "clear-settings-dialog-button-cancel",
									text: "Cancel",
									click: function(){
										$('#clear-settings-dialog').dialog('close');
									}
								}
							]
						});	
						$("#clear-settings-dialog").dialog( "open" );
					});

					//handle download of log file
					var libsynPluginLoggerFilePath = '<?php echo ($plugin->hasLogger)?plugins_url( LIBSYN_DIR . '/admin/lib/' . $libsyn_text_dom . '.log'):''; ?>';
					var libsynDebuginfo = "<?php echo $sanitize->text($plugin->admin_url() . '?action=libsyn_debuginfo&libsyn_debuginfo=1'); ?>";
					if(libsynPluginLoggerFilePath.length > 0){
						$("#libsyn-download-log-button").click(function(){
							$.ajax({
								url: libsynDebuginfo,
								type: "GET",
								success: function(e){
									var download_log_anchor = document.createElement('a');
									if(typeof download_log_anchor.download === 'string') {
										download_log_anchor.href = libsynPluginLoggerFilePath;
										download_log_anchor.setAttribute('download', '<?php _e($libsyn_text_dom . '.log'); ?>');
										document.body.appendChild(download_log_anchor);
										download_log_anchor.click();
										document.body.removeChild(download_log_anchor);
									} else {
										window.open(libsynPluginLoggerFilePath);
									}
								},
								error: function(error) {
									$("#libsyn-download-log-containter").hide('fast');
									$("#libsyn-no-debug-log-message").fadeIn('fast');
								}
							});
						});
					} else {
						$("#libsyn-download-log-containter").hide('fast');
					}
				});
			});
		})(jQuery);
	</script>
	<!-- EOS Handle Clear-Settings -->
	<?php ELSE: ?>
	<!-- BOS Handle Oauth-Dialog -->
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				$("#submit_authorization").click(function(event) {
					event.preventDefault(); // cancel default behavior
					var libsyn_redirect_uri = "<?php if ( !empty($redirectUri) ) { echo $redirectUri; } ?>";
					var libsyn_active_client_id = $("#clientId").val();
					var getLibsynOauthAuthUrl = function(libsyn_active_client_id, libsyn_redirect_uri){
						var oauth_url =  
							"<?php echo $plugin->getApiBaseUri(); ?>/oauth/authorize?" +
							"client_id=" + libsyn_active_client_id + "&" +
							"redirect_uri=" + encodeURIComponent(libsyn_redirect_uri) + "&" + 
							"response_type=code" + "&" + "state=xyz" + "&" + "libsyn_authorized=true" + "&" + "auth_namespace=true";
						return oauth_url;
					}
					
					//check if input fields are valid
					if($('#clientId').valid() && $('#clientSecret').valid()) {
						if($('#clientId').prop("validity").valid && $('#clientSecret').prop("validity").valid) {
							//run ajax to clear settings meta
							$.ajax({
							  type: "POST",
							  url: "<?php echo $sanitize->text($plugin->admin_url() . '?action=libsyn_update_oauth_settings&libsyn_update_oauth_settings=1&client_id=') ?>" + $("#clientId").val() + "&client_secret=" + $("#clientSecret").val(),
							  data: {clientId:$("#clientId").val(),clientSecret:$("#clientSecret").val()},
							  success: function(data, textStatus, jqXHR) {
									//Looks good run update
									//run ajax to update_user_option
									$.ajax({
									  type: "POST",
									  url: "<?php echo $sanitize->text($plugin->admin_url() . '?action=libsyn_oauth_settings&libsyn_oauth_settings=1') ?>",
									  data: {clientId:$("#clientId").val(),clientSecret:$("#clientSecret").val()},
									  success: function(data, textStatus, jqXHR) {
											//looks good redirect
											if (typeof window.top.location.href == "string") window.top.location.href = getLibsynOauthAuthUrl($("#clientId").val(),libsyn_redirect_uri);
												else if(typeof document.location.href == "string") document.location.href = getLibsynOauthAuthUrl($("#clientId").val(),libsyn_redirect_uri);
													else if(typeof window.location.href == "string") window.location.href = getLibsynOauthAuthUrl($("#clientId").val(),libsyn_redirect_uri);
														else alert("Unknown Libsyn Plugin error 1022.  Please report this error to support@libsyn.com and help us improve this plugin!");
										},
										error: function (jqXHR, textStatus, errorThrown){
											//console.log(errorThrown);
										}
									});
								},
								error: function (jqXHR, textStatus, errorThrown){
									//console.log(errorThrown);
								}
							});
						} else {
							if(!$('#clientId').prop("validity").valid){
								$('#clientId').after('<label id="clientId-error" class="error" for="clientId"><?php _e("Client ID is not valid.", $libsyn_text_dom); ?></label>');
							}
							if(!$('#clientSecret').prop("validity").valid){
								$('#clientSecret').after('<label id="clientSecret-error" class="error" for="clientSecret"><?php _e("Client Secret is not valid.", $libsyn_text_dom); ?></label>');
							}
							
						}
					}
				});
			});
		})(jQuery);
	</script>
	<!-- EOS Handle Oauth-Dialog -->
	<?php ENDIF; ?>
<?php ENDIF; ?>
