<?php

/**
 * Forums Loop
 *
 * @package bbPress
 * @subpackage Theme
 */

?>

<?php do_action( 'bbp_template_before_forums_loop' ); ?>

	<?php if ( /*!bbp_is_forum_category() &&*/ bbp_has_topics() ) : ?>

		<?php //bbp_get_template_part( 'pagination', 'topics'    ); ?>

		<?php bbp_get_template_part( 'loop',       'topics'    ); ?>

		<?php bbp_get_template_part( 'pagination', 'topics'    ); ?>

		<?php //bbp_get_template_part( 'form',       'topic'     ); ?>

	<?php //elseif ( !bbp_is_forum_category() ) : ?>
	<?php else : ?>

		<?php bbp_get_template_part( 'feedback',   'no-topics' ); ?>

		<?php //bbp_get_template_part( 'form',       'topic'     ); ?>

	<?php endif; ?>


<?php do_action( 'bbp_template_after_forums_loop' ); ?>
