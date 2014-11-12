# bbPress - Report Content #

Contributors: jjeaton  
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7DR8UF55NRFTS  
Tags: bbpress, topics, replies, report, content, spam  
Requires at least: 3.6  
Tested up to: 3.8.1  
Stable tag: 1.0.4  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

Give your bbPress forum users the ability to report inappropriate content or spam in topics or replies.

## Description ##

Give your bbPress forum users the ability to report inappropriate content or spam in topics or replies. This plugin adds a "report" admin link to topics and replies, when clicked, the topic/reply is assigned a post status of "User Reported".

All logged-in users can report content and see that a topic has been reported, only Moderators and up can un-report the content. Integrates with the standard Topic admin screens.

When a topic is reported, a banner is shown at the top of the page indicating that the topic has been reported as inappropriate. For replies, a message is added within the reply, before the content.

Requires bbPress 2.4+.

### Translators ###

bbPress - Report Content is fully internationalized and ready for translation:

The following translations are currently available:

* Croatian (hr) - Sanjin Barac
* Finnish (fi) - [Marko Kaartinen](https://github.com/MarkoKaartinen)
* French (fr_FR) - [Matthieu Durocher](http://technocyclope.com/)
* German (de_DE) - [Alexander Ihrig](http://www.thunderbird-mail.de/)
* Italian (it_IT) - Barbara Lerici
* Polish (pl_PL) - Paulina
* Spanish (es_ES) - [Andrew Kurtis - WebHostingHub](http://www.webhostinghub.com/)

New language packs, or updates to existing ones, can be sent via GitHub or by [contacting me](http://www.josheaton.org/contact/).

### Developers ###

Active development happens on Github: [https://github.com/jjeaton/bbpress-report-content](https://github.com/jjeaton/bbpress-report-content). PRs welcome!

## Installation ##

1. Upload the `bbpress-report-content` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

## Frequently Asked Questions ##

### Can I see which user reported the content as inappropriate? ###

The user who reported the topic or reply is stored as post meta and displayed as a column on the "User Reported" view.

## Screenshots ##

1. The report link displayed in the topic admin links list.
2. Topics admin screen, showing the custom "User Reported" status.
3. Front end display of a reported topic.

## Changelog ##

### 1.0.5 - 2014-11-12 ###

* Enhancement - Croatian translation (props Sanjin Barac)
* Enhancement - Italian translation (props Barbara Lerici)
* Fix - Require bbPress to be activated before loading.
* Fix - Errors in es_ES translation (props Barbara Lerici)

### 1.0.4 - 2014-02-11 ###

* Enhancement - German translation (props Alexander Ihrig)

### 1.0.3 - 2014-01-31 ###

* Enhancement - Polish translation (props Paulina)
* Enhancement - Finnish translation (props Marko Kaartinen)

### 1.0.2 - 2014-01-30 ###

* Fix - Plugin was loading the wrong directory for language files.
* Fix - Modify all textdomain strings and update POT file
* Fix - Make `get_reply_report_link` public so it can be used to output the report link elsewhere.
* Enhancement - Spanish Translation (props Andrew Kurtis)
* Enhancement - French Translation (props Matthieu Durocher)

### 1.0.1 - 2013-10-13 ###

* Fix - Issue where bbPress feeds were only showing "reported" topics/replies.

### 1.0.0 ###

* Initial release

## Upgrade Notice ##

### 1.0.4 ###

Translation update only.

* Enhancement - German translation (props Alexander Ihrig)

### 1.0.3 ###

Translation update only.

* Enhancement - Polish translation (props Paulina)
* Enhancement - Finnish translation (props Marko Kaartinen)

### 1.0.2 ###

* Fix - Plugin was loading the wrong directory for language files.
* Fix - Modify all textdomain strings and update POT file
* Fix - Make `get_reply_report_link` public so it can be used to output the report link elsewhere.
* Enhancement - Spanish Translation (props Andrew Kurtis)
* Enhancement - French Translation (props Matthieu Durocher)

### 1.0.1 ###

* Upgrade to fix a bug in 1.0.0 that broke bbPress RSS feeds.

### 1.0.0 ###

* Initial release
