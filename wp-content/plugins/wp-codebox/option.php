<?php
//function for the Admin GUI
function cb_GUI() {
	global $WpVersion;
	$WpVersion = floatval(get_bloginfo('version'));
	if(!current_user_can('manage_options')) {
?>
		<div class="wrap">
			<h2>WP-CodeBox (V<?php print(WD_VERSION); ?>) <?php _e('Configuration','wp-codebox');?></h2>
			<br /><div style="color:#770000;"><?php _e("You are not Options Manager &amp; hence you cannot configure <strong>WP-Codebox</strong>. If you are admin, then please Logout &amp; Login again.",'wp-codebox'); ?></div><br />
		</div>
<?php 
		die(__('Access Denied','wp-codebox'));
	}
	
	if (!(get_option("cb_default_setting")=="1")){
		update_option('cb_default_setting', "1");
		delete_option('cb_plain_txt');
		update_option('cb_colla', "1");
		delete_option('cb_line');
		delete_option('cb_wrap_over');
		update_option('cb_highlight', "1");
		delete_option('cb_strict');
		update_option('cb_caps', "GESHI_CAPS_NO_CHANGE");
		update_option('cb_tab_width', "2");
		update_option('cb_keywords_link', "1");
	}
		
	
	//update the fesh option into the DB
	if ($_POST['cb_stage'] == 'process') {
		update_option('cb_plain_txt', $_POST['cb_plain_txt']);
		update_option('cb_colla', $_POST['cb_colla']);
		update_option('cb_line', $_POST['cb_line']);
		update_option('cb_wrap_over', $_POST['cb_wrap_over']);		
		update_option('cb_highlight', $_POST['cb_highlight']);
		update_option('cb_strict', $_POST['cb_strict']);		
		update_option('cb_caps', $_POST['cb_caps']);
		update_option('cb_tab_width', $_POST['cb_tab_width']);
		update_option('cb_keywords_link', $_POST['cb_keywords_link']);		
		$cbERR = "Successfully Saved Settings";
	}

	//get freshed option from DB
	$cb_plain_txt = get_option("cb_plain_txt");
	$cb_line = get_option("cb_line");
	$cb_colla = get_option("cb_colla");
	$cb_wrap_over = get_option("cb_wrap_over");
	$cb_highlight = get_option("cb_highlight");
	$cb_strict = get_option("cb_strict");
	$cb_caps = get_option("cb_caps");
	$cb_tab_width = get_option("cb_tab_width");
	$cb_keywords_link = get_option("cb_keywords_link");
	
	if(!empty($cbERR)) {
		if($WpVersion < 2) {
			$cbErrAttributes = " class=\"updated\""; 
		} else {
			$cbErrAttributes = " id=\"message\" class=\"updated fade\"";
		}
?>
		<div<?php print($cbErrAttributes); ?>><br /><strong><?php _e($cbERR,'wp-codebox'); ?></strong><br />&nbsp;</div>
<?php
	}
?>
	<div class="wrap">

	<fieldset>
	 <h2> <legend><?php _e("WP-CodeBox-Configration",'wp-codebox'); ?></legend></h2>
	 <table width="100%">
  	<tr>
    	<td width="85%">
    	<?php _e("You can configure <strong>WP-CODEBOX</strong> here. It's easy to configure highlighter style/formatting.<br />Since the plugin is developing, in the future it will support more options.<em>CSS option,Keyword display style,Auto Caps/Nocaps,Case Sensitivity etc</em>. <br /> By the way, please consider making a small donation. all donations, even the small ones, help to defray my costs and support continued development. <br />Besides, all donators get a link on the main <strong><a href=\"http://www.ericbess.com/ericblog/2008/03/03/wp-codebox/\">WP-codebox page</a></strong> and its daily 200 - 500 unique visitors.",'wp-codebox'); ?>
    	</td>
    	<td width="15%">
      	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_donations">
        <input type="hidden" name="business" value="eric.wzy@gmail.com">
        <input type="hidden" name="item_name" value="donate for supporting Wp-CodeBox, thanks a lot.">
        <input type="hidden" name="item_number" value="p134">
        <input type="hidden" name="no_shipping" value="0">
        <input type="hidden" name="logo_custom" value="http://www.ericbess.com/ericblog/wp-content/gallery/illustration/paypal.jpg">
        <input type="hidden" name="no_note" value="1">
        <input type="hidden" name="currency_code" value="USD">
        <input type="hidden" name="tax" value="0">
        <input type="hidden" name="lc" value="C2">
        <input type="hidden" name="bn" value="PP-DonationsBF">
        <input type="image" src="http://www.ericbess.com/ericblog/wp-content/gallery/illustration/paypal.jpg" border="0" name="submit" alt="PayPal,fast free and secure!¾èÔù²å¼þ£¡">
        <img alt="" border="0" src="https://www.paypal.com/zh_XC/i/scr/pixel.gif" width="1" height="1">
        </form>
      </td>
  	</tr>
	</table>
	  
	</fieldset>

	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<input type="hidden" name="cb_stage" value="process" />

	<fieldset>
	<h3><legend><?php _e('DEFAULT OPTIONS','wp-codebox'); ?></legend> </h3>
		<table width="96%" align="center" id="options">
			<tr>
				<td>
				<input type="checkbox" name="cb_plain_txt" <?php if ($cb_plain_txt) echo "checked=\"1\""; ?>/><?php _e("Hide the Function Bar",'wp-codebox'); ?><br />
				<span class="help"><?php _e("Just show the codebox only.",'wp-codebox'); ?>  </span>
				</td>
				
				<td>
				<input type="checkbox" name="cb_colla" <?php if ($cb_colla) echo "checked=\"1\""; ?>/><?php _e("Collapse Codebox",'wp-codebox'); ?><br />
				<span class="help"><?php _e('If you turn this option on, do no hide the function bar. The tag <em><strong>colla="+/-"</strong></em> will switch the attribute in the single codebox.','wp-codebox'); ?> </span>
				</td>
			</tr>

			<tr>
				<td>
				<input type="checkbox" name="cb_line" <?php if ($cb_line) echo "checked=\"1\""; ?>/><?php _e('Default show Line Number','wp-codebox'); ?><br />
				<span class="help"><?php _e('Line Number display default setting. The tag <em><strong>line="n"</strong></em> will hide the single codebox.','wp-codebox'); ?> </span>
				</td>
				
				<td>
				<input type="checkbox" name="cb_strict" <?php if ($cb_strict) echo "checked=\"1\""; ?>/> <?php _e('Strict Mode','wp-codebox'); ?><br />
				<span class="help"><?php _e('Strict Mode means that if your language is a scripting language (such as PHP), then highlighting will only be done on the code between appropriate code delimiters (eg &lt;?php ,&gt;).','wp-codebox'); ?></span>
				</td>
			</tr>
			
			<tr>
				<td>
				<select name="cb_caps" >
				<option value="<?php _e('GESHI_CAPS_UPPER','wp-codebox'); ?>"<?php if(($cb_caps=="GESHI_CAPS_UPPER")) { _e(" selected",'wp-codebox'); } ?>><?php _e('CAPS_UPPER','wp-codebox'); ?></option>
				<option value="<?php _e('GESHI_CAPS_LOWER','wp-codebox'); ?>"<?php if(($cb_caps=="GESHI_CAPS_LOWER")) { _e(" selected",'wp-codebox'); } ?>><?php _e('CAPS_LOWER','wp-codebox'); ?></option>
				<option value="<?php _e('GESHI_CAPS_NO_CHANGE','wp-codebox'); ?>"<?php if(($cb_caps=="GESHI_CAPS_NO_CHANGE")) { _e(" selected",'wp-codebox'); } ?>><?php _e('CAPS_NO_CHANGE','wp-codebox'); ?></option>
				</select> <?php _e('Auto-Caps/Nocaps','wp-codebox'); ?><br />
				<span class="help"><?php _e('capitalises or lowercases automatically certain lexics when they are styled.','wp-codebox'); ?></span>
				</td>
				
				<td>
				<input type="text" name="cb_tab_width" value= "<?php echo stripslashes($cb_tab_width); ?>" size="4" /> <?php _e('Tab Width (in spaces)','wp-codebox'); ?><br />
				<span class="help"><?php _e('Tabs in your input will be turned into this many spaces (maximum of 20).','wp-codebox'); ?></span>
				</td>
			</tr>

			<tr>
				<td>
				<input type="checkbox" name="cb_keywords_link" <?php if ($cb_keywords_link) echo "checked=\"1\""; ?>/> <?php _e('All URLs for Keywords Activated','wp-codebox'); ?> <br />
				<span class="help"><?php _e('System automatic keywords link to the API documentation inserting, this option you can enable all URL linking for keywords .','wp-codebox'); ?></span>
				</td>
				
				<td style= "display: none;">
				<input type="checkbox" name="cb_wrap_over" <?php if ($cb_wrap_over) echo "checked=\"1\""; ?>/><?php _e('Wrap Overflowing Text','wp-codebox'); ?><br />
				<span class="help"><?php _e('Lines will be wrapped when they reach the width of the screen.','wp-codebox'); ?></span>
				</td>
			</tr>
			
		</table>
	</fieldset>

  <fieldset style= "display: none;" >
  <h3><legend>Output Styles</legend></h3>
  <table class="tehTable" width="96%" align="center" cellspacing="1">
  	<tr>
  		<th><div align="left">Aspect</th>
  		<th><div align="left">Colour</th>
  		<th><div align="left">Bold</th>
  		<th><div align="left">Italic</th>
  		<th><div align="left">Case Sensitive</th>
  	</tr>
  	<tr>
  		<td>Default Colour<br /><span class="help" style="color: #0000bb;">All the text not to be highlighted</span></td>
  		<td class="inputTD"><select name="default_color">
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue" selected="selected">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="default_bold" /></td>
  		<td class="inputTD"><input type="checkbox" name="default_italic" /></td>
  		<td class="inputTD"><input type="checkbox" name="default_case_sensitive" disabled="disabled" /></td>
  	</tr>
  	<tr>
  		<td>Keywords<br /><span class="help" style="color: #bbbb00;">if, while, do etc</span></td>
  		<td class="inputTD"><select name="keyword_colors[1]">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_bold[1]" /></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_italic[1]" /></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_case_sensitive[1]" /></td>
  	</tr>
  	<tr>
  		<td>Keywords II<br /><span class="help"><span style="color: #000000; font-weight: bold;">null, void, true, false</span> etc</span></td>
  		<td class="inputTD"><select name="keyword_colors[2]">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_bold[2]" /></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_italic[2]" /></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_case_sensitive[2]" /></td>
  	</tr>
  	<tr>
  		<td>Inbuilt Functions/Classes<br /><span class="help">Functions/classes predefined by your language: <span style="color: #000066;">System, rand()</span> etc</span></td>
  		<td class="inputTD"><select name="keyword_colors[3]">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_bold[3]" /></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_italic[3]" /></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_case_sensitive[3]" /></td>
  	</tr>
  	<tr>
  		<td>Data Types<br /><span class="help">Keywords that signal a data type: <span style="color: #660000;">int, double, boolean</span> etc</span></td>
  		<td class="inputTD"><select name="keyword_colors[4]">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_bold[4]" /></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_italic[4]" /></td>
  		<td class="inputTD"><input type="checkbox" name="keyword_case_sensitive[4]" /></td>
  	</tr>
  	<tr>
  		<td>Comments<br /><span class="help" style="color: #808080; font-style: italic;">Comments in your language</span></td>
  		<td class="inputTD"><select name="comments_color">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="comments_bold" /></td>
  		<td class="inputTD"><input type="checkbox" name="comments_italic" /></td>
  		<td class="inputTD"><input type="checkbox" name="comments_case_sensitive" /><a href="#n4" class="note">4</a></td>
  	</tr>
  	<tr>
  		<td>Escaped Characters<br /><span class="help">Escaped characters in your language: <span style="color: #000066; font-weight: bold;">\n, \t, \\</span> etc</span></td>
  		<td class="inputTD"><select name="escaped_chars_color">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="escaped_chars_bold" /></td>
  		<td class="inputTD"><input type="checkbox" name="escaped_chars_italic" /></td>
  		<td class="inputTD"><input type="checkbox" name="escaped_chars_case_sensitive" disabled="disabled" /></td>
  	</tr>
  	<tr>
  		<td>Brackets<br /><span class="help">Brackets: <span style="color: #00bb00;">(, ), [, ], {, }</span></span></td>
  		<td class="inputTD"><select name="brackets_color">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="brackets_bold" /></td>
  		<td class="inputTD"><input type="checkbox" name="brackets_italic" /></td>
  		<td class="inputTD"><input type="checkbox" name="brackets_case_sensitive" disabled="disabled" /></td>
  	</tr>
  	<tr>
  		<td>Strings<br /><span class="help">Strings: <span style="color: #ff0000;">"hello!", 'hello, world!'</span></span></td>
  		<td class="inputTD"><select name="strings_color">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="strings_bold" /></td>
  		<td class="inputTD"><input type="checkbox" name="strings_italic" /></td>
  		<td class="inputTD"><input type="checkbox" name="strings_case_sensitive" disabled="disabled" /></td>
  	</tr>
  	<tr>
  		<td>Numbers<br /><span class="help">Literal numbers, including decimal numbers and powers (<span style="color: #bb00bb;">1, -1.2e6</span>)</span></td>
  		<td class="inputTD"><select name="numbers_color">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="numbers_bold" /></td>
  		<td class="inputTD"><input type="checkbox" name="numbers_italic" /></td>
  		<td class="inputTD"><input type="checkbox" name="numbers_case_sensitive" disabled="disabled" /></td>
  	</tr>
  	<tr>
  		<td>Methods and Data Fields<br /><span class="help" style="color: #006600;">Methods and data fields for class languages</span></td>
  		<td class="inputTD"><select name="methods_color">
  			<option value="DEFAULT" selected="selected">Language Default</option>
  			<option value="000000" class="black">Black</option>
  			<option value="000066" class="darkblue">Dark Blue</option>
  			<option value="660000" class="darkred">Dark Red</option>
  			<option value="006600" class="darkgreen">Dark Green</option>
  			<option value="666600" class="darkyellow">Dark Yellow</option>
  			<option value="808080" class="darkgrey">Dark Grey</option>
  			<option value="0000bb" class="blue">Blue</option>
  			<option value="bb0000" class="red">Red</option>
  			<option value="00bb00" class="green">Green</option>
  			<option value="bbbb00" class="yellow">Yellow</option>
  			<option value="bbbbbb" class="grey">Grey</option>
  			<option value="0000ff" class="lightblue">Light Blue</option>
  			<option value="ff0000" class="lightred">Light Red</option>
  			<option value="00ff00" class="lightgreen">Light Green</option>
  			<option value="ffff00" class="lightyellow">Light Yellow</option>
  			<option value="bbbbbb" class="lightgrey">Light Grey</option>
  		</select></td>
  		<td class="inputTD"><input type="checkbox" name="methods_bold" /></td>
  		<td class="inputTD"><input type="checkbox" name="methods_italic" /></td>
  		<td class="inputTD"><input type="checkbox" name="methods_case_sensitive" disabled="disabled" /></td>
  	</tr>
  </table>
  </fieldset>

	<fieldset class="submit">
		<h3><legend><?php _e('Configuration Complete','wp-codebox'); ?></legend></h3>
		<div align="center">
  		<input type="submit" value="<?php _e('Highlight Preview!','wp-codebox'); ?>" style= "display: none;"/>
  		&nbsp;
    	<input type="submit" value="<?php _e('Update Options','wp-codebox'); ?>" />
  		&nbsp;
    	<input name="reset" type="reset" value="<?php _e('Reset','wp-codebox'); ?>" />
		</div>
	</fieldset>
	</form>
	</div>
<?php
}
?>
