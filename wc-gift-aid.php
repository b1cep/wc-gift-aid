<?php
/**
 * Plugin Name:         WC Gift Aid
 * Description:         WC Gift Aid.
 * Author:              I Give Online Limited
 * Author URI:          http://igiveonline.com
 * Version:             1.0.0
 * Text Domain: wc-gift-aid
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if (!class_exists('WC_Gift_Aid')) {

    class WC_Gift_Aid {

        function __construct() {
            if (!defined('WCGAID_PLUGIN_PATH')) {
                define("WCGAID_PLUGIN_PATH", plugin_dir_path(__FILE__));
            }
            if (!defined('WCGAID_PLUGIN_URL')) {
                define("WCGAID_PLUGIN_URL", plugin_dir_url(__FILE__));
            }

            add_action('plugins_loaded', array($this, 'wcgaid_init'));
        }

        public function wcgaid_init() {
            //check for plugin dependencies
            if (class_exists('WooCommerce')) {
                $this->includes();
            } else {
                add_action('admin_notices', array($this, 'wcsr_inactive_plugin_notice'));
            }
        }

        public function includes() {
            //plugin settings
            require_once( WCGAID_PLUGIN_PATH . 'classes/wcgaid-settings.php' );
            //woocommerce class
            require_once( WCGAID_PLUGIN_PATH . 'classes/wcgaid-woocommerce.php' );
        }

        public function wcsr_inactive_plugin_notice() {
            ?>
            <div id="message" class="error">
                <p><?php printf(__('WC Gift Aid requires Woocommerce to be installed and active.', 'wc-gift-aid')); ?></p>
            </div>
            <?php
        }

    }

    //create instance
    $instance = new WC_Gift_Aid();
}