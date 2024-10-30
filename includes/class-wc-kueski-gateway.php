<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Payment Gateway for Kueski Pay.
 *
 * @author    Kueski <info@kueski.com>
 * @copyright 2024 Kueski
 * @license   Commercial use allowed (Non-assignable & non-transferable), can modify source-code but cannot distribute modifications (derivative works).
 */

if (!class_exists('WC_Payment_Gateway')) {
    return;
}

class Kueski_Gateway extends WC_Payment_Gateway
{

    static private $client_id                = null;
    static private $sandbox                  = null;
    static private $widget_product           = true;
    static private $widget_cart              = true;
    static private $is_load                  = null;
    static private $log                      = null;
    static private $color_schema             = null;
    static private $color_schema_cart          = null;
    static private $color_schema_checkout    = null;
    static private $font_size                 = null;
    static private $text_aling                 = null;
    static private $decimals                 = null;
    static private $cache_metadata           = array();

    private $api_me                          = null;
    private $helper                          = null;
    private $api                             = null;
    private $supported_currencies            = array();
    private $order                           = null;


    public function __construct()
    {
        $this->id                       = 'kueski-gateway';
        $this->icon                     = apply_filters('woocommerce_kueski_icon', plugins_url('images/kueski.png', plugin_dir_path(__FILE__)));
        $this->method_title             = __('Kueski', 'kueskipay-gateway');
        $this->method_description       = __('Kueski Gateway Description', 'kueskipay-gateway');

        $this->title                    = __('Elige cuántas quincenas, sin tarjeta de crédito.', 'kueskipay-gateway');
        $this->description              = __('<strong>Al pagar con kueski Pay</strong><ul style="margin-left:0;"><li style="list-style:disc !important">Compras ahora y terminas de pagar después</li><li style="list-style:disc !important">Eliges el número de quincenas</li><li style="list-style:disc !important">Puedes comprar en miles de comercios afiliados</li></ul>', 'kueskipay-gateway');

        self::$client_id                = (string) $this->get_option('client_id');
        self::$sandbox                  = $this->get_option('sandbox') == 'yes';
        self::$widget_product           = true;
        self::$widget_cart              = true;
        self::$color_schema             = (string) $this->get_option('color_scheme');
        self::$color_schema_cart        = (string) $this->get_option('color_scheme_cart');
        self::$color_schema_checkout    = (string) $this->get_option('color_scheme_checkout');
        self::$font_size                = (string) $this->get_option('font_size');
        self::$text_aling               = (string) $this->get_option('text_align');
        self::$decimals                 = (int)$this->get_option('decimals', 2);
        self::$is_load                  = $this;

        // New attributes
        $this->supported_currencies = ['MXN'];
        
        $settings_object = new Kueski_Gateway_Settings();
        $settings = array_merge($this->settings, $settings_object->get_settings() );

        $this->helper = new Kueski_Gateway_Helper();
        $this->api = new Kueski_Gateway_Api($settings);
        $this->order = new Kueski_Gateway_Order($settings);
        
        $section = isset( $_GET['section'] ) ? sanitize_text_field($_GET['section']) : null;
        $section = esc_attr($section);
        
        if ( is_admin() && $section !== null && $this->id == $section ) {
            $this->run_admin_validators();
            $this->init_form_fields();
        }

        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function is_available()
    {
        $available = false;

        if( is_admin() )
        {
            return true;
        }

        if ( $this->settings['enabled'] != 'yes') {
            Kueski_Gateway_Helper::error(
                "Not available: Plugin is disabled"
            );
            return false;
        }

        if(empty($this->settings['client_id'])){
            Kueski_Gateway_Helper::error(
                "Not available: Empty api_key"
            );
            return false;
        }

        $merchant_data = $this->api->get_merchant_data();
        if (WC()->cart && $merchant_data['status'] == "success") {
            $ordertotal = (float)wp_kses_data( WC()->cart->get_cart_contents_total() );
            $min_amount = (float)$merchant_data['data']['merchant_limits']['min_amount'];
            $max_amount =  (float)$merchant_data['data']['merchant_limits']['max_amount'];
            
            if( $ordertotal == 0 ){
                return true;
            }
            
            $supported_currency = in_array(get_woocommerce_currency(), $this->supported_currencies);
            $available = $ordertotal >= $min_amount
                        && $ordertotal <= $max_amount
                        && $supported_currency;
        }

        if( $available === false && is_checkout() ){
            Kueski_Gateway_Helper::error(
                "Not available: Error en merchant data".
                Kueski_Gateway_Helper::serialize($merchant_data)
            );
        }
        
        return $available;
    }

    public function client_key_invalid_message() {
        echo '<div class="error"><p><strong>Kueski Desactivado: </strong>El Public Key es inválido</p></div>';
    }

    protected function run_admin_validators()
    {
        $is_available = $this->api->wp_validate_keys();
        if( !$is_available ){
            add_action('admin_notices', array($this, 'client_key_invalid_message'));
        }   
    }

    public function process_admin_options()
    {
        return parent::process_admin_options();
    }

    public function init_form_fields()
    {
        $this->form_fields = include plugin_dir_path(dirname(__FILE__)) . 'admin/data-settings-kueski.php';
    }

    public function process_payment($order_id)
    {
        Kueski_Gateway_Helper::debug(
            "========== Process order ============"
        );
        $success = false;
        $order = wc_get_order($order_id);
        $currency = get_woocommerce_currency();
        
        $merchant_data = $this->api->get_merchant_data();
        if($merchant_data != false){
            $data = $this->order->getOrderPayload($order, $merchant_data);
            if( $data ){
                $payment = $this->api->wp_create_order($data);
            }
        }
        
        Kueski_Gateway_Helper::debug(
                    "createOrder RAW RESPONSE ". 
                    Kueski_Gateway_Helper::serialize($payment)
                );

        if( !isset($payment->status) || $payment->status != "success" ){
            $error_message = isset($payment->data) ? $payment->data : __('Error de comunicación con Kueski Pay', 'kueskipay-gateway');
            Kueski_Gateway_Helper::error(
                "Create Order: ".
                Kueski_Gateway_Helper::serialize($error_message)
            );
            throw new WC_Data_Exception('payment_error', __('Error de comunicación con Kueski Pay', 'kueskipay-gateway') );
        }
        
        //Set order status
        try{
            $order->update_meta_data('kp_payment_id', $payment->data->payment_id);
        }catch( Exception $e ){
            Kueski_Gateway_Helper::debug($e->getMessage());
        }
        
        $order->update_status('pending', __( 'Kueski: Waiting for payment.', 'kueskipay-gateway' ));
        
        return array(
            'result'   => 'success',
            'redirect' => $payment->data->callback_url,
        );
        
    }

    public function generate_html_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults  = array(
            'title'             => '',
            'type'              => 'html',
            'description'       => '',
        );

        $data = wp_parse_args($data, $defaults);

        ob_start();
?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
            </th>
            <td class="forminp">
                <?php echo wp_kses_post($data['description']); ?>
            </td>
        </tr>
<?php

        return ob_get_clean();
    }
}
