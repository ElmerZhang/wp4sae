<?php 
function smc_warning(){
	if($_GET['page']!='smc_bind_weibo_option')
		echo '<div class="error"><p>请到<a href="'.smc_menu_page_url('smc_bind_weibo_option',false).'">Social Medias Connect</a>更新插件设置</p></div>';
}
function smc_no_global_option(){
	if(smc_is_administrator())
		echo '<div class="error"><p>插件需要更新配置，请<a href="'.smc_menu_page_url('social-medias-connect/function.php',false).'&smc_request=getglobaloption">点此获取</a>插件配置</p></div>';
}
add_action('init', 'smc_init');
function smc_init(){
	if(session_id()==''){
		session_start();
	}
	$SMC=get_option('smc_global_option');
	if(empty($SMC)){
		add_action('admin_notices', 'smc_no_global_option');
	}else{
		wp_cache_add('global_option',$SMC,'smc');
	}
	if((is_user_logged_in() && is_admin() && ($_GET['page']=='social-medias-connect/function.php' || $_GET['page']=='smc_bind_weibo_acount') || !is_user_logged_in()) && (isset($_GET['oauth_token'])||isset($_GET['code'])) && $_GET['action']!=='smcregister'){
		$weibo=$_SESSION["smc_weibo"];
		if(!$weibo){
			wp_die('您的主机配置不正确，请检查您主机的php.ini中的session.save_path设置，或者将session.use_trans_sid一项设置为session.use_trans_sid=1。<br/><div style="text-align:right;"><p><a onclick="window.close();" target="_blank" href="http://www.qiqiboy.com/products/plugins/social-medias-connect">Powered by 社交媒体连接</a></p></div>');
		}
		if(isset($SMC[$weibo])){
			include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
			$rfunc="smc_{$weibo}_verify_credentials";
			$r=$rfunc();
		}else{
			wp_die("An error occurred. Please close the window and retry.");
		}
		if(!(is_array($r)||$r['user_login'])){
			wp_die("An error occurred. Please close the window and retry.");
		}
		$r['user_email']=$r['user_email']?$r['user_email']:$r['user_login'].'@'.$r['emailendfix'];
		unset($_SESSION["smc_weibo"]);unset($_SESSION["smc_oauth_token_secret"]);
	}
	if(!is_user_logged_in()){
		if(isset($r)){
			smc_user_register($r);
		}
		if($_GET['action']==='smcregister'){
			$r=$_SESSION['smc_temp_userdata'];
			if(is_array($r)){
				unset($_SESSION['smc_temp_userdata']);
				$r['smc_user_login']=$_POST['smc_user_login'];
				$r['smc_user_email']=$_POST['smc_user_email'];
				$r['smc_user_url']=esc_url($_POST['smc_user_url']);
				$r['password']=$_POST['password'];
				$r['add_follow']=$_POST['add_follow'];
				smc_user_register($r);
			}
		}
	}else{
		if(smc_is_administrator()){
			if($_GET['action']=='getappkey'){
				smc_get_appkey($_GET['weibo']);die();
			}
			if($_GET['page']=='social-medias-connect/function.php' && is_admin() && isset($r)){
				if(is_array($r)){
					$tok=array('user_login'=>$r['user_login'],'weibo'=>$r['weibo'],'weibo_uid'=>$r['weibo_uid'],'uid'=>get_current_user_id(),'oauth_token'=>$r['oauth_access_token'],'oauth_token_secret'=>$r['oauth_access_token_secret']);
					$weibotok=get_option('weibo_access_token');
					$weibotok[$weibo]=$tok;
					update_option('weibo_access_token',$weibotok);
				}
			}
			if($_GET['smc_request']=='getglobaloption') {
				$sae=smc_get_global_option();
				if($sae===false)$sae=smc_get_global_option('http://u.boy.im/socialmedias/');
				if($sae===true){
					$info=array('updated','获取配置成功！');
				} else $info=array('error',$sae['result']);
				wp_safe_redirect(smc_menu_page_url('social-medias-connect/function.php',false).'&smc_info='.urlencode($info[1]).'&smc_info_type='.$info[0]);
			}
		}
		if($_GET['page']=='smc_bind_weibo_acount' && is_admin() && isset($r)){
			if($__wpuid=smc_get_user_by_meta('smc_weibo_email_bind',$r['user_email'])){
				$_user=get_user_by('id',$__wpuid);
				wp_die('此微博已经绑定了账号(<b>'.$_user->user_login.'</b>)，如果要绑定到此账户，请先使用<b>'.$_user->user_login.'</b>登陆网站，然后解除与<b>'.$_user->user_login.'</b>的绑定。<br/><br/><a href="javascript:window.close();">点击这里关闭窗口</a>');
			}else{
				$wpuid=get_current_user_id();
				smc_update_smcdata($wpuid,array(),$r);
			}
		}
	}
	
	$weiboopt=get_option('smc_weibo_options');
	if(empty($weiboopt)){
		add_action('admin_notices', 'smc_warning');
	}
	if(empty($weiboopt) || $weiboopt['smc_auto_connect']){
		add_action('comment_form', 'smc_connect');
		add_action("login_form", "smc_connect");
		add_action("comment_form_must_log_in_after", "smc_connect");
	}
	$smc_types=preg_split("/[,\s\|]+\s*/",$weiboopt['smc_post_types']);
	foreach($smc_types as $type){
		add_action('publish_'.$type, 'smc_publish_post', 999);
	}

}

add_action("wp_head", "smc_wp_head");
add_action("admin_head", "smc_wp_head");
add_action("admin_head", "smc_admin_header");
add_action("login_head", "smc_wp_head");
function smc_wp_head(){
	$weiboopt=get_option('smc_weibo_options');
    echo '<!-- Social Medias Connect '.SMC_VERSION.' by qiqiboy support Fcebook, RenRen, KaiXin, SINA weibo, Tencent weibo, SOHU weibo, Douban, Twitter...connection with WordPress -->';
	if(is_user_logged_in()) {
        if(isset($_GET['oauth_token']) && $_GET['oauth_token'] !== $_SESSION["smc_weibo"] || isset($_GET['code'])){
			echo '<script type="text/javascript">window.opener.smc_reload("");window.close();</script>';
		}
	}
	echo '<script type="text/javascript" src="'.(WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__))).'/js/social-medias-connect.js?s='.SMC_VERSION.'"></script>';
	smc_wp_css();
	echo '<!-- End Social Medias Connect '.SMC_VERSION.' -->';
}
function smc_admin_header(){
	wp_enqueue_script('jquery');
	echo '<script type="text/javascript" src="'.(WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__))).'/js/social-medias-connect-admin.js?s='.SMC_VERSION.'"></script>';
}
function smc_wp_css(){
	echo '<link rel="stylesheet" media="all" href="'.(WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__))).'/style.css?s='.SMC_VERSION.'" type="text/css" />';
}
$smc_loaded=0;
function smc_connect($args=""){
	$SMC=wp_cache_get('global_option','smc');
	if(empty($SMC)){
		echo smc_no_global_option_tips();;
		return false;
	}
	global $smc_loaded;
	$weiboopt=get_option('smc_weibo_options');
	$defaults=array(
		"callback_url"=>"",
		"is_comment"=>0,
		"list_style"=>isset($weiboopt['smc_list_style'])?$weiboopt['smc_list_style']:4,
		"smc_auto_js"=>isset($weiboopt['smc_auto_js'])?$weiboopt['smc_auto_js']:1,
		"icon_size"=>isset($weiboopt['smc_icon_size'])?$weiboopt['smc_icon_size']:32,
		"showtips"=>1,
		"is_bind"=>0
	);
	$r=wp_parse_args( $args, $defaults );
	extract($r);
	if(is_user_logged_in()){
		if(!is_admin()){
			$user = wp_get_current_user(); $user_ID=isset( $user->ID )?(int)$user->ID:0;
			$smcdata = get_user_meta($user_ID, 'smcdata',true);$weibo=$smcdata?$smcdata['smcweibo']:'noweibo';
			if($is_comment){
				$display_name=$user->display_name;
				printf('<p>您已经以 <a class="smc_user_login smc_%1s" href="%2$s"><b>%3$s</b></a> 登录。<a href="%4$s" title="退出登录">退出</a></p>', $weibo, admin_url('/wp-admin/profile.php'), $display_name, wp_logout_url(get_current_page_url()));
			}elseif($smcdata&&is_singular()){
				echo '<p id="smc_sync" class="smc_button smc_'.$weibo.'"><input name="post_to_socialmedias" type="checkbox"'.(!($_COOKIE['post_to_socialmedias_' . COOKIEHASH] === 'no')?' checked="checked"':'').' id="post_to_socialmedias" value="1"  /><label for="post_to_socialmedias">同步到'.smc_get_weibo_name($weibo).'</label></p>';
			}
			return false;
		}
		$weibotok=get_option('weibo_access_token');
	}else{
		$allows=get_option('smc_allowed_weibo');
		$list_icon=($smc_auto_js||$list_style!=2)?false:true;
		$icon_size=(int)$icon_size;
		$smc_icon_size="width='$icon_size' height='$icon_size'";
	}
	$smc_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__));
	$callback="&callback_url=".($callback_url?urlencode($callback_url):urlencode(preg_replace('/\?redirect_to.*/','',get_current_page_url())));
