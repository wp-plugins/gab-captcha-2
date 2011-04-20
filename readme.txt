=== Gab Captcha 2 ===
Contributors: GabSoftware
Donate link: http://www.gabsoftware.com/donate/
Tags: comments, spam, captcha, turing, test
Requires at least: 3.0.0
Tested up to: 3.1.1
Stable tag: 1.0.5

Gab Captcha 2 is an efficient and simple captcha plugin for Wordpress comments.

== Description ==

<p>
Gab Captcha 2 is an efficient and simple captcha plugin for Wordpress comments.
</p>

<p>
It adds an easy turing test before each comment form. The turing test consist in typing the characters that appear emphasized and red in a text field. The plugin will add an entry in your Wordpress administration area to let you configure some options.
</p>

<p>
Gab Captcha 2 currently comes in two languages: English (default) and French. You are welcome to propose your own translation or to update existing ones, especially the English one as I am not a native English speaker.
</p>

<p>
Important notes:
</p>

<ol>
<li>This plugin requires Javascript to be able to post a comment</li>
<li>This plugin can automatically approve valid comments depending on your settings</li>
<li>This plugin requires PHP 5</li>
</ol>

== Installation ==

<p>
This section describes how to install the plugin and get it working.
</p>

<ol>
<li>Extract and upload the directory "gabcaptcha2" and all its content to your '/wp-content/plugins/' directory</li>
<li>Activate the plugin through the 'Plugins' menu in WordPress</li>
</ol>

<p>
Optional but recommended :
</p>

<ol>
<li>Change the options to fit your needs in the 'Settings > Gab Captcha 2' menu in your Wordpress administration area</li>
<li>If you don't want to receive an email after each spam has been catches:
	<ol>
	<li>4.1. Go to the 'Settings > Discussion' menu
		<ol>
		<li>Tick the following checkboxes:
			<ul>
			<li>"E-mail me whenever > Anyone posts a comment"</li>
			<li>"Before a comment appears > An administrator must always approve the comment"</li>
			<li>"Before a comment appears > Comment author must have a previously approved comment"</li>
		</li>
		<li>Uncheck the following checkbox:
			<ul>
			<li>"E-mail me whenever > A comment is held for moderation"</li>
			</ul>
		</li>
		</ol>
	</li>
	<li>Go to the 'Settings > Gab Captcha 2' menu
		<ol>
		<li>Choose 'yes' for 'Automatically approve comments who passed the test'</li>
		</ol>
	</li>
	</ol>
</li>
</ol>

<p>
You will now receive an email only after a valid comment has been posted.
</p>

== Frequently Asked Questions ==

= Is Gab Captcha unbeatable by spambots? =

Definitely not. But it stopped all my spam as of today (getting about 100 spams every single day).

= Can I change some settings for Gab Captcha 2? =

Yes. Go to the 'Settings > Gab Captcha 2' menu in your Wordpress administration area.

= Why Gab Captcha 2 is not translated in my language? =

Most probably because nobody submitted a translation for your language yet. But you can help:
1. Copy and paste the file "lang/default.po" and rename it to "lang/gabcaptcha2-code" where "code" is your language code (eg, it is "fr" for French)
2. Use Poedit or any other tool able to handle .po and .mo files
3. Then send me your translated file.
Note: do not edit .po files by hand. Use an appropriate tool.

== Screenshots ==

1. Gab Captcha 2 settings in Wordpress administration area
2. Gab Captcha 2 before the comment text area

== Changelog ==

= 1.0.5 =
* Fixed the appearance of message "Dupplicate comment detected" after retrying to post a comment with a valid solution.

= 1.0.4 =
* Use a class now
* Use the standard Wordpress translation system using .po and .mo files. You can contribute your translation using Poedit.
* Use the recommended way to insert CSS in Wordpress
* Function reorganisation
* Code cleaning

= 1.0.3 =
* Added choice for 3 methods of generation: Standard (most compatible but average security), CSS (improved security, compatible with CSS-capable browsers), and CSS 3 (better security but only compatible with CSS3-compliant browsers)
* Corrected a bug in CSS 3 method: the indices of :nth-child() were not shifted, leading to the CSS 3 method to be unusable previously
* Corrected some translations

= 1.0.2 =
* Corrected captcha random generation issue
* Improved performance a little.
* Added option for CSS3 only captcha solution (but keep in mind that only CSS3-enabled browsers will be compatible!)
* Corrected invalid value for CSS property problem

= 1.0.1 =
* Initial public version

== Upgrade Notice ==

= 1.0.3 =
Extract new files and overwrite old ones. If you chose 'CSS 3 only->yes' in the previous version, you have to choose it again now.

= 1.0.2 =
Just overwrite older files with the new ones

= 1.0.1 =
None (initial version)
