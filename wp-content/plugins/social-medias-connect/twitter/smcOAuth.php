<?php
global $APP_KEY, $APP_SECRET;
/* CopyRight © Social Medias Connect. 尊重开发者，如果没有特殊需求，请不要随意更改APPKEY。若要更改为你的appkey，请联系imqiqiboy@gmail.com。*/
$smc_appkey=smc_get_weibo_appkey('twitter');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];
require_once(dirname(__FILE__).'/../OAuth.php');
class TwitterOAuth {
  public $http_code;
  public $url;
  public $host = "https://api.twitter.com/1/";
  public $timeout = 30;
  public $connecttimeout = 30; 
  public $ssl_verifypeer = FALSE;
  public $format = 'json';
  public $decode_json = TRUE;
  public $http_info;
  public $useragent = 'Social Medias Connect';

  function accessTokenURL()  { return 'https://twitter.com/oauth/access_token'; }
  function authenticateURL() { return 'https://twitter.com/oauth/authenticate'; }
  function authorizeURL()    { return 'https://twitter.com/oauth/authorize'; }
  function requestTokenURL() { return 'https://twitter.com/oauth/request_token'; }

  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }

  function getRequestToken($oauth_callback = NULL) {
    $parameters = array();
    if (!empty($oauth_callback)) {
      $parameters['oauth_callback'] = $oauth_callback;
    } 
    $request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);//print_r($token);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  function getAuthorizeURL($token, $sign_in_with_twitter = TRUE) {
    if (is_array($token)) {
      $token = $token['oauth_token'];
    }
    if (empty($sign_in_with_twitter)) {
      return $this->authorizeURL() . "?oauth_token={$token}";
    } else {
       return $this->authenticateURL() . "?oauth_token={$token}";
    }
  }

  function getAccessToken($oauth_verifier = FALSE) {
    $parameters = array();
    if (!empty($oauth_verifier)) {
      $parameters['oauth_verifier'] = $oauth_verifier;
    }
    $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  function getXAuthToken($username, $password) {
    $parameters = array();
    $parameters['x_auth_username'] = $username;
    $parameters['x_auth_password'] = $password;
    $parameters['x_auth_mode'] = 'client_auth';
    $request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  function get($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'GET', $parameters);//print_r($response);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }

  function post($url, $parameters = array(),$multi = false) {
    $response = $this->oAuthRequest($url, 'POST', $parameters,$multi);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }

  function delete($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'DELETE', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }

  function oAuthRequest($url, $method, $parameters,$multi=false) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
      $url = "{$this->host}{$url}.{$this->format}";
    }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
    case 'GET':
      return $this->http($request->to_url(), 'GET');
    default:
      return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata($multi),$multi);
    }
  }

  function http($url, $method, $postfields = NULL, $multi = false){
		$this->http_info = array();
		$http=new WP_Http();
		$header=array();
		if($multi){
			$header=array("Content-Type"=>"multipart/form-data; boundary=" . OAuthUtil::$boundary , "Expect"=>"");
		}
		$response=$http->request($url,array(
			"method"=>$method,
			"timeout"=>$this->timeout,
			"sslverify"=>$this->ssl_verifypeer,
			"user-agent"=>$this->useragent,
			"body"=>$postfields,
			"headers"=>$header
		));
		if(!is_array($response))wp_die('Error Info: '.$response->errors['http_request_failed'][0].'<br/>你的主机不被支持，请联系你的主机商重新配置主机。<br/><br/>Powered by © <a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">Social Medias Connect</a>');
		$this->http_code=$response['response']['code'];
		$this->http_info=$response['response']['message'];
		$this->http_header=$response['headers'];
		return $response['body'];
	}
}

