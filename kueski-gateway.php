<?php

/**
 * Plugin Name: KueskiPay Gateway 
 * Plugin URI: https://www.kueskipay.com/
 * Description: Kueski plugin for WooCommerce
 * Author: Kueski
 * Author URI: https://www.kueski.com/
 * Version: 2.3.3
 * License: GPL-2.0
 * Text Domain: kueskipay-gateway
 * Domain Path: /languages/
 * Requires at least: 6.1
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 7.6
 * WC tested up to: 8.9.3
 * WC custom order tables: yes
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if (!function_exists('get_plugin_data')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!defined('KUESKI_GATEWAY_VERSION')) {
    define('KUESKI_GATEWAY_VERSION', get_plugin_data(__FILE__)['Version']);
}
if (!defined('KUESKI_BASE_ORDER')) {
    define('KUESKI_BASE_ORDER', plugin_basename(__FILE__));
}

if (!defined('KUESKI_PATH_ORDER')) {
	define('KUESKI_PATH_ORDER', plugin_dir_path(__FILE__));
}

include(plugin_dir_path(__FILE__) . 'includes/class-wc-kueski.php');

function kueskipay_gateway_run()
{
    $plugin = new Kueski_Gateway_Payment();
    $plugin->run();
}
kueskipay_gateway_run();

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
 */
function kueski_cart_checkout_blocks_compatibility()
{
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

add_action('before_woocommerce_init', 'kueski_cart_checkout_blocks_compatibility');
add_action('woocommerce_blocks_loaded', 'kueski_register_order_approval_payment_method_type');

add_action('before_woocommerce_init', function(){
    if(class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class )){
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables', __FILE__, true
        );
    }
});

function kueski_register_order_approval_payment_method_type()
{
    // Check if the required class exists
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-kueski-gateway-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            // Register an instance of Kueski_Gateway_Blocks
            $payment_method_registry->register(new Kueski_Gateway_Blocks);
        }
    );
}
