<?php global $APP_KEY, $APP_SECRET;
/* CopyRight © Social Medias Connect. 尊重开发者，如果没有特殊需求，请不要随意更改APPKEY。若要更改为你的appkey，请联系imqiqiboy@gmail.com。*/
$smc_appkey=smc_get_weibo_appkey('sohuweibo');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];
/*
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 * The first PHP Library to support OAuth for Twitter's REST API.
 */

/* Load OAuth lib. You can find it at http://oauth.net */

/**
 * 搜狐(SOHU)的php示例代码是 基于Abraham Williams发布的开源twitteroauth库的。
 * https://github.com/abraham/twitteroauth
 */
require_once(dirname(__FILE__).'/../OAuth.php');

/**
 * 搜狐OAuth认证php类
 */
class SohuOAuth {
	/* Contains the last HTTP status code returned. */
	public $http_code;
	/* Contains the last API call. */
	public $url;
	/* Set up the API root URL. */
	public $host = "http://api.t.sohu.com/";
	/* Set timeout default. */
	public $timeout = 30;
	/* Set connect timeout. */
	public $connecttimeout = 30;
	/* Verify SSL Cert. */
	public $ssl_verifypeer = FALSE;
	/* Respons format. */
	public $format = 'json';
	/* Decode returned json data. */
	public $decode_json = TRUE;
	/* Contains the last HTTP headers returned. */
	public $http_info;
	/* Set the useragnet. */
	public $useragent = 'SohuOAuth v0.0.1';

	/**
	 * 设置OAuth认证需要的Urls
	 */
	function accessTokenURL()  { return 'http://api.t.sohu.com/oauth/access_token'; }
	function authenticateURL() { return 'http://api.t.sohu.com/oauth/authorize'; }
	function authorizeURL()    { return 'http://api.t.sohu.com/oauth/authorize'; }
	function requestTokenURL() { return 'http://api.t.sohu.com/oauth/request_token'; }

	function lastStatusCode() { return $this->http_status; }
	function lastAPICall() { return $this->last_api_call; }

	/**
	 *
	 * 创建SohuOAuth对象实例
	 * @param String $consumer_key
	 * @param String $consumer_secret
	 * @param String $oauth_token 这是access key，没有申请到的时候可以省略
	 * @param String $oauth_token_secret  这是access key对应的密钥，没有申请到的时候可以省略
	 */
	function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		if (!empty($oauth_token) && !empty($oauth_token_secret)) {
			$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
		} else {
			$this->token = NULL;
		}
	}


	/**
	 * 获取request_token
	 * @param $oauth_callback
	 * @return a key/value array containing oauth_token and oauth_token_secret
	 */
	function getRequestToken($oauth_callback = NULL) {
		$parameters = array();
		if (!empty($oauth_callback)) {
			$parameters['oauth_callback'] = $oauth_callback;
		}
		$request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * 获取用户认证地址（authorize url），此过程用户将对App的访问进行授权。
	 * @param string $token 这是之前获取的request_token
	 * @param $sign_in_with_sohu
	 */
	function getAuthorizeURL($token, $sign_in_with_sohu = TRUE) {
		if (is_array($token)) {
			$token = $token['oauth_token'];
		}
		if (empty($sign_in_with_sohu)) {
			return $this->authorizeURL() . "?oauth_token={$token}";
		} else {
			return $this->authenticateURL() . "?oauth_token={$token}";
		}
	}
	/**
     * get authorize url for oauth version 1.0
     * @param $token request token
     * @param $oauth_callback oauth callback url
     */
    function getAuthorizeUrl1($token, $oauth_callback) {
        if (is_array($token)) {
            $token = $token['oauth_token'];
        }
        return $this->authorizeURL() . "?oauth_token={$token}"."&oauth_callback={$oauth_callback}";
    }

	/**
	 *
	 * 用户认证完毕后获取access token
	 * @param string $oauth_verifier 用户授权后产生的认证码
	 * @returns array("oauth_token" => "the-access-token",
	 *                "oauth_token_secret" => "the-access-secret",
	 *                "user_id" => "9436992",
	 *                "screen_name" => "abraham")
	 */
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

	/**
	 * OAuthRequest GET请求的包装类
	 */
	function get($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'GET', $parameters);
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response);
		}
		return $response;
	}

	/**
	 * OAuthRequest POST请求的包装类
	 */
	function post($url, $parameters = array(),$multi = false) {
		$response = $this->oAuthRequest($url, 'POST', $parameters,$multi);
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response);
		}
		return $response;
	}

	/**
	 * OAuthRequest DELETE请求的包装类
	 */
	function delete($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'DELETE', $parameters);
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response);
		}
		return $response;
	}

	/**
	 * 签名方法并发送http请求
	 * @param string $url api 地址
	 * @param string $method http请求方法，包括 GET,POST,DELETE,TRACE,HEAD,OPTIONS,PUT
	 * @param $parameters 请求参数
	 */
	function oAuthRequest($url, $method, $parameters, $multi = false) {
		if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
			$url = "{$this->host}{$url}.{$this->format}";
		}
		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		switch ($method) {
			case 'GET':
				return $this->http($request->to_url(), 'GET');
			default:
				return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata($multi), $multi);
		}
	}

	/**
	 * 发起HTTP请求
	 *
	 * @return API返回结果
	 */
	function http($url, $method, $postfields = NULL , $multi = false){
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
		if(!is_array($response))wp_die('你的主机不被支持，请联系你的主机商重新配置主机。');
		$this->http_code=$response['response']['code'];
		$this->http_info=$response['response']['message'];
		$this->http_header=$response['headers'];
		return $response['body'];
	}
}