/* 2011.04.20 */
function smc_twitter_verify_credentials(){
	global $APP_KEY, $APP_SECRET;
	$to = new TwitterOAuth($APP_KEY, $APP_SECRET, $_GET['oauth_token'],$_SESSION['smc_oauth_token_secret']);
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);
	$to = new TwitterOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$weiboInfo = $to->get('http://api.twitter.com/1/account/verify_credentials.json', array());
	if($weiboInfo->error){
		echo '<script type="text/javascript">alert(""Something Error!);window.close();</script>';
		return;
	}
	if($weiboInfo->name){
		$smc_user_name=$weiboInfo->screen_name;
	}else{
		$smc_user_name=$weiboInfo->id;
	}
	$r=array(
		'profile_image_url'=>$weiboInfo->profile_image_url,
		'user_login'=>$smc_user_name,
		'display_name'=>$weiboInfo->name,
		'user_url'=>'http://twitter.com/'.$smc_user_name,
		'user_email'=>$weiboInfo->id.'@twitter.com',
		'oauth_access_token'=>$tok['oauth_token'],
		'oauth_access_token_secret'=>$tok['oauth_token_secret'],
		'friends_count'=>$weiboInfo->friends_count,
		'followers_count'=>$weiboInfo->followers_count,
		'location'=>$weiboInfo->location,
		'description'=>$weiboInfo->description,
		'statuses_count'=>$weiboInfo->statuses_count,
		'emailendfix'=>'twitter.com',
		'usernameprefix'=>'twitter_',
		'weibo'=>'twitter'
	);
	return $r;
}

function smc_twitter_getAccessToken($oauth_verifier,$oauth_token,$token_secret){
	global $APP_KEY, $APP_SECRET;
	$to=new TwitterOAuth($APP_KEY, $APP_SECRET, $oauth_token, $token_secret);
	return $to->getAccessToken($oauth_verifier);
}
function smc_twitter_weibo_update($data,$thumb,$tok){
	global $APP_KEY, $APP_SECRET;
	$content=get_weibo_str_length($data,140,'twitter');
	$to=new TwitterOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	if($thumb){
		$resp = $to->post('https://upload.twitter.com/1/statuses/update_with_media.json',array(
			'status' => get_weibo_str_length($data,120,'twitter'),
			'media[]' => '@'.$thumb
		),true);
		if($resp->error){
			return smc_twitter_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('http://api.twitter.com/1/statuses/update.json',array(
			'status' => $content
		));//print_r($content);die();
	}
	if($resp->id_str){
		return $resp->id_str;
	}else{
		return false;
	}
}
function smc_twitter_weibo_repost($p,$smcdata){//60729894912532480
	global $APP_KEY, $APP_SECRET;
	$content=get_comment_str_length($p,140,'twitter');
	if(!$p['weibosync']){
		return smc_twitter_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$to=new TwitterOAuth($APP_KEY, $APP_SECRET, $smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
	$resp = $to->get('http://api.twitter.com/1/statuses/show/'.$p['weibosync'].'.json',array());

	if(!$resp->id_str){
		return smc_twitter_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$reid=$resp->id_str;
	$author=$resp->user->screen_name;
	$retext=' RT @'.$author.' : '.$resp->text;
	$param=array("status"=>get_weibo_str_length($p['comment'].' - '.$p['url'].$retext,140,'twitter'));
	$resp=$to->post('http://api.twitter.com/1/statuses/update.json',$param);//	print_r($resp);die();
}
function smc_twitter_weibo_timeline($r,$tok){
	global $APP_KEY, $APP_SECRET;
	$to=new TwitterOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	switch($r['type']){
		case 'user_timeline':$api='http://api.twitter.com/1/statuses/user_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		case 'friends_timeline':$api='http://api.twitter.com/1/statuses/home_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		case 'public_timeline':$api='http://api.twitter.com/1/statuses/public_timeline.json';
							$param=array(
								'count'=>$r['number'],
								'format'=>'html'
							);
							break;
		default:break;
	}
	$resp = $to->get($api,$param);
	if(is_array($resp)&&$resp[0]->id){
		$data=array(); $length=(int)$r['length'];
		foreach($resp as $w){
			$text=$w->text;
			if(smc_strlen($text)>$length){
				$text = smc_substr($text, 0, $length).'...';
			}
			$text = smc_to_html($text,'twitter');
			$data[]=array(
				'id'=>$w->id,
				'text'=>$text,
				'author'=>$w->user->screen_name,
				'avatar'=>$w->user->profile_image_url,
				'time'=>smc_time_since(strtotime($w->created_at)),
				'source'=>'来自'.$w->source,
				'thumb'=>''
			);
		}
		return $data;
	}else{
		return false;
	}
}
function _smc_twitter_make_at_user($matches){
	if(strpos($matches[1],'#')!==false){
		return $matches[0];
	}else return '<a href="http://twitter.com/'.$matches[1].'" rel="nofollow">'.$matches[0].'</a>';
}
function _smc_twitter_make_topic($matches){
	return '<a href="http://twitter.com/k/'.urlencode($matches[1]).'" rel="nofollow">'.$matches[0].'</a>';
}