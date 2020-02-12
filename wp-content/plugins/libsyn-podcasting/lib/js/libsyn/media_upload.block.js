var libsynUploadPrimaryMedia;
( function($) {
	libsynUploadPrimaryMedia = function ( event ) {
		$("#libsyn-media-progressbox-area").show();
		$("#libsyn-upload-media-error-text").empty();
		$(".upload-error-dialog").empty();
		if ( typeof event.target.files[0] !== 'undefined') {
			var mediaUploadForm = new FormData();
			mediaUploadForm.append('show_id', libsyn_nmp_data.api.show_id);
			mediaUploadForm.append('form_access_token', libsyn_nmp_data.api.access_token);
			mediaUploadForm.append('upload', event.target.files[0]);

			//add filename text
			if ( (typeof event.target.files[0].name !== 'undefined') &&  event.target.files[0].name.length >= 1 ) {
				if ( event.target.files[0].type.match('audio.*') !== null ) {
					var dashicon_modal_html = '<span class="dashicons dashicons-media-audio" style="font-size:40px;width:40px;height:40px;overflow:visible;"></span>';
					var dashicon_form_html = '<span class="dashicons dashicons-media-audio" style="font-size:30px;width:30px;height:30px;overflow:visible;"></span>';
				} else if ( event.target.files[0].type.match('video.*') !== null ) {
					var dashicon_modal_html = '<span class="dashicons dashicons-media-video" style="font-size:40px;width:40px;height:40px;overflow:visible;"></span>';
					var dashicon_form_html = '<span class="dashicons dashicons-media-video" style="font-size:30px;width:30px;height:30px;overflow:visible;"></span>';
				}
				var filename_modal_html = '<p style="color:black;font-size:20px;position:absolute;left:2%;overflow:visible;font-family:\'Segoe UI\',Roboto,Ubuntu,sans-serif;margin-top:2px;">' + dashicon_modal_html + event.target.files[0].name + '</p>';
				var filename_form_html = '<p style="color:black;overflow:visible;line-height:2;margin:0;font-family:\'Segoe UI\',Roboto,Ubuntu,sans-serif;">' + dashicon_form_html + event.target.files[0].name + '</p>';
				$("#libsyn-media-progressbox-area").find(".libsyn-dots").empty().html(filename_modal_html);
			}
		}

		$.ajax({
			url: libsyn_nmp_data.url,
			type: 'POST',
			data: mediaUploadForm,
			processData: false,
			contentType: false,
			success: function (response, textStatus, xhr) {
				var currentBlock = wp.data.select('core/block-editor').getSelectedBlock();
				if ( !!currentBlock && !!currentBlock.name ) {
					if (currentBlock.name === 'cgb/block-libsyn-podcasting-gutenberg') {
						currentBlock.attributes.libsynNewMediaMedia = "libsyn-upload-" + response._embedded.media.content_id;
						wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( currentBlock.clientId, { libsynNewMediaMedia: currentBlock.attributes.libsynNewMediaMedia } );
					}
				}
				libsyn_nmp_data.libsyn_new_media_media = "libsyn-upload-" + response._embedded.media.content_id;
				libsyn_nmp_data.error.media_show_mismatch = false;
				$.get(libsyn_nmp_ftp.admin_ajax_url + "?action=update_libsyn_postmeta&update_libsyn_postmeta=1&post_id=" + libsyn_nmp_ftp.post_id + "&meta_key=libsyn-new-media-media&meta_value=libsyn-upload-" + response._embedded.media.content_id);

				$("#libsyn-new-media-media").val("libsyn-upload-" + response._embedded.media.content_id).attr("readonly", true);
				$("#libsyn-media-progressbox-area").slideUp();
				$("#libsyn-preview-media-button").hide();
				$(".libsyn-upload-media-dialog").find(".components-icon-button").click();

				//add preview
				var file_class = response._embedded.media.file_class;
				var mime_type = response._embedded.media.mime_type;
				mime_type = mime_type.replace("x-","");
				var media_url = response._embedded.media.secure_url;
				var preview_url = media_url.replace("libsyn.com/","libsyn.com/preview/");

				if(file_class == 'audio' || file_class == 'video'){
					if(mime_type != 'undefined' && preview_url != 'undefined'){
						var previewHTML = '<' + file_class + ' width="400" controls>';
						previewHTML += '<source src="' + preview_url + '" type="' + mime_type + '">'
						previewHTML += 'Your browser does not support HTML5 audio/video</' + file_class + '>';
						if ( typeof filename_form_html == 'undefined' ) {
							filename_form_html = "";
						}
						$("#libsyn-upload-media-preview").empty().html('<p style="margin:0;">' + previewHTML + '</p>' + filename_form_html);
					}
				}
			},
			 error: function (xhr, status, error) {
				if((typeof xhr.responseJSON.status !== 'undefined') && xhr.responseJSON.status == '403') {
					$(".upload-error-dialog").empty().append("Error Uploading:  " + error);
					$("#libsyn-media-progressbox-area").hide();
					$("#libsyn-upload-media-preview").empty();
				} else if((typeof xhr.responseJSON.validation_messages !== 'undefined') && xhr.responseJSON.validation_messages.upload.length >= 0) {
					var stringError = xhr.responseJSON.validation_messages.upload;
					$(".upload-error-dialog").empty().append(
						"Error Uploading:  " + xhr.responseJSON.validation_messages.upload
					);
					$("#libsyn-upload-media-error-text").html("<p>" + error + "</p>");
					$("#libsyn-upload-media-error").show();
					$("#libsyn-media-progressbox-area").hide();
					$("#libsyn-upload-media-preview").empty();
				} else {
					$(".upload-error-dialog").empty().append("Error Uploading:  " + error);
					$("#libsyn-media-progressbox-area").hide();
					$("#libsyn-upload-media-preview").empty();
				}

				$("#upload-dialog-spinner").hide();
				$("#dialog-button-upload").attr("disabled", false);
				$(".upload-error-dialog").fadeIn('normal');
			},
			xhr: function() {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener("progress", function(evt) {
					if (evt.lengthComputable) {
						var completed = evt.loaded / evt.total;
						var percentComplete = Math.floor(completed * 100);
						$("#libsyn-media-statustxt").html(percentComplete + '%');
						$("#libsyn-media-progressbar").width(percentComplete + '%');
					}
				}, false);
				return xhr;
			}
		});
	}
}) (jQuery);
