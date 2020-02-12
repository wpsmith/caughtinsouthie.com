( function($) {
	$(window).bind("load", function() {
		if (typeof libsyn_nmp_data.libsyn_edit_item !== 'undefined') {
			wp.data.dispatch( 'core/editor' ).editPost( { title: libsyn_nmp_data.libsyn_edit_item.item_title } );
			var el = wp.element.createElement;
			var libsynContentBlock = wp.blocks.createBlock('core/paragraph', {
				content: libsyn_nmp_data.libsyn_edit_item.body,
			});
			var libsynPublisherBlock = wp.blocks.createBlock('cgb/block-libsyn-podcasting-gutenberg', {
				content: ''
			});
			wp.data.dispatch('core/block-editor').insertBlocks(libsynContentBlock);
			wp.data.dispatch('core/block-editor').insertBlocks(libsynPublisherBlock);
		}
	});
} ) (jQuery);
