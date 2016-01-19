/**
 * 
 * WestFS Theme Functions
 * @package     WestFS
 * 
 */

(function($){
if ( $('#bbpmetafields').length ) {
	$( '#the-list-bbpmeta' ).wpList( { addAfter: function() {
		$('table#list-table-bbp').show();
	}, addBefore: function( s ) {
		s.data += '&post_id=' + $('#post_ID').val();
		return s;
	}
	});
	$("#list-table-bbp tbody").sortable({
		placeholder: 'sortable-placeholder',
		items: 'tr',
		handle: '.hndle',
		cursor: 'move',
		distance: 2,
		tolerance: 'pointer',
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		stop:function(e,ui){
			var postVars, mids = $( '#list-table-bbp tbody' ).sortable( 'toArray' ).join( ',' );
			postVars = {
				action: 'bbpmeta-order',
				_ajax_nonce: $('#bbpmeta-order-nonce').val(),
				order: mids,
				post_id: $('#post_ID').val()
			};
			$.post( ajaxurl, postVars );
		}
	});
}
if ( $('#bbp-perms-tabs').length ) {
	$('#bbp-perms-tabs').tabs();
}
})(jQuery);
