<?php
/**
 * Javascript for admin pages
 * 
 * @package bbPressKR
 * @subpackage Meta Admin JS
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

?>

<script type="text/javascript">
// Custom Fields
var bbpmeta_reorder;
(function($){
if ( $('#bbpmetafields').length ) {
	bbpmeta_reorder = function(rres, s) {
		var deleted = s.element || 'bbpmeta-tmp';

		$("#the-list-bbpmeta tr:visible").not('#'+deleted).find("td.column-order").each(function(idx){
			// console.log( rres, _s, idx, $(this).find('input').val() );
			$(this).find('span').text(idx);
			$(this).find('input').val(idx);
		});
	};
	$( 'input#bbpmeta_no_js' ).remove();
	$( '#the-list-bbpmeta' ).wpList( { addAfter: function() {
		$('table#list-table-bbpmeta').show();
		bbpmeta_reorder();
	}, addBefore: function( s ) {
		s.data += '&post_id=' + $('#post_ID').val();
		return s;
	}, delAfter: bbpmeta_reorder
	} );
	$("#the-list-bbpmeta").sortable({
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
			var postVars, mids = $("#the-list-bbpmeta tr:visible").map(function(){return this.id;}).get().join(',');
			postVars = {
				action: 'bbpmeta-order',
				_ajax_nonce: $('#bbpmeta-order-nonce').val(),
				order: mids,
				post_id: $('#post_ID').val()
			};
			$.post( ajaxurl, postVars, bbpmeta_reorder );
		}
	});
}
})(jQuery);
</script>
