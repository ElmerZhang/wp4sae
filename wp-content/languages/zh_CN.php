<?php
/**
 * WordPress China Localization Patches Collection
 *
 * Offering users an easy access to various WordPress China-published patches.
 */

define( 'ZH_CN_L10N_OPTIONS_PAGE_MENU_SLUG', 'zh-cn-l10n-options' );


/**
 * Loads user preference
 *
 * If an option is not defined by the user, use the default value.
 *
 * @since 3.3.0
 */
function zh_cn_l10n_load_patches_states() {
	global $zh_cn_l10n_patches_enabled;
	
	$_zh_cn_l10n_preference_patches = get_option( 'zh_cn_l10n_preference_patches', false );
	
	if ( false == $_zh_cn_l10n_preference_patches ) {
		add_option( 'zh_cn_l10n_preference_patches', serialize( array() ), null, true );
		$_zh_cn_l10n_preference_patches = array();
	} else {
		$_zh_cn_l10n_preference_patches = maybe_unserialize( $_zh_cn_l10n_preference_patches );
	}
	
	$zh_cn_l10n_patches_enabled = array();
	$_zh_cn_l10n_patches_default_state = _zh_cn_l10n_info( 'patches', 'id+default' );
	foreach ( $_zh_cn_l10n_patches_default_state as $patch_default ) {
		$patch_id = $patch_default['id'];
		if ( true == @ $_zh_cn_l10n_preference_patches[ $patch_id ] || ( @ $_zh_cn_l10n_preference_patches[ $patch_id ] === null && $patch_default['state'] == true ) ) {
			$zh_cn_l10n_patches_enabled[] = $patch_id;
		}
	}
}

zh_cn_l10n_load_patches_states();

/**
 * Changes the state of a certain patch in the database
 *
 * @since 3.3.0
 */
function zh_cn_l10n_save_patch_states( $patch_id, $updated_state, $flush = false ) {
	$_zh_cn_l10n_preference_patches = unserialize( get_option( 'zh_cn_l10n_preference_patches' ) );
	$_zh_cn_l10n_preference_patches[ $patch_id ] = $updated_state;

	update_option( 'zh_cn_l10n_preference_patches', serialize( $_zh_cn_l10n_preference_patches ) );
	
	if ( $flush )
		wp_load_alloptions();
}

/**
 * Adds options page to the navigation menu
 *
 * @since 3.3.0
 */
function zh_cn_l10n_add_options_page() {
	add_options_page( '中文本地化选项', '中文本地化', 'administrator',
		ZH_CN_L10N_OPTIONS_PAGE_MENU_SLUG, 'zh_cn_l10n_options_page' );
}

/**
 * Get info in certain format
 *
 * @since 3.3.0
 */
