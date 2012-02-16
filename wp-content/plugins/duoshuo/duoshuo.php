<?php
/*
Plugin Name: 多说
Plugin URI: http://wordpress.org/extend/plugins/duoshuo/
Description: 追求最佳用户体验的社会化评论框，为中小网站提供“新浪微博、QQ、人人、豆瓣等多帐号登录并评论”功能。“多说”帮你搭建更活跃，互动性更强的评论平台。它还有众多实用特性，功能强大且永久免费。
Author: 多说网
Version: 0.4.3
Author URI: http://duoshuo.com/
*/

if (version_compare(PHP_VERSION, '5.0.0', '<')){
	if(is_admin()){
		function duoshuo_php_version_warning(){
			echo '<div class="updated"><p><strong>您的php版本低于5.0，请升级php到最新版，多说就能为您服务了。</strong></p></div>';
		}
		add_action('admin_notices', 'duoshuo_php_version_warning');
	}
	return;
}

if (version_compare( $wp_version, '2.8', '<' )){
	if(is_admin()){
		function duoshuo_wp_version_warning(){
			echo '<div class="updated"><p><strong>您的WordPress版本低于2.8，请升级WordPress到最新版，多说就能为您服务了。</strong></p></div>';
		}
		add_action('admin_notices', 'duoshuo_wp_version_warning');
	}
	return;
}

function duoshuo_get_available_transport(){
	if (extension_loaded('curl') && function_exists('curl_init') && function_exists('curl_exec'))
		return 'curl';
	
	if (function_exists('fopen') && function_exists('ini_get') && ini_get('allow_url_fopen'))
		return 'streams';
	
	if (function_exists('fsockopen') && (false === ($option = get_option( 'disable_fsockopen' )) || time() - $option >= 43200))
		return 'fsockopen';
	
	return false;
}

$transport = duoshuo_get_available_transport();
if ($transport === false){
	if(is_admin()){
		function duoshuo_transport_warning(){
			echo '<div class="updated"><p><strong>没有可用的 HTTP 传输器</strong>，请联系你的主机商，安装或开启curl</p></div>';
		}
		add_action('admin_notices', 'duoshuo_transport_warning');
	}
	return;
}

if (!extension_loaded('json'))
	include dirname(__FILE__) . '/compat-json.php';

include dirname(__FILE__) . '/compat-wp.php';


function rfc3339_to_mysql($string){
	if (method_exists('DateTime', 'createFromFormat')){	//	php 5.3.0
		return DateTime::createFromFormat(DateTime::RFC3339, $string)->format('Y-m-d H:i:s');
	}
	else{
		$timestamp = strtotime($string);
		return gmdate('Y-m-d H:i:s', $timestamp  + get_option('gmt_offset') * 3600);
	}
}

function rfc3339_to_mysql_gmt($string){
	if (method_exists('DateTime', 'createFromFormat')){	//	php 5.3.0
		return DateTime::createFromFormat(DateTime::RFC3339, $string)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
	}
	else{
		$timestamp = strtotime($string);
		return gmdate('Y-m-d H:i:s', $timestamp);
	}
}

class Duoshuo_Exception extends Exception{
	const SUCCESS		= 0;
	const ENDPOINT_NOT_VALID = 1;
	const MISSING_OR_INVALID_ARGUMENT = 2;
	const ENDPOINT_RESOURCE_NOT_VALID = 3;
	const NO_AUTHENTICATED = 4;
	const INVALID_API_KEY = 5;
	const INVALID_API_VERSION = 6;
	const CANNOT_ACCESS = 7;
	const OBJECT_NOT_FOUND = 8;
	const API_NO_PRIVILEGE = 9;
	const OPERATION_NOT_SUPPORTED = 10;
	const API_KEY_INVALID = 11;
	const NO_PRIVILEGE = 12;
	const RESOURCE_RATE_LIMIT_EXCEEDED = 13;
	const ACCOUNT_RATE_LIMIT_EXCEEDED = 14;
	const INTERNAL_SERVER_ERROR = 15;
	const REQUEST_TIMED_OUT = 16;
	const NO_ACCESS_TO_THIS_FEATURE = 17;
	const INVALID_SIGNATURE = 18;
	
	const USER_DENIED_YOUR_REQUEST = 21;
	const EXPIRED_TOKEN = 22;
	const REDIRECT_URI_MISMATCH = 23;
	const DUPLICATE_CONNECTED_ACCOUNT = 24;
	
	const PLUGIN_DEACTIVATED = 30;
}

/**
 * 
 * @author shen2
 *
 */
class DuoshuoClient{
	var $end_point = 'http://duoshuo.com/api/';
	var $format = 'json';
	var $userAgent;
	var $shortName;
	var $http;
	
	function __construct($shortName = null){
		global $wp_version;
		
		$this->shortName = $shortName;
		$this->http = new WP_Http();
		$this->userAgent = 'WordPress/' . $wp_version . '|Duoshuo/'. Duoshuo::VERSION;
	}
	