/* 2011.04.12 */
function smc_sohuweibo_verify_credentials(){
	global $APP_KEY, $APP_SECRET;
	$to = new SohuOAuth($APP_KEY, $APP_SECRET, $_GET['oauth_token'],$_SESSION['smc_oauth_token_secret']);
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);
	$to = new SohuOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$weiboInfo = $to->get('http://api.t.sohu.com/account/verify_credentials.json', array());
	if($weiboInfo->code){
		echo '<script type="text/javascript">alert("Something error!");window.close();</script>';
		return;
	}
	if($weiboInfo->screen_name){
		$smc_user_name=$weiboInfo->screen_name;
	}else{
		$smc_user_name=$weiboInfo->id;
	}
	$r=array(
		'profile_image_url'=>$weiboInfo->profile_image_url,
		'user_login'=>$smc_user_name,
		'weibo_uid'=>$weiboInfo->id,
		'display_name'=>$weiboInfo->screen_name,
		'user_url'=>'http://t.sohu.com/u/'.$weiboInfo->id,
		'oauth_access_token'=>$tok['oauth_token'],
		'oauth_access_token_secret'=>$tok['oauth_token_secret'],
		'friends_count'=>$weiboInfo->friends_count,
		'followers_count'=>$weiboInfo->followers_count,
		'location'=>$weiboInfo->location,
		'description'=>$weiboInfo->description,
		'statuses_count'=>$weiboInfo->statuses_count,
		'emailendfix'=>'t.sohu.com',
		'usernameprefix'=>'sohu_t_',
		'weibo'=>'sohuweibo'
	);
	return $r;
}

