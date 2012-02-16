<?php global $APP_KEY, $APP_SECRET;
/* CopyRight © Social Medias Connect. 尊重开发者，如果没有特殊需求，请不要随意更改APPKEY。若要更改为你的appkey，请联系imqiqiboy@gmail.com。*/
$smc_appkey=smc_get_weibo_appkey('163weibo');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];
require_once(dirname(__FILE__).'/../OAuth.php');

date_default_timezone_set('Asia/Chongqing');

class Weibo163OAuth { 
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
    public $host = "http://api.t.163.com/"; 
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
    public $useragent = 'Social Medias Connect'; 
    /* Immediately retry the API call if the response was not successful. */ 
    //public $retry = TRUE; 
    



    /** 
     * Set API URLS 
     */ 
    /** 
     * @ignore 
     */ 
    function accessTokenURL()  { return 'http://api.t.163.com/oauth/access_token'; } 
    /** 
     * @ignore 
     */ 
    function authenticateURL() { return 'http://api.t.163.com/oauth/authenticate'; } 
    /** 
     * @ignore 
     */ 
    function authorizeURL()    { return 'http://api.t.163.com/oauth/authorize'; } 
    /** 
     * @ignore 
     */ 
    function requestTokenURL() { return 'http://api.t.163.com/oauth/request_token'; } 


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
            return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata($multi) , $multi,$request->to_header() ); 
        } 
    } 

    /** 
     * Make an HTTP request 
     * 
     * @return string API results 
     */ 
    function http($url, $method, $postfields = NULL , $multi = false, $headermulti=""){
		$this->http_info = array();
		$http=new WP_Http();
		$header=array();
		$headermulti=WP_Http::processHeaders($headermulti);
		if($multi){
			$header=array_merge(array("Content-Type"=>"multipart/form-data; boundary=" . OAuthUtil::$boundary , "Expect"=>""),$headermulti['headers']);
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
function smc_163weibo_verify_credentials(){
	global $APP_KEY, $APP_SECRET;
	$to = new Weibo163OAuth($APP_KEY, $APP_SECRET, $_GET['oauth_token'],$_SESSION['smc_oauth_token_secret']);
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);
	$to = new Weibo163OAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$weiboInfo = $to->get('http://api.t.163.com/account/verify_credentials.json', array());
	if($weiboInfo['error_code']=='401'){
		echo '<script type="text/javascript">alert("Something error!");window.close();</script>';
		return;
	}
	$screen_name=$weiboInfo['screen_name']?$weiboInfo['screen_name']:$weiboInfo['name'];
	$smc_user_name=$weiboInfo['name']?$weiboInfo['name']:$weiboInfo['id'];
	$r=array(
		'profile_image_url'=>$weiboInfo['profile_image_url'],
		'user_login'=>$smc_user_name,
		'weibo_uid'=>$weiboInfo['id'],
		'display_name'=>$screen_name,
		'user_url'=>'http://t.163.com/n/'.$weiboInfo['name'],
		'oauth_access_token'=>$tok['oauth_token'],
		'oauth_access_token_secret'=>$tok['oauth_token_secret'],
		'friends_count'=>$weiboInfo['friends_count'],
		'followers_count'=>$weiboInfo['followers_count'],
		'location'=>$weiboInfo['location'],
		'description'=>$weiboInfo['description'],
		'statuses_count'=>$weiboInfo['statuses_count'],
		'emailendfix'=>'t.163.com',
		'usernameprefix'=>'163_t_',
		'weibo'=>'163weibo'
	);
	return $r;
}

function smc_163weibo_getAccessToken($oauth_verifier,$oauth_token,$token_secret){
	global $APP_KEY, $APP_SECRET;
	$to=new Weibo163OAuth($APP_KEY, $APP_SECRET, $oauth_token, $token_secret);
	return $to->getAccessToken($oauth_verifier);
}
function smc_163weibo_weibo_update($data,$thumb,$tok){//-8737476567477287845
	global $APP_KEY, $APP_SECRET;
	$content=get_weibo_str_length($data,139,true);
	$to=new Weibo163OAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	if($thumb){
		$pic = $to->post('http://api.t.163.com/statuses/upload.json',array('pic'=>'@'.$thumb),true);
		if($pic['error_code']){
			return smc_163weibo_weibo_update($content,'',$tok);
		}
		$resp = $to->post('http://api.t.163.com/statuses/update.json',array('status'=>$content.' '.$pic['upload_image_url'],'source'=>'<a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">社交媒体连接</a>'));
	}else{
		$resp = $to->post('http://api.t.163.com/statuses/update.json',array('status'=>$content,'source'=>'<a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">社交媒体连接</a>'));
	}
	if($resp['id']){
		return $resp['id'];
	}else{
		return false;
	}
}
function smc_163weibo_weibo_repost($p,$smcdata){//-8737476567477287845
	global $APP_KEY, $APP_SECRET;
	$content=get_comment_str_length($p,139,true);
	if(!$p['weibosync']){
		return smc_163weibo_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$to=new Weibo163OAuth($APP_KEY, $APP_SECRET, $smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
	$param=array();
	$param['status']=$content;
	$param['id']=$p['weibosync'];
	$param['source']='<a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">社交媒体连接</a>';
	$param['is_retweet']=1;//print_r($param);die();
	$resp = $to->post('http://api.t.163.com/statuses/reply.json',$param);
}
function smc_163weibo_weibo_timeline($r,$tok){
	global $APP_KEY, $APP_SECRET;
	$to=new Weibo163OAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	switch($r['type']){
		case 'user_timeline':$api='http://api.t.163.com/statuses/user_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		case 'friends_timeline':$api='http://api.t.163.com/statuses/home_timeline.json';
							$param=array(
								'count'=>$r['number']
							);
							break;
		case 'public_timeline':$api='http://api.t.163.com/statuses/public_timeline.json';
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
			$text=strip_tags($w['text']); $retweet='';
			if(smc_strlen($text)>$length){
				$text = smc_substr($text, 0, $length).'...';
			}
			$text = smc_to_html($text,'163weibo');
			$thumb = preg_match('/http:\/\/126\.fm\/[a-zA-Z0-9]+/',$text, $matchs) ? '<a href="http://oimagea4.ydstatic.com/image?w=460&h=&url='.urlencode($matchs[0]).'&gif=0" original_pic="http://oimagea4.ydstatic.com/image?w=2000&h=&url='.urlencode($matchs[0]).'" rel="nofollow"><img src="http://oimagea4.ydstatic.com/image?w=120&h=&url='.urlencode($matchs[0]).'"  class="smc_weibo_image" alt="'.$w['user']['name'].'" />' : '';
			if(!empty($w['root_in_reply_to_screen_name'])){
				$retweet=array(
					'id'=>$w['root_in_reply_to_status_id'],
					'text'=>smc_strlen($w['root_in_reply_to_status_text'])>$length?smc_substr($w['root_in_reply_to_status_text'], 0, $length).'...':$w['root_in_reply_to_status_text'],
					'author'=>$w['root_in_reply_to_screen_name'],
					'avatar'=>'',
					'time'=>'',
					'source'=>'',
					'thumb'=>preg_match('/http:\/\/126\.fm\/[a-zA-Z0-9]+/',$w['root_in_reply_to_status_text'], $matchs) ? '<a href="http://oimagea4.ydstatic.com/image?w=460&h=&url='.urlencode($matchs[0]).'&gif=0" original_pic="http://oimagea4.ydstatic.com/image?w=2000&h=&url='.urlencode($matchs[0]).'" rel="nofollow"><img src="http://oimagea4.ydstatic.com/image?w=120&h=&url='.urlencode($matchs[0]).'"  class="smc_weibo_image" alt="'.$w['root_in_reply_to_screen_name'].'" />' : '',
					'url'=>'http://t.163.com/'.$w['user']['screen_name'].'/status/'.$w['root_in_reply_to_status_id']
				);
			}
			$data[]=array(
				'id'=>$w['id'],
				'text'=>$text,
				'author'=>$w['user']['name'],
				'avatar'=>$w['user']['profile_image_url'],
				'time'=>smc_time_since(strtotime($w['created_at'])),
				'source'=>'来自'.$w['source'],
				'thumb'=>$thumb,
				'url'=>'http://t.163.com/'.$w['user']['screen_name'].'/status/'.$w['id'],
				'retweeted_status'=>$retweet
			);
		}
		return $data;
	}else{
		return false;
	}
}
function _smc_163weibo_make_at_user($matches){
	if(strpos($matches[1],'#')!==false){
		return $matches[0];
	}else return '<a href="http://t.163.com/?nickName='.$matches[1].'" rel="nofollow">'.$matches[0].'</a>';
}
function _smc_163weibo_make_topic($matches){
	return '<a href="http://t.163.com/tag/'.urlencode($matches[1]).'" rel="nofollow">'.$matches[0].'</a>';
}
function smc_163weibo_add_follow_user($r){
	global $APP_KEY, $APP_SECRET;
	$to=new Weibo163OAuth($APP_KEY, $APP_SECRET, $r['oauth_access_token'], $r['oauth_access_token_secret']);
	$param=array(
		'user_id'=>$r['add_follow']
	);
	$resp = $to->post('http://api.t.163.com/friendships/create.json',$param);
	if($resp['error'])return false;
	else return true;
}