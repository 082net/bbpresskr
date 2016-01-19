<?php

/**
 * Single Topic Information
 *
 * @package bbPressKR
 * @subpackage Theme
 */

?>

<div class="topic-info-top topic-info-wrap">

	<ul class="topic-info">
		<?php if ( is_user_logged_in() ) { ?>
		<li class="topic-actions clearfix">
				<?php bbp_topic_subscription_link(); ?>
				<?php bbp_topic_favorite_link(); ?>
		</li>
		<?php } ?>
		<li class="topic-info-general clearfix">
			<div class="path fl"><a href="<?php bbp_forum_permalink( bbp_get_forum_id() ); ?>"><?php bbp_forum_title( bbp_get_topic_forum_id( bbp_get_topic_id() ) ); ?></a></div>
			<div class="date fr">
				<time datetime="<?php echo get_post_time( 'c', false, bbp_get_topic_id() ); ?>">
					<?php printf( _x( '%1$s at %2$s', '1: date, 2: time' ), get_post_time( bbpresskr()->forum_option('date_format'), false, bbp_get_topic_id() ), get_post_time( bbpresskr()->forum_option('time_format'), false, bbp_get_topic_id() ) ); ?>
				</time>
			</div>
			<div class="title"><?php bbp_topic_title(); ?></div>
		</li>
		<li class="topic-info-meta clearfix">
			<div class="author">
			<?php do_action( 'bbp_theme_before_reply_author_details' ); ?>

			<?php bbp_topic_author_link( array( 'type' => 'name', 'show_role' => false ) ); ?>

			<?php do_action( 'bbp_theme_after_reply_author_details' ); ?>

			<?php if ( bbp_is_user_keymaster() ) : ?>

				<?php do_action( 'bbp_theme_before_topic_author_admin_details' ); ?>

				<div class="ip"><?php bbp_author_ip( bbp_get_topic_id() ); ?></div>

				<?php do_action( 'bbp_theme_after_topic_author_admin_details' ); ?>

			<?php endif; ?>

			</div>

			<!-- <div class="author"><span><?php bbp_topic_author_link('type=name'); ?></span></div> -->
			<div class="meta-counts fr">
				<span class="hits"><?php _e('Hits:', 'bbpresskr'); ?><strong><?php bbpkr_topic_views(); ?></strong></span>
				<span class="favs"><?php _e('Favorites:', 'bbpresskr'); ?><strong><?php echo bbPressKR\Topic::favorites_counts(); ?></strong></span>
				<span class="replies"><?php _e('Replies:', 'bbpresskr'); ?><strong><?php bbp_topic_reply_count(); ?></strong></span>
			</div>
		</li>
		<?php if ( $bbpkr_extras = bbpkr_meta_fields(bbp_get_forum_id(), array('single' => 'top')) ) { ?>
		<li class="topic-info-extra clearfix">
			<ul class="topic-extra">
			<?php foreach ( $bbpkr_extras as $extra ) { ?>
			<?php $extra_val = get_post_meta(bbp_get_topic_id(), 'bbpmeta_' . $extra['key'], true); ?>
				<li class="<?php echo $extra['key']; ?>"><span class="topic-extra-label"><?php echo $extra['label']; ?>:</span> <span class="topic-extra-data"><?php echo $extra_val; ?></span></li>
			<?php } ?>
			</ul>
		</li>
		<?php } ?>
		<?php if ( /*bbpresskr()->forum_option(bbp_get_topic_id(), 'attachments') == 'top' &&*/ bbpkr_topic_has_attachments() ) { ?>
		<li class="topic-attachments clearfix">
			<?php bbpkr_embed_attachments( bbp_get_topic_id() ); ?>
		</li>
		<?php } ?>
	</ul>

	<?php do_action( 'bbpkr_topic_info', 'top' ); ?>
</div>
