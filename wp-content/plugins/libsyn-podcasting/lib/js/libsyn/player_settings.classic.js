jQuery(document).ready(function($){
	$( "#libsyn-player-settings-page-dialog" ).dialog({
		autoOpen: false,
		draggable: true,
		height: 'auto',
		width: 620,
		modal: true,
		resizable: false,
		show: {
			effect: "scale",
			duration: 500
		},
		hide: {
			effect: "clip",
			duration: 500
		},
		open: function(event, ui) {
			if(typeof event.zIndex == 'undefined') {
				event.zIndex = 1002;
			}
			$('#wp-content-wrap').css('z-index', 1);
			$('.ui-widget-overlay').each(function() {
				$(this).css('z-index', 999);
				$(this).attr('style', 'z-index:999 !important;');
				if(($(this).css("z-index") != typeof 'undefined') && $(this).css("z-index") >= 1000) {
					//worse case scenario hide the overlays
					$(this).fadeOut('fast');
				}
			});
			$('.ui-dialog-title').each(function() {
				$(this).css('z-index', 1002);
			});
			
			$('#player-settings-submit').hide();
			$('#player-description-text').empty().append('<em>Player settings for this post.  **Note these settings will be changed only for this post.  You can modify the default player settings on the Libsyn Podcasting settings page.</em>');
			
			if($('#player-settings-input-div').length > 0) {
				$('#player-settings-input-div').empty();
			} else {
				$(".libsyn-post-form").append('<div id="player-settings-input-div" class="hidden"></div>');
			}
			$('.ui-widget-overlay').bind('click',function(){
				updateFormWithSettings();
				if( $('#libsyn-player-settings-page-dialog').is(':ui-dialog') ) {
					$('#libsyn-player-settings-page-dialog').dialog('close');
				}
			});
			$('#player_settings_title').hide();
		},
		close: function() {
			$('#libsyn-player-settings-page-dialog').dialog('close');
		},
		buttons: [
			{
				id: "dialog-player-settings-button-cancel",
				text: "Cancel",
				click: function(){
					updateFormWithSettings();
					$('#libsyn-player-settings-page-dialog').dialog('close');
				},
			},
			{
				id: "dialog-button-insert",
				text: "Use Custom Settings",
				class: "button-primary",
				click: function(){
					var dlgPlayerSettings = $(this);
					updateFormWithSettings();
					dlgPlayerSettings.dialog('close');
				}
			}
		]
	});
	
	var updateFormWithSettings = function() {
		//player_use_thumbnail
		if($('#player_use_thumbnail').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_thumbnail" value="use_thumbnail">');
		} else {
			$("#player-settings-input-div").append('<input name="player_use_thumbnail" value="">');
		}
		
		//player_use_theme
		if($('#player_use_theme_standard').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="standard" type="hidden">');
		} else if($('#player_use_theme_mini').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="mini" type="hidden">');
		} else if($('#player_use_theme_custom').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="custom" type="hidden">');
		} else {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="" type="hidden">');
		}
		
		//player_width
		var playerSettingsWidth = $('#player_width').val();
		$("#player-settings-input-div").append('<input name="player_width" value="' + playerSettingsWidth + '" type="hidden">');
		
		
		//player_height
		var playerSettingsHeight = $('#player_height').val();
		$("#player-settings-input-div").append('<input name="player_height" value="' + playerSettingsHeight + '" type="hidden">');
		
		//player_placement
		if($('#player_placement_top').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_placement" value="top" type="hidden">');
		} else if($('#player_placement_bottom').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_placement" value="bottom" type="hidden">');
		} else {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="" type="hidden">');
		}
		
		//player_use_download_link
		if($('#player_use_download_link').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_download_link" value="use_download_link">');
			var playerUseDownloadLinkText = $('#player_use_download_link_text').val();
			$("#player-settings-input-div").append('<input name="player_use_download_link_text" value="' + playerUseDownloadLinkText + '" type="hidden">');
		$("#player-settings-input-div").append('<input name="player_width" value="' + playerSettingsWidth + '" type="hidden">');
		} else {
			$("#player-settings-input-div").append('<input name="player_use_download_link" value="">');
			$("#player-settings-input-div").append('<input name="player_use_download_link_text" value="" type="hidden">');
		}
		
		//player_custom_color
		var playerSettingsCustomColor = $('#player_custom_color').val();
		$("#player-settings-input-div").append('<input name="player_custom_color" value="' + playerSettingsCustomColor + '" type="hidden"><div id="player_custom_color_picker_container" style="padding: 0px 0px 0px 0px; width:100%; margin-left:40px;"></div>');
		
	};
	
	var playerSettingsButton = $("<button/>",
	{
		text: " Libsyn Player Settings",
		click: function(event) {
			event.preventDefault();
			$("#libsyn-player-settings-page-dialog").dialog( "open" );
		},
		class: "button",
		"data-editor": "content",
		"font": "400 18px/1 dashicons"
	}).prepend("<span class=\"dashicons dashicons-format-video wp-media-buttons-icon libsyn-format-video\"></span>");
	
	$("#wp-content-media-buttons").append(playerSettingsButton);
	if( $("#wp-content-editor-tools").is(":visible") ) {
		//visible.. looks good
		if ( !!document.getElementById('wp-content-media-buttons') ) {
			//visible.. looks good
		} else {
			$("#player_settings_button_bottom").empty().append(playerSettingsButton);
			$("#player_settings_button_bottom_tr").fadeIn('fast');
		}
	} else { //try to set the player settings button inside the post area
		$("#player_settings_button_bottom").empty().append(playerSettingsButton);
		$("#player_settings_button_bottom_tr").fadeIn('fast');
	}
	
});