<?php
global $APP_KEY, $APP_SECRET;
/* CopyRight © Social Medias Connect. 尊重开发者，如果没有特殊需求，请不要随意更改APPKEY。若要更改为你的appkey，请联系imqiqiboy@gmail.com。*/
$smc_appkey=smc_get_weibo_appkey('qqweibo');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];
define( "MB_API_HOST" , 'open.t.qq.com' );
define( "MB_RETURN_FORMAT" , 'json' );
require_once(dirname(__FILE__).'/../OAuth.php');
/**
 * 开放平台鉴权类
 * @param 
 * @return
 * @author tuguska
 */

class MBOpenTOAuth {
	public $host = 'http://open.t.qq.com/';
	public $timeout = 30; 
	public $connectTimeout = 30;
	public $sslVerifypeer = FALSE; 
	public $format = MB_RETURN_FORMAT;
	public $decodeJson = TRUE; 
	public $httpInfo; 
	public $userAgent = 'Social Medias Connect'; 
	public $decode_json = FALSE; 

    function accessTokenURL()  { return 'https://open.t.qq.com/cgi-bin/access_token'; } 
    function authenticateURL() { return 'https://open.t.qq.com/cgi-bin/authorize'; } 
    function authorizeURL()    { return 'https://open.t.qq.com/cgi-bin/authenticate'; } 
	function requestTokenURL() { return 'https://open.t.qq.com/cgi-bin/request_token'; } 

	function lastStatusCode() { return $this->http_status; } 

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
     * oauth授权之后的回调页面 
	 * 返回包含 oauth_token 和oauth_token_secret的key/value数组
     */ 
    function getRequestToken($oauth_callback = NULL) { 
        $parameters = array(); 
        if (!empty($oauth_callback)) { 
            $parameters['oauth_callback'] = $oauth_callback; 
        }  

        $request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters); //print_r($request);
		$token = OAuthUtil::parse_parameters($request); 
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']); 
        return $token; 
    } 

    /** 
     * 获取授权url
     * @return string 
     */ 
    function getAuthorizeURL($token, $signInWithWeibo = TRUE , $url='') { 
        if (is_array($token)) { 
            $token = $token['oauth_token']; 
        } 
        if (empty($signInWithWeibo)) { 
            return $this->authorizeURL() . "?oauth_token={$token}"; 
        } else { 
            return $this->authenticateURL() . "?oauth_token={$token}"; 
        } 
	} 	

    /** 
	* 交换授权
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

	function jsonDecode($response, $assoc=true)	{
		$response = preg_replace('/[^\x20-\xff]*/', "", $response);	
		$jsonArr = json_decode($response, $assoc);
		if(!is_array($jsonArr))
		{
			throw new Exception('格式错误!');
		}
		$ret = $jsonArr["ret"];
		$msg = $jsonArr["msg"];
		/**
		 *Ret=0 成功返回
		 *Ret=1 参数错误
		 *Ret=2 频率受限
		 *Ret=3 鉴权失败
		 *Ret=4 服务器内部错误
		 */
		switch ($ret) {
			case 0:
				return $jsonArr;;
				break;
			case 1:
				throw new Exception('参数错误!');
				break;
			case 2:
				throw new Exception('频率受限!');
				break;
			case 3:
				throw new Exception('鉴权失败!');
				break;
			default:
				$errcode = $jsonArr["errcode"];
				if(isset($errcode))			//统一提示发表失败
				{
					throw new Exception("发表失败");
					break;
					//require_once MB_COMM_DIR.'/api_errcode.class.php';
					//$msg = ApiErrCode::getMsg($errcode);
				}
				throw new Exception('服务器内部错误!');
				break;
		}
	}
	
    /** 
     * 重新封装的get请求. 
     * @return mixed 
     */ 
    function get($url, $parameters) { 
		$response = $this->oAuthRequest($url, 'GET', $parameters); 
		if (MB_RETURN_FORMAT === 'json') { 
            return $this->jsonDecode($response, true);
		}
        return $response; 
	}

	 /** 
     * 重新封装的post请求. 
     * @return mixed 
     */ 
    function post($url, $parameters = array() , $multi = false) { 
        $response = $this->oAuthRequest($url, 'POST', $parameters , $multi ); 
		if (MB_RETURN_FORMAT === 'json') { 
            return json_decode($response); 
        } 
        return $response; 
	}

	 /** 
     * DELTE wrapper for oAuthReqeust. 
     * @return mixed 
     */ 
    function delete($url, $parameters = array()) { 
        $response = $this->oAuthRequest($url, 'DELETE', $parameters); 
		if (MB_RETURN_FORMAT === 'json') { 
            return $this->jsonDecode($response, true); 
        } 
        return $response; 
    } 

    /** 
     * 发送请求的具体类
     * @return string 
     */ 
    function oAuthRequest($url, $method, $parameters , $multi = false) { 
        if (strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0) { 
            $url = "{$this->host}{$url}.{$this->format}"; 
		}
        $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters); 
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
        switch ($method) { 
        case 'GET': 
            return $this->http($request->to_url(), 'GET'); 
        default: 
            return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata($multi) , $multi ); 
        } 
	}     
	
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
			"sslverify"=>$this->sslVerifypeer,
			"user-agent"=>$this->userAgent,
			"body"=>$postfields,
			"headers"=>$header
		));
		if(!is_array($response))wp_die('Error Info: '.$response->errors['http_request_failed'][0].'<br/>你的主机不被支持，请联系你的主机商重新配置主机。<br/><br/>Powered by © <a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">社交媒体连接</a>');
		$this->http_code=$response['response']['code'];
		$this->http_info=$response['response']['message'];
		$this->http_header=$response['headers'];
		return $response['body'];
	}
}

