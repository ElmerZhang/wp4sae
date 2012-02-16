<?php global $APP_KEY, $APP_SECRET;
/* CopyRight © Social Medias Connect. 尊重开发者，如果没有特殊需求，请不要随意更改APPKEY。若要更改为你的appkey，请联系imqiqiboy@gmail.com。*/
$smc_appkey=smc_get_weibo_appkey('douban');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];
require_once(dirname(__FILE__).'/../OAuth.php');

/** 
 * 豆瓣 OAuth 认证类 
 * 
 * @package sae 
 * @author Easy Chen 
 * @version 1.0 
 */ 
class doubanOAuth {/*{{{*/
	/* Contains the last HTTP status code returned */
	private $http_status;

	/* Contains the last API call */
	private $last_api_call;

	/* Set up the API root URL */
	public static $TO_API_ROOT = "http://www.douban.com/service";

	/**
	 * Set API URLS
	 */
	function requestTokenURL() { return self::$TO_API_ROOT.'/auth/request_token'; }
	function authorizeURL() { return self::$TO_API_ROOT.'/auth/authorize'; }
	function authenticateURL() { return self::$TO_API_ROOT.'/auth/authenticate'; }
	function accessTokenURL() { return self::$TO_API_ROOT.'/auth/access_token'; }

	/**
	 * Debug helpers
	 */
	function lastStatusCode() { return $this->http_status; }
	function lastAPICall() { return $this->last_api_call; }

	/**
	 * construct DoubanOAuth object
	 */
	function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {/*{{{*/
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		if (!empty($oauth_token) || !empty($oauth_token_secret)) {
			$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
		} else {
			$this->token = NULL;
		}
	}/*}}}*/


	/**
	 * Get a request_token from Douban
	 *
	 * @returns a key/value array containing oauth_token and oauth_token_secret
	 */
	function getRequestToken() {/*{{{*/
		$r = $this->oAuthRequest($this->requestTokenURL());

		$token = $this->oAuthParseResponse($r);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}/*}}}*/

	/**
	 * Parse a URL-encoded OAuth response
	 *
	 * @return a key/value array
	 */
	function oAuthParseResponse($responseString) {
		$r = array();
		foreach (explode('&', $responseString) as $param) {
			$pair = explode('=', $param, 2);
			if (count($pair) != 2) continue;
			$r[urldecode($pair[0])] = urldecode($pair[1]);
		}
		return $r;
	}

	/**
	 * Get the authorize URL
	 *
	 * @returns a string
	 */
	function getAuthorizeURL($token, $sign_in_with_Weibo = TRUE , $url) {/*{{{*/
		if (is_array($token)) $token = $token['oauth_token'];
		return $this->authorizeURL() . "?oauth_token={$token}&oauth_callback=" . urlencode($url); 
	}/*}}}*/
	/**
	 * Get the authenticate URL
	 *
	 * @returns a string
	 */
	function getAuthenticateURL($token) {/*{{{*/
		if (is_array($token)) $token = $token['oauth_token'];
		return $this->authenticateURL() . '?oauth_token=' . $token;
	}/*}}}*/

	/**
	 * Exchange the request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @returns array("oauth_token" => the access token,
	 *                "oauth_token_secret" => the access secret)
	 */
	function getAccessToken($token = NULL) {/*{{{*/
		$r = $this->oAuthRequest($this->accessTokenURL());
		$token = $this->oAuthParseResponse($r);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}/*}}}*/

	/**
	 * Format and sign an OAuth / API request
	 */
	function oAuthRequest($url, $args = array(), $method = NULL, $post_data = NULL) {/*{{{*/
			
		if (empty($method)) $method = empty($args) ? "GET" : "POST";
		$req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $args);
		$req->sign_request($this->sha1_method, $this->consumer, $this->token);
		switch ($method) {
			case 'GET': return $this->http($req->to_url(),'GET');
			case 'POST': return $this->http($req->get_normalized_http_url(),'POST',
				$post_data, $req->to_header());
		}
	}/*}}}*/

	function http($url, $method='GET', $postfields = NULL,$headermulti="" ){
		$this->http_info = array();
		$http=new WP_Http();
		$header=array();
		if($method=='POST'){
			$headermulti=WP_Http::processHeaders($headermulti);
			$header=array("Content-Type"=>"application/atom+xml");
			$header=array_merge(array("Content-Type"=>"application/atom+xml"),$headermulti['headers']);
		}
		$response=$http->request($url,array(
			"method"=>$method,
			"body"=>$postfields,
			"headers"=>$header
		));
		if(!is_array($response))wp_die('你的主机不被支持，请联系你的主机商重新配置主机。');
		$this->http_code=$response['response']['code'];
		$this->http_info=$response['response']['message'];
		$this->http_header=$response['headers'];
		return $response['body'];
	}
}/*}}}*/


