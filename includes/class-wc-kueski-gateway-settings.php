<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Kueski_Gateway_Settings
{

    private $settings;
    
    public function __construct()
    {
        $this->settings = get_option( 'woocommerce_kueski-gateway_settings', [] );
        $this->settings['is_sandbox'] = $this->settings['sandbox'] == 'yes';
        $version = defined('KUESKI_GATEWAY_VERSION') ? KUESKI_GATEWAY_VERSION : '1.0.0';
        $this->settings['kp_version'] = 'v'.$version;
        $this->settings['kp_name'] = 'kueski_woocommerce';

    }
    
    public function get_settings(){
        return $this->settings;
    }
}
