<?php
/**
 * Plugin Name: Crossmint Payment Gateway
 * Plugin URI: https://buyt.com.au
 * Description: Accepts Digital Wallet Payment.
 * Version: 1.0.0
 * Author: Buy't.
 * Author URI: https://buyt.com.au
 * Text Domain: crossmint-payment-gateway
 * Requires Plugins: woocommerce
 * WC requires at least: 6.7
 * WC tested up to: 6.7
 * Domain Path: /languages/
 * License: GPL2
 */

use WCClientCrossmint\WC_Client_Crossmint;
use WCCrossmintGateway\WC_Crossmint_Gateway;

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Add this near the top of crossmint-payment-gateway.php
register_activation_hook(__FILE__, 'crossmint_flush_rewrite_rules');

function crossmint_flush_rewrite_rules() {
    $client_wallet = new WCClientCrossmint\WC_Client_Crossmint();
    $client_wallet->crossmint_add_wallet_endpoint();
    flush_rewrite_rules();
}

add_action('before_woocommerce_init', 'crossmint_declare_hpos_compatibility');

function crossmint_declare_hpos_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
}

add_action('plugins_loaded', 'initialize_crossmint_gateway');

function initialize_crossmint_gateway() {
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-crossmint-gateway.php';
        require_once plugin_dir_path(__FILE__) . 'includes/customer/class-client-crossmint.php';
        require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

        add_action('wp_enqueue_scripts', 'crossmint_enqueue_assets');

        $gateway = new WCCrossmintGateway\WC_Crossmint_Gateway();
        $gateway->init();

        $client_wallet = new WCClientCrossmint\WC_Client_Crossmint();
        $client_wallet->init();
    } else {
        add_action('admin_notices', 'crossmint_woocommerce_not_active_notice');
    }
}

function crossmint_woocommerce_not_active_notice() {
    echo '<div class="error"><p>Crossmint Payment Gateway requires WooCommerce to be installed and activated.</p></div>';
}

function crossmint_enqueue_assets() {
    if (is_checkout()) {
        wp_enqueue_style('crossmint-styles', plugin_dir_url(__FILE__) . 'assets/css/crossmint-style.css');
        wp_enqueue_script('crossmint-script', plugin_dir_url(__FILE__) . 'assets/js/crossmint-scripts.js', [], '1.0.0', true);
    }

    if (is_page() && strpos($_SERVER['REQUEST_URI'], 'crossmint-wallet') !== false) {
        wp_enqueue_style('crossmint-wallet', plugin_dir_url(__FILE__) . 'assets/css/crossmint-wallet.css');
        wp_enqueue_script('crossmint-wallet-script', plugin_dir_url(__FILE__) . 'assets/js/crossmint-wallet.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('crossmint-sdk', 'https://unpkg.com/@crossmint/client-sdk-vanilla@latest', [], null, true);

        // Load custom passkey script
        wp_enqueue_style('crossmint-passkey', plugin_dir_url(__FILE__) . 'assets/css/crossmint-wallet-passkey.css');
        wp_enqueue_script('crossmint-passkey-script', plugin_dir_url(__FILE__) . 'assets/js/crossmint-passkey.js', ['jquery'], '1.0.0', true);
        
        // Localize the correct script handle
        wp_localize_script('crossmint-passkey-script', 'webauthnAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('webauthn_save_nonce')
        ));
    }
}
