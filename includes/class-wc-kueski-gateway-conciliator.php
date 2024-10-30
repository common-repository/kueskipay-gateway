<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Kueski_Gateway_Conciliator
{
    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function conciliate($encoded_order_id, $order_status)
    {
        $order_id = Kueski_Gateway_Encryptor::decryptData($encoded_order_id, $this->settings['client_id']);
        $order = wc_get_order($order_id);
        
        if ($order) {
            switch ($order_status) {
                case 'success':
                    $this->completeOrder($order);
                    break;
                case 'canceled':
                    $this->cancelOrder($order);
                    break;
                case 'reject':
                    $this->rejectOrder($order);
                    break;
                default:
                    $this->failOrder($order);
                    break;
            }
        } else {
            wc_add_notice(__('Número de orden no encontrado.', 'kueskipay-gateway'), 'error');
            $redirect_url = wc_get_cart_url();
            wp_redirect($redirect_url);
            exit();
        }
    }

    private function completeOrder($order)
    {
        $this->validateStatusAndPayment($order, 'pending', 'La orden no pudo ser completada.');
        $order->add_order_note( __( 'Kueski: Payment approved.', 'kueskipay-gateway' ) );
		$order->payment_complete();
        if( $this->settings['mp_completed'] == 'yes' )
        {
            $order->update_status( 'completed', __( 'Kueski: Order processed.', 'kueskipay-gateway' ) );
        }
        $order_received_url = $order->get_checkout_order_received_url();
        wp_redirect($order_received_url);
        exit;
    }

    private function cancelOrder($order)
    {
        $this->validateStatusAndPayment($order, 'pending', 'La orden no ha podido cancelarse.');
        $order->update_status('cancelled', __('Kueski: Transacción cancelada por el usuario.', 'kueskipay-gateway'));
        wc_add_notice(__('Tu pedido ha sido cancelado.', 'kueskipay-gateway'));
        $this->goToCart();
    }

    private function rejectOrder($order)
    {
        $this->validateStatusAndPayment($order, 'pending', 'La orden no ha podido cancelarse.');
        $order->update_status('cancelled', __('Kueski: El pago ha sido denegado.', 'kueskipay-gateway'));
        wc_add_notice(__('El pago no ha podido completarse.', 'kueskipay-gateway'), 'error');
        $this->goToCart();
    }

    private function failOrder($order)
    {
        $this->validateStatusAndPayment($order, 'pending', 'La orden no ha podido cancelarse.');
        $order->update_status('cancelled', __('Kueski: Ha ocurrido un error al procesar el pago.', 'kueskipay-gateway'));
        wc_add_notice(__('El pago no ha podido completarse.', 'kueskipay-gateway'), 'error');
        $this->goToCart();
    }

    private function goToCart(){
        $redirect_url = wc_get_cart_url();
        wp_redirect($redirect_url);
        exit();
    }

    private function validateStatusAndPayment($order, $status, $message){
        $method_is_kueski = $this->validatePaymentMethod($order);
        $status_is_valid = $this->validateStatus($order, $status);
        if( !$method_is_kueski || !$status_is_valid){
            wc_add_notice(esc_attr($message), 'error');
            $this->goToCart();
        }

        return true;
    }

    private function validatePaymentMethod( $order ){
        $payment_title = $order->get_payment_method();
        if( $payment_title == 'kueski-gateway' ){
            return true;
        }
        return false;
    }

    private function validateStatus( $order, $status ){
        $order_status = $order->get_status();
        $valid_status = false;
        if( is_array( $status ) ){
            foreach( $status as $stat ){
                if( $stat == $order_status ){
                    $valid_status = true;
                    break;
                }
            }
        }else{
            if( $order_status == $status ){
                $valid_status = true;
            }
        }

        return $valid_status;
    }
}
