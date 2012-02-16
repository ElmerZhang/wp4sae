<div class="wrap">
<?php screen_icon(); ?>
<h2>多说评论管理
<a style="font-size:13px" target="_blank" href="<?php echo 'http://' . Duoshuo::$shortName . '.' . Duoshuo::DOMAIN.'/admin/';?>">在新窗口中打开</a>
</h2>
<?php
$user = wp_get_current_user();
$params = array(
	'template'		=>	'wordpress',
	//'local_identity'=>	$user->ID,
	//'signature'		=>	Duoshuo::buildSignature($user->ID)
);
?>
<iframe id="duoshuo-remote-window" src="<?php echo 'http://' . Duoshuo::$shortName . '.' . Duoshuo::DOMAIN.'/admin/?' . http_build_query($params);?>" style="width:100%;600px;"></iframe>
</div>

<script>
jQuery(function(){
var $ = jQuery,
	iframe = $('#duoshuo-remote-window'),
	resetIframeHeight = function(){
		iframe.height($(window).height() - iframe.offset().top - 70);
	};
resetIframeHeight();
$(window).resize(resetIframeHeight);
});
</script>