function _zh_cn_l10n_info( $category, $type, $additional_vars = null ) {
	$patches_info = array(
		array(
			'id' 						=> 'asian-character-count',
			'name'					=> '亚洲文字字数统计',
			'description' 	=> '修正英文词数统计不包含中文的问题，在文章和页面编辑器处显示中文字数和英文词数之和。',
			'default_state'	=> true,
			'post_enable'		=> '字数统计将在您下次写文章时自动显示中文字数和英文词数之和。',
			'post_disable'	=> '谢谢使用。下次写作时将不再显示中文字数。<a href="http://cn.wordpress.org/contact/" target="_blank">报告问题？</a>'
		),
		array(
			'id'						=> 'chinese-media-embedding',
			'name'					=> '中国视频网站视频自动嵌入',
			'description' 	=> '自动通过视频页面 URL 嵌入中国视频网站的视频。',
			'default_state'	=> true,
			'post_enable'		=> '在文章中另起一段，写入当前支持的视频地址即可在文章显示时自动嵌入。',
			'post_disable'	=> '谢谢使用。您文章中的中国视频 URL 将不再被替换。<a href="http://cn.wordpress.org/contact/" target="_blank">报告问题？</a>'
		),
		array(
			'id'						=> 'chinese-administration-screens-style',
			'name'					=> '管理页面样式优化',
			'description' 	=> '微调管理页面（后台）英文默认 CSS 样式表，应用针对中文的样式优化。',
			'default_state'	=> true,
			'post_enable'		=> '在您下次访问其它管理页面时将自动应用中文样式，站点前台样式不会变化。',
			'post_disable'	=> '谢谢使用。当您下次访问页面时将不再使用优化的样式。<a href="http://cn.wordpress.org/contact/" target="_blank">报告问题？</a>'
		)
	);
	
	if ( $category == 'patches' ) {
		global $zh_cn_l10n_patches_enabled;
		
		switch ( $type ) {
			case 'all':
				$result = array();
				
				foreach ( $patches_info as $patch ) {
					if ( in_array( $patch['id'], $zh_cn_l10n_patches_enabled ) )
						$patch['enabled'] = true;
					else
						$patch['enabled'] = false;
					
					$result[] = $patch;
				}
				
				return $result;
				break;
				
			case 'id+default':
				$result = array();
				
				foreach ( $patches_info as $patch ) {
					$result[] = array( 'id'			=> $patch['id'],
											 			  'state'	=> $patch['default_state'] );
				}
				
				return $result;
				break;
			
			case 'id':
				$result = array();
				
				foreach ( $patches_info as $patch ) {
					$result[] = $patch['id'];
				}
				
				return $result;
				break;
			
			case 'disable-message':
				$patch_id = $additional_vars[0];
				
				foreach ( $patches_info as $patch ) {
					if ( $patch['id'] == $patch_id )
						return $patch['post_disable'];
				}
				
				return '';
				break;
			
			case 'enable-message':
				$patch_id = $additional_vars[0];
				
				foreach ( $patches_info as $patch ) {
					if ( $patch['id'] == $patch_id )
						return $patch['post_enable'];
				}
				
				return '';
				break;
		}
	}
}

/**
 * Options page content
 *
 * Detects if the patches we shipped are activated, and displays corresponding
 * options, e.g. activating/deactivating them.
 *
 * @since 3.3.0
 */
function zh_cn_l10n_options_page() {
	_zh_cn_l10n_handle_http_requests();
	
	?><div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div><h2 class="title">中文本地化选项</h2>
	<p>下方列出的补丁由 WordPress China 开发，您可决定是否使用下列补丁。</p>

	<?php	_zh_cn_l10n_echo_plugins(); ?>
	
</div>
<?php
}

/**
 * List all patches from WordPress China
 * 
 * This function is intended to use internally thus calling this function in
 * your work is highly unrecommended.
 *
 * @since 3.3.0
 */
function _zh_cn_l10n_echo_plugins() {
	$patches = _zh_cn_l10n_info( 'patches', 'all' );
	
	foreach ( $patches as $patch ) {
		?><h3><?php echo $patch['name']; ?></h3>
		<p><?php echo $patch['description']; ?></p>
		<?php
		if ( true == $patch['enabled'] ) {
			echo "<p class=\"description\">补丁正在工作。</p>\n";
			echo "<p><a class='button' href='" . _zh_cn_l10n_deactivate_plugin_link( $patch['id'] ) . "'>停止使用该补丁</a></p>";
		} else {
			echo "<p class=\"description\">补丁已停用。您可以重新启用它。</p>\n";
			echo "<p><a class='button-primary' href='" . _zh_cn_l10n_activate_plugin_link( $patch['id'] ) . "'>启用该补丁</a></p>";
		}
	}
		
}

/**
 * Outputs links for activating patches
 *
 * This function is intended to use internally thus calling this function in
 * your work is highly unrecommended.
 *
 * @since 3.3.0
 */
function _zh_cn_l10n_activate_plugin_link( $patch_name ) {
	return wp_nonce_url( "options-general.php?page=" . ZH_CN_L10N_OPTIONS_PAGE_MENU_SLUG . "&action=activate_patch&patch=$patch_name" );
}

/**
 * Output links for deactivating patches
 *
 * This function is intended to use internally thus calling this function in
 * your work is highly unrecommended.
 *
 * @since 3.3.0
 */
function _zh_cn_l10n_deactivate_plugin_link( $patch_name ) {
	return wp_nonce_url( "options-general.php?page=" . ZH_CN_L10N_OPTIONS_PAGE_MENU_SLUG . "&action=deactivate_patch&patch=$patch_name" );
}

/**
 * Handles HTTP requests
 *
 * Checks if user initiated a patch status change and activate/deactivate the
 * patch.
 *
 * @since 3.3.0
 */
