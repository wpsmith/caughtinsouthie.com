<?php
$plugin = new Libsyn\Service();
$utilities = new Libsyn\Utilities();
$importer = new Libsyn\Service\Importer();
$sanitize = new Libsyn\Service\Sanitize();
$current_user_id = $plugin->getCurrentUserId();
$api = $plugin->retrieveApiById($current_user_id);
$integration = new Libsyn\Service\Integration();
$render = true;
$error = false;
$libsyn_text_dom = $plugin->getTextDom();
add_action( 'wp_feed_options', 'Libsyn\\Utilities::disableFeedCaching' ); //disable feed caching

//create import emailer
$importerEmailer = (new \Libsyn\Service\Cron\ImporterEmailer())->activate();//TODO: do additional testing to ensure this is correctly invoking the mailer

/* Handle saved api */
if ($api instanceof \Libsyn\Api && !$api->isRefreshExpired()) {
	$refreshApi = $api->refreshToken();
	if($refreshApi) { //successfully refreshed
		$api = $plugin->retrieveApiById($current_user_id);
	} else { //in case of a api call error...
		$handleApi = true;
		$clientId = (!isset($clientId)) ? $api->getClientId() : $clientId;
		$clientSecret = (!isset($clientSecret)) ? $api->getClientSecret() : $clientSecret;
		$api = false;
		if(isset($showSelect)) unset($showSelect);
	}
}

//Handle Checking Feed Status
$hasIncompleteImport = false;
$feedImportTriggered = get_user_option('libsyn-podcasting-feed_import_triggered');
$ppFeedTriggered = get_user_option('libsyn-podcasting-pp_feed_triggered');
$powerpressFeed = get_user_option('libsyn-podcasting-pp_feed');
$powerpressFeedUrl = get_user_option('libsyn-podcasting-pp_feed_url');
$feedImportId = get_user_option('libsyn-podcasting-feed_import_id');
$originFeedUrl = get_user_option('libsyn-podcasting-feed_import_origin_feed');
$libsynFeedUrl = get_user_option('libsyn-podcasting-feed_import_libysn_feed');
$feedImportPosts = get_user_option('libsyn-podcasting-feed_import_posts');
$importedPostIds = get_user_option('libsyn-podcasting-imported_post_ids');
$hasSavedData = (!empty($ppFeedTriggered) || !empty($feedImportId) || !empty($feedImportPosts) || !empty($importedPostIds) || !empty($powerpressFeedUrl));

//Handle Powerpress Integration
$checkPowerpress = $integration->checkPlugin('powerpress');
if($checkPowerpress) {
	if ( ( empty($_POST['libsyn-powerpress-feed-url-submit']) && empty($_POST['libsyn-importer-action']) ) && ( empty($powerpressFeed) || empty($ppFeedTriggered) ) ) {
		global $wp_rewrite;
		if ( !empty($wp_rewrite->{'feed_base'}) ) {
			$feedBase = $wp_rewrite->{'feed_base'};
		} else {//just default to feed if unknown
			$feedBase = 'feed';
		}
		if(in_array('podcast', $wp_rewrite->{'feeds'}) && in_array('feed', $wp_rewrite->{'feeds'})) {
			$ppMainFeedUrl = get_site_url() . "/{$feedBase}/podcast";
		} else {
			$ppMainFeedUrl = get_site_url() . "/{$feedBase}";
		}
		//handle pp category feeds
		$ppSettings = get_option('powerpress_general', false);
		if ( !empty($ppSettings) ) {
			if ( !empty($ppSettings['cat_casting']) ) {//has category casting
				if ( empty($powerpressFeedUrl) ) {
					$required_text = ' required';
				} else {//Has Powerpress Category Feed Url Selected
					$ppFeedUrl = $powerpressFeedUrl;
					$required_text = '';
				}

				if ( !empty($ppSettings['custom_cat_feeds']) && is_array($ppSettings['custom_cat_feeds']) ) {
					if ( count($ppSettings['custom_cat_feeds']) > 1 ) {//Build category selector
						$ppCategorySelector = "<br /><fieldset class=\"" . LIBSYN_NS . "_pp_category_selector\"><legend>Select Powerpress Category Feed</legend>";

						//Statically Make Primary feed first option
						if ( !empty($ppMainFeedUrl) ) {
							$ppCategorySelector .=
							"<div>
								<input type=\"radio\" id=\"pp_cat_key_0\" name=\"pp_category_feed_selector\" value=\"{$ppMainFeedUrl}\" class=\"validate{$required_text}\" />
								<label for=\"pp_cat_key_0\"><strong>Full Feed (All Categories):</strong>\t<a href=\"{$ppMainFeedUrl}\" target=\"_blank\">{$ppMainFeedUrl}</a></label>
							</div>";
						}

						foreach($ppSettings['custom_cat_feeds'] as $key => $working_ppCatKey) {
							$category = $category = get_category_to_edit($working_ppCatKey);
							if ( !empty($category) && $category instanceof WP_Term ) {
								$cat_name = (!empty($category->cat_name)) ? $category->cat_name : '';
								$cat_name = (empty($cat_name) && !empty($category->name)) ? $category->name : '';
								$cat_name = (empty($cat_name) && !empty($category->slug)) ? $category->slug : '';
								if ( !empty($category->slug) ) {
									$working_ppFeedUrl = get_site_url() . "/category/{$category->slug}/{$feedBase}";
									$ppCategorySelector .=
									"<div>
										<input type=\"radio\" id=\"pp_cat_key_{$working_ppCatKey}\" name=\"pp_category_feed_selector\" value=\"{$working_ppFeedUrl}\" class=\"validate{$required_text}\" />
										<label for=\"pp_cat_key_{$working_ppCatKey}\"><strong>{$cat_name}:</strong>\t<a href=\"{$working_ppFeedUrl}\" target=\"_blank\">{$working_ppFeedUrl}</a></label>
									</div>";
								}
							}
							if(isset($category)) unset($category);
							if(isset($cat_name)) unset($cat_name);
							if(isset($working_ppFeedUrl)) unset($working_ppFeedUrl);
						}
						$ppCategorySelector .= "<div id=\"helper-text\" style=\"display:none;color:red;\"></div></fieldset>";
					} else {
						$ppCatKey = reset($ppSettings['custom_cat_feeds']);
						$category = get_category_to_edit($ppCatKey);
						if ( !empty($category) && $category instanceof WP_Term ) {
							if ( !empty($category->slug)  && !isset($ppFeedUrl) ) {
								$ppFeedUrl = get_site_url() . "/category/{$category->slug}/{$feedBase}";
							}
						}
					}
				}
			}
		}

		if( !isset($ppFeedUrl) && !empty($ppMainFeedUrl) ) {
			$ppFeedUrl = $ppMainFeedUrl;
		} else {
			//TODO: Check to make if empty($ppFeedUrl) doesn't cause a error
		}
		if( function_exists('fetch_feed') ) {
			$powerpressFeed = fetch_feed( $ppFeedUrl );
		} else {
			$powerpressFeed = $utilities->libsyn_fetch_feed( $ppFeedUrl );
		}
	}

	if ( !empty($powerpressFeed) ) {
		//Feed Arguments
		$feed_args = array(
			'singular'=> 'libsyn_feed_item' //Singular label
			,'plural' => 'libsyn_feed_items' //plural label, also this well be one of the table css class
			,'ajax'   => false //We won't support Ajax for this table
			,'screen' => get_current_screen()
		);

		if(!is_wp_error($powerpressFeed) && $powerpressFeed instanceof \SimplePie) {
			//setup new array with feed data
			$powerpress_feed_items = array();
			$x=0;
			foreach ($powerpressFeed->get_items() as $feed_item) {
				$working_url = $feed_item->get_permalink();
				if( function_exists('url_to_postid') ) {
					$id = url_to_postid($working_url);
				}

				$powerpress_feed_items[$x] = new \stdClass();
				if( !empty($id) ) {
					$powerpress_feed_items[$x]->id = $id;
				} else {
					$powerpress_feed_items[$x]->id = 'entry_'.$x;
				}
				$powerpress_feed_items[$x]->title = $feed_item->get_title();
				$powerpress_feed_items[$x]->content = $feed_item->get_content();
				$powerpress_feed_items[$x]->description = $feed_item->get_description();
				$powerpress_feed_items[$x]->permalink = "<a href=\"".$feed_item->get_permalink()."\" target=\"_blank\">".$feed_item->get_permalink()."</a>";
				$powerpress_feed_items[$x]->custom_permalink_url = $feed_item->get_permalink();
				$powerpress_feed_items[$x]->guid = $feed_item->get_permalink();
				$powerpress_feed_items[$x]->link = $feed_item->get_permalink();
				$powerpress_feed_items[$x]->release_date = $feed_item->get_date();
				$x++;
				if(isset($id)) unset($id);
			}

			//Save PP Feed to Meta
			if( !empty($ppFeedUrl) ) {
				$working_powerpressFeed = new \stdClass();
				$working_powerpressFeed->{'feed_url'} = $ppFeedUrl;
				$working_powerpressFeed->{'items'} = $powerpress_feed_items;
				update_user_option($current_user_id, 'libsyn-podcasting-pp_feed', $working_powerpressFeed, false);
				unset($working_powerpressFeed);
			}

			//Prepare Table of elements
			$libsyn_feed_wp_list_table = new \Libsyn\Service\Table($feed_args, $powerpress_feed_items);
			$libsyn_feed_wp_list_table->item_headers = array(
				'id' => 'id'
				,'title' => 'Episode Title'
				,'description' => 'Description'
				,'permalink' => 'Episode Link'
				,'release_date' => 'Release Date'
			);
			$libsyn_feed_wp_list_table->prepare_items();
		} elseif( !is_wp_error($powerpressFeed) && $powerpressFeed instanceof \__PHP_Incomplete_Class) {//unserialized instanceof SimplePie (Causes serialize/unserialize problem)
			$msg = "It appears that the Powerpress Feed URL we are trying to import is invalid.  You can check your settings or try to import manually below.";
			if($plugin->hasLogger) $plugin->logger->error("Importer:\t".$msg);
			$error = true;
			$checkPowerpress = false;
		} elseif( !is_wp_error($powerpressFeed) && is_object($powerpressFeed) && !empty($powerpressFeed->items) ) {//default saved powepressfeed (stdclass)
			//Prepare Table of elements
			$libsyn_feed_wp_list_table = new \Libsyn\Service\Table($feed_args, $powerpressFeed->items);
			$libsyn_feed_wp_list_table->item_headers = array(
				'id' => 'id'
				,'title' => 'Episode Title'
				,'description' => 'Description'
				,'permalink' => 'Episode Link'
				,'release_date' => 'Release Date'
			);
			$libsyn_feed_wp_list_table->prepare_items();
		} elseif( is_wp_error($powerpressFeed) ) {
			if(!empty($powerpressFeed->{'errors'}) && !empty($powerpressFeed->{'errors'}['simplepie-error'][0])) {
					$msg = "Feed Reader Error:\t" . $powerpressFeed->{'errors'}['simplepie-error'][0];
			} else {
					$msg = "Your Powerpress feed cannot be read by the importer.  The feed may be invalid.";
			}
			if($plugin->hasLogger) $plugin->logger->error("Importer:\t".$msg);
			$error = true;
		} else {
			$msg = "Something went wrong when trying to read your Powerpress Feed.  Or you can check your settings or try entering your Feed URL manually below.";
			if($plugin->hasLogger) $plugin->logger->error("Importer:\t".$msg);
			$error = true;
			$checkPowerpress = false;
		}
	}
}


