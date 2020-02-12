<?php

/* Admin Functions */

/**
 * Oembed Support
 *
 * @since 1.0.1.1
 * @return void
 */
function libsyn_add_oembed_handlers() {
	wp_oembed_add_provider( 'http://html5-player.libsyn.com/*', 'http://oembed.libsyn.com/', false );
}
libsyn_add_oembed_handlers();

/**
 * Create Admin Menu
 *
 * @since 1.0.1.1
 * @return void
 */
function libsyn_plugin_admin_menu() {
	add_menu_page('Libsyn Publisher Hub', 'Libsyn Publisher Hub', 'administrator', 'LibsynSettings', 'libsyn_settings_menu', plugins_url(LIBSYN_DIR . '/lib/images/icon.png' ) );
	add_submenu_page( 'LibsynSettings', 'Settings', 'Settings', 'administrator', 'LibsynSettings', 'libsyn_settings_menu' );
	add_submenu_page( 'LibsynSettings', 'Content', 'Content', 'administrator', 'LibsynContent', 'libsyn_content_menu' );
	add_submenu_page( 'LibsynSettings', 'Import Feed', 'Import Feed', 'administrator', 'LibsynImports', 'libsyn_imports_menu' );
	add_submenu_page( 'LibsynSettings', 'Debug Log', 'Debug', 'administrator', 'LibsynDebugLog', 'libsyn_debug_log_menu' );
}
add_action('admin_menu', 'libsyn_plugin_admin_menu');

/**
* Loads settings Screen
* @since 1.2.2
* @return void
*/
function libsyn_settings_menu() {
	$pluginDir = plugin_dir_path( __DIR__ );
	require_once( $pluginDir . 'admin' . DIRECTORY_SEPARATOR . 'settings.php' );
}
/**
* Loads Content Screen
* @since 1.2.2
* @return void
*/
function libsyn_content_menu() {
	$pluginDir = plugin_dir_path( __DIR__ );
	require_once( $pluginDir . 'admin' . DIRECTORY_SEPARATOR . 'content.php' );
}
/**
* Loads Imports Screen
* @since 1.2.2
* @return void
*/
function libsyn_imports_menu() {
	$pluginDir = plugin_dir_path( __DIR__ );
	require_once( $pluginDir . 'admin' . DIRECTORY_SEPARATOR . 'imports.php' );
}
/**
* Loads Debug Log Screen
* @since 1.2.2
* @return void
*/
function libsyn_debug_log_menu() {
	$pluginDir = plugin_dir_path( __DIR__ );
	require_once( $pluginDir . 'admin' . DIRECTORY_SEPARATOR . 'debug_log.php' );
}
/**
* Loads Support Screen
* @since 1.2.2
* @return void
*/
function libsyn_support_menu() {
	$pluginDir = plugin_dir_path( __DIR__ );
	require_once( $pluginDir . 'admin' . DIRECTORY_SEPARATOR . 'support.php' );
}
/**
* Loads About Screen
* @since 1.2.2
* @return void
*/
function libsyn_about_menu() {
	$pluginDir = plugin_dir_path( __DIR__ );
	require_once( $pluginDir . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'about.php' );
}

/**
 * Add Plugin About Page
 *
 * @since 1.0.1.1
 * @param array $links
 * @param string $file
 *
 * @return void
 */
