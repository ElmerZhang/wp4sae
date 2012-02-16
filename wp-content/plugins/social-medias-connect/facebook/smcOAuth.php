<?php
global $APP_ID, $APP_SECRET;$smc_appkey=smc_get_weibo_appkey('facebook');
$APP_ID=$smc_appkey[0];//'114940795265369';
$APP_SECRET=$smc_appkey[1];//'dccb702c085eba482df9f70a834dab70';
require_once(dirname(__FILE__).'/base_facebook.php');
require_once(dirname(__FILE__).'/facebook.php');

class FacebookOAuth extends Facebook{}

function smc_facebook_verify_credentials(){
	global $APP_ID, $APP_SECRET;
	$facebook = new FacebookOAuth(array(
		'appId'  => $APP_ID,
		'secret' => $APP_SECRET
	));
	$user = $facebook->getUser();
	if(!$user){
		echo '<script type="text/javascript">alert("Something Error!");window.close();</script>';
		exit;
	}
	$weiboInfo = $facebook->api('/me');
	$user_login=$weiboInfo['username']?$weiboInfo['username']:$weiboInfo['id'];
	$r=array(
		'profile_image_url'=>'https://graph.facebook.com/'.$weiboInfo['id'].'/picture',
		'user_login'=>$user_login,
		'display_name'=>$weiboInfo['name'],
		'user_url'=>$weiboInfo['link'],
		'smc_user_email'=>$weiboInfo['email'],
		'oauth_access_token'=>$facebook->getAccessToken(),
		'oauth_access_token_secret'=>'',
		'friends_count'=>'',
		'followers_count'=>'',
		'location'=>'',
		'description'=>$weiboInfo['bio'],
		'statuses_count'=>'',
		'emailendfix'=>'facebook.com',
		'usernameprefix'=>'facebook_',
		'weibo'=>'facebook'
	);
	return $r;
}

function smc_facebook_getAccessToken(){
	$r=smc_facebook_verify_credentials();
	return array('oauth_access_token'=>$r['oauth_access_token'], 'username'=>$r['user_login'], 'oauth_access_token_secret'=>'');
}
function smc_facebook_weibo_update($data,$thumb,$tok){
	global $APP_ID, $APP_SECRET;
	$content=get_weibo_str_length($data);
	$facebook = new FacebookOAuth(array(
		'appId'  => $APP_ID,
		'secret' => $APP_SECRET
	));
	$param['message']=$content;
	$resp = $facebook->api('/'.$tok['username'].'/feed','POST',$param);
	if($resp['id']){
		return $resp['id'];
	}else{
		return false;
	}
}
function smc_facebook_weibo_repost($p,$smcdata){
	global $APP_ID, $APP_SECRET;
	$content=get_comment_str_length($p,140,'twitter');
	$facebook = new FacebookOAuth(array(
		'appId'  => $APP_ID,
		'secret' => $APP_SECRET
	));
	$param['message']=$content;
	$resp = $facebook->api('/'.$smcdata['username'].'/feed','POST',$param);
	if($resp['id']){
		return $resp['id'];
	}else{
		return false;
	}
}