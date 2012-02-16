<?php $user = wp_get_current_user();
	if($_POST['send_to_me'] && $_POST['message']){
		$blogname=get_option('blogname');$plugin_author_email=str_replace('#','@',PLUGIN_AUTHOR_EMAIL);
		$subject="来自 $blogname 的关于Social Medias Connect插件的消息";
		$body="来自 $blogname 的消息：\r\n\r\n";
		$body.=$_POST['message'];
		$body.="\r\n";
		$body.="------------------------------------------\r\n";
		$body.="网站名称: $blogname\r\n";
		$body.="网站地址: ".get_option('home')."\r\n";
		$body.="站长昵称: ".$user->user_nicename."\r\n";
		$body.="站长邮箱: ".get_option('admin_email')."\r\n";
		$headers = 'From: '.$user->user_nicename.' <'.get_option('admin_email').'>' . "\r\n" . 'Reply-To: ' . get_option('admin_email');
		wp_mail($plugin_author_email, $subject, $body, $headers);
		update_option('smc_send_me_email',time());$_sended=true;
	}
	if($_sended || get_option('smc_send_me_email') && time()-get_option('smc_send_me_email')<60*60*24){
		$_send=true;
	}
	$SMC=wp_cache_get('global_option','smc');
	if($_POST['donate']){
		$blogname=get_option('blogname');$plugin_author_email=str_replace('#','@',PLUGIN_AUTHOR_EMAIL);
		$weibos=array();
		foreach($SMC as $weibo => $arr){
			if($_POST[$weibo])$weibos[]=$weibo;
		}
		if(empty($weibos)){
			$error='错误：请至少选择一个微博！';
		}elseif($_POST['server_ip']==''){
			$error='错误：请填写你的主机IP地址！';
		}else{
			$subject="【捐助】来自 $blogname 的开通自定义API功能的请求";
			$body="来自 $blogname 的消息：\r\n\r\n";
			$body.=$_POST['message'];
			$body.="\r\n";
			$body.="------------------------------------------\r\n";
			$body.="网站名称: $blogname\r\n";
			$body.="网站地址: ".get_option('siteurl')."\r\n";
			$body.="网站IP: ".$_POST['server_ip']."\r\n";
			$body.="站长昵称: ".$user->user_nicename."\r\n";
			$body.="站长邮箱: ".get_option('admin_email')."\r\n";
			$body.="开通微博: ".join(', ',$weibos)."\r\n";
			$headers = 'From: '.$user->user_nicename.' <'.get_option('admin_email').'>' . "\r\n" . 'Reply-To: ' . get_option('admin_email');
			wp_mail($plugin_author_email, $subject, $body, $headers);
			$donate_send=true;
		}
	}
	
