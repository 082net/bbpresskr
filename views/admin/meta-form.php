<?php
/**
 * New meta fields form
 * 
 * @package bbPressKR
 * @subpackage Admin
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */
if ( !defined('ABSPATH') ) die('HACK');

		global $wpdb;
	?>
	<p><strong><?php _e( 'Add New Forum Meta:', 'bbpresskr' ) ?></strong></p>
	<table id="newbbpmeta" class="widefat">
	<thead>
	<tr>
	<th class="bbpleft"><label for="bbpmeta_key"><?php _e( 'Meta Key', 'bbpresskr' ) ?></label></th>
	<th><label for="bbpmeta_label"><?php _e( 'Meta Label', 'bbpresskr' ) ?></label></th>
	<th><label for="bbpmeta_list"><?php _e( 'List', 'bbpresskr' ) ?></label></th>
	<!-- <th><label for="bbpmeta_adminlist"><?php _e( 'Admin List', 'bbpresskr' ) ?></label></th> -->
	<th><label for="bbpmeta_single"><?php _e( 'Single', 'bbpresskr' ) ?></label></th>
	</tr>
	</thead>

	<tbody>
	<tr>
	<td id="newmetaleft" class="bbpleft">
	<input type="hidden" id="bbpmeta_no_js" name="bbpmeta_no_js" value="1" />
	<input type="hidden" name="bbpmeta_order" value="<?php echo $order; ?>" />
	<input type="text" class="widefat" id="bbpmeta_key" name="bbpmeta_key" value="" />
	</td>
	<td>
	<input type="text" class="widefat" id="bbpmeta_label" name="bbpmeta_label" value="" />
	</td>
	<td>
	<input type="checkbox" id="bbpmeta_list" name="bbpmeta_list" value="1" />
	</td>
	<!-- <td>
	<input type="checkbox" id="bbpmeta_adminlist" name="bbpmeta_showadminlist" value="1" />
	</td> -->
	<td>
	<select id="bbpmeta_single" name="bbpmeta_single">
		<option value=""><?php _e('None', 'bbpresskr') ?></option>
		<option value="top" selected="selected"><?php _e('Top', 'bbpresskr') ?></option>
		<option value="bottom"><?php _e('Bottom', 'bbpresskr') ?></option>
	</select>
	</td>
	</tr>

	<tr><td colspan="5">
	<div class="submit">
	<?php submit_button( __( 'Add Meta Field', 'bbpresskr' ), 'secondary', 'addbbpmeta', false, array( 'id' => 'newbbpmeta-submit', 'data-wp-lists' => 'add:the-list-bbpmeta:newbbpmeta' ) ); ?>
	</div>
	<?php wp_nonce_field( 'add-bbpmeta', '_ajax_nonce-add-bbpmeta', false ); ?>
	</td></tr>
	</tbody>
	</table>
	<?php
