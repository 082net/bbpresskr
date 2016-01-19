<?php

/**
 * Single Forum Content Part
 *
 * @package bbPress
 * @subpackage Theme
 */

?>

<div id="bbpress-forums">

	<?php //bbp_breadcrumb(); ?>

	<?php //bbp_forum_subscription_link(); ?>

	<?php do_action( 'bbp_template_before_single_forum' ); ?>

	<?php if ( post_password_required() ) : ?>

		<?php bbp_get_template_part( 'form', 'protected' ); ?>

	<?php else : ?>

		<?php if ( bbp_is_topic_write() ): ?>

			<?php bbp_get_template_part( 'form',       'topic'     ); ?>

		<?php else: ?>

		<?php //bbp_single_forum_description(); ?>

		<?php if ( bbp_has_forums() ) : ?>

			<?php bbp_get_template_part( 'loop', 'forums' ); ?>

		<?php endif; ?>

		<?php if ( !bbp_is_forum_category() && bbp_has_topics() ) : ?>

			<?php //bbp_get_template_part( 'pagination', 'topics'    ); ?>

			<?php bbp_get_template_part( 'loop',       'topics'    ); ?>

			<?php //bbp_get_template_part( 'pagination', 'topics'    ); ?>

			<?php //bbp_get_template_part( 'form',       'topic'     ); ?>
			<?php bbp_get_template_part( 'button', 'write' ); ?>

		<?php elseif ( !bbp_is_forum_category() ) : ?>

			<?php bbp_get_template_part( 'feedback',   'no-topics' ); ?>

			<?php //bbp_get_template_part( 'form',       'topic'     ); ?>
			<?php //bbp_get_template_part( 'button', 'write' ); ?>
			<?php bbp_topic_write_link(); ?>

		<?php elseif ( bbp_is_forum_category() ) : ?>

			<?php //bbp_get_template_part( 'loop',       'topics'    ); ?>

		<?php endif; ?>

		<?php endif;// bbp_is_topic_write() ?>

	<?php endif;// post_password_required() ?>

	<?php do_action( 'bbp_template_after_single_forum' ); ?>

</div>
