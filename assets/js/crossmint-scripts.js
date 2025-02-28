document.addEventListener('DOMContentLoaded', function() {
    // A function to attach the event listener
    function addEventListenerToButton() {
        const placeOrderButton = document.querySelector('#place_order');
        
        if (!placeOrderButton) {
            return;
        }

        // Check if the event listener is already attached
        if (placeOrderButton.hasAttribute('data-listener-attached')) {
            return;
        }

        placeOrderButton.addEventListener('click', function(e) {
            e.preventDefault();  // Prevent default form submission

            // Check if the Crossmint payment method is selected
            const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');

            if (!selectedPaymentMethod || selectedPaymentMethod.value !== 'crossmint') {
                // If Crossmint is not selected, allow the WooCommerce checkout to proceed normally
                return document.querySelector('form.checkout').submit();
            }

            // If Crossmint is selected, perform validation
            const recipientAddress = document.querySelector('#recipient-address').value;
            const transferAmount = document.querySelector('#transfer-amount').value;
            const passKey = document.querySelector('#pass-key').value;

            // Validate required fields
            if (!recipientAddress || !transferAmount || !passKey) {
                alert('Please fill in all fields, including the pass key.');
                return;  // Stop the process if validation fails
            }

            // Prepare data for the AJAX request
            const data = {
                action: 'process_crossmint_transfer',
                recipient_address: recipientAddress,
                transfer_amount: transferAmount,
                pass_key: passKey,
                // order_id: document.querySelector('input[name="order_id"]').value, // Assuming you pass the order ID
                wallet_address: recipientAddress, // Include the wallet address in the request
            };

            // Send the AJAX request
            fetch(wc_checkout_params.ajax_url + '?action=process_crossmint_transfer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'process_crossmint_transfer', // Ensure the action is included
                    recipient_address: recipientAddress,
                    transfer_amount: transferAmount,
                    pass_key: passKey
                })
            })
            .then(response => {
                console.log('Raw response:', response);
                return response.json();
            })
            .then(response => {
                console.log('AJAX Response:', response);
                if (response.success) {
                    alert('Transfer successful!');
                    document.querySelector('form.checkout').submit();
                } else {
                    alert('Error: ' + response.message);
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                alert('An error occurred. Check console for details.');
            });
            
        });

        // Mark the listener as attached to prevent it from being added multiple times
        placeOrderButton.setAttribute('data-listener-attached', 'true');
    }

    // Start observing for changes in the DOM to detect when the Place Order button is available
    const observer = new MutationObserver(function(mutationsList, observer) {
        addEventListenerToButton();
    });

    // Start observing for changes in the DOM (new elements added, like the Place Order button)
    observer.observe(document.body, { childList: true, subtree: true });

    // Initially, call the function to check if the button is already available
    addEventListenerToButton();
});
