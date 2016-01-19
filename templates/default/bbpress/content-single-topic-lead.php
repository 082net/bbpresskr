<?php

/**
 * Single Topic Lead Content Part
 *
 * @package bbPress
 * @subpackage Theme
 */

?>

<?php do_action( 'bbp_template_before_lead_topic' ); ?>

<br class="clear" />

<?php bbp_get_template_part( 'topic-info', 'top' ); ?>


<div class="topic-body">

	<div id="bbp-topic-<?php bbp_topic_id(); ?>" <?php bbp_topic_class('bbp-lead-topic'); ?>>

		<div class="bbp-topic-content">

			<?php do_action( 'bbp_theme_before_topic_content' ); ?>

			<?php bbp_topic_content(); ?>

			<?php do_action( 'bbp_theme_after_topic_content' ); ?>

			<?php
			$args = array (
				'id'     => 0,
				'before' => '<span class="bbp-admin-links">',
				'after'  => '</span>',
				'sep'    => ' ',
				'links'  => array()
			);
			bbp_topic_admin_links( $args ); ?>
			<?php //bbp_topic_admin_links(); ?>

		</div><!-- .bbp-topic-content -->

	</div><!-- #post-<?php bbp_topic_id(); ?> -->

</div>

<?php do_action( 'bbp_template_after_lead_topic' ); ?>
