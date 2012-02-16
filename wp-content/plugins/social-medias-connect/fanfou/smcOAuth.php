<?php
global $APP_KEY, $APP_SECRET;
/* CopyRight © Social Medias Connect. 尊重开发者，如果没有特殊需求，请不要随意更改APPKEY。若要更改为你的appkey，请联系imqiqiboy@gmail.com。*/
$smc_appkey=smc_get_weibo_appkey('fanfou');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];
require_once(dirname(__FILE__).'/../OAuth.php');

class FanfouOAuth { 
    public $http_code; 
    public $url; 
    public $host = "http://api.fanfou.com/"; 
    public $timeout = 30; 
    public $connecttimeout = 30;  
    public $ssl_verifypeer = FALSE; 
    public $format = 'json'; 
    public $decode_json = TRUE; 
    public $http_info; 
    public $useragent = 'Social Medias Connect'; 
    function accessTokenURL()  { return 'http://fanfou.com/oauth/access_token'; } 
    function authorizeURL()    { return 'http://fanfou.com/oauth/authorize'; } 
    function requestTokenURL() { return 'http://fanfou.com/oauth/request_token'; } 

    /** 
     * FanfouOAuth类的构造方法
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
	 * 获取requestToken
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

    function getAuthorizeURL($token, $url) { 
        if (is_array($token)) { 
            $token = $token['oauth_token']; 
        } 
        return $this->authorizeURL() . "?oauth_token={$token}&oauth_callback=" . urlencode($url); 
    } 

    function getAccessToken($oauth_verifier = FALSE, $oauth_token = false) { 
        $parameters = array(); 
        if (!empty($oauth_verifier)) { 
            $parameters['oauth_verifier'] = $oauth_verifier; 
        } 


        $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters); 
        $token = OAuthUtil::parse_parameters($request); 
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']); 
        return $token; 
    } 

    function get($url, $parameters = array()) { 
        $response = $this->oAuthRequest($url, 'GET', $parameters); 
        if ($this->format === 'json' && $this->decode_json) { 
            return json_decode($response, true); 
        } 
        return $response; 
    } 

    function post($url, $parameters = array() , $multi = false) { 
        
        $response = $this->oAuthRequest($url, 'POST', $parameters , $multi ); 
        if ($this->format === 'json' && $this->decode_json) { 
            return json_decode($response, true); 
        } 
        return $response; 
    } 

    function delete($url, $parameters = array()) { 
        $response = $this->oAuthRequest($url, 'DELETE', $parameters); 
        if ($this->format === 'json' && $this->decode_json) { 
            return json_decode($response, true); 
        } 
        return $response; 
    } 

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
		return $response['body'];
	}
}

/*2011.05.12*/
function smc_fanfou_verify_credentials(){
	global $APP_KEY, $APP_SECRET;
	$to = new FanfouOAuth($APP_KEY, $APP_SECRET, $_GET['oauth_token'],$_SESSION['smc_oauth_token_secret']);
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);
	$to = new FanfouOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$weiboInfo = $to->get('http://api.fanfou.com/account/verify_credentials.json',array());

	if($weiboInfo['error']){
		echo '<script type="text/javascript">alert("something error!");window.close();</script>';
		return;
	}
	$r=array(
		'profile_image_url'=>$weiboInfo['profile_image_url'],
		'user_login'=>$weiboInfo['id'],
		'display_name'=>$weiboInfo['name'],
		'user_url'=>'http://fanfou.com/'.$weiboInfo['id'],
		'oauth_access_token'=>$tok['oauth_token'],
		'oauth_access_token_secret'=>$tok['oauth_token_secret'],
		'friends_count'=>$weiboInfo['friends_count'],
		'followers_count'=>$weiboInfo['followers_count'],
		'location'=>$weiboInfo['location'],
		'description'=>$weiboInfo['description'],
		'statuses_count'=>$weiboInfo['statuses_count'],
		'emailendfix'=>'fanfou.com',
		'usernameprefix'=>'fanfou_',
		'weibo'=>'fanfou'
	);
	return $r;
}

function smc_fanfou_getAccessToken($oauth_verifier,$oauth_token,$token_secret){
	global $APP_KEY, $APP_SECRET;
	$to=new FanfouOAuth($APP_KEY, $APP_SECRET, $oauth_token, $token_secret);
	return $to->getAccessToken($oauth_verifier);
}
function smc_fanfou_weibo_update($data,$thumb,$tok){
	global $APP_KEY, $APP_SECRET;
	$content=get_weibo_str_length($data);
	$to=new FanfouOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$param=array();
	$param['status']=$content;
	if($thumb){
		$param['photo']=$thumb;
		$resp = $to->post('http://api.fanfou.com/photos/upload.json',$param,true);//wp_die(print_r($resp));
		if($resp['error']){
			return smc_fanfou_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('http://api.fanfou.com/statuses/update.json',$param);//wp_die(print_r($resp));
	}
	if($resp['id']){
		return $resp['id'];
	}else{
		return false;
	}
}
function smc_fanfou_weibo_repost($p,$smcdata){//9105332181
	global $APP_KEY, $APP_SECRET;
	$content=get_comment_str_length($p,140,true);
	if(!$p['weibosync']){
		return smc_fanfou_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$to=new FanfouOAuth($APP_KEY, $APP_SECRET, $smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
	$resp = $to->get('http://api.fanfou.com/statuses/show/'.$p['weibosync'].'.json',array());print_r($resp);
	if($resp['error']){
		return smc_twitter_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$author=$resp['user']['id'];
	$retext=' 转: @'.$author.' '.$resp['text'];
	$param=array("status"=>get_weibo_str_length($p['comment'].' - '.$p['url'].$retext,140,true));
	$resp=$to->post('http://api.fanfou.com/statuses/update.json',$param);
}
function smc_fanfou_weibo_timeline($r,$tok){
	global $APP_KEY, $APP_SECRET;
	$to=new FanfouOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	switch($r['type']){
		case 'user_timeline':$api='http://api.fanfou.com/statuses/user_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		case 'friends_timeline':$api='http://api.fanfou.com/statuses/friends_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		case 'public_timeline':$api='http://api.fanfou.com/statuses/public_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		default:break;
	}
	$resp = $to->get($api,$param);
	if(is_array($resp)&&$resp[0]['id']){
		$data=array(); $length=(int)$r['length'];
		foreach($resp as $w){
			$text=strip_tags($w['text']);
			if(smc_strlen($text)>$length){
				$text = smc_substr($text, 0, $length).'...';
			}
			$text = smc_to_html($text,'fanfou');
			$data[]=array(
				'id'=>$w['id'],
				'text'=>$text,
				'author'=>$w['user']['name'],
				'avatar'=>$w['user']['profile_image_url'],
				'time'=>smc_time_since(strtotime($w['created_at'])),
				'source'=>'来自'.$w['source'],
				'thumb'=>''
			);
		}
		return $data;
	}else{
		return false;
	}
}
function _smc_fanfou_make_at_user($matches){
	if(strpos($matches[1],'#')!==false){
		return $matches[0];
	}else return '<a href="http://fanfou.com/'.$matches[1].'" rel="nofollow">'.$matches[0].'</a>';
}
function _smc_fanfou_make_topic($matches){
	return '<a href="http://fanfou.com/q/'.urlencode($matches[1]).'" rel="nofollow">'.$matches[0].'</a>';
}
?>