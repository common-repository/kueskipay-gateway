<?php
if ( ! defined( 'ABSPATH' ) ) exit;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Kueski_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'kueski-gateway';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_kueski-gateway_settings', [] );
        $this->gateway = new Kueski_Gateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        $version = defined('KUESKI_GATEWAY_VERSION') ? KUESKI_GATEWAY_VERSION : '1.0.0';
        wp_register_script(
            'kueski-gateway-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            $version,
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'kueski-gateway-blocks-integration');
            
        }
        wp_localize_script('kueski-gateway-blocks-integration','kueski_gateway_data',[
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => isset($this->settings['supports'])?$this->settings['supports']:[]
        ]);
        return [ 'kueski-gateway-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'plugin_url' => plugin_dir_url(__DIR__),
        ];
    }

}
?>