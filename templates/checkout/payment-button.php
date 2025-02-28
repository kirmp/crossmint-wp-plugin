<form id="crossmint-transfer-form">
    <label for="recipient-address">Recipient Address:</label>
    <input type="text" id="recipient-address" name="recipient-address" value="<?php echo esc_attr( $wallet_address ); ?>" placeholder="Enter recipient address" required readonly>

    <label for="transfer-amount">Amount to Transfer (USDC):</label>
    <input type="number" id="transfer-amount" name="transfer-amount" placeholder="Amount to Transfer" value="<?php echo esc_attr( $crossmint_total ); ?>" required readonly>

    <label for="pass-key">Pass Key:</label>
    <input type="password" id="pass-key" name="pass-key" placeholder="Enter pass key" required>

    <!-- <button type="button" id="crossmint-confirm-transfer" class="btn btn-primary">Confirm Transfer</button> -->
</form>
