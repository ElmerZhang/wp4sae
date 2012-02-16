<?php
/*
Plugin Name: Social Medias Connect
Author: qiqiboy
Author URI: http://www.qiqiboy.com/
Plugin URI: http://www.qiqiboy.com/plugins/social-medias-connect/
Description: 为WordPress提供社交媒体网站连接同步功能, 支持微博账号绑定
Version: 1.7.4
*/
define('SMC_VERSION', '1.7.4');
define('PLUGIN_AUTHOR_EMAIL', 'imqiqiboy#gmail.com');
require_once(dirname(__FILE__).'/function.php');
require_once(dirname(__FILE__).'/widgets.php');
register_activation_hook( __FILE__, 'smc__install');
?>
