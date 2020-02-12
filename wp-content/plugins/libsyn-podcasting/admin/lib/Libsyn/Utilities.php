<?php
namespace Libsyn;

class Utilities extends \Libsyn {

	/*                     */
	/* Plugin Meta Related */
	/*                     */

    /**
     * List of User Option keys in use for the plugin
     *
     * @since 1.0.1.6
     * @return array
     */
	public static function getUserOptionKeys() {
		return array(
				//Plugin Settings Related
				'libsyn-podcasting-client',
				'libsyn_api_settings',
				'libsyn-podcasting-settings_add_podcast_metadata',
				'libsyn-podcasting-settings_use_classic_editor',
				//Feed Import Related
				'libsyn-podcasting-pp_feed',
				'libsyn-podcasting-pp_feed_url',
				'libsyn-podcasting-pp_feed_triggered',
				'libsyn-podcasting-feed_import_triggered',
				'libsyn-podcasting-feed_import_id',
				'libsyn-podcasting-feed_import_origin_feed',
				'libsyn-podcasting-feed_import_libysn_feed',
				'libsyn-podcasting-feed_import_posts',
				'libsyn-podcasting-imported_post_ids',
				//Player Settings Related
				'libsyn-podcasting-player_use_download_link_text',
				'libsyn-podcasting-player_use_download_link',
				'libsyn-podcasting-player_custom_color',
				'libsyn-podcasting-player_placement',
				'libsyn-podcasting-player_width',
				'libsyn-podcasting-player_height',
				'libsyn-podcasting-player_use_theme',
				'libsyn-podcasting-player_use_thumbnail',
			);
	}

    /**
     * List of User Option keys in use for the plugin
     *
     * @since 1.2.1
     * @return array
     */
	public static function getSiteOptionKeys() {
		return array(
				//Additional Settings
				'libsyn-podcasting-settings_add_podcast_metadata',
				'libsyn-podcasting-settings_use_classic_editor',
			);
	}

	/**
	 * Handles WP callback to send variable to trigger AJAX response.
	 *
	 * @since 1.0.1.1
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function plugin_add_trigger_libsyn_check_ajax($vars) {
		$vars[] = 'libsyn_check_url';
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
	public static function plugin_add_trigger_libsyn_phpinfo($vars) {
		$vars[] = 'libsyn_phpinfo';
		return $vars;
	}

	/**
	 * Handles WP callback to send variable to trigger AJAX response.
	 *
	 * @since 1.0.1.4
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function plugin_add_trigger_libsyn_debuginfo($vars) {
		$vars[] = 'libsyn_debuginfo';
		return $vars;
	}

	/**
	 * Handles WP callback to save ajax settings
	 *
	 * @since 1.0.1.1
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function plugin_add_trigger_libsyn_oauth_settings($vars) {
		$vars[] = 'libsyn_oauth_settings';
		return $vars;
	}

	/**
	 * Handles WP callback to clear outh settings
	 *
	 * @since 1.0.1.1
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function plugin_add_trigger_libsyn_update_oauth_settings($vars) {
		$vars[] = 'libsyn_update_oauth_settings';
		return $vars;
	}

	/**
	 * Handles WP callback to load Powerpress Feed
	 *
	 * @since 1.0.1.6
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function plugin_add_trigger_libsyn_pploadfeed($vars) {
		$vars[] = 'libsyn_pploadfeed';
		return $vars;
	}

    /**
     * Disables Wordpress Feed Caching
	 * Needed for feed importer loading of local feeds
	 * (default 12 WP cache)
     *
	 * @since 1.0.1.6 Added action to manually remove feed caching timeout
	 * @since 1.0.1.1
     * @param string $feed
     *
     * @return void
     */
	public static function disableFeedCaching( $feed ) {
		if ( function_exists('add_action') ) {
			add_filter( 'wp_feed_cache_transient_lifetime', function($a) { return 0; } ); //refresh the feed
		}
		$feed->enable_cache( false );
	}

    /**
     * Re-enables Wordpress Feed Caching
	 * Needed for feed importer loading of local feeds
	 * (default 12 WP cache)
     *
	 * @since 1.0.1.1
     * @param string $feed
     *
     * @return void
     */
	public static function enableFeedCaching( $feed ) {
		$feed->enable_cache( true );
	}




	/*                                         */
	/* Libsyn Functions (Plugin Functionality) */
	/*                                         */

