<?php
/*
Plugin Name: WP-CodeBox
Plugin URI: http://www.ericbess.com/ericblog/2008/03/03/wp-codebox/
Description: WP-CodeBox provides clean syntax highlighting and AJAX advanced features for embedding source code within pages or posts.Wrap code blocks with <code>&lt;pre lang="LANGUAGE" line="1" file="download.txt" colla="+"&gt;</code> <code>&lt;/pre&gt;</code>. The <code>LANG</code> is supported by wide range of popular languages syntax. The <code>FILE</code> will create code downloading attribute. <code>line="n"</code>is the starting line number, <code>colla="+/-"</code> will expand/collapse the codebox.
Author: Eric Wang
Version: 1.4.3
Author URI: http://www.ericbess.com/ericblog/
*/
#
#  Copyright (c) 2008 Eric Wang
#
#  This file is part of WP-CodeBox.
#
#  Wp-CodeBox is free software; you can redistribute it and/or modify it under
#  the terms of the GNU General Public License as published by the Free
#  Software Foundation; either version 2 of the License, or (at your option)
#  any later version.
#
define('CB_VERSION', "1.4.3"); // Version of the Plugin
define('CB_FILE', "wp-codebox.php"); // Plugin File Name
$tOffAutoFmt = false; // set autoFormatOff as FALSE to enable auto formatting by default
$wp_codebox_token = md5(uniqid(rand()));
require_once ("geshi/geshi.php");
// Download the code
if (isset($_GET['download'])) {
    $post = $_GET['p'];
    $download = $_GET['download'];
    $post = &get_post($post);
    $content = $post->post_content;
    if (preg_match("/\s*<pre(.*file=[\"']" . $download . "[\"']|\s.*)+>(.*)<\/pre>\s*/siU", $content, $match)) {
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$download\"");
        echo trim($match[2]);
        exit();
    }
}
### Create Text Domain For Translations
add_action('init', 'wp_codebox_textdomain');
function wp_codebox_textdomain ()
{
    if (! function_exists('wp_print_styles')) {
        load_plugin_textdomain('wp-codebox', 'wp-content/plugins/wp-codebox');
    } else {
        load_plugin_textdomain('wp-codebox', false, 'wp-codebox');
    }
}
include_once ("main.php");
include_once ("option.php");
//function for adding the sub-panel in the Options panel
function cb_Menu ()
{
    if (function_exists('add_options_page')) {
        add_options_page(__('WP-CodeBox Options', 'wp-codebox'), __('WP-CodeBox', 'wp-codebox'), 'level_8', basename(__FILE__), 'cb_GUI');
    }
}
// output to the <head> section of the page
//add_action('wp_head', 'codebox_header');
add_action('wp_print_scripts', 'Codebox_ScriptsAction');
function Codebox_ScriptsAction ()
{
    $cb_path = WP_PLUGIN_DIR . "wp-codebox/"; //URL to the plugin directory
    if (! is_admin()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('codebox', get_bloginfo ( 'wpurl' ) . '/wp-content/plugins/wp-codebox/js/codebox.js', array('jquery'), '0.1');
    }
}
add_action('wp_print_styles', 'Codebox_StylesAction');
function Codebox_StylesAction ()
{
    $cb_path = WP_PLUGIN_DIR . "wp-codebox/"; //URL to the plugin directory
    if (! is_admin()) {
        wp_enqueue_style('codebox', get_bloginfo ( 'wpurl' ) . '/wp-content/plugins/wp-codebox/css/codebox.css', array(), '0.1', 'screen');
    }
}
/**
 * Adds an action link to the plugins page
 */
add_action('plugin_action_links_' . plugin_basename(__FILE__), 'wp_codebox_plugin_actions');
function wp_codebox_plugin_actions ($links)
{
    $new_links = array();
    $new_links[] = '<a href="options-general.php?page=wp-codebox.php">' . __('Settings', 'wp-codebox') . '</a>';
    return array_merge($new_links, $links);
}
/**
 * Add FAQ and support information
 */
add_filter('plugin_row_meta', 'wp_codebox_plugin_links', 10, 2);
function wp_codebox_plugin_links ($links, $file)
{
    if ($file == plugin_basename(__FILE__)) {
        $links[] = '<a href="http://www.ericbess.com/paypal/paypal.php">' . __('Donate', 'wp-codebox') . '</a>';
    }
    return $links;
}
// output to the <head> section of admin in case of preview
// add_action('admin_head', 'codebox_header');
/*
// disable wptexturize filter
remove_filter('the_excerpt', 'wptexturize');
remove_filter('the_content', 'wptexturize');
remove_filter('comment_text', 'wptexturize');

// add conditional wptexturize
add_filter('the_excerpt', 'cb_Cond_WPTexturize');
add_filter('the_content', 'cb_Cond_WPTexturize');
add_filter('comment_text', 'cb_Cond_WPTexturize');
*/
// We want to run before other filters; hence, a priority of 0 was chosen.
// The lower the number, the higher the priority.  10 is the default and
// several formatting filters run at or around 6.
add_filter('the_content', 'wp_codebox_before_filter', 0);
add_filter('the_excerpt', 'wp_codebox_before_filter', 0);
add_filter('comment_text', 'wp_codebox_before_filter', 0);
// We want to run after other filters; hence, a priority of 99.
add_filter('the_content', 'wp_codebox_after_filter', 99);
add_filter('the_excerpt', 'wp_codebox_after_filter', 99);
add_filter('comment_text', 'wp_codebox_after_filter', 99);
// add the sub-panel under the OPTIONS panel
add_action('admin_menu', 'cb_Menu');
?>
