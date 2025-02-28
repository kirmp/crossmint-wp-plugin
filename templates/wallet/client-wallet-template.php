<?php
if (!defined('ABSPATH')) {
    exit;
}

// Default values if wallet_data is not yet set
$nickname = wp_get_current_user()->nickname ?? 'User';
$wallet_balance = isset($wallet_data['balance']) ? $wallet_data['balance'] : '0.00';
$display_address = strlen($wallet_address) > 8 ? substr($wallet_address, 0, 6) . ' ... ' . substr($wallet_address, -4) : $wallet_address;
?>

<div class="wallet-container">
    <div class="wallet-card">
        <h5>Hi <?php echo esc_html( $nickname ); ?></h5>
        <p class="wallet-address"><?php echo esc_html( $display_address ); ?></p>
        <div class="wallet-balance">
            <p>AUD <span class="balance-amount"><?php echo esc_html($wallet_balance); ?></span></p>
            <p class="balance-crypto">0.000000 devtk</p>
        </div>
    </div>

    <div class="wallet-actions">
        <button class="action-btn send-btn">SEND</button>
        <button class="action-btn receive-btn">GET</button>
        <button class="action-btn transactions-btn">TXN</button>
        <button class="action-btn address-book-btn">ADDRESS BOOK</button>
    </div>

    <div class="wallet-send-form">
        <div class="form-group">
            <label>AUD</label>
            <input type="text" class="amount-input" placeholder="Amount...">
        </div>
        <div class="preset-amounts">
            <button class="preset-btn">50</button>
            <button class="preset-btn">100</button>
            <button class="preset-btn">200</button>
            <button class="preset-btn">250</button>
        </div>
        <div class="form-group">
            <label>Recipient Address</label>
            <input type="text" class="recipient-input" placeholder="Address...">
        </div>
        <button class="send-submit-btn">SEND</button>
    </div>
</div>