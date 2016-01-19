<?php
/**
 * Nav buttons (edit, write, list, prev, next)
 * @package bbPressKR
 * @subpackage template
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

?>

<?php do_action( 'bbp_template_before_nav_topic' ); ?>

<div class="bbp-nav-topic">
	<div class="bbp-nav-links">

		<?php bbp_topic_pagination_count(); ?>

	</div>

	<div class="bbp-nav-buttons">

		<?php bbp_topic_pagination_links(); ?>

	</div>
</div>

<?php do_action( 'bbp_template_after_nav_topic' ); ?>
