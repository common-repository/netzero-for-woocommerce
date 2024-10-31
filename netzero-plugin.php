<?php
/*
@wordpress-plugin
Plugin Name: Netzero for WooCommerce
Plugin URI:  https://github.com/netzeroapp/netzero-woocommerce
Description: Offset your carbon emissions with Netzero, now for WooCommerce.
Version:     1.1.0
Author:      Netzero
Author URI:  http://net-zero.earth

WC tested up to: 3.8.0

Text Domain: netzero-woocommerce
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die('Nope, not accessing this');

// Main plugin class
if (!class_exists('netzero_woocommerce')) {
    class netzero_woocommerce
    {
        /**
         * Netzero plugin version
         */
        public $version = '1.0.0';

        // Constructor
        public function __construct()
        {
            if ($this->is_wc_active()) {
                //Custom Woocomerce menu
                add_action('admin_menu', array( $this, 'register_woocommerce_menu' ), 99);

                // Add settings
                add_action('admin_init', function () {
                    // General settings
                    register_setting('netzero-settings', 'netzero-api-key');
                    register_setting('netzero-settings', 'netzero-default-weight');
                    register_setting('netzero-settings', 'netzero-powered-by');

                    // Custom styles
                    register_setting('netzero-settings', 'netzero-color');
                    register_setting('netzero-settings', 'netzero-font-weight');
                    register_setting('netzero-settings', 'netzero-custom-css');
                });

                // Get checkout plugin functions
                require_once(plugin_dir_path(__FILE__) . 'inc/checkout.php');

                // Get plugin styles
                require_once(plugin_dir_path(__FILE__) . 'inc/styles.php');
            }
        }

        /**
         * Check if WooCommerce is active
         *
         * @access private
         * @since  1.0.0
         * @return bool
        */
        private function is_wc_active()
        {
            if (! function_exists('is_plugin_active')) {
                require_once(ABSPATH . '/wp-admin/includes/plugin.php');
            }
            if (is_plugin_active('woocommerce/woocommerce.php')) {
                $is_active = true;
            } else {
                $is_active = false;
            }

            // Do the WC active check
            if (false === $is_active) {
                add_action('admin_notices', array( $this, 'notice_activate_wc' ));
            }
            return $is_active;
        }

        /**
         * Display WC active notice
         *
         * @access public
         * @since  1.0.0
        */
        public function notice_activate_wc()
        {
            if (function_exists('get_plugins')) {
                $all_plugins  = get_plugins();
                $is_installed = ! empty($all_plugins['woocommerce/woocommerce.php']);
            } ?>
          <div class="error">
            <p>Netzero for WooCommerce requires WooCommerce to be installed and active.</p>
            <?php if ($is_installed && current_user_can('install_plugins')) : ?>
              <p><a href="<?php echo esc_url(wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active'), 'activate-plugin_woocommerce/woocommerce.php')); ?>" class="button button-primary"><?php esc_html_e('Active WooCommerce', 'netzero-woocommerce'); ?></a></p>
            <?php else : ?>
            <?php
              if (current_user_can('install_plugins')) {
                  $url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');
              } else {
                  $url = 'http://wordpress.org/plugins/woocommerce/';
              } ?>
              <p><a href="<?php echo esc_url($url); ?>" class="button button-primary"><?php esc_html_e('Install WooCommerce', 'netzero-woocommerce'); ?></a></p>
            <?php endif; ?>
          </div>
          <?php
        }

        /*
          * Admin Menu add function
          * WC sub menu
          */
        public function register_woocommerce_menu()
        {
            add_submenu_page('woocommerce', 'Netzero', 'Netzero', 'manage_options', 'woocommerce-netzero', array( $this, 'woocommerce_netzero_callback' ));
        }

        /**
         * Netzero admin page
         */
        public function woocommerce_netzero_callback()
        {
            require_once(plugin_dir_path(__FILE__) . 'inc/settings.php');
        }
    }

    new netzero_woocommerce();
}