?> 
<div class="wrap" style="-webkit-text-size-adjust:none;">
	<div class="icon32" id="icon-options-general"><br></div>
	<?php if(isset($error))echo '<div class="error"><p>'.$error.'</p></div>';
		if(isset($donate_send))echo '<div class="updated"><p>你的请求开通相关微博自定义功能的请求已经成功发送。如果您捐助后长时间未收到我的回复，请通过下面的邮件表单与我进行联系。</p></div>'; ?>
	<h2>社交媒体连接帮助信息</h2>
	<?php if($_sended): ?>
	<h3 style="color:red;">您的信息已经发送给了插件作者(<a href="htttp://weibo.com/qiqiboy">@qiqiboy</a>)</h3>
	<?php endif; ?>
	<div>
		<h2>Social Medias Connect 的功能特点</h2>
		<h3>功能一：文章同步到各大微博</h3>
			<p>支持对人人网、开心网、新浪微博、腾讯微博、搜狐微博、网易微博、豆瓣、天涯微博、饭否、Facebook、twitter的同步，并且可以同步文章中的图片。利用强大的自定义同步格式功能，你可以自由组织要同步的内容，包括标题、前缀、标题、摘要等。</p>
		<h3>功能二：微博连接登陆功能</h3>
			<p>通过Social Medias Connect，访客可以很方便的通过微博连接来登陆你的网站，这样不仅可以方便读者浏览网站，进行评论等操作，而且无形中也增加了读者对网站的粘性。</p>
		<h3>功能三：微博与现有网站账号的绑定功能</h3>
			<p>对于网站上已经存在的用户，通过本插件可以允许其绑定一个微博账号到他的账户，绑定之后就可以使用微博连接登陆了，原来的用户名、密码登陆也不受影响。并且绑定微博之后该用户的评论等内容也可以选择同步到其绑定的微博。</p>
		<h3>功能四：评论同步到微博</h3>
			<p>对于使用微博连接登陆的用户和绑定微博到其账户的用户，在发表评论可以选择性的将评论发布到其微博。</p>
		<h3>功能五：个性化浮动面板登陆</h3>
			<p>本插件提供了独特的浮动窗口登陆功能，节省页面空间，使页面更简洁，更紧凑。</p>
		<h3>功能六：“我的微博”小工具<span style="color:red;font-size:10px;"> ~building</span></h3>
			<p>Social Medias Connect也内置了许多小工具，其中就有“我的微博”小工具，通过这个widget小工具，你可以很方便在你的博客边栏显示你的微博。支持新浪微博、腾讯微博、搜狐微博、网易微博、豆瓣、twitter的调用，并且可以选择显示仅自己的消息、自己和自己关注的好友的信息或者公共消息等。</p>
		<div style="font-weight:bold;">更多介绍请参见<a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">插件专题页面</a></div>
	</div>
<?php if((int)$user->user_level>8)if($_send): ?>
	<h3 style="color:red;">对不起，(<a href="htttp://weibo.com/qiqiboy">@qiqiboy</a>)每天只接收您的一封邮件，如果还有问题，请明天再来向他提问吧！</h3>
	如果确实还需要与我进一步联系，欢迎到我微博或者博客与我联系。
<?php else: ?>
	<h3>如果你有使用上的疑问或者对插件有好的建议，可以通过下面的表单同我(<a href="htttp://weibo.com/qiqiboy">@qiqiboy</a>)取得联系：</h3>
	<form method="post" action="">
		<p><textarea name="message" style="width:500px;font-size:12px;" rows="5"></textarea></p>
		<p class="submit">
			<input type="submit" name="send_to_me" class="button-primary" value="发送" /> 
		</p>
	</form>
<?php endif; ?>
	<hr/>
<?php if(smc_is_administrator() && $SMC): ?>
	<h3 id="donate">捐助我获取自定义api（微博尾巴）版本</h3>
	<?php if(!isset($donate_send) ): if(isset($error))echo '<div class="error"><p>'.$error.'</p></div>'; ?>
	<form action="" method="post">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">请选择需要开启自定义API功能的微博<br/><code><small>每个开通一个微博的自定义API功能需要<big>￥20</big>，所以如果你不需要全部自定义API，请去掉勾选的相应微博。<br/><sup style="color:red;">NEW</sup>打包开通所有微博的自定义功能仅需<big>￥120</big>。</small></code></th>
				<td>
					<ul id="smc_allow">
					<?php foreach($SMC as $weibo => $arr){
							if(!in_array('customappkey',$arr['supports'])){
								echo '<li class="smc_'.$weibo.'"><input name="'.$weibo.'" type="checkbox" '.(((isset($error) && $_POST[$weibo]) || !isset($error))?'checked="checked" ':'').'value="1" /></li>';
							}
						}
					?>
					</ul><div class="clear"></div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">你的主机IP地址</th>
				<td>
					<input type="text" name="server_ip" class="regular-text" value="<?php echo htmlspecialchars($_POST['server_ip']); ?>" /><br/>务必正确填写，填写错误将无法开通自定义功能
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">接受捐助&付款地址</th>
				<td>
					通过<a style="color:#fff;" href="https://me.alipay.com/qiqiboy" target="_blank"> <input type="button" name="send_to_me" class="button-primary" value=" 支付宝 " /></a> 进行捐助（捐助数额为开通自定义功能的微博数量 * 20）
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">附件留言</th>
				<td>
					<textarea name="message" style="width:500px;font-size:12px;" rows="5"></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"></th>
				<td>
					<p class="submit">
						<input type="submit" name="donate" class="button-primary" value="提交" /> 只有等你捐助完成后才能开通相应自定义功能
					</p>
				</td>
			</tr>
		</table>
	</form>
	<?php else: ?>
		<div class="updated"><p>你的请求开通相关微博自定义功能的请求已经成功发送。如果您捐助后长时间未收到我的回复，请通过上面的邮件表单与我进行联系。</p></div>
	<?php endif; ?>
	<hr/>