?>
	<script type="text/javascript">
    window.smc_reload=function(url){
       if(!url)url=window.location.href.replace(/#.*|&delete.*|\?redirect_to.*|&smc_info.*/i,'')+'#smc_sync';
       window.location.href = url;
       <?php if(!$_GET['redirect_to']){ ?>setTimeout(function(){location.reload()},1300); <?php } ?>
    }
	<?php if(!is_user_logged_in()&&$smc_auto_js){ ?>
			if(window.smcJS){
				if(window.jQuery)jQuery(document).ready(function(){
					smcJS.smc("<?php echo addslashes(preg_replace("/[\n\s\t\r]+/",'',$weiboopt['smc_weibo_notice'])); ?>",<?php echo $smc_loaded; ?>);
				});
				else smcJS.documentReady(function(){
					smcJS.smc("<?php echo addslashes(preg_replace("/[\n\s\t\r]+/",'',$weiboopt['smc_weibo_notice'])); ?>",<?php echo $smc_loaded; ?>);
				});
			}
	<?php } ?>
    </script>
	<?php if($showtips&&!empty($weiboopt['smc_weibotips'])){
			echo'<p id="smc_connect_tips">'.$weiboopt['smc_weibotips'].'</p>';
		}
		echo (isset($list_icon)&&$list_icon===true?'<div':'<ul').' id="smc_connect_area'.($smc_loaded?'_'.$smc_loaded:'').'" class="smc_connect_area smc_button"'.($smc_auto_js?' style="display:none;"':'').'>';
		foreach($SMC as $weibo=>$arr){
			if((isset($weibotok)&&(!$weibotok[$weibo]||$is_bind))||(isset($allows)&&(empty($allows)||$allows[$weibo]))){
				if(!is_dir(dirname(__FILE__).'/'.$weibo)){
					continue;
				}
				$click=htmlspecialchars('window.open("'.$smc_url.'/start-connect.php?socialmedia='.$weibo.$callback.'","smcWindow","width=800,height=600,left=150,top=100,scrollbar=no,resize=no");return false;');
				if(isset($list_icon)&&$list_icon===true){
					echo '<span class="smc_list_icon"><img '.$smc_icon_size.' class="smc_img" onclick="'.$click.'" src="'.$smc_url.'/images/'.$weibo.'.png" alt="使用'.$arr['name'].'登陆" /></span> ';
				}else{
					echo '<li><img class="smc_img" class="smc_img" onclick="'.$click.'" src="'.$smc_url.'/images/'.$weibo.'_button.png" alt="使用'.$arr['name'].'登陆" />';
					if(in_array('customappkey',$arr['supports']) && smc_is_administrator()){
						if(($appkey=get_option('smc_'.$weibo.'_custom_appkey'))) echo ' <a href="javascript:void(0)" title="app Id: '.$appkey['app_key']."\napp Secret: ".$appkey['app_secret'].'" onclick="window.open(\''.get_bloginfo('home').'?action=getappkey&weibo='.$weibo.'&appkey='.$appkey['app_key'].'&appsecret='.$appkey['app_secret'].'\',\'smcWindow\',\'width=400,height=410,left=150,top=100,scrollbar=no,resize=no\');return false;"><input type="submit" class="smc_appkey_btn button-primary" value="修改appkey"></a>';
						else echo ' <a href="javascript:void(0)" title="No Appkey" onclick="window.open(\''.get_bloginfo('home').'?action=getappkey&weibo='.$weibo.'\',\'smcWindow\',\'width=400,height=410,left=150,top=100,scrollbar=no,resize=no\');return false;"><input type="submit" class="smc_appkey_btn button-primary" value="添加appkey"></a>';
					}
					echo '</li>';
				}
			}
		}
		if(!$smc_auto_js && !is_user_logged_in()){
			if(isset($list_icon)&&$list_icon===true){
				echo '<span><a class="smc_plugin_url" href="http://www.qiqiboy.com/plugins/social-medias-connect/" rel="bookmark"><img '.$smc_icon_size.' class="smc_img" alt="社交媒体连接" src="'.$smc_url.'/images/smc_3.png" /></a></span>';
			}else{
				echo '<li><a class="smc_plugin_url" href="http://www.qiqiboy.com/plugins/social-medias-connect/" rel="bookmark">我也想要使用此服务</a></li>';
			}
		}
		echo isset($list_icon)&&$list_icon===true?'</div>':'</ul>';
		if($smc_auto_js){
			echo '<a id="smc_weibo_start'.($smc_loaded?'_'.$smc_loaded:'').'" class="smc_weibo_start smc_btn_'.(isset($weiboopt['smc_btn_img'])?$weiboopt['smc_btn_img']:'4').'" title="绑定账号登陆" rel="nofollow" href="javascript:void(0);"></a>';
		}
    $smc_loaded = mt_rand(1,999999999);
}

add_filter("get_avatar", "smc_get_avatar",10,4);
function smc_get_avatar($avatar, $id_or_email='',$size='32') { 
	if ( is_object($id_or_email) ) {
		if ( !empty($id_or_email->user_id) ) {
			$user_id = (int) $id_or_email->user_id;
		}
	} else if( is_numeric($id_or_email) ){
		$user_id = (int)$id_or_email;
	} else {
		$user = get_user_by('email', $id_or_email);
		$user_id = (int) $user->ID;
	}

	if($user_id && ($smcdata = get_usermeta($user_id, 'smcdata'))){
		if($smcdata['smcid']){
			$weibo=$smcdata['smcweibo'];
			switch($weibo){
				case 'sinaweibo':
						$out = 'http://tp3.sinaimg.cn/'.$smcdata['smcid'].'/50/1.jpg';
						break;
				case 'qqweibo':
						$out = 'http://app.qlogo.cn/mbloghead/'.$smcdata['smcid'].'/100';
						break;
				case 'douban':
						$out = 'http://img3.douban.com/icon/u'.$smcdata['smcid'].'.jpg';
						break;
				case 'sohuweibo':
						$out = $smcdata['smcid'];
						break;
				case '163weibo':
						$out = $smcdata['smcid'];
						break;
				default:return $avatar;
			}
		}else $out=$smcdata['avatar'];
		if(!$out)return $avatar;
		$avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
		return $avatar;
	}else {
		return $avatar;
	}
}
if(!function_exists('smc_user_register')){
function smc_user_register($r) {
	if(!(is_array($r)||$r['user_login'])){
		wp_die("An error occurred. Please close the window and retry.");
	}
	$r['user_email']=$r['user_email']?$r['user_email']:$r['user_login'].'@'.$r['emailendfix'];
	$user_id=smc_get_user_by_meta('smc_weibo_email_bind',$r['user_email']);$sync=array();
	if(!$user_id){
		$error=false;
		$user_login=isset($r['smc_user_login'])?$r['smc_user_login']:$r['user_login'];
		$user_email=$r['smc_user_email'];
		$password=$r['password'];
		$sanitized_user_login=sanitize_user($user_login);
		$user_email = apply_filters('user_registration_email',$user_email);
		if(empty($sanitized_user_login)){
			$error=array('code'=>1,'info'=>__('<strong>ERROR</strong>: Please enter a username.'));//'<strong>ERROR</strong>: Please enter a username.';
		}elseif(!validate_username($user_login)){
			$error=array('code'=>2,'info'=>__('<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.'));
			$sanitized_user_login='';
		}elseif(strlen($user_login)>12){
			$error=array('code'=>8,'info'=>__('<strong>ERROR</strong>: This length of this username is too long.(Max: 12)'));
			$sanitized_user_login='';
		}elseif(username_exists($sanitized_user_login)){
			$error=array('code'=>3,'info'=>__('<strong>ERROR</strong>: This username is already registered, please choose another one.'));
		}elseif(empty($user_email)){
			$error=array('code'=>4,'info'=>__('<strong>ERROR</strong>: Please type your e-mail address.'));
		}elseif(!is_email($user_email)) {
			$error=array('code'=>5,'info'=>__('<strong>ERROR</strong>: The email address isn&#8217;t correct.'));
			$user_email = '';
		}elseif(email_exists($user_email)){
			$error=array('code'=>6,'info'=>__('<strong>ERROR</strong>: This email is already registered, please choose another one.'));
		}else{
			$user_pass=empty($password)?wp_generate_password(12,false):$password;
			$user_id=wp_create_user($sanitized_user_login,$user_pass,$user_email);
			if(!$user_id){
				$error=array('code'=>7,'info'=>'<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:'.get_option( 'admin_email' ).'">webmaster</a> !');
			}else{
				$sync=array(
					'display_name'=>$r['display_name'],
					'user_url'=>$r['smc_user_url'],
					'description'=>$r['description']
				);
				if(empty($password))update_user_option($user_id,'default_password_nag',true,true);
				smc_new_user_notification($user_id,$user_pass,$r['weibo']);
				smc_new_user_email($r,$r['weibo']);
				smc_add_follow($r);
			}
		}
	}
	if($error){
		$_SESSION['smc_temp_userdata']=$r;
		smc_user_register_form($r,$error);
	}
	$weiboopt = get_option('smc_weibo_options');
	$remember = (boolean)$weiboopt['smc_auto_remember'];
	smc_update_smcdata($user_id,$sync,$r);
	wp_set_auth_cookie($user_id, $remember, false);
	wp_set_current_user($user_id);
}
function smc_update_smcdata($wpuid,$usermeta,$r){
	$smc_array = array (
		"avatar" => $r['profile_image_url'],
		"smcweibo" => $r['weibo'],
		"useremail" => $r['user_email'],
		"username" => $r['user_login'],
		"userurl" => $r['user_url'],
		"oauth_access_token" => $r['oauth_access_token'],
		"oauth_access_token_secret" => $r['oauth_access_token_secret']
	);
	foreach($usermeta as $key=>$meta){
		if($meta)update_usermeta($wpuid, $key, $meta);
	}
	update_usermeta($wpuid, 'smcdata', $smc_array);
	update_usermeta($wpuid, 'smc_weibo_email_bind', $r['user_email']);
	/* refresh access_token */
	$weibotok=get_option('weibo_access_token');
	$_tok=$weibotok[$r['weibo']];
	if($_tok['uid']==$wpuid && $_tok['user_login']==$r['user_login']){
		$_tok['oauth_token']=$r['oauth_access_token'];
		$_tok['oauth_token_secret']=$r['oauth_access_token_secret'];
		$weibotok[$r['weibo']]=$_tok;
		update_option('weibo_access_token',$weibotok);
	}
}
function smc_add_follow($r){
	if(empty($r['add_follow']))return false;
	$weibo=$r['weibo'];
	if(!class_exists($SMC[$weibo]['OAuthClass'])){
		include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
	}
	$rfunc="smc_{$weibo}_add_follow_user";
	$rfunc($r);
}
}

