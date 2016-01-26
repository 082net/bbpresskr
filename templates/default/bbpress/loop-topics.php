<?php

/**
 * Topics Loop
 *
 * @package bbPress
 * @subpackage Theme
 */

?>

<?php do_action( 'bbp_template_before_topics_loop' ); ?>


<div class="tableinfo">
	<?php bbpresskr()->topic_list_table->views(); ?>
</div>

<?php bbpresskr()->topic_list_table->display(); ?>

<div class="tablenav">
	<?php bbpresskr()->topic_list_table->search_box( 'Search', 'post' ); ?>
</div>

<?php do_action( 'bbp_template_after_topics_loop' ); ?>

