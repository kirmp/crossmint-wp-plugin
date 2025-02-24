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

/*
 * Copyright (c) 2025 Buyt. (email: info@buyt.com.au). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// Prevent direct access to this file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'before_woocommerce_init', 'crossmint_declare_hpos_compatibility' );

function crossmint_declare_hpos_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}

// Hook into plugins_loaded to ensure WooCommerce is fully loaded
add_action( 'plugins_loaded', 'initialize_crossmint_gateway' );

function initialize_crossmint_gateway() {
    // Check if WC_Payment_Gateway exists (indicating WooCommerce is active)
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        // Include the gateway class
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-crossmint-gateway.php';
        require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

        // Enqueue Scripts
        add_action('wp_enqueue_scripts', 'crossmint_enqueue_assets');

        // Register the payment gateway with WooCommerce
        add_filter( 'woocommerce_payment_gateways', 'add_crossmint_gateway' );
    } else {
        // Show an admin notice if WooCommerce isn’t active
        add_action( 'admin_notices', 'crossmint_woocommerce_not_active_notice' );
    }
}

function add_crossmint_gateway( $gateways ) {
    $gateways[] = 'WCCrossmintGateway\WC_Crossmint_Gateway'; // Use the namespaced class name
    return $gateways;
}

function crossmint_woocommerce_not_active_notice() {
    echo '<div class="error"><p>Crossmint Payment Gateway requires WooCommerce to be installed and activated.</p></div>';
}

function crossmint_enqueue_assets() {
    if (is_checkout()) {
        wp_enqueue_style('crossmint-styles', plugin_dir_url(__FILE__) . 'assets/css/crossmint-style.css');
        wp_enqueue_script( 'crossmint-script', plugin_dir_url( __FILE__ ) . 'assets/js/crossmint-scripts.js', [], '1.0.0', true);
    }
}