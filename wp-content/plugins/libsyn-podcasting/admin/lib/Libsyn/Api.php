<?php
namespace Libsyn;

class Api extends \Libsyn{

	protected $user_id;

	protected $plugin_api_id;

	protected $client_id;

	protected $client_secret;

	protected $access_token;

	protected $refresh_token;

	protected $is_active;

	protected $refresh_token_expires;

	protected $access_token_expires;

	protected $show_id;

	protected $show_title;

	protected $feed_url;

	protected $feed_redirect_id;

	protected $itunes_subscription_url;

	protected $creation_date;

	protected $last_updated;


	public function __construct( $properties ){
		parent::getVars();
		if($properties===null||empty($properties)) return false;
		$isJson = $this->utilities->isJson($properties);
		if($isJson) $properties = json_decode($properties, true);
		if(!empty($properties) && (is_array($properties) || is_object($properties))) {
			foreach($properties as $key => $value){
				$this->{$key} = $value;
			}
		}
		$this->sanitize = new Service\Sanitize();
		$this->service = new \Libsyn\Service();
	}

    /**
     * Simply does a API call to refresh the token and update access_tokens.
     *
     *
     * @return <bool>
     */
	public function refreshToken() {
		// global $wpdb;

		//first check to see if we need to make the call
		if(strtotime($this->refresh_token_expires) >= strtotime("+87 days 23 hours 59 minutes")) return true;
		/*
		add_action('http_api_curl', function( $handle ){
			//Don't verify SSL certs
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($handle, CURLOPT_SSLVERSION, 1);
		}, 10);
		*/
		$args =array(
			'headers' => array (
					'date' => date("D j M Y H:i:s T", $this->wp_time),
					'x-powered-by' => $this->plugin_name,
					'x-server' => $this->plugin_version,
					'expires' => date("D j M Y H:i:s T", strtotime("+3 hours", $this->wp_time)),
					'vary' => 'Accept-Encoding',
					'connection' => 'close',
					'content-type' => 'application/json',
					'accept' => 'application/json',
				),
			'body' => json_encode(
					array(
						'grant_type' => 'refresh_token',
						'refresh_token' => $this->getRefreshToken(),
						'client_id' => $this->getClientId(),
						'client_secret' => $this->getClientSecret(),
					)
				),
			'timeout' => 20,
		);
		$url = $this->api_base_uri."/oauth";
		$obj =  (object) wp_remote_post( $url, $args );
		if($this->checkResponse($obj)) {
			$data = json_decode($obj->body);
			$checkResponse = (!empty($data->refresh_token))?true:false;
		} else $checkResponse = false;
		if(!$checkResponse) { //bad check will remove refresh token and return false so user can re-authenticate oauth
			$this->setRefreshToken(null);
			$this->save();
			if($this->service->hasLogger) $this->service->logger->error("API:\trefreshToken:\tcheckResponse is false.");
			return false;
		}
		//check to make sure we have proper data response
		if(!empty($data->access_token) && !empty($data->refresh_token)) {
			//update settings
			$this->setAccessToken( $data->access_token );
			$this->setRefreshToken( $data->refresh_token );
			$this->setRefreshTokenExpires( $this->sanitize->date_format( date("Y-m-d H:i:s", strtotime("+88 days", $this->wp_time))) );
			$this->setAccessTokenExpires( $this->sanitize->date_format(date("Y-m-d H:i:s", strtotime("+28 days", $this->wp_time))) );
			$this->save();
			return true;
		} else {
			if($this->service->hasLogger) $this->service->logger->error("API:\trefreshToken:\taccess_token or refresh_token is not set.");
			return false;
		}
	}

    /**
     * Check for if the refresh token is expired.
     *
     *
     * @return <bool>
     */
	public function isRefreshExpired() {
		$refreshExpires = $this->refresh_token_expires;
		if(!empty($refreshExpires)) {
			$checkDate = $this->sanitize->date_format($refreshExpires);
		} else {
			$checkDate = '';
		}

		if(!empty($checkDate)) {
			if ($this->wp_time <=  strtotime($checkDate)) {
				return false; //not expired
			} else {
				if($this->service->hasLogger) $this->service->logger->info("API:\tisRefreshExpired:\t".date("Y-m-d H:i:s", $this->wp_time)." not less than ".$this->refresh_token_expires);
			}
		} else {
			if($this->service->hasLogger) $this->service->logger->error("API:\tisRefreshExpired:Refresh Token Expires Empty");
		}
		return false; //default
	}