function smc_add_user_info($user_id){
	/*if($_POST['user_twitter']){
		update_usermeta($user_id, 'user_twitter', $_POST['user_twitter']);
	}*/
}
function smc_show_user_info($profileuser){
	$smcdata=get_usermeta($profileuser->ID,'smcdata');
	if(!$smcdata)return;
?>
	<h3 id="smc-user-info">社交媒体连接</h3>
	<table class="form-table"><tbody>
	<tr>
		<th><label for="description">绑定的微博</label></th>
		<td><input type="text" disabled="disabled" name="user_weibo" id="user_weibo" value="<?php echo esc_attr(smc_get_weibo_name($smcdata['smcweibo'])) ?>" class="regular-text code" /><br />
		<span class="description">您的相关信息可以同步到<?php echo smc_get_weibo_name($smcdata['smcweibo']); ?>。</span></td>
	</tr>
	<tr>
		<th><label for="description">微博地址</label></th>
		<td><input type="text" disabled="disabled" name="user_url" id="user_url" value="<?php echo esc_attr($smcdata['userurl']) ?>" class="regular-text code" /><br/>
		<span class="description">不可自行修改。</span></td>
	</tr>
	<tr>
		<th><label for="description">Oauth Access Token</label></th>
		<td><input type="text" disabled="disabled" name="oauth_access_token" id="oauth_access_token" value="<?php echo esc_attr($smcdata['oauth_access_token']) ?>" class="regular-text code" /></td>
	</tr>
	<tr>
		<th><label for="description">Oauth Access Token Secret</label></th>
		<td><input type="text" disabled="disabled" name="oauth_access_token_secret" id="oauth_access_token_secret" value="<?php echo esc_attr($smcdata['oauth_access_token_secret']) ?>" class="regular-text code" /></td>
	</tr>
	</tbody></table>
<?php
}
add_action('personal_options_update', 'smc_add_user_info');
add_action('edit_user_profile_update', 'smc_add_user_info');
add_action('show_user_profile', 'smc_show_user_info');
add_action('edit_user_profile', 'smc_show_user_info');

function smc_user_register_form($r,$error){
	global $is_iphone; $SMC=wp_cache_get('global_option','smc');
	$weibotok=get_option('weibo_access_token');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<title>请填写必要的信息 - <?php echo smc_get_weibo_name($r['weibo']); ?>用户注册 &rsaquo; <?php bloginfo('name'); ?></title>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<?php
	wp_admin_css( 'login', true );
	wp_admin_css( 'colors-fresh', true );
	if ( $is_iphone ) { ?>
	<meta name="viewport" content="width=320; initial-scale=0.9; maximum-scale=1.0; user-scalable=0;" />
	<style type="text/css" media="screen">
	form { margin-left: 0px; }
	#login { margin-top: 20px; }
	</style>
<?php
	}
	if($head_info){echo "\n".$head_info.'</head><body></body></html>';die();}
	smc_shake_js();
?>
	<style type="text/css">
		#login{
			margin:1em auto;
		}
		body form .input-error{
			background:#FFEBE8;
		}
	</style>
</head>
<body class="login">
<div id="login"><h1><a target="_blank" href="http://www.qiqiboy.com/products/plugins/social-medias-connect" title="社交媒体连接">社交媒体连接</a></h1>
<?php echo '<div id="login_error">' .$error['info']. '</div>'; ?>
<form id="smcregister" action="<?php echo site_url('/?action=smcregister&oauth_token='.$_GET['oauth_token'].'&code='.$_GET['code']); ?>" method="post">
	<p>
		<label>用户名 *<?php if($error['code']==3){ ?> （是你的账号？<a target="_blank" onclick="window.opener.smc_reload('<?php echo site_url('/wp-login.php'); ?>');window.close();return false;" href="<?php echo site_url('/wp-login.php'); ?>">点此登陆</a>）<?php } ?><br/>
		<input type="text" name="smc_user_login" id="smc_user_login" class="input<?php if(in_array($error['code'],array(1,2,3,8)))echo ' input-error'; ?>" value="<?php echo esc_attr(isset($r['smc_user_login'])?$r['smc_user_login']:$r['user_login']); ?>" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label>电子邮件 *<?php if($error['code']==6){ ?> （是你的邮箱？<a target="_blank" onclick="window.opener.smc_reload('<?php echo site_url('/wp-login.php?action=lostpassword'); ?>');window.close();return false;" href="<?php echo site_url('/wp-login.php?action=lostpassword'); ?>">点此找回账号</a>）<?php } ?><br />
		<input type="text" name="smc_user_email" id="smc_user_email" class="input<?php if(in_array($error['code'],array(4,5,6)))echo ' input-error'; ?>" value="<?php echo esc_attr($r['smc_user_email']); ?>" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label>网站<br />
		<input type="text" name="smc_user_url" id="smc_user_url" class="input" value="<?php echo esc_attr(isset($r['smc_user_url'])?$r['smc_user_url']:$r['user_url']); ?>" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label>密码<br />
		<input type="password" name="password" id="password" class="input" value="<?php echo esc_attr($r['password']); ?>" size="20" tabindex="10" /></label>
	</p>
	<p id="reg_passmail">您可以不输入密码，系统将会为您自动生成一串密码并通过电子邮件发送给您。</p>
	<?php if(is_array($weibotok) && !empty($weibotok[$r['weibo']]) && !empty($weibotok[$r['weibo']]['weibo_uid']) && !empty($SMC[$r['weibo']]) && in_array('addfollow',$SMC[$r['weibo']]['supports'])):  ?>
	<br/><p id="addfollow" class="forgetmenot"><label><input type="checkbox" checked="checked" name="add_follow" id="add_follow" class="checkbox" value="<?php echo $weibotok[$r['weibo']]['weibo_uid']; ?>" /> <img align="top" src="<?php echo (WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__))); ?>/images/<?php echo $r['weibo']; ?>_16.png" alt="" /> 关注本站微博</label></p>
	<?php endif; ?>
	<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="注册" tabindex="100" />
		<input type="hidden" name="" value="" />
	</p>
	<div style="clear:both;line-height:0;height:0"></div>
	<p style="float:right;-webkit-text-size-adjust:none;"><br/><a target="_blank" href="http://www.qiqiboy.com/plugins/social-medias-connect/">Powered by © Social Medias Connect.</a></p>
</form>
</div>
<script type="text/javascript">
	if(typeof wpOnload=='function')wpOnload();
</script>
</body>
</html>
<?php
exit;
}
function smc_shake_js() {
	global $is_iphone;
	if ( $is_iphone )
		return;
?>
<script type="text/javascript">
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
function s(id,pos){g(id).left=pos+'px';}
function g(id){return document.getElementById(id).style;}
function shake(id,a,d){c=a.shift();s(id,c);if(a.length>0){setTimeout(function(){shake(id,a,d);},d);}else{try{g(id).position='static';wp_attempt_focus();}catch(e){}}}
addLoadEvent(function(){ var p=new Array(15,30,15,0,-15,-30,-15,0);p=p.concat(p.concat(p));var i=document.forms[0].id;g(i).position='relative';shake(i,p,20);});
</script>
<?php
}

function smc_get_user_by_meta($meta_key, $meta_value) {
  global $wpdb;
  $sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s' AND meta_value = '%s'";
  return $wpdb->get_var($wpdb->prepare($sql, $meta_key, $meta_value));
}

if(!function_exists('connect_login_form_login')){
	add_action("login_form_login", "connect_login_form_login");
	add_action("login_form_register", "connect_login_form_login");
	function connect_login_form_login(){
		if(is_user_logged_in()){
			$redirect_to = admin_url('index.php');
			wp_safe_redirect($redirect_to);
		}
	}
}