//check for clear or inserting of posts
$clearImports = false;
$addImports = false;
if(!empty($_POST['libsyn-importer-action'])) {
	switch($_POST['libsyn-importer-action']) {
		case "clear_imports":
			//handle clear imports
			$clearImports = true;
			break;
		case "add_player_to_posts":
			//Set Time Limit
			$utilities->set_safe_time_limit(300);
			//handle add player to posts
			$addImports = true;
			break;
	}
}


//Check in case feed import timed out
if(empty($feedImportId) && ($ppFeedTriggered || $feedImportTriggered)) {
	$importStatus = (!empty($api) && $api instanceof \Libsyn\Api) ? $plugin->feedImportStatus($api, array('list_items' => true)) : false;
	if(!empty($importStatus->{'feed-import'}) && is_array($importStatus->{'feed-import'})) {
		if(!empty($importStatus->{'feed-import'}[0]->parent_job_id)) {
			$feedImportId = $importStatus->{'feed-import'}[0]->parent_job_id;
			if(!empty($feedImportId)) {
				update_user_option($current_user_id, 'libsyn-podcasting-feed_import_id', $feedImportId, false);
			}
		}
	}
}

if(empty($feedImportPosts) && ($ppFeedTriggered)) {
	if ( !empty($powerpressFeed) && ( !is_wp_error($powerpressFeed) && is_object($powerpressFeed) && !empty($powerpressFeed->items) ) ) {
		$feedImportPosts = $powerpressFeed->items;
	}
	if ( !isset($feedImportPosts) && !empty($powerpress_feed_items)) {//handle in case first call timed out with no response
		$feedImportPosts = $powerpress_feed_items;
	}
}

