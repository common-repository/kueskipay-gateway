<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Kueski_Gateway_RestApi
{
    
    private $settings;
    private $api;

    public function __construct($settings)
    {
        $this->settings = $settings->get_settings();
        $this->api = new Kueski_Gateway_Api($this->settings);
    }

    public function register_endpoints(){
        register_rest_route('kueski/v1', '/monitoring', array(
                'methods' => 'GET',
                'callback' => array($this, 'kp_monitoring_handler'),
                'permission_callback' => '__return_true'
            ));
    }

    public function kp_monitoring_handler()
    {
        $merchant_data = $this->api->get_merchant_data();
        
        if( !isset( $merchant_data['status'] ) ){
            return $this->kueski_monitoring_response(503);
        }
        if( $merchant_data['status'] != 'success' ){
            return $this->kueski_monitoring_response(401);
        }

        return $this->kueski_monitoring_response(200);
    }

    function kueski_monitoring_response( $code ){
        switch ($code) {
            case 200:
                $status = 'success';
                $message = 'Service Up';
                break;
            case 401:
                $status = 'error';
                $message = 'Invalid Api Credentials';
                break;
            case 503:
            default:
                $status = 'error';
                $message = 'Service Unavailable';
                break;
        }
    
        return new WP_REST_Response( array('status' => $status, 'message' => $message), $code );
        }    
}