	/**
	 * Renders a simple ajax page to check against and test the ajax urls
	 *
	 *
	 * @since 1.0.1.1
	 *
	 * @return void
	 */
	public static function checkAjax() {
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}
		if ( intval($urlParams['libsyn_check_url']) === 1 ) {
			$error = false;
			$json = 'true';

			//set output
			header('Content-Type: application/json');
			if ( !$error ) echo json_encode($json);
				else echo json_encode(array());
			exit;
		}
	}

	/**
	 * Renders a phpinfo dump and returns json
	 *
	 * @since 1.0.1.1
	 *
	 * @return void
	 */
	public static function getPhpinfo() {
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}
		if ( intval($urlParams['libsyn_phpinfo']) === 1 ) {
			$data = self::parse_phpinfo();

			//set output
			header('Content-Type: text/html');
			if ( !empty($data) ) {
				echo "<h3>PHP Server Information</h3>\n" . self::pretty_print_array($data);
			} else echo "";
			exit;
		}
	}

	/**
	 * Saves Settings form oauth settings for dialog
	 *
	 * @since 1.0.1.1
	 * @return void
	 */
	public static function saveOauthSettings() {
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		$current_user_id = get_current_user_id();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}
		if ( intval($urlParams['libsyn_oauth_settings']) === 1 ) {
			$error = false;
			$json = 'true';
			$sanitize = new \Libsyn\Service\Sanitize();

			if ( isset($_POST['clientId'])&&isset($_POST['clientSecret']) ) {
				update_user_option($current_user_id, 'libsyn-podcasting-client', array('id' => $sanitize->clientId($_POST['clientId']), 'secret' => $sanitize->clientSecret($_POST['clientSecret'])), false);
				$clientId = $_POST['clientId'];
				$clientSecret = $_POST['clientSecret'];
			}
			if ( !empty($clientId) ) $json = json_encode(array('client_id' => $clientId, 'client_secret' => $clientSecret));
				else $error = true;

			//set output
			header('Content-Type: application/json');
			if ( !$error ) echo json_encode($json);
				else echo json_encode(array());
			exit;
		}
	}

	/**
	 * Saves Settings form oauth settings for dialog
	 *
	 * @since 1.0.1.1
	 * @return void
	 */
	public static function updateOauthSettings() {
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		$current_user_id = get_current_user_id();
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}
		if ( intval($urlParams['libsyn_update_oauth_settings']) === 1 ) {
			$error = false;
			$sanitize = new \Libsyn\Service\Sanitize();
			$json = 'true'; //set generic response to true

			if ( isset($_GET['client_id']) && isset($_GET['client_secret']) ) {
				update_user_option($current_user_id, 'libsyn-podcasting-client', array('id' => $sanitize->clientId($_GET['client_id']), 'secret' =>$sanitize->clientSecret($_GET['client_secret'])), false);
			} else {
				$error=true;
				$json ='false';
			}

			//set output
			header('Content-Type: application/json');
			if ( !$error ) echo json_encode($json);
				else echo json_encode(array());
			exit;
		}
	}

	/**
	 * Loads Powerpress Feed
	 *
	 * @since 1.0.1.6
	 * @return string HTML of WP_Table
	 */
	public static function loadPPFeed() {
		//Set Initial Vars
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		$current_user_id = get_current_user_id();
		add_action( 'wp_feed_options', 'Libsyn\\Utilities::disableFeedCaching' ); //disable feed caching
		if ( function_exists('wp_parse_str') ) {
			wp_parse_str($checkUrl, $urlParams);
		} else {
			parse_str($checkUrl, $urlParams);
		}

		//Check Feed & Run
		if ( intval($urlParams['libsyn_pploadfeed']) === 1 ) {
			$sanitize = new \Libsyn\Service\Sanitize();

			if ( isset($_GET['pp_url']) ) {//has feed to load
				$ppFeedUrl = $sanitize->url_raw($_GET['pp_url']);
			}

			//Build Feed
			global $wp_rewrite;
			if ( !empty($wp_rewrite->{'feed_base'}) ) {
				$feedBase = $wp_rewrite->{'feed_base'};
			} else {//just default to feed if unknown
				$feedBase = 'feed';
			}
			if ( in_array('podcast', $wp_rewrite->{'feeds'}) && in_array('feed', $wp_rewrite->{'feeds'}) ) {
				if ( !isset($ppFeedUrl) ) {
					$ppFeedUrl = get_site_url() . "/{$feedBase}/podcast";
				}
			} else {
				if ( !isset($ppFeedUrl) ) {
					$ppFeedUrl = get_site_url() . "/{$feedBase}";
				}
			}
			if ( !empty($ppFeedUrl) ) {
				self::safe_set_time_limit(5 * 60);
				if( function_exists('fetch_feed') ) {
					$powerpressFeed = fetch_feed( $ppFeedUrl );
				} else {
					$powerpressFeed = self::libsyn_fetch_feed( $ppFeedUrl );
				}
			}

			if ( !is_wp_error($powerpressFeed) && $powerpressFeed instanceof \SimplePie ) {

				//build sudo screen of importer page for args
				$screen = convert_to_screen(null);
				if ( !empty($screen) && $screen instanceof \WP_Screen) {//check sanity
					$screen->base			= 'libsyn-podcasting/admin/imports';
					$screen->id				= 'libsyn-podcasting/admin/imports';
					$screen->parent_base	= 'libsyn-podcasting/admin/settings';
					$screen->parent_file	= 'libsyn-podcasting/admin/settings.php';
				} else {
					$screen = null;
				}

				$feed_args = array(
					'singular'=> 'libsyn_feed_item' //Singular label
					,'plural' => 'libsyn_feed_items' //plural label, also this well be one of the table css class
					,'ajax'   => false //We won't support Ajax for this table
					,'screen' => $screen
				);

				$plugin = new \Libsyn\Service();

				//Hackish fix to make ajax table first pagination page load on imports page
				//TODO: look into further how to use WP_List_Table and pass a different url base to the pagination url
				if ( !empty($_GET['pp_url']) ) {
					if ( function_exists('urlencode_deep') ) {
						$ppUrlParam = '&amp;pp_url=' . urlencode_deep($_GET['pp_url']);
					} else {
						$ppUrlParam = '&amp;pp_url=' . urlencode($_GET['pp_url']);
					}
				} else {
					$ppUrlParam = '';
				}
				$current_url = $plugin->admin_url() . '?action=libsyn_pploadfeed&amp;libsyn_pploadfeed=1' . $ppUrlParam;
				$imports_url = $plugin->admin_url() . '?page=LibsynImports';

				//setup new array with feed data
				$powerpress_feed_items = array();
				$x=0;
				foreach ($powerpressFeed->get_items() as $feed_item) {
					$working_url = $feed_item->get_permalink();

					if ( function_exists('url_to_postid') ) {
						$id = url_to_postid($working_url);
					}

					$powerpress_feed_items[$x] = new \stdClass();

					if ( !empty($id) ) {
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
					if ( isset($id) ) unset($id);
				}

				//Save PP Feed to Meta
				$working_powerpressFeed = new \stdClass();
				$working_powerpressFeed->{'feed_url'} = $ppFeedUrl;
				$working_powerpressFeed->{'items'} = $powerpress_feed_items;
				update_user_option($current_user_id, 'libsyn-podcasting-pp_feed', $working_powerpressFeed, false);
				update_user_option($current_user_id, 'libsyn-podcasting-pp_feed_url', $ppFeedUrl, false);
				unset($working_powerpressFeed);

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
			} elseif ( !is_wp_error($powerpressFeed) && $powerpressFeed instanceof __PHP_Incomplete_Class) {
				$msg = "It appears that the Powerpress Feed URL we are trying to import is invalid.  You can check your settings or try to import manually below.";
				if ( $plugin->hasLogger ) $plugin->logger->error("Importer:\t".$msg);
				$error = true;
				$checkPowerpress = false;
			} elseif ( is_wp_error($powerpressFeed) ) {
				if(!empty($powerpressFeed->{'errors'}) && !empty($powerpressFeed->{'errors'}['simplepie-error'][0])) {
						$msg = "Feed Reader Error:\t" . $powerpressFeed->{'errors'}['simplepie-error'][0];
				} else {
						$msg = "Your Powerpress feed cannot be read by the importer.  The feed may be invalid.";
				}
				if ( $plugin->hasLogger ) $plugin->logger->error("Importer:\t".$msg);
				$error = true;
			} else {
				$msg = "Something went wrong when trying to read your Powerpress Feed.  Or you can check your settings or try entering your Feed URL manually below.";
				if ( $plugin->hasLogger ) $plugin->logger->error("Importer:\t".$msg);
				$error = true;
				$checkPowerpress = false;
			}

			//set output
			if ( !empty($libsyn_feed_wp_list_table) ) {
				if( !empty($imports_url) && !empty($current_url) ) {
					echo str_replace($current_url, $imports_url, $libsyn_feed_wp_list_table->display());
				} else {
					echo $libsyn_feed_wp_list_table->display();
				}
			}

			//Exit out
			add_action( 'wp_feed_options', 'Libsyn\\Utilities::enableFeedCaching' );
			exit;
		}
	}

    /**
     * Gets Zend dispatch params from a Url String
	 * Useful for extracting data from Libsyn Trafficker Urls
     *
	 * @since 1.0.1.6
     * @param string $url
     *
     * @return array html params
     */
	public static function getDispatch( $url ) {
		// Split the URL into its constituent parts.
		if ( function_exists('wp_parse_url') ) {
			$parse = wp_parse_url($url);
		} else {
			$parse = parse_url($url);
		}

		// Remove the leading forward slash, if there is one.
		$path = ltrim($parse['path'], '/');

		// Put each element into an array.
		$elements = explode('/', $path);

		// Create a new empty array.
		$params = array();

		// Loop through each pair of elements.
		for( $i = 0; $i < count($elements); $i = $i + 2) {
			if ( !empty($elements[$i + 1]) ) {
				$params[$elements[$i]] = $elements[$i + 1];
			}
		}

		return $params;
	}

	/**
	 * Clears Settings and deletes table for uninstall
	 *
	 * @since 1.0.1.1
	 * @return bool
	 */
	public static function uninstallSettings() {
		global $wpdb;
		try {
			self::deactivateSettings();
			$option_names = self::getUserOptionKeys();
			$plugin = new \Libsyn\Service();
			$api_table_name = $plugin->getApiTableName();
			$option_names[] = $api_table_name;
			$current_user_id = get_current_user_id();

			foreach($option_names as $option) {
				// Delete option (Normal WP Setup)
				if ( !delete_option( $option ) ) {
					//user may not have delete privs on database
					update_option( $option, array() ); //fill with empty array
					update_user_option( $current_user_id, $option, array(), false ); //fill with empty array
					if($plugin->hasLogger) $plugin->logger->info("Utilities::uninstallSettings:\tRemoving Option:\t" . $option);
				}
				// For site options in (Multisite WP Setup)
				if ( !delete_site_option( $option ) && is_multisite() ) {
					//user may not have delete privs on database
					update_site_option($option, array()); //fill with empty array
					if ( $plugin->hasLogger ) $plugin->logger->info("Utilities::uninstallSettings:\tClearing Option:\t" . $option);
				}
			}
		} catch( Exception $e ) {
			if ( $plugin->hasLogger ) $plugin->logger->error("Utilities::uninstallSettings:\t" . $e);
			return false;
		}

		//drop libsyn db table
		if ( !empty($api_table_name) ) {
			try {
				$wpdb->query( "DROP TABLE IF EXISTS ".$api_table_name ); //old without prefix
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}".$api_table_name );
			} catch( Exception $e ) {
				if ( $plugin->hasLogger ) $plugin->logger->error("Utilities::uninstallSettings:\t" . $e);
				return false;
			}
		}
		return true;
	}

	/**
	 * Clears Settings and deletes table for uninstall
	 *
	 * @since 1.0.1.1
	 * @return void
	 */
	public static function deactivateSettings() {
		try {
			//clear settings first
			$plugin = new \Libsyn\Service();
			$api_table_name = $plugin->getApiTableName();
			$user_id = get_current_user_id();

			//empty settings
			$dataSettings = array(
				'user_id'				=> $user_id,
				'access_token'			=> null,
				'refresh_token'			=> null,
				'refresh_token_expires'	=> null,
				'access_token_expires'	=> null,
				'show_id'				=> null,
				'plugin_api_id'			=> null,
				'client_id'				=> null,
				'client_secret'			=> null,
				'is_active'				=> 0
			);

			if ( function_exists('delete_user_option') ) {
				if(!delete_user_option($user_id, $api_table_name, false)) {
					update_user_option($user_id, $api_table_name, json_encode($dataSettings));
					if ( $plugin->hasLogger ) $plugin->logger->info("Utilities::deactivateSettings:\tClearing API Settings");
				}
			} elseif ( function_exists('update_user_option') ) {
				update_user_option($user_id, $api_table_name, json_encode($dataSettings));
				if ( $plugin->hasLogger ) $plugin->logger->info("Utilities::deactivateSettings:\tClearing API Settings");
			} else {
				$deactivate = false;
				if ( $plugin->hasLogger ) $plugin->logger->error("Utilities::deactivateSettings:\tUnknown Error Occured");
			}
		} catch(Exception $e) {
			if ( $plugin->hasLogger ) $plugin->logger->error("Utilities::uninstallSettings:\t" . $e);
		}
		return;
	}

    /**
     * Simple function to check if a string is Json
     *
	 * @since 1.0.1.1
     * @param <string> $json_string
     *
     * @return bool
     */
	public function isJson($json_string) {
		return ( !empty($json_string) && ( is_string($json_string) && ( is_object(json_decode($json_string)) || is_array(json_decode($json_string, true)) ) ) ) ? true : false;
	}

	/**
	 * Gets the current page url
	 *
	 * @since 1.0.1.1
	 * @return string
	 */
	public static function getCurrentPageUrl() {
		global $wp;
		return add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) );
	}

	/**
	 * Takes an __PHP_Incomplete_Class and casts it to a stdClass object.
	 * All properties will be made public in this step.
	 *
	 * @since  1.0.1.6
	 * @param  object $object __PHP_Incomplete_Class
	 *
	 * @return object
	 */
	function fixObject( $object ) {
		// preg_replace_callback handler. Needed to calculate new key-length.
		$fix_key = create_function(
			'$matches',
			'return ":" . strlen( $matches[1] ) . ":\"" . $matches[1] . "\"";'
		);

		// 1. Serialize the object to a string.
		$dump = serialize( $object );

		// 2. Change class-type to 'stdClass'.
		$dump = preg_replace( '/^O:\d+:"[^"]++"/', 'O:8:"stdClass"', $dump );

		// 3. Make private and protected properties public.
		$dump = preg_replace_callback( '/:\d+:"\0.*?\0([^"]+)"/', $fix_key, $dump );

		// 4. Unserialize the modified object again.
		return unserialize( $dump );
	}

    /**
     * Logs list of plugins for use with downloading the debug log
     * Also Logs useful infomation like the WP Version, Theme, Classic Editor Option
     *
     * @since 1.0.1.4
	 *
     * @return void
     */
	public static function logPluginData() {
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		parse_str($checkUrl, $urlParams);
		if ( intval($urlParams['libsyn_debuginfo']) === 1 ) {
			$plugin = new \Libsyn\Service();
			/* Export list of installed plugins */
			if ( !function_exists( 'get_plugins' ) ) {
				if ( file_exists(ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php') ) {
					require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
				} elseif ( file_exists(ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'plugin.php') ) {
					require_once ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'plugin.php';
				} elseif ( file_exists(realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php') ) {
					require_once realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
				}
			}

			if ( function_exists('get_bloginfo') ) {
				try {
					global $wp_version;
					$line = '';
					if ( $plugin->logger ) $plugin->logger->info("Plugins:\tGeneral Wordpress Information.");
					$line .= "\n\t\tWordpress Version:\t".get_bloginfo('version');
					$line .= "\n\t\tSite Address:\t".get_bloginfo('url');
					if ( self::is_classic_editor_plugin_active() ) {
						$line .= "\n\t\tEditor Type:\tClassic";
					} elseif ( self::is_gutenberg_editor_active() ) {
						$line .= "\n\t\tEditor Type:\tGutenberg";
					}
					if ( $plugin->logger ) $plugin->logger->info($line);
					if ( isset($line) ) unset($line);
				} catch ( Exception $e ) {
					//TODO: log error
				}
			}

			if ( function_exists('get_plugins') ) {
				try {
					$all_plugins = get_plugins();
					$active_plugins = get_option('active_plugins');
					if ( is_array($all_plugins) ) {
						if ( $plugin->logger ) $plugin->logger->info("Plugins:\tGenerating list of installed plugins.");
						foreach($all_plugins as $pluginName => $pluginInfo){
							if ( !empty($pluginName) ) {
								$line = "Plugins:\t".$pluginName;
								if ( !empty($pluginInfo['Name']) ) $line .= "\n\t\tName:\t".$pluginInfo['Name'];
								if ( !empty($pluginInfo['PluginURI']) ) $line .= "\n\t\tURI:\t".$pluginInfo['PluginURI'];
								if ( !empty($pluginInfo['Version']) ) $line .= "\n\t\tVersion:\t".$pluginInfo['Version'];
								if ( isset($active_plugins[$pluginName]) ) {
									$line .= "\n\t\tActive:\tTRUE";
								} else {
									$line .= "\n\t\tActive:\tFALSE";
								}
								if ( !empty($pluginInfo['Network']) ) {
									$line .= "\n\t\tNetwork:\tTRUE";
								} else {
									$line .= "\n\t\tNetwork:\tFALSE";
								}
								if ( $plugin->logger ) $plugin->logger->info($line);
							}
							if ( isset($line) ) unset($line);
						}
					}
				} catch ( Exception $e ) {
					//TODO: log error
				}
			}

			if ( function_exists('wp_get_theme') ) {
				try {
					$active_theme = wp_get_theme();
					$line = '';
					if ( $plugin->logger ) $plugin->logger->info("Plugins:\tActive Theme Information.");
					if ( !empty($active_theme->get('Name')) ) $line .= "\n\t\tName:\t".$active_theme->get('Name');
					if ( !empty($active_theme->get('Version')) ) $line .= "\n\t\tVersion:\t".$active_theme->get('Version');
					if ( !empty($active_theme->get('Author')) ) $line .= "\n\t\tAuthor:\t".$active_theme->get('Author');
					if ( !empty($active_theme->get('AuthorURI')) ) $line .= "\n\t\tAuthorURI:\t".$active_theme->get('AuthorURI');
					if ( $plugin->logger ) $plugin->logger->info($line);
					if ( isset($line) ) unset($line);
				} catch ( Exception $e ) {
					//TODO: log error
				}
			}

			//set output
			header('Content-Type: application/json');
			echo json_encode('success');
			exit;
		}
	}

	/**
	 * Gets a Libsyn Embed URL based off of Usermeta settings
	 * @example "https://html5-player.libsyn.com/embed/episode/id/10049438/height/90/theme/custom/thumbnail/yes/direction/forward/render-playlist/no/custom-color/bfd66e/" Url markup for return
	 *
	 * @since 1.2.2.2
	 * @param  int $current_post_id Current WP Post Id
	 * @return mixed         Embed Url | false on error
	 */
	public static function getEmbedUrlGeneric( $current_post_id ) {
		if ( empty($current_post_id) ) return false;
		$item_id = get_post_meta($current_post_id, 'libsyn-item-id', true);
		if ( empty($item_id) ) return false;

		$playerSettings = array();
		$playerSettings['theme'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_use_theme', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_use_theme', true) : get_user_option('libsyn-podcasting-player_use_theme');
		$playerSettings['height'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_height', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_height', true) : get_user_option('libsyn-podcasting-player_height');
		$playerSettings['player_placement'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_placement', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_placement', true) : get_user_option('libsyn-podcasting-player_placement');
		$playerSettings['player_use_download_link'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_use_download_link', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_use_download_link', true) : get_user_option('libsyn-podcasting-player_use_download_link');
		$playerSettings['player_use_download_link_text'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_use_download_link_text', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_use_download_link_text', true) : get_user_option('libsyn-podcasting-player_use_download_link_text');
		$playerSettings['custom-color'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_custom_color', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_custom_color', true) : get_user_option('libsyn-podcasting-player_custom_color');
		$playerSettings['thumbnail'] = ( !empty(get_post_meta($current_post_id, 'libsyn-podcasting-player_use_thumbnail', true)) ) ? get_post_meta($current_post_id, 'libsyn-podcasting-player_use_thumbnail', true) : get_user_option('libsyn-podcasting-player_use_thumbnail');
		$playerSettings['thumbnail'] = ( !empty($playerSettings['thumbnail']) ) ? 'yes' : 'no';
		$playerSettings['direction'] = 'forward';
		$playerSettings['render-playlist'] = 'no';

		foreach ( $playerSettings as &$row ) {
			if ( empty($row) ) unset($row);
		}
		if ( isset($row) ) unset($row);
		return 'https://html5-player.libsyn.com/embed/episode/id/' . $item_id . '/' . str_replace('=', '/', str_replace('&', '/', http_build_query($playerSettings) ) );
	}


	/*                          */
	/* General Helper Functions */
	/*                          */

    /**
     * Attempts to set the php time limit without causing errors
	 * Useful for increasing timeouts for feed import jobs
     *
	 * @since 1.0.1.6
     * @param int $timeLimit  (seconds)
     *
     * @return void
     */
	public function safe_set_time_limit( $timeLimit = 60) {
		try {

			//Set Regular PHP Time Limit
			if ( function_exists('set_time_limit') && is_numeric($timeLimit) ) {
				@set_time_limit(intval($timeLimit));
			}

			//Set HTTP Request Timeout
			add_filter('http_request_args', '\Libsyn\Utilities::libsyn_http_request_args', 100, 1);

			//Setting WP HTTP API Timeout
			add_action('http_api_curl', '\Libsyn\Utilities::libsyn_http_api_curl', 100, 1);

			// Setting custom timeout for the HTTP request
			add_filter('http_request_timeout', '\Libsyn\Utilities::libsyn_custom_http_request_timeout', 101 );

		} catch (Exception $e) {
			//do nothing
		}
	}

    /**
     * Handles incrasing WP HTTP API Timeout
	 * ( Defaults to 60 Seconds )
	 *
     * @since 1.0.1.6
     * @param mixed $r
     *
     * @return void
     */
	public static function libsyn_http_request_args( $r ) {
		$r['timeout'] = 60;
		return $r;
	}

    /**
     * Handles increasing Curl Timeout
	 * ( Defaults to 60 Seconds )
	 *
     * @since 1.0.1.6
     * @param mixed $handle
     *
     * @return void
     */
	public static function libsyn_http_api_curl( $handle ) {
		curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt( $handle, CURLOPT_TIMEOUT, 60 );
	}

    /**
     * Handles increasing WP HTTP API Timeout
	 * ( Defaults to 60 Seconds )
     *
     * @param int $timeLimit
     *
     * @return void
     */
	public static function libsyn_custom_http_request_timeout( $timeLimit ) {
		return 60;
	}

    /**
     * Libsyn Feed Reader based on WP fetch_feed
	 * See also {@link https://core.trac.wordpress.org/browser/tags/5.0/src/wp-includes/feed.php}
	 *
	 * Build SimplePie object based on RSS or Atom feed from URL.
	 *
	 * @since 1.0.1.3
     *
     * @param mixed $url URL of feed to retrieve. If an array of URLs, the feeds are merged
	 * using SimplePie's multifeed feature.
	 * See also {@link ​http://simplepie.org/wiki/faq/typical_multifeed_gotchas}
     *
     * @return WP_Error|SimplePie WP_Error object on failure or SimplePie object on success
     */
	public function libsyn_fetch_feed( $url ) {
		if ( ! class_exists('\SimplePie', false) ) {
			require_once( ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'class-simplepie.php' );
		}

		require_once( ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'class-wp-feed-cache.php' );
		require_once( ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'class-wp-feed-cache-transient.php' );
		require_once( ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'class-wp-simplepie-file.php' );
		require_once( ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'class-wp-simplepie-sanitize-kses.php' );

		$feed = new \SimplePie();

		$feed->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );
		// We must manually overwrite $feed->sanitize because SimplePie's
		// constructor sets it before we have a chance to set the sanitization class
		$feed->sanitize = new \WP_SimplePie_Sanitize_KSES();

		/* Customize sanitization */
		$feed->sanitize->enable_cache = false;
		// $feed->sanitize->timeout = 60;
		$feed->sanitize->useragent = "Libsyn Publisher Hub FeedReader";

		$feed->set_cache_class( 'WP_Feed_Cache' );
		$feed->set_file_class( 'WP_SimplePie_File' );

		$feed->set_feed_url( $url );
		// $feed->set_timeout( 30 );
		// $feed->cache = false;
		// $feed->force_feed = true; //unsure what this does
		// $feed->input_encoding = false; //default

		/** This filter is documented in wp-includes/class-wp-feed-cache-transient.php */
		// $feed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', 60, $url ) ); //changing cache time to 60 seconds (instead of 12 hours)
		/**
		 * Fires just before processing the SimplePie feed object.
		 *
		 * @since 3.0.0
		 *
		 * @param object $feed SimplePie feed object (passed by reference).
		 * @param mixed  $url  URL of feed to retrieve. If an array of URLs, the feeds are merged.
		 */
		do_action_ref_array( 'wp_feed_options', array( &$feed, $url ) );
		$feed->init();
		$feed->set_output_encoding( get_option( 'blog_charset' ) );
		// $feed->set_output_encoding( "UTF-8" ); //set statically to UTF-8
		if ( $feed->error() )
			return new \WP_Error( 'simplepie-error', $feed->error() );

		return $feed;
	}

    /**
     * Parses phpinfo into usable information format
     *
	 * @since 1.0.1.1
	 *
     * @return mixed
     */
	private static function parse_phpinfo() {
		ob_start(); phpinfo(INFO_MODULES); $s = ob_get_contents(); ob_end_clean();
		$s = strip_tags($s, '<h2><th><td>');
		$s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
		$s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
		$t = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
		$r = array(); $count = count($t);
		$p1 = '<info>([^<]+)<\/info>';
		$p2 = '/'.$p1.'\s*'.$p1.'\s*'.$p1.'/';
		$p3 = '/'.$p1.'\s*'.$p1.'/';
		for ($i = 1; $i < $count; $i++) {
			if ( preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs) ) {
				$name = trim($matchs[1]);
				$vals = explode("\n", $t[$i + 1]);
				foreach ($vals AS $val) {
					if ( preg_match($p2, $val, $matchs) ) { // 3cols
						$r[$name][trim($matchs[1])] = array(trim($matchs[2]), trim($matchs[3]));
					} elseif ( preg_match($p3, $val, $matchs) ) { // 2cols
						$r[$name][trim($matchs[1])] = trim($matchs[2]);
					}
				}
			}
		}
		return $r;
	}

	/**
	 * function will chmod dirs and files recursively
	 *
	 * @since 1.0.1.1
	 * @param type $start_dir
	 * @param type $debug (set false if you don't want the function to echo)
	 *
	 * @return void
	 */
	public static function chmod_recursive($start_dir, $debug = false) {
		$dir_perms = 0755;
		$file_perms = 0644;
		$str = "";
		$files = array();
		if ( is_dir($start_dir) ) {
			$fh = opendir($start_dir);
			while ( ($file = readdir($fh)) !== false ) {
				// skip hidden files and dirs and recursing if necessary
				if ( strpos($file, '.')=== 0 ) continue;
				$filepath = $start_dir . DIRECTORY_SEPARATOR . $file;
				if ( is_dir($filepath) ) {
					@chmod($filepath, $dir_perms);
					self::chmod_recursive($filepath);
				} else {
					@chmod($filepath, $file_perms);
				}
			}
			closedir($fh);
		}
		if ( $debug ) {
			echo $str;
		}
	}

    /**
     * Simple function returns a pretty-print array
     *
	 * @since 1.0.1.1
     * @param array $arr
     *
     * @return string
     */
	public static function pretty_print_array($arr){
		$retStr = '<ul>';
		if ( is_array($arr) ){
			foreach ($arr as $key=>$val){
				if ( is_array($val) ){
					$retStr .= '<li>' . $key . ' => ' . self::pretty_print_array($val) . '</li>';
				} else {
					$retStr .= '<li>' . $key . ' => ' . $val . '</li>';
				}
			}
		}
		$retStr .= '</ul>';
		return $retStr;
	}

    /**
     * Gets the id from Wordpress by the guid
     *
     * @param string|int $guid
     *
     * @return mixed
     */
	public function get_id_from_guid( $guid ){
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid ) );
	}


	/**
	 * Check if Block Editor is active.
	 * Must only be used after plugins_loaded action is fired.
	 *
	 * @since 1.2.1
	 *
	 * @return bool
	 */
	public static function is_gutenberg_editor_active() {
		// Gutenberg plugin is installed and activated.
		$gutenberg = ! ( false === has_filter( 'replace_editor', 'gutenberg_init' ) );

		// Block editor since 5.0.
		$block_editor = version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' );

		if ( ! $gutenberg && ! $block_editor ) {
			return false;
		}

		if ( self::is_classic_editor_plugin_active() ) {
			$editor_option       = get_option( 'classic-editor-replace' );
			$block_editor_active = array( 'no-replace', 'block' );

			return in_array( $editor_option, $block_editor_active, true );
		}

		return true;
	}

	/**
	 * Check if Classic Editor plugin is active.
	 *
	 * @since 1.2.1
	 *
	 * @return bool
	 */
	public static function is_classic_editor_plugin_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
		}

		if ( is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
			return true;
		}

		return false;
	}
}

?>
