<div class="wrap" style="-webkit-text-size-adjust:none;">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2>将微博绑定到你的当前账户</h2><br/><br/>
<?php $user = wp_get_current_user(); $wpuid=isset( $user->ID )?(int)$user->ID:0;
	$user_login=$user->user_login; $smc_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__));
	$callback=smc_menu_page_url('smc_bind_weibo_acount');
	if($_POST['delete']){
		if(user_pass_ok($user_login,$_POST['repassword'])){
			delete_usermeta($wpuid, 'smcdata');
			delete_usermeta($wpuid, 'smc_weibo_bind');
			delete_usermeta($wpuid, 'smc_weibo_email_bind');
		}else{
			echo "<div class='error'><p>输入的密码有误，无法删除绑定。</p></div>";
		}
	}
	$weibo_data=get_usermeta($wpuid, 'smcdata');
	if($weibo_data){
		$_weibo=$weibo_data['smcweibo'];$_weiboname=smc_get_weibo_name($_weibo);
		echo '<div id="weibobind"><form action="'.smc_menu_page_url('smc_bind_weibo_acount',false).'" id="delete_bind" method="post"><input type="hidden" name="page" value="smc_bind_weibo_acount"/><input type="hidden" name="delete" value="'.$_weibo.'"/><input type="hidden" name="weiboname" value="'.$_weiboname.'"/><img class="smc_img" src="'.$smc_url.'/images/'.$_weibo.'.png" /><input type="text" style="display:none;margin-right:0px;" name="repassword" id="repassword" value="" /><input type="submit" class="button-primary" value="删除'.$_weiboname.'绑定"><br/>微博账号: '.$weibo_data['username'].'</form></div>';
		echo '<a href="'.admin_url('profile.php').'#smc-user-info">更多信息请点此查看</a>';
	}else{
		echo '你只能绑定一个微博账号到当前账号<br/><br/>';
		smc_connect(array('showtips'=>0,'is_bind'=>1,'smc_auto_js'=>0,'callback_url'=>$callback));
	}
?>
	<br/><br/><p><strong style="color:red;">注意：</strong>一个账号只允许绑定一个微博账号。如果您要删除现有绑定，需要验证你的账号密码。此操作是保证您记住了你的账号密码，<span style="color:red;">因为一旦删除绑定，你将不能使用此微博账号登陆网站</span>，而只能使用现有账号密码登陆，所以在您删除绑定前，请确认你已经牢牢记住了您的密码。如果您的密码过于难记，可以到个人资料页面进行密码修改。</p>
</div>
