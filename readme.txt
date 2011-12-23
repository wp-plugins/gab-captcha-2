=== Gab Captcha 2 ===
Contributors: GabSoftware
Donate link: http://www.gabsoftware.com/donate/
Tags: comments, spam, captcha, turing, test
Requires at least: 3.0.0
Tested up to: 3.3.0
Stable tag: 1.0.16

Gab Captcha 2 is a simple captcha plugin for fighting spam in WordPress comments.

== Description ==

<p>
Gab Captcha 2 is a simple, easy-to-solve and efficient captcha plugin for fighting spam in WordPress comments.
</p>

<p>
It adds an easy Turing test before each comment form. The Turing test consists of emphasized characters (red by default) that you must type in a text field. The plugin can be configured in your administration area.
</p>

<p>
You can choose to insert (or not insert) the comments into the database in case of test failure. Inserting comments on test failure can be useful if you want to be sure that blocked comments are really spam. On the other hand, choosing not to insert the comments on test failure can lower your database usage as writing to the database is an expensive process.
</p>

<p>
A visitor who failed to provide a valid solution to the test will have the opportunity to try again and will not loose his comment.
</p>

<p>
Gab Captcha 2 currently comes in three languages: English (default), Russian and French. You are welcome to propose your own translation or to update existing ones, especially the English one as I am not a native English speaker.
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
	<li>If you don't want to receive an email after each spam has been catched:
		<ol style="list-style-type:upper-alpha">
			<li>Go to the 'Settings > Discussion' menu
				<ol style="list-style-type:lower-alpha">
					<li>Tick the following checkboxes:
						<ul>
							<li>"E-mail me whenever > Anyone posts a comment"</li>
							<li>"Before a comment appears > An administrator must always approve the comment"</li>
							<li>"Before a comment appears > Comment author must have a previously approved comment"</li>
						</ul>
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
1. Go to read <a href="http://www.gabsoftware.com/tips/a-guide-for-wordpress-plugins-translators-gettext-poedit-locale/">this article<a>
2. The POT file is located in the "lang" sub-directory of Gab Captcha 2
3. Then send me your translated file.
Note: do not edit .po files by hand. Use an appropriate tool such as Poedit and send me both the resulting .po and .mo files.

== Screenshots ==

1. Gab Captcha 2 settings in Wordpress administration area
2. Gab Captcha 2 before the comment text area

== Changelog ==

= 1.0.16 =
* Option to block spam before it is inserted into the database (default is: insert, like the previous version)
* Corrected the "required" attribute of checkbox fields
* Updated the description in readme.txt (this file)
* Code cleaning
* Bugs fixed

= 1.0.15 =
* Performance tweaks
* Use DOM methods to add the captcha instead of element.innerHTML: can now be used on XHTML websites served as application/xhtml+xml
* Corrected the display bug when the credits are displayed as text
* Code cleaning
* Administration page improved
* WordPress 3.3 support

= 1.0.14 =
* Fixed the checkbox that was not saved in the options (thanks to Hans!)
* No longer use a file to get back the comment after failure
* Better escaping thanks to esc_js()
* Code cleaning
* Moved the code outside the main class inside the class
* Corrected the visibility of some main class functions

= 1.0.13 =
* This is a maintenance release
* Bug fixed: Captcha field length was hard-coded to "4", ignoring the corresponding option. Now fixed. Thanks to Billy Willoughby for reporting this!
* Renamed the PHP functions outside the class and the Javascript functions so that they all begin with the "gabcaptcha2_" prefix to avoid conflicts.
* Updated French translation. Waiting for the Russian translation.

= 1.0.12 =
* New administration page
* Only two options in the Wordpress options table now
* Bugs fixed
* Updated French translation

= 1.0.11 =
* Bugs fixed
* Translation issues fixed
* If the URL field is not found, try with other fields

= 1.0.10 =
* Bugs fixed

= 1.0.9 =
* Russian translation added (thanks!)

= 1.0.8 =
* Fixed some translation issues
* Updated the Readme file

= 1.0.7 =
* After an invalid solution is provided, automatically scrolls to the comment area so that the user knows he failed.

= 1.0.6 =
* Fixed the appearance of message "You are posting too quickly. Slow down!" when a valid solution has been provided.

= 1.0.5 =
* Fixed the appearance of message "Dupplicate comment detected" after retrying to post a comment with a valid solution.
* Comments are no longer moved to the spam folder.

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

== Upgrade notice ==

= 1.0.16 =

New option in your administration menu: spam can be blocked before it is inserted into the database.
See the change log for more information.