/* 2011.04.12 */
function smc_qqweibo_verify_credentials(){
	global $APP_KEY, $APP_SECRET;
	$to = new MBOpenTOAuth($APP_KEY, $APP_SECRET, $_GET['oauth_token'],$_SESSION['smc_oauth_token_secret']);
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);
	$to = new MBOpenTOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$weiboInfo = $to->get('http://open.t.qq.com/api/user/info', array());
	$weiboInfo=$weiboInfo['data'];
	if(empty($weiboInfo)){
		echo '<script type="text/javascript">alert(""Something Error!);window.close();</script>';
		return;
	}
	$smc_user_name=$weiboInfo['name']?$weiboInfo['name']:$weiboInfo['uid'];
	$r=array(
		'profile_image_url'=>$weiboInfo['head'].'/50',
		'user_login'=>$smc_user_name,
		'weibo_uid'=>$weiboInfo['name'],
		'display_name'=>$weiboInfo['nick'],
		'user_url'=>'http://t.qq.com/'.$weiboInfo['name'],
		'user_email'=>$smc_user_name.'@t.qq.com',
		'oauth_access_token'=>$tok['oauth_token'],
		'oauth_access_token_secret'=>$tok['oauth_token_secret'],
		'friends_count'=>$weiboInfo['idolnum'],
		'followers_count'=>$weiboInfo['fansnum'],
		'location'=>$weiboInfo['location'],
		'description'=>$weiboInfo['introduction'],
		'statuses_count'=>$weiboInfo['tweetnum'],
		'emailendfix'=>'t.qq.com',
		'usernameprefix'=>'qq_t_',
		'weibo'=>'qqweibo'
	);
	return $r;
}
function smc_qqweibo_getAccessToken($oauth_verifier,$oauth_token,$token_secret){
	global $APP_KEY, $APP_SECRET;
	$to=new MBOpenTOAuth($APP_KEY, $APP_SECRET, $oauth_token, $token_secret);
	return $to->getAccessToken($oauth_verifier);
}
function smc_qqweibo_weibo_update($data,$thumb,$tok){
	global $APP_KEY, $APP_SECRET;
	$content=get_weibo_str_length($data,140,false,2);
	$to=new MBOpenTOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	if($thumb){
		//$thumb=urlencode($thumb);
		$resp = $to->post('http://open.t.qq.com/api/t/add_pic',array(
			'format' => 'json',
			'content' => $content,
			'clientip' => smc_get_client_ip(),
			'pic' => $thumb
		),true);//print_r('yuuu'.$resp);die();
		if($resp->ret){
			return smc_qqweibo_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('http://open.t.qq.com/api/t/add',array(
			'format' => 'json',
			'content' => $content,
			'clientip' => smc_get_client_ip()
		));
	}
	if($resp->ret=='0'&&$resp->data->id){
		return $resp->data->id;
	}else{
		return false;
	}
}
function smc_qqweibo_weibo_repost($p,$smcdata){
	global $APP_KEY, $APP_SECRET, $wp_smiliessearch;
	$p['comment']=preg_replace_callback($wp_smiliessearch, 'smc_qqweibo_convert_smilies', $p['comment']);
	$content=get_comment_str_length($p);
	if(!$p['weibosync']){
		return smc_qqweibo_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	}
	$to=new MBOpenTOAuth($APP_KEY, $APP_SECRET, $smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
	$param=array("content"=>get_weibo_str_length($p['comment'].' - '.$p['url']),
				"format" => 'json',
				"clientip" => smc_get_client_ip(),
				"reid"=>$p['weibosync']
			);
	$resp = $to->post('http://open.t.qq.com/api/t/re_add',$param);
	/*
	sleep(5);
	$param1=array("content"=>$p['comment'].' - '.$p['url'],
				"format" => 'json',
				"clientip" => smc_get_client_ip(),
				"reid"=>$p['weibosync']
			);
	$resp = $to->post( 'http://open.t.qq.com/api/t/comment' , $param1 ); //print_r($resp);
	*/
}
function smc_qqweibo_convert_smilies($smiley){
	if (count($smiley) == 0) {
		return '';
	}
	$smiley_array=array(
		':wink:' => ' /鄙视',
		':twisted:' => ' /咒骂',
		':smile:' => ' /微笑',
		':shock:' => ' /吓',
		':sad:' => ' /快哭了',
		':roll:' => ' /晕',
		':razz:' => ' /调皮]',
		':oops:' => ' /委屈',
		':o' => ' /哈欠',
		':neutral:' => ' /闭嘴',
		':mrgreen:' => ' /呲牙',
		':mad:' => ' /难过',
		':lol:' => '[鼓掌]',
		':idea:' => ' /强',
		':grin:' => ' /憨笑',
		':evil:' => ' /阴险',
		':eek:' => ' /惊讶',
		':cry:' => ' /流泪',
		':cool:' => ' /酷',
		':?:'=>' /疑问',
		':???:'=>' /白眼',
		':!:'=>' /抠鼻'
	);
	$smiley = trim(reset($smiley));
	if($smiley_array[$smiley]){
		return $smiley_array[$smiley];
	}else{
		return $smiley;
	}
}
function smc_qqweibo_weibo_timeline($r,$tok){
	global $APP_KEY, $APP_SECRET;
	$to=new MBOpenTOAuth($APP_KEY, $APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	switch($r['type']){
		case 'user_timeline':$api='http://open.t.qq.com/api/statuses/broadcast_timeline';
							$param=array(
								'pageflag'=>'0',
								'format' => 'json',
								'reqnum'=>$r['number'],
								'PageTime'=>'0',
								'Lastid'=>'0'
							);
							break;
		case 'friends_timeline':$api='http://open.t.qq.com/api/statuses/home_timeline';
							$param=array(
								'pageflag'=>'0',
								'format' => 'json',
								'reqnum'=>$r['number'],
								'PageTime'=>'0',
								'Lastid'=>'0'
							);
							break;
		case 'public_timeline':$api='http://open.t.qq.com/api/statuses/public_timeline';
							$param=array(
								'pageflag'=>'0',
								'format' => 'json',
								'reqnum'=>$r['number'],
								'PageTime'=>'0',
								'Lastid'=>'0'
							);
							break;
		default:break;
	}
	$resp = $to->get($api,$param);
	if($resp['ret']=='0'){
		$resp=$resp['data']['info']; $length=(int)$r['length'];
		$data=array();
		foreach($resp as $w){
			$text=strip_tags($w['text']); $retweet='';
			if(smc_strlen($text)>$length){
				$text = smc_substr($text, 0, $length).'...';
			}
			$thumb = $w['image'][0] ? '<a href="'.$w['image'][0].'/460" original_pic="'.$w['image'][0].'/2000" rel="nofollow"><img src="'.$w['image'][0].'/160" class="smc_weibo_image" alt="'.$w['nick'].'" /></a>' : '';
			if(!empty($w['source'])){
				$z=$w['source'];
				$_thumb = $z['image'][0] ? '<a href="'.$z['image'][0].'/460" original_pic="'.$z['image'][0].'/2000" rel="nofollow"><img src="'.$z['image'][0].'/160" class="smc_weibo_image" alt="'.$z['nick'].'" /></a>' : '';
				$_text = smc_strlen($z['text'])>$length?smc_substr($z['text'], 0, $length).'...':$z['text'];
				$retweet=array(
					'id'=>$z['id'],
					'text'=>smc_to_html($_text,'qqweibo'),
					'author'=>$z['nick'],
					'avatar'=>$z['head'].'/50',
					'time'=>smc_time_since($z['timestamp']),
					'source'=>'来自'.$z['from'],
					'thumb'=>$_thumb,
					'url'=>'http://t.qq.com/p/t/'.$z['id']
				);
			}
			$data[]=array(
				'id'=>$w['id'],
				'text'=>smc_to_html($text,'qqweibo'),
				'author'=>$w['nick'],
				'avatar'=>$w['head'].'/50',
				'time'=>smc_time_since($w['timestamp']),
				'source'=>'来自'.$w['from'],
				'thumb'=>$thumb,
				'url'=>'http://t.qq.com/p/t/'.$w['id'],
				'retweeted_status'=>$retweet
			);
		}
		return $data;
	}else{
		return false;
	}
}
function _smc_qqweibo_make_at_user($matches){
	if(strpos($matches[1],'#')!==false){
		return $matches[0];
	}else return '<a href="http://t.qq.com/'.$matches[1].'" rel="nofollow">'.$matches[0].'</a>';
}
function _smc_qqweibo_make_topic($matches){
	return '<a href="http://t.qq.com/k/'.urlencode($matches[1]).'" rel="nofollow">'.$matches[0].'</a>';
}
function smc_qqweibo_add_follow_user($r){
	global $APP_KEY, $APP_SECRET;
	$to=new MBOpenTOAuth($APP_KEY, $APP_SECRET, $r['oauth_access_token'], $r['oauth_access_token_secret']);
	$param=array(
		'name'=>$r['add_follow']
	);
	$resp = $to->post('http://open.t.qq.com/api/friends/add',$param);
	if($resp->errcode)return false;
	else return true;
}
?>