    /**
     * Updates a saved api if the refresh token expired.
     *
     * @param <Libsyn\Api> $api
     * @param <array> $settings
     *
     * @return <Libsyn\Api>
     */
	public function update( array $settings ) {
		// global $wpdb;

		//check to make sure we have proper data response
		if(isset($settings['access_token']) && isset($settings['refresh_token'])) {
			//set defaults
			$showId = (!empty($settings['show_id']))?$settings['show_id']:null;


			//update settings
			$dataSettings = array(
				'user_id'					=> $this->sanitize->userId($settings['user_id']),
				'plugin_api_id'				=> $this->sanitize->pluginApiId($this->plugin_api_id),
				'client_id'					=> $this->sanitize->clientId($this->client_id),
				'client_secret'				=> $this->sanitize->clientSecret($this->client_secret),
				'access_token'				=> $this->sanitize->accessToken($settings['access_token']),
				'refresh_token'				=> $this->sanitize->refreshToken($settings['refresh_token']),
				'refresh_token_expires'		=> $this->sanitize->date_format(date("Y-m-d H:i:s", strtotime("+88 days", $this->wp_time))),
				'access_token_expires'		=> $this->sanitize->date_format(date("Y-m-d H:i:s", strtotime("+28 days", $this->wp_time))),
				'show_id'					=> $this->sanitize->showId($showId),
				'is_active'					=> 1
			);

			//update extra settings
			if(!empty($settings['feed_url'])) {
				$dataSettings['feed_url'] = $this->sanitize->url_raw($settings['feed_url']);
			}
			if(!empty($settings['feed_redirect_id'])) {
				$dataSettings['feed_redirect_id'] = $this->sanitize->numeric($settings['feed_redirect_id']);
			}
			if(!empty($settings['itunes_subscription_url'])) {
				$dataSettings['itunes_subscription_url'] = $this->sanitize->url_raw($settings['itunes_subscription_url']);
			}
			if(!empty($settings['show_title'])) {
				$dataSettings['show_title'] = $this->sanitize->url_raw($settings['show_title']);
			}

			$api_table_name = $this->getApiTableName();
			update_user_option($settings['user_id'], $api_table_name, json_encode($dataSettings), false);
			return $this->retrieveApiById($this->sanitize->pluginApiId($this->user_id));
		} else {
			if($this->service->hasLogger) $this->service->logger->error("API:\tupdate:\taccess_token or refresh_token not set.");
			if($this->service->hasLogger) $this->service->logger->error("API:\tupdate:\taccess_token:\t".$settings['access_token']);
			if($this->service->hasLogger) $this->service->logger->error("API:\tupdate:\trefresh_token:\t".$settings['refresh_token']);
			return false;
		}
	}

    /**
     * Send back API object as an Array
     *
     *
     * @return <array>
     */
	public function toArray() {
		return array(
			'user_id'					=>  $this->getUserId(),
			'plugin_api_id'				=>	$this->getPluginApiId(),
			'client_id'					=>	$this->getClientId(),
			'client_secret'				=>	$this->getClientSecret(),
			'access_token'				=>	$this->getAccessToken(),
			'refresh_token'				=>	$this->getRefreshToken(),
			'refresh_token_expires'		=>	$this->getRefreshTokenExpires(),
			'feed_url'					=>	$this->getFeedUrl(),
			'feed_redirect_id'			=>	$this->getFeedRedirectId(),
			'itunes_subscription_url'	=>	$this->getItunesSubscriptionUrl(),
			'access_token_expires'		=>	$this->getAccessTokenExpires(),
			'show_id'					=>	$this->getShowId(),
			'show_title'				=>	$this->getShowTitle(),
			'is_active'					=>	$this->is_active,
			'creation_date'				=>	$this->getCreationDate(),
			'last_updated'				=>	$this->getLastUpdated(),
		);
	}

