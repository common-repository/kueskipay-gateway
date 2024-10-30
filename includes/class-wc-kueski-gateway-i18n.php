<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 **/

class Kueski_Gateway_i18n
{

    protected $locale;
    public function __construct()
    {
        $this->locale = apply_filters('plugin_locale', get_locale(), 'kueskipay-gateway');
    }
    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain()
    {

        load_textdomain(
            'kueskipay-gateway',
            dirname(plugin_basename(__FILE__)) . '/languages/kueskipay-gateway-' . $this->locale . '.mo'
        );

        load_plugin_textdomain(
            'kueskipay-gateway',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function copy_plugin_locale_files()
    {

        if (
            !file_exists(trailingslashit(WP_LANG_DIR) . 'plugins/kueskipay-gateway-' . $this->locale . '.mo') &&
            file_exists(dirname(plugin_basename(__FILE__)) . '/languages/kueskipay-gateway-' . $this->locale . '.mo')
        ) {
            @copy(
                dirname(plugin_basename(__FILE__)) . '/languages/kueskipay-gateway-' . $this->locale . '.mo',
                trailingslashit(WP_LANG_DIR) . 'plugins/kueskipay-gateway-' . $this->locale . '.mo'
            );
            @copy(
                dirname(plugin_basename(__FILE__)) . '/languages/kueskipay-gateway-' . $this->locale . '.po',
                trailingslashit(WP_LANG_DIR) . 'plugins/kueskipay-gateway-' . $this->locale . '.po'
            );
        }
    }
}