<?php endif; ?>
	<div>
		<h2>最近更新：</h2>
		<h3 style="color:red">V1.6 更新：</h3>
		<p>1.新增支持Facebook、天涯微博、人人、开心。<br/></p><br/>
		<h3 style="color:red">V1.5 更新：</h3>
		<p>1.新增支持Facebook、天涯微博、Follow5、饭否、嘀咕、做啥、人间网、人民微博等(逐步增加中)。<br/></p><br/>
		<h3 style="color:red">V1.4 更新：</h3>
		<p>1.新增自定义同步格式，支持文章同步和评论同步的格式自定义。<br/>2.新增同步标签、同步摘要。<br/>
			3.更精确的文字截取，准确截取twitter和国内新浪微博的发布字数<br/>4.新增微博与现有账号的绑定功能</p><br/>
		<h3 style="color:red">V1.3 更新：</h3>
		<p>1.新增连接登陆新增twitter服务。默认关闭。请确认你的主机身在“墙外”再启用twitter连接、同步功能。<br/>2.同步文章新增同步文章缩略图到微博功能。如果没有给文章设置缩略图，那么程序会自动抓取文章中第一张图片<br/>
			3.增加文字截断功能。不用再担心文字过多同步失败了。<br/>4.新增使用短网址api功能。</p>
		<h2>插件信息</h2>
		<p><b>插件名称：</b>Social Medias Connect<br/>
		<b>插件作者：</b>qiqiboy<br/>
		<b>插件地址：</b>wordpress官方下载页面<a href="http://wordpress.org/extend/plugins/social-medias-connect/">WordPress Download</a> | 作者博客发布页面<a href="http://www.qiqiboy.com/plugins/social-medias-connect/">插件发布页面</a><br/>
		<b>插件描述：</b>提供wordpress与各大微博网站的连接登陆功能，以及同步文章信息到微博，转发文章的评论到微博。</p>
		<h3>关于插件的一些FAQ</h3>
		<p>1. 问：启用插件为什么没有任何效果？
			答：请确认评论模板中有<code>&lt;?php do_action('comment_form', $post->ID); ?></code>代码，没有的话要加上。
		</p>
		<p>2. 问：启用插件wordpress报错？
			答：请确认评论wordpress版本>3.0。
		</p>
		<p>3. 问：微博连接能和现有的用户账户绑定吗？
			答：可以的。请使用V1.4.5以上版本。
		</p>
		<p>4. 问：如何再次同步文章？
			答：请在文章编辑页面找到“同步文章状态”一栏，选择“同步”即可。
		</p>
		<h3>微博与我交流(欢迎Follow我)</h3>
		新浪微博：<a href="http://weibo.com/qiqiboy">@qiqiboy</a><br/>
		腾讯微博：<a href="http://t.qq.com/imqiqiboy">@imqiqiboy</a><br/>
		Twitter：<a href="http://twitter.com/qiqiboy">@qiqiboy</a>
		<p><h3>支持我继续开发此插件</h3>
			方式一：支付宝捐助我：imqiqiboy#gmail.com （捐助100以上可获得自定义api版本，如需帮忙申请api，请与我联系）<br/>
			方式二：联系我付费为你的网站开发Social Medias Connect的专业版。
			<hr/>如果你对我开发社交媒体连接插件有兴趣，希望可以针对你的网站进行api自定义，欢迎与我联系进行插件定制（捐助100以上可获得自定义api版本，如需帮忙申请api，请与我联系）。(邮件 or Gtalk[支付宝]：imqiqiboy@gmail.com)
		</p>
	</div>
</div>