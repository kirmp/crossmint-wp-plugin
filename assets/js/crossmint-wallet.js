document.addEventListener('DOMContentLoaded', function () {
    const presetButtons = document.querySelectorAll('.preset-btn');
    const amountInput = document.querySelector('.amount-input');
    const sendButton = document.querySelector('.send-submit-btn');
    const recipientInput = document.querySelector('.recipient-input');

    // Handle preset amount buttons
    presetButtons.forEach(button => {
        button.addEventListener('click', function () {
            amountInput.value = this.textContent;
        });
    });

    if ( sendButton ) {
        // Simple form validation and alert on send
        sendButton.addEventListener('click', function () {
            const amount = amountInput.value;
            const recipient = recipientInput.value;

            if (amount && recipient) {
                alert(`Sending ${amount} AUD to ${recipient}`);
                // Add your send logic here (e.g., AJAX call to Crossmint API)
                amountInput.value = '';
                recipientInput.value = '';
            } else {
                alert('Please enter both amount and recipient address.');
            }
        });
    }
});