function libsyn_unqprfx_plugin_meta( $links, $file ) {
	if ( ( function_exists('mb_strpos') && mb_strpos( $file, 'libsyn.php' ) !== false ) || ( strpos( $file, 'libsyn.php' ) !== false ) ) {
		$links = array_merge( $links, array( '<a href="https://support.libsyn.com/kb/libsyn-publisher-hub/" title="Libsyn Wordpress Plugin">' . __('Libsyn') . '</a>' ) );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'libsyn_unqprfx_plugin_meta', 10, 2 );

/**
 * Adds Libsyn Post Meta
 * For use with Wordpress 5.xx (Gutenberg Editor)
 *
 * @since 1.0.1.1
 * @param WP_Post $post
 *
 * @return void
 */
function add_libsyn_post_meta($post) {
	add_meta_box(
		'libsyn-meta-box',
		__( 'Post Episode'),
		'\Libsyn\Post\Classic::addLibsynPostMeta',
		'post',
		'normal',
		'default'
	);
}

/**
 * Creates loader for block editor assets
 *
 * @since 1.2.1
 * @return <type>
 */
function invoke_block_editor_assets() {

	add_action( 'enqueue_block_editor_assets', '\Libsyn\Post\Block::addAssets' );
	add_action( 'enqueue_block_assets', '\Libsyn\Post\Block::blockAssets' );
	add_action( 'init', '\Libsyn\Post\Block::initBlock' );
}

/**
 * This will include the base Libsyn Podcast Plugin classes
 * Note this is currently not being used since it caused problems with
 * some clients' PHP versions
 *
 * @since 1.0.1.1
 * @param string $scope admin is the default
 * @return array
 */
function build_libsyn_includes($scope = 'admin') {
	$classesDir = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
				plugin_dir_path( __FILE__ ) . 'Libsyn/'
			),
		RecursiveIteratorIterator::SELF_FIRST
	);
	   foreach($iterator as $file) {
		if($file->isDir()) {
			$path = $file->getRealpath() ;
			$path2 = PHP_EOL;
			$path3 = $path.$path2;
			$result = end(explode('/', $path3));
			if(str_replace(array("\r\n", "\r", "\n"), "", $result)!=='includes') $classesDir[] = $path;
		}
	}
	$includesArray = array();$libsyn_includes = array();
	foreach($classesDir as $row) foreach (glob($row.'/*.php') as $filename) $includesArray[$filename] = 'include';
	foreach($includesArray as $key => $val) $libsyn_includes[] = $key;
	usort($libsyn_includes, "libsyn_sort_array");
	return array_reverse($libsyn_includes);
}

/**
 * This is the base Libsyn Podcast Plugin classes for include
 *
 * @since 1.0.1.1
 * @param string $scope admin is the default
 * @return array
 */
function build_libsyn_includes_original($scope = 'admin') {
	return array (
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . 'Api.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . 'Post.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Post' . DIRECTORY_SEPARATOR . 'Block.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Post' . DIRECTORY_SEPARATOR . 'Classic.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Cron.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Cron' . DIRECTORY_SEPARATOR . 'ImporterEmailer.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Destination.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Importer.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Integration.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Page.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Playlist.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Sanitize.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Table.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Site.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Playlist.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Utilities.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'PlaylistWidget.php',
	);
}

/**
 * This is the required files for the logger class
 * (Requires PHP version 5.4+ since it uses Traits)
 *
 * @since 1.0.1.1
 * @param string $scope admin is the default
 * @return array
 */
function build_libsyn_logger_includes($scope = 'admin') {
	return array (
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'LoggerInterface.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'AbstractLogger.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'InvalidArgumentException.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'LoggerAwareInterface.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'LoggerAwareTrait.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'LoggerTrait.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'LogLevel.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'NullLogger.php',
		plugin_dir_path( __FILE__ ) . 'lib' . DIRECTORY_SEPARATOR . 'Libsyn' . DIRECTORY_SEPARATOR . '' . 'Service' . DIRECTORY_SEPARATOR . 'Logger.php',
	);
}

/**
 * List of plugin files
 * (Requires PHP version 5.4+ since it uses Traits)
 *
 * @since 1.0.1.1
 * @param string $scope admin is the default
 * @return array
 */
function build_libsyn_include_scripts($scope = 'admin') {
	return array (
		plugin_dir_path( __FILE__ ) . 'config.php',
		plugin_dir_path( __FILE__ ) . 'content.php',
		plugin_dir_path( __FILE__ ) . 'debug_log.php',
		plugin_dir_path( __FILE__ ) . 'playlist.php',
		plugin_dir_path( __FILE__ ) . 'settings.php',
		plugin_dir_path( __FILE__ ) . 'support.php',
		plugin_dir_path( __FILE__ ) . 'views' . DIRECTORY_SEPARATOR . '' . 'box_about.php',
		plugin_dir_path( __FILE__ ) . 'views' . DIRECTORY_SEPARATOR . '' . 'box_clear-settings.php',
		plugin_dir_path( __FILE__ ) . 'views' . DIRECTORY_SEPARATOR . '' . 'box_playersettings.php',
		plugin_dir_path( __FILE__ ) . 'views' . DIRECTORY_SEPARATOR . '' . 'box_support.php',
		plugin_dir_path( __FILE__ ) . 'views' . DIRECTORY_SEPARATOR . 'support' . DIRECTORY_SEPARATOR . '' . 'initial-setup.php',
	);
}

