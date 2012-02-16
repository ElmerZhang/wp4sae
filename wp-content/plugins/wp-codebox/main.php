<?php

// function for admin head output only
function codebox_adminHeader ()
{
    global $cb_path;
    $cb_path = get_bloginfo('wpurl') . "/wp-content/plugins/wp-codebox"; //URL to the plugin directory
    $hHead .= "	<link rel=\"stylesheet\" href=\"{$cb_path}/css/admin.css\" type=\"text/css\" media=\"screen\" />\n";
    $hHead .= "	<script language=\"javascript\" type=\"text/javascript\" src=\"{$cb_path}/js/admin.js\" ></script>\n";
    print($hHead);
}
// function for enabling/disabling auto-formatting
function cb_Cond_WPTexturize ($inData)
{
    global $tOffAutoFmt;
    if ($tOffAutoFmt != 1) {
        $inData = wptexturize($inData);
    }
    return $inData;
}
// special ltrim b/c leading whitespace matters on 1st line of content
function wp_codebox_code_trim ($code)
{
    $code = preg_replace("/^\s*\n/siU", "", $code);
    $code = rtrim($code);
    return $code;
}
function wp_codebox_substitute (&$match)
{
    global $wp_codebox_token, $wp_codebox_matches;
    $i = count($wp_codebox_matches);
    $wp_codebox_matches[$i] = $match;
    return "\n\n<p>" . $wp_codebox_token . sprintf("%03d", $i) . "</p>\n\n";
}
function wp_codebox_line_numbers ($code, $start)
{
    $line_count = count(explode("\n", $code));
    $output = "<pre>";
    for ($i = 0; $i < $line_count; $i ++) {
        $output .= ($start + $i) . "\n";
    }
    $output .= "</pre>";
    return $output;
}
function wp_codebox_highlight ($match)
{
    global $wp_codebox_matches;
    $i = intval($match[1]);
    $match = $wp_codebox_matches[$i];
    return wp_codebox_highlight_geshi($match);
}
function wp_codebox_highlight_geshi ($match)
{
    global $codeid, $post;
    $codeid ++;
    //get option from DB
    $cb_plain_txt = get_option("cb_plain_txt");
    $cb_line = get_option("cb_line");
    $cb_colla = get_option("cb_colla");
    $cb_wrap_over = get_option("cb_wrap_over");
    $cb_highlight = get_option("cb_highlight");
    $cb_strict = get_option("cb_strict");
    $cb_caps = get_option("cb_caps");
    $cb_tab_width = intval(get_option("cb_tab_width"));
    $cb_keywords_link = get_option("cb_keywords_link");
    if ($match[1]) {
        $language = strtolower(trim($match[1]));
    } else {
        $language = "text";
    }
    $line = trim($match[4]);
    $file = trim($match[2]);
    $colla = trim($match[3]);
    $code = wp_codebox_code_trim($match[5]);
    $is_windowsie = wp_codebox_is_windowsie();
    $geshi = new GeSHi($code, $language);
    $geshi->enable_keyword_links($cb_keywords_link);
    $geshi->set_case_keywords($cb_caps);
    $geshi->set_tab_width($cb_tab_width);
    $geshi->enable_strict_mode($cb_strict);
    do_action_ref_array('wp_codebox_init_geshi', array(&$geshi));
    $output = "\n";
    if (! ($cb_plain_txt)) {
        $output .= "<div class=\"wp_codebox_msgheader";
        if (($cb_colla && (! ($colla == "+"))) || ($colla == "-")) {
            $output .= " wp_codebox_hide";
        }
        $output .= "\">";
        $output .= "<span class=\"right\">";
        $output .= "<sup><a href=\"http://www.ericbess.com/ericblog/2008/03/03/wp-codebox/#examples\" target=\"_blank\" title=\"WP-CodeBox HowTo?\"><span style=\"color: #99cc00\">?</span></a></sup>";
        if ($is_windowsie) {
            $output .= "<a href=\"javascript:;\" onclick=\"copycode('p" . $post->ID . "code" . $codeid . "');\">" . __('[Copy to clipboard]', 'wp-codebox') . "</a>";
        }
        /*
		$output .= "<a href=\"javascript:;\" onclick=\"toggle_collapse('p".$post->ID.$codeid."');\">[<span id=\"p".$post->ID.$codeid."_symbol\">";
		if (($cb_colla && (!($colla == "+"))) || ($colla == "-")){$output .= "+";} else {$output.= "-";}
		$output .= "</span>]</a>";
		*/
        $output .= "</span>";
        if ($file) {
            $output .= "<span class=\"left2\">" . __('Download', 'wp-codebox') . ' <a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-codebox/wp-codebox.php?p=' . $post->ID . '&amp;download=' . wp_specialchars($file) . '">' . wp_specialchars($file) . '</a>';
        } else {
            $output .= "<span class=\"left\">" . "<a href=\"javascript:;\" onclick=\"javascript:showCodeTxt('p" . $post->ID . "code" . $codeid . "'); return false;\">" . __('View Code', 'wp-codebox') . "</a> " . strtoupper($language);
        }
        $output .= "</span><div class=\"codebox_clear\"></div></div>";
    }
    $output .= "<div class=\"wp_codebox\">";
    $output .= "<table>";
    $output .= "<tr ";
    $output .= "id=\"p" . $post->ID . $codeid . "\">";
    if ($cb_line && (! ($line)))
        $line = "1";
    if (($line) && (! ($line == "n"))) {
        $output .= "<td class=\"line_numbers\">";
        $output .= wp_codebox_line_numbers($code, $line);
        $output .= "</td>";
    }
    $output .= "<td class=\"code\" id=\"p" . $post->ID . "code" . $codeid . "\">";
    $output .= $geshi->parse_code();
    $output .= "</td></tr></table></div>\n";
    return $output;
}
function wp_codebox_before_filter ($content)
{
    return preg_replace_callback("/\s*<pre(?:lang=[\"']([\w-]*)[\"']|file=[\"']([\w-]*\.?[\w-]*)[\"']|colla=[\"']([\+\-])[\"']|line=[\"'](\d*|n)[\"']|\s)+>(.*)<\/pre>\s*/siU", "wp_codebox_substitute", $content);
}
function wp_codebox_after_filter ($content)
{
    global $wp_codebox_token;
    $content = preg_replace_callback("/<p>\s*" . $wp_codebox_token . "(\d{3})\s*<\/p>/si", "wp_codebox_highlight", $content);
    return $content;
}
function wp_codebox_is_windowsie ()
{
    $agent = $_SERVER['HTTP_USER_AGENT'];
    return eregi("win", $agent) && eregi("msie", $agent) && ! eregi("opera", $agent);
}
?>