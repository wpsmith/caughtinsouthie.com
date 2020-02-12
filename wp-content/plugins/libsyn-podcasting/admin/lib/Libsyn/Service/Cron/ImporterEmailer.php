<?php
namespace Libsyn\Service\Cron;

/*
	This is the child class of Cron to handle 
	only the ImporterEmailer WP Cron task
*/
class ImporterEmailer extends \Libsyn\Service\Cron {
	
	const CRON_HOOK = 'libsyn_cron_importeremailer';
	
	public function __construct() {
		// parent::__construct();
		
		//define function name
		$this->function_name = 'Libsyn\Service\Cron\ImporterEmailer::ImporterEmailer';	
		
		//define hook
		$this->hook = self::CRON_HOOK;
		
		//define recurrence interval
		$this->recurrence_interval = 'thirty_seconds';
	}
	
    /**
     * Handles building a email to notify user
	 * of the importer status upon completion.
     * 
     * 
     * @return bool
     */
	public static function ImporterEmailer() {
		//Set Vars
		$plugin = new \Libsyn\Service();
		$current_user_id = $plugin->getCurrentUserId();
		$api = $plugin->retrieveApiById($current_user_id);
		$ppFeedTriggered = get_user_option('libsyn-podcasting-pp_feed_triggered');
		$feedImportId = get_user_option('libsyn-podcasting-feed_import_id');
		$feedImportPosts = get_user_option('libsyn-podcasting-feed_import_posts');

		//Handle Feed
		if(!empty($feedImportId)) {
			
			//get the job status
			if(!empty($feedImportPosts) && is_array($feedImportPosts)) {//pass $feedImportPosts as items to handle feed import update only media
				$importGuids = array();
				foreach($feedImportPosts as $post) {
					if(!empty($post->guid)) {
						$importGuids[] = $post->guid;
					}
				}
				$importStatus = (!empty($api) && $api instanceof \Libsyn\Api) ? $plugin->feedImportStatus($api, array('job_id' => $feedImportId, 'items' => $importGuids)) : false;
			} else {
				$importStatus = (!empty($api) && $api instanceof \Libsyn\Api) ? $plugin->feedImportStatus($api, array('job_id' => $feedImportId)) : false;
			}

			//Feed Import Status
			if(!empty($importStatus->{'feed-import'}) && is_array($importStatus->{'feed-import'})) {
				foreach ($importStatus->{'feed-import'} as $row) {
					if(!empty($feedImportPosts) && !empty($ppFeedTriggered)) {//has powerpress or local feed
						foreach ($feedImportPosts as $feed_item) {
							if(function_exists('url_to_postid')) {
								$feedItemLink = url_to_postid($feed_item->{'link'});
							} else {
								$feedItemLink = false;
							}
							if(function_exists('get_permalink')) {
								$feedItemId = get_permalink($feed_item->{'id'});
							} else {
								$feedItemId = false;
							}
							if(function_exists('url_to_postid')) {
								$rowCustomPermalinkUrl = url_to_postid($row->custom_permalink_url);
							} else {
								$rowCustomPermalinkUrl = '';
							}
							
							if(!empty($feed_item->{'link'}) && !empty($feedItemLink) ) {
								$working_id = url_to_postid($feed_item->{'link'});
							} else if(!empty($feed_item->{'id'}) && !empty($feedItemId) ) {
								$working_id = $feed_item->{'id'};
							} else {
								$working_id = null;
							}

							if( //Check to make sure working_id matches up to what we imported
								!empty($working_id) && 
								(!empty($feed_item->{'guid'}) && !empty($row->guid) && ($feed_item->{'guid'} === $row->guid)) ||
								(!empty($row->custom_permalink_url) && ($rowCustomPermalinkUrl == $working_id)) ||
								(!empty($row->guid) && ( function_exists('mb_strpos') && mb_strpos($row->guid, $working_id) !== false ) || ( strpos($row->guid, $working_id) !== false ) )
							) {
								$hasIncompleteImport = false;
								$contentStatus = $row->primary_content_status;
								switch ($contentStatus) {
									case "":
									case null:
									case false:
										break;
									case "awaiting_payload":
										$hasIncompleteImport = true;
										break;
									case "failed":
										$contentStatusColor = "style=\"color:red;\"";
										$hasFailedImport = true;
										$hasIncompleteImport = true;
										break;
									case "available":
										$available = true;
										break;
									default:
								}
								if(isset($contentStatus)) unset($contentStatus);
							}
							if( isset($feedItemLink) ) unset($feedItemLink);
							if( isset($feedItemId) ) unset($feedItemId);
							if( isset($rowCustomPermalinkUrl) ) unset($rowCustomPermalinkUrl);
							if( isset($working_id) ) unset($working_id);
						}
					} elseif(empty($ppFeedTriggered)) {//has external feed import (make sure not pp feed import)
						if(function_exists('url_to_postid')) {
							$rowCustomPermalinkUrl = url_to_postid($row->custom_permalink_url);
						} else {
							$rowCustomPermalinkUrl = '';
						}
						if(!empty($row->custom_permalink_url) && empty($rowCustomPermalinkUrl)) {//check that this is not actually a wp post already
							$hasIncompleteImport = false;
							$contentStatus = $row->primary_content_status;
							switch ($contentStatus) {
								case "":
								case null:
								case false:
									break;
								case "awaiting_payload":
									$hasIncompleteImport = true;
									break;
								case "failed":
									$hasFailedImport = true;
									$hasIncompleteImport = true;
									break;
								case "available":
									$available = true;
									break;
								default:
							}
							if(isset($contentStatus)) unset($contentStatus);
						}
						if( isset($working_id) ) unset($working_id);
						if( isset($rowCustomPermalinkUrl) ) unset($rowCustomPermalinkUrl);
					}
				}
			}

			if(isset($available) && (isset($hasFailedImport) && $hasFailedImport === true)) {//import failed
				$sendMail = self::sendMail('failed');
				if(!$sendMail) {
					//TODO: Log fail mail sending
				}
				//deactivate regardless because import failed
				parent::deactivateStatic(self::CRON_HOOK);
				return false;
			} elseif(isset($available) && (isset($hasIncompleteImport) && $hasIncompleteImport === false)) {//import success and complete
				$sendMail = self::sendMail('complete');
				
				if($sendMail) {
					parent::deactivateStatic(self::CRON_HOOK);
					return true;
				} else {//keep cron active (do no deactivate)
					return false;
					//TODO: Log fail mail sending
				}
			}
			
		} else {//feed import id not set so deactivate cron
			parent::deactivateStatic(self::CRON_HOOK);
			return true;
		}
		return false;
	}
	