	/**
	 * 
	 * @param $method
	 * @param $path
	 * @param $params
	 * @throws Duoshuo_Exception
	 * @return array
	 */
	function request($method, $path, $params = array()){
        if (!empty($this->shortName))
			$params['short_name'] = $this->shortName;
		
		$url = $this->end_point . $path. '.' . $this->format;
		$args = array(
			'method' => $method,
			'timeout' => 30,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent' => $this->userAgent,
			//'blocking' => true,
			//'headers' => array(),
			//'cookies' => array(),
			//'compress' => false,
			//'decompress' => true,
			'sslverify' => false,
			//'stream' => false,
			//'filename' => null
		);
		
		switch($method){
			case 'GET':
				$url .= '?' . http_build_query($params);
				break;
			case 'POST':
				$args['body'] = $params;
				break;
			default:
		}
		
		$response = $this->http->request($url, $args);
			
		if ($response->errors)//array(1) { ["http_request_failed"]=> array(1) { [0]=> string(21) "name lookup timed out" } }
            throw new Duoshuo_Exception('连接服务器失败,详细信息：' . json_encode($response->errors), Duoshuo_Exception::REQUEST_TIMED_OUT);
        
        $json = json_decode($response['body'], true);
		return $json === null ? $response['body'] : $json;
	}
	
	/**
	 * 
	 * @param $action
	 * @param $params
	 * @throws Duoshuo_Exception
	 * @return string
	 */
	function getContents($action, $params){
		$url = 'http://' . $this->shortName . '.duoshuo.com/' . $action . '/?' . http_build_query($params);
		$args = array(
			'method' => 'GET',
			'timeout' => 15,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent' => $this->userAgent,
		);
		
		$response = $this->http->request($url, $args);
		if ($response->errors)
            throw new Duoshuo_Exception('连接服务器失败,详细信息：' . json_encode($response->errors), Duoshuo_Exception::REQUEST_TIMED_OUT);
        
		return $response['body'];
	}
	
	/**
	 * FIXME 还没做完
	 */
	function buildSignature(){
		$base_string = $request->get_signature_base_string(); 
        $request->base_string = $base_string; 

        $key_parts = array( 
            $consumer->secret, 
            ($token) ? $token->secret : "" 
        ); 

		$key_parts = OAuthUtil::urlencode_rfc3986($key_parts); 
		$key = implode('&', $key_parts); 

        return base64_encode(hash_hmac('sha1', $base_string, $key, true));
	}
}

class Duoshuo {
	const DOMAIN = 'duoshuo.com';
	const STATIC_DOMAIN = 'static.duoshuo.com';
	const VERSION = '0.4.3';
	
	/**
	 * 
	 * @var DuoshuoClient
	 */
	static $_client = null;
	
	/**
	 * 
	 * @var string
	 */
	static $shortName;
	
	/**
	 * 
	 * @var string
	 */
	static $secret;
	
	/**
	 * 
	 * @var string
	 */
	static $pluginDirUrl;
	
	/**
	 * 
	 * @var array
	 */
	static $errorMessages = array();
	
	// ugly global hack for comments closing
	static $EMBED = false;
	
	static function setVariables(){
		self::$shortName = get_option('duoshuo_short_name');
		self::$secret = get_option('duoshuo_secret');
		
		$defaultOptions = array(
			'duoshuo_cron_sync_enabled'	=>	1,
			'duoshuo_seo_enabled'		=>	1,
			'duoshuo_comments_wrapper_intro'=>'',
			'duoshuo_comments_wrapper_outro'=>'',
			'duoshuo_last_post_id'		=>	0,
		);
		
		foreach ($defaultOptions as $optionName => $value)
			if (get_option($optionName) === false)
				update_option($optionName, $value);
		
		self::$pluginDirUrl = plugin_dir_url(__FILE__);
	}
	
