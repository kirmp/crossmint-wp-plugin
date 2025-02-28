<?php

namespace WCCrossmintGateway;

use Automattic\Jetpack\Connection\Tokens;
use WC_Payment_Gateway;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Crossmint_Gateway extends WC_Payment_Gateway {

    const WALLET_TYPE                   = "evm-smart-wallet";
    const WALLET_SIGNER_TYPE            = "evm-passkey";
    const USER_IDENTITY_TYPE            = "evm-passkey";
    const USER_CROSSMINT_WALLET_SLUG    = "user_crossmint_wallet_address";
    const AVAILABLE_TOKENS              = "eth";
    const WALLET_LOCATOR_TYPE           = "userId";

    /**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'crossmint';
		$this->icon               = trailingslashit(WP_PLUGIN_URL) . basename(dirname(__DIR__)) . '/assets/images/crossmint-original.svg';
		$this->has_fields         = true;
		$this->method_title       = __( 'Crossmint', 'woocommerce' );
		$this->method_description = __( 'Accepts Tokenized Payment Method like Qoin.', 'woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
        $this->init();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->enabled      = $this->get_option('enabled');

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

    /**
     * Initialize webhooks
     */
    public function init() {
        add_filter( 'woocommerce_payment_gateways', [ $this, 'add_crossmint_gateway' ], 10, 1 );
        add_action( 'wp_ajax_process_crossmint_transfer', [ $this, 'process_crossmint_transfer' ] );
        add_action( 'wp_ajax_nopriv_process_crossmint_transfer', [ $this, 'process_crossmint_transfer' ] );
    }

    /**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'crossmint_enabled'         => [
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Crossmint Wallet Payment', 'woocommerce' ),
				'default' => 'no',
			],
			'crossmint_title'           => [
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'safe_text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Crossmint Wallet Transfer', 'woocommerce' ),
				'desc_tip'    => true,
			],
			'crossmint_description'     => [
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Make payment via wallet.', 'woocommerce' ),
				'desc_tip'    => true,
			],
			'crossmint_instructions'    => [
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'crossmint_project_id'		=> [
				'title' 		=> __( 'Project ID', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Enter your Crossmint Project ID. Get your crossmint Project ID from <a href="https://www.crossmint.com/console/overview" target="__blank">Crossmint Dashboard</a>', 'woocommerce' ),
			],
			'crossmint_collection_id' => [
				'title' => __( 'Collection ID', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'Enter your Crossmint Collection ID (optional for non-NFT payments).Get your crossmint Collection ID from <a href="https://www.crossmint.com/console/collections" target="__blank">Crossmint Dashboard</a>', 'woocommerce' ),
			],
			'crossmint_api_key' => [
				'title' => __( 'API Key', 'woocommerce' ),
				'type' => 'password',
				'description' => __( 'Enter your Crossmint API Key for server-side validation.Get your crossmint API Key from <a href="https://www.crossmint.com/console/projects/apiKeys" target="__blank">Crossmint Dashboard</a>', 'woocommerce' ),
			],
            'crossmint_wallet_address' => [
				'title' => __( 'Wallet Address', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'Default wallet address to receive token. Get it from <a href="https://www.crossmint.com/console/wallets" target="__blank">Crossmint Dashboard</a>', 'woocommerce' ),
			],
            'crossmint_api_url' => [
				'title' => __( 'API URL', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'Default API url. Get it from <a href="https://docs.crossmint.com/api-reference/introduction" target="__blank">Crossmint API Reference</a>', 'woocommerce' ),
			],
		];
	}

    /**
     * API requests
     * 
     * @return array
     */
    public function makeHttpRequest( $url, $method, $body = [] ) {
        $args = [
            'method'  => $method,
            'headers' => [
                'Content-Type'  => 'application/json',
                'X-API-KEY'     => $this->get_option('crossmint_api_key'),
                // 'x-idempotency-key' =>  '' // need to add this to avoid duplicate transaction/wallet creation
            ],
        ];
    
        if ( 'GET' != $method) {
            $args['body'] = wp_json_encode( $body );
        }
    
        $response       = wp_remote_request( $url, $args );
        $data           = wp_remote_retrieve_body( $response );
        $response_code  = wp_remote_retrieve_response_code( $response );
    
        return [
            'data' => json_decode( $data, true ),
            'code' => $response_code
        ];
    }

    /**
	 * Payment Fields on the checkout page
	 */
	public function payment_fields() {
        // Get the wallet address from the plugin options
        $wallet_address     = apply_filters('crossmint_destination_address', $this->get_option('crossmint_wallet_address') );
        $crossmint_total    = apply_filters('crossmint_payment_total', WC()->cart->total);

        include plugin_dir_path(__FILE__) . '../templates/checkout/payment-button.php';
    }

    /**
     * Register Crossmint Payment Gateway on checkout
     * 
     * @params $gateways
     * @return array
     */
    public function add_crossmint_gateway( $gateways ) {
        $gateways[] = 'WCCrossmintGateway\WC_Crossmint_Gateway'; // Use the namespaced class name
        return $gateways;
    }

    /**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
    public function process_payment( $order_id ) {
        $order = wc_get_order($order_id);

        // Call Crossmint API to process payment
        $response = $this->process_crossmint_transfer($order);

        if ($response['success']) {
            $order->payment_complete();
            $order->add_order_note('Payment successful via Crossmint.');
            return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
        } else {
            wc_add_notice('Payment failed: ' . $response['message'], 'error');
            return ['result' => 'failure'];
        }
    }

    /**
     * Transfer Fund to another Wallet
     * @return array
     */
    public function process_crossmint_transfer() {
        // Handle the AJAX transfer logic here
        $recipient = sanitize_text_field( $_POST[ 'recipient_address' ] );
        $amount = floatval( $_POST[ 'transfer_amount' ] );
        $pass_key = sanitize_text_field( $_POST[ 'pass_key' ] );

        $result = $this->crossmint_transfer_tokens( $recipient, $amount);

        if ($result) {
            wp_send_json_success(['message' => 'Transfer Successful!']);
        } else {
            wp_send_json_error(['message' => 'Transfer failed.']);
        }
    }

    /**
     * PHP function to call the Crossmint API (example)
     */
    public function crossmint_transfer_tokens( $recipient, $amount) {
        $wallet_locater = "0x3Bb0573Cf7DA15F40FefC6112D1D2e06Fd3a56E4";
        $api_key = "sk_staging_33DCA1v72Eb3d9LnAbjRWCheyrXSjHyVomvo5rdwHf1CSLFy7P2z7s1DeSyjgx8jwFDsodygLZBVMx35PXiri9GNGsc5HGwemFcVT6fwoLw29QEPE4f392yUc685Te3WJAGspMqmiZvfd9qARG9kawoXdPaqiNFr1N8hX1TZnWnv8SphfgVNaYhoUH3uXJ68oLMP9mu5KuPKfy9x54ss9XF";

        // Prepare the request body for sending native currency
        $request_body = json_encode([
            "params" => [
                "calls" => [
                    [
                        "address" => $recipient,
                        "value" => $amount,
                        "data" => "test",
                        ""
                    ]
                ],
                "chain" => "base"
            ]
        ]);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://staging.crossmint.com/api/2022-06-09/wallets/" . $wallet_locater . "/transactions");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-API-KEY: " . $api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the request
        $response = curl_exec($ch);
    
        error_log(json_encode($response));  // For debugging purposes
    
        if (is_wp_error($response)) {
            return false;
        }
    
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
    
        // Check if the transfer was successful
        return isset($data['success']) && $data['success'] === true;
    }

    /**
     * Create Crossmint Wallet
     * 
     * @params $user_identity
     * @return array
     */
    public function create_crossmint_wallet( $user_id, $public_key_x, $public_key_y, $credential_id ) {
        try {
            $user_identity = $this->get_username( $user_id );

            if ( ! $user_identity ) {
                return [
                    'error' => true,
                    'message'   =>  'User not found.'
                ];
            }

            // Retrieves a wallet by its locator (address or user identifier and wallet type)
            $has_user_crossmint_wallet = $this->get_user_crossmint_wallet( $user_identity );

            if ( ! empty ( $has_user_crossmint_wallet[ 'address' ] ) && $has_user_crossmint_wallet[ 'code' ] == 200  ) {
                $create_crossmint_wallet =  $has_user_crossmint_wallet;
            } else {
                $url = $this->get_option( 'crossmint_api_url' ) . '2022-06-09/wallets';

                // Prepare payload
                $body = [
                    "type"          => self::WALLET_TYPE,
                    "linkedUser"    => self::WALLET_LOCATOR_TYPE . ':' . $user_identity,
                    "config"    => [
                        "adminSigner"   => [
                            "type"  => self::WALLET_SIGNER_TYPE,
                            "id"    => $credential_id,
                            "name"  => $user_identity,
                            "publicKey" => [
                                "x" => $public_key_x,
                                "y" => $public_key_y
                            ]
                        ]
                    ],
                ];
    
                $create_crossmint_wallet =  $this->makeHttpRequest( $url, 'POST', $body );
                
            }

            if ( ! empty( $create_crossmint_wallet[ 'data' ][ 'address' ] ) ) {
                update_user_meta( $user_id, self::USER_CROSSMINT_WALLET_SLUG, $create_crossmint_wallet[ 'data' ][ 'address' ] );

                return $create_crossmint_wallet;
            }

            return $create_crossmint_wallet;
        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            return [
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves a wallet by its locator (address or user identifier and wallet type)
     * 
     * @params $user_identity
     * 
     * 
     */
    public function get_user_crossmint_wallet( $user_id ) {
        try {
            $user_identity = $this->get_username( $user_id );

            if ( ! $user_identity ) {
                return [
                    'error' => true,
                    'message'   =>  'User not found.'
                ];
            }

            // If stored locally
            $wallet_address = get_user_meta( $user_id, self::USER_CROSSMINT_WALLET_SLUG, true );

            if ( $wallet_address ) return $wallet_address;

            $url = $this->get_option( 'crossmint_api_url' ) . '2022-06-09/wallets/'. self::WALLET_LOCATOR_TYPE .':' . $user_identity . ':' . self::WALLET_TYPE;

            $api_request = $this->makeHttpRequest( $url, 'GET' );

            if ( ! empty( $api_request[ 'data' ][ 'address' ] ) ) {
                update_user_meta( $user_id, self::USER_CROSSMINT_WALLET_SLUG, $api_request[ 'data' ][ 'address' ] );

                return $api_request[ 'data' ][ 'address' ];
            }

            return false;
        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            return [
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ];
        }
    }

    /**
     * Update User Wallet Address
     * 
     * @params $user_identity
     * @return array
     */
    public function get_username( $user_id ) {
        try {
            $user = get_userdata( $user_id );

            if ( ! $user ) {
                return false;
            }
            
            return $user->nickname;
        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            return [
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ];
        }
    }

    /**
     * Get Wallet Balance
     * Get the balance of a wallet for a given chain and currency
     */
    public function get_crossmint_wallet_balance( $user_id ) {
        try{
            $user_identity = $this->get_username( $user_id );

            if ( ! $user_identity ) {
                return [
                    'error' => true,
                    'message'   =>  'User not found.'
                ];
            }

            $url = $this->get_option( 'crossmint_api_url' ) . 'v1-alpha2/wallets/'. self::USER_IDENTITY_TYPE .':' . $user_identity . ':' . self::WALLET_TYPE . '/balances?tokens=' . self::AVAILABLE_TOKENS;

            return $this->makeHttpRequest( $url, 'GET' );
        } catch( \Exception $e ) {
            error_log( $e->getMessage() );
            return [
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ];
        }
    }
}