<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Kueski_Gateway_CronManager
{
    private $plugin_name;
    private $plugin_version;
    private $settings;
    private $api;

    public function __construct($plugin_name, $version, $settings)
    {
        $this->plugin_name = $plugin_name;
        $this->plugin_version = $version;
        $this->settings = $settings->get_settings();
        $this->api = new Kueski_Gateway_Api($this->settings);
    }
    
    public function ordersSync(){
        $args = array(
            'payment_method' => 'kueski-gateway',
            'status' => 'pending',
            // 'status' => 'wait-kueski-payment',
            'limit' => 3,
        );
        $orders = wc_get_orders( $args );
        if( count( $orders ) == 0){
            return;
        }

        $payments = array();
        $order_collection = array();
        foreach ($orders as $order) {
            $order_id  = $order->get_id();
            $payment_id = $order->get_meta('kp_payment_id');
            if($payment_id!=''){
                array_push($payments, $payment_id);
            }
            $order_collection[$order_id] = $payment_id;
        }

        if( count($payments) == 0 ){
            return;
        }
        $payment_ids = implode(',', $payments);
        $response = $this->api->wp_orders_sync($payment_ids);

        if($response['status'] == 'success')
        {
            $payment_status = $response['data'];
            foreach ($orders as $order) {
                $order_id  = $order->get_id();
                $payment_id = $order_collection[$order_id];
                $status = $payment_status[$payment_id];
                switch ( $status ) {
                    case 'canceled':
                    case 'reject':
                    case 'denied':
                        $new_status = 'cancelled';
                        break;
                    case 'failed':
                        $new_status = 'failed';
                        break;
                }

                if( $status == 'approved'){
                    $order->add_order_note( __( 'Kueski: Payment approved.', 'kueskipay-gateway' ) );
                    $order->payment_complete();
                }else{
                    if( $new_status != '' ){
                        $payment_title = $order->get_payment_method();
                        if( $payment_title == 'kueski-gateway' ){
                            $order->update_status( $new_status, __( 'Kueski: Transacción cancelada.', 'kueskipay-gateway' ) );
                            wc_add_notice(__('Tu pedido ha sido cancelado.', 'kueskipay-gateway'));
                        }
                    }
                }
            }

        }

    }
    
    public function activate(){

    }

    public function deactivate(){

    }
}