	static function initialize(){
		global $wp_version;
		
		if(is_admin()){//在admin界面内执行的action
			// wordpress2.8 以后都支持这个过滤器
			add_filter('plugin_action_links_duoshuo/duoshuo.php', array('Duoshuo', 'pluginActionLinks'), 10, 2);
			
			if (empty(self::$shortName) || empty(self::$secret)){//你尚未安装这个插件。
				add_action('admin_notices', array('Duoshuo', 'warning'));
				return ;
			}
			
			add_action('admin_notices', array('Duoshuo', 'notices'));
			
			add_filter('post_row_actions', array('Duoshuo', 'actionsFilter'));
			
			//// backwards compatible (before WP 3.0)
			if (version_compare( $wp_version, '3.0', '<' )){
				add_action('admin_init', array('Duoshuo', 'addPostMetaBoxes'));
				add_action('admin_init', array('Duoshuo', 'addPageMetaBoxes'));
				
				function duoshuo_wp_version_notice(){
					echo '<div class="updated"><p>您的WordPress版本低于3.0，如果您能升级WordPress，多说就能更好地为您服务。</p></div>';
				}
				add_action('admin_notices', 'duoshuo_wp_version_notice');
			}
			else{
				add_action('add_meta_boxes_post', array('Duoshuo', 'addPostMetaBoxes'));
				add_action('add_meta_boxes_page', array('Duoshuo', 'addPageMetaBoxes'));
			}
			
			add_action('publish_post', array('Duoshuo','syncPostToRemote'));
			add_action('publish_page', array('Duoshuo','syncPostToRemote'));
			add_action('profile_update', array('Duoshuo', 'syncUserToRemote'));
			add_action('user_register', array('Duoshuo', 'syncUserToRemote'));
			
			add_action('wp_dashboard_setup', array('Duoshuo','addDashboardWidget'));
			
			if (defined('DOING_AJAX')){
				add_action('wp_ajax_duoshuo_export', array('Duoshuo', 'export'));
			}
		}
		else{
			if (empty(self::$shortName) || empty(self::$secret)){
				return;
			}
			
			add_action('login_form', array('Duoshuo', 'loginForm'));
			add_action('wp_login', array('Duoshuo', 'login'));
			
			// wp2.8 以后支持这个事件
			add_action('wp_print_scripts', array('Duoshuo', 'appendScripts'));
			//add_action('wp_head', array('Duoshuo', 'appendStyles'));
			
			//以下应该根据是否设置，选择是否启用
			add_filter('comments_template', array('Duoshuo','commentsTemplate'));
			
			//add_filter('comments_number')
		}
		
		if (get_option('duoshuo_cron_sync_enabled')){
			add_action('duoshuo_sync_cron', array('Duoshuo', 'syncCron'));
			if (!wp_next_scheduled('duoshuo_sync_cron')){
				wp_schedule_event(time(), 'hourly', 'duoshuo_sync_cron');
			}
		}
		 
		add_action('widgets_init', array('Duoshuo','registerWidgets'));
	}
	
	static function addPages() {
		if (empty(self::$shortName) || empty(self::$secret) || !is_numeric(get_option('duoshuo_synchronized'))){
			add_object_page(
				'安装',
				'多说评论',
				'moderate_comments',
				'duoshuo',
				array('Duoshuo','config'),
				self::$pluginDirUrl . 'images/menu-icon.png' 
			);
		}
		elseif (current_user_can('moderate_comments')){
			add_object_page(
				'多说评论管理',
				'多说评论',
				'moderate_comments',
				'duoshuo',
				array('Duoshuo','manage'),
				self::$pluginDirUrl . 'images/menu-icon.png' 
			);
			add_submenu_page(
		         'duoshuo',//$parent_slug
		         '评论框设置',//page_title
		         '评论框设置',//menu_title
		         'manage_options',//权限
		         'duoshuo-settings',//menu_slug
		         array('Duoshuo', 'settings')//function
		    );
		    if (self::getUserMeta(wp_get_current_user()->ID, 'duoshuo_user_id')){
			    add_submenu_page(
			         'duoshuo',//$parent_slug
			         '我的多说帐号',//page_title
			         '我的多说帐号',//menu_title
			         'level_0',//权限
			         'duoshuo-profile',//menu_slug
			         array('Duoshuo', 'profile')//function
			    );
		    }
		}
		elseif(current_user_can('level_0')){
		    if (self::getUserMeta(wp_get_current_user()->ID, 'duoshuo_user_id')){
			    add_submenu_page(
			         'profile.php',//$parent_slug
			         '绑定社交帐号',//page_title
			         '[多说]绑定社交帐号',//menu_title
			         'level_0',//权限
			         'duoshuo-profile',//menu_slug
			         array('Duoshuo', 'profile')//function
			    );
		    }
		}
	}
	
	static function addPostMetaBoxes($post = null){
		add_meta_box('duoshuo-sidebox', '同时发布到', array('Duoshuo','syncOptions'), 'post', 'side', 'high');
		
		//wp 3.0以下不支持此项功能
		/**
		 * TODO 
		if ($post !== null && 'publish' == $post->post_status || 'private' == $post->post_status)
			add_meta_box('duoshuo-comments', '来自社交网站的评论(多说)', array('Duoshuo','managePostComments'), 'post', 'normal', 'low');
		 */
	}
	
	
	static function addPageMetaBoxes($post = null){
		add_meta_box('duoshuo-sidebox', '同时发布到', array('Duoshuo','syncOptions'), 'page', 'side', 'high');
		
		//wp 3.0以下不支持此项功能
		/**
		 * TODO 
		if ($post !== null && ('publish' == $post->post_status || 'private' == $post->post_status))
			add_meta_box('duoshuo-comments', '来自社交网站的评论(多说)', array('Duoshuo','managePostComments'), 'post', 'normal', 'low');
		 */
	}
	
	static function addDashboardWidget(){
		wp_add_dashboard_widget('dashboard_duoshuo', '多说', array('Duoshuo', 'dashboardWidget'));
	}
	
	static function registerSettings(){
		register_setting('duoshuo', 'duoshuo_short_name');
		register_setting('duoshuo', 'duoshuo_secret');
		
		register_setting('duoshuo', 'duoshuo_cron_sync_enabled');
		register_setting('duoshuo', 'duoshuo_seo_enabled');
		register_setting('duoshuo', 'duoshuo_comments_wrapper_intro');
		register_setting('duoshuo', 'duoshuo_comments_wrapper_outro');
	}
	