    /**
     * Simple function to call for 
     * 'wp_mail_content_type' filter
     * 
     * @return string
     */
	public static function setContentType() {
		return 'text/html';
	}
	
    /**
     * Handles sending a email through wordpress
	 * to the current user's email
     * 
     * @param string $status (complete,failed) 
     * 
     * @return bool
     */
	private static function sendMail($status) {

		if(empty($status)) return false;
		$plugin = new \Libsyn\Service();
		$current_user = wp_get_current_user();
		
		$params = array();
		$params['name'] = (!empty($current_user->user_login)) ? 'Greetings '.$current_user->user_login : 'Greetings Podcaster';
		
		$params['button_link'] = $plugin->admin_url('admin.php').'?page=LibsynImports';
		
		switch($status) {
			case "complete":				
				$subject = "Feed Import - Complete";
				$params['top_text'][0] = 'Your feed import has successfully completed!  You may return to the "Imports" page under Libsyn Publisher Hub to view what you need to do next, such as <strong>Setting up 301 redirects</strong> and <strong>Adding the Libsyn Player</strong> to your Wordpress Posts.';
				break;
				
			case "failed":
				$subject = "Feed Import - Failed";
				$params['top_text'][0] = 'Something went wrong with your feed import.  You may return to the "Imports" page under Libsyn Publisher Hub for more information or to check your settings and try again.';
				break;
		}

		add_filter( 'wp_mail_content_type', 'Libsyn\Service\Cron\ImporterEmailer::setContentType' );
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
		$status = wp_mail( $current_user->user_email, $subject, self::buildContent($params), $headers);
		// if(!$status) {//try native php mail
			// $status = mail( $current_user->user_email, $subject, self::buildContent($params), $headers );
		// }

		// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
		remove_filter( 'wp_mail_content_type', 'Libsyn\Service\Cron\ImporterEmailer::setContentType' );

		if(!empty($status)) {
			return true;
		}
		return false;
	}
	