function _zh_cn_l10n_handle_http_requests() {
	if ( isset( $_GET['action'] ) ) {
		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			echo "<div id='message' class='error'><p><strong>错误：</strong>检测到攻击尝试，因此无法修改补丁状态。请再试一次。（<code>_wpnonce</code> 安全验证未能通过）</p></div>";
			return -1;
		}

		if ( 'activate_patch' == $_GET['action'] || 'deactivate_patch' == $_GET['action'] ) {
			$patch_id = $_GET['patch'];
			$allowed_patches = _zh_cn_l10n_info( 'patches', 'id' );
			
			if ( ! in_array( $patch_id, $allowed_patches ) ) {
				echo "<div id='message' class='error'><p><strong>错误：</strong>检测到攻击尝试，因此无法修改补丁状态。请再试一次。（<code>patch</code> 变量不合法）</p></div>";
				return -1;
			}
			
			if ( 'activate_patch' == $_GET['action'] ) {
				zh_cn_l10n_save_patch_states( $patch_id, true, true );
				print( "<div id='message' class='updated'><p>补丁 $patch_id <strong>已启用</strong>。" . _zh_cn_l10n_info( 'patches', 'enable-message', array( $patch_id ) ) . "</p></div>" );
			}
			
			if ( 'deactivate_patch' == $_GET['action'] ) {
				zh_cn_l10n_save_patch_states( $patch_id, false, true );
				print( "<div id='message' class='updated'><p>补丁 $patch_id <strong>已停用</strong>。" . _zh_cn_l10n_info( 'patches', 'disable-message', array( $patch_id ) ) . "</p></div>" );
			}
			
			zh_cn_l10n_load_patches_states();			
		}
		
	}
}

/**
 * PATCH: Asian Character Count - Register Script
 *
 * This patch serves as a work-around to fix the built-in word-count.js.
 * 
 * The sum of Asian characters and English word will show up in "word count"
 * field once enabled.
 *
 * @since 3.3.0
 */
function zh_cn_l10n_patch_asian_character_count_register_script() {
	$path_to_word_count_js = WP_CONTENT_URL . '/languages/zh_CN-word-count.js';
	wp_deregister_script( 'word-count' );
	wp_register_script( 'word-count', $path_to_word_count_js, array( 'jquery' ), '20111120' );
}

/**
 * PATCH: Chinese Media Embedding - Replace URLs to media pages
 *
 * This patch serves as a work-around to fix the built-in oEmbed functionality.
 *
 * Submit more URL formats here:
 * http://cn.wordpress.org/contact/
 *
 * @since 3.3.0
 */
function zh_cn_l10n_patch_chinese_media_embedding_replace_url( $content ) {
    $schema = array('/^<p>http:\/\/v\.youku\.com\/v_show\/id_([a-z0-9_=\-]+)\.html((\?|#|&).*?)*?\s*<\/p>\s*$/im' => '<p><embed src="http://player.youku.com/player.php/sid/$1/v.swf" quality="high" width="480" height="400" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash"></embed></p>',
        '/^<p>http:\/\/www\.56\.com\/[a-z0-9]+\/v_([a-z0-9_\-]+)\.html((\?|#|&).*?)*?\s*<\/p>\s*$/im' => '<p><embed src="http://player.56.com/v_$1.swf" type="application/x-shockwave-flash" width="480" height="395" allowNetworking="all" allowScriptAccess="always"></embed></p>',
        '/^<p>http:\/\/www\.tudou\.com\/programs\/view\/([a-z0-9_\-]+)[\/]?((\?|#|&).*?)*?\s*<\/p>\s*$/im' => '<p><embed src="http://www.tudou.com/v/$1/v.swf" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" wmode="opaque" width="480" height="400"></embed></p>');

    foreach ( $schema as $pattern => $replacement ) {
        $content = preg_replace( $pattern, $replacement, $content );
    }
    
    return $content;
}

/**
 * PATCH: Chinese Administration Screens Style - enqueue stylesheet
 *
 * This patch serves as a work-around to fix the font-size and font-style.
 *
 * Submit better CSS code here:
 * http://cn.wordpress.org/contact/
 *
 * @since 3.3.0
 */