/**
 * Simple sort function
 *
 * @since 1.0.1.1
 * @param array $a
 * @param array $b
 * @return array
 */
function libsyn_sort_array ($a,$b) { return strlen($b)- strlen($a); }

/**
 * Handle Podcast Short Codes
 *
 *
 * @since 1.0.1.1
 * @return void
 */
if ( ! function_exists( 'libsyn_unqprfx_embed_shortcode' ) ) {
	function libsyn_enqueue_script() {
		wp_enqueue_script( 'jquery' );
	}
	add_action( 'wp_enqueue_scripts', 'libsyn_enqueue_script' );

	function libsyn_unqprfx_shortcode_defaults() {
		return array(
			'src' => '',
			'width' => '100%',
			'height' => '200',
			'scrolling' => 'no',
			'class' => 'podcast-class',
			'frameborder' => '0',
			'placement' => 'bottom',
			'use_download_link' => false,
			'download_link_text' => '',
			'primary_content_url' => '',
			'theme' => 'standard',
			'custom_color' => '',
			'libsyn_item_id' => 0
		);
	}

	/* Add iframe shortcode */
	function libsyn_unqprfx_embed_shortcode( $atts, $content = null ) {
		$defaults = libsyn_unqprfx_shortcode_defaults();

		foreach ( $defaults as $default => $value ) { // add defaults
			if ( ! @array_key_exists( $default, $atts ) ) { // hide warning with "@" when no params at all
				$atts[$default] = $value;
			}
		}

		$src_cut = substr( $atts["src"], 0, 35 ); // special case for google maps
		if( strpos( $src_cut, 'maps.google' ) ){
			$atts["src"] .= '&output=embed';
		}

		// get_params_from_url
		if( isset( $atts["get_params_from_url"] ) && ( $atts["get_params_from_url"] == '1' || $atts["get_params_from_url"] == 1 || $atts["get_params_from_url"] == 'true' ) ) {
			if( $_GET != NULL ){
				if( strpos( $atts["src"], '?' ) ){ // if we already have '?' and GET params
					$encode_string = '&';
				}else{
					$encode_string = '?';
				}
				foreach( $_GET as $key => $value ){
					$encode_string .= $key.'='.$value.'&';
				}
			}
			$atts["src"] .= $encode_string;
		}

		$html = '';
		if( isset( $atts["same_height_as"] ) ){
			$same_height_as = $atts["same_height_as"];
		}else{
			$same_height_as = '';
		}

		if( $same_height_as != '' ){
			$atts["same_height_as"] = '';
			if( $same_height_as != 'content' ){ // we are setting the height of the iframe like as target element
				if( $same_height_as == 'document' || $same_height_as == 'window' ){ // remove quotes for window or document selectors
					$target_selector = $same_height_as;
				}else{
					$target_selector = '"' . $same_height_as . '"';
				}
				$html .= '
					<script>
					jQuery(function($){
						var target_height = $(' . $target_selector . ').height();
						$("iframe.' . $atts["class"] . '").height(target_height);
					});
					</script>
				';
			} else { // set the actual height of the iframe (show all content of the iframe without scroll)
				$html .= '
					<script>
					jQuery(function($){
						$("iframe.' . $atts["class"] . '").bind("load", function() {
							var embed_height = $(this).contents().find("body").height();
							$(this).height(embed_height);
						});
					});
					</script>
				';
			}
		}
		$html .= '<iframe style="display:block;" ';
		foreach( $atts as $attr => $value ) {
			if( $attr != 'same_height_as' ){ // remove some attributes
				if( $value != '' ) { // adding all attributes
					$html .= ' ' . $attr . '="' . $value . '"';
				} else { // adding empty attributes
					$html .= '';
				}
			}
		}
		$html .= '></iframe>';

		//do player download link
		// @since 1.2.1
		if ( $atts['use_download_link'] && ( $atts['use_download_link'] == "true" || $atts['use_download_link'] == 1 || $atts['use_download_link'] === true || $atts['use_download_link'] == 'use_download_link' ) ) {
			if ( !empty($atts['primary_content_url']) ) {
				$download_url = $atts['primary_content_url'];
			} else {
				$download_url = $atts['src'];
			}
			if ( !empty($atts['download_link_text']) ) {
				$download_link_text = $atts['download_link_text'];
			} else {
				$download_link_text = 'Click here to download the Episode!';
			}

			$player_download_link = '<br /><a class="libsyn-download-link" href ="' . $download_url . '" target="_blank">' . $download_link_text . '</a><br />';
		} else {
			$player_download_link = '';
		}

		//handle player placement
		if( $atts['placement'] == "top" ){
			$html = $html.$player_download_link."<br />";
		} else {
			$html = "<br />".$html.$player_download_link;
		}

		return $html;
	}
	add_shortcode( 'iframe', 'libsyn_unqprfx_embed_shortcode' );
	add_shortcode( 'podcast', 'libsyn_unqprfx_embed_shortcode' );
}