	private static function buildContent($params) {
		if(empty($params['button_link'])) $params['button_link'] = '';
		if(empty($params['name'])) $params['name'] = '';
		if(empty($params['top_text'][0])) $params['top_text'][0] = '';
		if(empty($params['bottom_text'][0])) $params['bottom_text'][0] = '';
		if(empty($params['bottom_text'][1])) $params['bottom_text'][1] = '';
		
		//html temptlate
		return '
<!doctype html>
<html>
  <head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Simple Transactional Email</title>
    <style>
    /* -------------------------------------
        RESPONSIVE AND MOBILE FRIENDLY STYLES
    ------------------------------------- */
    @media only screen and (max-width: 620px) {
      table[class=body] h1 {
        font-size: 28px !important;
        margin-bottom: 10px !important;
      }
      table[class=body] p,
            table[class=body] ul,
            table[class=body] ol,
            table[class=body] td,
            table[class=body] span,
            table[class=body] a {
        font-size: 16px !important;
      }
      table[class=body] .wrapper,
            table[class=body] .article {
        padding: 10px !important;
      }
      table[class=body] .content {
        padding: 0 !important;
      }
      table[class=body] .container {
        padding: 0 !important;
        width: 100% !important;
      }
      table[class=body] .main {
        border-left-width: 0 !important;
        border-radius: 0 !important;
        border-right-width: 0 !important;
      }
      table[class=body] .btn table {
        width: 100% !important;
      }
      table[class=body] .btn a {
        width: 100% !important;
      }
      table[class=body] .img-responsive {
        height: auto !important;
        max-width: 100% !important;
        width: auto !important;
      }
    }

    /* -------------------------------------
        PRESERVE THESE STYLES IN THE HEAD
    ------------------------------------- */
    @media all {
      .ExternalClass {
        width: 100%;
      }
      .ExternalClass,
            .ExternalClass p,
            .ExternalClass span,
            .ExternalClass font,
            .ExternalClass td,
            .ExternalClass div {
        line-height: 100%;
      }
      .apple-link a {
        color: inherit !important;
        font-family: inherit !important;
        font-size: inherit !important;
        font-weight: inherit !important;
        line-height: inherit !important;
        text-decoration: none !important;
      }
      .btn-primary table td:hover {
        background-color: #34495e !important;
      }
      .btn-primary a:hover {
        background-color: #34495e !important;
        border-color: #34495e !important;
      }
    }
    </style>
  </head>
  <body class="" style="background-color: #f6f6f6; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
    <table border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: #f6f6f6;">
      <tr>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
        <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; Margin: 0 auto; max-width: 580px; padding: 10px; width: 580px;">
          <div class="content" style="box-sizing: border-box; display: block; Margin: 0 auto; max-width: 580px; padding: 10px;">

            <!-- START CENTERED WHITE CONTAINER -->
            <span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;">This is preheader text. Some clients will show this text as a preview.</span>
            <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background: #ffffff; border-radius: 3px;">

              <!-- START MAIN CONTENT AREA -->
              <tr>
                <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                  <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                    <tr>
                      <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">' . $params['name'] . ',</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">' . $params['top_text'][0] . '</p>
                        <table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; box-sizing: border-box;">
                          <tbody>
                            <tr>
                              <td align="left" style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding-bottom: 15px;">
                                <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto;">
                                  <tbody>
                                    <tr>
                                      <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; background-color: #6ba342; border-radius: 5px; text-align: center;"> <a href="' . $params['button_link'] . '" target="_blank" style="display: inline-block; color: #ffffff; background-color: #6ba342; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-transform: capitalize; border-color: #6ba342;">Libsyn Publisher Hub - Imports</a> </td>
                                    </tr>
                                  </tbody>
                                </table>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">' . $params['bottom_text'][0] . '</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">' . $params['bottom_text'][1] . '</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

            <!-- END MAIN CONTENT AREA -->
            </table>

            <!-- START FOOTER -->
            <div class="footer" style="clear: both; Margin-top: 10px; text-align: center; width: 100%;">
              <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                <tr>
                  <td class="content-block" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; font-size: 12px; color: #999999; text-align: center;">
                    <span class="apple-link" style="color: #999999; font-size: 12px; text-align: center;">Libsyn - Liberated Syndication, Pittsburgh PA 15213</span>
                    <br> Questions?  Contact Us - <a href="mailto:support@libsyn.com" style="text-decoration: underline; color: #999999; font-size: 12px; text-align: center;">support@libsyn.com</a>.
                  </td>
                </tr>
              </table>
            </div>
            <!-- END FOOTER -->

          <!-- END CENTERED WHITE CONTAINER -->
          </div>
        </td>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
      </tr>
    </table>
  </body>
</html>';
	}
}