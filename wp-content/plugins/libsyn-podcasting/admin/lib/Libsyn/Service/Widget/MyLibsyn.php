<?php
namespace Libsyn\Service\Widget;

class MyLibsyn extends \Libsyn\Service\Widget {

	protected $widget_opts;

	public function __construct() {
		$this->widget_opts = array(
		  'name'        => esc_html__('MyLibsyn Login Widget', LIBSYN_TEXT_DOMAIN),
		  'classname'   => 'libsyn_mylibsyn',
		  'description' => esc_html__('Add a login widget for MyLibsyn users.', LIBSYN_TEXT_DOMAIN),
		);
		parent::__construct($this->widget_opts);
	}

	/**
	 * Register Widget
	 * @since 1.3.1
	 * @return void
	 */
	public function register() {
		add_action('widgets_init', function() {
		  register_widget( $this->widget_opts['name'] );
		});
	}


  	/**
  	 * Outputs the content of the widget
  	 *
  	 * @since 1.3.1
  	 * @param  array $args     Widget Args
  	 * @param  array $instance Instance of WP_Widget
  	 * @return void
  	 */
  	public function widget( $args, $instance ) {
  		//outputs the content of the widget
  		global $wp;
		wp_enqueue_style( 'dashicons' );
		$plugin = new \Libsyn\Service();
		$api = $plugin->getApi();//NOTE: this may not be accurate as is will return only the current active show
		if ( !$api instanceof \Libsyn\Api ) {//sanity check
			return false;
		}
		wp_enqueue_style( 'libsyn-meta-boxes', plugins_url(LIBSYN_DIR . '/lib/css/libsyn/mylibsyn.css' ));
		$title = apply_filters( 'widget_title', $instance[ 'title' ] );
		$custom_color = ( !empty($instance['custom_color']) ) ? $instance['custom_color'] : '';
	    $blog_title = get_bloginfo( 'name' );
	    $tagline = get_bloginfo( 'description' );
		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		$failureMessage = ( isset($_GET['failure']) ) ? $_GET['failure'] : '';

		//TODO: get user-shows call and grab paywall_link
		$actionUrl = "https://" . $plugin->getMylibsynDomain() . "/auth/login/?referer=" . urlencode( $current_url ) . "&amp;in-website=false&wordpress-plugin=true&show_id=" . $api->getShowId();
    	// echo $args['before_widget'] . '<div class="mylibsyn-logo" style="width: 100%; margin-left:12px;"><img src="' . plugins_url(LIBSYN_DIR . '/lib/images/mylibsyn-logo-black.png' ) . '" width="100" title="' . $title . '" alt="' . $title . '"></div>';
    	echo $args['before_widget'];


		//custom color
		add_action( 'wp_head', function() use ( $custom_color ) {
			$this->customColor( $custom_color );
		});
		$this->customColor( $custom_color );
		?>

		<div class="mylibsyn-widget-title"><?php echo $title; ?></div>
		<!-- <div class="mylibsyn-loader" style="display:none; margin:auto;"><img src="https://static.libsyn.com/p/assets/platform/directory/svg-icons/loading_ring.svg" alt="loader"/></div> -->
		<form id="myLibsynLoginForm" class="libsyn-form-signin" method="POST" action="<?php echo $actionUrl; ?>" autocomplete="off">
			<div id="mylibsyn-login-message">
				<p></p>
			</div>
			<div class="mylibsyn-login-field-row">
				<div class="mylibsyn-login-field-group">
					<input type="text" id="myLibsynInputEmail" name="email" class="form-control" required="" autofocus="">
					<span></span>
					<label for="inputEmail">Email</label>
				</div>
				<div class="mylibsyn-login-field-group">
					<input type="password" id="myLibsynInputPassword" name="password" class="form-control" required="">
					<span></span>
					<label for="inputPassword">Password</label>
				</div>
				<div class="mylibsyn-login-row">
					<button name="Login for Premium Access" type="submit" class="mylibsyn-login-btn">Login</button>
					<a class="mylibsyn-trouble-link" href="<?php echo $actionUrl; ?>" target="_blank">Having trouble logging in?</a>
				</div>
			</div>

		</form>

		<div id="mylibsyn-loggedin-section">

		</div>

		<script type="text/javascript">
		(function($) {
			$( document ).ready(function() {
				$("#myLibsynLogout").on("click", function(e) {
				 e.preventDefault();
				 var theUrl = "https://<?php echo $plugin->getMylibsynDomain() ?>/auth/logout?user_logout=true&render=json&referer=<?php echo urlencode( home_url( $wp->request ) ); ?>";
				 $.ajax({
					 type: 'GET',
					 url: theUrl,
					 data: {user_logout: true},
					 dataType: 'jsonp'
				 });
				 location.reload();
				});

				var theUrl = "https://<?php echo $plugin->getMylibsynDomain(); ?>/auth/check-premium-episodes?user_information=true";
				var myLibsynFailure = '<?php echo $failureMessage; ?>';
				if ( myLibsynFailure.length >= 1 ) {
					$('#mylibsyn-login-message').empty().append('<span">' + myLibsynFailure + '</span>').fadeIn('fast');
				} else {
					$('#mylibsyn-login-message').empty().hide();
				}
				$.ajax({
		           type: 'GET',
		           url: theUrl,
		           data: {show_id: <?php echo $api->getShowId(); ?>, user_information: true},
		           dataType: 'jsonp',
							 beforeSend: function() {
								 $(".mylibsyn-loader").show();
							 },
		           success:function(json){
								 $(".mylibsyn-loader").hide();
		               if(typeof json.success != "undefined"){
							var isLibsynUserLoggedIn = returnLibsynEmail(json);
		               } else {
						  if( typeof json.failure !== 'undefined' ) {
							if ( json.failure == 'No logged in user.' ) {
								//user not logged in
								$('.libsyn-form-signin').fadeIn('fast');
							} else if ( json.failure == 'No premium subscription for this show.' ) {
								//no premium subscriptions found
								var isLibsynUserLoggedIn = returnLibsynEmail(json);
							} else if (json.failure == 'Invalid e-mail address or password provided') {
								//do nothing
							}
						  }
		               }
		           },
		           error:function(xhr, textStatus, errorThrown){
						// noPremium
						console.log('errrrrorrrrrr');
						console.log(errorThrown);
						console.log(xhr);
						$('.libsyn-form-signin').fadeIn('fast');
		           }
		       });

			   function returnLibsynEmail(data) {
			  		if ( typeof data.user_information !== 'undefined' ) {
						if ( typeof data.user_information.email !== 'undefined' ) {
							$('#mylibsyn-login-message').empty().hide();
							var myLibsynBlock = '<div class="mylibsyn-loggedin-container"><div class="mylibsyn-loggedin-row"><h4><?php echo $blog_title; ?> Premium</h4><div class="mylibsyn-loggedin-row-link"> <a href="https://<?php echo $plugin->getMylibsynDomain(); ?>/manage/index" aria-label="Go To Settings" data-btn-txt="Settings" target="_blank"><span class="dashicons dashicons-admin-generic"></span></a></div><div class="mylibsyn-loggedin-row-link"> <a id="myLibsynLogout" name="myLibsynLogout" aria-label="Sign Out" data-btn-txt="Sign Out"><span class="dashicons dashicons-migrate"></span></a></div></div><div class="mylibsyn-loggedin-row-hidden"><h4>Account Information</h4><div class="mylibsyn-loggedin-circle"><span class="dashicons dashicons-microphone" style="margin-top: 3px;margin-left: 5px;"></span>' + data.user_information.email.charAt(0).toUpperCase() + '</div><p style="font-weight:300">'+ data.user_information.email + ' | <i>Active</i></p></div></div>';
							var loggedinContainer = $('#mylibsyn-loggedin-section');

							if ( typeof libsynLoginBlock !== 'undefined' ) {

								loggedinContainer.html(myLibsynBlock);
								loggedinContainer.fadeIn('fast');
							} else {
								$('.libsyn-form-signin').detach();
								loggedinContainer.html(myLibsynBlock);

	 				 		   $(".libsyn-show, #libsyn-close-modal, #libsyn-mask-modal").on("click", function(e){
								   e.preventDefault();
	 				 			   $(".libsyn-mask").addClass("libsyn-active");
	 				 		   });

	 				 		   $(document).keyup(function(e) {
	 				 			   if (e.keyCode == 27) {
	 				 			   		mylibsynCloseModal();
	 				 			   }
	 				 		   });
							}
							return true;
						}
					}
					return false;
			   };
		   });
	   	})(jQuery);
		</script>

	    <?php echo $args['after_widget'];
  	}