/**
 * Extends WP_Error for message displays
 *
 * Usage:
 *
 * global $libsyn_notifications;
 *
 *
 * //The notification message.
 * $message = __( 'Congratulations! You have successfully updated your profile.', 'your-textdomain' );
 *
 * //Statuses: error, warning, success, info
 *
 * //Optionally specify a status and an/or an icon.
 * $data = array(
 *   'status' => 'success',
 *   'icon'   => 'thumbs-up'
 * );
 *
 * $libsyn_notifications->add( 'profile-updated', $message, $data );
 *
 * //or pass just status
 * $libsyn_notifications->add( 'profile-not-optimal', $message, 'warning' );
 *
 * @since 1.0.1.1
 */
if ( !class_exists( 'LIBSYN_Notification' ) ) {

	class LIBSYN_Notification extends WP_Error {
		/**
		* HTML Output
		* @var string
		*/
		private $html = '';
		/**
		* Textual key of status
		* @var string
		*/
		private $status = 'error';
		/**
		* Icon key
		* @var string
		*/
		private $icon = '';
		/**
		* HTML container class
		* @var string
		*/
		public $container_class = 'libsyn-podcasting-message';

		/**
		* Initialize the notification.
		*
		*
		* @param string|int $code Error code
		* @param string $message Error message
		* @param mixed $data Optional. Error data.
		* @return WP_Error
		*/
		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( empty($code) ) return;
			$this->add( $code, $message, $data );
		}
		/**
		* Add a notification or append additional message to an existing notification.
		*
		* @param string|int $code Notification code.
		* @param string $message Notification message.
		* @param mixed $data Optional. Notification data.
		*/
		public function add($code, $message, $data = '') {
			$this->errors[$code][] = $message;
			if(!empty($data)) $this->error_data[$code] = $data;
			if(!empty($data)) {
				if(is_array($data) && !empty($data['status'])) {
					//optional pass array of data to handle later i.e. icon (below)
					$this->status = $data['status'];
				} else {
					//default to just setting status
					$this->status = $data;
				}
			}
			if(!empty($data['icon'])) $this->icon = $data['icon']; //icon not supported currently
		}
		/**
		* Build the html string with all the notifications.
		*
		* @return string The html for the notifications.
		*/
		public function build( $container_class = '' ) {
			$html                 = '';
			$status               = $this->status;
			$icon                 = $this->icon;
			$container_class      = ( $container_class ) ? $container_class : $this->container_class;
			foreach ( $this->errors as $code => $message ) {
				$html .= "<div class=\"notice notice-$status is-dismissible $container_class $container_class-$code\">\n";
				$html .= "<p><strong><span style=\"display: block; margin: 0.5em 0.5em 0 0; clear: both;\">";
				$html .= $this->get_error_message( $code ) . "\n";
				$html .= "</span></strong></p>";
				$html .= "<button type=\"button\" class=\"notice-dismiss\"><span class=\"screen-reader-text\">Dismiss this notice.</span></button></div>";
				$html .= "</div>";
			}
			return $html;
		}
		/**
		* Echo html string with all the notifications.
		*
		* @param string $container_class The class for the notification container.
		* @return void        If at least one notification is present, echoes the notifications HTML.
		*/
		public function display( $container_class = '' ) {
			if ( !empty( $this->errors ) ) echo $this->build( $container_class );
		}
	}

	/**
	 * Create an instance of LIBSYN_Notification for site-wide usage.
	 *
	 * @since 1.0.1.1
	 */
	$libsyn_notifications = new LIBSYN_Notification();

	/**
	 * Create an action to display all notifications.
	 *
	 * It is now possibile to display all the registered notifications just
	 * adding do_action('libsyn_notifications') to a page or template file.
	 *
	 * Using the 'libsyn_container_class' filter, it is also possible to change
	 * the default notifications container class.
	 *
	 * @since 1.0.1.1
	 * @param mixed $note A LIBSYN_Notification object. Defaults to the global object.
	 * @return void        If at least one notification is present, echoes the notifications HTML.
	 */
	function libsyn_notifications( $note = '' ) {
		global $libsyn_notifications;
		if ( !$libsyn_notifications ) {
			$libsyn_notifications = $note;
		}
		if ( !is_libsyn_notification( $libsyn_notifications ) ) {
			return false;
		}
		/**
		* Add a filter to change the default notifications container class.
		*/
		$container_class = apply_filters( 'libsyn_container_class', $libsyn_notifications->container_class );
		/**
		* Build and display the notifications.
		*/
		$libsyn_notifications->display( $container_class );
	}
	add_action( 'admin_notices', 'libsyn_notifications', 10, 1 );
	/**
	* Check whether variable is a LIBSYN_Notification Object.
	*
	* This is just an alias of the is_wp_error class.
	* Checking a class returns true even with class extensions.
	*
	* @since 1.0.1.1
	* @uses is_wp_error()
	*
	* @param mixed $note Check if unknown variable is a LIBSYN_Notification or WP_Error object.
	* @return bool True, if LIBSYN_Notification or WP_Error. False, if not LIBSYN_Notification or WP_Error.
	*/
	function is_libsyn_notification( $note ) {
		return is_wp_error( $note );
	}
}

