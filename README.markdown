#WordPress &#8250; 说明

WordPress 3.3.1 版本 for sae
https://github.com/ElmerZhang/wp4sae.git

2012/02/17
优美的个人信息发布平台

##写在最前
欢迎。WordPress 对我来说是一个具有特殊意义的项目。大家都能为 WordPress 添砖加瓦，因此作为其中一员我十分自豪。开发者和贡献者为 WordPress 奉献了难以估量的时间，我们都在致力于让 WordPress 更加优秀。现在，感谢您也参与其中。
&#8212; Matt Mullenweg

##安装：著名的五分钟安装
* 将 WordPress 压缩包解压至一个空文件夹，并上传它。
* 在浏览器中访问 [wp-admin/install.php](wp-admin/install.php)。它将帮助您把数据库链接信息写入到 wp-config.php 文件中。
** 如果上述方法无效，也没关系，这很正常。请用文本编辑器（如写字板）手动打开 wp-config-sample.php 文件，填入数据库信息。
** 将文件另存为 wp-config.php 并上传。
** 在浏览器中访问 [wp-admin/install.php](wp-admin/install.php)。
* 在配置文件就绪之后，WordPress 会自动尝试建立数据库表。若发生错误，请检查 wp-config.php 文件中填写的信息是否准确，然后再试。若问题依然存在，请访问[中文支持论坛](http://zh-cn.forums.wordpress.org/)寻求帮助。
* <strong>若您不设置密码，请牢记生成的随机密码。</strong>若您不输入用户名，用户名将是 admin。
* 完成后，安装向导会带您到[登录页面](wp-login.php)。用刚刚设置的用户名和密码登录。若您使用随机密码，在登录后可以按照页面提示修改密码。

##升级
## 自动升级
若您正在使用 WordPress 2.7 或以上版本，您可使用内置的自动升级工具进行升级：
* 在浏览器中打开 [wp-admin/update-core.php](wp-admin/update-core.php)，按照提示操作。
* 还有别的步骤么 —— 没了！

## 手动升级
* 在升级之前，请确保备份旧有数据以及被您修改过的文件，例如 index.php。
* 删除旧版程序文件，记得备份修改过的内容。
* 上传新版程序文件。
* 在浏览器中访问 [/wp-admin/upgrade.php](wp-admin/upgrade.php)。

## 模板结构变化
如果您曾自己制作或者修改主题，可能您需要做一些修改以使模板在跨版本更新后正常工作。

##从其他内容管理系统“搬家”
WordPress 支持[导入多种系统的数据](http://codex.wordpress.org/Importing_Content)。请先按照上述步骤安装 WordPress。安装后，您可在后台使用[我们提供的导入工具](wp-admin/import.php)。

##最低系统需求

* [PHP](http://php.net/) <strong>5.2.4</strong> 或更高版本。
* [MySQL](http://www.mysql.com/) <strong>5.0</strong> 或更高版本。


## 系统推荐

* 启用 [mod_rewrite](http://httpd.apache.org/docs/2.2/mod/mod_rewrite.html) 这一 Apache 模块。
* 在您的站点设置至 [http://cn.wordpress.org](http://cn.wordpress.org/) 的链接。


##在线资源
若您遇上文档中未有提及的情况，请首先参考我们为您准备的丰富 WordPress 在线资源：
* [WordPress Codex 文档](http://codex.wordpress.org/)
** Codex 是 WordPress 的百科全书。它包含现有版本 WordPress 的海量信息资源。主要文章均包含中文译文。
* [WordPress 官方博客](http://wordpress.org/news/)
** 在这里，您将接触到 WordPress 的最新升级信息和相关新闻，建议加入收藏夹。
* [WordPress Planet ](http://planet.wordpress.org/)
** WordPress Planet 汇集了全球所有 WordPress 相关的内容。
* [WordPress 中文支持论坛](http://zh-cn.forums.wordpress.org/forum/issues)
** 如果感到束手无策，请将问题提交至中文支持论坛，它有大量的热心的用户和良好的社区氛围。无论求助还是助人，在这里您应该确保自己的问题和答案均准确细致。
* [WordPress IRC 频道](http://codex.wordpress.org/IRC)
** 同样，WordPress 也有即时的聊天室用于 WordPress 用户交流以及部分技术支持。IRC 的详细使用方法可以访问前面几个关于技术支持的站点。（[irc.freenode.net #wordpress](irc://irc.freenode.net/wordpress)）

## XML-RPC 和 Atom 接口
您可以使用诸如 [XML-RPC 支持](http://codex.wordpress.org/XML-RPC_Support)（英文）的内容。

##用电子邮件发布文章
您可以通过电子邮件发表站点更新！请前往后台的“写作”设置页面，输入相关信息和 [计划任务（Cron job）](http://en.wikipedia.org/wiki/Cron)来实现，或是让某个站点检测服务定期访问您的 wp-mail.php 的 URL。
更新很简单：使用任何邮箱发送内容到指定地址均会被 WordPress 自动发表，并以邮件主题作为文章标题，所以该"指定地址"也最好保密并专用。发表后程序将自动删除邮件。

##用户角色
WordPress 2.0 之后的版本加入了更为灵活的用户身份系统，同时移除了之前的用户等级制度。 [到 Codex 阅读关于身份和权限的更多内容](http://codex.wordpress.org/Roles_and_Capabilities)（英文）。

##最后

* 对 WordPress 有任何建议、想法、评论或发现了 bug，请加入[中文支持论坛](http://zh-cn.forums.wordpress.org/)。
* WordPress 准备了完善的插件 API 接口方便您进行扩展开发。作为开发人员，如果你有兴趣了解并加以利用，请参阅 [Codex 上的插件文档](http://codex.wordpress.org/Plugin_API)。请尽量不要更改核心代码。


##分享精神
WordPress 没有数百万的市场运作资金，也没有名人赞助。不过我们有更棒的支持，那就是您！如果您喜欢 WordPress，请将它介绍给自己的朋友，或者帮助他人安装一个 WordPress，又或者写一篇赞扬我们的文章。

WordPress 是 Michel V. 创建的 [捐赠](http://wordpress.org/donate/)。

##版权许可
WordPress 基于 [license.txt](license.txt)（英文）。

