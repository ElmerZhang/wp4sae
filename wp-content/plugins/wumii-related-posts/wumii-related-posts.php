<?php
/*
Plugin Name: 无觅相关文章插件
Plugin URI: http://wordpress.org/extend/plugins/wumii-related-posts/
Author: Wumii Team
Version: 1.0.5.5
Author URI: http://www.wumii.com
Description: 利用数据挖掘的技术，智能匹配相关文章，并以图片形式展示。
 
Copyright 2010 wumii.com (email: team[at]wumii.com)
*/

if (!class_exists('WumiiRelatedPosts')) {
    class WumiiRelatedPosts {
        /*
         * !!! You must do the two things before you try to do local test with this plugin:
         * 1. turn on WUMII_DEBUG mode
         * 2. change WUMII_SERVER to "http://{computer-name}:8080"
         *    {computer-name} is the name of computer which running a local test wumii web server.
         */
        const WUMII_DEBUG = false;
        const WUMII_SERVER = 'http://widget.wumii.com';
        
        const VERSION = '1.0.5.5';
        const ADMIN_OPTION_NAME = 'wumii_admin_options';
        const PLUGIN_PATH = '/wp-content/plugins/wumii-related-posts';
        
        // admin option names
        const NUM_POSTS = 'num_posts';
        const SINGLE_PAGE_ONLY = 'single_page_only';
        const DISPLAY_IN_FEED = 'display_in_feed';
        const ENABLE_CUSTOM_POS = 'enable_custom_pos';
        const SCRIPT_IN_FOOTER = 'script_in_footer';
        
        // this containter is a stack and its size is 1.
        const NOTIFY_MESSAGE_CONTAINER = 'wumii_notify_message_container';
        
        const VERIFICATION_CODE = 'wumii_verification_code';
        
        private $sitePrefix;
        
        private $adminOptions;
        private $excludePostIds = array();
        private $lastRssRequestFailedTime;
        private $canFilledRssContentPostIds = array();
        
        // PHP 5 style constructor
        function __construct() {
            $this->WumiiRelatedPosts();
        }
        
        // treated as regular method since PHP 5.3.3
        // PHP 4 style constructor
        function WumiiRelatedPosts() {
            $this->resetAdminOptions();
            
            $persistentOptions = get_option(self::ADMIN_OPTION_NAME);
            if (is_array($persistentOptions)) {
                foreach ($this->adminOptions as $key => $value) {
                    if (array_key_exists($key, $persistentOptions)) {
                        $this->adminOptions[$key] = $persistentOptions[$key];
                    }
                }
            }
            
            // get_bloginfo('url') deprecated since WordPress 3.0.0.
            $this->sitePrefix = function_exists('home_url') ? home_url() : get_bloginfo('url');
        }
        
        function getOrigTitle() {
            global $post;
            return isset($post->post_title) ? $post->post_title : '';
        }
        
        function showNotifyMessage() {
            $notifyMessage = $this->popNotifyMessage();
            if ($notifyMessage) {
                $this->printHtmlNotifyMessage(array($notifyMessage));
            }
        }
        
        function registerOptionsPage () {
            add_options_page('无觅相关文章插件', '无觅', 'activate_plugins', basename(__FILE__), array($this, 'printAdminPage'));
        }
        
        function addPluginActionLinks($links, $file) {
            if ($file == plugin_basename(__FILE__)) {
                $links[] = '<a href="options-general.php?page=wumii-related-posts.php">' . __('Settings') . '</a>';
            }
            return $links;
        }
        
        function addToRssContent($content) {
            $newContent = $content;
            if ($this->getAdminOption(self::DISPLAY_IN_FEED) && is_feed()) {
                global $post;
                
                // 1. Both "rss excerpt mode" and "rss full text mode" will call the_excerpt_rss()
                //    function first to generate feed description.
                // 2. "the_excerpt_rss" apply filters process:
                //         (1. Only run when empty(post->excerpt) && post_password_required(post))
                //            the_content (Get the whole content and then extract excerpt.
                //                      We shouldn't add related items here since the content will
                //                      be processed by wp_trim_excerpt() later) ->
                //         (2)
                //            the_excerpt_rss (Here will add our related items content).
                // 3. "rss full text mode" applies the filters on "the_content" hook again after
                //    the_excerpt_rss() to generate the feed content. However this time we should
                //    add related items content.
                // 4. Because wordpress 2.5.x don't have function post_password_required and we have
                //    to do a lot to check if the current user is admin, we don't show rss related items
                //    if post excerpt is empty and it's a password required post.
                if (!has_excerpt() && !in_array($post->ID, $this->canFilledRssContentPostIds)) {
                    $this->canFilledRssContentPostIds[] = $post->ID;
                } else {
                    $relatedItemsHtml = $this->getRelatedItemsHtml();
                    if ($relatedItemsHtml) {
                        $newContent .= $relatedItemsHtml;
                    }
                }
            }
            
            return $newContent;
        }
        
        private function getRelatedItemsHtml() {
            // Don't do anything for 30 seconds if last request failed.
            if (isset($this->lastRssRequestFailedTime) &&
                time() - $this->lastRssRequestFailedTime < 30) {
                return;
            }
            
            $encodedUrl = urlencode(get_permalink());
            $encodedTitle = urlencode($this->getOrigTitle());
            $num = $this->getAdminOption(self::NUM_POSTS);
            $encodedSitePrefix = urlencode($this->sitePrefix);
            
            global $wp_version;
            $path = "/ext/relatedItemsRss.htm?type=1&url=$encodedUrl&title=$encodedTitle&num=$num&sitePrefix=$encodedSitePrefix&mode=3&v=" . self::VERSION . "&pf=WordPress$wp_version";
            $relatedItemsHtml = $this->httpGet(self::WUMII_SERVER, $path);
            
            if ($relatedItemsHtml) {
                return $relatedItemsHtml;
            } else {
                $this->lastRssRequestFailedTime = time();
            }
        }
        
        private function httpGet($server, $path) {
            global $wp_version;
            $response = '';
            if (function_exists('wp_remote_get')) {    // wp_remote_get function was added in WordPress 2.7
                $addr = $server . $path;
                
                // Default: method: GET, timeout: 5s, redirection: 5, httpversion: 1.0, blocking: true, body: null, cookies: array()
                $args = array(
                    'user-agent' => apply_filters('http_headers_useragent', 'WordPress/' . $wp_version . '; ' . $_SERVER['HTTP_USER_AGENT']),
                    // TODO: use batch fetch related items instead of lower the timeout.
                    'timeout' => 1
                );
                $raw_response = wp_remote_get($addr, $args);
                
                if (!is_wp_error($raw_response) && 200 == $raw_response['response']['code']) {
                    $response = trim($raw_response['body']);
                }
            }
            
            // For:
            // 1. WordPress version < 2.7
            // 2. For some reason the client php is misconfigurated, the wp_remote_get function can not work properly.
            //    Currently know wp_remote_get($url, $args) will crash if wordpress do request with WP_Http_ExtHTTP(in /wp-includes/http.php) and the PECL extension is not bundled with PHP.
            // (empty($response) === true) if match one of the above reasons.
            if (empty($response) && function_exists('fsockopen')) {
                $urlComponents = parse_url($server);
                $host = $urlComponents['host'] . ($urlComponents['port'] ? ':' . $urlComponents['port'] : '');
                
                $http_request  = "GET $path HTTP/1.0\r\n";
                $http_request .= "Host: $host\r\n";
                $http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
                $http_request .= 'User-Agent: WordPress/' . $wp_version . '; ' . $_SERVER['HTTP_USER_AGENT'] . "\r\n\r\n";
                
                $answer = '';
                // The connection timeout is 1 second.
                // TODO: recover this to 5s and remove stream_set_timeout() setting when we support betch fetch related items.
                if( false != ($fs = @fsockopen($host, 80, $errno, $errstr, 1)) && is_resource($fs)) {
                    // The timeout for reading data over the socket is 1 second.
                    stream_set_timeout($fs, 1);
                    
                    fwrite($fs, $http_request);
            
                    while (!feof($fs)) {
                        $answer .= fgets($fs, 1160); // One TCP-IP packet
                    }
                    fclose($fs);
                    
                    $answer = explode("\r\n\r\n", $answer, 2);
                    
                    // Response Header: $answer[0]
                    // Response Content: $answer[1]
                    if (preg_match('#HTTP/.*? 200#', $answer[0])) {
                        $response = trim($answer[1]);
                    }
                }
            }
            
            return $response;
        }
        
        function addWumiiContent($content) {
            $newContent = $content;
            if ($this->canDisplayWumiiContent()) {
                $escapedUrl = $this->htmlEscape(get_permalink());
                $escapedTitle = $this->htmlEscape($this->getOrigTitle());
                $escapedPic = $this->htmlEscape($this->getPostThumbnailSrc());
                
                // The first line in 'WUMII_HOOK' must be an empty line. Because some blogs use 'Embeds'(http://codex.wordpress.org/Embeds)
                // in the post content and the embed must be on its own line.
                // For example, if the 'embed' happen to add before our wumii code,
                // then we have to make sure wumii code doesn't follow that within the same line.
                $newContent .= <<<WUMII_HOOK

<div class="wumii-hook">
    <input type="hidden" name="wurl" value="$escapedUrl" />
    <input type="hidden" name="wtitle" value="$escapedTitle" />
    <input type="hidden" name="wpic" value="$escapedPic" />
</div>
WUMII_HOOK;
            }

            global $wp_query;
            if ($wp_query->current_post + 1 == $wp_query->post_count) {
                $newContent = $this->addScriptInPage($newContent);
            }

            return $newContent;
        }
        
        function echoWumiiScript() {
            echo $this->createWumiiScript();
        }
        
        function echoVerificationMeta() {
            $code = $this->getVerificationCode();
            if ($code) {
                echo "<meta name='wumiiVerification' content='$code' />\n";
            }
        }
        
        private function addScriptInPage($content) {
            if ($this->getAdminOption(self::SCRIPT_IN_FOOTER)) {
                add_action('wp_footer', array($this, 'echoWumiiScript'));
                return $content;
            } else {
                return $content . $this->createWumiiScript();
            }
        }
        
        private function createWumiiScript() {
            $enableCustomPos = $this->getAdminOption(self::ENABLE_CUSTOM_POS) ? 'true' : 'false';
            $numPosts = $this->getAdminOption(self::NUM_POSTS);
            
            $server = self::WUMII_SERVER;
            
            $script = '';
            if (self::WUMII_DEBUG) {
                $script = "<script type='text/javascript'>var wumiiDebugServer = '$server';</script>";
            }
            
            global $wp_version;
            $params = array(
                'num' => $numPosts,
                'mode' => 3,    // Use the DisplayMode.AUTO as default
                'displayInFeed' => $this->getAdminOption(self::DISPLAY_IN_FEED),    // 1=true, 0=false
                'version' => self::VERSION,
                'pf' => 'WordPress' . $wp_version
            );
            $queryParams = '';
            foreach ($params as $name => $value) {
                $value = urlencode($value);
                $queryParams .= "&$name=$value";
            }
            
            // Do not break the following html code into lines between each html tag.
            // One default filter in wordpress will replace "\n" with "<br />" between html tags.
            $script .= <<<WUMII_SCRIPT

<p style="margin:0;padding:0;height:1px;overflow:hidden;">
    <script type="text/javascript"><!--
        var wumiiSitePrefix = "$this->sitePrefix";
        var wumiiEnableCustomPos = $enableCustomPos;
        var wumiiParams = "$queryParams";
    //--></script><script type="text/javascript" src="$server/ext/relatedItemsWidget.htm"></script><a href="http://www.wumii.com/widget/relatedItems.htm" style="border:0;"><img src="http://static.wumii.com/images/pixel.png" alt="无觅相关文章插件，快速提升流量" style="border:0;padding:0;margin:0;" /></a>
</p>
WUMII_SCRIPT;

            return $script;
        }
        
        private function htmlEscape($str) {
            return htmlspecialchars(html_entity_decode($str, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        }
        
        private function getPostThumbnailSrc() {
            // function 'get_post_thumbnail_id' need blog support
            if (!function_exists('get_post_thumbnail_id')) {
                return;
            }
            
            $image_info = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            if ($image_info) {
                return $image_info[0];
            }
        }
        
        private function canDisplayWumiiContent() {
            // We can identify the live-blogging post by checking if Live Blogging plugin is activated and the shortcode in the content.
            // The related items will show in such live blogging post only one time generally,
            // but if the theme call the filter on 'the_content' hook or run another 'The Loop' to fetch the post content using in some other cases before to display,
            // the related items will not show.
            // In brief, the related items show one time at most in each post.
            global $post;
            if (array_key_exists($post->ID, $this->excludePostIds)) {
                return false;
            }
            
            global $shortcode_tags; // Container for storing shortcode tags and their hook to call for the shortcode
            if (!empty($shortcode_tags) && is_array($shortcode_tags)
                    && array_key_exists('liveblog', $shortcode_tags) && strpos(get_the_content(), '[liveblog]') !== false) {
                $this->excludePostIds[$post->ID] = 1;
            }
            
            return get_post_status($post->ID) == 'publish' &&
                   get_post_type() == 'post' && // In some pages e.g. "attachment page" should not display related items
                   empty($post->post_password) &&
                   !is_preview() && // When create a new post and press the preview button before publish it,
                                    // the post's permalink is not the correct form as the setting in "Permalink".
                                    // We have to prevent the related items displaying in these pages.
                   !is_feed() &&
                   !is_page() && // In some pages e.g. "about me" also should not display.
                   (is_single() || !$this->getAdminOption(self::SINGLE_PAGE_ONLY));
        }
        
        private function resetAdminOptions () {
            $this->adminOptions = array(
                self::NUM_POSTS => 4,
                self::SINGLE_PAGE_ONLY => true,
                self::DISPLAY_IN_FEED => true,
                self::ENABLE_CUSTOM_POS => false,
                self::SCRIPT_IN_FOOTER => true);
        }
        
        private function popNotifyMessage() {
            $notifyMessage = get_option(self::NOTIFY_MESSAGE_CONTAINER);
            if ($notifyMessage) {
                update_option(self::NOTIFY_MESSAGE_CONTAINER, '');
            }
            return $notifyMessage;
        }
        
        // This function is always executed by one user(admin).So no synchronization problem may occur.
        private function pushNotifyMessage($message, $type = 'updated') {
            if ($legacyMessage = get_option(self::NOTIFY_MESSAGE_CONTAINER)) {
                echo 'NOTIFY_MESSAGE_CONTAINER is not empty, legacy message: ' . $legacyMessage->getMessage();
                return false;
            }
            
            update_option(self::NOTIFY_MESSAGE_CONTAINER, new Wumii_Notify_Message($message, $type));
        }
        
        private function setVerificationCode($code) {
            update_option(self::VERIFICATION_CODE, $code);
        }
        
        private function getVerificationCode() {
            return get_option(self::VERIFICATION_CODE);
        }
        
        private function saveAdminOptions() {
            update_option(self::ADMIN_OPTION_NAME, $this->adminOptions);
        }
        
        private function getAdminOption($key) {
            return $this->adminOptions[$key];
        }
        
        // echo true will be 1, echo false will be 0
        // But echo !true will be null. So it have to strictly convert false to '0'.
        private function getBooleanInStr($boolean) {
            return $boolean ? '1' : '0';
        }
        
        private function addReplacementsForCheckboxState($replacementsArr, $checkboxOptionNames) {
            if (!is_array($checkboxOptionNames)) {
                return false;
            }
            
            foreach ($checkboxOptionNames as $optionName) {
                $checkState = $this->getAdminOption($optionName);
                $replacementsArr[$optionName . '_checked_' . $this->getBooleanInStr($checkState)] = 'checked="checked"';
                $replacementsArr[$optionName . '_checked_' . $this->getBooleanInStr(!$checkState)] = '';
            }
            
            return $replacementsArr;
        }
        
        private function printHtmlAdminOptionsPage() {
            $adminOptionTemplate = new Wumii_Template('adminOptionsPanel.html');
            
            $replacements = array('request_uri' => $_SERVER["REQUEST_URI"]);
            $replacements = $this->addReplacementsForCheckboxState($replacements,
                                                                    array(self::SINGLE_PAGE_ONLY,
                                                                          self::DISPLAY_IN_FEED,
                                                                          self::ENABLE_CUSTOM_POS,
                                                                          self::SCRIPT_IN_FOOTER));
            
            $numPost = $this->getAdminOption(self::NUM_POSTS);
            // We allow the num of related items from 1 to 12.
            for ($i = 1; $i <= 12; $i++) {
                if ($i == $numPost) {
                    $replaceStr = 'selected="selected"';
                } else {
                    $replaceStr = '';
                }
                $replacements['num_posts_selected_' . $i] = $replaceStr;
            }
            $replacements['verification_code'] = $this->getVerificationCode();
            
            $adminOptionTemplate->addReplacements($replacements);
            
            echo $adminOptionTemplate->render();
        }
        
        
        function printAdminPage() {
            $OptionsUpdatedMessage = array();
            if (array_key_exists('add_verification_meta', $_POST)) {
                
                // WARNING: This check is not reliable if the theme doesn't call get_header() to fetch the contents of header.php.
                // REASON: We don't know in which file of the theme call the function get_header(), although in almost every case themes will call it.
                if ($this->isHookFuncInTemplate('header.php', 'wp_head')) {
                    $code = $_POST[self::VERIFICATION_CODE];
                    $this->setVerificationCode($code);
                    if ($code) {
                        $OptionsUpdatedMessage[] = new Wumii_Notify_Message('已成功添加认证代码，现在请回到<a href="http://www.wumii.com/site/index.htm" target="_blank">无觅网站管理中心</a>，点击“验证”按钮即可完成博客认证。');
                    }
                } else {
                    $OptionsUpdatedMessage[] = new Wumii_Notify_Message('此主题可能不支持将代码嵌入到header区域，请使用其他方法进行博客认证。', 'error');
                }
            } else {
                if (array_key_exists('update_wumii_settings', $_POST)) {
                    foreach ($this->adminOptions as $key => $value) {
                        if (array_key_exists($key, $_POST)) {
                            $this->adminOptions[$key] = $_POST[$key];
                        }
                    }
                    $OptionsUpdatedMessage[] = new Wumii_Notify_Message('设置已更新.');
                } else if (array_key_exists('reset_wumii_settings', $_POST)) {
                    $this->resetAdminOptions();
                    $OptionsUpdatedMessage[] = new Wumii_Notify_Message('设置已重置.');
                }
                
                if ($this->adminOptions[self::SCRIPT_IN_FOOTER]
                        // The wp_footer action is theme-dependent.
                        // If the theme-defined file footer.php doesn't call wp_footer(), the action will not fire.
                        && !$this->isScriptInFooterSupported()) {
                    $OptionsUpdatedMessage[] = new Wumii_Notify_Message('正在使用的主题可能不支持将加载脚本置于文档末尾， 建议不要开启此功能。', 'error');
                }
                
                $this->saveAdminOptions();
            }
            $this->printHtmlNotifyMessage($OptionsUpdatedMessage);
            $this->printHtmlAdminOptionsPage();
        }
        
        private function printHtmlNotifyMessage($notifyMessages) {
            if (empty($notifyMessages) || !is_array($notifyMessages)) {
                return false;
            }
            
            foreach($notifyMessages as $msg) {
                $type = $msg->getType();
                $message = $msg->getMessage();
                echo "<div class='$type'><p>$message</p></div>";
            }
        }
        
        private function isHookFuncInTemplate($template, $func) {
            global $wp_version;
            
            $location = '';
            if (strcmp($wp_version, '2.7.0') >= 0) {
                // Only WordPress >= 2.7.0 support footer.php located in style sheet path
                // and it's the first path being checked in wordpress's source code.
                $probableLocation = get_stylesheet_directory() . '/' . $template;
                if (file_exists($probableLocation)) {
                    $location = $probableLocation;
                }
            }
            
            if (!$location) {
                $probableLocation = get_template_directory() . '/' . $template;
                if (file_exists($probableLocation)) {
                    $location = $probableLocation;
                }
            }
            
            // this template doesn't include in current theme and wordpress will use the default template instead.
            // Hook function must exist in the default template
            if (!$location) {
                return true;
            }
            $contents = file_get_contents($location);
            return $contents !== false ? (strpos($contents, $func . '()') !== false) : false;
        }
        
        // WARNING: This function is not reliable if theme doesn't call get_footer() to fetch the contents of footer.php.
        // REASON: We don't know in which file of the theme call the function get_footer(), although in almost every case themes will call it.
        private function isScriptInFooterSupported() {
            return $this->isHookFuncInTemplate('footer.php', 'wp_footer');
        }
        
        function checkFooterScriptSupportedByTheme($theme) {
            if ($this->getAdminOption(self::SCRIPT_IN_FOOTER) && !$this->isScriptInFooterSupported()) {
                $this->pushNotifyMessage('此主题不支持将<strong>无觅相关文章脚本</strong>置于文档末尾, 可能会导致相关文章无法显示, 建议关闭此功能.', 'error');
            }
        }
        
        function doActivation() {
            // now script in footer is the default setting and we need to check if it supported when activation.
            if (!$this->isScriptInFooterSupported()) {
                $this->adminOptions[self::SCRIPT_IN_FOOTER] = false;
                $this->saveAdminOptions();
            }
            $this->pushNotifyMessage('<strong>无觅相关文章插件: </strong>如果您的博客装有缓存(cache)插件, 请在首次启用插件时清空缓存. 另外, 启用插件后无觅爬虫会立刻去获取贵站的文章信息, 这时切记不要停掉插件, 以免影响文章的相关性.装完可来<strong><a href="http://www.wumii.com/site/index.htm" target="_blank">无觅网站管理中心</a></strong>查看收录状况.');
        }
        
        function finalize() {
            delete_option(self::ADMIN_OPTION_NAME);
            delete_option(self::NOTIFY_MESSAGE_CONTAINER);
            delete_option(self::VERIFICATION_CODE);
        }
    }
    
    class Wumii_Notify_Message {
        private $message;
        private $type;
        
        function __construct($message, $type = 'updated') {
            $this->Wumii_Notify_Message($message, $type);
        }
        
        function Wumii_Notify_Message($message, $type = 'updated') {
            if ($type != 'error') {
                $type = 'updated';
            }
            
            $this->message = $message;
            $this->type = $type;
        }
        
        function getMessage() {
            return $this->message;
        }
        
        function getType() {
            return $this->type;
        }
    }
    
    class Wumii_Template {
        private $htmlStrTemplate;
        private $keyValuePair = array();
        
        // PHP 5 Constructors
        function __construct($templateName) {
            $this->Wumii_Template($templateName);
        }
        
        // treated as regular method since PHP 5.3.3
        function Wumii_Template($templateName) {
            $this->htmlStrTemplate = file_get_contents($this->wumii_get_config_file_path() . WumiiRelatedPosts::PLUGIN_PATH . '/templates/' . $templateName);
        }
        
        function addReplacements($keyValuePair) {
            foreach ($keyValuePair as $key => $value) {
                $this->keyValuePair[$this->getReplacementStr($key)] = $value;
            }
        }
        
        function render() {
            return str_replace(array_keys($this->keyValuePair), array_values($this->keyValuePair), $this->htmlStrTemplate);
        }
        
        private function getReplacementStr($key) {
            return '{{' . $key . '}}';
        }
        
        private function wumii_get_config_file_path() {
            $pathname = $_SERVER['SCRIPT_FILENAME'];
            preg_match("/^(.+)wp-admin/Ui", $pathname, $matches);
            // Here if $matches[0] == 'C:/workspace/www/wordpress/wordpress/wp-admin', than matches[1] == 'C:/workspace/www/wordpress/wordpress/'.
            return $matches[1];
        }
    }
    
    $wumii_incompatible_plugins_in_rss = array('ozh-better-feed/wp_ozh_betterfeed.php',
                                               'rejected-wp-keyword-link-rejected/wp_keywordlink.php');
    
    $wumii_incompatible_plugins_in_content = array('markdown-for-wordpress-and-bbpress/markdown.php');
    
    function wumii_has_incompatible_plugins($incompatiblePlugins = array()) {
        foreach($incompatiblePlugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return true;
            }
        }
        return false;
    }
    
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    $wumii_related_posts = new WumiiRelatedPosts();
    
    add_action('wp_head', array($wumii_related_posts, 'echoVerificationMeta'));
    
    add_action('admin_notices', array($wumii_related_posts, 'showNotifyMessage'));
    add_action('admin_menu', array($wumii_related_posts, 'registerOptionsPage'));
    //  We need to place our content as near to the front as possible, so set the priority 1.
    add_action('the_content', array($wumii_related_posts, 'addWumiiContent'),
                   wumii_has_incompatible_plugins($wumii_incompatible_plugins_in_content) ? 99999 : 1);
    add_action('switch_theme', array($wumii_related_posts, 'checkFooterScriptSupportedByTheme'));
    
    // function add_filter($tag, $function_to_add, $priority, $num_accepted_args)
    add_filter('plugin_action_links', array($wumii_related_posts, 'addPluginActionLinks'), 10, 2);
    
    add_filter('the_excerpt_rss', array($wumii_related_posts, 'addToRssContent'));
    
    // In order to avoid some plugins removing or modifing our related items in rss, we try to delay to add our content as late as possible.
    // Default priority is 10.
    add_filter('the_content', array($wumii_related_posts, 'addToRssContent'),
                   wumii_has_incompatible_plugins($wumii_incompatible_plugins_in_rss) ? 99999 : 10);
    
    register_activation_hook(__FILE__, array($wumii_related_posts, 'doActivation'));
    register_deactivation_hook(__FILE__, array($wumii_related_posts, 'finalize'));
} else {
    function classConflictException() {
        echo '<div class="error"><p><strong>插件冲突。</strong>您的博客正在运行一个与“无觅相关文章插件”定义了相同类名的插件，只有在关闭冲突插件以后“无觅相关文章插件”才能正常启用。</p></div>';
    }
    add_action('admin_notices', 'classConflictException');
}
?>