<?php
global $APP_KEY, $APP_SECRET;
/* CopyRight © Social Medias Connect. 尊重开发者，如果没有特殊需求，请不要随意更改APPKEY。若要更改为你的appkey，请联系imqiqiboy@gmail.com。*/
$smc_appkey=smc_get_weibo_appkey('tianya');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];
require_once(dirname(__FILE__).'/../OAuth.php');
class TianyaOAuth { 
    public $http_code; 
    public $url; 
    public $host = "http://open.tianya.cn/"; 
    public $timeout = 30; 
    public $connecttimeout = 30;  
    public $ssl_verifypeer = FALSE; 
    public $format = 'json'; 
    public $decode_json = TRUE; 
    public $http_info; 
    public $useragent = 'Social Medias Connect'; 
    function accessTokenURL()  { return 'http://open.tianya.cn/oauth/access_token.php'; } 
    function authorizeURL()    { return 'http://open.tianya.cn/oauth/authorize.php'; } 
    function requestTokenURL() { return 'http://open.tianya.cn/oauth/request_token.php'; } 
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


    /** 
     * Get a request_token 
     * 
     * @return array a key/value array containing oauth_token and oauth_token_secret 
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
     * Get the authorize URL 
     * 
     * @return string 
     */ 
    function getAuthorizeURL($token, $sign_in_with_Tianya = TRUE , $url) { 
        if (is_array($token)) { 
            $token = $token['oauth_token']; 
        } 
        return $this->authorizeURL() . "?oauth_token={$token}&consumer_key={$this->consumer->key}&oauth_callback=" . urlencode($url); 
    } 

    /** 
     * Exchange the request token and secret for an access token and 
     * secret, to sign API calls. 
     * 
     * @return array array("oauth_token" => the access token, 
     *                "oauth_token_secret" => the access secret) 
     */ 
    function getAccessToken($oauth_verifier = FALSE, $oauth_token = false) { 
        $parameters = array(); 
        $parameters['oauth_consumer_key'] = $this->consumer->key;
        if (!empty($oauth_verifier)) { 
            $parameters['oauth_verifier'] = $oauth_verifier; 
        } 


        $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters); 
        $token = OAuthUtil::parse_parameters($request); 
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']); 
        return $token; 
    } 

    /** 
     * GET wrappwer for oAuthRequest. 
     * 
     * @return mixed 
     */ 
    function get($url, $parameters = array()) { 
        $response = $this->oAuthRequest($url, 'GET', $parameters); 
        if ($this->format === 'json' && $this->decode_json) { 
            return json_decode($response, true); 
        } 
        return $response; 
    } 

    /** 
     * POST wreapper for oAuthRequest. 
     * 
     * @return mixed 
     */ 
    function post($url, $parameters = array() , $multi = false) { 
        
        $response = $this->oAuthRequest($url, 'POST', $parameters , $multi ); 
        if ($this->format === 'json' && $this->decode_json) { 
            return json_decode($response, true); 
        } 
        return $response; 
    } 

    /** 
     * DELTE wrapper for oAuthReqeust. 
     * 
     * @return mixed 
     */ 
    function delete($url, $parameters = array()) { 
        $response = $this->oAuthRequest($url, 'DELETE', $parameters); 
        if ($this->format === 'json' && $this->decode_json) { 
            return json_decode($response, true); 
        } 
        return $response; 
    } 

    /** 
     * Format and sign an OAuth / API request 
     * 
     * @return string 
     */ 
    function oAuthRequest($url, $method, $parameters , $multi = false) { 

        if (strrpos($url, 'http://') !== 0 && strrpos($url, 'http://') !== 0) { 
            $url = "{$this->host}{$url}.{$this->format}"; 
        } 

        // echo $url ; 
        $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters); 
        $request->sign_request($this->sha1_method, $this->consumer, $this->token); 
        switch ($method) { 
        case 'GET': 
            //echo $request->to_url(); 
            return $this->http($request->to_url(), 'GET'); 
        default: 
            return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata($multi) , $multi ); 
        } 
    } 

    /** 
     * Make an HTTP request 
     * 
     * @return string API results 
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
		return preg_replace("/(\"id\":)(\d+)/i","\${1}\"\${2}\"",$response['body']);
	}
} 


/*2011.04.12*/
function smc_tianya_verify_credentials(){
	global $APP_KEY, $APP_SECRET;
	$to = new TianyaOAuth($APP_KEY, $APP_SECRET, $_GET['oauth_token'],$_SESSION['smc_oauth_token_secret']);
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);
	$to = new TianyaOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$weiboInfo = $to->get('http://open.tianya.cn/api/user/info.php',array('appkey'=>$APP_KEY,'oauth_token_secret'=>$tok['oauth_token_secret']));

	if($weiboInfo['error_code']){
		echo '<script type="text/javascript">alert("something error!");window.close();</script>';
		return;
	}
	$weiboInfo=$weiboInfo['user'];
	if(validate_username($weiboInfo['user_name'])){
		$smc_user_name = $weiboInfo['user_name'];
	} else {
		$smc_user_name = $weiboInfo['user_id'];
	}
	$r=array(
		'profile_image_url'=>$weiboInfo['head'],
		'user_login'=>$smc_user_name,
		'display_name'=>$weiboInfo['user_name'],
		'user_url'=>'http://t.tianya.cn/'.$weiboInfo['user_id'],
		'oauth_access_token'=>$tok['oauth_token'],
		'oauth_access_token_secret'=>$tok['oauth_token_secret'],
		'friends_count'=>$weiboInfo['friends_count'],
		'followers_count'=>$weiboInfo['followers_count'],
		'location'=>$weiboInfo['location'],
		'description'=>$weiboInfo['describe'],
		'statuses_count'=>$weiboInfo['statuses_count'],
		'emailendfix'=>'tianya.cn',
		'usernameprefix'=>'tianya_t_',
		'weibo'=>'tianya'
	);
	return $r;
}

function smc_tianya_getAccessToken($oauth_verifier,$oauth_token,$token_secret){
	global $APP_KEY, $APP_SECRET;
	$to=new TianyaOAuth($APP_KEY, $APP_SECRET, $oauth_token, $token_secret);
	return $to->getAccessToken($oauth_verifier);
}
function smc_tianya_weibo_update($data,$thumb,$tok){
	global $APP_KEY, $APP_SECRET;
	$content=get_weibo_str_length($data);
	$to=new TianyaOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$param=array('appkey'=>$APP_KEY,'oauth_token_secret'=>$tok['oauth_token_secret']);
	$param['word']=$content;
	if($thumb){
		$param['media']=$thumb;
		$resp = $to->post('http://open.tianya.cn/api/weibo/addimg.php',$param,true);
		if($resp['error_code']||$resp['error']){
			return smc_tianya_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('http://open.tianya.cn/api/weibo/add.php',$param);
	}
	if($resp['error_code']||$resp['error']){
		return false;
	}else{
		return $resp['data']['id'];
	}
}
function smc_tianya_weibo_repost($p,$smcdata){//9105332181
	global $APP_KEY, $APP_SECRET, $wp_smiliessearch;
	$content=get_comment_str_length($p);
	return smc_tianya_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
}
?>