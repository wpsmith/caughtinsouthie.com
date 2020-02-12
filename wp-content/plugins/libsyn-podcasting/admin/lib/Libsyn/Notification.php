<?php
namespace Libsyn;

class Notification extends WP_Error {
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
			// $html .= "<button type=\"button\" class=\"notice-dismiss\"><span class=\"screen-reader-text\">Dismiss this notice.</span></button></div>";
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