add_action('admin_menu', 'smc_options_add_page');
function smc_options_add_page() {
	add_menu_page('社交媒体连接(Social Medias Connect)设置','社交媒体连接','administrator',__FILE__,'smc_bind_weibo_sync_posts',(WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__))).'/images/favicon.png');
	add_submenu_page(__FILE__, '文章同步微博绑定', '绑定微博同步文章', 'administrator', __FILE__, 'smc_bind_weibo_sync_posts');
	add_submenu_page(__FILE__, '文章同步设置', '社交媒体连接设置', 'administrator', 'smc_bind_weibo_option', 'smc_bind_weibo_option');
	add_submenu_page(__FILE__, '绑定微博到现有账号', '绑定微博到此账户', 0, 'smc_bind_weibo_acount', 'smc_bind_weibo_acount');
	add_submenu_page(__FILE__, '帮助信息', '帮助', 0, 'smc_bind_weibo_help', 'smc_bind_weibo_help');
}
function smc_menu_page_url($pagename, $flag=false){
	return site_url('/wp-admin/admin.php?page='.$pagename);
}
function smc_bind_weibo_sync_posts(){
	global $info;
	$SMC=wp_cache_get('global_option','smc');
?>
	<div class="wrap" style="-webkit-text-size-adjust:none;">
		<div class="icon32" id="icon-options-general"><br></div>
			<h2>绑定社交媒体网站账号</h2>

            <?php if(empty($SMC)){
					echo '<h3>'.smc_no_global_option_tips().'</h3>';
				}else{
				$info=array($_GET['smc_info_type'],$_GET['smc_info']);
				if(isset($_GET['smc_request'])&&isset($_GET['delete_all'])){
					delete_option('weibo_access_token');
					$info=array('updated','你已经删除了所有绑定！');
				}elseif(isset($_GET['smc_request'])&&isset($_GET['delete'])) {
					$weibotok=get_option('weibo_access_token');
					$weibo=trim($_GET['delete']);
					unset($weibotok[$weibo]);
					update_option('weibo_access_token',$weibotok);
					$info=array('updated','你已经删除了'.$SMC[$weibo]['name'].'的绑定！');
				}
				if(isset($info)){
					echo "<div class='{$info[0]}'><p>{$info[1]}</p></div>";
				}
				$tok = get_option('weibo_access_token');//print_r($tok);//die();
				$smc_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__));
				if($tok){
					echo '<h3>您已经绑定以下网站: </h3>';
					echo '<div id="weibobind">';
					foreach($SMC as $weibo=>$arr){
						if($tok[$weibo])echo '<img class="smc_img" src="'.$smc_url.'/images/'.$weibo.'.png" /><a href="'.smc_menu_page_url('social-medias-connect/function.php',false).'&smc_request=smc_admin_option&delete='.$weibo.'"><input type="submit" class="button-primary" value="删除'.$arr['name'].'绑定"></a>';
					}
					echo '</div>';
				}
				if(count($tok)>=10){
					echo '<p>你已经绑定了所有网站，点击下面按钮解除所有绑定</p>';
				}else{
					echo '<p>点击下面的图标，将你的帐号和你的博客绑定，当你的博客更新的时候，会同时更新到绑定的微博。</p>';
				}
				smc_connect(array('showtips'=>0,'smc_auto_js'=>0,'callback_url'=>smc_menu_page_url('social-medias-connect/function.php',false)));
				if($tok){
			?>
				<a href="<?php echo smc_menu_page_url('social-medias-connect/function.php',false); ?>&smc_request=smc_admin_option&delete_all=1"><input type="submit" class="button-primary" value="删除所有绑定"></a>
			<?php
				}
			?>
			<br/><br/>
			如果你需要自定义微博API（即微博小尾巴显示你的网站），请<a href="<?php echo smc_menu_page_url('smc_bind_weibo_help',false) ?>#donate">点此查看详细</a>。通过<a style="color:#fff;" href="https://me.alipay.com/qiqiboy" target="_blank"> <input type="button" name="send_to_me" class="button-primary" value=" 支付宝 " /></a> 捐助我<br/>
			<br/>注意：请在IE8以上版本浏览器（IE8、IE9火狐、chrome、opera等）中进行绑定、解绑操作。如果还有疑问，请到我<a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">博客留言</a>或者在<a href="http://weibo.com/qiqiboy">新浪微博</a>上向我提问，我会帮助你进行绑定。
			<?php } ?>
	</div>
			<?php
}
function smc_bind_weibo_option() {
	?>
	<div class="wrap" style="-webkit-text-size-adjust:none;">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>社交媒体连接设置</h2>
		<?php $smc_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__));
			if(isset($_POST['smc_allowed_weibo'])){
				$allows=get_option('smc_allowed_weibo');
				if($allows){
					foreach($allows as $key => $t){
						if(isset($_POST['smc_allowed_'.$key])){
							$allows[$key]='1';
						}else{
							$allows[$key]='0';
						}
					}
					$info=array('updated','设置成功！');
					update_option('smc_allowed_weibo',$allows);
				}else{
					$info=array('error','设置失败，请刷新页面重试!');
				}
			}
			$allows = get_option('smc_allowed_weibo');
			if(!$allows||count($allows)<14){
				$_allows=array(
					'sinaweibo'=>'1',
					'qqweibo'=>'1',
					'sohuweibo'=>'1',
					'163weibo'=>'1',
					'douban'=>'1',
					'facebook'=>'0',
					'twitter'=>'0',
					'fanfou'=>'1',
					'renren'=>'0',
					'kaixin'=>'0',
					'tianya'=>'1',
					'follow5'=>'0',
					'zuosa'=>'0',
					'wbto'=>'0'
				);
				if($allows)$_allows=array_merge($_allows,$allows);
				update_option('smc_allowed_weibo',$_allows);
				$allows=get_option('smc_allowed_weibo');
			}
			/* 微博设置 */
			$opt=get_option('smc_weibo_options');
			$default_opt=array(
					'smc_auto_connect'=>'1',
					'smc_auto_js'=>'1',
					'smc_weibo_notice'=>'使用微博连接登陆后，请尽快到后台修改您的邮箱地址，以便接收在本站的一些通知及回复。',
					'smc_btn_img'=>'4',
					'smc_front_prefix'=>'【博客更新】',
					'smc_end_prefix'=>'【博文修改】',
					'smc_shorturl'=>'',
					'smc_use_short'=>'1',
					'smc_thumb'=>'1',
					'smc_post_format'=>'%%prefix%%%%title%% %%tags%% - %%url%%',
					'smc_comment_format'=>'我对《%%title%%》的观点: %%comment%% - %%url%%',
					'smc_shorturl_service'=>'sinaurl',
					'smc_auto_remember'=>'0',
					'smc_weibotips'=>'您也可以使用微博账号登陆',
					'smc_post_types'=>smc_get_post_types(),
					'smc_list_style'=>'1',
					'smc_icon_size'=>'32'
				);
			if(empty($opt)||count($opt)<count($default_opt)){
				if($opt)$default_opt=array_merge($default_opt,$opt);
				update_option('smc_weibo_options',$default_opt);
				$opt=$default_opt;
			}
			if(isset($_POST['smc_weibo_option'])){
				foreach($opt as $key => $t){
					if(isset($_POST[$key])){
						$opt[$key]=stripslashes($_POST[$key]);
					}
				}
				update_option('smc_weibo_options',$opt);
			}
		?>
		<form action="<?php echo smc_menu_page_url('smc_bind_weibo_option',false); ?>" enctype="multipart/form-data" method="post">
			<?php if(isset($info)){
					echo "<div class='{$info[0]}'><p>{$info[1]}</p></div>";
				}
			?>
			<h3>勾选允许连接登陆的网站</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">允许的网站请打上勾<br/></th>
						<td>
							<ul id="smc_allow">
							<?php foreach($allows as $key => $t): ?>
								<li class="smc_<?php echo $key; ?>">
									<input type="checkbox" title="<?php echo smc_get_weibo_name($key); ?>" name="smc_allowed_<?php echo $key; ?>"<?php if($t)echo ' checked="checked"'; ?> value="1" />
								</li>
							<?php endforeach; ?>
							</ul>
							<input type="hidden" name="smc_allowed_weibo" value="1" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">自动插入社交媒体连接按钮<br/><br/><small>1.如果选择"是"，将会自动在评论表单、登陆界面处插入连接按钮</small><br/><small>2.如果你选择“否”，那么你需要手动在需要显示连接按钮的地方调用<code>&lt;?php if(function_exists('smc_connect'))smc_connect(); ?></code></small></th>
						<td>
							<input type="radio" name="smc_auto_connect"<?php if($opt['smc_auto_connect'])echo ' checked="checked"'; ?> value="1" /><label>是</label>
							<input type="radio" name="smc_auto_connect"<?php if(!$opt['smc_auto_connect'])echo ' checked="checked"'; ?> value="0" /><label>否</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">启用浮动面板<br/><br/><small>1.如果选择"是"，将会在一个浮动窗口中显示登陆连接按钮（节省页面空间）</small><br/><small>2.如果你选择“否”，登陆连接按钮将会全部列出（占地方）</small></th>
						<td id="smc_is_use_float">
							<input type="radio" name="smc_auto_js"<?php if($opt['smc_auto_js'])echo ' checked="checked"'; ?> value="1" /><label>是</label>
							<input type="radio" name="smc_auto_js"<?php if(!$opt['smc_auto_js'])echo ' checked="checked"'; ?> value="0" /><label>否</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">记住用户登录状态<br/><br/><small>1.如果选择"是"，下次访问网站时将自动登录</small><br/><small>2.如果你选择“否”，下次需要重新登陆</small></th>
						<td>
							<input type="radio" name="smc_auto_remember"<?php if($opt['smc_auto_remember'])echo ' checked="checked"'; ?> value="1" /><label>是</label>
							<input type="radio" name="smc_auto_remember"<?php if(!$opt['smc_auto_remember'])echo ' checked="checked"'; ?> value="0" /><label>否</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">登陆按钮的提示文字<br/><small>留空不显示</small></th>
						<td>
							<textarea name="smc_weibotips" cols="50" rows="1" id="smc_weibotips" style="width:500px;font-size:12px;" class="code"><?php echo htmlspecialchars($opt['smc_weibotips']); ?></textarea>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">选择按钮样式<br/></th>
						<td class="smc_admin_radio">
							<div id="smc_float_style"<?php if(!$opt['smc_auto_js'])echo ' style="display:none;"'; ?>>
								<input type="radio" name="smc_btn_img"<?php if($opt['smc_btn_img']=='1')echo ' checked="checked"'; ?> value="1" /><label><img alt="img 1" src="<?php echo $smc_url; ?>/images/smc_1.png"/></label>
								<input type="radio" name="smc_btn_img"<?php if($opt['smc_btn_img']=='2')echo ' checked="checked"'; ?> value="2" /><label><img alt="img 2" src="<?php echo $smc_url; ?>/images/smc_2.png"/></label>
								<input type="radio" name="smc_btn_img"<?php if($opt['smc_btn_img']=='3')echo ' checked="checked"'; ?> value="3" /><label><img alt="img 3" src="<?php echo $smc_url; ?>/images/smc_3.png"/></label>
								<input type="radio" name="smc_btn_img"<?php if($opt['smc_btn_img']=='4')echo ' checked="checked"'; ?> value="4" /><label><img alt="img 4" src="<?php echo $smc_url; ?>/images/smc_4.png"/></label>
							</div>
							<div id="smc_list_style"<?php if($opt['smc_auto_js'])echo ' style="display:none;"'; ?>>
								<input type="radio" name="smc_list_style"<?php if($opt['smc_list_style']=='1')echo ' checked="checked"'; ?> value="1" /><label><img alt="img 1" src="<?php echo $smc_url; ?>/images/smc_style_1.png"/></label>
								<input type="radio" name="smc_list_style"<?php if($opt['smc_list_style']=='2')echo ' checked="checked"'; ?> value="2" /><label><img alt="img 2" src="<?php echo $smc_url; ?>/images/smc_2.png"/></label>
							</div>
						</td>
					</tr>
					<tr id="smc_icon_size_set" valign="top"<?php if(!$opt['smc_auto_js'] && $opt['smc_list_style']=='2')echo ' style="display:table-row;"'; ?>>
						<th scope="row">登陆按钮图标尺寸<br/><small>正方形图标，默认32</small></th>
						<td>
							<textarea name="smc_icon_size" cols="50" rows="1" id="smc_icon_size" style="width:500px;font-size:12px;" class="code"><?php echo (int)htmlspecialchars($opt['smc_icon_size']); ?></textarea>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">浮动面板右边提示文字</th>
						<td>
							<textarea name="smc_weibo_notice" cols="50" rows="3" id="smc_weibo_notice" style="width:500px;font-size:12px;" class="code"><?php echo htmlspecialchars($opt['smc_weibo_notice']); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			<h3>同步到微博文字前缀</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">发布新文章时</th>
						<td>
							<input type="text" name="smc_front_prefix" class="regular-text" value="<?php echo htmlspecialchars($opt['smc_front_prefix']); ?>" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">修改早期文章时</th>
						<td>
							<input type="text" name="smc_end_prefix" class="regular-text" value="<?php echo htmlspecialchars($opt['smc_end_prefix']); ?>" />
						</td>
					</tr>
				</tbody>
			</table>
			<h3>短链接api</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">启用url缩短功能<br/></th>
						<td>
							<input type="radio" name="smc_use_short"<?php if($opt['smc_use_short'])echo ' checked="checked"'; ?> value="1" /><label>启用</label>
							<input type="radio" name="smc_use_short"<?php if(!$opt['smc_use_short'])echo ' checked="checked"'; ?> value="0" /><label>关闭</label>
						</td>
					</tr>
					<?php 
						$shortsite=array(
							'sinaurl'=>'新浪微博短连接(推荐)',
							'baidudwz'=>'百度短网址(dwz.cn)',
							'bitly'=>'Bit.ly shortener',
							'wp_short'=>'wordpress短网址',
							'custom'=>'自定义短网址服务api'
						)
					?>
					<tr valign="top">
						<th scope="row">请选择短网址服务提供商<br/></th>
						<td>
							<select id="smc_shorturl_service" name="smc_shorturl_service">
						<?php 
							foreach($shortsite as $key => $desc){
						?>
								<option <?php if($opt['smc_shorturl_service']==$key)echo 'selected="selected" '; ?> value="<?php echo $key; ?>"><?php echo $desc; ?></option>
						<?php
							}
						?>
							</select>
						</td>
					</tr>
					<tr valign="top" id="smc_shorturl" style="<?php if($opt['smc_shorturl_service']!=='custom')echo 'display:none'; ?>">
						<th scope="row">输入短连接服务的api地址</th>
						<td>
							<input type="text" name="smc_shorturl" class="regular-text" value="<?php echo htmlspecialchars($opt['smc_shorturl']); ?>" /><label> 例如：<code>http://myshort.com/api.php?format=simple&action=shorturl&url=</code></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" style="color:red;">向我推荐优秀的短网址服务</th>
						<td>
							<label>如果你正在经营着一家短网址服务网站，或者你知道一些优秀的短网址服务站点，欢迎向我推荐。<br/>微博FO我<a href="http://weibo.com/qiqiboy">@qiqiboy</a>，<a href="http://www.qiqiboy.com">我的博客</a>留言，或者邮件&Gtalk与我联系:imqiqiboy#gmail.com。</label>
						</td>
					</tr>
				</tbody>
			</table>
			<h3>文章同步</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">支持的文章类型<br/><br/>博客所有文章类型:<br/><small><?php echo smc_get_post_types(true); ?></small><br/></th>
						<td>
							<input type="text" name="smc_post_types" class="regular-text" style="width:500px;" value="<?php echo htmlspecialchars($opt['smc_post_types']); ?>" /><br/>输入需要支持同步的文章类型，多个之间以半角<code>, </code>分隔。
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">文章同步格式<br/><br/><small>%%prefix%%: 文章前缀<br/>%%title%%: 文章标题<br/>%%url%%: 文章链接<br/>%%tags%%: 文章标签<br/>%%excerpt%%: 文章摘要</small><br/></th>
						<td>
							<textarea name="smc_post_format" cols="50" rows="3" id="smc_post_format" style="width:500px;font-size:12px;" class="code"><?php echo htmlspecialchars($opt['smc_post_format']); ?></textarea><br/>你可以随意调整标题、链接、标签等的顺序，并且可以插入其他内容
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">同步文章缩略图<br/><small>如果没有设置文章缩略图，那么插件会提取文章中第一张图片进行同步。<br/><br/>如果开启缩略图同步时容易出现500错误或者页面显示不可用，那么请最好暂时停用缩略图同步。</small></th>
						<td>
							<input type="radio" name="smc_thumb"<?php if($opt['smc_thumb'])echo ' checked="checked"'; ?> value="1" /><label>同步文章缩略图</label>
							<input type="radio" name="smc_thumb"<?php if(!$opt['smc_thumb'])echo ' checked="checked"'; ?> value="0" /><label>不同步缩略图</label>
						</td>
					</tr>
				</tbody>
			</table>
			<h3>评论同步</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">评论同步格式<br/><br/><small>%%title%%: 文章标题<br/>%%url%%: 评论链接<br/>%%comment%%: 评论内容</small><br/></th>
						<td>
							<textarea name="smc_comment_format" cols="50" rows="3" id="smc_comment_format" style="width:500px;font-size:12px;" class="code"><?php echo htmlspecialchars($opt['smc_comment_format']); ?></textarea><br/>你可以随意调整标题、链接、标签等的顺序，并且可以插入其他内容
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="option_save" class="button-primary" value="保存设置" />
				<input type="hidden" name="smc_weibo_option" value="1" />
			</p>
		</form><br><br><br>
		<code>提示：请在文章编辑页面下部找到“同步文章状态”一栏，查看当前文章的同步状态，并选择文章是否同步。<br/> 草稿、私密文章、早期文章不同步。如果要同步，请选择“同步”(私密、草稿无效)。<br/> 有任何问题请与我联系(gtalk&gmail:imqiqiboy@gmail.com,也可博客与我交流:http://www.qiqiboy.com/)</code>
	</div>
	<?php
}
function smc_bind_weibo_help(){
	require_once 'help.php';
}
function smc_bind_weibo_acount(){
	require_once 'bind.php';
}
/* sync your entry to social media sites */
function smc_publish_post($post_ID=""){
	$SMC=wp_cache_get('global_option','smc');
	if($_POST['smc_must_sync']==='no' || $_POST['action'] == "autosave" || $_POST['action'] == "inline-save" || $_POST['post_status'] == "draft" || $_POST['post_status'] == "private" || isset($_GET['bulk_edit']) || isset($_POST['_inline_edit'])){
		return false;
	}
	$weibotok = get_option('weibo_access_token');
	$weiboopt=get_option('smc_weibo_options');
	if(!$weibotok || empty($SMC)) return;
	$_post=get_post($post_ID);
	$thumb=$weiboopt['smc_thumb']?smc_post_thumb($_post):'';
	$prefix=$_POST['original_post_status'] == 'publish' || $_POST['original_post_status'] == 'private' ? $weiboopt['smc_end_prefix'] : $weiboopt['smc_front_prefix'];
	$url=get_permalink($post_ID);
	if($weiboopt['smc_use_short']){
		$url=smc_shorturl($weiboopt['smc_shorturl_service'],$url,$post_ID);
	}
	$excerpt=$_post->post_excerpt?strip_tags($_post->post_excerpt):strip_tags($_post->post_content);
	$content=array('prefix'=>$prefix,'text'=>$_post->post_title,'url'=>wp_urlencode($url),'tags'=>wp_get_post_tags($_post->ID),'excerpt'=>$excerpt);
	foreach($weibotok as $weibo => $tok){
		if((!$_POST['smc_must_sync'] && !defined('DOING_CRON') || $_POST['smc_must_sync']==='only') && get_post_meta($post_ID, '_'.$weibo.'_sync', true)){
			continue;
		}
		if(isset($SMC[$weibo])){
			if(!class_exists($SMC[$weibo]['OAuthClass'])){
				include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
			}
			$rfunc="smc_{$weibo}_weibo_update";
			$resp=$rfunc($content,$thumb,$tok);
			if($resp){
				if(!update_post_meta($post_ID, '_'.$weibo.'_sync', 'true'))
					add_post_meta($post_ID, '_'.$weibo.'_sync', 'true', true);
				if(!update_post_meta($post_ID, '_'.$weibo.'_sync_id', $resp))
					add_post_meta($post_ID, '_'.$weibo.'_sync_id', $resp, true);//9105332181
			}
		}
	}
}

