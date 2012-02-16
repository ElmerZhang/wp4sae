<?php
require_once(dirname(__FILE__).'/../OAuth.php');
class KXHttpClient{
	
	/* Contains the last HTTP status code returned. */
	public $http_code;

	/* Contains the last API call. */
	public $url;

	/* Set timeout default. */
	public $timeout = 30;

	/* Set connect timeout. */
	public $connecttimeout = 30; 

  /* Verify SSL Cert. */
  public $ssl_verifypeer = FALSE;
  
  /* Respons format. */
  public $format = 'json';
  
  /* Decode returned json data. */
  public $decode_json = TRUE;
  
  /* Contains the last HTTP headers returned. */
  public $http_info;
  
  /* Set the useragnet. */
  public $useragent = 'KX PHPSDK API v2.0.1';
  
 
	public function get($url, $params = array())
	{
		$url .= "?".OAuthUtil::build_http_query($params);
		$response = $this->http($url,'GET');	
    	if ($this->format === 'json' && $this->decode_json) {
      		return json_decode($response);
    	}
	}

	function post($url, $params = array(), $multi = false) {
		$query = "";
		if($multi)
			$query = OAuthUtil::build_http_query_multi($params);
		else 
			$query = OAuthUtil::build_http_query($params);
	    $response = $this->http($url,'POST',$query,$multi);
	    if ($this->format === 'json' && $this->decode_json) {
	      return json_decode($response);
	    }
	    return $response;
	}
  
	
 /**
   * Make an HTTP request
   *
   * @return API results
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
		if(!is_array($response))wp_die('Error Info: '.$response->errors['http_request_failed'][0].'<br/>你的主机不被支持，请联系你的主机商重新配置主机。<br/><br/>Powered by ? <a href="http://www.qiqiboy.com/products/plugins/social-medias-connect">社交媒体连接</a>');
		$this->http_code=$response['response']['code'];
		$this->http_info=$response['response']['message'];
		$this->http_header=$response['headers'];
		return preg_replace("/(\"id\":)(\d+)/i","\${1}\"\${2}\"",$response['body']);
	}
  
}

class KXClient{
	
	public $client_id = ""; //api key
	public $client_secret = ""; //app secret
	public $redirect_uri = ".../redirect.php";//回调地址，所在域名必须与开发者注册应用时所提供的网站根域名列表或应用的站点地址（如果根域名列表没填写）的域名相匹配
  
	 /* Set up the API root URL. */
	public $host = "https://api.kaixin001.com/";
	public $authorizeURL = "http://api.kaixin001.com/oauth2/authorize";
	public $accessTokenURL = "http://api.kaixin001.com/oauth2/access_token";
	public $accessTokenURLssl = "https://api.kaixin001.com/oauth2/access_token";
	

    /*
     * Construct method
     */
    function __construct($access_token = null) { 
		global $config;
		$this->client_id=$config->APIKey;
		$this->client_secret=$config->SecretKey;
		$this->redirect_uri=$config->CALLBACK;
        $this->http = new KXHttpClient(); 
        if($access_token)
        	$this->access_token = $access_token;
    }
    

	/**
   * Get the authorize URL
   *
   * @returns a string
   */
  function getAuthorizeURL($response_type='code', $scope='user_intro create_records user_records user_forward', $state=null, $display=null) {
    $params = array(
    	'client_id' => $this->client_id,
    	'response_type' => $response_type,
    	'redirect_uri' => $this->redirect_uri,
    );
    if(!empty($scope))	$params['scope'] = $scope;
    if(!empty($state))	$params['state'] = $state;
    if(!empty($display))	$params['display'] = $display;
  	$query = OAuthUtil::build_http_query($params);
	return $this->authorizeURL . "?{$query}";  
  }

  /**
   *
   */
  function getAccessTokenFromCode($code) {
    $params = array(
    	'grant_type' => "authorization_code",
    	'code' => $code,
    	'client_id' => $this->client_id,
    	'client_secret' => $this->client_secret,
    	'redirect_uri' => $this->redirect_uri,
    );
    $request = $this->http->get($this->accessTokenURL,$params);
    return $request;
  }
  
  function getAccessTokenFromPassword($username, $password,  $scope) {
    $params = array(
    	'grant_type' => "password",
    	'username' => $username,
    	'password' => $password,
    	'client_id' => $this->client_id,
    	'client_secret' => $this->client_secret,
    	'scope' => $scope,
    );
    $request = $this->http->get($this->accessTokenURLssl,$params);
    return $request;
  }
  
  function getAccessTokenFromRefreshToken($refresh_token, $scope) {
    $params = array(
    	'grant_type' => "refresh_token",
    	'refresh_token' => $refresh_token,
    	'client_id' => $this->client_id,
    	'client_secret' => $this->client_secret,
    	'scope' => $scope,
    );
    $request = $this->http->get($this->accessTokenURL,$params);
    return $request;
  }

   
    function get($api,$params = array()){
    	$url = $this->host.$api.".".$this->http->format;
        $params['access_token'] = $this->access_token;
        return $this->http->get($url, $params);
    }
    function post($api,$params = array(),$multi=false){
    	$url = $this->host.$api.".".$this->http->format;
        $params['access_token'] = $this->access_token;
        return $this->http->post($url, $params,$multi);
    }
}
