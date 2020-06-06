<?php
/*
Plugin Name: wp-hardener
Plugin URI: https://github.com/jsgm/wp-hardener
Description: wp-hardener is a ready to use plugin for adding an extra layer of security and performance improvements to your WordPress.
Author: José Aguilera
Version: 0.1
Author URI: https://github.com/jsgm
*/
use Jaybizzle\CrawlerDetect\CrawlerDetect;

if(!defined("ABSPATH")){
    header('HTTP/1.0 403 Forbidden');
    die();
}

if(!defined("PHP_INT_MIN")){
    // For WP versions < 5.5
    define('PHP_INT_MIN', ~PHP_INT_MAX);
}

if(!defined("FORCE_SSL_ADMIN")){
    // Forces SSL on admin panel.
    define("FORCE_SSL_ADMIN", TRUE);

    if(isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && strpos($_SERVER["HTTP_X_FORWARDED_PROTO"], "https") !== FALSE){
        $_SERVER['HTTPS']='on';
    }
}

if(!defined('DISALLOW_FILE_EDIT')){
    // Disables embebbed file editor.
    define('DISALLOW_FILE_EDIT', TRUE);
}

if(!defined("WP_POST_REVISIONS")){
    // Limit WordPress posts revisions saves to 3.
    define('WP_POST_REVISIONS', 3);
}

// expose_php => Off
@ini_set("expose_php", "Off");

// Configuration
define("ADD_SECURITY_HEADERS", TRUE);
define("HIDE_WP_VERSION", TRUE);
define("VERIFY_CHECKSUMS", FALSE);
define("DISABLE_WLWMANIFEST", TRUE);
define("DISABLE_OEMBED", TRUE);
define("DISABLE_LICENSE_FILES", TRUE);
define("DISABLE_XMLRPC", TRUE);
define("DISABLE_WPTEXTURIZE", TRUE);
define("DISABLE_EMOJIS", TRUE);
define("REMOVE_SHORTLINKS", TRUE);
define("REMOVE_UNWANTED_HEADERS", TRUE);
define("REMOVE_WP_BAKERY_GENERATOR", TRUE);
define("REMOVE_FILES_VERSIONS", TRUE);
define("REMOVE_LICENSE_FILES", TRUE);
define("BLOCK_CRAWLERS_ON_LOGIN", TRUE);
define("CHANGE_LOGIN_URL", TRUE);
define("NEW_LOGIN_URL", "url");
define("DISABLE_URL_GUESSING", TRUE);
define("REPLACE_JQUERY_WITH_GOOGLE_CDN", TRUE);
define("DISABLE_API", TRUE);

class wphardener{
    public function __construct(){
        if(REPLACE_JQUERY_WITH_GOOGLE_CDN){
            //
        }
        if(DISABLE_API){
            $this->disable_api();
        }
        if(ADD_SECURITY_HEADERS){
            $this->security_headers();
        }
        if(HIDE_WP_VERSION){
            $this->hide_wordpress_generator();
        }
        if(REMOVE_LICENSE_FILES){
            $this->remove_license_files();
        }
        if(DISABLE_XMLRPC){
            $this->disable_xmlrpc();
        }
        if(DISABLE_WLWMANIFEST){
            $this->disable_wlwmanifest();
        }
        if(VERIFY_CHECKSUMS){
            $this->verify_checksums();
        }
        if(DISABLE_WPTEXTURIZE){
            $this->disable_wptexturize();
        }
        if(REMOVE_SHORTLINKS){
            $this->remove_shortlinks();
        }
        if(REMOVE_UNWANTED_HEADERS){
            $this->remove_headers();
        }
        if(REMOVE_WP_BAKERY_GENERATOR){
            $this->remove_bakery_generator();
        }
        if(DISABLE_OEMBED){
            $this->disable_oembed();
        }
        if(REMOVE_FILES_VERSIONS){
            $this->remove_files_versions();
        }
        if(DISABLE_EMOJIS){
            $this->disable_emojis();
        }
        if(BLOCK_CRAWLERS_ON_LOGIN){
            $this->block_crawlers_on_login();
        }
        if(DISABLE_URL_GUESSING){
            $this->disable_url_guessing();
        }
    }

