jQuery(document).ready(function($){
	$( "#libsyn-upload-media-dialog" ).dialog({
		autoOpen: false,
		draggable: true,
		height: 'auto',
		width: 500,
		modal: true,
		resizable: false,
		open: function(){
			setOverlays();
			$(".ui-widget-overlay").bind('click',function(){
				$("#libsyn-upload-media-dialog").dialog('close');
			})
		},
		buttons: [
			{
				id: "dialog-button-cancel",
				text: "Cancel",
				click: function(){
					$('#libsyn-upload-media-dialog').dialog('close');
				}
			},
			{
				id: "dialog-button-upload",
				text: "Upload",
				class: "button-primary",
				click: function(){
					$("#dialog-button-upload").prop("disabled", true);
					var dlg = $(this);
					$("#libsyn-media-progressbox-area").show();
					$('#libsyn-upload-media-error-text').html('');
					var mediaUploadForm = new FormData();
					mediaUploadForm.append('show_id', libsyn_nmp_data.api.show_id);
					mediaUploadForm.append('form_access_token', libsyn_nmp_data.api.access_token);
					mediaUploadForm.append('upload', $('#libsyn-media-file-upload')[0].files[0]);
					$.ajax({
						url: libsyn_nmp_data.url,
						type: 'POST',
						data: mediaUploadForm,
						processData: false,
						contentType: false,
						success: function (response, textStatus, xhr) {
							$("#libsyn-new-media-media").val("libsyn-upload-" + response._embedded.media.content_id).prop("readonly", true);
							$("#libsyn-media-progressbox-area").slideUp();
							$("#libsyn-preview-media-button").hide();
							dlg.dialog('close');

							//add preview
							var file_class = response._embedded.media.file_class;
							var mime_type = response._embedded.media.mime_type;
							mime_type = mime_type.replace("x-","");
							var media_url = response._embedded.media.secure_url;
							var preview_url = media_url.replace("libsyn.com/","libsyn.com/preview/");
							var media_show_id = response._embedded.media.show_id;

							if ( typeof media_show_id != 'undefined')  {
								$("#libsyn-new-media-media").attr({ 'data-show-id':  media_show_id });
							}

							if(file_class == 'audio' || file_class == 'video'){
								if(mime_type != 'undefined' && preview_url != 'undefined'){
									var previewHTML = '<'+file_class+' width="400" controls>';
									previewHTML += '<source src="'+preview_url+'" type="'+mime_type+'">'
									previewHTML += 'Your browser does not support HTML5 audio/video</'+file_class+'>';
									$("#libsyn-upload-media-preview").empty().html(previewHTML);
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
								$("#libsyn-upload-media-error-text").html(error);
								$("#libsyn-upload-media-error").show();
								$("#libsyn-media-progressbox-area").hide();
								$("#libsyn-upload-media-preview").empty();
							} else {
								$(".upload-error-dialog").empty().append("Error Uploading:  " + error);
								$("#libsyn-media-progressbox-area").hide();
								$("#libsyn-upload-media-preview").empty();
							}

							$("#upload-dialog-spinner").hide();
							$("#dialog-button-upload").prop("disabled", false);
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
			}
		]
	});

	$("#libsyn-upload-media").click(function(event) {
		event.preventDefault();
		document.getElementById('libsyn-new-media-media').type = 'text';
		$("#libsyn-upload-media-dialog").dialog( "open" );
	});

});
