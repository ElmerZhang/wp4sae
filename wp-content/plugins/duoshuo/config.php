<div class="wrap">
<?php echo screen_icon();?>
<h2>设置多说站点</h2>
<?php if (!(self::$shortName && self::$secret)):?>
<h3>还没有多说站点？</h3>
<form method="post">
<?php echo wp_nonce_field('duoshuo-register-site');?>
<script>
function updateDuoshuoUnique(unique){
	document.write('<input name="unique" type="hidden" id="duoshuo-unique" value="' + unique + '" />');
}
</script>
	<script src="http://<?php echo self::DOMAIN;?>/identifier.js?callback=updateDuoshuoUnique&<?php echo time();?>"></script>
	<p>站点域名：http://<input type="text" class="small-text" name="short_name" value="" />.duoshuo.com</p>
	<p><input type="submit" class="button-primary" value="一键注册" /></p>
</form>

<h3>或者输入已有的多说站点信息</h3>
<form action="" method="post">
<?php wp_nonce_field('duoshuo-set-site'); ?>
	<script src="http://<?php echo Duoshuo::DOMAIN;?>/identifier.js?callback=updateDuoshuoUnique&<?php echo time();?>"></script>
	<table class="form-table">
		<tbody>
		<tr>
		<th scope="row"><label>站点域名</label></th>
		<td><span>http://<input type="text" name="short_name" class="small-text" value="<?php echo get_option('duoshuo_short_name');?>" />.duoshuo.com</span><span class="description">注册时填写的二级域名</span></td>
		</tr>
		<tr>
		<th scope="row"><label>密钥(Secret)</label></th>
		<td><input type="text" name="secret" class="regular-text" /><span class="description">32位长的字符串</span></td>
		</tr>
		</tbody>
	</table>
	<p class="submit"><button type="submit" class="button-primary">提交</button></p>
</form>
<?php else:?>
<h3>数据同步</h3>
<div id="ds-export">
	<p class="message-start">安装成功了！只要一键将您的评论数据同步到多说，多说就可以开始为您服务了！<a href="javascript:void(0)" class="button-primary" onclick="fireExport();return false;">开始同步</a></p>
	<p class="status"></p>
	<p class="message-complete">同步完成，现在你可以<a href="<?php echo admin_url('admin.php?page=duoshuo-settings');?>">设置</a>或<a href="<?php echo admin_url('admin.php?page=duoshuo');?>">管理</a></p>
</div>
<?php include_once dirname(__FILE__) . '/common-script.html';?>
<?php endif;?>
</div>