/**
 * Build Libsyn file handler
 *
 * @since 1.0.1.1
 * @param string $include
 *
 * @return void
 */
function libsyn_include_file($include) {
	global $libsyn_notifications;
	if(file_exists($include)) {
		$is_readable = is_readable($include);
		if($is_readable) {
			try {
				require_once($include);
			} catch(Exception $e) {
				throw new Exception('Libsyn Podcast library load error');
			}
		} else { //one or more files unreadable
			$data = array(
			  'status' => 'error'
			);

			//attempt to make writable.
			@chmod($include, 0777);

			//check again
			if(!is_readable($include)) {
				$libsyn_notifications->add('file-unreadable', 'File not readable for the Libsyn Publisher Hub. <em>'.$include.'</em><span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">Please contact your server Administrator or get <a href="https://codex.wordpress.org/Changing_File_Permissions" target=\"_blank\">Help Changing File Permissions</a>', $data);
				if(empty($readableErrors)) {
					$readableErrors = new WP_Error('libsyn-podcasting', $include.' file is not readable and required for the Libsyn Publisher Hub.');
				} else {
					$readableErrors->add('libsyn-podcasting', $include.' file is not readable and required for the Libsyn Publisher Hub.');
				}
			}
		}
	} else {
		$libsyn_notifications->add('file-missing', 'File is missing and requied for the Libsyn Publisher Hub. <em>'.$include.'</em><span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">Please contact your server Administrator or try <a href="https://codex.wordpress.org/Managing_Plugins" target=\"_blank\">Manually Installing Plugins.</a>', $data);
		if(empty($readableErrors)) {
			$readableErrors = new WP_Error('libsyn-podcasting', $include.' file is missing and required for the Libsyn Publisher Hub.');
		} else {
			$readableErrors->add('libsyn-podcasting', $include.' file is missing and required for the Libsyn Publisher Hub.');
		}
	}
}

/**
 * Invoke Libsyn Actions and Filters
 * Used for ajax pages, hooks and various actions and filters
 *
 * @since 1.0.1.6
 *
 * @return void
 */
