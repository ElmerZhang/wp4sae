<?php
global $APP_KEY, $APP_SECRET;
/* CopyRight © Social Medias Connect. 尊重开发者，如果没有特殊需求，请不要随意更改APPKEY。若要更改为你的appkey，请联系imqiqiboy@gmail.com。*/
$smc_appkey=smc_get_weibo_appkey('sinaweibo');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];
require_once(dirname(__FILE__).'/../OAuth.php');
/** 
 * 新浪微博 OAuth 认证类 
 * 
 * @package sae 
 * @author Easy Chen 
 * @version 1.0 
 */ 
class WeiboOAuth { 
    /** 
     * Contains the last HTTP status code returned.  
     * 
     * @ignore 
     */ 
    public $http_code; 
    /** 
     * Contains the last API call. 
     * 
     * @ignore 
     */ 
    public $url; 
    /** 
     * Set up the API root URL. 
     * 
     * @ignore 
     */ 
    public $host = "http://api.t.sina.com.cn/"; 
    /** 
     * Set timeout default. 
     * 
     * @ignore 
     */ 
    public $timeout = 30; 
    /**  
     * Set connect timeout. 
     * 
     * @ignore 
     */ 
    public $connecttimeout = 30;  
    /** 
     * Verify SSL Cert. 
     * 
     * @ignore 
     */ 
    public $ssl_verifypeer = FALSE; 
    /** 
     * Respons format. 
     * 
     * @ignore 
     */ 
    public $format = 'json'; 
    /** 
     * Decode returned json data. 
     * 
     * @ignore 
     */ 
    public $decode_json = TRUE; 
    /** 
     * Contains the last HTTP headers returned. 
     * 
     * @ignore 
     */ 
    public $http_info; 
    /** 
     * Set the useragnet. 
     * 
     * @ignore 
     */ 
    public $useragent = 'Sae T OAuth v0.2.0-beta2'; 
    /* Immediately retry the API call if the response was not successful. */ 
    //public $retry = TRUE; 
    



    /** 
     * Set API URLS 
     */ 
    /** 
     * @ignore 
     */ 
    function accessTokenURL()  { return 'http://api.t.sina.com.cn/oauth/access_token'; } 
    /** 
     * @ignore 
     */ 
    function authenticateURL() { return 'http://api.t.sina.com.cn/oauth/authenticate'; } 
    /** 
     * @ignore 
     */ 
    function authorizeURL()    { return 'http://api.t.sina.com.cn/oauth/authorize'; } 
    /** 
     * @ignore 
     */ 
    function requestTokenURL() { return 'http://api.t.sina.com.cn/oauth/request_token'; } 


    /** 
     * Debug helpers 
     */ 
    /** 
     * @ignore 
     */ 
    function lastStatusCode() { return $this->http_status; } 
    /** 
     * @ignore 
     */ 
    function lastAPICall() { return $this->last_api_call; } 

    /** 
     * construct WeiboOAuth object 
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
     * Get a request_token from Weibo 
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
    function getAuthorizeURL($token, $sign_in_with_Weibo = TRUE , $url) { 
        if (is_array($token)) { 
            $token = $token['oauth_token']; 
        } 
        if (empty($sign_in_with_Weibo)) { 
            return $this->authorizeURL() . "?oauth_token={$token}&oauth_callback=" . urlencode($url); 
        } else { 
            return $this->authenticateURL() . "?oauth_token={$token}&oauth_callback=". urlencode($url); 
        } 
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
		if(!is_array($response))wp_die('Error Info: '.$response->errors['http_request_failed'][0].'<br/>你的主机不被支持，请联系你的主机商重新配置主机。<br/><br/>Powered by © <a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">社交媒体连接</a>');
		$this->http_code=$response['response']['code'];
		$this->http_info=$response['response']['message'];
		$this->http_header=$response['headers'];
		return preg_replace("/(\"id\":)(\d+)/i","\${1}\"\${2}\"",$response['body']);
	}
}

/*2011.04.12*/
function smc_sinaweibo_verify_credentials(){
	global $APP_KEY, $APP_SECRET;
	$to = new WeiboOAuth($APP_KEY, $APP_SECRET, $_GET['oauth_token'],$_SESSION['smc_oauth_token_secret']);
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);
	$to = new WeiboOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$weiboInfo = $to->get('http://api.t.sina.com.cn/account/verify_credentials.json',array());