    private function disable_api(){
        add_action( 'rest_api_init', function(){
            if(!current_user_can("manage_options")){
                $whitelist = array('127.0.0.1', "::1");
                if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
                    die('REST API is disabled.');
                }
            }
        }, 1);
    }

    private function is_admin_login(){
        return ($GLOBALS['pagenow'] === 'wp-login.php' ? true : false);
    }
    
    private function block_crawlers_on_login(){
        // Block crawlers in login page.
        add_action("init", function(){
            if($this->is_admin_login()){
                require dirname(__FILE__)."/vendor/autoload.php";
                $CrawlerDetect = new CrawlerDetect;
                if($CrawlerDetect->isCrawler()){
                    header('HTTP/1.0 404 Not Found', true, 404);
                    die();
                }
            }
        }, PHP_INT_MAX);
    }

    private function disable_wptexturize(){
        // Disable wptexturize.
        add_filter( 'xmlrpc_enabled', '__return_false' );
    }
    
    private function clear_version_from_string($s){
        // Remove version from URL.
        if(is_string($s) && strlen($s)>0 && strpos($s, 'ver=') !== FALSE){
            $s = remove_query_arg('ver', $s);
        }
        return $s;
    }

    private function disable_emojis(){
        // Check out: https://kinsta.com/knowledgebase/disable-emojis-wordpress/#disable-emojis-code
        add_action('init', function(){
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
            remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
            remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
            remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
            remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
            add_filter( 'tiny_mce_plugins', function($plugins){
                if(is_array($plugins)){
                    return array_diff($plugins, array('wpemoji'));
                }else{
                    return array();
                }
            });
            add_filter( 'wp_resource_hints', function($urls, $relation_type){
                if(is_string($relation_type) && 'dns-prefetch' == $relation_type){
                    /** This filter is documented in wp-includes/formatting.php */
                    $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
                    $urls = array_diff($urls, array($emoji_svg_url));
                }
               return $urls;
            }, 10, 2);
        });
    }
    private function remove_files_versions(){
        // Remove version from styles and scripts only if not running debug mode.
        if(!WP_DEBUG){
            add_filter('style_loader_src', function($s){
                return $this->clear_version_from_string($s);
            }, PHP_INT_MAX);
            add_filter('script_loader_src', function($s){
                return $this->clear_version_from_string($s);
            }, PHP_INT_MAX);
        }
    }

    private function security_headers(){
        // Adds security headers. This might not work using WP-Rocket or other cache plugins. Check out: https://securityheaders.com/
        add_action('send_headers', function(){
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
            header("X-XSS-Protection: 1; mode=block");
            header("X-Frame-Options: DENY");
            header("Feature-Policy: microphone 'none'; speaker 'none'");
            header("X-Content-Type-Options: nosniff");
            header("Expect-CT: max-age:0");
            header("X-DNS-Prefetch-Control: on");
            Header("Content-Security-Policy: upgrade-insecure-requests");
            header("Referrer-Policy: strict-origin-when-cross-origin");
        });
    }

    private function remove_bakery_generator(){
        // Removes meta data for WP Bakery Page Builder.
        add_action('wp_head', function(){
            if(class_exists('Vc_Base') && function_exists("visual_composer")) {
                remove_action('wp_head', array(visual_composer(), 'addMetaData'));
            }
        });
    }

    private function remove_headers(){
        // Try to remove X-Powered-By if possible.
        header_remove('X-Powered-By');

        // Removes unnecesary headers.
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
    }

    private function remove_license_files(){
        // Deletes license files and readme.
        try{
            $files = ["license.txt", "licencia.txt", "readme.html"];
            foreach($files as $file){
                $location = ABSPATH.$file;
                if(file_exists($location)){
                    unlink($location);
                }
            }
        }catch(Exception $ex){
            $this->save_log($ex->getMessage());
        }
    }

    private function disable_xmlrpc(){
        // Fully disables XMLRPC.
        remove_action("wp_head", "rsd_link"); // Remove text-link from head.
        add_filter("xmlrpc_enabled", "__return_false"); // Disable XMLRPC.
        // Disable endpoints.
        add_filter("xmlrpc_methods", function(){
            return [];
        }, PHP_INT_MIN);

        add_filter('wp_headers', function($headers){
            unset($headers['X-Pingback']);
            return $headers;
        }, PHP_INT_MIN);

        add_action('wp', function() {
            header_remove('X-Pingback');
        }, 1000);

        add_action("init", function(){
            // Make /xmlrpc.php return 403.
            if(defined("XMLRPC_REQUEST")){
                header('HTTP/1.0 403 Forbidden');
                header("Content-type:text/plain; charset=utf-8");
                exit("XMLRPC disabled!");
            }
        }, PHP_INT_MIN);
    }

    private function disable_wlwmanifest(){
        try{
            remove_action("wp_head", "wlwmanifest_link");
            $wlwmanifest_location = ABSPATH."wp-includes/wlwmanifest.xml";
            if(file_exists($wlwmanifest_location)){
                unlink($wlwmanifest_location);
            }
        }catch(Exception $ex){
            $this->save_log($ex->getMessage());
        }
    }

    private function save_log($text){

    }

    private function disable_url_guessing(){
        // By Andrew Nacin: https://profiles.wordpress.org/nacin/
        add_filter('redirect_canonical', function($redirect_url){
            if(is_404()){
                return false;
            }
            return $redirect_url;
        });
    }

    private function remove_shortlinks(){
        add_filter('after_setup_theme', function(){
            // Remove HTML meta tag.
            remove_action('wp_head', 'wp_shortlink_wp_head', 10);

            // Remove HTTP header.
            remove_action( 'template_redirect', 'wp_shortlink_header', 11);
        });
    }

    private function disable_oembed(){
        add_action("init", function(){
            // Turn off oEmbed auto discovery.
            // Don't filter oEmbed results.
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

            // Remove oEmbed discovery links.
            remove_action('wp_head', 'wp_oembed_add_discovery_links');

            // Remove oEmbed-specific JavaScript from the front-end and back-end.
            remove_action('wp_head', 'wp_oembed_add_host_js');
        }, 1000);
    }

    private function verify_checksums(){
        if(defined('ABSPATH')){
            include(ABSPATH.'wp-includes/version.php');
            $wp_locale = isset( $wp_local_package ) ? $wp_local_package : 'en_US';
            $apiurl = 'https://api.wordpress.org/core/checksums/1.0/?version=' . $wp_version . '&locale=' .  $wp_locale;
            $json = json_decode ( file_get_contents ( $apiurl ) );
            $checksums = $json->checksums;
            foreach( $checksums as $file => $checksum ) {
                $file_path = ABSPATH . $file;
                if ( file_exists( $file_path ) ) {
                /*if ( md5_file ($file_path) !== $checksum ) {
                    echo '<p>¡Checksum de " .$file_path ." no es coincidente!</p>';
                }else{
                    echo '<p>Checksum coincidente.</p>';
                }*/
                }
            }
        }
    }
    
    private function check_http2(){
       /* $url = "https://google.com";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
        ]);
        $response = curl_exec($ch);        
        if ($response !== false && strpos($response, "HTTP/2.0") === 0) {
            echo "Server of the URL has HTTP/2.0 support.";
        } elseif ($response !== false) {
            echo "Server of the URL has no HTTP/2.0 support.";
        } else {
            echo curl_error($ch);
        }
        curl_close($ch); */
    }
    
    private function hide_wordpress_generator(){
        // Removes the meta generator tag with WP version
        remove_action("wp_head", "wp_generator");

        // Hides version from wp-admin footer
        add_action("admin_menu", function(){
            remove_filter('update_footer', 'core_update_footer');
        });
    }
}

add_action("init", function(){
    (new wphardener());
});
?>