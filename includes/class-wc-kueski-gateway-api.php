<?php
if ( ! defined( 'ABSPATH' ) ) exit;
use WpOrg\Requests\Requests;

class Kueski_Gateway_Api
{
    const URL_PRODUCTION = "https://woocommerce-middleware-go.production-pay.kueski.com/";
    const URL_SANDBOX = "https://woocommerce-middleware-go.staging-pay.kueski.codes/";

    private $settings;
    private $cache_metadata = array();

    public function __construct($settings)
    {
        if(Kueski_Gateway_Helper::wp_version_6_or_higher()){
            if (!class_exists('WpOrg\Requests\Requests')) {
                require_once ABSPATH . WPINC . '/class-requests.php';
            }
        }else{
            if (!class_exists('Requests')) {
                require_once ABSPATH . WPINC . '/class-requests.php';
            }
        }
        
        $this->settings = $settings;
    }

    public function is_api_key_valid($force_reload = false)
    {
        $merchant_data = $this->get_merchant_data($force_reload);
        if (!$merchant_data) {
            return false;
        }

        $status =  (isset($merchant_data['status']))
            ? $merchant_data['status']
            : false;

        if ($status != 'success') {
            return false;
        }

        return true;
    }

    public function getUrl()
    {

        if ($this->settings['is_sandbox'] == true) {
            return self::URL_SANDBOX;
        } else {
            return self::URL_PRODUCTION;
        }
    }

    public function wp_validate_keys(){
        $base_url = $this->getUrl() . "api/v1/merchant/validate-keys?api_key=" . $this->settings['client_id'];
        $user_agent = $this->getUserAgent();
        $kp_identifier = $this->getKpIdentifier();

        $headers = array(
                'kp-identifier' => $kp_identifier,
                // 'Cache-Control' => 'no-cache',
                'Content-Type' => 'application/json',
                'Connection' => 'keep-alive',
                'User-Agent' => $user_agent
        );

        $options = array(
            'timeout' => 120,
            'connect_timeout' => 0,
            'follow_redirects' => true,
            'max_redirects' => 10,
        );

        try{
            $response = Requests::get($base_url, $headers, $options);
            if( $response->status_code == 200 ){
                return json_decode($response->body, true);
            }else{
                return false;
            }
        } catch (Exception $e){
            Kueski_Gateway_Helper::error('Validate Keys:' . $e->getMessage());
            return false;
        }

        return false;
    }

    public function wp_create_order($arr_order){
        $url = $this->getUrl() . "api/v1/order/create";
        $source = Kueski_Gateway_Helper::is_mobile() ? 'mobile' : 'web';
        $post = wp_json_encode($arr_order);

        $user_agent = $this->getUserAgent();
        $headers = array(
            'Authorization' => 'Bearer ' . $this->settings['client_id'],
            'Content-Length' => strlen($post),
            'Content-Type' => 'application/json',
            'Connection' => 'keep-alive',
            'User-Agent' => $user_agent,
            'kp-identifier' => $this->getKpIdentifier(),
            'kp-name' => $this->settings['kp_name'],
            'kp-version' => $this->settings['kp_version'],
            'kp-source' => $source,
            'kp-trigger' => 'rendered_button',
            'kp-wp-version' => get_bloginfo('version'),
            'kp-wc-version' => $this->getWcVersion(),
            'kp-php-version' => PHP_VERSION
        );

        $options = array(
            'timeout' => 120,
            'connect_timeout' => 0,
            'follow_redirects' => true,
            'max_redirects' => 10,
        );

        Kueski_Gateway_Helper::debug(
                        "createOrder RAW[".$url . "]:\n\n".
                        Kueski_Gateway_Helper::serialize($headers).
                        "\n\n$post\n"
                        );

        try {
            $response = Requests::post($url, $headers, $post, $options);
            
            $status_code = $response->status_code;
            if( $status_code != 200 ){
                Kueski_Gateway_Helper::error("Create Order - Status code: " . $status_code);
            }
            return json_decode( $response->body );
        } catch ( Exception $e) {
            $error_msg = $e->getMessage();
            Kueski_Gateway_Helper::error("Request CreateOrder: " . $error_msg);
            $result = (object) array(
                'status' => 'fail',
                'message' => $error_msg
            );
            return $result;
        }

        $result = (object) array(
            'status' => 'fail',
            'message' => 'Error inesperado'
        );
    }

    public function wp_orders_sync($order_ids){
        $url = $this->getUrl() . 'api/v1/orders-sync';
        $api_key = $this->settings['client_id'];
        
        $fields = array('order_ids' => $order_ids);
        $post = wp_json_encode($fields);

        $user_agent = $this->getUserAgent();
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'Connection' => 'keep-alive',
            'User-Agent' => $user_agent,
            'kp-identifier' => $this->getKpIdentifier(),
        );