function smc_sohuweibo_getAccessToken($oauth_verifier,$oauth_token,$token_secret){
	global $APP_KEY, $APP_SECRET;
	$to=new SohuOAuth($APP_KEY, $APP_SECRET, $oauth_token, $token_secret);
	return $to->getAccessToken($oauth_verifier);
}
function smc_sohuweibo_weibo_update($data,$thumb,$tok){
	global $APP_KEY, $APP_SECRET;
	$content=urlencode(get_weibo_str_length($data,300));
	$to=new SohuOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	if($thumb){
		$resp = $to->post('http://api.t.sohu.com/statuses/upload.json',array('status'=>$content,'pic'=>'@'.$thumb),true);
		if($resp->error){
			return smc_sohuweibo_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('http://api.t.sohu.com/statuses/update.json',array('status'=>$content));
	}
	if($resp->id){
		return $resp->id;
	}else{
		return false;
	}
}
function smc_sohu_weibo_repost($p,$smcdata){//706433772
	global $APP_KEY, $APP_SECRET;
	$content=get_comment_str_length($p,999);
	if(!$p['weibosync']){
		return smc_sohuweibo_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$param=array();
	$param['status']=urlencode($content);//.' - '.$p['url'];
	$to=new SohuOAuth($APP_KEY, $APP_SECRET, $smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
	$resp = $to->post('http://api.t.sohu.com/statuses/transmit/'.$p['weibosync'].'.json',$param);//print_r($resp);
	$param1=array();
	$param1['comment'] = urlencode(get_weibo_str_length($p['comment'].' - '.$p['url'],300));
	$param1['id']=$p['weibosync'];//$p['weibosync'];
	$resp = $to->post( 'http://api.t.sohu.com/statuses/comment.json' , $param1 ); 
}
function smc_sohuweibo_weibo_timeline($r,$tok){
	global $APP_KEY, $APP_SECRET;
	$to=new SohuOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	switch($r['type']){
		case 'user_timeline':$api='http://api.t.sohu.com/statuses/user_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		case 'friends_timeline':$api='http://api.t.sohu.com/statuses/friends_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		case 'public_timeline':$api='http://api.t.sohu.com/statuses/public_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		default:break;
	}
	$resp = $to->get($api,$param);
	if(is_array($resp)){
		$data=array(); $length=(int)$r['length'];
		foreach($resp as $w){
			$text=$w->text; $retweet='';
			if(smc_strlen($text)>$length){
				$text = smc_substr($text, 0, $length).'...';
			}
			$text = smc_to_html($text,'sohuweibo');
			$thumb = $w->small_pic ? '<a href="'.$w->middle_pic.'" original_pic="'.$w->original_pic.'" rel="nofollow"><img src="'.$w->small_pic.'" class="smc_weibo_image" alt="'.$w->user->screen_name.'" /></a>' : '';
			if(!empty($w->in_reply_to_screen_name)){
				$retweet=array(
					'id'=>$w->in_reply_to_status_id,
					'text'=>smc_strlen($w->in_reply_to_status_text)>$length?smc_substr($w->in_reply_to_status_text, 0, $length).'...':$w->in_reply_to_status_text,
					'author'=>$w->in_reply_to_screen_name,
					'avatar'=>'',
					'time'=>'',
					'source'=>'',
					'thumb'=>'',
					'url'=>'http://t.sohu.com/m/'.$w->in_reply_to_status_id
				);
			}
			$data[]=array(
				'id'=>$w->id,
				'text'=>$text,
				'author'=>$w->user->screen_name,
				'avatar'=>$w->user->profile_image_url,
				'time'=>smc_time_since(strtotime($w->created_at)),
				'source'=>'来自'.$w->source,
				'thumb'=>$thumb,
				'url'=>'http://t.sohu.com/m/'.$w->id,
				'retweeted_status'=>$retweet
			);
		}
		return $data;
	}else{
		return false;
	}
}
function _smc_sohuweibo_make_at_user($matches){
	if(strpos($matches[1],'#')!==false){
		return $matches[0];
	}else return '<a href="http://t.sohu.com/n/'.$matches[1].'" rel="nofollow">'.$matches[0].'</a>';
}
function _smc_sohuweibo_make_topic($matches){
	return '<a href="http://t.sohu.com/ht/'.urlencode($matches[1]).'" rel="nofollow">'.$matches[0].'</a>';
}
function smc_sohuweibo_add_follow_user($r){
	global $APP_KEY, $APP_SECRET;
	$to=new SohuOAuth($APP_KEY, $APP_SECRET, $r['oauth_access_token'], $r['oauth_access_token_secret']);
	$resp = $to->post('http://api.t.sohu.com/friendships/create/'.$r['add_follow'].'.json');
	if($resp->id)return true;
	else return false;
}