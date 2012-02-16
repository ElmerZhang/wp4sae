<?php

class WBToAuth { 
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
	
	function verify_account($parameters){
		return $this->post('http://api.weiboto.com/account/verify_credentials.json',$parameters);
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
        //$url=$url.'?sourece='. $this->appkey;
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
function smc_wbto_verify_credentials(){
	if(!$_POST['username']||!$_POST['password']){
		wp_die('<h2>请输入你的微博通(weiboto.com)注册邮箱和密码登陆</h2><form action="" method="post">
			<input type="text" name="username" onfocus="if(this.value==\'邮箱\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'邮箱\';" value="邮箱" />
			<input type="password" name="password" onfocus="if(this.value==\'***\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'***\';" value="***" />
			<input type="submit" value="登陆"/>
		</form><div style="font-size:11px;margin-top:20px;"><b>注意：</b>此登录方式为非OAuth认证，需要你填入你的正确的用户名及密码，为了保证你的账号安全，请确认你信任[<span style="color:red;">'.get_bloginfo('name').'</span>]网站。</div>
		<div style="font-size:11px;margin-top:10px;text-align:right;">Powered by © <a target="_blank" href="http://www.qiqiboy.com/products/plugins/social-medias-connect">Social Medias Connect</a></div>
		','请输入您的做啥用户名和密码');
	}
	$to = new WBToAuth($_POST['username'],$_POST['password']);
	$weiboInfo = $to->verify_account(array('username'=>$_POST['username'],'password'=>$_POST['password']));
	if(!$weiboInfo['user'] || !$weiboInfo['user']['id']){
		wp_die('<script type="text/javascript">alert("用户名或者密码错误，请重试！");window.close();</script>');
	}
	$weiboInfo=$weiboInfo['user'];
	$r=array(
		'profile_image_url'=>$weiboInfo['profile_image_url'],
		'user_login'=>$weiboInfo['id'],
		'display_name'=>$weiboInfo['nickname'],
		'user_url'=>'http://weiboto.com/'.$weiboInfo['id'],
		'user_email'=>$weiboInfo['id'].'@weiboto.com',
		'oauth_access_token'=>$_POST['username'],
		'oauth_access_token_secret'=>$_POST['password'],
		'friends_count'=>'0',
		'followers_count'=>'0',
		'location'=>'',
		'description'=>'',
		'statuses_count'=>'',
		'emailendfix'=>'weiboto.com',
		'usernameprefix'=>'wbto_',
		'weibo'=>'wbto'
	);
	return $r;
}

function smc_wbto_getAccessToken(){
	$r=smc_wbto_verify_credentials();
	if(is_array($r)){
		return array('oauth_token'=>$r['oauth_access_token'],'oauth_token_secret'=>$r['oauth_access_token_secret']);
	}else return false;
}
function smc_wbto_weibo_update($data,$thumb,$tok){
	$content=get_weibo_str_length($data);
	$to=new WBToAuth($tok['oauth_token'], $tok['oauth_token_secret']);
	$param=array();
	$param['status']=$content;
	$param['weibo_type_list']='all';
	if($thumb){
		$param['pic']=$thumb;//print_r($param);
		$resp = $to->post('http://api.weiboto.com/statuses/update.json',$param,true);
		if(!$resp['status']){
			return smc_wbto_weibo_update($content,'',$tok);
		}
	}else{
		$resp = $to->post('http://api.weiboto.com/statuses/update.json',$param);
	}
	if($to->http_code == '200' && $resp['status']['id']){
		return $resp['status']['id'];
	}else{
		return false;
	}
}
function smc_wbto_weibo_repost($p,$smcdata){//9105332181
	$content=get_comment_str_length($p,140,'twitter');
	if(!$p['weibosync'])return smc_wbto_weibo_update($content,'',array('oauth_token'=>$smcdata['oauth_access_token'],'oauth_token_secret'=>$smcdata['oauth_access_token_secret']));
	$to=new WBToAuth($smcdata['oauth_access_token'], $smcdata['oauth_access_token_secret']);
	$param=array();
	$param['weibo_type_list']='all';
	$param['status'] = get_weibo_str_length($p['comment'].' - '.$p['url']);
	$param['in_reply_to_status_id']=$p['weibosync'];//$p['weibosync'];
	$resp = $to->post('http://api.weiboto.com/statuses/update.json', $param);
}
?>