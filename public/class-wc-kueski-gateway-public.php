<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The public functionality of the plugin.
 *
 * Defines the plugin name, version
 * and enqueue the adminstylesheet and JavaScript.
 * 
 **/

class Kueski_Gateway_Public
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

    /**
     * The plugin settings.
     *
     * @access   private
     * @var      array    $settings
     */
    private $settings;

    private $conciliator;

    public function __construct($plugin_name, $version, $settings)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = $settings->get_settings();
        $this->conciliator = new Kueski_Gateway_Conciliator( $this->settings );
    }

    public function enqueue_block_scripts(){
        wp_enqueue_style(
            'kp-widget-cart-style',
            plugins_url('src/style.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'src/style.css')
        );
    }

    public function register_widget_block(){
        register_block_type('kp-widget-cart/kp-widget', array(
            'render_callback' => array($this, 'render_widget_block')
        ));
    }

    public function render_widget_block(){
        return '<div class="kp-widget-cart">
            </div>';
    }

    public function display_widget_block($block_content, $block){

        if($this->detect_is_block_template()){
            $cart_color_scheme = $this->settings['color_scheme_cart'];
            $font_size = $this->settings['font_size'];
            $text_aling_cart = $this->settings['text_align_cart'];

            $cart_block = $this->settings['cart_widget_block'];
            $cart_custom_block = $this->settings['cart_widget_custom_block'];
            $cart_block_name = trim($cart_custom_block) === '' ? $cart_block : $cart_custom_block;

            if ( $cart_block_name === $block['blockName'] ) {
                $banner_content = '
                    <div class="row">
                    <div class="col-md-12 woo-kueskipay-widget">
                    <kueskipay-widget
                         data-kpay-widget-theme="blocks"
                         data-kpay-widget-type="cart"
                         data-kpay-color-scheme="'.$cart_color_scheme.'"
                         data-kpay-widget-font-size="'.$font_size.'"
                         data-kpay-widget-text-align="'.$text_aling_cart.'"
                         >
                    </kueskipay-widget>
                    </div>
                    </div>
                    ';
                $block_content .= $banner_content;
            }

            $product_color_scheme = $this->settings['color_scheme'];
            $product_text_aling = $this->settings['text_align'];
            $product_block = $this->settings['product_widget_block'];
            $product_custom_block = $this->settings['product_widget_custom_block'];
            $product_block_name = trim($product_custom_block) === '' ? $product_block : $product_custom_block;

            if ( $product_block_name === $block['blockName'] ) {
                $banner_content = '
                    <div class="row">
                    <div class="col-md-12 woo-kueskipay-widget">
                    <kueskipay-widget
                         data-kpay-widget-theme="blocks"
                         data-kpay-widget-type="product"
                         data-kpay-color-scheme="'.$product_color_scheme.'"
                         data-kpay-widget-font-size="'.$font_size.'"
                         data-kpay-widget-text-align="'.$product_text_aling.'"
                         >
                    </kueskipay-widget>
                    </div>
                    </div>
                    ';
                $block_content .= $banner_content;
            }

        }

        return $block_content;
    }

    public function detect_is_block_template(){
        // Verificar si la página del carrito está construida con bloques
        if (is_cart()) {
            $post = get_post(wc_get_page_id('cart'));
            if (has_blocks($post->post_content)) {
                return true;
            }
        }
        
        // Verificar si la página del producto está construida con bloques
        if (is_product()) {
            global $post;
            if (has_blocks($post->post_content)) {
                return true;
            }
        }

        return false;
    }

    public function display_widget_product()
    {
        global $product;
        
        $price = 0;
        if ( function_exists( 'wc_get_price_to_display' ) ) {
            $price = wc_get_price_to_display( $product );
        } else {
            $display_price         = $product->get_display_price();
            $display_regular_price = $product->get_display_price( $product->get_regular_price() );
            if ( $display_regular_price > 0 ) {
                $price = $display_regular_price;
            } else {
                $price = $display_price;
            }
        }
        
        $this->display_widget($price, 'product', $product->get_title());
    }

    public function display_widget_cart()
    {
        $this->display_widget(WC()->cart->total);
    }

    public function kueski_conciliation_endpoint()
    {

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ 'kp_in_cociliate_nonce' ] ) ), 'kp_in_cociliate' ) ) {
            return;
        }

        $conciliation = isset( $_GET['kp-conciliation'] ) ? sanitize_text_field($_GET['kp-conciliation']) : null;
        $conciliation = esc_attr($conciliation);

        if($conciliation){
            $encoded_order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : null;
            $encoded_order_id = esc_attr($encoded_order_id);
            $order_status = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : null;
            $order_status = esc_attr($order_status);
            $this->conciliator->conciliate($encoded_order_id, $order_status);
        }
    }
    
    public function display_widget($price, $type = 'cart', $name = false)
    {
        $price = (int)($price * 100.0);
        if ($type == 'product') {
            $color_schema = $this->settings['color_scheme'];
            $text_aling = $this->settings['text_align'];
        } else if ($type == 'cart') {
            $color_schema = $this->settings['color_scheme_cart'];
            $text_aling = $this->settings['text_align_cart'];
        }
        $font_size = $this->settings['font_size'];
        
?> 
        <div class="row">
            <div class="col-md-12 woo-kueskipay-widget">
                <kueskipay-widget 
                    data-kpay-widget-theme="classic"
                    data-kpay-widget-amount="<?php echo esc_attr($price); ?>" 
                    data-kpay-widget-type="<?php echo esc_attr($type); ?>" 
                    data-kpay-color-scheme="<?php echo esc_attr($color_schema); ?>" 
                    data-kpay-widget-font-size="<?php echo esc_attr($font_size); ?>" 
                    data-kpay-widget-text-align="<?php echo esc_attr($text_aling); ?>" 
                <?php
                if ($type == 'product') {
                    echo 'data-kpay-widget-product-name="'.esc_attr($name).'"';
                }
                ?>
                ></kueskipay-widget>
            </div>
        </div>
    <?php
    }

    public function add_widget_init()
    {
        $sandbox = $this->settings['is_sandbox'] ? '&sandbox=true' : '';
        $client_id = $this->settings['client_id'];
    ?>
        <script id="kpay-advertising-script"
						src="https://cdn.kueskipay.com/widgets.js?authorization=<?php echo esc_attr($client_id.$sandbox); ?>&integration=woocommerce&version=v<?php echo esc_attr($this->version); ?>">
						</script>

        <script type="">
            addEventListener("DOMContentLoaded", function(){
                setTimeout(function() {
                    let kpayWidget = document.querySelector('kueskipay-widget');
                    let kpayScript = document.getElementById('kpay-advertising-script');
                    if( kpayWidget && kpayScript ){
                        new KueskipayAdvertising().init();
                    }
                }, 1000)
            });
        </script>
<?php
    }
}