function zh_cn_l10n_patch_chinese_administration_screens_style_enqueue_stylesheet() {
	$path_to_administration_screens_css = WP_CONTENT_URL . '/languages/zh_CN-administration-screens.css';
	wp_register_style( 'zh-cn-l10n-administration-screens', $path_to_administration_screens_css, array( 'wp-admin' ), '20111120');
	wp_enqueue_style( 'zh-cn-l10n-administration-screens' );
}

/**
 * Contextual help
 *
 * @since 3.3.0
 */
function zh_cn_l10n_contextual_help_tab( $current_screen ) {
	if ( $current_screen->id == 'settings_page_' . ZH_CN_L10N_OPTIONS_PAGE_MENU_SLUG ) {
		// General contexual help
		$general  = '<p>感谢选择 WordPress China 提供的官方 WordPress 中文版本。本版本内置对 WordPress 的三个补丁，您可以在本页面决定是否使用它们。</p>';
		$general .= '<p><strong>亚洲文字字数统计</strong> - WordPress 当前内置的字数统计算法不能处理亚洲文字字符，因此中文字数无法被计算。启用该补丁来显示中文字数和英文词数之和。</p>';
		$general .= '<p><strong>中国视频网站视频自动嵌入</strong> - 通过您输入的 URL 自动嵌入优酷网、56.com 和土豆网的视频。详见左侧“中国视频嵌入”标签。</p>';
		$general .= '<p><strong>管理页面样式优化</strong> - 针对中文进行的管理页面（后台）字体、字号优化。</p>';
		//$general .= '<div style="height:60px"></div>'; // XXX vertical placeholder for other tabs
		
		$current_screen->add_help_tab( array(
			'id'      => 'zh-cn-l10n-general',
			'title'   => '概述',
			'content' => $general
		) );
		
		// Asian character count
		$asian_character_count  = '<p>WordPress 当前内置的字数统计算法不能处理亚洲文字字符，因此中文字数无法被计算。启用该补丁来显示中文字数和英文词数之和。</p>';
		$asian_character_count .= '<p><strong>工作原理</strong> - 该补丁将在运行时取消注册 WordPress 内置的 <code>word-count.js</code>，并以 <code>zh_CN-word-count.js</code> 替换。<code>zh_CN-word-count.js</code> 将先除去各种 HTML 代码，计算非 ASCII 字符的个数，并除去中文标点符号，得到中文字数，剩余字符使用原方法进行统计。和内置字数统计一样，补丁的所有步骤都在您本地的浏览器通过 JavaScript 完成，因此您的隐私不会受到威胁。为了节省资源，字数统计并非完全实时，仅在您写作的过程中几秒更新一次。</p>';
		$asian_character_count .= '<p><strong>开发信息</strong> - 这是一个临时补丁，因为在 WordPress Trac 中，这个问题已经提交，会在未来版本修复。这个补丁未压缩的开发版本代码可以在您 <code>wp-content/languages/zh_CN-word-count.dev.js</code> 找到。</p>';
		
		$current_screen->add_help_tab( array(
			'id'      => 'zh-cn-l10n-asian-character-count',
			'title'   => '亚洲文字字数统计',
			'content' => $asian_character_count
		) );
		
		// Chinese media embedding
		$chinese_media_embedding  = '<p>通过您输入的 URL 自动嵌入优酷网、56.com 和土豆网的视频。</p>';
		$chinese_media_embedding .= '<p><strong>使用方法</strong> - 请复制视频网站视频页面的网址，并以如下支持的格式加入文章。请确保视频页面的网站独立成段。请不要设置超链接。</p>';
		$chinese_media_embedding .= '<p><strong>支持格式</strong> - 请只使用形如这样的网址：<code>http://v.youku.com/v_show/id_XMjQxMjc1MDIw.html</code>、<code>http://www.56.com/u21/v_NTgxMzE4NDI.html</code> 和 <code>http://www.tudou.com/programs/view/o9tsm_CL5As/</code>。</p>';
		$chinese_media_embedding .= '<p><strong>工作原理</strong> - 在显示您的文章时，查找查找独立成段的视频 URL，并替换成视频网站提供的相应格式的嵌入代码。</p>';
		$chinese_media_embedding .= '<p><strong>开发信息</strong> - 由于中国大部分视频网站不支持 WordPress 所支持的 oEmbed 自动嵌入方式，暂时只能使用 URL 替换的方式。</p>';
		
		$current_screen->add_help_tab( array(
			'id'      => 'zh-cn-l10n-chinese-media-embedding',
			'title'   => '中国视频嵌入',
			'content' => $chinese_media_embedding
		) );
		
		// Chinese administration screens style
		$chinese_administration_screens_style  = '<p>针对中文进行的管理页面（后台）字体、字号和字型优化。</p>';
		$chinese_administration_screens_style .= '<p><strong>调整内容</strong> - 主要修改所有斜体字和原字号小于 12 点的文字。这个补丁不会修改您站点的前台样式。</p>';
		$chinese_administration_screens_style .= '<p><strong>使用方法</strong> - 启用后不需您的干预，自动修改样式。</p>';
		$current_screen->add_help_tab( array(
			'id'      => 'zh-cn-l10n-chinese-administration-screens-style',
			'title'   => '管理页面样式优化',
			'content' => $chinese_administration_screens_style
		) );
		
		// Contact sidebar
		$current_screen->set_help_sidebar(
			'<p><strong>意见、建议和问题？</strong></p>' .
			'<p><a href="http://cn.wordpress.org/contact/" target="_blank">填写表单联系我们</a></p>' .
			'<p><a href="http://zh-cn.forums.wordpress.org/" target="_blank">访问官方中文论坛</a></p>' .
			'<p><a href="http://cn.wordpress.org/" target="_blank">访问 WordPress China</a></p>'
		);
	}
	
	return $current_screen;
}

