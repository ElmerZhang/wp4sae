<?php
global $APP_KEY, $APP_SECRET, $config;
$smc_appkey=smc_get_weibo_appkey('kaixin');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];

$config	= new stdClass;
$config->AUTHORIZEURL = 'http://api.kaixin001.com/oauth2/authorize';
$config->ACCESSTOKENURL = 'http://api.kaixin001.com/oauth2/access_token';
$config->CALLBACK = $_SESSION['smc_callback_url'];
$config->APIKey		= $APP_KEY;
$config->SecretKey	= $APP_SECRET;
$config->APIVersion	= '1.0';
$config->decodeFormat	= 'json';

require_once(dirname(__FILE__).'/base_kaixin.php');

class KaiXinOAuth extends KXClient {}

function smc_kaixin_verify_credentials(){
	$config->CALLBACK = $_SESSION['smc_callback_url'];
	$to=new KaiXinOAuth();
	$tok=$to->getAccessTokenFromCode($_GET['code']);
	$to=new KaiXinOAuth($tok->access_token);
	$weiboInfo=$to->get('users/me',array('fields'=>'uid,name,gender,logo120,intro'));
	if($weiboInfo->error_code){
		echo '<script type="text/javascript">alert("Something Error!");window.close();</script>';
		exit;
	}
	$r=array(
		'profile_image_url'=>$weiboInfo->logo120,
		'user_login'=>$weiboInfo->uid,
		'display_name'=>$weiboInfo->name,
		'user_url'=>'http://www.kaixin001.com/home/?uid='.$weiboInfo->uid,
		'user_email'=>$weiboInfo->uid.'@kaixin.com',
		'oauth_access_token'=>$tok->access_token,
		'oauth_access_token_secret'=>'',
		'friends_count'=>'',
		'followers_count'=>'',
		'location'=>'',
		'description'=>$weiboInfo->intro,
		'statuses_count'=>'',
		'emailendfix'=>'kaixin.com',
		'usernameprefix'=>'kaixin_',
		'weibo'=>'kaixin'
	);
	return $r;
}
function smc_kaixin_weibo_update($data,$thumb,$tok){
	$data['tags']='';
	$content=get_weibo_str_length($data);
	$to=new KaiXinOAuth($tok['oauth_token']);
	$param = array(
		'content' => $content,
	);
	if($thumb){
		$param['picurl']=$thumb;
		$resp = $to->post('records/add',$param,true);
		if($resp->error_code){
			return smc_kaixin_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('records/add',$param);
	}
	if($resp->error_code){
		return false;
	}else{
		return $resp->rid;
	}
}
function smc_kaixin_weibo_repost($p,$smcdata){
	$content=get_comment_str_length($p);
	return smc_kaixin_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
}
?>