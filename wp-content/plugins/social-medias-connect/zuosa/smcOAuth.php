<?php

class ZuosaAuth { 
    public $http_code; 
    public $url; 
    public $timeout = 30; 
    public $connecttimeout = 30;  
    public $ssl_verifypeer = FALSE; 
    public $format = 'json'; 
    public $decode_json = TRUE; 
    public $http_info; 
	public $appkey = '';
    public $useragent = 'Social Medias Connect'; 

	/**
	 * $oauth_token -> $username
	 * $oauth_token_secret -> $password
	 */
    function __construct($username = NULL, $password = NULL) { 
        if (!empty($username) && !empty($password)) { 
            $this->requestheader = array( 'Authorization' => 'Basic '.base64_encode("$username:$password") );; 
        } else { 
            $this->requestheade = NULL; 
        } 
    }
	
	function verify_account(){
		return $this->post('http://api.zuosa.com/account/verify_credentials.json');
	}

    function get($url, $parameters = array()) { 
		$url=count($parameters)>0?$url.'?'.join('&',$parameters):$url;
        $response = $this->http($url, 'GET'); 
        if ($this->format === 'json' && $this->decode_json) { 
            return json_decode($response, true); 
        } 
        return $response; 
    } 

    function post($url, $parameters = array() , $multi = false) { 
        $url=$url.'?api_key='. $this->appkey;
		$parameters['source']='SocialMedias';
        $response = $this->http($url, 'POST', $parameters , $multi ); 
        if ($this->format === 'json' && $this->decode_json) { 
            return json_decode($response, true); 
        } 
        return $response; 
    } 

    /** 
     * Make an HTTP request 
     * 
     * @return string API results 
     */ 
    function http($url, $method, $postfields = NULL , $multi = false){
		$this->http_info = array();
		$http=new WP_Http();
		$header=$this->requestheader;
		if($multi){
			$header=array_merge($header, array("Content-Type"=>"multipart/form-data;", "Expect"=>""));
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

/*2011.04.12*/
function smc_zuosa_verify_credentials(){
	if(!$_POST['username']||!$_POST['password']){
		wp_die('<h2>请输入你的做啥用户名和密码登陆</h2><form action="" method="post">
			<input type="text" name="username" onfocus="if(this.value==\'用户名\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'用户名\';" value="用户名" />
			<input type="password" name="password" onfocus="if(this.value==\'***\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'***\';" value="***" />
			<input type="submit" value="登陆"/>
		</form><div style="font-size:11px;margin-top:20px;"><b>注意：</b>此登录方式为非OAuth认证，需要你填入你的正确的用户名及密码，为了保证你的账号安全，请确认你信任[<span style="color:red;">'.get_bloginfo('name').'</span>]网站。</div>
		<div style="font-size:11px;margin-top:10px;text-align:right;">Powered by © <a target="_blank" href="http://www.qiqiboy.com/products/plugins/social-medias-connect">Social Medias Connect</a></div>
		','请输入您的做啥用户名和密码');
	}
	$to = new zuosaAuth($_POST['username'],$_POST['password']);
	$weiboInfo = $to->verify_account();
	if(!$weiboInfo['authorized']){
		wp_die('<script type="text/javascript">alert("用户名或者密码错误，请重试！");window.close();</script>');
	}
	$weiboInfo = $to->get('http://api.zuosa.com/users/show.json',array('id='.$_POST['username']));
	if($weiboInfo['screen_name']){
		$smc_user_name = $weiboInfo['screen_name'];
	} else {
		$smc_user_name = $weiboInfo['id'];
	}
	$r=array(
		'profile_image_url'=>$weiboInfo['profile_image_url'],
		'user_login'=>$smc_user_name,
		'display_name'=>$weiboInfo['name'],
		'user_url'=>'http://zuosa.com/'.$smc_user_name,
		'user_email'=>$weiboInfo['id'].'@zuosa.com',
		'oauth_access_token'=>$_POST['username'],
		'oauth_access_token_secret'=>$_POST['password'],
		'friends_count'=>$weiboInfo['friends_count'],
		'followers_count'=>$weiboInfo['followers_count'],
		'location'=>$weiboInfo['location'],
		'description'=>$weiboInfo['description'],
		'statuses_count'=>$weiboInfo['statuses_count'],
		'emailendfix'=>'zuosa.com',
		'usernameprefix'=>'zuosa_',
		'weibo'=>'zuosa'
	);
	return $r;
}

function smc_zuosa_getAccessToken(){
	$r=smc_zuosa_verify_credentials();
	if(is_array($r)){
		return array('oauth_token'=>$r['oauth_access_token'],'oauth_token_secret'=>$r['oauth_access_token_secret']);
	}else return false;
}
function smc_zuosa_weibo_update($data,$thumb,$tok){
	$content=get_weibo_str_length($data,140,'twitter');
	$to=new ZuosaAuth($tok['oauth_token'], $tok['oauth_token_secret']);
	$param=array();
	$param['status']=$content;
	if($thumb){
		$param['pics']=$thumb;//print_r($param);
		$resp = $to->post('http://api.zuosa.com/statuses/update.json',$param,true);
		if(!$resp['id']){
			return smc_zuosa_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('http://api.zuosa.com/statuses/update.json',$param);
	}
	if($resp['id']){
		return $resp['id'];
	}else{
		return false;
	}
}
function smc_zuosa_weibo_repost($p,$smcdata){//9105332181
	$content=get_comment_str_length($p,140,'twitter');
	smc_zuosa_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	if($p['weibosync']){
		sleep(2);
		$to=new zuosaAuth($smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
		$param=array();
		$param['status'] = get_weibo_str_length($p['comment'].' - '.$p['url']);
		$param['in_reply_to_status_id']=$p['weibosync'];//$p['weibosync'];
		$resp = $to->post('http://api.zuosa.com/statuses/update.json', $param);
	}
}
?>