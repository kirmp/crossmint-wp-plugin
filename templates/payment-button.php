<!-- Payment button template -->
<button id="crossmintPayButton"
    data-client-id="<?php echo esc_attr($atts['client_id']); ?>"
    data-amount="<?php echo esc_attr($atts['amount']); ?>"
    data-currency="<?php echo esc_attr($atts['currency']); ?>"
    data-recipient-email="<?php echo esc_attr($atts['recipient_email']); ?>"
>Pay with Crossmint</button>