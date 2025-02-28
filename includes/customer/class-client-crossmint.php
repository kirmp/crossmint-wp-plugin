<?php

namespace WCClientCrossmint;

use WCCrossmintGateway\WC_Crossmint_Gateway;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Client_Crossmint {

    const URL_ENDPOINT                  = "crossmint-wallet";
    const USER_CROSSMINT_WALLET_SLUG    = "user_crossmint_wallet_address";

    /**
	 * Constructor for the gateway.
	 */
	public function __construct() {
        $this->init();
	}

    /**
     * Initialize webhooks
     */
    public function init() {
        add_filter( 'woocommerce_account_menu_items', [ $this, 'crossmint_add_wallet_menu_item' ] );
        add_action( 'init', [ $this, 'crossmint_add_wallet_endpoint' ] );
        add_action( 'woocommerce_account_crossmint-wallet_endpoint', [ $this, 'crossmint_wallet_endpoint_content' ] );
        add_action( 'wp_ajax_save_webauthn_passkey', [ $this, 'save_webauthn_passkey' ] );
    }

    /**
     * Add "Wallet" to the My Account menu
     */
    public function crossmint_add_wallet_menu_item($items) {
        $items[ 'crossmint-wallet' ] = __( 'Wallet', 'crossmint-payment-gateway' );
        return $items;
    }

    /**
     * Register the "wallet" endpoint
     */
    public function crossmint_add_wallet_endpoint() {
        add_rewrite_endpoint( 'crossmint-wallet', EP_ROOT | EP_PAGES );
    }

    /**
     * Display content for the "Wallet" endpoint
     */
    public function crossmint_wallet_endpoint_content() {
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );
        
        // Check if the 'has_wallet' meta key exists for this user
        $wallet_address = $this->check_crossmint_wallet( $user_id );

        if ( ! $wallet_address ) {
            // Load the template
            ob_start();
            include plugin_dir_path(__FILE__) . '../../templates/wallet/client-wallet-passkey-template.php';
            echo ob_get_clean();
        } else {
            // $wallet_data = $this->crossmint_get_wallet_data($user_id);
            // $wallet_address = $wallet_address ?: 'Not set';
            // $wallet_balance = $wallet_data[ 'wallet_balance' ] ?: '0.00';
            $nickname = $user->user_email ? $user->nickname : 'User';
            
            // Load the template
            ob_start();
            include plugin_dir_path(__FILE__) . '../../templates/wallet/client-wallet-template.php';
            echo ob_get_clean();
        }
    }

    public function crossmint_get_wallet_data( $user_id ) {
        try {
            $crossmint_wallet_class = new WC_Crossmint_Gateway();
            $user_wallet_address = $crossmint_wallet_class->get_user_crossmint_wallet( $user_id );
            $user_wallet_balance = $crossmint_wallet_class->get_crossmint_wallet_balance( $user_id );

            $wallet_address = "";
            $wallet_balance = "";
            
            if ( ! empty( $user_wallet_address[ 'data' ][ 'address' ] ) ) {
                $wallet_address = $user_wallet_address[ 'data' ][ 'address' ];
            }

            return [
                'wallet_address'    => $wallet_address,
                'wallet_balance'    => $wallet_balance
            ];

        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            return [
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ];
        }
    }

    /**
     * AJAX Request to save user passkey
     * Only save User's
     * Public X, Public Y and Credential ID
     * WARNING: Do not store user's private Key
     */
    public function save_webauthn_passkey() {
        try {
            if( empty( $_POST[ 'credentialId' ] ) || empty( $_POST[ 'publicKeyX' ] ) || empty( $_POST[ 'publicKeyY' ] ) ) {
                apply_filters( 'webauthn_error', wp_send_json_error( 'Missing: public x / public y / credentials id.' ) );
                return;
            } 

            $credential_id = sanitize_text_field( $_POST[ 'credentialId' ] );
            $public_key_x = sanitize_text_field( $_POST[ 'publicKeyX' ] );
            $public_key_y = sanitize_text_field( $_POST[ 'publicKeyY' ] );

            $store_passkey_details = $this->save_user_webauthn_passkey( $public_key_x, $public_key_y, $credential_id );

            if ( $store_passkey_details[ 'error' ] == false ) {
                wp_send_json_success('Passkey saved successfully.');
            } else {
                wp_send_json_error( 'Something went wrong. Please try again.' );    
            }
        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            wp_send_json_error( 'Something went wrong. Please try again.' );
        }
    }

    /**
     * Save 
     * Credential ID from the WebAuthn registration response
     * Human-readable name for the passkey
     * X coordinate of the public key as a decimal string
     * Y coordinate of the public key as a decimal string
     * 
     */
    private function save_user_webauthn_passkey( $public_key_x, $public_key_y, $credential_id ) {
        try {
            if ( ! is_user_logged_in() ) {
                return [
                    'error'     => true,
                    'message'   => 'User not found.'
                ];
            }
    
            $user_id = get_current_user_id();
    
            update_user_meta( $user_id, 'webauthn_credential_id', $credential_id );
            update_user_meta( $user_id, 'webauthn_public_key_x', $public_key_x );
            update_user_meta( $user_id, 'webauthn_public_key_y', $public_key_y );
            
            // Create user Wallet as well
            $create_user_crossmint_wallet = $this->create_crossmint_wallet( $user_id, $public_key_x, $public_key_y, $credential_id );

            return [
                'error'     => false,
                'message'   => 'Passkey saved successfully.'
            ];
        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            return [
                'error'     => true,
                'message'   => 'Something went wrong. Cannot save passkey.'
            ];
        }
    }

    /**
     * Get user crossmint wallet
     * 
     * @params $user_id
     */
    public function check_crossmint_wallet( $user_id ) {
        try {
            // Step 1: Check crossmint metada update_user_meta( $user_id, self::USER_CROSSMINT_WALLET_SLUG, $create_crossmint_wallet[ 'data' ][ 'address' ] );
            $crossmint_class = new WC_Crossmint_Gateway();
            $has_crossmint_wallet = $crossmint_class->get_user_crossmint_wallet( $user_id );

            return $has_crossmint_wallet;
        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            return [
                'error'     => true,
                'message'   => 'Something went wrong.'
            ];
        }
    }

    /**
     * Create User Crossmint Wallet
     * 
     * @params public_key_x, public_key_y, credentials_id, user_id
     * 
     * @return array
     */
    public function create_crossmint_wallet( $user_id, $public_key_x, $public_key_y, $credential_id ) {
        try {
            // Step 1: Check crossmint metada update_user_meta( $user_id, self::USER_CROSSMINT_WALLET_SLUG, $create_crossmint_wallet[ 'data' ][ 'address' ] );
            $crossmint_class = new WC_Crossmint_Gateway();
            $create_crossmint_wallet = $crossmint_class->create_crossmint_wallet( $user_id, $public_key_x, $public_key_y, $credential_id );

            error_log( json_encode( $create_crossmint_wallet ) );
            return $create_crossmint_wallet;
        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            return [
                'error'     => true,
                'message'   => 'Something went wrong. Cannot save passkey.'
            ];
        }
    }
}