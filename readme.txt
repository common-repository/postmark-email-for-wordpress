=== Postmark Email for WordPress by Yoast ===
Contributors: joostdevalk
Donate link: http://yoast.com/
Tags: email, reliability, email reliability, postmark, yoast, postmarkapp, smtp, wildbit, notifications, wp_mail
Requires at least: 3.1
Tested up to: 3.3
Stable tag: 0.3.2

Reroute all WordPress mail through Postmark to increase email reliability.

== Description ==

This plugin replaces WordPress' internal email sending functionality by routing all email through [Postmark](http://postmarkapp.com), greatly enhancing email reliability. If you rely on your WordPress install to send you email, this plugin along with the awesome service from Postmark makes sure that it actually does reach its destination.

Why you should use this plugin over the official Postmark approved plugin or other Postmark plugins? 

* Uses the [WordPress HTTP API](http://yoast.com/wp-best-practice/wordpress-http-api/) instead of CURL, so it even works when there's no CURL available.
* This plugin sends your email over a secure (SSL) connection (and actually checks whether the host on the other side is really the host we expect).
* Handles From / CC / BCC headers correctly, when set by plugins like [Gravity Forms](http://yoast.com/wp-plugin-review/gravity-forms/).
* The plugin handles attachments correctly.

More info about the plugin:

* [Sending reliable email with Postmark](http://yoast.com/postmark-reliable-email/).
* [Plugin page on yoast.com](http://yoast.com/wordpress/postmark-email-plugin/).

== Screenshots ==

1. An email sent through Postmark, properly signed by the domain.

== Installation ==

How to install this plugin?

1. Search for "postmark email" in the "Add new plugin" interface.
1. Install the plugin and activate it.
1. Open your wp-config.php file and add your Postmark API key in the following way: `define('POSTMARKAPP_API_KEY', '<API KEY>');`. (Find your API key under Your Rack -> Your Server -> Credentials).
1. Set your FROM address in the same way: `define('POSTMARKAPP_MAIL_FROM_ADDRESS', 'from@example.com');`, make sure it's a valid Postmark [Sender Signature](https://postmarkapp.com/signatures).
1. You're done!

== Frequently Asked Questions ==

= Why would I use Postmark instead of sending email with my web server? =

Because sending email with your web server is horribly unreliable. While this might not be a problem when all it emails is some comment emails, but when you start relying on emails from your WordPress install for inquiries, order forms or transaction emails, you really should make 100% sure these emails arrive. Postmark makes that part easy. You might want to read the authors posts about [email reliability](http://yoast.com/email-reliability/) and [sending reliable email with Postmark](http://yoast.com/postmark-reliable-email/). 

There are definitely other choices of email parties out there, so far the author has just used Postmark and loves its simplicity.

= How do I specify multiple allowed from addresses? =

By comma separating the from addresses in the wp-config: 

`define('POSTMARKAPP_MAIL_FROM_ADDRESS', 'from@example.com,secondfrom@example.com');`

= Can I use this plugin as a Must Use plugin too? =

Yes, simply copy the contents of the plugin directory (so not the entire directory itself) into your `wp-content/mu-plugins/` directory. This is the main reason why the plugin doesn't have an admin screen; it's meant as a drop-in functionality for sites.

== Changelog ==

= 0.3.2 =

* Apparently I can't type so I made ANOTHER error in the `function_exists` call.

= 0.3.1 =

* You can now specify several allowed from addresses, by comma separating them in the `POSTMARKAPP_MAIL_FROM_ADDRESS` constant.
* If from address is not in list of allowed from addresses, the from address is changed to the first allowed email address and the set from address and name is used in the reply-to instead.
* Improved the readme.txt, adding an [FAQ](http://wordpress.org/extend/plugins/postmark-email-for-wordpress/faq/).

= 0.3 =

* Fixed typo in 0.2 that would still cause activation issues.
* Plugin no longer activates when API key is not set.

= 0.2 =

* Properly wrap in a `function_exists` call so the plugin will activate when used as a normal plugin.

= 0.1 =

* Initial beta release.