	/**
	 * 
	 * @return DuoshuoClient
	 */
	static function getClient(){
		if (self::$_client === null)
			self::$_client = new DuoshuoClient(self::$shortName);
		return self::$_client;
	}
	
	static function buildSignature($string){
		return base64_encode(hash_hmac('sha1', $string, self::$secret, true));
	}
	
	static function showException($e){
		echo '<div class="updated fade"><p>' . $e->getMessage() . '</p></div>';
	}
	
	static function sendException($e){
		$response = array(
			'code'	=>	$e->getCode(),
			'errorMessage'=>$e->getMessage(),
		);
		echo json_encode($response);
		exit;
	}
	
	static function config(){
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && !(self::$shortName && self::$secret)){
			self::registerSite();
		}
		include_once dirname(__FILE__) . '/config.php';
	}
	
	static function manage(){
		include_once dirname(__FILE__) . '/manage.php';
	}
	
	static function settings(){
		 if ($_SERVER['REQUEST_METHOD'] == 'POST')
			try{
				 $params = $_POST + array(
					'short_name'	=>	Duoshuo::$shortName,
					'local_identity'=>	$user->ID,
					'signature'		=>	Duoshuo::buildSignature($user->ID)
				);
				
				$response = Duoshuo::getClient()->request('POST', 'sites/settings', $params);
				
				if ($response['code'] != 0)
					echo '<div id="message" class="updated fade"><p><strong>' . $response['errorMessage'] . '</strong></p></div>';
			}
			catch(Duoshuo_Exception $e){
				Duoshuo::showException($e);
			}
		include_once dirname(__FILE__) . '/settings.php';
	}
	
	static function profile(){
		include_once dirname(__FILE__) . '/profile.php';
	}
	
	static function deactivate($network_wide){
		delete_option('duoshuo_synchronized');
	}
	
	static function uninstall(){
		//delete_option('duoshuo_short_name');
		delete_option('duoshuo_secret');
		delete_option('duoshuo_synchronized');
		$redirect_url = add_query_arg('message', 'uninstalled', admin_url('admin.php?page=duoshuo'));
		wp_redirect($redirect_url);
		exit;
	}
	
	static function commentsTemplate($value){
	    global $post;
	    global $comments;
		/*
	    if ( !( is_singular() && ( have_comments() || 'open' == $post->comment_status ) ) ) {
	        return;
	    }
	
	    if ( !dsq_is_installed() || !dsq_can_replace() ) {
	        return $value;
	    }*/
	    
	    $threadId = get_post_meta($post->ID, 'duoshuo_thread_id', true);
	    
	    if (empty($threadId)){
	    	self::syncUserToRemote($post->post_author);
	    	self::syncPostToRemote($post->ID);
		    try{
		    	self::syncPostComments($post->ID);
		    }
		    catch(Duoshuo_Exception $e){
				self::showException($e);
			}
	    }
	    
		self::$EMBED = true;
		return dirname(__FILE__) . '/comments.php';
	    //	return $value;
	}
	
	static function warning(){
		echo '<div class="updated"><p><strong>只要再<a href="' . admin_url('admin.php?page=duoshuo') . '">配置一下</a>多说帐号，多说就能开始为您服务了。</strong></p></div>';
	}
	
	static function requestHandler(){
		if ($_SERVER['REQUEST_METHOD'] == 'POST'
			&& isset($_GET['page'])
			&& in_array($_GET['page'], array('duoshuo-settings', 'duoshuo')))
			switch(true){
				case isset($_POST['duoshuo_uninstall']):
					self::uninstall();
					break;
				case isset($_POST['duoshuo_local_options']):
					update_option('duoshuo_cron_sync_enabled', isset($_POST['duoshuo_cron_sync_enabled']) ? 1 : 0);
					update_option('duoshuo_seo_enabled', isset($_POST['duoshuo_seo_enabled']) ? 1 : 0);
					update_option('duoshuo_comments_wrapper_intro', isset($_POST['duoshuo_comments_wrapper_intro']) ? stripslashes($_POST['duoshuo_comments_wrapper_intro']) : '');
					update_option('duoshuo_comments_wrapper_outro', isset($_POST['duoshuo_comments_wrapper_outro']) ? stripslashes($_POST['duoshuo_comments_wrapper_outro']) : '');
					break;
				default:
			}
	}
	
	static function appendScripts(){
		$query = array('short_name' => self::$shortName);
		echo '<script type="text/javascript">var duoshuoQuery = ' . json_encode($query) . ';</script>';
		 
		$duoshuo_shortname = 'static';
		$url = 'http://' . $duoshuo_shortname . '.' . self::DOMAIN . '/embed.js';
		//?pname=wordpress&pver=' . Duoshuo::VERSION
		wp_register_script('duoshuo-embed', $url, array(), null);
		
		wp_enqueue_script('duoshuo-embed');
	}
	
	/**
	 * 在wp_print_scripts 没有执行的时候执行最传统的代码
	 */
	static function printScripts(){
		$query = array('short_name' => self::$shortName);
		$duoshuo_shortname = 'static';?>
<script type="text/javascript">
(function() {
    var ds = document.createElement('script'); ds.type = 'text/javascript'; ds.async = true;
    var duoshuoQuery = <?php echo json_encode($query);?>;
    ds.src = 'http://<?php echo $duoshuo_shortname;?>.duoshuo.com/embed.js';
    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ds);
})();
</script><?php
	}
	
	// Register widgets.
	static function registerWidgets(){
		require_once dirname(__FILE__) . '/widgets.php';
		
		register_widget('Duoshuo_Widget_Recent_Comments');
	}
	
	static function login($userLogin){
		if (isset($_POST['login_duoshuo'])){
			$user = get_user_by('login', $userLogin);
			
			self::loginUser($user->ID, $_POST['login_duoshuo']);
		}
	}
	
	static function loginUser($userId, $unique){
		$params = array(
			'short_name'	=>	Duoshuo::$shortName,
			'local_identity'=>	$userId,
			'unique'		=>	$unique,
			'signature'		=>	self::buildSignature($userId)
		);
		
		try{
			$apiResponse = Duoshuo::getClient()->request('POST', 'users/localLogin', $params);
			
			if (isset($apiResponse['response']['user_id']))
				function_exists('update_user_meta')
					? update_user_meta($userId, 'duoshuo_user_id', $apiResponse['response']['user_id'])
					: update_usermeta($userId, 'duoshuo_user_id', $apiResponse['response']['user_id']);
		}
		catch(Duoshuo_Exception $e){
			
		}
	}
	
	static function loginForm(){?>
<script>
function updateDuoshuoUnique(unique){
	document.write('<p><label style="font-size:12px;"><input name="login_duoshuo" type="checkbox" value="' + unique + '" tabindex="85" checked="checked" /> 同时登录多说</label></p>');
}
</script>
<script src="http://<?php echo self::DOMAIN;?>/identifier.js?callback=updateDuoshuoUnique&<?php echo time();?>"></script>
<?php 
	}

	static function registerSite(){
		$params = array(
			'name'		=>	get_option('blogname'),
			'description'=> get_option('blogdescription'),
			'url'		=>	get_option('home'),
			'siteurl'	=>	get_option('siteurl'),
			'admin_email'=>	get_option('admin_email'),
			'timezone'	=>	get_option('timezone_string'),
			'use_smilies'=>	get_option('use_smilies'),
			'system'	=>	'wordpress',
			'system_theme'=>get_current_theme(),
			'plugin_dir_url'=>	self::$pluginDirUrl,
		);
		$params['unique'] = $_POST['unique'];
		
		try{
			if (empty($_POST['secret'])){	//	register
				$params['short_name'] = trim($_POST['short_name']);
				
				$client = new DuoshuoClient($params['short_name']);
				
				$apiResponse = $client->request('POST', 'sites/create', $params);
			}
			else{	//	connect
				$params['short_name'] = trim($_POST['short_name']);
				$params['secret'] = trim($_POST['secret']);
				
				$client = new DuoshuoClient($params['short_name']);
				
				$apiResponse = $client->request('POST', 'sites/update', $params);
			}
			
			if ($apiResponse['code'] != 0)
				throw new Duoshuo_Exception($apiResponse['errorMessage'], $apiResponse['code']);
			
			$site = $apiResponse['response'];
			
			update_option('duoshuo_short_name', $site['short_name']);
			update_option('duoshuo_secret', $site['secret']);
			self::$shortName = $site['short_name'];
			self::$secret = $site['secret'];
			
			$client->shortName = $site['short_name'];
			$user = wp_get_current_user();
			self::syncUserToRemote($user->ID);
			self::loginUser($user->ID, $_POST['unique']);
		}
		catch(Duoshuo_Exception $e){
			self::showException($e);
		}
	}
	
	static function export(){
		$progress = get_option('duoshuo_synchronized');
		
		if (!$progress || is_numeric($progress))//	之前已经完成了导出流程
			$progress = 'user/0';
		
		list($type, $offset) = explode('/', $progress);
		
		try{
			switch($type){
				case 'user':
					$limit = 30;
					$count = self::exportUsers($limit, $offset);
					break;
				case 'post':
					$limit = 10;
					$count = self::exportPosts($limit, $offset);
					break;
				case 'comment':
					$limit = 50;
					$count = self::exportComments($limit, $offset);
					break;
				default:
			}
			
			if ($count == $limit){
				$progress = $type . '/' . ($offset + $limit);
			}
			elseif($type == 'user')
				$progress = 'post/0';
			elseif($type == 'post')
				$progress = 'comment/0';
			elseif($type == 'comment')
				$progress = time();
			
			update_option('duoshuo_synchronized', $progress);
	        $response = array(
				'progress'=>$progress,
	        	'code'	=>	0
			);
			self::sendJsonResponse($response);
		}
		catch(Duoshuo_Exception $e){
			self::sendException($e);
		}
	}
	
	static function exportUsers($limit, $offset = 0){
		global $wpdb;
		
		// 不包括user_login, user_pass
		$columns = array('ID', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'display_name');
		$users = $wpdb->get_results( $wpdb->prepare("SELECT " . implode(',', $columns) . "  FROM $wpdb->users order by ID asc limit $offset,$limit"));
		
		$blog_prefix = $wpdb->get_blog_prefix();
	    foreach($users as $user)
		    $user->capabilities = self::getUserMeta($user->ID, $blog_prefix.'capabilities');
		$params = array('data'=>json_encode($users));
		
		$remoteResponse = self::getClient()->request('POST', 'import/wordpressUsers', $params);
	
		foreach($remoteResponse['response'] as $userId => $duoshuoUserId)
			self::updateUserMeta($userId, 'duoshuo_user_id', $duoshuoUserId);
		
		return count($users);
	}
	
	static function syncUserToRemote($userId){
		global $wpdb;
		$user = get_userdata($userId);
		
		if ($user instanceof WP_User){	//	wordpress 3.3
			$userData = $user->data;
			unset($userData->user_pass);
			unset($userData->user_login);
			$userData->capabilities = $user->caps;
		}
		else{
			$userData = $user;
			unset($userData->user_pass);
			unset($userData->user_login);
			$blog_prefix = $wpdb->get_blog_prefix();
			$userData->capabilities = self::getUserMeta($user->ID, $blog_prefix.'capabilities', true);
		}
		
		$params = array('data'=>json_encode(array($userData)), 'short_name'=>self::$shortName);
		try{
			$remoteResponse = self::getClient()->request('POST','import/wordpressUsers', $params);
		
			if (isset($remoteResponse['response'])){
				foreach($remoteResponse['response'] as $userId => $duoshuoUserId)
					self::updateUserMeta($userId, 'duoshuo_user_id', $duoshuoUserId);
			}
		}
		catch(Duoshuo_Exception $e){
			self::showException($remoteResponse);
		}
	}
	
	static function exportPosts($limit, $offset = 0){
		global $wpdb;
		
		$columns = array('ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'post_name', 'post_modified_gmt', 'guid', 'post_type');
		$posts = $wpdb->get_results( $wpdb->prepare("SELECT " . implode(',', $columns) . "  FROM $wpdb->posts where post_status <> 'auto-draft' and post_status <> 'inherit' order by ID asc limit $offset,$limit") );
		
		$data = array();
		foreach($posts as $post)
			$data[] = Duoshuo::packagePost($post);
		
		$params = array('data'=>json_encode($data));
	
		
		$remoteResponse = self::getClient()->request('POST','import/wordpressPosts', $params);
		
		foreach($remoteResponse['response'] as $postId => $threadId)
			update_post_meta($postId, 'duoshuo_thread_id', $threadId);
		
		return count($posts);
	}
	
	/**
	 * 同步这篇文章到所有社交网站
	 * @param string $postId
	 */
	static function syncPostToRemote($postId){
		$post = get_post($postId);
		
		$params = self::packagePost($post);
		
		$user = wp_get_current_user();
		$params['local_identity'] = $user->ID;
		$params['signature'] = self::buildSignature($user->ID);
		
		try{
			$response = self::getClient()->request('POST', 'threads/sync', $params);
			if ($response['code'] == 0 && isset($response['response']))
				update_post_meta($postId, 'duoshuo_thread_id', $response['response']['thread_id']);
		}
		catch(Duoshuo_Exception $e){
			self::showException($e);
		}
	}
	
	static function packagePost($post){
		$post->custom = get_post_custom($post->ID);
		
		$params = array(
			'title'		=>	$post->post_title,
			'content'	=>	$post->post_content,
			'excerpt'	=>	$post->post_excerpt,
			'created_at'=>	mysql2date('Y-m-d\TH:i:sP', $post->post_date_gmt),
			'updated_at'=>	mysql2date('Y-m-d\TH:i:sP', $post->post_modified_gmt),
			'ip'		=>	$_SERVER['REMOTE_ADDR'],
			'url'		=>	get_permalink($post),
			'slug'		=>	$post->post_name,
			'status'	=>	$post->post_status,
			'comment_status'=>	$post->comment_status,
			'guid'		=>	$post->guid,
			'type'		=>	$post->post_type,
			'meta'		=>	json_encode($post),
			'source'	=>	'wordpress',
			'source_author_id'=>$post->post_author,
			'source_thread_id'=>$post->ID,
		);
		if (isset($_POST['sync_to']))
			$params['sync_to'] = implode(',', $_POST['sync_to']);
		
		if (function_exists('get_post_thumbnail_id')){	//	WordPress 2.9开始支持
			$post_thumbnail_id = get_post_thumbnail_id( $post->ID );
			if ( $post_thumbnail_id ) {
				$params['thumbnail'] = wp_get_attachment_url($post_thumbnail_id);
				//$image = wp_get_attachment_image_src( $post_thumbnail_id, $size, false);
				//list($src, $width, $height) = $image;
				//$meta = wp_get_attachment_metadata($id);
				//'large-feature'
				//'post-thumbnail'
			}
		}
		
		$args = array(
			'post_parent' => $post->ID,
			'post_status' => 'inherit',
			'post_type'	=> 'attachment',
			'post_mime_type' => 'image',
			'order' => 'ASC',
			'orderby' => 'menu_order ID'
		);
		$images = array();
		foreach(get_children($args) as $attachment)
			$images[] = wp_get_attachment_url($attachment->ID);
		if (!empty($images))
			$params['images'] = json_encode($images);
		
		$authorId = self::getUserMeta($post->post_author, 'duoshuo_user_id', true);
		if (!empty($authorId))
			$params['author_id'] = $authorId;
		
		$threadId = get_post_meta($post->ID, 'duoshuo_thread_id', true);
		if (!empty($threadId))
			$params['thread_id'] = $threadId;
		return $params;
	}
	
	static function exportComments($limit, $offset = 0){
		global $wpdb;
		
		$comments = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->comments where comment_agent NOT LIKE 'Duoshuo/%%' order by comment_ID asc limit $offset,$limit") );
		
		$remoteResponse = self::getClient()->request('POST', 'import/wordpressComments', array('data'=>json_encode($comments)));
	
		if (count($comments) < $limit)
			update_option('duoshuo_synchronized', time());
		
		return count($comments);
	}
	
	static function syncPostComments($postId){
		global $wpdb;
		
		$comments = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->comments where comment_post_ID = %d AND comment_agent NOT LIKE 'Duoshuo/%%' order by comment_ID asc", $postId));
	    
		$remoteResponse = self::getClient()->request('POST','import/wordpressComments', array('data'=>json_encode($comments)));
		
		return $remoteResponse;
	}
	
	static function syncCommentsToLocal(){
		update_option('_duoshuo_sync_lock', time());
		
		$last_post_id = get_option('duoshuo_last_post_id');
		
		$params = array(
			'start_id' => $last_post_id,
            'limit' => 20,
            'order' => 'asc',
			'sources'=>'duoshuo,anonymous'
		);
		
		$client = self::getClient();
		
		$response = $client->request('GET', 'sites/listPosts', $params);
		
		$imported = self::_syncCommentsToLocal($response['response']);
		$client->request('POST', 'posts/imported', $imported);
	
		$max_post_id = 0;
		foreach($response['response'] as $post)
			if ($post['post_id'] > $max_post_id)
				$max_post_id = $post['post_id'];
		
		if ($max_post_id > $last_post_id)
			update_option('duoshuo_last_post_id', $max_post_id);
		
		delete_option('_duoshuo_sync_lock');
		
		return $imported;
	}
	
	/**
	 * 从服务器pull评论到本地
	 * 
	 * @param array $posts
	 */
	static function _syncCommentsToLocal($posts){
		global $wpdb;
		
		$approvedMap = array(
			'pending'	=>	'0',
			'approved'	=>	'1',
			'deleted'	=>	'trash',
			'spam'		=>	'spam',
		);
		
		$threadMap = array();
		$commentMap = array();
		$userMap = array();
		foreach($posts as $post){
			$threadMap[$post['thread_id']] = null;
			$commentMap[$post['parent_id']] = null;
			$userMap[$post['author_id']] = null;
			
		}
		unset($commentMap[0]);
		
		$thread_ids = "'" . implode("', '", array_keys($threadMap)) . "'";
		$results = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'duoshuo_thread_id' AND meta_value IN ($thread_ids)");
	    foreach ($results as $result)
	        $threadMap[$result->meta_value] = $result->post_id;
		
	    $comment_ids = "'" . implode("', '", array_keys($commentMap)) . "'";
	    $results = $wpdb->get_results( "SELECT comment_id, meta_value FROM $wpdb->commentmeta WHERE meta_key = 'duoshuo_post_id' AND meta_value IN ($comment_ids)");
		foreach ($results as $result)
	        $commentMap[$result->meta_value] = $result->comment_id;
		
		$user_ids = "'" . implode("', '", array_keys($userMap)) . "'";
	    $results = $wpdb->get_results( "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = 'duoshuo_user_id' AND meta_value IN ($user_ids)");
		foreach ($results as $result)
	        $userMap[$result->meta_value] = $result->user_id;
	    
	    $imported = array();
	    foreach($posts as $post){
			
			if (!$threadMap[$post['thread_id']]){
				//skip
				continue;
			}
			
			$data = array(
				'comment_author'	=>	$post['author_name'],
		 		'comment_author_email'=>$post['author_email'],
		 		'comment_author_url'=>	$post['author_url'], 
		 		'comment_author_IP'	=>	$post['ip'],
				'comment_date'		=>	rfc3339_to_mysql($post['created_at']), 
		 		'comment_date_gmt'	=>	rfc3339_to_mysql_gmt($post['created_at']),
				'comment_content'	=>	$post['message'], 
		 		'comment_approved'	=>	$approvedMap[$post['status']],
				'comment_agent'		=>	'Duoshuo/' . Duoshuo::VERSION . ':' . $post['post_id'],
				'comment_type'		=>	$post['type'],
				'comment_post_ID'	=>	$threadMap[$post['thread_id']],
				//'comment_karma'
			);
			
			if ($post['parent_id'] && $commentMap[$post['parent_id']])
				$data['comment_parent'] = $commentMap[$post['parent_id']];
			
			if ($post['author_id'] && isset($userMap[$post['author_id']]))
				$data['user_id'] = $userMap[$post['author_id']];
			
			if (isset($post['source_post_id']) && $post['source_post_id']){
				$data['comment_ID'] = $post['source_post_id'];
				wp_update_comment($data);
			}
			else{
				$data['comment_ID'] = wp_insert_comment($data);
			}
			$imported['post_' . $post['post_id']] = $data['comment_ID'];
			
			update_comment_meta($data['comment_ID'], 'duoshuo_parent_id', $post['parent_id']);
        	update_comment_meta($data['comment_ID'], 'duoshuo_post_id', $post['post_id']);
		}
		
		return $imported;
	}
	
	static function notices(){
		foreach(self::$errorMessages as $message)
			echo '<div class="updated"><p><strong>'.$message.'</strong></p></div>';
		
		$duoshuo_notice = get_option('duoshuo_notice');
		if (!empty($duoshuo_notice)){//系统推送的通知
			echo '<div class="updated">'.$duoshuo_notice.'</div>';
		}
		elseif ($duoshuo_notice === false){
			update_option('duoshuo_notice', '');
		}
		
		$messages = array(
			'registered'	=>'<strong>注册成功，请同步数据</strong>',
			'uninstalled'	=>'<strong>已卸载</strong>',
		);
		if (isset($_GET['message']) && isset($messages[$_GET['message']]))
			echo '<div class="updated"><p>'.$messages[$_GET['message']].'</p></div>';
	}
	
	static function sendJsonResponse($response){
		if (!headers_sent()) {
			nocache_headers();
			header('Content-type: application/json; charset=UTF-8');
		}
		
        echo json_encode($response);
        exit;
	}
	
	//发布文章时候的同步设置
	static function syncOptions(){
		global $post;
		
		switch($post->post_status){
			case 'auto-draft':
				break;
			case 'publish':
				break;
			default:
		}
		$user = wp_get_current_user();
		$params = array(
			'template'	=>	'wordpress',
			'local_identity'=>	$user->ID,
			'signature'	=> self::buildSignature($user->ID)
		);
		try{
			echo self::getClient()->getContents('partials/sync-options', $params);
		}
		catch(Duoshuo_Exception $e){
			Duoshuo::showException($e);
		}
		echo '<p><a href="' . admin_url('admin.php?page=duoshuo-profile') . '">绑定更多社交网站</a></p>';
	}
	
	static function managePostComments($post){
		//这里应该嵌入一个iframe框
	}
	
	static function syncCron(){
		try{
			self::syncCommentsToLocal();
		}
		catch(Duoshuo_Exception $e){
		}
	}
	
	static function updateUserMeta($userId, $metaKey, $metaValue){
		if (function_exists('update_user_meta'))
			update_user_meta($userId, $metaKey, $metaValue);
		else
			update_usermeta($userId, $metaKey, $metaValue);
	}
	
	static function getUserMeta($userId, $metaKey, $single = false){
		//get_user_meta 从3.0开始有效: get_usermeta($user->ID, $blog_prefix.'capabilities', true);
		if (function_exists('get_user_meta'))
			return get_user_meta($userId, $metaKey, true);
		else
			return get_usermeta($userId, $metaKey);
	}
	
	static function actionsFilter($actions){
		/**
		 * TODO 
		$actions['ds-comments'] = '<a href="javascript:void(0);">管理评论</a>';
		 */
		return $actions;
	}
	
	static function pluginActionLinks($links, $file) {
		if (empty(self::$shortName) || empty(self::$secret) || !is_numeric(get_option('duoshuo_synchronized')))
	    	array_unshift($links, '<a href="' . admin_url('admin.php?page=duoshuo') . '">'.__('Install').'</a>');
		else
			array_unshift($links, '<a href="' . admin_url('admin.php?page=duoshuo-settings') . '">'.__('Settings').'</a>');
	    return $links;
	}
	
	static function dashboardWidget(){
		if ( !$widget_options = get_option( 'dashboard_widget_options' ) )
			$widget_options = array();
	
		if ( !isset($widget_options['dashboard_recent_comments']) )
			$widget_options['dashboard_recent_comments'] = array();
	
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['widget-recent-comments']) ) {
			$number = absint( $_POST['widget-recent-comments']['items'] );
			$widget_options['dashboard_recent_comments']['items'] = $number;
			update_option( 'dashboard_widget_options', $widget_options );
		}
	
		$number = isset( $widget_options['dashboard_recent_comments']['items'] ) ? (int) $widget_options['dashboard_recent_comments']['items'] : '';
	
		echo '<p><label for="comments-number">' . __('Number of comments to show:') . '</label>';
		echo '<input id="comments-number" name="widget-recent-comments[items]" type="text" value="' . $number . '" size="3" /></p>';
	}
}

Duoshuo::setVariables();

if(is_admin()){//在admin界面内执行的action
	register_deactivation_hook(__FILE__, array('Duoshuo', 'deactivate'));
	add_action('admin_menu', array('Duoshuo','addPages'), 10);
	add_action('admin_init', array('Duoshuo','requestHandler'));
	add_action('admin_init', array('Duoshuo','registerSettings'));
	add_action('admin_init', array('Duoshuo','initialize'));
}
else{
	add_action('init', array('Duoshuo','initialize'));
}
