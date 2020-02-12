<?php

namespace wpautoterms\frontend\notice;

use wpautoterms\admin\Options;
use wpautoterms\Updated_Posts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Update_Notice extends Base_Notice {
	const ID = 'update_notice';
	const COOKIE_PREFIX = 'wpautoterms-update-notice-';
	const BLOCK_CLASS = 'wpautoterms-update-notice';
	const CLOSE_CLASS = 'wpautoterms-notice-close';
	const ACTION_NAME = '_check_updates';

	public $message_multiple;
	protected $compat;

	public static function create() {
		$a = new Update_Notice( static::ID, WPAUTOTERMS_TAG . '-update-notice-container', static::BLOCK_CLASS );
		$a->message_multiple = get_option( WPAUTOTERMS_OPTION_PREFIX . $a->id() . '_message_multiple' );

		return $a;
	}

	protected function _print_box() {
		\wpautoterms\print_template( 'update-notice', array(
			'class_escaped' => esc_attr( static::BLOCK_CLASS ),
			'close' => $this->_close_message,
		) );
	}

	protected function _localize_args() {
		$ret = parent::_localize_args();
		$posts = new Updated_Posts( intval( get_option( WPAUTOTERMS_OPTION_PREFIX . 'update_notice_duration' ) ),
			static::COOKIE_PREFIX, $this->_message, $this->message_multiple );
		$posts->fetch_posts();
		$ret['data'] = $posts->transform();
		$ret['ajaxurl'] = admin_url( 'admin-ajax.php' );
		$ret['action'] = WPAUTOTERMS_SLUG . static::ACTION_NAME;
		$ret['cache_detector_cookie'] = WPAUTOTERMS_SLUG . '_cache_detector';
		$ret['cache_detected'] = 1;
		setcookie( $ret['cache_detector_cookie'], 0, 0, COOKIEPATH, COOKIE_DOMAIN );

		return $ret;
	}
}
