<?php
/*
Plugin Name: Libsyn Publisher Hub
Plugin URI: https://wordpress.org/plugins/libsyn-podcasting/
Description: Post or edit Libsyn Podcast episodes directly through Wordpress.
Version: 1.2.2.6
Author: Libsyn
Author URI: https://support.libsyn.com/kb/libsyn-publisher-hub/
License: GPLv3
*/

define("LIBSYN_NS", "libsynmodule_");
define("LIBSYN_PLUGIN_ROOT", dirname(__FILE__));
define("LIBSYN_DIR", basename(LIBSYN_PLUGIN_ROOT));
define("LIBSYN_ADMIN_DIR", basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "admin" . DIRECTORY_SEPARATOR);
define("LIBSYN_TEXT_DOMAIN", "libsyn-podcasting");

//include plugin.php to run is_plugin_active() check
if(file_exists(ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php')) {
	include_once( ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php' );
}



//if plugin is active declare plugin
if( is_plugin_active(LIBSYN_DIR . DIRECTORY_SEPARATOR . LIBSYN_DIR.'.php') ) {

	/**
	 * Invoke LPH functions
	 * Includes actions and filters and other global properties
	 * such as Oembed support and Player shortcode support
	 *
	 * @since 1.0.1.6
	 */
	if ( file_exists(plugin_dir_path( __FILE__ ) . 'admin' . DIRECTORY_SEPARATOR . 'functions.php') ) {
		require_once(plugin_dir_path( __FILE__ ) . 'admin' . DIRECTORY_SEPARATOR . 'functions.php');
	}

	//$libsyn_admin_includes = build_libsyn_includes('admin'); //NOTE: may be able to use this in the future but it is not working on php 5.3
	//global $libsyn_admin_includes;
	global $libsyn_notifications;

	foreach(build_libsyn_includes_original('admin') as $include) {
		libsyn_include_file($include);
	}

	//make sure include scripts are readable
	foreach(build_libsyn_include_scripts('admin') as $include) {
		if(file_exists($include)) {
			$is_readable = is_readable($include);
			if($is_readable) {
				//Do nothing.. looks good
			} else { //one or more files unreadable
				$data = array(
				  'status' => 'error'
				);

				//attempt to make writable.
				@chmod($include, 0777);

				//check again
				if(!is_readable($include)) {
					$libsyn_notifications->add('file-unreadable', __('File not readable for the Libsyn Publisher Hub. ', $libsyn_text_dom) . '<em>'.$include.'</em><span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">' . __('Please contact your server Administrator or get ', $libsyn_text_dom) . '<a href="https://codex.wordpress.org/Changing_File_Permissions" target=\"_blank\">' . __('Help Changing File Permissions', $libsyn_text_dom) . '</a>', $data);
					if(empty($readableErrors)) {
						$readableErrors = new WP_Error('libsyn-podcasting', $include . __(' file is not readable and required for the Libsyn Publisher Hub.', $libsyn_text_dom));
					} else {
						$readableErrors->add('libsyn-podcasting', $include . __(' file is not readable and required for the Libsyn Publisher Hub.', $libsyn_text_dom));
					}
				}
			}
		} else {
			$libsyn_notifications->add('file-missing', __('File is missing and requied for the Libsyn Publisher Hub. ', $libsyn_text_dom) . '<em>'.$include.'</em><span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">' . __('Please contact your server Administrator or try ', $libsyn_text_dom) . '<a href="https://codex.wordpress.org/Managing_Plugins" target=\"_blank\">'. __('Manually Installing Plugins.', $libsyn_text_dom) . '</a>', $data);
			if(empty($readableErrors)) {
				$readableErrors = new WP_Error('libsyn-podcasting', $include . __(' file is missing and required for the Libsyn Publisher Hub.', $libsyn_text_dom));
			} else {
				$readableErrors->add('libsyn-podcasting', $include . __(' file is missing and required for the Libsyn Publisher Hub.', $libsyn_text_dom));
			}
		}
	}

	/* Declare Plugin */
	$plugin = new \Libsyn\Service();
	//check for Logger
	$checkRecommendedPhpVersion = \Libsyn\Service\Integration::getInstance()->checkRecommendedPhpVersion();
	if($checkRecommendedPhpVersion){
		foreach(build_libsyn_logger_includes('admin') as $include) {
			if(file_exists($include)) {
				require_once($include);
			}
		}
		//redeclare plugin with logger
		$plugin = new \Libsyn\Service();
	} else {
		if($plugin->hasLogger) $plugin->logger->error("Plugin:\t Php version is lower than recommended version");
	}

	//check for classic editor in settings
	if ( function_exists('get_option') ) {
		$utilities = new \Libsyn\Utilities();
		$classic_editor_override = get_option('libsyn-podcasting-settings_use_classic_editor');
		$classic_editor_override = ( !empty($classic_editor_override) && $classic_editor_override == 'use_classic_editor' ) ? true : false;
		$classic_editor_plugin_active = $utilities->is_classic_editor_plugin_active();
		if ( !$classic_editor_override && !$classic_editor_plugin_active ) {
			if( function_exists( 'register_block_type' ) ) {
				add_action( 'init', '\Libsyn\Post\Block::initBlock' );
				add_action( 'enqueue_block_editor_assets', '\Libsyn\Post\Block::addAssets' );
				add_action( 'enqueue_block_assets', '\Libsyn\Post\Block::blockAssets' );
			}
		} else {//classic editor
			add_action( 'add_meta_boxes_post', 'add_libsyn_post_meta');
		}
	} else {
		if($plugin->hasLogger) $plugin->logger->error("Could not load classic or block editor.");
	}

	\Libsyn\Post::actionsAndFilters();
	add_action('save_post', '\Libsyn\Post::handlePost', 100, 2);
	add_filter( 'show_post_locked_dialog', '__return_false' );

	if ( function_exists('libsynActionsAndFilters')	) {
		libsynActionsAndFilters();
	}
}
