<?php 
class smc_sidebar_login_widget extends WP_Widget{
	function smc_sidebar_login_widget(){
		$widget_des = array('classname'=>'smc-login-widget','description'=>'显示微博连接登陆按钮');
		$this->WP_Widget(false,'社交媒体网站连接登陆',$widget_des);
	}
	function form($instance){
		$instance = wp_parse_args((array)$instance,array(
			'title'=>'社交网站连接登陆',
			'smc_auto_js'=>1,
			'icon_size'=>24,
			'desc'=>''
		));
		echo '<p><label for="'.$this->get_field_name('title').'">标题: <br/><input class="widefat" name="'.$this->get_field_name('title').'" type="text" value="'.htmlspecialchars($instance['title']).'" /></label></p>';
		echo '<p><input '.($instance['smc_auto_js']?'checked="checked" ':'').'class="checkbox" name="'.$this->get_field_name('smc_auto_js').'" type="checkbox" value="1" /> <label for="'.$this->get_field_name('smc_auto_js').'">使用浮动面板</label></p>';
		echo '<p><label for="'.$this->get_field_name('icon_size').'">图标大小: <br/><input class="widefat" name="'.$this->get_field_name('icon_size').'" type="text" value="'.htmlspecialchars($instance['icon_size']).'" /></label></p>';
		echo '<p><label for="'.$this->get_field_name('desc').'">描述信息(支持html标签): <textarea rows="5" cols="20" name="'.$this->get_field_name('desc').'" class="widefat">'.htmlspecialchars($instance['desc']).'</textarea></label></p>';
	}
	function update($new_instance,$old_instance){
		$instance = $old_instance;
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['smc_auto_js'] = stripslashes($new_instance['smc_auto_js']);
		$instance['icon_size'] = stripslashes($new_instance['icon_size']);
		$instance['desc'] = stripslashes($new_instance['desc']);
		return $instance;
	}
	function widget($args,$instance){
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$smc_auto_js = (int)$instance['smc_auto_js'];
		$icon_size = (int)$instance['icon_size'];
		$desc = $instance['desc'];
		echo $before_widget;
		if($title)echo $before_title . $title . $after_title;
		if(!is_user_logged_in())echo $desc;
		$opt=array('is_comment'=>1,'showtips'=>0,'icon_size'=>$icon_size,'smc_auto_js'=>$smc_auto_js);
		if(!$smc_auto_js)$opt['list_style']=2;
		smc_connect($opt);
		echo $after_widget;
	}
}
class smc_weibo_timeline_widget extends WP_Widget{
	function smc_weibo_timeline_widget(){
		$widget_des = array('classname'=>'smc-weibo-timeline-widget','description'=>'显示你的微博消息');
		$this->WP_Widget(false,'最新微博(by社交媒体连接)',$widget_des);
	}
	function form($instance){
		$instance = wp_parse_args((array)$instance,array(
			'title'=>'我的微博',
			'domtag'=>'li',
			'weibo'=>'',
			'number'=>5,
			'type'=>'user_timeline',
			'cachetime'=>5,
			'length'=>200,
			'size'=>48,
			'showretweet'=>1,
			'format'=>'<div class="smc-avatar">%%avatar%%</div><div class="smc-author">%%author%%</div><div class="smc-excerpt">%%excerpt%%<br/><div class="smc-weibo-image">%%image%%</div></div><!--<div class="smc_date">%%source%% | <a href="%%url%%">%%date%%</a></div>-->'
		));
		$weibotok=get_option('weibo_access_token');
		echo '<p><label for="'.$this->get_field_name('title').'">标题: <br/><input class="widefat" name="'.$this->get_field_name('title').'" type="text" value="'.htmlspecialchars($instance['title']).'" /></label></p>';
		echo '<p><label for="'.$this->get_field_name('weibo').'">选择微博(你需要先<a href="'.smc_menu_page_url('social-medias-connect/function.php',false).'">绑定</a>才能在此选择微博): <select name="'.$this->get_field_name('weibo').'" class="widefat"><option '.(!$instance['weibo']?'selected="selected" ':'').'value="0"></option>';
		if($weibotok){
			foreach($weibotok as $weibo => $tok){
				echo '<option '.($instance['weibo']==$weibo?'selected="selected" ':'').'value="'.$weibo.'">'.smc_get_weibo_name($weibo).'</option>';
			}
		}
		echo '</select></label></p>';
		echo '<p><label for="'.$this->get_field_name('number').'">显示数量: <br/><input class="widefat" name="'.$this->get_field_name('number').'" type="text" value="'.htmlspecialchars($instance['number']).'" /></label></p>';
		echo '<p><label for="'.$this->get_field_name('type').'">消息类型: <select name="'.$this->get_field_name('type').'" class="widefat"><option '.($instance['type']=='user_timeline'?'selected="selected" ':'').'value="user_timeline">我自己的微博列表</option><option '.($instance['type']=='friends_timeline'?'selected="selected" ':'').'value="friends_timeline">我和我关注的人的微博</option><option '.($instance['type']=='public_timeline'?'selected="selected" ':'').'value="public_timeline">所有人的公共微博</option></select></label></p>';
		echo '<p><label for="'.$this->get_field_name('format').'">输出格式: <br/><textarea class="widefat" style="font-size:10px;-webkit-text-size-adjust:none;" rows="10" cols="20" name="'.$this->get_field_name('format').'">'.htmlspecialchars($instance['format']).'</textarea></label><div style="background:#EAEAEA;font-size:10px;-webkit-text-size-adjust:none;">格式说明：<br/>%%avatar%%: 头像<br/>%%author%%: 作者<br/>%%excerpt%%: 摘要<br/>%%date%%: 时间<br/>%%url%%: 地址<br/>%%source%%: 来源<br/>%%image%%: 图片(有图片时才显示)</div></p>';
		echo '<p><label for="'.$this->get_field_name('length').'">输出长度: <br/><input class="widefat" name="'.$this->get_field_name('length').'" type="text" value="'.htmlspecialchars($instance['length']).'" /></label></p>';
		echo '<p><label for="'.$this->get_field_name('size').'">头像尺寸: <br/><input class="widefat" name="'.$this->get_field_name('size').'" type="text" value="'.htmlspecialchars($instance['size']).'" /></label></p>';
		echo '<p><label for="'.$this->get_field_name('cachetime').'">缓存时间(单位: 分钟): <br/><input class="widefat" name="'.$this->get_field_name('cachetime').'" type="text" value="'.htmlspecialchars($instance['cachetime']).'" /></label></p>';
		echo '<p><input class="checkbox" name="'.$this->get_field_name('showretweet').'" type="checkbox"'.($instance['showretweet']?' checked="checked"':'').' /> <label for="'.$this->get_field_name('showretweet').'">显示转发的微博</label></p>';
	}
	function update($new_instance,$old_instance){
		$instance = $old_instance;
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['weibo'] = strip_tags(stripslashes($new_instance['weibo']));
		$instance['number'] = strip_tags(stripslashes($new_instance['number']));
		$instance['type'] = strip_tags(stripslashes($new_instance['type']));
		$instance['cachetime'] = strip_tags(stripslashes($new_instance['cachetime']));
		$instance['format'] = stripslashes($new_instance['format']);
		$instance['length'] = stripslashes($new_instance['length']);
		$instance['size'] = stripslashes($new_instance['size']);
		$instance['showretweet'] = stripslashes($new_instance['showretweet']);
		return $instance;
	}
	function widget($args,$instance){
		extract($args);
		$title = apply_filters('widget_title',empty($instance['title']) ? '我的微博' : $instance['title']);
		$weibo = empty($instance['weibo'])?'':$instance['weibo'];
		$number = empty($instance['number'])?5:(int)$instance['number'];
		$type = empty($instance['type'])?'user_timeline':$instance['type'];
		$cachetime = empty($instance['cachetime'])?'5':$instance['cachetime'];
		$length = empty($instance['length'])?'200':$instance['length'];
		$size = empty($instance['size'])?'48':$instance['size'];
		$showretweet = $instance['showretweet'];
		$format = empty($instance['format'])?'<div class="smc-avatar">%%avatar%%</div><div class="smc-author">%%author%%</div><div class="smc-excerpt">%%excerpt%%<br/><div class="smc-weibo-image">%%image%%</div></div><!--<div class="smc_date">%%source%% | <a href="%%url%%">%%date%%</a></div>-->':$instance['format'];
		echo $before_widget;
		echo $before_title . $title . $after_title;
		smc_weibo_timeline(array("weibo"=>$weibo,"size"=>$size,"retweet"=>$showretweet,"number"=>$number,"type"=>$type,"expire"=>$cachetime,"format"=>$format,"length"=>$length));
		echo $after_widget;
	}
}
function smc_sidebar_widget_init(){
	register_widget('smc_sidebar_login_widget');
	register_widget('smc_weibo_timeline_widget');
}
add_action('widgets_init','smc_sidebar_widget_init');
?>