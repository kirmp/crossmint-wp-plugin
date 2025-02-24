<?php

namespace WCCrossmintGateway;

use WC_Payment_Gateway;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Crossmint_Gateway extends WC_Payment_Gateway {
    
    /**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		$this->id                 = 'crossmint';
		$this->icon               = 'https://blog.crossmint.com/content/images/2024/09/BLOG---LIGHT.png';//apply_filters( 'woocommerce_crossmint_icon', 'woocommerce' );
		$this->has_fields         = true;
		$this->method_title       = __( 'Crossmint', 'woocommerce' );
		$this->method_description = __( 'Accepts Tokenized Payment Method like Qoin.', 'woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->enabled = $this->get_option('enabled');

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

    /**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'         => [
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Crossmint Wallet Payment', 'woocommerce' ),
				'default' => 'no',
			],
			'title'           => [
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'safe_text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Crossmint Wallet Transfer', 'woocommerce' ),
				'desc_tip'    => true,
			],
			'description'     => [
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Make payment via wallet.', 'woocommerce' ),
				'desc_tip'    => true,
			],
			'instructions'    => [
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'project_id'		=> [
				'title' 		=> __( 'Project ID', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Enter your Crossmint Project ID. Get your crossmint Project ID from <a href="https://www.crossmint.com/console/overview" target="__blank">Crossmint Dashboard</a>', 'woocommerce' ),
			],
			'collection_id' => [
				'title' => __( 'Collection ID', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'Enter your Crossmint Collection ID (optional for non-NFT payments).Get your crossmint Collection ID from <a href="https://staging.crossmint.com/console/collections" target="__blank">Crossmint Dashboard</a>', 'woocommerce' ),
			],
			'api_key' => [
				'title' => __( 'API Key', 'woocommerce' ),
				'type' => 'password',
				'description' => __( 'Enter your Crossmint API Key for server-side validation.Get your crossmint API Key from <a href="https://staging.crossmint.com/console/projects/apiKeys" target="__blank">Crossmint Dashboard</a>', 'woocommerce' ),
			],
		];
	}

    /**
	 * Payment Fields on the checkout page
	 */
	public function payment_fields() {
        $collection_id = esc_attr( $this->get_option('collection_id') );
        ?>
        <p>Secure payment via Crossmint.</p>
        <div id="crossmint-button-container"></div>
        <?php

        wp_enqueue_script( 'crossmint-script', plugin_dir_url( __FILE__ ) . 'assets/js/crossmint-scripts.js', [], '1.0.0', true);
    }

    /**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        // Get API key from settings
        $api_key = $this->get_option( 'api_key' );

        // Call Crossmint API for payment processing (dummy URL for now)
        $response = wp_remote_post( 'https://api.crossmint.io/payment', array(
            'method'    => 'POST',
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body'      => json_encode(array(
                'amount'      => $order->get_total(),
                'currency'    => get_woocommerce_currency(),
                'order_id'    => $order_id,
                'callback_url' => esc_url( $this->get_return_url( $order ) )
            )),
            'timeout'   => 30
        ));

        // Handle response
        if ( is_wp_error( $response ) ) {
            wc_add_notice( 'Payment failed: ' . $response->get_error_message(), 'error' );
            return;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['status'] ) && $body['status'] === 'success' ) {
            // Mark order as processing
            $order->payment_complete();
            $order->add_order_note( 'Crossmint payment successful.' );
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        } else {
            wc_add_notice( 'Payment failed. Please try again.', 'error' );
            return;
        }
    }
}