function libsynActionsAndFilters() {

	//post form ajax
	add_filter('query_vars','Libsyn\\Post::plugin_add_trigger_load_form_data');
	add_action('wp_ajax_load_libsyn_media', 'Libsyn\\Post::loadFormData');
	add_action('wp_ajax_nopriv_load_libsyn_media', 'Libsyn\\Post::loadFormData');

	//post remove ftp unreleased ajax
	add_filter('query_vars','Libsyn\\Post::plugin_add_trigger_remove_ftp_unreleased');
	add_action('wp_ajax_remove_ftp_unreleased', 'Libsyn\\Post::removeFTPUnreleased');
	add_action('wp_ajax_nopriv_remove_ftp_unreleased', 'Libsyn\\Post::removeFTPUnreleased');

	//post add custom postmeta (for block editor)
	add_filter('query_vars','Libsyn\\Post::plugin_add_trigger_update_libsyn_postmeta');
	add_action('wp_ajax_update_libsyn_postmeta', 'Libsyn\\Post::updateLibsynPostmeta');
	add_action('wp_ajax_nopriv_update_libsyn_postmeta', 'Libsyn\\Post::updateLibsynPostmeta');

	//post form player settings dialog ajax
	add_filter('query_vars','Libsyn\\Post::plugin_add_trigger_load_player_settings');
	add_action('wp_ajax_load_player_settings', 'Libsyn\\Post::loadPlayerSettings');
	add_action('wp_ajax_nopriv_load_player_settings', 'Libsyn\\Post::loadPlayerSettings');

	//get player shortcode ajax
	add_filter('query_vars', 'Libsyn\\Post::plugin_add_trigger_libsyn_player_shortcode');
	add_action( 'wp_ajax_libsyn_player_shortcode', 'Libsyn\\Post::getPlayerShortcodeAjax' );
	add_action( 'wp_ajax_nopriv_libsyn_player_shortcode', 'Libsyn\\Post::getPlayerShortcodeAjax' );

	//ajax check
	add_filter('query_vars', 'Libsyn\\Utilities::plugin_add_trigger_libsyn_check_ajax');
	add_action( 'wp_ajax_libsyn_check_url', 'Libsyn\\Utilities::checkAjax' );
	add_action( 'wp_ajax_nopriv_libsyn_check_url', 'Libsyn\\Utilities::checkAjax' );

	//phpinfo debug_log ajax
	add_filter('query_vars', 'Libsyn\\Utilities::plugin_add_trigger_libsyn_phpinfo');
	add_action( 'wp_ajax_libsyn_phpinfo', 'Libsyn\\Utilities::getPhpinfo' );
	add_action( 'wp_ajax_nopriv_libsyn_phpinfo', 'Libsyn\\Utilities::getPhpinfo' );

	//plugins list debug_log ajax
	add_filter('query_vars', 'Libsyn\\Utilities::plugin_add_trigger_libsyn_debuginfo');
	add_action( 'wp_ajax_libsyn_debuginfo', 'Libsyn\\Utilities::logPluginData' );
	add_action( 'wp_ajax_nopriv_libsyn_debuginfo', 'Libsyn\\Utilities::logPluginData' );

	//powerpress feed import loader ajax
	add_filter('query_vars', 'Libsyn\\Utilities::plugin_add_trigger_libsyn_pploadfeed');
	add_action( 'wp_ajax_libsyn_pploadfeed', 'Libsyn\\Utilities::loadPPFeed' );
	add_action( 'wp_ajax_nopriv_libsyn_pploadfeed', 'Libsyn\\Utilities::loadPPFeed' );

	//oauth settings save
	add_filter('query_vars', 'Libsyn\\Utilities::plugin_add_trigger_libsyn_oauth_settings');
	add_action( 'wp_ajax_libsyn_oauth_settings', 'Libsyn\\Utilities::saveOauthSettings' );
	add_action( 'wp_ajax_nopriv_libsyn_oauth_settings', 'Libsyn\\Utilities::saveOauthSettings' );

	//clear settings
	add_filter('query_vars', 'Libsyn\\Utilities::plugin_add_trigger_libsyn_update_oauth_settings');
	add_action( 'wp_ajax_libsyn_update_oauth_settings', 'Libsyn\\Utilities::updateOauthSettings' );
	add_action( 'wp_ajax_nopriv_libsyn_update_oauth_settings', 'Libsyn\\Utilities::updateOauthSettings' );

	/* Add Uninstall Hook */
	register_uninstall_hook( LIBSYN_PLUGIN_ROOT . DIRECTORY_SEPARATOR . LIBSYN_DIR . ".php", 'Libsyn\\Utilities::uninstallSettings');
	register_deactivation_hook( LIBSYN_PLUGIN_ROOT . DIRECTORY_SEPARATOR . LIBSYN_DIR . ".php", 'Libsyn\\Utilities::uninstallSettings');

	//shortcode embedding
	add_action('save_post', '\Libsyn\Playlist::playlistInit', 10, 2);
	add_shortcode( 'libsyn-playlist', '\Libsyn\Playlist::embedShortcode' );

	//setup cron schedules
	$cron = new \Libsyn\Service\Cron();
	add_action('libsyn_cron_importeremailer', 'Libsyn\Service\Cron\ImporterEmailer::ImporterEmailer');//ImporterEmailer Job

	//setup meta values
	add_action('wp_head', 'Libsyn\Service\Page::addMeta');

	/* Add Meta Links */
	add_filter( 'plugin_action_links_' . plugin_basename( LIBSYN_PLUGIN_ROOT . DIRECTORY_SEPARATOR . LIBSYN_DIR . ".php" ), 'libsyn_add_plugin_action_links' );
	function libsyn_add_plugin_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=LibsynSettings">Settings</a>',
			),
			$links
		);
	}


	// add custom widget to dashboard
	add_action('wp_dashboard_setup', 'libsyn_dashboard_widgets');
}

