<?php
/* Set Notifications */
global $libsyn_notifications;
do_action('libsyn_notifications');
?>
<?php wp_enqueue_script( 'jquery-ui-dialog', array('jquery-ui')); ?>
<?php wp_enqueue_style( 'wp-jquery-ui-dialog'); ?>
<?php wp_enqueue_style( 'libsyn-meta-boxes', '/wp-content/plugins/'.LIBSYN_DIR.'/lib/css/libsyn/meta_boxes.css' ); ?>
<?php wp_enqueue_style( 'libsyn-meta-form', '/wp-content/plugins/'.LIBSYN_DIR.'/lib/css/libsyn/meta_form.css' ); ?>
<?php wp_enqueue_style( 'libsyn-dashicons', '/wp-content/plugins/'.LIBSYN_DIR.'/lib/css/libsyn/dashicons.css' ); ?>
<h2><?php _e("Libsyn Wordpress Plugin Support", LIBSYN_DIR); ?></h2>

<div id="poststuff">
	<div id="post-body">
		<div id="post-body-content">
		<!-- BOS Initial Setup -->
		  <div class="stuffbox" style="width:93.5%">
			<h3 class="hndle"><span><?php _e("Initial Setup", LIBSYN_DIR); ?></span></h3>
			<div class="inside" style="margin: 15px;">
				<h4>Setting up a new Wordpress Account</h4>
				<div class="inside supportDiv">
					<ul>
						<li>
							<?php _e("You will need to setup an account with Libsyn if you don't have one already to host your podcast.  Please visit ", LIBSYN_TEXT_DOMAIN); ?><a href="//www.libsyn.com">http://www.libsyn.com</a><?php _e(" to setup an account.", LIBSYN_TEXT_DOMAIN); ?>
						</li>
						<li><?php _e("Then go to \"Manage WordPress Plugins\".", LIBSYN_TEXT_DOMAIN); ?>
							<?php _e("Within your Libsyn account navigate to ", LIBSYN_TEXT_DOMAIN); ?><strong><?php _e("Click on the green arrow in the upper right hand corner</strong> then go to ", LIBSYN_TEXT_DOMAIN); ?><strong><?php _e("Manage Wordpress Plugins", LIBSYN_TEXT_DOMAIN); ?></strong><?php _e(" to continue.", LIBSYN_TEXT_DOMAIN); ?>
							<br><img src="<?php _e(plugins_url(LIBSYN_DIR . '/lib/images/support/support_ss1.png'));?>" alt="Add new Wordpress Plugin">
						</li>
						<li><?php _e("Choose an Application Name and the Domain of your Wordpress site.", LIBSYN_TEXT_DOMAIN); ?><br></li>
						<p></p><br><img src="<?php _e(plugins_url(LIBSYN_DIR . '/lib/images/support/support_ss2.png'));?>" alt="Wordpress Plugin Added"><p></p>
						<li><?php _e("Navigate to the Libsyn Podcast Plugin ", LIBSYN_TEXT_DOMAIN); ?><a href="/wp-admin/admin.php?page=LibsynSupport"><?php _e("Settings", LIBSYN_TEXT_DOMAIN); ?></a><?php _e(" page.  Enter the above client id and secret and follow the login procedure to connect Wordpress to your Libsyn account.  Before posting make sure to choose your show from the Settings page after successfully connecting the plugin.", LIBSYN_TEXT_DOMAIN); ?></li>
					</ul>
				</div>
			</div>
		  </div>
		<!-- EOS Initial Setup -->
		<!-- BOS Usage -->
		  <div class="stuffbox" style="width:93.5%">
			<h3 class="hndle"><span><?php _e("Usage", LIBSYN_TEXT_DOMAIN); ?></span></h3>
			<div class="inside" style="margin: 15px;">
				<h4>Creating/Editing a New Podcast Post</h4>
				<div class="inside supportDiv">
					<ul>
						<li>
							<p>
								<?php _e("Navigate to the ", LIBSYN_TEXT_DOMAIN); ?><a href="/wp-admin/post-new.php"><?php _e("Post Episode", LIBSYN_TEXT_DOMAIN); ?></a><?php _e(" page.  Once the post page is loaded, you should see the ", LIBSYN_TEXT_DOMAIN); ?><strong><?php _e("Post Episode", LIBSYN_TEXT_DOMAIN); ?></strong><?php _e(" form.", LIBSYN_TEXT_DOMAIN); ?>
							</p>
							<img src="<?php echo plugins_url(LIBSYN_DIR . '/lib/images/support/support_ss3.png'); ?>" alt="Post Form">
							<p></p>
						</li>
						<li>
							<?php _e("The Libsyn Wordpress Plugin uses the Wordpress Post title as the title of your podcast episode and body as the episode description.  Fill out the fields in the form above to post a new episode.  **If you do not check the box <strong>Post Libsyn Episode<strong> this will not post a new podcast episode, but will post a new Wordpress post as normal.", LIBSYN_TEXT_DOMAIN); ?>
						</li>
					</ul>
				</div>
				<hr />
				<h4><?php _e("Adding a Podcast Playlist into Post", LIBSYN_TEXT_DOMAIN); ?></h4>
				<div class="inside supportDiv">
					<ul>
						<li>
							<p><?php _e("Navigate to the ", LIBSYN_TEXT_DOMAIN); ?><a href="/wp-admin/post-new.php"><?php _e("Post Episode", LIBSYN_TEXT_DOMAIN); ?></a><?php _e(" page.  You will see a button below the title called ", LIBSYN_TEXT_DOMAIN); ?><strong><?php _e("Add Podcast Playlist", LIBSYN_TEXT_DOMAIN); ?></strong><?php _e(", this will open a new dialog to ", LIBSYN_TEXT_DOMAIN); ?><strong><?php _e("Create Podcast Playlist", LIBSYN_TEXT_DOMAIN); ?></strong>.</p>
							<img src="<?php _e(plugins_url(LIBSYN_DIR . '/lib/images/support/support_ss4.png'));?>" alt="Create Podcast Playlist">
							<p></p>
						</li>
						<li>
							<p><strong><?php _e("Playlist Type:", LIBSYN_TEXT_DOMAIN); ?></strong>&nbsp;&nbsp;<?php _e("Choose audio or video based on your podcast.  (If you have both audio and Video, choose Video.)", LIBSYN_TEXT_DOMAIN); ?></p>
							<p>
								<?php _e("This plugin supports either your Libsyn Podcast or inserting an rss of any other podcast.  If you would like to include a playlist of a non-Libsyn podcast choose <strong>Other Podcast</strong> and paste the link to the podcast rss feed below.", LIBSYN_TEXT_DOMAIN); ?>
							</p>
						</li>
					</ul>
				</div>
				<hr />
		  </div>
		<!-- EOS Usage -->
		<!-- BOS Integration -->
		  <?php //TODO: Set "stuffbox" to display:none; remove this for the support of the integraqtion ?>
		  <div class="stuffbox" style="width:93.5%;display:none;">
			<h3 class="hndle"><span><?php _e("Integration", LIBSYN_DIR); ?></span></h3>
			<div class="inside supportDiv" style="margin: 15px;">
			<p>
				<?php _e("Migrating from the PowerPress Plugin to Libsyn Plugin.  We offer full support to migrate your exisiting podcast when hosting with Libsyn.", LIBSYN_TEXT_DOMAIN); ?>
			</p>
			<p><?php _e("The Powerpress plugin will need to be active at the time of integration.  You will only need to provide your Powerpress feed url for submission for integration to the Libsyn Wordpress Plugin.  This will do a couple of things, first it will enable your existing podcast feed url to be redirected to the new one (if applicable).  Then it will automatically update all your Podcast's hosting episodes be available using the Libsyn as the Podcast Feed Host (Again, if applicable).</p>
			<p>After selecting a show and submitting your Powerpress feed url, you will be ready for use with the Libsyn Podcast Plugin!  You may now deactivate the Powerpress plugin at this time.", LIBSYN_TEXT_DOMAIN); ?></p>

			</div>
		  </div>
		<!-- EOS Integration -->
		<div>
	<div>
<div>
