<div class="wrap">
<?php screen_icon(); ?>
<h2>多说评论框设置</h2>

<?php try{
$user = wp_get_current_user();
$params = array(
	'template'	=>	'wordpress',
	'local_identity'=>	$user->ID,
	'signature'	=> Duoshuo::buildSignature($user->ID)
);
$content = Duoshuo::getClient()->getContents('settings', $params);?>

<form action="" method="post">
<?php wp_nonce_field('duoshuo-options');?>
<?php echo $content;?>
<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="保存"></p>
</form>

<?php
}	// end of try
catch(Duoshuo_Exception $e){
	Duoshuo::showException($e);
}?>

<h3>高级设定</h3>
<form action="" method="post">
<?php wp_nonce_field('duoshuo-local-options');?>
	<table class="form-table">
		<tbody>
		<tr valign="top">
			<th scope="row">本地数据备份</th>
			<td><input type="checkbox" name="duoshuo_cron_sync_enabled" value="1" <?php if (get_option('duoshuo_cron_sync_enabled')) echo ' checked="checked"';?>/>定时从多说备份评论到本地</td>
		</tr>
		<tr valign="top">
			<th scope="row">SEO优化</th>
			<td><input type="checkbox" name="duoshuo_seo_enabled" value="1" <?php if (get_option('duoshuo_seo_enabled')) echo ' checked="checked"';?>/>搜索引擎爬虫访问网页时，显示静态HTML评论</td>
		</tr>
		<tr valign="top">
			<th scope="row">评论框前缀</th>
			<td><input type="text" class="regular-text" name="duoshuo_comments_wrapper_intro" value="<?php echo esc_attr(get_option('duoshuo_comments_wrapper_intro'));?>" /><span class="description">仅在主题和评论框的div嵌套不正确的情况下使用</span></td>
		</tr>
		<tr valign="top">
			<th scope="row">评论框后缀</th>
			<td><input type="text" class="regular-text" name="duoshuo_comments_wrapper_outro" value="<?php echo esc_attr(get_option('duoshuo_comments_wrapper_outro'));?>" /><span class="description">仅在主题和评论框的div嵌套不正确的情况下使用</span></td>
		</tr>
		</tbody>
	</table>
	<p class="submit"><input type="submit" name="duoshuo_local_options" id="submit" class="button-primary" value="保存"></p>
</form>

<h3>数据同步</h3>
<div id="ds-export">
	<p class="message-start"><a href="javascript:void(0)" class="button" onclick="fireExport();return false;">同步评论到多说</a></p>
	<p class="status"></p>
	<p class="message-complete">同步完成</p>
</div>
<?php include_once dirname(__FILE__) . '/common-script.html';?>

<h3>卸载</h3>
<form action="" method="post" onsubmit="return confirm('你确定要卸载多说评论插件吗？');">
	<input type="hidden" name="action" value="duoshuo_uninstall" />
	<p class="submit"><input type="submit" class="button" value="卸载" name="duoshuo_uninstall" /></p>
</form>
</div>