/*2011.04.12*/
function smc_douban_verify_credentials(){
	global $APP_KEY, $APP_SECRET;
	$to = new doubanOAuth($APP_KEY, $APP_SECRET, $_GET['oauth_token'],$_SESSION['smc_oauth_token_secret']);
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);
	$to = new doubanOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$weiboInfo = $to->OAuthRequest('http://api.douban.com/people/%40me', array(),'GET');
	if($weiboInfo === false || $weiboInfo === null){
		echo '<script type="text/javascript">window.close();</script>';
		return;
	}
	$weiboInfo = simplexml_load_string($weiboInfo);
	$weibouid=str_replace('http://api.douban.com/people/','',$weiboInfo->id);
	$content=(string)$weiboInfo->content;
	$url='http://www.douban.com/people/'.$weibouid;
	$display_name=(string)$weiboInfo->title;
	$r=array(
		'profile_image_url'=>'http://img3.douban.com/icon/u'.$weibouid.'.jpg',
		'user_login'=>$weibouid,
		'display_name'=>$display_name,
		'user_url'=>$url,
		'oauth_access_token'=>$tok['oauth_token'],
		'oauth_access_token_secret'=>$tok['oauth_token_secret'],
		'friends_count'=>'',
		'followers_count'=>'',
		'location'=>'',
		'description'=>$content,
		'statuses_count'=>'',
		'emailendfix'=>'douban.com',
		'usernameprefix'=>'douban_',
		'weibo'=>'douban'
	);
	return $r;
}
function smc_douban_getAccessToken($oauth_verifier,$oauth_token,$token_secret){
	global $APP_KEY, $APP_SECRET;
	$to=new doubanOAuth($APP_KEY, $APP_SECRET, $oauth_token, $token_secret);
	return $to->getAccessToken($oauth_verifier);
}
function smc_douban_weibo_update($data,$thumb,$tok){
	global $APP_KEY, $APP_SECRET;
	$data['tags']=array();
	$content=get_weibo_str_length($data,140,true);
	$to=new doubanOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$content = '<?xml version="1.0" encoding="UTF-8"?>'.
			'<entry xmlns:ns0="http://www.w3.org/2005/Atom" xmlns:db="http://www.douban.com/xmlns/">'.
			'<content>'.$content.'</content>'.
			'</entry>';
    $resp=$to->OAuthRequest('http://api.douban.com/miniblog/saying', array(), 'POST', $content);
	$resp=@simplexml_load_string($resp);
	if($resp->id){
		$resp=(array)$resp->id;
		return $resp[0];
	}else{
		return false;
	}
}
function smc_douban_weibo_repost($p,$smcdata){//http://api.douban.com/miniblog/641923516
	global $APP_KEY, $APP_SECRET;
	$content=get_comment_str_length($p,140,true);
	if(!$p['weibosync']){
		return smc_douban_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$to=new doubanOAuth($APP_KEY, $APP_SECRET, $smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
	$content1=get_weibo_str_length($p['comment'].' - '.$p['url']);
	$xmlcontent = '<?xml version="1.0" encoding="UTF-8"?>'.
			'<entry>'.
			'<content>'.$content1.'</content>'.
			'</entry>';
    $resp=$to->OAuthRequest($p['weibosync'].'/comments', array(), 'POST', $xmlcontent);
	sleep(2);
	smc_douban_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
}
function smc_douban_weibo_timeline($r,$tok){
	global $APP_KEY, $APP_SECRET;
	$to=new doubanOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	switch($r['type']){
		case 'user_timeline':$api='http://api.douban.com/people/'.$tok['douban_user_id'].'/miniblog';
							$param=array(
								'max-results'=>$r['number']
							);
							break;
		case 'friends_timeline':
		case 'public_timeline':$api='http://api.douban.com/people/'.$tok['douban_user_id'].'/miniblog/contacts';
							$param=array(
								'max-results'=>$r['number']
							);
							break;
		default:break;
	}
	$resp=$to->OAuthRequest($api, $param, 'GET');
	$resp=@simplexml_load_string($resp);print_r($resp);
	die();
	$resp=@simplexml_load_xml($resp);
	if(is_array($resp)&&$resp[0]['id']){
		$data=array();
		foreach($resp as $w){
			$data[]=array(
				'id'=>$w['id'],
				'text'=>$w['text'],
				'author'=>$w['user']['name'],
				'avatar'=>$w['user']['profile_image_url'],
				'time'=>$w['created_at']
			);
		}
		return $data;
	}else{
		return false;
	}
}
?>