function libsyn_dashboard_widgets() {
	$plugin = new \Libsyn\Service();
	$sanitize = new \Libsyn\Service\Sanitize();
	$current_user_id = $plugin->getCurrentUserId();
	$api = $plugin->retrieveApiById($current_user_id, true);
	// Only call the stats widget if we have a show selected
	if ( $api !== false ) {
		try {
			$showId = $api->getShowId();
			if ( !empty( $showId ) ) {
				$show = $plugin->getShow( $api, $api->getShowId() );
				if ( !empty($show) ) {
					$showTitle = $show->{'user-shows'}->show_title;
				    wp_add_dashboard_widget('libsyn_stats_summary', __('Libsyn Publisher Hub Stats for ' . $showTitle, 'libsyn-podcasting'),  'display_libsyn_stats_summary');
				}
			}
		} catch ( Exception $e ) {
			//TODO: Log error
		}
	}
}

function display_libsyn_stats_summary() {
	$plugin = new \Libsyn\Service();
	$sanitize = new \Libsyn\Service\Sanitize();
	$current_user_id = $plugin->getCurrentUserId();
	$api = $plugin->retrieveApiById($current_user_id, true);
	try {
		$statsShowSummary = $plugin->getStatsShowSummary( $api, $api->getShowId() );

		if ( !empty( $statsShowSummary ) ) {
		    $totalShowDownloads = number_format( $statsShowSummary->stats_show_summary->total_show_downloads );
		}

	    if ( !empty( $totalShowDownloads ) ) {
	    	$statsShowThreeMonth = $plugin->getStatsShowThreeMonth($api, $api->getShowId());
			if ( !empty($statsShowThreeMonth) && !empty($statsShowThreeMonth->stats_show_three_month) ) {
				$monthArr = array_reverse( $statsShowThreeMonth->stats_show_three_month->show_three_month );
			} else {
				$monthArr = array();
			}

	    	$displayShowMonthArr = array();
	    	foreach ( $monthArr as $monthObj ) {
	    		$displayShowMonthArr[] = array(
	    			'month' => DateTime::createFromFormat( '!m', substr( $monthObj->month, 5) )->format('M'),
	    			'downloads' => number_format( $monthObj->downloads )
	    		);
	    	}

	    	echo '<table class="form-table">
									<tr>
										<th>' . __('Total Show Downloads', 'libsyn-podcasting') . '</th>
										<td></td>
									</tr>
									<tr>
										<th colspan=2>
											<table class="form-table">
												<tr>
													<th>' . date( 'M', strtotime( ' -2 months' ) ) . '</th>
													<th>' . date( 'M', strtotime( ' -1 months' ) ) . '</th>
													<th>' . date( 'M' )  . '</th>
													<th>' . __('All Time', 'libsyn-podcasting') . '</th>
												</tr>
												<tr>
													<th>' . displayStatsIfPresent( $displayShowMonthArr, 0, 'downloads') . '</th>
													<th>' . displayStatsIfPresent( $displayShowMonthArr, 1, 'downloads') . '</th>
													<th>' . displayStatsIfPresent( $displayShowMonthArr, 2, 'downloads') . '</th>
													<th>' . $totalShowDownloads . '</th>
												</tr>
											</table>
										</th>
									</tr>
									<tr>
										<th' . __('Most Recent Episodes', 'libsyn-podcasting') . '</th>
										<td></td>
									</tr>';
			$items = $statsShowSummary->stats_show_summary->items;
			$displayItemStats = FALSE;
			for ( $i = 0; $i < count( $items ); $i++ ) {
				$itemStats = doesItemHaveRecentStats( $items[$i]->item_id, $api, $plugin );
				// As long as we find one item with stats, switch this to TRUE
				if ($itemStats !== FALSE) {
					$displayItemStats = TRUE;
				}
			}

			if ( $displayItemStats ) {
				echo '<tr>
						<th colspan=2>
							<table class="form-table">
								<tr>
									<th>' . __('Title / Released', 'libsyn-podcasting') . '</th>
									<th>' . date( 'M', strtotime( ' -2 months' ) ) . '</th>
									<th>' . date( 'M', strtotime( ' -1 months' ) ) . '</th>
									<th>' . date( 'M' ) . '</th>
									<th>' . __('Total', 'libsyn-podcasting') . '</th>
								</tr>';

				for ( $i = 0; $i < count( $items ); $i++ ) {
				    displayItemStatsIfExist($items[$i]->item_id, $api, $plugin, $items[$i], $i);
				}
				echo '</table>
					</th>
				</tr>';

				}
				else {
					echo '<tr><th colspan=2><p id="player-description-text"><em>' . __('No recent episodes with recent stats to show', 'libsyn-podcasting') . '</em></p></th></tr>';
				}

			echo '</table>';
		}
		else {
			echo '<p id="player-description-text"><em>' . __('No recent stats to display for this show.', 'libsyn-podcasting') . '</em></p>';
		}
	} catch ( Exception $e ) {
		//TODO: Log error
	}
}

