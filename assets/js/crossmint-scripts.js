document.addEventListener("DOMContentLoaded", function () {
    function renderCrossmintButton() {
        let buttonContainer = document.getElementById("crossmint-button-container");
        if (buttonContainer && buttonContainer.innerHTML.trim() === "") {
            buttonContainer.innerHTML = `<crossmint-pay-button 
                class="crossmint-pay-button" 
                collectionId="<?php echo $collection_id; ?>" 
                environment="staging" 
                mintConfig='{"amount":"100", "currency":"USD"}'>
            </crossmint-pay-button>`;
        }
    }

    // Event listener for payment method selection
    document.addEventListener("change", function (event) {
        if (event.target.name === "payment_method") {
            if (event.target.value === "crossmint") {
                console.log("Crossmint selected");
                renderCrossmintButton();
            }
        }
    });

    // Ensure button is rendered if Crossmint is pre-selected
    let selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (selectedPaymentMethod && selectedPaymentMethod.value === "crossmint") {
        console.log("Crossmint pre-selected");
        renderCrossmintButton();
    }
});
