<?php

class Duoshuo_Widget_Recent_Comments extends WP_Widget {
	
	function __construct() {
		$widget_ops = array('classname' => 'ds-widget-recent-comments', 'description' => '最新评论(由多说提供)' );
		parent::__construct('ds-recent-comments', '最新评论(多说)', $widget_ops);
		
		$this->alt_option_name = 'duoshuo_widget_recent_comments';

		if ( is_active_widget(false, false, $this->id_base) )
			add_action( 'wp_head', array(&$this, 'recent_comments_style') );

		//add_action( 'comment_post', array(&$this, 'flush_widget_cache') );
		//add_action( 'transition_comment_status', array(&$this, 'flush_widget_cache') );
	}

	function recent_comments_style() {
		if ( ! current_theme_supports( 'widgets' ) )// Temp hack #14876
			return;
		
		if (!did_action('wp_head') && !Duoshuo::$scriptsPrinted){
			Duoshuo::printScripts();
			Duoshuo::$scriptsPrinted = true;
		}
	}
	
	function widget( $args, $instance ) {
/*
array(10) {
  ["name"]=>
  string(7) "sidebar"
  ["id"]=>
  string(9) "sidebar-1"
  ["description"]=>
  string(0) ""
  ["class"]=>
  string(0) ""
  ["before_widget"]=>
  string(74) "<li id="recent-comments-2" class="boxed widget ds-widget-recent-comments">"
  ["after_widget"]=>
  string(5) "</li>"
  ["before_title"]=>
  string(24) "<h3 class="widgettitle">"
  ["after_title"]=>
  string(5) "</h3>"
  ["widget_id"]=>
  string(17) "recent-comments-2"
  ["widget_name"]=>
  string(12) "近期评论"
}

array(2) {
  ["title"]=>
  string(0) ""
  ["number"]=>
  int(5)
}*/
		global $comments, $comment;

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

 		extract($args, EXTR_SKIP);
 		
 		$output = '';
 		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recent Comments' ) : $instance['title'], $instance, $this->id_base );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) )
 			$number = 10;

		$output .= $before_widget;
		if ( $title )
			$output .= $before_title . $title . $after_title;
		
		$data = array(
			'num_items'	=>	$number,
			'show_avatars'=>isset($instance['show_avatars']) ? $instance['show_avatars'] : 1,
			'show_time'=>	isset($instance['show_time']) ? $instance['show_time'] : 1,
			'show_title'=>	isset($instance['show_title']) ? $instance['show_title'] : 1,
			'show_admin'=>	isset($instance['show_admin']) ? $instance['show_admin'] : 1,
			'avatar_size'=>	32,
			'excerpt_length'=>$instance['excerpt_length'],
		);
		$attribs = '';
		foreach ($data as $key => $value)
			$attribs .= ' data-' . str_replace('_','-',$key) . '="' . esc_attr($value) . '"';
		$output .= '<ul class="ds-recent-comments"' . $attribs . '></ul>'
				. $after_widget;
		$output .= '<script>DUOSHUO.RecentCommentsWidget(\'.ds-recent-comments\')</script>';
		echo $output;
	}


	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = absint( $new_instance['number'] );
		$instance['excerpt_length'] = absint( $new_instance['excerpt_length'] );
		$instance['show_avatars'] =  absint( $new_instance['show_avatars'] );
		$instance['show_time'] =  absint( $new_instance['show_time'] );
		$instance['show_title'] =  absint( $new_instance['show_title'] );
		$instance['show_admin'] =  absint( $new_instance['show_admin'] );

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['duoshuo_widget_recent_comments']) )
			delete_option('duoshuo_widget_recent_comments');

		return $instance;
	}
	
	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
		$show_avatars = isset($instance['show_avatars']) ? absint( $instance['show_avatars']) : 1;
		$show_title = isset($instance['show_title']) ? absint($instance['show_title']) : 1;
		$show_time = isset($instance['show_time']) ? absint($instance['show_time']) : 1;
		$show_admin = isset($instance['show_admin']) ? absint($instance['show_admin']) : 1;
		$excerpt_length = isset($instance['excerpt_length']) ? absint($instance['excerpt_length']) : 70;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p>
			<input name="<?php echo $this->get_field_name('show_avatars'); ?>" type="hidden" value="0" />
			<input id="<?php echo $this->get_field_id('show_avatars'); ?>" name="<?php echo $this->get_field_name('show_avatars'); ?>" type="checkbox" value="1" <?php if ($show_avatars) echo 'checked="checked" '?>/>
			<label for="<?php echo $this->get_field_id('show_avatars'); ?>">显示头像</label>
		</p>
		
		<p>
			<input name="<?php echo $this->get_field_name('show_time'); ?>" type="hidden" value="0" />
			<input id="<?php echo $this->get_field_id('show_time'); ?>" name="<?php echo $this->get_field_name('show_time'); ?>" type="checkbox" value="1" <?php if ($show_time) echo 'checked="checked" '?>/>
			<label for="<?php echo $this->get_field_id('show_time'); ?>">显示评论时间</label>
		</p>
		
		<p>
			<input name="<?php echo $this->get_field_name('show_title'); ?>" type="hidden" value="0" />
			<input id="<?php echo $this->get_field_id('show_title'); ?>" name="<?php echo $this->get_field_name('show_title'); ?>" type="checkbox" value="1" <?php if ($show_title) echo 'checked="checked" '?>/>
			<label for="<?php echo $this->get_field_id('show_title'); ?>">显示文章标题</label>
		</p>
		
		<p>
			<input name="<?php echo $this->get_field_name('show_admin'); ?>" type="hidden" value="0" />
			<input id="<?php echo $this->get_field_id('show_admin'); ?>" name="<?php echo $this->get_field_name('show_admin'); ?>" type="checkbox" value="1" <?php if ($show_admin) echo 'checked="checked" '?>/>
			<label for="<?php echo $this->get_field_id('show_admin'); ?>">显示管理员评论</label>
		</p>
		
		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of comments to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
		
		
		<p><label for="<?php echo $this->get_field_id('excerpt_length'); ?>">引文字数(中文)：</label>
		<input id="<?php echo $this->get_field_id('excerpt_length'); ?>" name="<?php echo $this->get_field_name('excerpt_length'); ?>" type="text" value="<?php echo $excerpt_length; ?>" size="5" /></p>
<?php
	}
}
