=== WP-CodeBox ===
Contributors: Eric.Wang
Donate link: http://www.ericbess.com/ericblog/2008/03/03/wp-codebox/#donate
Tags: syntax highlighting, syntax, highlight, code, formatting ,ajax, post, posts, 
Requires at least: 2.0
Tested up to: 2.8.6
Stable tag: 1.4.3

WP-CodeBox provides clean syntax highlighting and AJAX advanced features for embedding source code within pages or posts.
It support wide range of popular languages highlighting with line numbers, code download, Copy to clipboard, 
collapse codebox,automatic keywords link to API manual and maintains formatting while copying snippets of code from the browser.

== Description ==
WP-CodeBox provides clean syntax highlighting and AJAX advanced features for embedding source code within pages or posts. 
It support wide range of popular languages highlighting with line numbers, code download, Copy to clipboard, 
collapse codebox,automatic keywords link to API manual and maintains formatting while copying snippets of code from the browser. 

It's provide simple background configuration for highlighter style/formatting customization.
Since the plugin is developing, in the future it will support more options.(CSS option,Keywords display style,Auto Caps/Nocaps,Case Sensitivity etc. )

= Basic Usage =

Wrap code blocks with `<pre lang="LANGUAGE" line="1" file="download.txt" colla="+">` and `</pre>` 

Possible Parameters:

* `lang="LANGUAGE"` - `LANGUAGE` is a [GeSHi](http://qbnz.com/highlighter/) supported language syntax.  
* `file="download.txt"` - The `file` will create a code downloading attribute.
* `line="N"` - The `N` is the starting line number.  
* `colla="+/-"` - The `+/-` will expand/collapse the codebox. 
* `line,file,colla` is optional.  

[More usage examples](http://www.ericbess.com/ericblog/2008/03/03/wp-codebox/#examples)

= About Author =
[Eric Wang](http://wordpress.org/extend/plugins/profile/ericbess)

== Installation ==

1. Upload WP-CodeBox.zip to your Wordpress plugins directory, usually `wp-content/plugins/` and unzip the file.  It will create a `wp-content/plugins/wp-codebox/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Option->Wp-CodeBox set the default setting.
1. Create a post/page that contains a code snippet following the [proper usage syntax](http://www.ericbess.com/ericblog/2008/03/03/wp-codebox/#examples).

== Frequently Asked Questions ==

When activate the plugin, popup the error:
"Fatal error: Cannot redeclare class GeSHi in ##/wp-content/plugins/wp-codebox/geshi/geshi.php on line 158".

**Answer:disactivate other Gashi based syntax highlighter plugin first.**

[Leave your FAQ](http://www.ericbess.com/ericblog/2008/03/03/wp-codebox/#respond)


== Screenshots ==

= Demo =

[Wp-CodBox Demo](http://www.ericbess.com/ericblog/2008/03/03/wp-codebox/#screenshots).

== Usage ==

**Example 1: PHP, no line numbers**

    <pre lang="php">
    <div id="foo">
    <?php
      function foo() {
        echo "Hello World!\\n";
      }
      for (\$i = 0; \$i < 10 $i++) {
        foo();
      }
    ?>
    </div>
    </pre>


**Example 2: Java, with line numbers,collapse codebox**

    <pre lang="java" line="1" colla="-">
    public class Hello {
      public static void main(String[] args) {
        System.out.println("Hello World!");
      }
    }
    </pre>

**Example 3: Ruby, with line numbers starting at 18, code downloading(ruby.txt)**

    <pre lang="ruby" line="18" file="ruby.txt">
    class Example
      def example(arg1)
        return "Hello: " + arg1.to_s
      end
    end
    </pre>

== Supported Languages ==

The following languages are supported in the `lang` attribute:

abap, actionscript, ada, apache, applescript, asm, **asp**, autoit, **bash**,
blitzbasic, bnf, **c**, c_mac, caddcl, cadlisp, cfdg, cfm, cpp-qt, **cpp**,
**csharp**, **css**, d, delphi, diff, div, dos, dot, eiffel, fortran, freebasic,
genero, gml, groovy, haskell, **html4strict**, idl, ini, inno, io, **java**,
**java5**, **javascript**, latex, lisp, lua, m68k, matlab, mirc, mpasm,
**mysql**, nsis, **objc**, ocaml-brief, ocaml, oobas, **oracle8**, pascal, per,
**perl**, php-brief, **php**, plsql, **python**, qbasic, **rails**, reg, robots,
**ruby**, sas, scheme, sdlbasic, smalltalk, smarty, **sql**, tcl, text,
thinbasic, tsql, **vb**, **vbnet**, vhdl, visualfoxpro, winbatch, **xml**, xpp,
z80

(Bold languages just highlight the more popular ones.)

= Languages HotLink =

[Actionscript](http://www.actionscript.org/),[ADA](#),[Apache Log](http://www.apache.org/) ,[AppleScript](http://www.apple.com/macosx/features/applescript/) ,[ASM](#) ,[ASP](http://www.asp.net/) ,[AutoIT](http://www.autoitscript.com/),[Backus-Naur form](http://en.wikipedia.org/wiki/Backus-Naur_form) ,[Bash](http://www.gnu.org/software/bash/bash.html) ,[BlitzBasic](http://blitzbasic.com/) ,[C](http://www.cprogramming.org/) ,[C for Macs](#) ,[C#](#) ,[C++](http://www.cplusplus.com/) ,[CAD DCL](http://www.intellicad.org/) ,[CadLisp](http://www.intellicad.org/) ,[CFDG](http://www.contextfreeart.org/wiki/) ,[ColdFusion](http://www.macromedia.com/software/coldfusion/),[CSS](http://www.w3.org/Style/) ,[Delphi](http://www.borland.com/) ,[DIV](http://div-arena.com/) ,[DOS](#) ,[Eiffel](http://eiffel.com/),[Fortran](http://en.wikipedia.org/wiki/Fortran) ,
[FreeBasic](http://www.freebasic.net/) ,[GML](http://www.gamemaker.nl/) ,[Groovy](http://groovy.codehaus.org/),[HTML](http://www.w3.org/TR/REC-html40/) ,[Inno](http://www.jrsoftware.org/isinfo.php) ,[IO](http://www.iolanguage.com/about/) ,[Java](http://java.sun.com/) ,[Java 5](http://java.sun.com/) ,[Javascript](http://www.javascript.com/),[LaTeX](http://www.latex-project.org/),[Lisp](http://www.lisp.org/),[Lua](http://www.lua.org/),[Microprocessor ASM](#) ,[mIRC](http://mirc.com/),[MySQL](http://mysql.com/),
[NSIS](http://nsis.sourceforge.net/),[Objective C](#) ,[OCaml](http://caml.inria.fr/),[OpenOffice BASIC](http://www.openoffice.org/),[Oracle 8 SQL](http://www.oracle.com/),[Pascal](#) ,[Perl](http://www.perl.com/),[PHP](http://www.php.net/),[PL/SQL](http://en.wikipedia.org/wiki/PL/SQL) ,[Python](http://www.python.org/),[Q(uick)BASIC](http://qbnz.com/),[robots.txt](http://www.robotstxt.org/wc/norobots.html),[Ruby](http://ruby-lang.org/) ,[SAS](http://en.wikipedia.org/wiki/SAS_programming_language),[Scheme](http://schemers.org/) ,[SDLBasic](http://sdlbasic.sf.net/),[Smalltalk](http://en.wikipedia.org/wiki/Smalltalk_programming_language),[Smarty](http://smarty.php.net/),[SQL](#),[T-SQL](#) ,[TCL](http://www.tcl.tk/),[thinBasic](http://www.thinbasic.com/),[Uno IDL](http://wiki.services.openoffice.org/wiki/Uno/Article/Understanding_Uno) ,[VB.NET](http://msdn.microsoft.com/),[Visual BASIC](http://msdn.microsoft.com/vbasic/) ,[Visual Fox Pro](http://msdn.microsoft.com/vfoxpro/) ,[Winbatch](http://winbatch.com/),[X++](http://msdn2.microsoft.com/en-us/library/aa867122.aspx),[XML](http://www.xml.com/),[Z80 ASM](http://en.wikipedia.org/wiki/Zilog_Z80#The_Z80_assembly_language).

== Release Notes ==

**1.0** : First internal release; Use GeSHi v1.0.7.20;

**1.0.1** : Add View Code AJAX feature;

**1.1** : Add simple background configuration for highlighter style/formatting customization;

**1.2** : css tuning and option i18n compatible;

**1.2.1** : Use GeSHi v1.0.7.21;

**1.2.2** : WP 2.5 compatible;

**1.2.2.1** : Correct small bugs;Improve the css layout to stick the code header;	Contributor:YiXia(http://www.e-xia.com).

**1.2.3** : Use GeSHi v1.0.7.22, add the keywords link to API manual option;

**1.3** : Jquery toggle, use GeSHi v1.0.8.

**1.3.2** : Localization  design.

**1.3.3** : fixed for "download" "option page". Spanish by Shakaran/www.shakaran.es