    /**
     * Checks API response
     *
     * @param <object> $obj
     *
     * @return <boolean>
     */
	public function checkResponse( $obj ) {
		if(!is_object($obj) || empty($obj->response)) return false;
		if($obj->response['code']!==200) {
			if(isset($this->service->logger)) {
				if($this->service->hasLogger) { // log errors
					$this->service->logger->error("API:\tcheckResponse:\tError");
					if(!empty($obj->response['url']))
						$this->service->logger->error("API:\turl:\t".$obj->response['url']);
							else $this->service->logger->error("API:\turl:\tunkown");
					if(!empty($obj->response['success']))
						$this->service->logger->error("API:\tsuccess:\t".$obj->response['success']);
							elseif(!empty($obj->response['message']))
								$this->service->logger->error("API:\tmessage:\t".$obj->response['message']);
									else $this->service->logger->error("API:\tsuccess:\tunkown");
					if(!empty($obj->response['status_code']))
						$this->service->logger->error("API:\tstatus_code:\t".$obj->response['status_code']);
							elseif(!empty($obj->response['code']))
								$this->service->logger->error("API:\tstatus_code:\t".$obj->response['code']);
									else $this->service->logger->error("API:\tstatus_code:\tunkown");
					if(!empty($obj->http_response)){
						$objectResponse = $obj->http_response->get_data();
						if(!empty($objectResponse)) {
							$res_data = json_decode($objectResponse);
							if(!empty($res_data->title)) $this->service->logger->error("API:\ttitle:\t".$res_data->title);
							if(!empty($res_data->detail)) $this->service->logger->error("API:\tdetail:\t".$res_data->detail);
						}
					}
				}
			}
			return false;
		} else {
			return true;
		}
	}

    /**
     * Saves Current Instance of Api
     *
     *
     * @return <type>
     */
	public function save() {
		$current_user_id = $this->getUserId();
		if(empty($current_user_id)) return false;  //no user.. backout!

		//update settings
		$dataSettings = array(
			'user_id'					=> $current_user_id,
			'plugin_api_id'				=> $this->getPluginApiId(),
			'client_id'					=> $this->getClientId(),
			'client_secret'				=> $this->getClientSecret(),
			'access_token'				=> $this->getAccessToken(),
			'refresh_token'				=> $this->getRefreshToken(),
			'refresh_token_expires'		=> $this->getRefreshTokenExpires(),
			'feed_url'					=> $this->getFeedUrl(),
			'feed_redirect_id'			=> $this->getFeedRedirectId(),
			'itunes_subscription_url'	=> $this->getItunesSubscriptionUrl(),
			'access_token_expires'		=> $this->getAccessTokenExpires(),
			'show_id'					=> $this->getShowId(),
			'show_title'				=> $this->getShowTitle(),
			'is_active'					=> 1
		);
		$api_table_name = $this->getApiTableName();
		update_user_option($current_user_id, $api_table_name, json_encode($dataSettings), false);
		return $this->retrieveApiById($this->sanitize->pluginApiId($this->user_id));
	}


	/* GETTERS OVERRIDE */

	public function getUserId() { return (!is_null($this->user_id))?$this->sanitize->userId($this->user_id):null; }

	public function getPluginApiId() { return (!is_null($this->plugin_api_id))?$this->sanitize->pluginApiId($this->plugin_api_id):null; }

	public function getClientId() { return (!is_null($this->client_id))?$this->sanitize->clientId($this->client_id):null; }

	public function getClientSecret() { return (!is_null($this->client_secret))?$this->sanitize->clientSecret($this->client_secret):null; }

	public function getAccessToken() { return (!is_null($this->access_token))?$this->sanitize->accessToken($this->access_token):null; }

	public function getRefreshToken() { return (!is_null($this->refresh_token))?$this->sanitize->refreshToken($this->refresh_token):null; }

	public function getFeedUrl() { return (!is_null($this->feed_url))?$this->sanitize->feedUrl($this->feed_url):null; }

	public function getFeedRedirectId() { return (!is_null($this->feed_redirect_id))?$this->sanitize->numeric($this->feed_redirect_id):null; }

	public function getItunesSubscriptionUrl() { return (!is_null($this->itunes_subscription_url))?$this->sanitize->itunesSubscriptionUrl($this->itunes_subscription_url):null; }

	public function getIsActive() { return ($this->is_active==1)?true:false; }

	public function getRefreshTokenExpires() { return (!is_null($this->refresh_token_expires))?$this->sanitize->refreshTokenExpires($this->refresh_token_expires):null; }

	public function getAccessTokenExpires() { return (!is_null($this->access_token_expires))?$this->sanitize->accessTokenExpires($this->access_token_expires):null; }

	public function getShowId() { return (!is_null($this->show_id))?$this->sanitize->showId($this->show_id):null; }

	public function getShowTitle() { return (!is_null($this->show_title))?$this->sanitize->text($this->show_title):null; }

	public function getCreationDate() { return (!is_null($this->creation_date))?$this->sanitize->creationDate($this->creation_date):null; }

	public function getLastUpdated() { return (!is_null($this->last_update))?$this->sanitize->lastUpdated($this->last_updated):null; }


}

?>
