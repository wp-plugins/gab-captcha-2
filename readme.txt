=== Gab Captcha 2 ===
Contributors: GabSoftware
Donate link: http://www.gabsoftware.com/donate/
Tags: comments, spam, captcha, turing, test
Requires at least: 3.0.0
Tested up to: 3.1.1
Stable tag: 1.0.1

Gab Captcha 2 is an efficient and simple captcha plugin for Wordpress comments.

== Description ==

Gab Captcha 2 is an efficient and simple captcha plugin for Wordpress comments.

It adds an easy turing test before each comment form. The turing test consist in typing the characters that appear emphasized and red in a text field. The plugin will add an entry in your Wordpress administration area to let you configure some options.

Gab Captcha 2 currently comes in two languages: English (default) and French. You are welcome to propose your own translation or to update existing ones, especially the English one as I am not a native English speaker.

Important notes:
1. This plugin requires Javascript to be able to post a comment.
2. This plugin puts spam comments in the "spam" folder and can automatically approve valid comments depending on your settings.

== Installation ==

This section describes how to install the plugin and get it working.

1. Extract and upload the directory "gabcaptcha2" and all its content to your '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Optional but recommended :
3. Change the options to fit your needs in the 'Settings > Gab Captcha 2' menu in your Wordpress administration area
4. If you don't want to receive an email after each spam has been catches:
4.1. Go to the 'Settings > Discussion' menu
4.1.1. Tick the following checkboxes:
       "E-mail me whenever > Anyone posts a comment"
       "Before a comment appears > An administrator must always approve the comment"
       "Before a comment appears > Comment author must have a previously approved comment"
4.1.2. Uncheck the following checkbox:
       "E-mail me whenever > A comment is held for moderation"
4.2. Go to the 'Settings > Gab Captcha 2' menu
4.2.1 Choose 'yes' for 'Automatically approve comments who passed the test'

You will now receive an email only after a valid comment has been posted.

== Frequently Asked Questions ==

= Is Gab Captcha unbeatable by spambots? =

Definitely not. But it stopped all my spam as of today (getting about 100 spams every single day).

= Can I change some settings for Gab Captcha 2? =

Yes. Go to the 'Settings > Gab Captcha 2' menu in your Wordpress administration area.

== Screenshots ==

1. Gab Captcha 2 settings in Wordpress administration area
2. Gab Captcha 2 before the comment text area

== Changelog ==

= 1.0.1 =
* Initial public version

== Upgrade Notice ==

= 1.0.1 =
None (initial version)