//Handle Feed
if(!empty($feedImportId)) {
	//get the job status
	if(!empty($feedImportPosts) && is_array($feedImportPosts)) {//pass $feedImportPosts as items to handle feed import update only media
		if(!isset($importStatus)) {
			$importStatus = (!empty($api) && $api instanceof \Libsyn\Api) ? $plugin->feedImportStatus($api, array('job_id' => $feedImportId, 'list_items' => true)) : false;
		}
	} else {
		if(!isset($importStatus)) {
			$importStatus = (!empty($api) && $api instanceof \Libsyn\Api) ? $plugin->feedImportStatus($api, array('job_id' => $feedImportId, 'list_items' => true)) : false;
		}
	}

	$feed_args = array(
		'singular'=> 'libsyn_feed_item' //Singular label
		,'plural' => 'libsyn_feed_items' //plural label, also this well be one of the table css class
		,'ajax'   => false //We won't support Ajax for this table
		,'screen' => get_current_screen()
	);
	//setup new array with feed data
	$imported_feed = array();
	$x=0;

	//Feed Import Status
	if(!empty($importStatus->{'feed-import'}) && is_array($importStatus->{'feed-import'})) {

		$msg = "Import Submitted!  Please reload the page to check the status of your import.";
		$error = false;

		//WP Uses a page for posts.  Get it here to check against
		$page_for_posts_url = get_permalink( get_option( 'page_for_posts' ) );
		foreach ($importStatus->{'feed-import'} as $row) {
			if(function_exists('url_to_postid') && !empty($row->custom_permalink_url)) {
				$rowCustomPermalinkUrl = url_to_postid($row->custom_permalink_url);
			} else {
				$rowCustomPermalinkUrl = '';
			}
			if(!empty($feedImportPosts) && !empty($ppFeedTriggered)) {//has powerpress or local feed
				foreach ($feedImportPosts as $feed_item) {

					//Find the Id
					if ( !empty($feed_item->{'guid'}) ) {
						$working_id = $utilities->get_id_from_guid($feed_item->{'guid'});
					}
					if( empty($working_id) && !empty($feed_item->{'id'}) ) {
						$working_id = $feed_item->{'id'};
						$working_id = ( ( function_exists('mb_strpos') && mb_strpos($working_id, 'entry_') === false ) || ( strpos($working_id, 'entry_') === false ) ) ? $working_id : null;
						if ( function_exists('wp_parse_url') && !empty($row->guid) ) { //check to make sure the guid matches domain of site since we are grabbing the post id param
							$working_domain = wp_parse_url($row->guid, 'host');
							$working_domain_feed_link = wp_parse_url($feed_item->{'link'});
							if ( !empty($working_domain) && !empty($working_domain_feed_link) ) {
								$working_id = ($working_domain == $working_domain_feed_link) ? $working_id : null;
							}
							if ( isset($working_domain) ) unset($working_domain);
							if ( isset($working_domain_feed_link) ) unset($working_domain_feed_link);
						}
					}
					if( empty($working_id) && ( function_exists('url_to_postid') && !empty($feed_item->{'link'}) ) ) {
						$working_id = url_to_postid($feed_item->{'link'});
					}
					if ( empty($working_id) ) {
						$working_id = null;
					}

					if( //Check to make sure working_id matches up to what we imported
						!empty($working_id) &&
						( empty($page_for_posts_url) || ( !empty($page_for_posts_url) && ( $row->custom_permalink_url !== $page_for_posts_url ) ) ) &&
						( !empty($feed_item->{'guid'}) && !empty($row->guid) && ( $feed_item->{'guid'} === $row->guid ) ) ||
						( !empty($row->guid) && ( ( function_exists('mb_strpos') && mb_strpos($row->guid, $working_id) !== false ) || ( strpos($row->guid, $working_id) !== false ) ) )
					) {
						$contentStatus = $row->primary_content_status;
						if(empty($contentStatus) && !empty($row->releases)) {
							if(is_array($row->releases)) {
								foreach($row->releases as $release) {
									if(!empty($release->{'release_state'}) && $release->{'release_state'} == "released") {
										$contentStatus = "available";
									}
								}
							}
						}

						switch ($contentStatus) {
							case "":
							case null:
							case false:
								$contentStatus = "unavailable";
								$contentStatusColor = "style=\"color:red;\"";
								$hasIncompleteImport = true;
								break;
							case "awaiting_payload":
								$contentStatus = "pending download";
								$contentStatusColor = "style=\"color:orange;\"";
								$hasIncompleteImport = true;
								break;
							case "failed":
								$contentStatusColor = "style=\"color:red;\"";
								break;
							case "available":
								if($addImports) {
									try {
										if(!empty($working_id)) {
											$importer->addPlayer($working_id, $row->url);
											if(!empty($api) && $api instanceof \Libsyn\Api) {
												$importer->createMetadata($working_id, $row, $api);
											} else {
												$importer->createMetadata($working_id, $row);
											}
										}
									} catch (Exception $e) {
										//TODO: Log error
									}
									$msg = "Successfully added Libsyn Player to Imported Posts!";
								}
								$contentStatusColor = "";
								break;
							default:
								$contentStatusColor = "";
						}
						if($clearImports) {
							if(!empty($working_id)) {
								try {
									$importer->clearPlayer($working_id);
									$importer->clearMetadata($working_id);
								} catch (Exception $e) {
									//TODO: Log error
								}
							}
						}

						$duplicate = false;
						if(!empty($imported_feed)) {
							foreach($imported_feed as $feed) { //check to make sure this is not a duplicate
								if(!empty($feed->id) && $feed->id === $row->id) {
									$duplicate = true;
								}
							}
						}
						if(!$duplicate) {
							$contentStatus = ucfirst($contentStatus);
							$imported_feed[$x] = new \stdClass();
							$imported_feed[$x]->id = $row->id;
							$imported_feed[$x]->title = $row->item_title;
							$imported_feed[$x]->content = $row->item_body;
							$imported_feed[$x]->subtitle = $row->item_subtitle;
							$imported_feed[$x]->permalink = "<a " . $contentStatusColor ." href=\"".$row->url."\" target=\"_blank\">" . $contentStatus . "</a>";
							$imported_feed[$x]->status = $contentStatus;
							$imported_feed[$x]->release_date = $row->release_date;
							$x++;
						}
						if(isset($contentStatus)) unset($contentStatus);
					}
					if( isset($feedItemId) ) unset($feedItemId);
					// if( isset($feedItemLink) ) unset($feedItemLink);
					if( isset($working_id) ) unset($working_id);
				}
			} elseif(empty($ppFeedTriggered)) {//has external feed import (make sure not pp feed import)
				if(!empty($row->custom_permalink_url) && empty($rowCustomPermalinkUrl)) {//check that this is not actually a wp post already
					$contentStatus = $row->primary_content_status;
					if(empty($contentStatus) && !empty($row->release_state) && $row->release_state === "released") {
						$contentStatus = "available";
					}
					switch ($contentStatus) {
						case "":
						case null:
						case false:
							$contentStatus = "unavailable";
							$contentStatusColor = "style=\"color:red;\"";
							$hasIncompleteImport = true;
							break;
						case "awaiting_payload":
							$contentStatus = "pending download";
							$contentStatusColor = "style=\"color:orange;\"";
							$hasIncompleteImport = true;
							break;
						case "failed":
							$contentStatusColor = "style=\"color:red;\"";
							break;
						case "available":
							if($addImports) {
								if(!empty($row->id)) {//checking item_id isset
									try {
										$working_post_id = $importer->createPost($row);
										if(!empty($working_post_id)) {
											if(!empty($api) && $api instanceof \Libsyn\Api) {
												$importer->createMetadata($working_post_id, $row, $api);
											} else {
												$importer->createMetadata($working_post_id, $row);
											}
											$importer->addPlayer($working_post_id, $row->url);
											$importedPostIds = get_user_option('libsyn-podcasting-imported_post_ids');
											if(empty($importedPostIds)) {
												$importedPostIds = array();
											} else {
												$importedPostIds = (!empty($importedPostIds)) ? $importedPostIds : array();
											}
											$importedPostIds[] = $working_post_id;
											update_user_option($current_user_id, 'libsyn-podcasting-imported_post_ids', $importedPostIds, false);


										}
									} catch (Exception $e) {
										//TODO: Log error
									}
								}
								$msg = "Successfully added Libsyn Player to Imported Posts!";
							}
							$contentStatusColor = "";
							break;
						default:
							$contentStatusColor = "";
					}

					$duplicate = false;
					if(!empty($imported_feed)) {
						foreach($imported_feed as $feed) { //check to make sure this is not a duplicate
							if(!empty($feed->id) && $feed->id === $row->id) {
								$duplicate = true;
							}
						}
					}
					if(!$duplicate) {
						$contentStatus = ucfirst($contentStatus);
						$imported_feed[$x] = new \stdClass();
						$imported_feed[$x]->id = $row->id;
						$imported_feed[$x]->title = $row->item_title;
						$imported_feed[$x]->content = $row->item_body;
						$imported_feed[$x]->subtitle = $row->item_subtitle;
						$imported_feed[$x]->permalink = "<a " . $contentStatusColor ." href=\"".$row->url."\" target=\"_blank\">" . $contentStatus . "</a>";
						$imported_feed[$x]->custom_permalink_url = $row->url;
						$imported_feed[$x]->status = $contentStatus;
						$imported_feed[$x]->release_date = $row->release_date;
						$x++;
					}
					if(isset($contentStatus)) unset($contentStatus);
				}
				if( isset($working_id) ) unset($working_id);
			}
			if( isset($rowCustomPermalinkUrl) ) unset($rowCustomPermalinkUrl);
		}

		//Prepare Table of elements
		$libsyn_feed_status_wp_list_table = new \Libsyn\Service\Table($feed_args, $imported_feed);
		$libsyn_feed_status_wp_list_table->item_headers = array(
			'id' => 'id'
			,'title' => 'Episode Title'
			,'subtitle' => 'Subtitle'
			,'permalink' => 'Episode Link'
			,'release_date' => 'Release Date'
		);
		$libsyn_feed_status_wp_list_table->prepare_items();
	}
}

