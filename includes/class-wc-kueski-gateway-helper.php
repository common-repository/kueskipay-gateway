<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Kueski_Gateway_Helper
{

    public function __construct()
    {
    }

    static public function error($message){
        $message = 'Error: ' . $message;
        self::debug($message);
    }

    static public function debug($message)
    {
        if( empty($message) ){
            return;
        }

        $logger = new WC_Logger();
        $logger->add('kueski_pay', $message);

        return;
    }
    
    static public function serialize( &$data, $return_log = true){
        return print_r($data, $return_log);
    }

    static public function is_mobile(){
        if ( function_exists('wp_is_mobile') ) {
            return wp_is_mobile();
        }
        if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $is_mobile = false;
        } elseif ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile' ) !== false // many mobile devices (all iPhone, iPad, etc.)
            || strpos( $_SERVER['HTTP_USER_AGENT'], 'Android' ) !== false
            || strpos( $_SERVER['HTTP_USER_AGENT'], 'Silk/' ) !== false
            || strpos( $_SERVER['HTTP_USER_AGENT'], 'Kindle' ) !== false
            || strpos( $_SERVER['HTTP_USER_AGENT'], 'BlackBerry' ) !== false
            || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mini' ) !== false
            || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mobi' ) !== false ) {
                $is_mobile = true;
        } else {
            $is_mobile = false;
        }

        return $is_mobile;
    }

    static public function wp_version_6_or_higher(){
        $wp_version = get_bloginfo('version');
        return version_compare($wp_version, '6.0', '>=');
    }
}