        $options = array(
            'timeout' => 120,
            'connect_timeout' => 0,
            'follow_redirects' => true,
            'max_redirects' => 10,
        );

        try {
            $response = Requests::post($url, $headers, $post, $options);
            return json_decode( $response->body, true );            
        } catch ( Exception $e) {
            $error_msg = $e->getMessage();
            Kueski_Gateway_Helper::error("Request OrderSync: " . $error_msg);
            $result = array(
                'status' => 'fail',
                'message' => $error_msg
            );
            return $result;
        }
    }

    public function get_merchant_data($force_reload = false)
    {
        if (
            empty($this->settings['client_id'])
        ) {
            return false;
        }
        $response = array();
        $client_id = $this->settings['client_id'];

        $cache_id = 'mp_me_' . md5($client_id);

        if (!$force_reload) {
            //Check for valid cache data
            $metadata = $this->wp_get_metadata(0, $cache_id);
            if( $metadata !== false ){
                if( isset($metadata['status']) ){
                    return $metadata;
                }
            }
        }
        try {
            $response = $this->wp_validate_keys();
            $this->wp_set_metadata(0, $cache_id, $response);
        } catch (Exception $e) {
            return false;
        }

        return $response;
    }

    public function kueskiRefundOrder($payment_id, $refund_amount)
    {

        $url = $this->getUrl() . "api/v1/order/refund";
        $source = Kueski_Gateway_Helper::is_mobile() ? 'mobile' : 'web';
        $payload = wp_json_encode(array(
            'payment_id' => $payment_id,
            'amount' => $refund_amount,
            'reason' => 'merchant_refund'
        ));

        $user_agent = $this->getUserAgent();

        $headers = array(
            'Authorization' => 'Bearer ' . $this->settings['client_id'],
            'Content-Length' => strlen($payload),
            'Content-Type' => 'application/json',
            'Connection' => 'keep-alive',
            'User-Agent' => $user_agent,
            'kp-identifier' => $this->getKpIdentifier(),
            'kp-name' => $this->settings['kp_name'],
            'kp-version' => $this->settings['kp_version'],
            'kp-source' => $source,
            'kp-trigger' => 'rendered_button',
            'kp-wp-version' => get_bloginfo('version'),
            'kp-wc-version' => $this->getWcVersion(),
            'kp-php-version' => PHP_VERSION
        );

        $options = array(
            'timeout' => 120,
            'connect_timeout' => 0,
            'follow_redirects' => true,
            'max_redirects' => 10,
        );

        Kueski_Gateway_Helper::debug(
                        "refundOrder RAW[".$url . "]:\n".
                        Kueski_Gateway_Helper::serialize($headers).
                        "\n\n$payload\n"
                        );

        try {
            $response = Requests::post($url, $headers, $payload, $options);
            Kueski_Gateway_Helper::debug(
                "Responseeee RefundOrder: " . $response->body
                );
            return json_decode( $response->body, true );
        } catch ( Exception $e) {
            $error_msg = $e->getMessage();
            Kueski_Gateway_Helper::error(
                                    "Request RefundOrder: " . $error_msg.
                                    "\nPayment id: " . $payment_id
                                    );
            
            $result = (object) array(
                'status' => 'fail',
                'message' => $error_msg
            );
            return $result;
        }
        
        $result = (object) array(
            'status' => 'fail',
            'message' => 'Error inesperado'
        );

        return $result;
    }

    public function wp_set_metadata($order_id, $key, $value){
        $hash = $order_id . '-' . $key;
        set_transient($hash, $value, 600);
    }

    public function wp_get_metadata($order_id, $key){
        $hash = $order_id . '-' . $key;
        $cache_metadata = get_transient($hash);
        return $cache_metadata;
    }

    public function getKpIdentifier(){
        $date = new DateTime();
        $year = $date->format('y');
        $month = $date->format('m');
        $seed = wp_rand(1,100);
        $id = (int)$month * $seed;
        $identifier = 'KPWP'.$year.$month.'-'.$id;
        
        return $identifier;
    }

    public function getWcVersion(){
        if( ! function_exists('get_plugins') ){
            include_once(ABSPATH.'wp-admin/includes/plugin.php');
        }

        $all_plugins = get_plugins();
        if( isset( $all_plugins['woocommerce/woocommerce.php'] ) ){
            $wc_version = $all_plugins['woocommerce/woocommerce.php']['Version'];
        }else{
            $wc_version = '1.0';
        }

        return $wc_version;
    }

    public function getUserAgent(){
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null;
        $user_agent = esc_attr($user_agent);
        return $user_agent;
    }
}
