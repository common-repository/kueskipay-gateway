<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class Kueski_Gateway_Refund 
{
    private $settings;
    private $api;

    public function __construct($settings)
    {
        $this->settings = $settings->get_settings();
        $this->api = new Kueski_Gateway_Api($this->settings);
    }

    public function kueski_add_refund_metabox(){
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id( 'shop-order' )
        : 'shop_order';

        add_meta_box(
            'kueski-metabox-refund',
            __( 'Reembolso Kueski', 'kueskipay-gateway'),
            array($this, 'kueski_render_metabox_refund'),
            $screen,
            'normal',
            'default'
        );
    }

    public function kueski_render_metabox_refund($post){
        $order = wc_get_order($post->ID);

        $payment_title = $order->get_payment_method();
        if( $payment_title != 'kueski-gateway' ){
            echo wp_kses_post(__('La orden no fue realizada con Kueski Pay', 'kueskipay-gateway'));
            return;
        }

        $refunds = $order->get_refunds();
        $refunded_quantities = array();
        $shipping_refunded = false;

        foreach($refunds as $refund) {
            
            if( $refund->get_meta('kueski_shipping_refunded') ){
                $shipping_refunded = true;
            }

            foreach( $refund->get_items() as $refunded_item_id => $refunded_item) {
                $product_id = $refunded_item->get_product_id();
                $refunded_quantity = abs($refunded_item->get_quantity());

                if(isset( $refunded_quantities[$product_id] )){
                    $refunded_quantities[$product_id] += $refunded_quantity;
                } else {
                    $refunded_quantities[$product_id] = $refunded_quantity;
                }
            }
        }

        $order_total = $order->get_total();
        $refunded_total = $order->get_total_refunded();
        $payment_id = $order->get_meta('kp_payment_id');
        $remaining_amount = $order_total - $refunded_total;

        $shipping_total = $order->get_shipping_total();
        $shipping_tax = $order->get_shipping_tax();
        $shipping_method = $order->get_shipping_method();
        $shipping_cost = $shipping_total + $shipping_tax;
        $shipping_disabled = ($shipping_refunded || $shipping_total <= 0) ? 'disabled' : '';
        $shipping_checked = $shipping_refunded ? 'checked' : '';

        ?>
        <table class="kueski-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Producto', 'kueskipay-gateway') ?></th>
                    <th><?php esc_html_e('Precio', 'kueskipay-gateway') ?></th>
                    <th><?php esc_html_e('Cantidad', 'kueskipay-gateway') ?></th>
                    <th><?php esc_html_e('Cantidad a reembolsar', 'kueskipay-gateway') ?></th>
                    <th><?php esc_html_e('Total', 'kueskipay-gateway') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach($order->get_items() as $item_id => $item) {
                    $product_id = $item['product_id'];
                    $product = wc_get_product($product_id);
                    $product_name = $product->get_name();
                    $product_sku = $product->get_sku() ? '<strong>SKU:</strong>' . $product->get_sku() : '';
                    $quantity = $item['qty'];
                    $total = $item['total'];
                    $unit_price = $total / $quantity;
                    $thumbnail = $product->get_image('thumbnail');

                    $refunded_quantity = isset($refunded_quantities[$product_id]) ? $refunded_quantities[$product_id] : 0;
                    $available_refund_qty = $quantity - $refunded_quantity;
                    $disabled = $available_refund_qty <= 0 ? 'disabled' : '';

                    $refunded_total = $order->get_total_refunded();
                    ?>
                    <tr>
                        <td class="column-primary">
                            <div class="kueski-order-item-thumbnail">
                                <?php echo wp_kses_post($thumbnail);?>
                            </div>
                            <div class="kueski-order-item-name">
                                <?php echo esc_html($product_name);?>
                                <br><small><?php echo esc_attr($product_sku); ?></small>
                            </div>
                        </td>
                        <td><?php echo wc_price($unit_price);?></td>
                        <td>
                            <?php echo esc_attr($quantity);?>
                        </td>
                        <td>
                            <input type="number" name="kueski_quantity[<?php echo esc_attr($item_id);?>]"
                                    value="0" min="0" max="<?php echo esc_attr($available_refund_qty);?>" 
                                    data-item-id="<?php echo esc_attr($item_id);?>"
                                    data-item-price="<?php echo esc_attr($unit_price); ?>"
                                    class="kueski-refund-qty"
                                    <?php echo esc_attr($disabled);?>
                            >
                            <p><small class="kueski-refunded-qty">
                            
                                <?php echo esc_html_e('Reembolsados: ', 'kueskipay-gateway') . esc_attr($refunded_quantity);?>
                            </small></p>
                        </td>
                        <td id="kueski-item-total-<?php echo esc_attr($item_id);?>">
                            <?php echo wc_price(0);?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <tr class="shipping">
                    <td class="column-primary">
                        <div class="kueski-order-item-name">
                            <?php echo esc_html_e('Envío:', 'kueskipay-gateway')?>
                            <br><small><?php echo esc_attr($shipping_method);?></small>
                        </div>
                    </td>
                    <td></td>
                    <td></td>
                    <td>
                        <label for="kueski_refund_shipping"><?php echo esc_html_e('Reembolsar envío', 'kueskipay-gateway');?></label>
                        <input type="checkbox" <?php echo esc_attr($shipping_checked); ?> <?php echo esc_attr($shipping_disabled); ?> 
                            id="kueski_refund_shipping" value="1" data-shipping-cost="<?php echo esc_attr($shipping_cost);?>"
                            data-shipping-refunded="<?php echo esc_attr($shipping_refunded); ?>"
                            >
                    </td>
                    <td><?php echo wc_price($shipping_cost);?></td>
                </tr>
            </tbody>
        </table>
        <div class="kueski-order-refund-total">
            <input type="hidden" id="kueski_refund_payment_id" value="<?php echo esc_attr( $payment_id );?>">
            <label for="kueski_refund_total">
                <?php echo esc_html_e('Total a reembolsar:', 'kueskipay-gateway');?>
            </label>
            <input type="text" id="kueski_refund_total" name="kueski_refund_total" value="0" readonly>
        </div>
        <div id="kueski-refund-message"></div>
        <div class="kueski-order-refund-summary">
            <p class="kueski-refunded-amount"><strong><?php echo esc_html_e('Total reembolsado: ', 'kueskipay-gateway');?></strong><?php echo wc_price($refunded_total);?></p>
            <?php if($shipping_refunded):?>
                <p><strong><?php echo esc_html_e('Envío reembolsado', 'kueskipay-gateway');?></strong></p>
            <?php endif;?>
            <p><strong><?php echo esc_html_e('Monto restante: ', 'kueskipay-gateway');?></strong><?php echo wc_price($remaining_amount);?></p>
            <hr>
            <h3><?php echo esc_html_e('Realizar reembolso', 'kueskipay-gateway');?></h3>
            <div id="kueski-loading-refund" class="spinner"></div>
            <div class="kueski-refund-actions">
                <button type="button" class="button button-primary" id="kueski_refund_button">Aceptar</button>
            </div>
        </div>
        
        <?php


    }

    public function kueski_refund_enqueue_scripts($hook){

        $order_screens = ['woocommerce_page_wc-orders','shop_order'];
        $screen = get_current_screen();
        $screen_id = $screen->id;

        if( !in_array($screen_id, $order_screens) ){
            return;
        }
        
        wp_enqueue_style('kueski_refund_style', plugin_dir_url(__FILE__).'../assets/css/kueski_refund.css?v='.$this->settings['kp_version']);

        wp_enqueue_script('kueski_refund_script', plugin_dir_url(__FILE__).'../assets/js/kueski_refund.js?v='.$this->settings['kp_version'],
                                array('jquery'), null, true);
        
        wp_localize_script('kueski_refund_script', 'kueski_refund_vars', array(
            'kueski_refund_nonce' => wp_create_nonce('kueski_process_refund_nonce')
        ));

    }

    public function kueski_process_refund_ajax() {
        // Validate nonce
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'kueski_refund_nonce' ] ) ), 'kueski_process_refund_nonce' ) ) {
            wp_send_json_error(array('message' => 'Nonce verification failed.'));
            wp_die();
        }

        $refund_amount = isset( $_POST['refund_amount'] ) ? sanitize_text_field($_POST['refund_amount']) : null;
        $refund_amount = (float) esc_attr($refund_amount);
        $payment_id = isset( $_POST['payment_id'] ) ? sanitize_text_field($_POST['payment_id']) : null;
        $payment_id = esc_attr($payment_id);
        $order_id = isset( $_POST['order_id'] ) ? sanitize_text_field($_POST['order_id']) : null;
        $order_id = esc_attr($order_id);
        $refund_shipping = isset( $_POST['refund_shipping'] ) ? sanitize_text_field($_POST['refund_shipping']) : null;
        $refund_shipping = esc_attr($refund_shipping);

        Kueski_Gateway_Helper::debug(
                    "\nRefund amount: " . $refund_amount .
                    "\nRefund Order Id: " . $order_id .
                    "\nRefund Payment Id: " . $refund_amount
                );

        // Kueski Pay refund
        $response = $this->api->kueskiRefundOrder($payment_id, $refund_amount);
        if( $response['status'] != 'success' ){
            wp_send_json_error(array('message' => $response['data']['message']));
        }
        
        $item_ids = isset( $_POST['item_ids'] ) ? array_map('intval', $_POST['item_ids']) : array();
        $quantities = isset( $_POST['quantities'] ) ? array_map('intval', $_POST['quantities']) : array();

        $order = wc_get_order($order_id);
        if(!$order) {
            wp_send_json_error(array('message' => 'no hay orden'));
        }

        $line_items = array();
        $total_items = 0;
        $totals = array();
        foreach( $item_ids as $item_id ){
            if(isset( $quantities[$item_id]) && $quantities[$item_id]>0){
                $item = $order->get_item($item_id);
                if( $item ){
                    $total_items++;
                    $total = $order->get_item_total($item) * $quantities[$item_id];
                    $totals[] = $total;
                    $line_items[$item_id] = array(
                        'qty' => $quantities[$item_id],
                        'refund_total' => wc_format_decimal($total),
                        'refund_tax' => array()
                    );
                }
            }
        }

        // Refund shipping
        $shipping_cost = $order->get_shipping_total();
        $shipping_tax_total = $order->get_shipping_tax();
        $shipping_tax_refund = array();

        // Calculate shipping taxes
        if($shipping_tax_total > 0){
            $shipping_items = $order->get_items('shipping');
            foreach($shipping_items as $shipping_item) {
                $taxes = $shipping_item->get_meta('taxes');
                if(!empty( $taxes )) {
                    $taxes = maybe_unserialize($taxes);
                    foreach($taxes as $tax_rate_id => $tax_amount){
                        if( !isset($shipping_tax_refund[$tax_rate_id]) ){
                            $shipping_tax_refund[$tax_rate_id] = wc_format_decimal($tax_amount);
                        }
                    }
                }
            }
        }
        
        if( $refund_shipping ) {
            $shipping_lines = array(
                array(
                    'method_id' => $order->get_shipping_method(),
                    'total' => wc_format_decimal($shipping_cost),
                    'taxes' => $shipping_tax_refund
                )
            );
        }else{
            $shipping_lines = array();
        }

        $refund_data = array(
            'amount' => wc_format_decimal($refund_amount),
            'reason' => 'Reembolso manual',
            'order_id' => $order_id,
            'refund_payment' => false,
            'line_items' => $line_items,
            'shipping_lines' => $shipping_lines
        );

        $refund = wc_create_refund($refund_data);

        if($refund_shipping) {
            $refund->update_meta_data('kueski_shipping_refunded', true);
            $refund->save();
        }

        if( is_wp_error($refund) ) {
            wp_send_json_error(
                array(
                    'message' => $refund->get_error_message()
                )
            );
        }

        $response = array(
            'message' => 'Reembolso procesado correctamente.',
            'total_itemssss' => $total_items,
            'refund_data' => $refund_data,
            'totals' => $totals,
            'order_id' => $order_id
        );
        wp_send_json_success($response);
    }
}