<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (defined('PHP_SESSION_NONE') && session_id() == PHP_SESSION_NONE || session_id() == '') {
    session_start();
}
$color_schema = array("black" => "Negro", "white" => "Blanco");
$cart_block_position = array(
    'woocommerce/cart-order-summary-block' => 'Summary',
    'woocommerce/cart-order-summary-taxes-block' => 'Taxes',
    'woocommerce/proceed-to-checkout-block' => 'Checkout',
    'woocommerce/cart-order-summary-subtotal-block' => 'Subtotal',
    'woocommerce/cart' => 'Bottom'
);
$product_block_position = array(
    'woocommerce/product-price' => 'Price',
    'woocommerce/product-title' => 'Title',
    'woocommerce/product-add-to-cart' => 'Add to Cart'
);

$product_widget_position = array(
    'woocommerce_before_add_to_cart_form' => 'Price',
    'woocommerce_product_meta_end' => 'After description',
    'woocommerce_single_product_summary' => 'Meta info',
    'woocommerce_after_single_product_summary' => 'Bottom'
);

$font_size = array("12" => "12", "14" => "14", "16" => "16");
$text_aling = array("left" => "Izquierda", "right" => "Derecha", "center" => "Centro");
$img_src = plugins_url('images/KP_WooCommerce_banner.jpg', dirname(__FILE__, 1));
return array(
    'img' => array(
        'title' => '',
        'type' => 'html',
        'description' => '
			<a href="https://www.kueskipay.com/para-comercios/como-funciona?utm_source%3Dwocmce%26utm_medium%3Do_web%26utm_campaign%3Dpro_bis_prt__20_2_2023%26utm_term%3Dcnn%26utm_content%3Dnh%23form-registro-merchant&hl=es&sa=D&source=meet" target="_blank">
				<img style="max-width:100%;" src="'.$img_src.'" >
			</a>',
    ),
    'enabled' => array(
        'title' => __('Activate', 'kueskipay-gateway'),
        'type' => 'checkbox',
        'label' => __('Activate Kueski', 'kueskipay-gateway'),
        'default' => 'yes',
    ),
    'client_id' => array(
        'title' => __('Kueski API Key', 'kueskipay-gateway'),
        'type' => 'text',
        'description' => __('Input the Public Key of Kueski.', 'kueskipay-gateway'),
        'default' => '',
    ),
    'mp_completed' => array(
        'title' => __('Leave orders with payment Accepted in Completed', 'kueskipay-gateway'),
        'type' => 'checkbox',
        'label' => __('Active', 'kueskipay-gateway'),
        'default' => 'no',
        'description' => __('When the payment is approved, the order in WooCommerce will not remain in Processing but in Completed.', 'kueskipay-gateway'),
    ),
    'sandbox' => array(
        'title' => __('Sandbox Mode', 'kueskipay-gateway'),
        'type' => 'checkbox',
        'label' => __('In Sandbox mode you must change the credentials to those of a Sandbox project.', 'kueskipay-gateway'),
        'default' => 'yes',
    ),
    'title' => array(
        'title' => 'Configuración de widgets',
        'type' => 'html',
        'description' => '
                <hr border: 1px solid #ccc;>
            ',
    ),
    'color_scheme' => array(
        'title' => __('Color del widget producto', 'kueskipay-gateway'),
        'type' => 'select',
        'options' => $color_schema,
        'description' => __('Selecciona el color del widget en la página de producto.', 'kueskipay-gateway'),
    ),
    'text_align' => array(
        'title' => __('Alineación del widget de producto.', 'kueskipay-gateway'),
        'type' => 'select',
        'options' => $text_aling,
        'description' => __('Selecciona la alineación del texto.', 'kueskipay-gateway'),
    ),
    'product_widget_position' => array(
        'title' => __('Ubicación de widget producto', 'kueskipay-gateway'),
        'type' => 'select',
        'options' => $product_widget_position,
        'description' => __('Ubicación del widget en temas clásicos.', 'kueskipay-gateway'),
    ),
    'product_widget_block' => array(
        'title' => __('Bloque de widget producto', 'kueskipay-gateway'),
        'type' => 'select',
        'options' => $product_block_position,
        'description' => __('Ubicación del widget en temas de Bloques.', 'kueskipay-gateway'),
    ),
    'product_widget_custom_block' => array(
        'title' => __('Bloque personalizado product', 'kueskipay-gateway'),
        'type' => 'text',
        'description' => __('Ingresa un bloque personalizado.', 'kueskipay-gateway'),
        'default' => '',
    ),
    'color_scheme_cart' => array(
        'title' => __('Color del widget carrito', 'kueskipay-gateway'),
        'type' => 'select',
        'options' => $color_schema,
        'description' => __('Selecciona el color del widget en el carrito', 'kueskipay-gateway'),
    ),
    'text_align_cart' => array(
        'title' => __('Alineación del widget de carrito', 'kueskipay-gateway'),
        'type' => 'select',
        'options' => $text_aling,
        'description' => __('Selecciona la alineación del texto.', 'kueskipay-gateway'),
    ),
    'cart_widget_block' => array(
        'title' => __('Bloque de widget carrito', 'kueskipay-gateway'),
        'type' => 'select',
        'options' => $cart_block_position,
        'description' => __('Bloque donde se muestra el widget del carrito', 'kueskipay-gateway'),
    ),
    'cart_widget_custom_block' => array(
        'title' => __('Bloque personalizado carrito', 'kueskipay-gateway'),
        'type' => 'text',
        'description' => __('Ingresa un bloque personalizado.', 'kueskipay-gateway'),
        'default' => '',
    ),
    'font_size' => array(
        'title' => __('Tamaño de la fuente', 'kueskipay-gateway'),
        'type' => 'select',
        'options' => $font_size,
        'description' => __('Selecciona el tamaño de fuente del widget.', 'kueskipay-gateway'),
    ),
    
);