/**
 * Initializes the pointer to Help tab
 *
 * @since 3.3.0
 */
function zh_cn_l10n_pointer_enqueue( $hook_suffix ) {
	$current_screen = get_current_screen();
	if ( $current_screen->id != 'settings_page_' . ZH_CN_L10N_OPTIONS_PAGE_MENU_SLUG )
		return;
	
	$enqueue = false;

	$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

	if ( ! in_array( 'zh-cn-l10n-options-page-help-v1', $dismissed ) ) {
		$enqueue = true;
		add_action( 'admin_print_footer_scripts', '_zh_cn_l10n_options_page_pointer_print_admin_bar' );
	}

	if ( $enqueue ) {
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
	}
}

/**
 * Output the code for the Help pointer
 *
 * @since 3.3.0
 */
function _zh_cn_l10n_options_page_pointer_print_admin_bar() {
	$pointer_content  = '<h3>需要帮助？</h3>';
	$pointer_content .= '<p>点击右上方的“帮助”来查看详情以及功能用法。</p>';

?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function($) {
	$('#contextual-help-link').pointer({
		content: '<?php echo $pointer_content; ?>',
		position: {
			edge:  'top',
			align: 'right'
		},
		close: function() {
			$.post( ajaxurl, {
					pointer: 'zh-cn-l10n-options-page-help-v1',
					action: 'dismiss-wp-pointer'
			});
		}
	}).pointer('open');
});
//]]>
</script>
<?php
}

/**
 * Hooks
 *
 * Register hooks to ensure proper functionality.
 */
// Word count
if ( in_array( 'asian-character-count', $zh_cn_l10n_patches_enabled ) )
	add_filter( 'admin_footer', 'zh_cn_l10n_patch_asian_character_count_register_script' );

// Video embedding
if ( in_array( 'chinese-media-embedding', $zh_cn_l10n_patches_enabled ) )
	add_filter( 'the_content', 'zh_cn_l10n_patch_chinese_media_embedding_replace_url' );

// Administration screens style
if ( in_array( 'chinese-administration-screens-style', $zh_cn_l10n_patches_enabled ) )
	add_action( 'admin_init', 'zh_cn_l10n_patch_chinese_administration_screens_style_enqueue_stylesheet' );

// Options page-related
if ( is_admin() ) {
	add_action( 'admin_menu', 'zh_cn_l10n_add_options_page' );
	add_filter( 'current_screen', 'zh_cn_l10n_contextual_help_tab' );
}

// 'Help' pointer
add_action( 'admin_enqueue_scripts', 'zh_cn_l10n_pointer_enqueue' );

?>
