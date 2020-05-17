<?php
/*
Plugin Name: wp-hardener
Plugin URI: https://github.com/jsgm/wp-hardener
Description: wp-hardener is a ready to use plugin for adding an extra layer of security and performance improvements to your WordPress.
Author: José Aguilera
Version: 0.1
Author URI: https://github.com/jsgm
*/

if(!defined("ABSPATH")){
    header('HTTP/1.0 403 Forbidden');
    die();
}

if(!is_ssl()){
    throw new Exception("SSL is required to install wp-hardener.");
}

if(!defined("PHP_INT_MIN")){
    // For WP versions < 5.5
    define('PHP_INT_MIN', ~PHP_INT_MAX);
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
define("VERIFY_CHECKSUMS", TRUE);
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

class wphardener{
    public function __construct(){
        if(ADD_SECURITY_HEADERS){
            $this->security_headers();
        }
        if(HIDE_WP_VERSION){
            $this->hide_wordpress_generator();
        }
        if(REMOVE_LICENSE_FILES){
            $this->remove_license_files();
        }
        if(DISALBE_XMLRPC){
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
    }

    private function disable_wptexturize(){
        add_filter( 'xmlrpc_enabled', '__return_false' );
    }
    
    private function clear_version_from_string($s){
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
        /*
         * Adds security headers. This might not work using WP-Rocket or other cache plugins.
         * 
         * More info: https://securityheaders.com/
         * 
         */
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
        /*
         * Removes meta data for WP Bakery Page Builder.
         * 
         */
        add_action('wp_head', function(){
            if(class_exists('Vc_Base') && function_exists("visual_composer")) {
                remove_action('wp_head', array(visual_composer(), 'addMetaData'));
            }
        });
    }

    private function remove_headers(){
        /*
         * Removes unnecesary headers.
         * 
         */
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
    }

    private function remove_license_files(){
        /*
         * Deletes license files and readme.
         * 
         */
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
        /*
         * Fully disables XMLRPC.
         * 
         */
        // Remove text-link from head.
        remove_action("wp_head", "rsd_link");

        // Disable XMLRPC.
        add_filter("xmlrpc_enabled", "__return_false");

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

        // Make /xmlrpc.php return 403.
        add_action("init", function(){
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

    private function remove_shortlinks(){
        add_filter('after_setup_theme', function(){
            // Remove HTML meta tag.
            remove_action('wp_head', 'wp_shortlink_wp_head', 10);

            // Remove HTTP header.
            remove_action( 'template_redirect', 'wp_shortlink_header', 11);
        });
        add_filter('after_setup_theme', 'remove_redundant_shortlink');
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
        if ( defined( 'ABSPATH' ) ) {
            include( ABSPATH . 'wp-includes/version.php' );
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
    private function hide_wordpress_generator(){
        remove_action("wp_head", "wp_generator");
    }
}
(new wphardener());
?>