/* sync comment to social media sites */
add_action('comment_post', 'smc_comment_post',999);
function smc_comment_post($id){
	$SMC=wp_cache_get('global_option','smc');
	$comment_post_id = $_POST['comment_post_ID'];
	if(empty($comment_post_id) || empty($SMC)){
		return;
	}
	$current_comment = get_comment($id);
	$current_post = get_post($comment_post_id);
	$smcdata = get_user_meta($current_comment->user_id, 'smcdata',true);
	$weibo=$smcdata['smcweibo'];
	if($smcdata){
		if($_POST['post_to_socialmedias']){
			$weiboopt=get_option('smc_weibo_options');
			$url=get_permalink($comment_post_id);
			if($weiboopt['smc_use_short']){
				$url=smc_shorturl($weiboopt['smc_shorturl_service'],$url,$comment_post_id);
			}
			$p=array(
				"title"=>get_the_title($comment_post_id),
				"url"=>wp_urlencode($url."#comment-".$id),
				"comment"=>strip_tags($current_comment->comment_content),
				"weibosync"=>get_post_meta($comment_post_id, '_'.$weibo.'_sync_id', true)
			);
			if(isset($SMC[$weibo])){
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$rfunc="smc_{$weibo}_weibo_repost";
				$rfunc($p,$smcdata);
			}
			setcookie('post_to_socialmedias_' . COOKIEHASH, 'no', time(), COOKIEPATH, COOKIE_DOMAIN);
		}else{
			$comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
			setcookie('post_to_socialmedias_' . COOKIEHASH, 'no', time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
		}
	}
}

function smc_add_custom_box(){
	$weiboopt=get_option('smc_weibo_options');
	$smc_types=preg_split("/[,\s\|]+\s*/",$weiboopt['smc_post_types']);
	foreach($smc_types as $type){
		add_meta_box( 
			'smc-meta_box','社交媒体连接','smc_weibo_must_sync',$type,'side','high'
		);
	}
}
function smc_weibo_must_sync(){
	global $post;
	$SMC=wp_cache_get('global_option','smc');
	if(empty($SMC)){
		echo '<div class="misc-pub-section" style="line-height:18px;">'.smc_no_global_option_tips().'</div>';
		return false;
	}
	$weiboarray=array_keys($SMC);
	$weibotok=get_option('weibo_access_token');
	$weiboname='';$bindweibo='';$synced='';
	if($weibotok){
		foreach($weibotok as $weibo => $tok){
			$bindweibo.=' '.($_weibo=smc_get_weibo_name($weibo));
		}
	}
	foreach($weiboarray as $weibo){
		$_synced=get_post_meta($post->ID, '_'.$weibo.'_sync', true);
		if($_synced){
			$synced='true';
			$weiboname.=' '.smc_get_weibo_name($weibo);
		}
	}
	$post_status=get_post_status($post->ID);
	switch($post_status){
		case 'publish':$status_info='这篇文章已经发布，默认不会同步。';$synced='true';
						break;
		case 'private':$status_info='这篇文章是私密文章，默认不会同步。';$synced='true';
						break;
		case 'future':$status_info='这篇文章是定时发布，将在设定的时间同步到微博。';$synced='';
						break;
		case 'auto-draft':$status_info='这篇文章是您新建的，点击“发布”将会同步到您的绑定微博。';
						break;
		case 'draft':$status_info='这篇文章是您之前保存的草稿，点击“发布”将会同步到您的绑定微博。';
						break;
		case 'pending':$status_info='这篇文章等待复审，通过后将会根据选择情况进行同步';
						break;
		default:break;
	}
	if($status_info) echo '<div class="misc-pub-section" style="line-height:18px;"><b>温馨提示: </b><br/>'.$status_info.'</div>';
	if(empty($bindweibo)){
		echo '<div class="misc-pub-section" style="line-height:18px;"><b>绑定状态: </b><br/>您还未绑定任何微博。如果要将文章同步到微博，请立即到<a href="'.smc_menu_page_url('social-medias-connect/function.php',false).'">Social Medias Connect</a>设置页面绑定您的微博账号。</div>';
	}else{
	echo '<div class="misc-pub-section" style="line-height:18px;"><b>绑定状态: </b><br/>您已经绑定了<strong>{'.$bindweibo.' }</strong>。</div>';
	if(!empty($weiboname)) echo '<div class="misc-pub-section smc-last2-child" style="line-height:18px;"><b>同步状态: </b><br/>这篇文章已经同步到了{<strong>'.$weiboname.'</strong> }。<br/>如果你要再次进行同步，请确保选中“同步”状态。</div>';
	else 			echo '<div class="misc-pub-section smc-last2-child" style="line-height:18px;"><b>同步状态: </b><br/>这篇文章未同步到任何微博。如果你要进行同步，请确认以选中了“同步按钮”。</div>';
	echo			'<div class="misc-pub-section smc-last-child" style="background:#EAF2FA;line-height:18px;"><b>同步到社交网站: </b><br/><input type="radio" name="smc_must_sync" value="no"';
	if(isset($synced)&&!empty($synced)) echo ' checked="checked"';
	echo 			'/><label>不同步</label> <br/>';
	echo			'<input type="radio" name="smc_must_sync" value="yes"';
	if(!isset($synced)||empty($synced)) echo ' checked="checked"';
	echo 			'/><label>全部同步</label> <br/>';
	echo			'<input type="radio" name="smc_must_sync" value="only"';
	echo 			'/><label>仅同步未同步过的微博</label> <br/><label>注意，只有选中“同步”状态，才会将文章同步到微博。</label></div>';
	}
	echo '<div class="clear"></div>';
}
add_action('add_meta_boxes', 'smc_add_custom_box');

add_action('wp_dashboard_setup', 'smc_dashboard_widgets');
function smc_dashboard_widgets() {
	return;
	wp_add_dashboard_widget('smc-dashboard-widget', 'I\'m qiqiboy! 博客更新', 'smc_dashboard_widget_show');
}
function smc_dashboard_widget_show() {?>
	<p><a href="http://www.qiqiboy.com" title="qiqiboy" target="_blank"><img src="http://www.qiqiboy.com/wp-content/themes/windPaled/images/title.png" alt="WordPress JAM"></a><br />
	Hello, <a href="http://www.qiqiboy.com" title="I'm qiqiboy !" target="_blank">I'm qiqiboy !</a> 本人长期从事wordpress开发相关，有多个海外项目经验。承接wordpress开发、插件定制等，有意者<a href="http://www.qiqiboy.com/about/contact" target="_blank">这里</a>获取我的联系方式。</p>
	<hr />
<?php 
	echo '<div class="rss-widget">
			<h4><a href="http://feed.qiqiboy.com">订阅I\'m iqiqboy!</a></h4><br/>
	';
		wp_widget_rss_output('http://www.qiqiboy.com/feed', array( 'show_author' => 0, 'items' => 5, 'show_date' => 1, 'show_summary' => 0 ));
	echo '<h4><a href="http://www.qiqiboy.com/tags/%E7%A4%BE%E4%BA%A4%E5%AA%92%E4%BD%93%E8%BF%9E%E6%8E%A5/feed">订阅社交媒体连接</a></h4><br/>';
		wp_widget_rss_output('http://www.qiqiboy.com/tags/%E7%A4%BE%E4%BA%A4%E5%AA%92%E4%BD%93%E8%BF%9E%E6%8E%A5/feed', array( 'show_author' => 0, 'items' => 5, 'show_date' => 1, 'show_summary' => 0 ));
	echo '<hr/>如果你对我开发社交媒体连接插件有兴趣，希望可以针对你的网站进行api自定义，欢迎与我联系进行插件定制（捐助100以上可获得自定义api版本，如需帮忙申请api，请与我联系）。(邮件 or Gtalk[支付宝]：imqiqiboy@gmail.com)';
	echo "</div>";
}

function smc_post_thumb($post=false ){
    if(!$post)global $post;

	$timthumb_src='';
	if( smc_has_post_thumbnail($post->ID)){
		$timthumbs = wp_get_attachment_image_src(smc_get_post_thumbnail_id($post->ID),'full');
		$timthumb_src=$timthumbs[0];
	}else{
		preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $index_matches);
		$first_img_src = $index_matches [1];
		if($first_img_src){
			$timthumb_src=$first_img_src;
		}
	}
	return $timthumb_src;
}
if(!function_exists('smc_has_post_thumbnail')){
	function smc_has_post_thumbnail( $post_id = NULL ) {
		global $id;
		$post_id = ( NULL === $post_id ) ? $id : $post_id;
		return !! smc_get_post_thumbnail_id( $post_id );
	}
	function smc_get_post_thumbnail_id( $post_id = NULL ) {
		global $id;
		$post_id = ( NULL === $post_id ) ? $id : $post_id;
		return get_post_meta( $post_id, '_thumbnail_id', true );
	}
}
if(!function_exists('wp_urlencode')){
	function wp_urlencode($url) {
		$a = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%23', '%5B', '%5D');
		$b = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "#", "[", "]");
		$url = str_replace($a, $b, urlencode($url));
		return $url;
	}
}
function smc_is_administrator(){
	$user = wp_get_current_user(); $is_administrator=false;
	if((($caps=$user->caps) && !empty($caps['administrator'])) || (($allcaps=$user->allcaps) && !empty($allcaps['level_10'])) || $user->data->wp_user_level >=10 || $user->data->user_level >=10)$is_administrator=true;
	return $is_administrator;
}
function smc_get_post_types($flag=false){
	$types=get_post_types();$base=$flag?array('post','page'):array('post');
	$smc_types=array_slice($types,5);
	$smc_types=array_merge($base,$smc_types);
	return join(', ',$smc_types);
}
function smc_get_client_ip(){
	if(getenv('HTTP_CLIENT_IP')){
		$client_ip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR')) {
		$client_ip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR')) {
		$client_ip = getenv('REMOTE_ADDR');
	} else {
		$client_ip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
	}
	return $client_ip;
}
function get_current_page_url(){
    $current_page_url = 'http';
    if ($_SERVER["HTTPS"] == "on") {
        $current_page_url .= "s";
    }
     $current_page_url .= "://";
     if ($_SERVER["SERVER_PORT"] != "80") {
    $current_page_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $current_page_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $current_page_url;
}
function smc_get_appkey(){
	global $is_iphone;
	if(!$_GET['weibo']){
		$head_info='<script type="text/javascript">alert("Something Error!");window.close();</script>';
	}elseif($_POST['wp-submit']){
		if(!$_POST['appkey'] || !$_POST['appsecret']){
			$error_info='请重新输入！';
		}else{
			update_option('smc_'.$_GET['weibo'].'_custom_appkey',array('app_key'=>$_POST['appkey'],'app_secret'=>$_POST['appsecret']));
			if($_GET['callback']!=''){
				wp_redirect((WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__))).'/start-connect.php?socialmedia='.$_GET['weibo'].'&callback_url='.urlencode($_GET['callback']));
			}else $head_info='<script type="text/javascript">alert("修改成功!");window.opener.smc_reload("");window.close();</script>';
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<title><?php bloginfo('name'); ?> &rsaquo; 设置<?php echo smc_get_weibo_name($_GET['weibo']); ?>的appkey</title>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<?php
	wp_admin_css( 'login', true );
	wp_admin_css( 'colors-fresh', true );
	if ( $is_iphone ) { ?>
	<meta name="viewport" content="width=320; initial-scale=0.9; maximum-scale=1.0; user-scalable=0;" />
	<style type="text/css" media="screen">
	form { margin-left: 0px; }
	#login { margin-top: 20px; }
	</style>
<?php
	}
	if($head_info){echo "\n".$head_info.'</head><body></body></html>';die();}
?>
	<style type="text/css">
		h1 a{
			background:url(<?php echo (WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__))).'/images/smc_3.png' ?>) no-repeat;
			width:64px;
			height:64px;
			margin-left:110px;
		}
		#login { margin-top: 0px; }
	</style>
