<?php
include "../../../wp-config.php";
function smc_showErr($ErrMsg) {
    header('HTTP/1.0 500 Internal Server Error');
	header('Content-Type: text/plain;charset=UTF-8');
    echo $ErrMsg;
    exit;
}
$weibo=$_GET['socialmedia'];
$callback_url = $_GET['callback_url']? $_GET['callback_url']:get_option('home');
if(empty($weibo)){
	smc_showErr('Unknow request!');
}else{
	$weibo=trim($weibo);
	$SMC=wp_cache_get('global_option','smc');
	if(empty($SMC)){
		wp_die(smc_no_global_option_tips());
	}
	switch($weibo){
		case 'sinaweibo':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$to = new WeiboOAuth($APP_KEY, $APP_SECRET);
				$tok = $to->getRequestToken($callback_url);
				$_SESSION["smc_oauth_token_secret"] = $tok['oauth_token_secret'];
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $to->getAuthorizeURL($tok['oauth_token'],true,$callback_url);
				wp_redirect($request_link);
				break;
		case 'qqweibo':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$to = new MBOpenTOAuth($APP_KEY, $APP_SECRET);
				$tok = $to->getRequestToken($callback_url);//print_r($tok);die();
				$_SESSION["smc_oauth_token_secret"] = $tok['oauth_token_secret'];
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $to->getAuthorizeURL($tok['oauth_token'],true,$callback_url);//print_r($request_link);die();
				wp_redirect($request_link);
				break;
		case 'douban':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$to = new doubanOAuth($APP_KEY, $APP_SECRET);
				$tok = $to->getRequestToken($callback_url);
				$_SESSION["smc_oauth_token_secret"] = $tok['oauth_token_secret'];
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $to->getAuthorizeURL($tok['oauth_token'],true,$callback_url);
				wp_redirect($request_link);
				break;
		case 'sohuweibo':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$to = new SohuOAuth($APP_KEY, $APP_SECRET);
				$tok = $to->getRequestToken($callback_url);//print_r($tok);die();
				$_SESSION["smc_oauth_token_secret"] = $tok['oauth_token_secret'];
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $to->getAuthorizeUrl1($tok['oauth_token'],$callback_url);
				header('Location:'.$request_link);
				break;
		case '163weibo':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$to = new Weibo163OAuth($APP_KEY, $APP_SECRET);
				$tok = $to->getRequestToken($callback_url);//print_r($tok);die();
				$_SESSION["smc_oauth_token_secret"] = $tok['oauth_token_secret'];
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $to->getAuthorizeURL($tok['oauth_token'],true,$callback_url);
				wp_redirect($request_link);
				break;
		case 'renren':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$config->CALLBACK = $_SESSION['smc_callback_url'] = $callback_url;
				$to = new RenRenOAuth();
				$request_link = $to->getAuthorizeUrl();
				$_SESSION["smc_weibo"] = $weibo;
				wp_redirect($request_link);
				break;
		case 'kaixin':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$config->CALLBACK = $_SESSION['smc_callback_url'] = $callback_url;
				$to = new KaiXinOAuth();
				$request_link = $to->getAuthorizeURL();
				$_SESSION["smc_weibo"] = $weibo;
				wp_redirect($request_link);
				break;
		case 'facebook':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$facebook = new Facebook(array(
					'appId'  => $APP_ID,
					'secret' => $APP_SECRET
				));
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $facebook->getLoginUrl(array('scope'=>'email,offline_access,publish_stream,user_birthday,user_location,user_work_history,user_about_me,user_hometown','redirect_uri'=>$callback_url));
				wp_redirect($request_link);
				break;
		case 'twitter':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$to = new TwitterOAuth($APP_KEY, $APP_SECRET);
				$tok = $to->getRequestToken($callback_url);//print_r($tok);die();
				$_SESSION["smc_oauth_token_secret"] = $tok['oauth_token_secret'];
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $to->getAuthorizeURL($tok['oauth_token'],true);
				wp_redirect($request_link);
				break;
		case 'fanfou':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$to = new FanfouOAuth($APP_KEY, $APP_SECRET);
				$tok = $to->getRequestToken($callback_url);//print_r($tok);die();
				$_SESSION["smc_oauth_token_secret"] = $tok['oauth_token_secret'];
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $to->getAuthorizeURL($tok['oauth_token'],$callback_url);
				wp_redirect($request_link);
				break;
		case 'tianya':
				if(!class_exists($SMC[$weibo]['OAuthClass'])){
					include dirname(__FILE__).'/'.$weibo.'/smcOAuth.php';
				}
				$to = new TianyaOAuth($APP_KEY, $APP_SECRET);
				$tok = $to->getRequestToken($callback_url);//print_r($tok);die();
				$_SESSION["smc_oauth_token_secret"] = $tok['oauth_token_secret'];
				$_SESSION["smc_weibo"] = $weibo;
				$request_link = $to->getAuthorizeURL($tok['oauth_token'],true,$callback_url);
				wp_redirect($request_link);
				break;
		case 'follow5':
				$_SESSION["smc_weibo"] = 'follow5';
				$split=stripos($callback_url,'?')===false?'?':'&';
				$_flag=is_user_logged_in()?'follow5':'_follow5';
				$request_link = $callback_url.$split.'oauth_token='.$_flag;
				wp_redirect($request_link);
				break;
		case 'zuosa':
				$_SESSION["smc_weibo"] = 'zuosa';
				$split=stripos($callback_url,'?')===false?'?':'&';
				$_flag=is_user_logged_in()?'zuosa':'_zuosa';
				$request_link = $callback_url.$split.'oauth_token='.$_flag;
				wp_redirect($request_link);
				break;
		case 'wbto':
				$_SESSION["smc_weibo"] = 'wbto';
				$split=stripos($callback_url,'?')===false?'?':'&';
				$_flag=is_user_logged_in()?'wbto':'_wbto';
				$request_link = $callback_url.$split.'oauth_token='.$_flag;
				wp_redirect($request_link);
				break;
		default:wp_die('<h2>Unknow request!</h2>');break;
	}
	exit;
}
?>