  	/**
  	 * Outputs the options form on admin_url
  	 *
  	 * @since 1.3.1
  	 * @param  [type] $instance [description]
  	 * @return [type]           [description]
  	 */
  	public function form( $instance ) {
  		//outputs the options form on admin
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$custom_color = ! empty($instance['custom_color']) ? $instance['custom_color'] : '';

		//enqueue colorpicker
		wp_enqueue_script( 'iris', array('jquery'));
		wp_enqueue_style( 'iris' );
		?>
		<p>
			<?php _e("The myLibsyn Login widget adds a unique login integration to your Wordpress Site for users to enable premium episodes.  This will disable the download links that may be added until the subscriber is logged in also.", LIBSYN_TEXT_DOMAIN); ?>
		</p>
		<div class="widget-content">
		    <fieldset>
				<legend><?php _e("Widget Settings", LIBSYN_TEXT_DOMAIN); ?></legend>
				<p>
					<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e("Title", LIBSYN_TEXT_DOMAIN); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'custom_color' ); ?>"><?php _e('Custom Color', LIBSYN_TEXT_DOMAIN); ?>:</label>
					<input type="text" id="<?php echo $this->get_field_id( 'custom_color' ); ?>" class="color-picker" name="<?php echo $this->get_field_name( 'custom_color' ); ?>" value="<?php echo $custom_color; ?>"/>
					<button type="button" id="<?php echo $this->get_field_id( 'custom_color' ); ?>_button" class="button libsyn-dashicons-art"><?php _e('Pick Color', LIBSYN_TEXT_DOMAIN); ?></button>
					<div id="<?php echo $this->get_field_id( 'custom_color' ); ?>_container" style="padding: 0px 0px 0px 0px; width:100%; margin-left:40px;"></div>
				</p>
			</fieldset>
		</div>
		<script type="text/javascript">
		(function($) {
			function myLibsynWidget() {
				if ( typeof myLibsynWidgetCustomColor == 'undefined' ) {
					myLibsynWidgetCustomColor = "<?php echo $custom_color; ?>";
				}
				var myLibsynCustomColorInput = $('#<?php echo $this->get_field_id( 'custom_color' ); ?>');
				myLibsynColorPicker = myLibsynCustomColorInput.iris({
					palettes: ['#125', '#459', '#78b', '#ab0', '#de3', '#f0f'],
					hide: true,
					border: false,
					target: $('#<?php echo $this->get_field_id( 'custom_color' ); ?>_container'),
					change: function(event, ui) {
						myLibsynWidgetCustomColor = ui.color.toString();
						$('#<?php echo $this->get_field_id( 'custom_color' ); ?>').css('background-color', myLibsynWidgetCustomColor ).val(myLibsynWidgetCustomColor).attr('value', myLibsynWidgetCustomColor).trigger('change');
					}
				});

				if ( typeof myLibsynCustomColorInput.val().length != 'undefined' && myLibsynCustomColorInput.val().length >= 1) {
					if ( myLibsynCustomColorInput.val().includes('#') ) {
						$('#<?php echo $this->get_field_id( 'custom_color' ); ?>').css('background-color', myLibsynCustomColorInput.val());
					} else {
						$('#<?php echo $this->get_field_id( 'custom_color' ); ?>').css('background-color', "#" + myLibsynCustomColorInput.val());
					}
				}
				myLibsynColorPicker.click(function(e) {
					e.preventDefault();
					if(typeof myLibsynColorPicker !== 'undefined') {
						myLibsynColorPicker.iris('show');
						myLibsynColorPicker.data('isOpen', 'show');
					}
				});

				$('#<?php echo $this->get_field_id( 'custom_color' ); ?>_button').click(function(e) {
					e.preventDefault();
					if(typeof myLibsynColorPicker !== 'undefined') {
						if ( typeof myLibsynColorPicker.data('isOpen') === 'undefined' ) {
							myLibsynColorPicker.iris('show');
							myLibsynColorPicker.data('isOpen', 'show');
						} else {
							if ( myLibsynColorPicker.data('isOpen') === 'show' ) {
								myLibsynColorPicker.iris('hide');
								myLibsynColorPicker.data('isOpen', 'hide');
							} else if ( myLibsynColorPicker.data('isOpen') === 'hide' ) {
								myLibsynColorPicker.iris('show');
								myLibsynColorPicker.data('isOpen', 'show');
							}
							myLibsynColorPicker.iris('toggle');
						}

					}
				});
			}
			$( document ).ready(function() {//intialization
				myLibsynWidget();
			});
			$( document ).on('widget-updated', function(e, widget){//on widget added
				myLibsynWidget();
			});
			$( document ).on('widget-added', function(e, widget){//on widget updated
				myLibsynWidget();
			});
		})(jQuery);
		</script>
		<?php
  	}

	/**
	 * Generates css style for custom color override/default
	 * to use in wp_head
	 *
	 * @since 1.3.1
	 * @param  string $hexColor hex color value
	 * @return string CSS markup
	 */
	public function customColor( $hexColor ) {
		if ( function_exists('sanitize_hex_color') ) {
			$hexColor = sanitize_hex_color($hexColor);
		}
		if ( !empty($hexColor) && strpos($hexColor, '#') === false ) {
			$hexColor = '#' . $hexColor;
		}
		if ( empty($hexColor) || $hexColor == "#") {
			$hexColor = '#6ba342';
		}
		?>
		<style type="text/css">
			.libsyn_mylibsyn{
			  --mylibsyn-color-main: <?php echo $hexColor; ?> !important;
			}
		</style>
		<?php
	}

  	/**
  	 * Processing widget options on saves
  	 *
  	 * @since 1.3.1
  	 * @param  array $new_instance New Instance of WP_Widget
  	 * @param  array $old_instance Old Instance of WP_Widget
  	 * @return void
  	 */
  	public function update( $new_instance, $old_instance ) {
  		//processing widget options to be saved
		$instance = $old_instance;
	    $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
		if ( function_exists('sanitize_hex_color') ) {
			$instance[ 'custom_color' ] = sanitize_hex_color( $new_instance[ 'custom_color' ] );
		}
	    return $instance;
  	}
}
?>
