<?php
global $APP_KEY, $APP_SECRET, $config;
$smc_appkey=smc_get_weibo_appkey('renren');
$APP_KEY=$smc_appkey[0];
$APP_SECRET=$smc_appkey[1];

$config	= new stdClass;
$config->AUTHORIZEURL = 'https://graph.renren.com/oauth/authorize';
$config->ACCESSTOKENURL = 'https://graph.renren.com/oauth/token';
$config->SESSIONKEYURL = 'https://graph.renren.com/renren_api/session_key';
$config->CALLBACK = $_SESSION['smc_callback_url'];

$config->APIURL		= 'http://api.renren.com/restserver.do';
$config->APIKey		= $APP_KEY;
$config->SecretKey	= $APP_SECRET;
$config->APIVersion	= '1.0';
$config->decodeFormat	= 'json';

$config->APIMapping		= array( 
		'admin.getAllocation' => '',
		'connect.getUnconnectedFriendsCount' => '',
		'friends.areFriends' => 'uids1,uids2',
		'friends.get' => 'page,count',
		'friends.getFriends' => 'page,count',
		'notifications.send' => 'to_ids,notification',
		'users.getInfo'	=> 'fields',
		'status.set' => 'status',
		'status.gets' => 'count',
		'share.share' => 'type,url,comment'
);
require_once(dirname(__FILE__).'/base_renren.php');
/**
 * renren oauth flow(web server flow)
 * please oauth2 details here : http://wiki.dev.renren.com/wiki/%E4%BD%93%E9%AA%8C%E4%BA%BA%E4%BA%BAOAuth2.0%E9%AA%8C%E8%AF%81%E6%8E%88%E6%9D%83%E5%9F%BA%E6%9C%AC%E6%B5%81%E7%A8%8B
 *
 * @author tom.wang<superlinyzu@qq.com>
 */
class RenRenOAuth extends RESTClient {
	private $_config;
	
	/**
	 * construct function
	 *
	 * @author tom.wang<superlinyzu@qq.com>
	 */
	public function __construct(){
		global $config;
		
		parent::__construct();
		
		$this->_config = $config;
		
		if(empty($this->_config->AUTHORIZEURL) || empty($this->_config->ACCESSTOKENURL) || empty($this->_config->SESSIONKEYURL) || empty($this->_config->CALLBACK)){
			throw new exception('Invalid AUTHORIZEURL or ACCESSTOKENURL or SESSIONKEYURL or CALLBACK, please check config.inc.php');
		}

	}
	/**
	 * get authorize url
	 * please read details at : http://wiki.dev.renren.com/wiki/%E8%8E%B7%E5%8F%96Authorization_Code
	 *
	 * @author tom.wang<superlinyzu@qq.com>
	 * @return string : the url used for user to authorize
	 */
	public function getAuthorizeUrl() {
		$url = $this->_config->AUTHORIZEURL . '?response_type=code&scope=email,photo_upload,status_update,read_user_status,read_user_share,publish_feed,publish_share,publish_comment&client_id=' . $this->_config->APIKey . '&redirect_uri=' . urlencode($this->_config->CALLBACK);
		
		return $url;
	}
	
	/**
	 * get access token
	 * please read details at : http://wiki.dev.renren.com/wiki/%E4%BD%BF%E7%94%A8Authorization_Code%E8%8E%B7%E5%8F%96Access_Token
	 *
	 * @author tom.wang<superlinyzu@qq.com>
	 * @param string $code : the authorized code
	 * @return string : access token
	 */
	public function getAccessToken($code) {
		$url = $this->_config->ACCESSTOKENURL;
		$params = array(
			'client_id' => $this->_config->APIKey,
			'client_secret' => $this->_config->SecretKey,
			'redirect_uri' => $this->_config->CALLBACK,
			'grant_type' => 'authorization_code',
			'code' => $code,
		);
		$ret = $this->call($url, 'POST', $params);
		$ret = json_decode($ret, true);
		
		# check error
		if(isset($ret['error_description'])) {
			throw new Exception($ret['error_description']);
		}
		
		return $ret;
	}
	
	/**
	 * get session key
	 *
	 * @author tom.wang<superlinyzu@qq.com>
	 * @param string $access_token
	 * @return string : session key
	 */
	public function getSessionKey($access_token) {
		$url = $this->_config->SESSIONKEYURL;
		$params = array(
			'oauth_token' => $access_token,
		);
		$ret = $this->call($url, 'POST', $params);
		$ret = json_decode($ret, true);
		
		# check error
		if(isset($ret['error_description'])) {
			throw new Exception($ret['error_description']);
		}
		
		return $ret;
	}
}

function smc_renren_verify_credentials(){
	$config->CALLBACK = $_SESSION['smc_callback_url'];
	try{
		$to=new RenRenOAuth();
		$tok=$to->getAccessToken($_GET['code']);
		if(!isset($tok)||!isset($tok['access_token']))throw new Exception('An error occurred. No oauth token.');
		$get_session_key=$to->getSessionKey($tok['access_token']);
		$session_key=$get_session_key['renren_token']['session_key'];
		if(!$session_key)throw new Exception('An error occurred. No dession key.');
		$to=new RenRenClient();
		$to->setSessionKey($session_key); 
		$weiboInfo=$to->POST('users.getInfo',array('fields'=>'uid,name,sex,star,zidou,vip,birthday,email_hash,tinyurl,headurl,mainurl,hometown_location,work_history,university_history')); 
	}catch(Exception $e){
		wp_die('Error Message: '.$e->getMessage());
	}
	if(empty($weiboInfo)){
		echo '<script type="text/javascript">alert("Something Error!");window.close();</script>';
		exit;
	}
	$weiboInfo=$weiboInfo[0];
	$r=array(
		'profile_image_url'=>$weiboInfo['headurl'],
		'user_login'=>$weiboInfo['uid'],
		'display_name'=>$weiboInfo['name'],
		'user_url'=>'http://www.renren.com/profile.do?id='.$weiboInfo['uid'],
		'user_email'=>$weiboInfo['uid'].'@renren.com',
		'oauth_access_token'=>$session_key,
		'oauth_access_token_secret'=>'',
		'friends_count'=>'',
		'followers_count'=>'',
		'location'=>'',
		'description'=>$weiboInfo['description'],
		'statuses_count'=>'',
		'emailendfix'=>'renren.com',
		'usernameprefix'=>'renren_',
		'weibo'=>'renren'
	);
	return $r;
}
function smc_renren_weibo_update($data,$thumb,$tok){
	$data['tags']='';
	$content=get_weibo_str_length($data);
	$to=new RenRenClient();
	$to->setSessionKey($tok['oauth_token']);
	$resp=$to->POST('status.set',array('status'=>$content));
	if($resp['result']=='1'){
		$resp=$to->POST('status.gets',array('count'=>'1'));
		$id=$resp[0]['status_id']?$resp[0]['status_id']:'1';
		return $id;
	}else{
		return false;
	}
}
function smc_renren_weibo_repost($p,$smcdata){
	$to=new RenRenClient();
	$to->setSessionKey($smcdata['oauth_access_token']);
	$resp=$to->POST('share.share',array('type'=>6,'url'=>$p['url'],'comment'=>get_weibo_str_length($p['comment'].' - '.$p['url'])));
}
?>