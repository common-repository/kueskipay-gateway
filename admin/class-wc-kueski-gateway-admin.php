<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The admin functionality of the plugin.
 *
 * Defines the plugin name, version
 * and enqueue the adminstylesheet and JavaScript.
 * 
 **/

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class Kueski_Gateway_Admin
{
    /**
     * The plugin ID.
     *
     * @access   private
     * @var      string    $plugin_name
     */
    private $plugin_name;

    /**
     * The plugin version.
     *
     * @access   private
     * @var      string    $version
     */
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Add the Kueski Gateway to WC Available Gateways
     */
    function wc_kueski_add_gateways($gateways)
    {
        $gateways[] = 'Kueski_Gateway';
        return $gateways;
    }
    /**
     * Require Kueski Gateway Class
     */
    function kueski_gateway_init()
    {
        require_once KUESKI_PATH_ORDER . 'includes/class-wc-kueski-gateway.php';
    }

    public function kueski_links_below_title_begin($links)
    {
        $url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=kueski-gateway'));
        $settings_link = "<a href='$url'>".__('Setting', 'kueskipay-gateway')."</a>";

        array_unshift($links,$settings_link);
        return $links;
    }

    public function kp_add_meta_boxes(){
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id( 'shop-order' )
        : 'shop_order';

        add_meta_box(
            'kp-metabox-payment-id',
            __( 'Kueski Information', 'kueskipay-gateway' ),
            array($this, 'metabox_payment_id'),
            $screen,
            'side',
            'high'
        );
    }

    public function metabox_payment_id($post){
        $order_id = $post->ID;
        $order = wc_get_order($order_id);

        if(!$order){
            echo wp_kses_post('<p>Error al obtener la orden</p>');
            return;
        }
        $payment_title = $order->get_payment_method();
        if( $payment_title != 'kueski-gateway' ){
            echo wp_kses_post(__('La orden no fue realizada con Kueski Pay', 'kueskipay-gateway'));
            return;
        }
        $payment_id = $order->get_meta('kp_payment_id');
        echo wp_kses_post( '<p><strong>'.__( 'Kueski Transaction ID', 'kueskipay-gateway' ).'</strong></p>' );
        echo wp_kses_post( $payment_id );
    }
    
}