function displayStatsIfPresent($statsArr = array(), $offset = 0, $key = '') {
	if ( ( !empty($statsArr) && !empty($key) ) && array_key_exists( $offset, $statsArr ) ) {
		if ( array_key_exists( $key, $statsArr[$offset] ) ) {
			return $statsArr[$offset][$key];
		}
		else {
			return 0;
		}

	}
	else {
		return 0;
	}
}

function doesItemHaveRecentStats( $itemId, $api, $plugin ) {
	$itemThreeMonth = $plugin->getStatsItemThreeMonth( $api, $itemId );
	if ( !empty( $itemThreeMonth ) ) {
		if ( property_exists( $itemThreeMonth->stats_item_three_month, 'item_three_month' ) ) {
			$statsItemArr = $itemThreeMonth->stats_item_three_month->item_three_month;
		}
		else {
			$statsItemArr = '';
		}

		if ( !empty( $statsItemArr ) ) {
			$itemMonthArr = array_reverse( $statsItemArr );
			return $itemMonthArr;
		}
		else {
			return false;
		}
	}

}

function displayItemStatsIfExist( $itemId, $api, $plugin, $itemObj, $i = 0 ) {
	$itemMonthArr = doesItemHaveRecentStats( $itemId, $api, $plugin );
	if ( $itemMonthArr !== FALSE) {
		foreach ( $itemMonthArr as $monthObj ) {
			$displayItemMonthArr[] = array(
				'month' => DateTime::createFromFormat( '!m', substr( $monthObj->month, 5) )->format('M'),
				'downloads' => number_format( $monthObj->downloads )
			);
		}
		if ($i % 2 === 0) {
			$style = '';
		}
		else {
			$style = ' style="background-color:#F2F2F2;"';
		}
		// If the title has no spaces in the first 20 characters, let's clip it
		if ( ( strpos ( $itemObj->item_title, ' ') > 24 ) || ( strpos ( $itemObj->item_title, ' ') === FALSE) ) {
			$epTitle = substr( $itemObj->item_title, 0, 24 );
		}
		else {
			$epTitle = $itemObj->item_title;
		}
		echo '<tr' . $style . '>' .
				'<th>' . $epTitle . '<br/>' .
				__('Released:', 'libsyn-podcasting') . ' ' . date( 'm/d/y', strtotime( $itemObj->release_date ) ) . '</th>' .
			    '<th>' . displayStatsIfPresent( $displayItemMonthArr, 0, 'downloads') . '</th>' .
			    '<th>' . displayStatsIfPresent( $displayItemMonthArr, 1, 'downloads') . '</th>' .
			    '<th>' . displayStatsIfPresent( $displayItemMonthArr, 2, 'downloads') . '</th>' .
			    '<th>' . number_format( $itemObj->total_item_downloads ) . '</th>' .
			  '</tr>';
	}
}
