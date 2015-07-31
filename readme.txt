===Questionnaire===
Contributors: kurohara
Donate link: 
Tags: questionnaire, survey, comments, plugin
Requires at least: 4.2.2
Tested up to: 4.2.3
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Issue questionnaire on your own WordPress site.

== Description ==

This plugin adds the ability to create the quetionnaire sheet.
You can create a questionnaire by adding a post of 'questionnaire' post-type.
The 'questionnaire' post-type has form editor to edit questionnaire form so that you can easily create the questionnaire sheet.
You can restrict the person who can answer the questionnaire to the users who has login account.
(This is default behavior)

== Installation ==

1. From 'plugin install' page, upload questionnaire-1.0.0.zip from 'plugin upload' button.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= How can I create a questionnaire? =

First, you have to have the 'Editor' role to create questionnaire, log in to your WordPress site with such account, then you can create one from the 'Questionnaire' menu of left menu bar of WordPress's dashboard.

= How can visitors see the questionnaire? =

The questionnaire you created has URL of its own.
If you are using customized permalink settings, the URL of your questionnaire will be depends on it.
You can add a link of the questionnaire(or a archive link to 'questionnaire' post-type) as a menu item to menu of your site.  
For example, if your site address is 'http://mysite.com/' and you are using permalink setting like as   
'http://mysite.com/%category%/%postname%', you can add a custom link menu item with link of 'http://mysite.com/questionnaire', this will work as 'archive' menu of your questionnaire.  

= Is the answer of questionnaire sent by email? =
NO, not currently.

= How can I see the answers ? =

To see the answer list of your questionnaire, you should log into your WordPress site with the user which has 'Editor' role.  
After you logged in to your WordPress site, go to the page of your questionnaire which you want to see the answers.
You can not see the answers on questionnaire edit page.  

= Where the answer data saved? =

The answer for a questionnaire is stored as 'comment' data with special comment-type has set.  

== Screenshots ==

1. Create a questionnaire from the admin menu 'Questionnaire'.
2. Like a normal post, name your questionair, and write some description.
3. The 'Questionnaire Sheet' option has to be on to edit your questionnaire.
4. You can edit the questionnaire form on 'Form Designer'.
5. You can edit the questionnaire form on 'Form Designer'.
6. You can edit the questionnaire form on 'Form Designer'.
7. To add your questionnaire's archive link to your menu, create the 'custom link' menu item.
8. The 'Is Public' has to be on if you want to issue the questionnaire to the visitors who has no login account.
9. The visitors will see the questionnaire by selecting the menu item which have link to questionnaire's archive.
  If multiple questionnaire is exist, the visitors will not see the questionnaire sheet unless they enter to individual questionnare.
10. The public questionnaire requires visitors to give a name and e-mail address.
11. To see the answer list of the questionnaire, you should log in with the account which have 'Editor' role, then visit the questionnaire page.
   You can see the entire list of answers, or you can download the answer list as CSV file.

== Sample Movie ==

[youtube https://www.youtube.com/watch?v=YbU9djfdCzU]


== Changelog ==

= 1.0 =
* First release.

== Other materials ==

1. This project is using [Icomoon](https://github.com/Keyamoon) icon fonts.

== Additional Requirements ==

1. To use this plugin, your WordPress should be running on PHP ver 5.3 or lator.


