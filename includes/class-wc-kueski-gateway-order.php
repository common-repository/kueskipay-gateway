<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Kueski_Gateway_Order
{

    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }


    public function getOrderPayload($order, $merchant_data)
    {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $total = $order->get_total();
        $data = array();
        $currency = get_woocommerce_currency();
        
        if (
            $total <= (float)$merchant_data['data']['merchant_limits']['min_amount'] ||
            $total >= (float)$merchant_data['data']['merchant_limits']['max_amount']
        ){
            return false;
        }

        
        $details = $this->get_details_from_order($order);

        $data = array(
            'description' => 'Orden Nro ' . $order_id,
            'order_id' => (string) $order_id,
            "items" => $details['items'],
            "amount" => array(
                'total' => (float)$details['order_total'],
                "currency" => $currency,
                "details" => array(
                    'subtotal' => (float)$details['total_item_amount'],
                    'shipping' => (float)$details['shipping'],
                    'tax' => (float)$details['order_tax'],
                    'handling_fee' => (float)$details['discounts'],
                    'discount' => (float)$details['discounts']
                )
            ),
        );

        $shipping_address = $this->getShippingAddress( $order );
        if ($shipping_address) {
            $data['shipping'] = $shipping_address;
        }

        $callbacks = $this->create_callbacks($order);
        $data['callbacks'] = $callbacks;

        $order = $data;
        $order = array(
            'order' => $data
        );
        
        Kueski_Gateway_Helper::debug(
            "Result payment data: ".
            Kueski_Gateway_Helper::serialize($order, true)
        );

        return $order;
    }

    public function create_callbacks($order)
    {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        
        $url = get_home_url();
        $encrypted_order_id = Kueski_Gateway_Encryptor::encryptData($order_id, $this->settings['client_id']);
        $url = $url.'?kp-conciliation=true&order_id='.$encrypted_order_id;
        $nonce = wp_create_nonce('kp_in_cociliate');
        $nonced_url = add_query_arg('kp_in_cociliate_nonce', $nonce, $url);

        $callbacks = array(
            'on_success' => $nonced_url.'&payment_status=success',
            'on_reject' => $nonced_url.'&payment_status=reject',
            'on_canceled' => $nonced_url.'&payment_status=canceled',
            'on_failed' => $nonced_url.'&payment_status=failed'
        );

        return $callbacks;
    }

    protected function get_details_from_order($order)
    {
        $details = array();
        $decimals = $this->settings['decimals'];
        $total = $order->get_total() - $order->get_shipping_total();
        $rounded_total = $this->get_rounded_total_in_order($order, $total);

        $details = array(
            'order_tax'         => round($rounded_total['tax'], $decimals),
            'shipping'          => round($order->get_shipping_total(), $decimals),
            'discounts'         => 0,
            'total_item_amount' => round($rounded_total['total'], $decimals),
            'items'             => $this->get_line_items_from_order($order, $total),
        );
        $details['order_total'] = round( $details['total_item_amount'] + $details['order_tax'] + $details['shipping'] - $details['discounts'], $decimals );


        // Compare WC totals with what Kueski will calculate to see if they match.
        $wc_order_total = round( $order->get_total(), $decimals );
        if ( (float) $wc_order_total !== (float) $details['order_total'] ) {
            $details['discounts'] += round( $details['order_total'], $decimals ) - $wc_order_total;
            $details['discounts'] = round( $details['discounts'], $decimals );
            $details['order_total'] = $wc_order_total;
        }
        
        return $details;
    }

    protected function get_rounded_total_in_order($order, $ototal)
    {
        $decimals = $this->settings['decimals'];
        $total = 0;
        $total_tax = 0;
        foreach ($order->get_items() as $cart_item_key => $values) {
            $amount     =  round($values['line_subtotal'] / $values['qty'], $decimals);
            $total      += round($amount * $values['qty'], $decimals);
            $total_tax  += round($values['line_subtotal_tax'], $decimals);
        }
        $discount_total = ($total + $total_tax) - $ototal;
        $dtotal = $discount_total * (1.0 - $total_tax / $total);
        $dtax = $discount_total * ($total_tax / $total);
        $rounded_total = 0;
        $rounded_total_tax = 0;
        foreach ($order->get_items() as $cart_item_key => $values) {
            $d  = $dtotal * ($values['line_subtotal'] / $total);
            $amount             =  $values['line_subtotal'];
            $amount             -= $d;
            $amount             =  round($amount / $values['qty'], $decimals);
            $rounded_total      += round($amount * $values['qty'], $decimals);

            $d = $total_tax > 0 ? ($dtax * ($values['line_subtotal_tax'] / $total_tax)) : 0.0;
            $amount             = $values['line_subtotal_tax'];
            $amount             -= $d;
            $rounded_total_tax  += round($amount, $decimals);
        }
        return array('total' => $rounded_total, 'tax' => $rounded_total_tax);
    }

    protected function get_line_items_from_order($order, $ototal)
    {
        $decimals = $this->settings['decimals'];
        $items = array();
        $currency = get_woocommerce_currency();
        $total = 0;
        $total_tax = 0;
        foreach ($order->get_items() as $cart_item_key => $values) {
            $amount     =  round($values['line_subtotal'] / $values['qty'], $decimals);
            $total      += round($amount * $values['qty'], $decimals);
            $total_tax  += round($values['line_subtotal_tax'], $decimals);
        }
        $discount_total = ($total + $total_tax) - $ototal;
        $dtotal = $discount_total * (1.0 - $total_tax / $total);
        $dtax = $discount_total * ($total_tax / $total);
        $rounded_total = 0;
        $rounded_total_tax = 0;
        foreach ($order->get_items() as $cart_item_key => $values) {
            $d  = $dtotal * ($values['line_subtotal'] / $total);
            $amount             =  $values['line_subtotal'];
            $amount             -= $d;
            $rounded_total      =  round($amount / $values['qty'], $decimals);

            $d = $total_tax > 0 ? ($dtax * ($values['line_subtotal_tax'] / $total_tax)) : 0.0;
            $amount             = $values['line_subtotal_tax'];
            $amount             -= $d;
            $rounded_total_tax  = round($amount, $decimals);

            if ((int)$values['variation_id'] > 0) {
                $product = new WC_Product_Variation($values['variation_id']);
                $description = trim(wp_strip_all_tags($product->get_short_description()));
                if (empty($description)) {
                    $product2 = new WC_Product($values['product_id']);
                    $description = trim(wp_strip_all_tags($product2->get_short_description()));
                }
            } else {
                $product = new WC_Product($values['product_id']);
                $description = trim(wp_strip_all_tags($product->get_short_description()));
            }
            $item = new stdClass;
            $item->name = $values['name'];
            $item->description = !$description || empty($description) ? $values['name'] : $description;

            $special_chars = '/[<>&\[\]]/';
            if( preg_match( $special_chars, $item->description ) ){
                $item->description = $values['name'];
            }
            $item->description = str_replace(
                ['"',"\n", "\r", "  "],
                '',
                $item->description
            );
            $item->description = preg_replace(
                '/\b\w+=/',
                '',
                $item->description
            );
            $item->description = str_replace(
                array('á', 'Á', 'é', 'É', 'í', 'Í', 'ó', 'Ó', 'ú', 'Ú', 'ñ', 'Ñ'),
                array('a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U', 'n', 'N'),
                $item->description
            );
            $item->description = iconv("UTF-8", "ISO-8859-5//IGNORE", $item->description);
            if (strlen($item->description) > 128) {
                $item->description = substr($item->description, 0, 124) . '...';
            }
            $item->quantity = (int)$values['qty'];
            $item->price = $rounded_total;
            $item->currency = $currency;
            $item->sku = $product->get_sku();
            
            $items[] = $item;
        }

        return $items;
    }

    private function getShippingAddress($order) {
        $address_kueski = new stdClass;
        $address_kueski->name = new stdClass;
        $address_kueski->address = new stdClass;
        $address_kueski->name->name = $order->get_shipping_first_name();
        $address_kueski->name->last = $order->get_shipping_last_name();
        $address_kueski->address->address = $order->get_shipping_address_1();
        if( $order->get_shipping_address_2() != '')
        {
            $address_kueski->address->interior = $order->get_shipping_address_2();
        }
        $address_kueski->address->city = $order->get_shipping_city();
        $address_kueski->address->state = $order->get_shipping_state();
        $address_kueski->address->zipcode = $order->get_shipping_postcode();
        $address_kueski->address->country = $order->get_shipping_country();
        $address_kueski->phone_number = $order->get_billing_phone();
        $address_kueski->email = $order->get_billing_email();
        $valid_address = $this->validate_object_properties($address_kueski);

        if (!$valid_address) {
            return false;
        }

        return $address_kueski;
    }

    private function validate_object_properties($address) {
        $is_valid = true;

        foreach ($address as $key => $value) {
            if (is_object($value)) {
                if (!$this->validate_object_properties($value)) {
                    $is_valid = false;
                    break;
                }
            } else {
                if (!$this->validate_property($address, $key)) {
                    $is_valid = false;
                    break;
                }
            }
        }

        return $is_valid;
    }

    private function validate_property($object, $key){
        $value = $object->$key;

        if (is_null($value) || $value == ''){
            return false;
        }else {
            return true;
        }

    }
}
