<?php

class Duoshuo_Widget_Recent_Comments extends WP_Widget {
	// Widget setup.
	function __construct() {
		/* Widget settings. */
		$widget_options = array(
			'classname' => 'ds-widget-recent-comments',
			'description' => '最新评论(由多说提供)'
		);

		/* Create the widget. */
		parent::__construct( 'ds-widget-recent-comments', '最新评论(多说)', $widget_options);
	}
	
	// outputs the content of the widget
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );
		$meta = $instance['meta'];
		if(!is_user_logged_in() || (is_user_logged_in() && $meta)) {
		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

		if (!is_user_logged_in()) {
			wp_connect();
		} else { ?>
			<ul>
			<?php wp_register(); ?>
			<li><?php wp_loginout(); ?></li>
			</ul>
        <?php }
		echo $after_widget;
		}
	}

	// processes widget options to be saved
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['meta'] = $new_instance['meta'];
		return $instance;
	}

    // outputs the options form on admin
	function form( $instance ) {
		$title = esc_attr($instance['title']);
		$meta = esc_attr($instance['meta']);
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">标题：</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
		</p>
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'meta' ); ?>" name="<?php echo $this->get_field_name( 'meta' ); ?>"  value="1" <?php if($meta) echo "checked "; ?> />
			<label for="<?php echo $this->get_field_id( 'meta' ); ?>">登录后显示站点管理和退出链接</label>
		</p>
	<?php
	}
}