//Handle clear imports
if($clearImports) {
	try {
		delete_user_option($current_user_id, 'libsyn-podcasting-pp_feed', false);
		delete_user_option($current_user_id, 'libsyn-podcasting-pp_feed_url', false);
		delete_user_option($current_user_id, 'libsyn-podcasting-pp_feed_triggered', false);
		delete_user_option($current_user_id, 'libsyn-podcasting-feed_import_triggered', false);
		delete_user_option($current_user_id, 'libsyn-podcasting-feed_import_id', false);
		delete_user_option($current_user_id, 'libsyn-podcasting-feed_import_origin_feed', false);
		delete_user_option($current_user_id, 'libsyn-podcasting-feed_import_libysn_feed', false);
		delete_user_option($current_user_id, 'libsyn-podcasting-feed_import_posts', false);

		$importedPostIds = get_user_option('libsyn-podcasting-imported_post_ids');
		if(!empty($importedPostIds) && is_string($importedPostIds)) {
			$importedPostIds = json_decode($importedPostIds, true);
			if(is_array($importedPostIds)) {
				foreach($importedPostIds as $postId) {
					if(!empty($postId)) {
						if(function_exists('wp_delete_post')) wp_delete_post($postId, false);//setting 2nd param true forces delete and not to trash
						$importer->clearPlayer($postId);
						$importer->clearMetadata($postId);
					}

				}
			}
		}
		delete_user_option($current_user_id, 'libsyn-podcasting-imported_post_ids', false);
	} catch (Exception $e) {
		//TODO: Log error
	}
	$msg = "Cleared importer settings and posts from Wordpress";
}

if(isset($_POST['msg'])) $msg = $_POST['msg'];
if(isset($_POST['error'])) $error = ($_POST['error']==='true')?true:false;

