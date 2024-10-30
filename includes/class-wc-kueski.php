<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin hooks
 * and public hooks.
 *
 **/

class Kueski_Gateway_Payment
{

    /** 
     * Class loader to registering all hooks
     * 
     * @access   protected
     * @var      Kueski_Gateway_Loader $loader
     */
    protected $loader;
    /** 
     * Class options which contains all the plugin settings
     * 
     * @access   protected
     * @var      Kueski_Gateway_Options $loader
     */
    protected $settings;
    /**
     * The plugin ID.
     *
     * @access   protected
     * @var      string    $plugin_name
     */
    protected $plugin_name;

    /**
     * The plugin version.
     *
     * @access   protected
     * @var      string    $version 
     */
    protected $version;


    public function __construct()
    {
        if (defined('KUESKI_GATEWAY_VERSION')) {
            $this->version = KUESKI_GATEWAY_VERSION;
        } else {
            $this->version = '1.0.0';
        }

        $this->plugin_name = 'kueski-gateway';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_cron_jobs();
        $this->define_refund_hooks();
    }

    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-kueski-gateway-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wc-kueski-gateway-public.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-helper.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-encryptor.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-order.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-conciliator.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-cron-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-kueski-gateway-rest-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kueski-gateway-refund.php';

        $this->loader = new Kueski_Gateway_Loader();
        $this->settings = new Kueski_Gateway_Settings();
    }

    private function set_locale()
    {
        $plugin_i18n = new Kueski_Gateway_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'copy_plugin_locale_files');
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        
    }

    private function define_admin_hooks()
    {
        $plugin_admin = new Kueski_Gateway_Admin($this->get_plugin_name(), $this->get_version());
        // Add Kueski to Payment Gateways
        $this->loader->add_filter('woocommerce_payment_gateways', $plugin_admin, 'wc_kueski_add_gateways');
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'kueski_gateway_init', 11);
        $this->loader->add_filter('plugin_action_links_' . KUESKI_BASE_ORDER, $plugin_admin, 'kueski_links_below_title_begin');

        if (is_admin()) {
            $this->loader->add_action('add_meta_boxes', $plugin_admin, 'kp_add_meta_boxes', 11);
        }
    }

    private function define_public_hooks(){
        $plugin_public = new Kueski_Gateway_Public($this->get_plugin_name(), $this->get_version(), $this->get_settings());

        //Block Widgets
        $this->loader->add_action('enqueue_block_assets', $plugin_public, 'enqueue_block_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_widget_block');
        $this->loader->add_filter('render_block', $plugin_public, 'display_widget_block', 10, 2);
        
        //Widgets
        $settings = $this->settings->get_settings();
        $position = isset($settings['product_widget_position']) ? $settings['product_widget_position'] : 'woocommerce_before_add_to_cart_form';
        $this->loader->add_action($position, $plugin_public, 'display_widget_product', 11);
        $this->loader->add_action('woocommerce_proceed_to_checkout', $plugin_public, 'display_widget_cart', 11);
        $this->loader->add_action('wp_footer', $plugin_public, 'add_widget_init', 11);
        //Setup endpoints
        $this->loader->add_action('template_redirect', $plugin_public, 'kueski_conciliation_endpoint');

        add_action('init', function(){
            add_action('rest_api_init', function(){
                $rest_api = new Kueski_Gateway_RestApi($this->settings);
                $rest_api->register_endpoints();
            });
        });
    }

    private function define_cron_jobs(){
        add_filter( 'cron_schedules', function ( $schedules ) {
                $schedules['every_kueski_sync'] = array(
                    'interval' => 300,
                    'display'  => __( 'Once Every 5min', 'kueskipay-gateway'),
                );
                
                return $schedules;
            }
        );
        // Sync Orders
        if ( ! wp_next_scheduled( 'isa_add_every_kueski_sync' ) ) {
            wp_schedule_event( time(), 'every_kueski_sync', 'isa_add_every_kueski_sync' );
        }
        add_action( 'isa_add_every_kueski_sync', function(){
            $cron_manager = new Kueski_Gateway_CronManager($this->get_plugin_name(), $this->get_version(), $this->get_settings());
            $cron_manager->ordersSync();
        });
    }

    public function define_refund_hooks()
    {
        $plugin_refund = new Kueski_Gateway_Refund($this->get_settings());
        if (is_admin()) {
            $this->loader->add_action('add_meta_boxes', $plugin_refund, 'kueski_add_refund_metabox');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_refund, 'kueski_refund_enqueue_scripts');
            $this->loader->add_action('wp_ajax_kueski_process_refund', $plugin_refund, 'kueski_process_refund_ajax');
        }
    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_version()
    {
        return $this->version;
    }

    public function get_loader()
    {
        return $this->loader;
    }

    public function get_settings(){
        return $this->settings;
    }
}
