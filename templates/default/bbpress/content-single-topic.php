<?php

/**
 * Single Topic Content Part
 *
 * @package bbPress
 * @subpackage Theme
 */

?>

<div id="bbpress-forums">

	<?php // bbp_breadcrumb(); ?>

	<?php do_action( 'bbp_template_before_single_topic' ); ?>

	<?php if ( post_password_required() ) : ?>

		<?php bbp_get_template_part( 'form', 'protected' ); ?>

	<?php else : ?>

		<?php // bbp_topic_tag_list(); ?>

		<?php // bbp_single_topic_description(); ?>

		<?php if ( bbp_show_lead_topic() ) : ?>

			<?php bbp_get_template_part( 'content', 'single-topic-lead' ); ?>

		<?php endif; ?>

		<?php if ( bbp_user_can_comment() ) : ?>

			<div id="topic-<?php bbp_topic_id(); ?>-comments" class="forums bbp-comments">
				<?php comments_template(); ?>
			</div>

		<?php elseif ( !bbpresskr()->forum_option('use_comments') ) : ?>

		<?php if ( bbp_has_replies() ) : ?>

			<?php // bbp_get_template_part( 'pagination', 'replies' ); ?>

			<?php bbp_get_template_part( 'loop',       'replies' ); ?>

			<?php //bbp_get_template_part( 'pagination', 'replies' ); ?>

		<?php endif; ?>

		<?php bbp_get_template_part( 'nav', 'topic' ); ?>

		<?php bbp_get_template_part( 'form', 'reply' ); ?>

		<?php endif; ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_single_topic' ); ?>

</div>