</head>
<body class="login">
<div id="login"><h1><a href="http://www.qiqiboy.com/products/plugins/social-medias-connect" title="社交媒体连接">社交媒体连接</a></h1>
<?php if(!empty($error_info))echo '<div id="login_error">' .$error_info. '</div>'; ?>
<form action="" method="post">
	<p>
		<label>App Key(facebook为appId)<br />
		<input type="text" name="appkey" id="app_key" class="input" value="<?php echo esc_attr($_POST['appkey']?$_POST['appkey']:$_GET['appkey']); ?>" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label>App Secret<br />
		<input type="text" name="appsecret" id="appsecret" class="input" value="<?php echo esc_attr($_POST['appsecret']?$_POST['appsecret']:$_GET['appsecret']); ?>" size="20" tabindex="10" /></label>
	</p>
	<input type="hidden" name="smc_weibo" value="<?php echo esc_attr( $redirect_to ); ?>" />
	<p><strong>注意：</strong>设置<?php echo smc_get_weibo_name($_GET['weibo']); ?>的appkey</p>
	<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="提交" tabindex="100" /></p>
</form>
</div>
</body>
</html>
<?php
}
function smc_get_weibo_name($weibo){
	$SMC=wp_cache_get('global_option','smc');
	if(isset($SMC[$weibo])){
		$weibo=$SMC[$weibo]['name'];
	}else{
		$weibo='xxx微博';
	}
	return $weibo;
}
function smc__install(){
	smc_get_global_option();
}
function smc_get_global_option($url=''){
	$url=$url?$url:'http://3.socialmedias.sinaapp.com/';
	$resp=smc_http($url,'POST',array());
	if($resp['info']=='success'){
		$SMC=$resp['result'];
		update_option('smc_global_option',$SMC);
		return true;
	}elseif($resp['info']=='error'){
		return $resp;
	}
	return false;
}
function smc_http($url, $method, $postfields = NULL , $header = array()){
	$http=new WP_Http();
	$header['smc-request-url']=get_bloginfo('wpurl');
	$response=$http->request($url,array(
		"method"=>$method,
		"timeout"=>26,
		"user-agent"=>'smc_appkey_opt',
		"body"=>$postfields,
		"headers"=>$header
	));
	if(!is_array($response)){
		return array('info'=>'error','result'=>'Error Info: '.$response->errors['http_request_failed'][0]);
	}
	return json_decode($response['body'],true);
}
function smc_get_weibo_appkey($weibo){
	$SMC=wp_cache_get('global_option','smc');
	$opt=$SMC[$weibo];
	if(empty($SMC) || !$weibo || empty($opt)){
		wp_die('插件需要更新配置！请根据后台提示进行操作！');
	}
	if(in_array('customappkey',$opt['supports'])){
		$appkey=get_option('smc_'.$weibo.'_custom_appkey');
		if(empty($appkey)){
			if(smc_is_administrator()){
				global $callback_url;
				$getappkeylink=get_bloginfo('home').'?action=getappkey&weibo='.$weibo.'&callback='.$callback_url;
				wp_redirect($getappkeylink);
				die();
			}else{
				wp_die('No appkey!! Please contact the administrator of this site.');
			}
		}
		return array($appkey['app_key'],$appkey['app_secret']);
	}
	return $opt['appkey'];
}
function smc_no_global_option_tips(){
	return '插件需要更新配置才能使用！';
}
function smc_new_user_email($userdata=array(),$weibo){
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	$name = smc_get_weibo_name($weibo);
	$uname = $userdata['display_name'];
	$ulogin = $userdata['smc_user_login'];
	$uemail = $userdata['smc_user_email'];
	$uurl = $userdata['user_url'];
	$subj = "新的{$name}连接用户注册 - $blogname";
	$body = "在 $blogname 新注册用户信息：\r\n";
	$body.= "用户名：$ulogin\r\n";
	$body.= "昵称：$uname\r\n";
	$body.= "邮箱：$uemail\r\n";
	$body.= "地址：$uurl\r\n";
	$body.= "\r\n";
	$body.= "-----------------------------------\r\n";
	$body.= "这是一封自动发送的邮件。 \r\n";
	$body.= "来自 {$blogname}。\r\n";
	$body.= "请不要回复本邮件。\r\n";
	$body.= "Powered by © Social Medias Connect。\r\n";
	$admin_email = get_option('admin_email');
	wp_mail($admin_email, $subj, $body, $headers = '');
}
function smc_new_user_notification($user_id,$user_pass,$weibo){
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	$name = smc_get_weibo_name($weibo);
	$user = new WP_User($user_id);
	$user_login = stripslashes($user->user_login);
	$user_email = stripslashes($user->user_email);
	$subj = "您的用户名和密码 - $blogname";
	$body = "您使用{$name}账号在 $blogname 的注册信息：\r\n";
	$body.= "用户名：$user_login\r\n";
	$body.= "密码：$user_pass\r\n";
	$body.= "登陆地址：".site_url('/wp-login.php')."\r\n";
	$body.= "\r\n";
	$body.= "-----------------------------------\r\n";
	$body.= "这是一封自动发送的邮件。 \r\n";
	$body.= "来自 {$blogname}。\r\n";
	$body.= "请不要回复本邮件。\r\n";
	$body.= "Powered by © Social Medias Connect。\r\n";
	wp_mail($user_email, $subj, $body, $headers = '');
}
function smc_strlen($str='',$twitter=false){//twitter和四大微博字数计数方式不一样
	$length = strlen(preg_replace('/[\x00-\x7F]/', '', $str));
    if($twitter){
		if ($length){
			return strlen($str) - $length + intval($length / 3) ;
		}else{
			return strlen($str);
		}
	}else{
		if ($length){
			return (strlen($str) - $length)/2 + intval($length / 3) ;
		}else{
			return strlen($str)/2;
		}
	}
}
/*
function smc_substr($str,$start,$length=false){
	$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
	preg_match_all($pa, $str, $t_str);
	if(count($t_str[0]) > $length) {
		$str = join('', array_slice($t_str[0], $start, $length));
	}
	return $str;
}
*/
function smc_substr($str, $from=0, $length=false, $old='', $twitter=false){
	$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
	if(is_array($str)){
		$t_str=$str;
	}else preg_match_all($pa, $str, $t_str);
	if(count($t_str[0]) > $length) {
		$_str = join('', array_slice($t_str[0], $from, $length));
		if(($_length=($length-smc_strlen($_str,$twitter)))>0){
			return smc_substr($t_str,intval($from+$length), intval($_length), $old.$_str, $twitter);
		}
		return $old.$_str;
	}
	return $old.$str;
}
function get_weibo_str_length($data='',$length=140,$twitter=false,$most_tags=999){
	$weiboopt=get_option('smc_weibo_options');
	$format=$weiboopt['smc_post_format'];
	if(is_array($data)){
		$title=$data['text'];$url=$data['url'];$prefix=$data['prefix'];
		$tags=$data['tags']?smc_convtags($data['tags'],$twitter,$most_tags):array();
		$excerpt=$data['excerpt'];
		if(($url_length=smc_strlen($url,$twitter))>$length)return false;
		$format=str_ireplace('%%title%%',$title,$format);
		$format=str_ireplace('%%prefix%%',$prefix,$format);
		$format=str_ireplace('%%url%%',$url,$format);
		$_format=$format;
		if(($prefix_title_url_length=smc_strlen(preg_replace('/%%tags%%|%%excerpt%%/','',$_format),$twitter))<$length){
			$t_count=0;$new_tags=array();
			if(stripos($format,'%%tags%%')>=0){
				foreach($tags as $tag){
					if($t_count+($_t_count=smc_strlen($tag,$twitter)+1)<$length-$prefix_title_url_length){
						$new_tags[]=$tag;
						$t_count+=$_t_count;
					}else break;
				}
				$format=str_ireplace('%%tags%%',join(' ',$new_tags),$format);
			}else{
				$format=preg_replace('/%%tags%%/','',$format);
			}
			if($excerpt&&($prefix_title_url_tags_length=$prefix_title_url_length+$t_count)<$length-3){
				$format=str_ireplace('%%excerpt%%',smc_substr($excerpt,0,$length-$prefix_title_url_tags_length,'',$twitter).'...',$format);
			}else{
				$format=preg_replace('/%%excerpt%%/','',$format);
			}
		}else{
			$format=smc_substr($prefix.$title,0,$length-$url_length-6).'... - '.$url;
		}
		$_temp=$format;
	}else{
		if(smc_strlen($data,$twitter)>=$length){
			$_temp=smc_substr($data,0,$length-3,'',$twitter).'...';
		}else $_temp=$data;
	}
	return $_temp;
}
function smc_convtags($tags,$twitter=false,$most_tags=999){
	$output=array();$count=1;
	foreach($tags as $tag){
		$output[] = '#'. str_ireplace(' ','-',trim($tag->name)) .($twitter&&$twitter==='twitter'?'':'#');
		if($count++==$most_tags)break;
	}
	return $output;
}
function get_comment_str_length($data,$length=140,$twitter=false){
	$weiboopt=get_option('smc_weibo_options');
	$format=$weiboopt['smc_comment_format'];
	$title=$data['title'];$url=$data['url'];$comment=$data['comment'];
	if(($url_length=smc_strlen($url,$twitter))>$length)return false;
	$_format=$format=str_ireplace('%%title%%',$title,$format);
	if(($_length=smc_strlen(preg_replace('/%%comment%%|%%url%%/','',$_format),$twitter)+$url_length-$length)>0){
		$_temp=smc_substr($_format,0,$length-6-$url_length,'',$twitter).'... - '.$url;
	}else{
		if(smc_strlen($comment,$twitter)>abs($_length)){
			$comment=smc_substr($comment,0,abs($_length)-3,'',$twitter).'...';
		}
		$format=str_ireplace('%%comment%%',$comment,$format);
		$format=str_ireplace('%%url%%',$url,$format);
		$_temp=$format;
	}
	return $_temp;
}
function smc_shorturl($t,$url,$post_id){
	$weiboopt=get_option('smc_weibo_options');
	$re=new WP_Http();
	$shorturl='';
	switch($t){
		case 'custom':
				if($weiboopt['smc_shorturl']){
					$response=$re->request($weiboopt['smc_shorturl'].$url);
					if(is_array($response)){
						$shorturl=$response['body'];
					}
				}
				break;
		case 'baidudwz':
				$response=$re->request('http://dwz.cn/create.php',array('method' => 'POST','body'=>array('url'=>$url)));
				if(is_array($response)){
					$response=json_decode($response['body']);
					if($response->status=='0'){
						$shorturl=$response->tinyurl;
					}
				}
				break;
		case 'bitly':
				$response=$re->request('http://api.bitly.com/v3/shorten?login=qiqiboy&apiKey=R_580153ea12cdeedc598e81f486d10a14&format=json&longUrl='.$url);
				if(is_array($response)){
					$response=json_decode($response['body']);
					if($response->status_code=='200'){
						$shorturl=$response->data->url;
					}
				}
				break;
		case 'sinaurl':
				$response=$re->request('http://api.t.sina.com.cn/short_url/shorten.json?source=3033277072&url_long='.urlencode($url));
				if(is_array($response)){
					$response=json_decode($response['body']);
					if(!$response->error){
						$shorturl=$response[0]->url_short;
					}
				}
				break;
		case 'wp_short':
		default:$shorturl=get_option('home').'?p='.$post_id;break;
	}
	if(substr($shorturl,0,7)=="http://")return $shorturl;
	else return smc_shorturl('wp_short',$url,$post_id);
}
/******************************************/
/***********************/
/************/
function smc_weibo_timeline($args=array()){
	$SMC=wp_cache_get('global_option','smc');
	if(empty($SMC)){
		echo smc_no_global_option_tips();
		return false;
	}
	$defaults=array(
		"weibo"=>"",
		"number"=>5,
		"type"=>"user_timeline",
		"expire"=>5,
		"format"=>'',
		"length"=>200,
		"size"=>48,
		"retweet"=>1
	);
	$r = wp_parse_args( $args, $defaults );
	extract( $r );
	$weibotok = get_option('weibo_access_token');
	if(!$weibotok)$weibotok=array();
	$expire=(int)$expire<1?5:(int)$expire;
	if((!$cachedata = get_option( 'smc_'.$weibo.'_timeline_cache')) || time()-$cachedata['updatetime']>60*$expire || $cachedata['type'] !== $type || $cachedata['weibo'] !== $weibo || $cachedata['number'] !== $number || $cachedata['format'] !== $format){
		$weibos= array_keys($weibotok);
		if(in_array($weibo,$weibos)){
			$tok=$weibotok[$weibo];
			if(isset($SMC[$weibo])){
				if(!in_array('timeline',$SMC[$weibo]['supports'])){
					echo '暂不支持此微博，请等待插件更新。';
					return false;
				}
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$rfunc="smc_{$weibo}_weibo_timeline";
				$data=$rfunc($r,$tok);
			}
			if($data){
				update_option('smc_'.$weibo.'_timeline_cache', array('data'=>$data,'updatetime'=>time(),'type'=>$type,'weibo'=>$weibo,'number'=>$number,'format'=>$format));
			}else{
				echo '数据获取失败，请稍后再试';
				return false;
			}
		}else{
			echo '授权异常: 你未绑定你选择的微博或者授权失效，请到后台重新绑定微博';
			return false;
		}
	}else{
		$data=$cachedata['data'];
	}
	
	if($data){
		$random_num = mt_rand(1,999999999);
		$output = '<script type="text/javascript">if(window.smcJS){if(window.jQuery)jQuery(document).ready(function(){smcJS.smcpic("'.$random_num.'");});else smcJS.documentReady(function(){smcJS.smcpic("'.$random_num.'");});}</script>';
		$output .= '<ul id="smc-weibo-list-'.$random_num.'">';
		foreach($data as $w){
			smc_format_weibo_data(&$output,$r,$w);
		}
		$output .='</ul>';
	}
	echo $output;
}
function smc_format_weibo_data(&$output,$r,$w,$_c=''){
	extract( $r );
	$output .= '<li id="smc-'.$w['id'].'" class="smc-weibo'.$_c.' '.$weibo.'">';
	$text = $w['text']; $avatar = $w['avatar'] ? '<img width="'.$size.'" height="'.$size.'" class="avatar avatar-'.$size.'" src="'.$w['avatar'].'" alt="'.$w['author'].'" />':'';
	$replace = array($avatar,$w['author'],$text,$w['time'],$w['thumb'],$w['source'],$w['url']);
	$_format = array('%%avatar%%','%%author%%','%%excerpt%%','%%date%%','%%image%%','%%source%%','%%url%%');
	if($format){
		$output .= str_replace($_format,$replace,$format);
	}else{
		$output .= '<div class="smc-avatar"><img src="'.$w['avatar'].'" alt="'.$w['author'].'" /></div><div class="smc-author">'.$w['author'].'</div><div class="smc-excerpt">'.$text.'</div>';
	}
	if($retweet&&!empty($w['retweeted_status'])){
		$output .= '<ul class="smc-child smc-retweet-list">'; 
		smc_format_weibo_data(&$output,$r,$w['retweeted_status'],' smc-retweet-weibo');
		$output .= '</ul>'; 
	}
	$output .= '</li>';
}
function smc_time_since($timestamp) {
	$since = abs(time()-$timestamp); $gmt_offset = get_option('gmt_offset') * 3600;
	$timestamp += $gmt_offset; $current_time = mktime() + $gmt_offset;
	if(floor($since/3600)){
		if(gmdate('Y-m-d',$timestamp) == gmdate('Y-m-d',$current_time)){
			$output = '今天 ';
			$output.= gmdate('H:i',$timestamp);
		}else{
			if(gmdate('Y',$timestamp) == gmdate('Y',$current_time)){
				$output = gmdate('m月d日 H:i',$timestamp);
			}else{
				$output = gmdate('Y年m月d日 H:i',$timestamp);
			}
		}
	}else{
		if(($output=floor($since/60))){
			$output = $output.'分钟前';
		}else $output = '刚刚';
	}
	return $output;
}
function smc_to_html($text='',$weibo=''){
	$text = make_clickable($text);
	$text = preg_replace_callback('/@([\x{4e00}-\x{9fa5}0-9A-Za-z_\-]+)/iu', '_smc_'.$weibo.'_make_at_user', $text);
	$text = $weibo=='twitter'||$weibo=='163'?preg_replace_callback('/#([^#\s]+)#?/is', '_smc_'.$weibo.'_make_topic', $text):preg_replace_callback('/\#([^#]+?)#/is', '_smc_'.$weibo.'_make_topic', $text);
	return $text;
}