/* Handle Form Submit */
if (!empty( $_POST )) {
	if($api instanceof \Libsyn\Api) { //Brand new setup or changes?
		if(!empty($_POST['libsyn-powerpress-feed-url-submit']) && ($_POST['libsyn-powerpress-feed-url-submit'] == "true")) {
			if(!empty($powerpressFeed)) {
				if(!is_wp_error($powerpressFeed) && !empty($powerpressFeed->feed_url)) {
					$importFeedUrl = $sanitize->url_raw($powerpressFeed->feed_url);
				} elseif(!empty($ppFeedUrl)) {//There may be a error when loading the feed try to use the feed url built above
					$importFeedUrl = $ppFeedUrl;
				}
			}
			if(empty($importFeedUrl)) {
				if(!is_wp_error($powerpressFeed)) {
					$msg = "Powerpress feed seems to be invalid.  Please check the following URL:  <em>{$powerpressFeed}</em>";
					$error = true;
				} else {
					$msg = "Powerpress feed seems to be invalid.  Please check your Powerpress Feed settings.";
					$error = true;
				}
			} else {
				update_user_option($current_user_id, 'libsyn-podcasting-pp_feed_triggered', 'true', false);
			}
		} elseif(!empty($_POST['libsyn-import-feed-url'])) {
			$importFeedUrl = $sanitize->url_raw($_POST['libsyn-import-feed-url']);
		}
		if(!empty($importFeedUrl)) {
			//run feed importer
			update_user_option($current_user_id, 'libsyn-podcasting-feed_import_triggered', 'true', false);
			$importData = $plugin->feedImport($api, array('feed_url' => $importFeedUrl));
			if(!empty($importData) && $importData->{'status'} == "success") {//save callback data
				if(!empty($importData->origin_feed)) {
					update_user_option($current_user_id, 'libsyn-podcasting-feed_import_origin_feed', $importData->origin_feed, false);
				}
				if(!empty($importData->feed_url)) {
					update_user_option($current_user_id, 'libsyn-podcasting-feed_import_libysn_feed', $importData->feed_url, false);
				}
				if(!empty($importData->job_id)) {
					update_user_option($current_user_id, 'libsyn-podcasting-feed_import_id', $importData->job_id, false);
				}
				if(!empty($importData->entries)) {
					update_user_option($current_user_id, 'libsyn-podcasting-feed_import_posts', $importData->entries, false);
				}

				//setup cron emailer
				$importerEmailer = (new \Libsyn\Service\Cron\ImporterEmailer())->activate();

			} else {
				$msg = "Feed Importer failed to import your feed please check your settings and try again.";
			}
		}

		if(!empty($feedImportId)) {//has existing feed import data

		}
	} else { //Failed Api check
		$msg = "Could not run import since your Libsyn Show is not configured.  Please visit the Settings page.";
	}

	//Need to redirect back to refesh page
	$msgParam = (!empty($msg)) ? '&'.urlencode($msg) : '';
	$url = $plugin->admin_url('admin.php').'?page=LibsynImports'.$msgParam;

	echo "<script type=\"text/javascript\">
			(function($){
				$(document).ready(function(){
					function sleepDelay(delay) {
						var start = new Date().getTime();
						$(\"form[name='libsynmodule_form'] .form-table\").css({'opacity': 0.3});
						$(\"#libsyn-loading-img\").css({'display': 'block'});
						while (new Date().getTime() < start + delay);
					}
					sleepDelay(8000);
					$(\"form[name='libsynmodule_form'] .form-table\").css({'opacity': 1});
					$(\"#libsyn-loading-img\").css({'display': 'none'});
					if (typeof window.top.location.href == 'string') window.top.location.href = \"".$url."\";
						else if(typeof document.location.href == 'string') document.location.href = \"".$url."\";
							else if(typeof window.location.href == 'string') window.location.href = \"".$url."\";
								else alert('Unknown Libsyn Plugin error 1012.  Please report this error to support@libsyn.com and help us improve this plugin!');
				});
			})(jQuery);
		 </script>";

}


//handle force page reload while media importer is running or not available
if($hasIncompleteImport && !empty($libsyn_feed_status_wp_list_table)) {
$msgParam = (!empty($msg)) ? '&'.urlencode($msg) : '';
$url = $plugin->admin_url('admin.php').'?page=LibsynImports'.$msgParam;
echo "<script type=\"text/javascript\">
		(function($){
			$(document).ready(function(){
				$(\"form[name='libsynmodule_form'] .form-table\").css({'opacity': 0.3});
				$(\"#libsyn-loading-img\").css({'display': 'block'});
				setTimeout(function(){
					if (typeof window.top.location.href == 'string') window.top.location.href = \"".$url."\";
						else if(typeof document.location.href == 'string') document.location.href = \"".$url."\";
							else if(typeof window.location.href == 'string') window.location.href = \"".$url."\";
								else alert('Unknown Libsyn Plugin error 1013.  Please report this error to support@libsyn.com and help us improve this plugin!');
				}, 15000);
				$(\"form[name='libsynmodule_form'] .form-table\").css({'opacity': 1});
				$(\"#libsyn-loading-img\").css({'display': 'none'});
			});
		})(jQuery);
	 </script>";
}

/* Handle API Creation/Update*/
if((!$api)||($api->isRefreshExpired())) { //does not have $api setup yet in WP
	$render = false;
}

/* Set Notifications */
global $libsyn_notifications;
do_action('libsyn_notifications');
?>

<?php wp_enqueue_script( 'jquery-ui-dialog', array('jquery-ui')); ?>
<?php wp_enqueue_style( 'wp-jquery-ui-dialog'); ?>
<?php wp_enqueue_script('jquery_validate', plugins_url(LIBSYN_DIR . '/lib/js/jquery.validate.min.js'), array('jquery')); ?>
<?php wp_enqueue_script('libsyn-meta-form', plugins_url(LIBSYN_DIR . '/lib/js/libsyn/meta_form.js')); ?>
<?php wp_enqueue_style( 'animate', plugins_url(LIBSYN_DIR . '/lib/css/animate.min.css')); ?>
<?php wp_enqueue_script( 'jquery-easing', plugins_url(LIBSYN_DIR . '/lib/js/jquery.easing.min.js')); ?>
<?php wp_enqueue_style( 'libsyn-meta-boxes', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_boxes.css' )); ?>
<?php wp_enqueue_style( 'libsyn-meta-form', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/meta_form.css' )); ?>
<?php wp_enqueue_style( 'libsyn-dashicons', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/dashicons.css' )); ?>

	<style media="screen" type="text/css">
	.code { font-family:'Courier New', Courier, monospace; }
	.code-bold {
		font-family:'Courier New', Courier, monospace;
		font-weight: bold;
	}
	</style>

	<!-- Main Body Area -->
	<div class="wrap">
	  <?php if (isset($msg)) echo $plugin->createNotification($msg, $error); ?>
	  <h2><?php _e("Publisher Hub - Import Feed", $libsyn_text_dom); ?><span style="float:right;"><a href="http://www.libsyn.com/"><img src="<?php _e(plugins_url( LIBSYN_DIR . '/lib/images/libsyn_dark-small.png'), $libsyn_text_dom); ?>" title="Libsyn Podcasting" height="28px"></a></span></h2>
	  <form name="<?php echo LIBSYN_NS . "form" ?>" id="<?php echo LIBSYN_NS . "form" ?>" method="post">
		 <div id="poststuff">
		  <div id="post-body">
			<div id="post-body-content">
			<?php if((isset($api) && ($api !== false)) || $render) { ?>
			<input type="hidden" id="libsyn-importer-action" name="libsyn-importer-action" />
			<!-- BOS Existing API -->
			  <div class="stuffbox" style="width:93.5%">
				<h3 class="inside hndle"><label><?php _e("Source Feed Information", $libsyn_text_dom); ?></label></h3>
				<div class="inside" style="margin: 15px;">
				  <table class="form-table">
					<tr valign="top" style="border-bottom:none;">
					  <th></th>
					  <td>
					    <div id="libsyn-loading-img"></div>
						<div style="width:50%;">
							<?php if(!$hasSavedData) { ?>
							<p class="libsyn-import-information"><em><?php _e("Here you can import a external Podcast Feed into your Libsyn account for use under Wordpress.", $libsyn_text_dom); ?></em></p>
							<?php } else { ?>
							<p class="libsyn-import-information"><em><!--Feed Import Text --></em></p>
							<?php } ?>
						</div>
					  </td>
					</tr>
					<?php if($checkPowerpress && empty($libsyn_feed_status_wp_list_table)) { ?>
					<tr valign="top">
					  <th><?php _e("Powerpress", $libsyn_text_dom); ?></th>
					  <td>
						<div class="input-field">
							<p style="font-size:1.1em;font-weight:bold;"><?php _e("Local Powerpress Feed Detected!", $libsyn_text_dom); ?></p>
							<?php if ( !empty($ppCategorySelector) ) { echo $ppCategorySelector; } elseif ( !empty($ppFeedUrl) ) { ?><p><strong>Powerpress Feed Url:</strong>&nbsp;&nbsp;<?php echo '<a href="' . $ppFeedUrl . '" target="_blank" title="Powerpress Feed Url" alt="Powerpress Feed Url">' . $ppFeedUrl . '</a>'; ?></p><?php } ?>
							<br />
							<?php if(!empty($libsyn_feed_wp_list_table) && empty($libsyn_feed_status_wp_list_table)) {
								$libsyn_feed_wp_list_table->display();
							} ?>
							<p><?php _e("Would you like to import the feed below to your Libsyn account and update existing posts with the Libsyn Player?", $libsyn_text_dom); ?><br /><strong><?php _e("Note:", $libsyn_text_dom); ?>&nbsp;&nbsp;</strong><?php _e("This would not replace any existing expisodes in your libsyn account.", $libsyn_text_dom); ?></p>
							<br />
							<div style="display:inline;">
								<button type="button" id="libsyn_import_powerpress_feed" class="button button-primary libsyn-dashicions-upload"><?php _e('Import Local Feed Above', $libsyn_text_dom); ?></button>
								&nbsp;-OR-&nbsp;
								<button type="button" id="libsyn_toggle_show_feed_importer" class="button button-primary libsyn-dashicions-download" onclick="toggleShowFeedImporter()"><?php _e('Import from a different Feed URL', $libsyn_text_dom); ?></button>
								<input type="hidden" id="libsyn-powerpress-feed-url-submit" name="libsyn-powerpress-feed-url-submit" />
								<?php if($hasSavedData) { ?>
								&nbsp;-OR-&nbsp;
								<button type="button" class="button button-primary libsyn-dashicions-trash libsyn_clear_imports"><?php _e('Clear all Imports Data', $libsyn_text_dom); ?></button>
								<?php } ?>
							</div>
						</div>
					  </td>
					</tr>
					<?php } ?>
					<?php if(!empty($feedImportId)) { ?>
					<tr valign="top">
					  <th><?php _e("Feed Import", $libsyn_text_dom); ?></th>
					  <td>
						<div class="input-field">
							<?php if(!empty($libsyn_feed_status_wp_list_table) && $hasIncompleteImport) { ?>
							<p style="font-size:1.1em;font-weight:bold;"><?php _e("Feed Import Status", $libsyn_text_dom); ?> - <span style="color:orange;"><?php _e("Processing", $libsyn_text_dom); ?></span></p>
							<?php } elseif(!empty($libsyn_feed_status_wp_list_table) && !$hasIncompleteImport) { ?>
							<p style="font-size:1.1em;font-weight:bold;"><?php _e("Feed Import Status", $libsyn_text_dom); ?> - <span style="color:green;"><?php _e("Success", $libsyn_text_dom); ?></span></p>
							<?php } elseif(empty($libsyn_feed_status_wp_list_table)) { ?>
							<p style="font-size:1.1em;font-weight:bold;"><?php _e("Feed Import Status", $libsyn_text_dom); ?> - <span style="color:red;"><?php _e("Failed", $libsyn_text_dom); ?></span></p>
							<?php } else { ?>
							<p style="font-size:1.1em;font-weight:bold;"><?php _e("Feed Import Status", $libsyn_text_dom); ?></p>
							<?php } ?>
							<br />
							<?php IF(!empty($libsyn_feed_status_wp_list_table)): ?>
							<?php if($hasIncompleteImport) { ?>
							<p><strong><?php _e("Your feed import is currently processing.", $libsyn_text_dom); ?></strong>&nbsp;<?php _e("This page will refresh and update the Episode Link for each episode as the process runs. You will receive an email, as well as notice on this page once the import is fully complete.", $libsyn_text_dom); ?></p>
							<?php } ?>
							<?php if(!empty($libsyn_feed_status_wp_list_table) && !$hasIncompleteImport) { ?>
							<p>
								<?php _e("Congratulations! You have successfully imported your RSS feed. Your next step is to create a 301 redirect which will point your old RSS feed to your new RSS feed, and setup a special \"new feed URL\" tag in your Libsyn feed. Please follow these steps to setup a 301 redirect:", $libsyn_text_dom); ?>
							</p>
							<br />
							<ul>
								<li>–&nbsp;<?php _e("Download Redirection", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php _e("Go under Tools", $libsyn_text_dom); ?> --> <?php _e("Redirection and hit Add New", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php _e("In the Source URL, enter your old feed URL", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php _e("In the Target URL, enter your Libsyn RSS feed URL", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php _e("Hit Add Redirect", $libsyn_text_dom); ?></li>
							</ul>
								<?php _e("Please follow these steps to setup a new feed URL tag in your Libsyn feed:", $libsyn_text_dom); ?>
							<br />
							<ul>
								<li>–&nbsp;<?php _e("Log into your ", $libsyn_text_dom); ?><a href="https://login.libsyn.com" target="_blank" title="Libsyn Dashboard" alt="Libsyn Dashboard"><?php _e("Libsyn Dashboard", $libsyn_text_dom); ?></a></li>
								<li>–&nbsp;<?php _e("Select Destinations", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php _e("Select Libsyn Classic Feed", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php _e("Scroll towards the bottom and select Advanced Options", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php _e("Enter the Apple Podcasts redirect tag in the Extra RSS Tags text box:", $libsyn_text_dom); ?></li>
								<li><strong>&lt;itunes:new-feed-url&gt;<?php if ( !empty($libsynFeedUrl) ) { echo $libsynFeedUrl; } else { echo 'http://www.myfeedurl.com/rss.xml'; } ?>&lt;/itunes:new-feed-url&gt;</strong></li>
								<li><small><?php if ( !empty($libsynFeedUrl) ) { echo __('Note: ', $libsyn_text_dom) . '“' . $libsynFeedUrl . '”'. __(' is your current imported destination (Libsyn) feed url.', $libsyn_text_dom); } else { echo __('Replace ', $libsyn_text_dom) . '“http://www.myfeedurl.com/rss.xml” ' . __('with whatever the URL of the feed you will be using (Libsyn) is.', $libsyn_text_dom); } ?></small></li>
							</ul>
							<br />
							<?php } ?>
							<br />
							<?php if(!empty($ppFeedTriggered)) { ?>
							<p style="font-size:1.1em;font-weight:bold;"><?php _e("If Migrating from Powerpress", $libsyn_text_dom); ?></p>
							<?php _e("Once your redirects are in place, your next step is to update the player on your WordPress posts to the Libsyn player.", $libsyn_text_dom); ?>
							<br />
							<ul>
								<li>–&nbsp;<?php _e("Come back to this page, scroll to the bottom, and hit \"Add Libsyn Player to Wordpress Posts\".", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php echo __("Go under Plugins", $libsyn_text_dom) . " --> " . __("Installed Plugins", $libsyn_text_dom); ?></li>
								<li>–&nbsp;<?php _e("Hit Deactivate for PowerPress", $libsyn_text_dom); ?></li>
							</ul>
							<?php _e("This will update your player on your Wordpress posts to your Libsyn player, and completes your migration process from Powerpress to Libsyn.", $libsyn_text_dom); ?>
							<?php } ?>
							</p>
							<?php ELSEIF(empty($libsyn_feed_status_wp_list_table)): ?>
							<p>
								<?php _e("We initiated the feed importer, but it appears that the media imported into your Libsyn account may already exist or media failed to download from your feed.  Please make sure the media from your feed import is available and/or make sure the media does not already exist in your Libsyn account.", $libsyn_text_dom); ?>
							</p>
							<br />
							<p>
								<strong>
									<?php _e("Depending on the size of your feed, the importer may take some time to process, try waiting a few minutes then refreshing your browswer.", $libsyn_text_dom); ?>
								</strong>
							</p>
							<br />
							<p>
								<?php echo __("If the importer is still not working then you may ", $libsyn_text_dom) . "<strong>" . __("Clear all imports data", $libsyn_text_dom) . "</strong>" . __("and try again.", $libsyn_text_dom); ?>
							</p>
							<br />
							<button type="button" class="button button-primary libsyn-dashicions-trash libsyn_clear_imports"><?php echo __('Clear all Imports Data', $libsyn_text_dom); ?></button>
							<?php ENDIF; ?>
							<?php if( !empty($libsyn_feed_status_wp_list_table)) {
								$libsyn_feed_status_wp_list_table->display(); ?>
							<div style="display:inline;">
								<button type="button" id="libsyn_add_player_to_posts" class="button button-primary libsyn-dashicions-format-video"><?php echo __('Add Libsyn Player to Wordpress Posts', $libsyn_text_dom); ?></button>
								&nbsp;-OR-&nbsp;
								<button type="button" class="button button-primary libsyn-dashicions-trash libsyn_clear_imports"><?php echo __('Clear all Imports Data', $libsyn_text_dom); ?></button>
							</div>
							<?php } ?>

						</div>
					  </td>
					</tr>
					<?php } ?>
					<tr valign="top" id="libsyn-feed-import-tr" <?php if ( $checkPowerpress ) { echo 'style="display:none;"'; } ?>>
					  <th><?php _e("Feed URL", $libsyn_text_dom); ?></th>
					  <td>
						<div class="input-field">
							<input type="url" style="width:64%;" name="libsyn-import-feed-url" id="libsyn-import-feed-url" class="validate" pattern="https?://.+" />
							<span class="helper-text" data-error="Invalid Feed" data-success="Feed Valid"></span>
							<button type="button" id="libsyn_import_feed_rss" class="button button-primary libsyn-dashicions-update"><?php echo __('Import Feed', $libsyn_text_dom); ?></button>
							<?php if($hasSavedData) { ?>
							<button type="button" class="button button-primary libsyn-dashicions-trash libsyn_clear_imports"><?php echo __('Clear all Imports Data', $libsyn_text_dom); ?></button>
							<?php } ?>
						</div>
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
				  </table>
				</div>
			  </div>
			<!-- EOS Existing API -->
			  <!-- Dialogs -->
			  <div id="import-libsyn-player-dialog" class="hidden" title="Post Import">
				<p><?php echo '<span style="color:red;font-weight:600;">' . __('Warning!', $libsyn_text_dom) . '</span> ' . __('By accepting you will modifying your Wordpress Posts with adding the player to the available feed import posts.  Would you like to proceed?', $libsyn_text_dom); ?></p>
				<p id="extra-text"></p>
				<br>
			  </div>
			  <div id="clear-settings-dialog" class="hidden" title="Confirm Clear Settings">
				<p><?php echo '<span style="color:red;font-weight:600;">' . __('Warning!', $libsyn_text_dom) . '</span> ' . __('By accepting you will be removing all your import settings.  Click yes to continue.', $libsyn_text_dom); ?></p>
				<p id="extra-text"><?php echo '<span style="color:gray;font-weight:600;">' . __("NOTE:", $libsyn_text_dom) . '</span>  ' . __("You will also need to remove any imported posts from your within the Libsyn Account Dashboard.", $libsyn_text_dom); ?></p>
				<br>
			  </div>
			  <div id="accept-import-dialog" class="hidden" title="Confirm Import">
				<p><?php echo '<span style="color:red;font-weight:600;">' . __('Warning!', $libsyn_text_dom) . '</span> ' . __('By accepting you will importing the episodes in your external feed into your Libsyn Account. Would you like to proceed?', $libsyn_text_dom); ?></p>
				<br>
			  </div>

			<?php } else { ?>
			<!-- BOS Existing API -->
			  <div class="stuffbox" style="width:93.5%">
				<h3 class="hndle"><span><?php _e("Plugin needs configured", $libsyn_text_dom); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <p style="font-size: 1.8em;"><?php echo __("The Libsyn Publisher Hub is either not setup or something is wrong with the configuration, please visit the ", $libsyn_text_dom) . "<a href=\"".admin_url('admin.php?page=LibsynSettings')."\">" . __("settings page", $libsyn_text_dom) . "</a>."; ?></p>
				</div>
			  </div>
			<!-- EOS Existing API -->
			<?php } ?>
			<!-- BOS Libsyn WP Post Page -->
			<div class="stuffbox" id="libsyn-wp-post-page" style="display:none;width:93.5%;">

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
				var setOverlays = function() {
					//make sure overlays are not over dialogs
					$(".ui-widget-overlay").each(function() {
						$(this).css("z-index", 999);
						$(this).attr("style", "z-index:999;");
					});
					$(".ui-dialog-title").each(function() {
						$(this).css("z-index", 1002);
					});
					$(".ui-dialog").each(function() {
						$(this).css("z-index", 1002);
					});
					$(".ui-colorpicker").each(function() {
						$(this).css("z-index", 1003);
					});
				}

				//check ajax
				<?php if(empty($feedImportTriggered)) { //Only run check before feed import has been triggered to save on resources ?>
				var check_ajax_url = "<?php echo $sanitize->text($plugin->admin_url() . '?action=libsyn_check_url&libsyn_check_url=1'); ?>";
				var ajax_error_message = '<?php echo __("Something went wrong when trying to load your site\'s base url.  Please make sure your \"Site Address (URL)\" in Wordpress settings is correct.", $libsyn_text_dom); ?>';
				$.getJSON( check_ajax_url).done(function(json) {
					if(json){
						//success do nothing
					} else {
						//redirect to error out
						var ajax_error_url = '<?php echo $plugin->admin_url("admin.php")."?page=LibsynImports&error=true&msg="; ?>' + ajax_error_message;
						if (typeof window.top.location.href == "string") window.top.location.href = ajax_error_url;
								else if(typeof document.location.href == "string") document.location.href = ajax_error_url;
									else if(typeof window.location.href == "string") window.location.href = ajax_error_url;
										else alert('<?php __("Unknown javascript error 1028.  Please report this error to support@libsyn.com and help us improve this plugin!", $libsyn_text_dom); ?>');
					}
				}).fail(function(jqxhr, textStatus, error) {
						//redirect to error out
						var ajax_error_url = "<?php echo $plugin->admin_url('admin.php').'?page=LibsynImports&error=true&msg='; ?>" + ajax_error_message;
						if (typeof window.top.location.href == "string") window.top.location.href = ajax_error_url;
								else if(typeof document.location.href == "string") document.location.href = ajax_error_url;
									else if(typeof window.location.href == "string") window.location.href = ajax_error_url;
										else alert('<?php echo __("Unknown javascript error 1029.  Please report this error to support@libsyn.com and help us improve this plugin!", $libsyn_text_dom); ?>');
				});
				<?php } ?>
				$("#libsyn_toggle_show_feed_importer").click( function() {
					$("#libsyn-feed-import-tr").toggle('fast');
				});
				$("#libsyn_import_feed_rss").click( function() {
					//check if input fields are valid
					if ( $('#libsyn-import-feed-url').valid() ) {
						if ( $("#libsyn-import-feed-url").val() !== "" && $("#libsyn-import-feed-url").prop("validity").valid ) {
							//handle submission & dialog
							$( "#accept-import-dialog" ).dialog({
								autoOpen: false,
								draggable: false,
								height: 'auto',
								width: 'auto',
								modal: true,
								resizable: false,
								open: function(event,ui){
									setOverlays();
									$(".ui-widget-overlay").bind("click", function(){
										$("#accept-import-dialog").dialog( "close" );
									});
								},
								buttons: [
									{
										id: "import-posts-dialog-button-confirm",
										text: "Proceed with Import",
										click: function(){
											$("#libsyn-powerpress-feed-url-submit").val("false");
											$("#accept-import-dialog").dialog( "close" );
											$("form[name='libsynmodule_form'] .form-table").css({'opacity': 0.3});
											$("#libsyn-loading-img").css({'display': 'block'});
											$("#<?php echo LIBSYN_NS . "form" ?>").submit();
										}
									},
									{
										id: "import-posts-dialog-button-cancel",
										text: "Cancel",
										click: function(){
											$('#accept-import-dialog').dialog('close');
										}
									}
								]
							});
							$("#accept-import-dialog").dialog( "open" );
						} else {
							if ( !$("#libsyn-import-feed-url").prop("validity").valid ){
								$("#libsyn-import-feed-url").nextAll().remove().after('<label id="libsyn-import-feed-url-error" class="error" for="libsyn-import-feed-url"><?php _e("Feed URL not valid.", $libsyn_text_dom); ?></label>');
							} else if ( $("#libsyn-import-feed-url").val() == "" ) {
								$("#libsyn-import-feed-url").nextAll().remove().after('<label id="libsyn-import-feed-url-error" class="error" for="libsyn-import-feed-url"><?php _e("You must enter a Feed Import URL.", $libsyn_text_dom); ?></label>');
							}
						}
					}
				});
				$("#libsyn_import_powerpress_feed").click( function() {
					if ( $("input[name='pp_category_feed_selector']").length && ( $("input[name='pp_category_feed_selector']").valid() == false ) ) {
						var libsyn_pp_valid = false;
					} else {
						var libsyn_pp_valid = true;
					}
					if ( libsyn_pp_valid ) {
						//Set Validation Message
						if ( $(".<?php echo LIBSYN_NS . "_pp_category_selector"; ?>").length ) {
							console.log($("<?php echo LIBSYN_NS . "_pp_category_selector"; ?>").find("#helper-text"));
							$(".<?php echo LIBSYN_NS . "_pp_category_selector"; ?>").find("#helper-text").empty().hide('fast');
						}

						//handle submission & dialog
						$( "#accept-import-dialog" ).dialog({
							autoOpen: false,
							draggable: false,
							height: 'auto',
							width: 'auto',
							modal: true,
							resizable: false,
							open: function(event,ui){
								setOverlays();
								$(".ui-widget-overlay").bind('click', function(){
									$("#accept-import-dialog").dialog( "close" );
								});
							},
							buttons: [
								{
									id: "import-posts-dialog-button-confirm",
									text: "<?php _e("Proceed with Import", $libsyn_text_dom); ?>",
									click: function(){
										$("#libsyn-powerpress-feed-url-submit").val("true");
										$("#accept-import-dialog").dialog( "close" );
										$("form[name='libsynmodule_form'] .form-table").css({'opacity': 0.3});
										$("#libsyn-loading-img").css({'display': 'block'});
										$("#<?php echo LIBSYN_NS . "form" ?>").submit();
									}
								},
								{
									id: "import-posts-dialog-button-cancel",
									text: "Cancel",
									click: function(){
										$("#accept-import-dialog").dialog( "close" );
									}
								}
							]
						});
						$("#accept-import-dialog").dialog( "open" );
					} else {
						//Set Validation Message
						if ( $(".<?php echo LIBSYN_NS . "_pp_category_selector"; ?>").length ) {
							console.log($("<?php echo LIBSYN_NS . "_pp_category_selector"; ?>").find("#helper-text"));
							$(".<?php echo LIBSYN_NS . "_pp_category_selector"; ?>").find("#helper-text").empty().append("<?php _e("You must select a category feed to continue.", $libsyn_text_dom); ?>").fadeIn("fast");
						}
					}
				});
				$("#libsyn_add_player_to_posts").click( function() {
					<?php if ( $checkPowerpress ) { ?>
					$("#import-libsyn-player-dialog").children("#extra-text").empty().append('<?php echo "<span style=\"color:green;font-weight:600;\">" . __("NOTE:", $libsyn_text_dom) . "</span>  " . __("You may uninstall the Powerpress Plugin after this to avoid duplicate players appearing in your posts.", $libsyn_text_dom); ?>');
					<?php } ?>
					//handle submission & dialog
					$( "#import-libsyn-player-dialog" ).dialog({
						autoOpen: false,
						draggable: false,
						height: 'auto',
						width: 'auto',
						modal: true,
						resizable: false,
						open: function(event,ui){
							setOverlays();
							$(".ui-widget-overlay").bind("click", function(){
								$('#import-libsyn-player-dialog').dialog( "close" );
							});
						},
						buttons: [
							{
								id: "import-posts-dialog-button-confirm",
								text: "Add Libsyn Player",
								click: function(){
									$("#libsyn-importer-action").val("add_player_to_posts");
									$("#import-libsyn-player-dialog").dialog( "close" );
									$("form[name='libsynmodule_form'] .form-table").css({'opacity': 0.3});
									$("#libsyn-loading-img").css({'display': 'block'});
									$("#<?php echo LIBSYN_NS . "form" ?>").submit();
								}
							},
							{
								id: "import-posts-dialog-button-cancel",
								text: "Cancel",
								click: function(){
									$("#import-libsyn-player-dialog").dialog( "close" );
								}
							}
						]
					});
					$("#import-libsyn-player-dialog").dialog( "open" );
				});
				$(".libsyn_clear_imports").each(function() {
					$(this).click( function() {
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
								$(".ui-widget-overlay").bind("click" ,function(){
									$("#clear-settings-dialog").dialog("close");
								});
							},
							buttons: [
								{
									id: "clear-settings-dialog-button-confirm",
									text: "Clear Imports",
									click: function(){
										$("#libsyn-importer-action").val( "clear_imports" );
										$("#clear-settings-dialog").dialog( "close" );
										$("form[name='libsynmodule_form'] .form-table").css({'opacity': 0.3});
										$("#libsyn-loading-img").css({'display': 'block'});
										$("#<?php echo LIBSYN_NS . "form" ?>").submit();
									}
								},
								{
									id: "clear-settings-dialog-button-cancel",
									text: "Cancel",
									click: function(){
										$("#clear-settings-dialog").dialog( "close" );
									}
								}
							]
						});
						$("#clear-settings-dialog").dialog( "open" );
					});
					$("#libsyn-import-feed-url").focus(function() {
						$("#libsyn-import-feed-url").siblings(".helper-text").empty();
					});
				});
				<?php if(!empty($ppCategorySelector)) { ?>
				var libsyn_ajax_url_base = "<?php echo $sanitize->text($plugin->admin_url() . '?action=libsyn_pploadfeed&libsyn_pploadfeed=1'); ?>";
				$.fn.loadWith = function(u){
					var c=$(this);
					$("form[name='libsynmodule_form'] .form-table").css({'opacity': 0.3});
					$("#libsyn-loading-img").css({'display': 'block'});
					$("form[name='libsynmodule_form'] .tablenav").each(function(el) {
						$(this).remove();
					});
					$.get(u,function(d){
						c.replaceWith(d);
					})
					.fail(function() {
						console.log("<?php _e("Something went wrong when loading the category feed.", $libsyn_text_dom); ?>")
					})
					.always(function() {
						$("form[name='libsynmodule_form'] .form-table").css({'opacity': 1});
						$("#libsyn-loading-img").css({'display': 'none'});
					});
				};
				$("input[name='pp_category_feed_selector']").each(function() {
					<?php if(!empty($powerpressFeedUrl)) { ?>
					if( $(this).val() == '<?php if ( !empty($powerpressFeedUrl) ) { echo $powerpressFeedUrl; } ?>' ) {
						$(this).attr('checked', true);
					}
					<?php } ?>
					$(this).change(function() {
						$("#libsyn-powerpress-feed-url-submit").val($(this).val());
						$("table.wp-list-table").loadWith(libsyn_ajax_url_base + "&pp_url=" + $(this).val());
					});
				});
				<?php } ?>
			});
		})(jQuery);
	</script>
<?php //re-enable feed caching
add_action( 'wp_feed_options', 'Libsyn\\Utilities::enableFeedCaching' );
?>
