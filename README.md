![License](https://img.shields.io/github/license/jsgm/wp-hardener)

!!! This plugin is not finished yet but it's fine to use in the current version in a production enviroment.

# wp-hardener
wp-hardener is a ready to use plugin which adds an extra layer of security and performance improvements to your WordPress. Most of the features listed here are a recopillation from functions that I've used in different WordPress sites. 

# Requeriments
* WP 5.4.1 or higher.
* SSL Certificate enabled and installed.

# Disabling options
In case you need a specific feature not being disabled, modify the ***plugin.php*** file before install. At firsts lines you'll find some constants like shown here. Set to FALSE.

```php
define("DISABLE_OEMBED", FALSE); 
```

# Security features
- Forces SSL for /wp-admin.
- Disables the built-in file editor.
- Removes files versions from URLs if WP_DEBUG is set to FALSE.
- Fully disables XMLRPC.
- Removes the WLW meta tag and file (wlwmanifest.xml) for Windows Live Writer.
- Removes the license files and readme.html from root folder.
- Hides WP version.
- Removes meta tag from Visual Composer / WP Bakery.
- Adds security headers. You can checkout yours on [securityheaders.com](https://securityheaders.com/)
- Disables oEmbed.
- Removes Link HTTP header.
- Disables WordPress URL guessing.
- Will send a 404 response in wp-login.php if the User-Agent is not legit. This will add a little help to stop brute-force attacks altought it's easy to bypass.

# Performance features
- Disables [wptexturize](https://developer.wordpress.org/reference/functions/wptexturize/).
- Limit [posts revisions](https://kinsta.com/knowledgebase/wordpress-revisions/) to 3. 
- Disables [emojis](https://kinsta.com/knowledgebase/disable-emojis-wordpress/).
- Switchs local jQuery files to Google jQuery CDN.

# Tested on
| WP Version | Working |
|--|--|
| 5.4.1 | &check; |