	if($weiboInfo['error_code']){
		echo '<script type="text/javascript">alert("something error!");window.close();</script>';
		return;
	}

	if((string)$weiboInfo['domain']){
		$smc_user_name = $weiboInfo['domain'];
	} else {
		$smc_user_name = $weiboInfo['id'];
	}
	$r=array(
		'profile_image_url'=>$weiboInfo['profile_image_url'],
		'user_login'=>$smc_user_name,
		'weibo_uid'=>$weiboInfo['id'],
		'display_name'=>$weiboInfo['screen_name'],
		'user_url'=>'http://weibo.com/'.$smc_user_name,
		'oauth_access_token'=>$tok['oauth_token'],
		'oauth_access_token_secret'=>$tok['oauth_token_secret'],
		'friends_count'=>$weiboInfo['friends_count'],
		'followers_count'=>$weiboInfo['followers_count'],
		'location'=>$weiboInfo['location'],
		'description'=>$weiboInfo['description'],
		'statuses_count'=>$weiboInfo['statuses_count'],
		'emailendfix'=>'weibo.com',
		'usernameprefix'=>'sina_t_',
		'weibo'=>'sinaweibo'
	);
	return $r;
}

function smc_sinaweibo_getAccessToken($oauth_verifier,$oauth_token,$token_secret){
	global $APP_KEY, $APP_SECRET;
	$to=new WeiboOAuth($APP_KEY, $APP_SECRET, $oauth_token, $token_secret);
	return $to->getAccessToken($oauth_verifier);
}
function smc_sinaweibo_weibo_update($data,$thumb,$tok){
	global $APP_KEY, $APP_SECRET;
	$content=get_weibo_str_length($data);
	$to=new WeiboOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$param=array();
	$param['status']=$content;
	if($thumb){
		$param['pic']='@'.$thumb;//print_r($param);
		$resp = $to->post('http://api.t.sina.com.cn/statuses/upload.json',$param,true);
		if($resp['error_code']){
			return smc_sinaweibo_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('http://api.t.sina.com.cn/statuses/update.json',$param);
	}
	if($resp['id']){
		return $resp['id'];
	}else{
		return false;
	}
}
function smc_sinaweibo_weibo_repost($p,$smcdata){//9105332181
	global $APP_KEY, $APP_SECRET, $wp_smiliessearch;
	$p['comment']=preg_replace_callback($wp_smiliessearch, 'smc_sinaweibo_convert_smilies', $p['comment']);
	$content=get_comment_str_length($p);
	if(!$p['weibosync']){
		return smc_sinaweibo_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$to=new WeiboOAuth($APP_KEY, $APP_SECRET, $smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
	$param=array();
	$param['status']=$content;
	$param['id']=$p['weibosync'];//print_r($param);die();
	$resp = $to->post('http://api.t.sina.com.cn/statuses/repost.json',$param);
	sleep(2);
	$param1=array();
	$param1['comment'] = get_weibo_str_length($p['comment'].' - '.$p['url']);
	$param1['id']=$p['weibosync'];//$p['weibosync'];
	$resp = $to->post( 'http://api.t.sina.com.cn/statuses/comment.json' , $param1 ); 
}
function smc_sinaweibo_convert_smilies($smiley){
	if (count($smiley) == 0) {
		return '';
	}
	$smiley_array=array(
		':wink:' => '[鄙视]',
		':twisted:' => '[怒骂]',
		':smile:' => '[呵呵]',
		':shock:' => '[抓狂]',
		':sad:' => '[悲伤]',
		':roll:' => '[晕]',
		':razz:' => '[馋嘴]',
		':oops:' => '[害羞]',
		':o' => '[懒得理你]',
		':neutral:' => '[闭嘴]',
		':mrgreen:' => '[嘻嘻]',
		':mad:' => '[失望]',
		':lol:' => '[鼓掌]',
		':idea:' => '[good]',
		':grin:' => '[哈哈]',
		':evil:' => '[阴险]',
		':eek:' => '[吃惊]',
		':cry:' => '[泪]',
		':cool:' => '[酷]',
		':?:'=>'[疑问]',
		':???:'=>'[思考]',
		':!:'=>'[挖鼻屎]'
	);
	$smiley = trim(reset($smiley));
	if($smiley_array[$smiley]){
		return $smiley_array[$smiley];
	}else{
		return $smiley;
	}
}
function smc_sinaweibo_weibo_timeline($r,$tok){
	global $APP_KEY, $APP_SECRET;
	$to=new WeiboOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	switch($r['type']){
		case 'user_timeline':$api='http://api.t.sina.com.cn/statuses/user_timeline.json';
							$param=array(
								'count'=>$r['number'],
								'feature'=>'0'
							);
							break;
		case 'friends_timeline':$api='http://api.t.sina.com.cn/statuses/friends_timeline.json';
							$param=array(
								'count'=>$r['number'],
								'feature'=>'0'
							);
							break;
		case 'public_timeline':$api='http://api.t.sina.com.cn/statuses/public_timeline.json';
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
			$text=$w['text']; $retweet='';
			if(smc_strlen($text)>$length){
				$text = smc_substr($text, 0, $length).'...';
			}
			$thumb = $w['thumbnail_pic'] ? '<a href="'.$w['bmiddle_pic'].'" original_pic="'.$w['original_pic'].'" rel="nofollow"><img src="'.$w['thumbnail_pic'].'" class="smc_weibo_image" alt="'.$w['user']['screen_name'].'" /></a>' : '';
			if($w['retweeted_status']){
				$z=$w['retweeted_status'];
				$_thumb = $z['thumbnail_pic'] ? '<a href="'.$z['bmiddle_pic'].'" original_pic="'.$z['original_pic'].'" rel="nofollow"><img src="'.$z['thumbnail_pic'].'" class="smc_weibo_image" alt="'.$z['user']['screen_name'].'" /></a>' : '';
				$_text=smc_strlen($z['text'])>$length?smc_substr($z['text'], 0, $length).'...':$z['text'];
				$retweet=array(
					'id'=>$z['id'],
					'text'=>smc_to_html($_text,'sinaweibo'),
					'author'=>$z['user']['screen_name'],
					'avatar'=>$z['user']['profile_image_url'],
					'time'=>smc_time_since(strtotime($z['created_at'])),
					'source'=>'来自'.$z['source'],
					'thumb'=>$_thumb,
					'url'=>'http://api.t.sina.com.cn/'.$z['user']['id'].'/statuses/'.$z['id']
				);
			}
			$data[]=array(
				'id'=>$w['id'],
				'text'=>smc_to_html($text,'sinaweibo'),
				'author'=>$w['user']['screen_name'],
				'avatar'=>$w['user']['profile_image_url'],
				'time'=>smc_time_since(strtotime($w['created_at'])),
				'source'=>'来自'.$w['source'],
				'thumb'=>$thumb,
				'url'=>'http://api.t.sina.com.cn/'.$w['user']['id'].'/statuses/'.$w['id'],
				'retweeted_status'=>$retweet
			);
		}
		return $data;
	}else{
		return false;
	}
}
function _smc_sinaweibo_make_at_user($matches){
	if(strpos($matches[1],'#')!==false){
		return $matches[0];
	}else return '<a href="http://weibo.com/n/'.$matches[1].'" rel="nofollow">'.$matches[0].'</a>';
}
function _smc_sinaweibo_make_topic($matches){
	return '<a href="http://weibo.com/k/'.urlencode($matches[1]).'" rel="nofollow">'.$matches[0].'</a>';
}
function smc_sinaweibo_add_follow_user($r){
	global $APP_KEY, $APP_SECRET;
	$to=new WeiboOAuth($APP_KEY, $APP_SECRET, $r['oauth_access_token'], $r['oauth_access_token_secret']);
	$param=array(
		'user_id'=>$r['add_follow']
	);
	$resp = $to->post('http://api.t.sina.com.cn/friendships/create.json',$param);
	if($resp['error'])return false;
	else return true;
}
?>