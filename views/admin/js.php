<?php
/**
 * Javascript for admin pages
 * 
 * @package bbPressKR
 * @subpackage Admin Forum
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

wp_print_scripts( 'jquery-ui-tabs' );
?>

<script type="text/javascript">
// Custom Fields
(function($){
if ( $('#bbp-perms-tabs').length ) {
	$('#bbp-perms-tabs').tabs();
}
})(jQuery);
</script>
