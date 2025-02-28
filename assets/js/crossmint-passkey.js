document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('generatePasskeyBtn');
    const resultDisplay = document.getElementById('resultDisplay');

    btn.addEventListener('click', function() {
        if (confirm('Ready to create your Crossmint Wallet passkey? Click OK to proceed.')) {
            registerPasskey();
        } else {
            alert('Passkey creation canceled.');
        }
    });

    async function registerPasskey() {
        try {
            const publicKeyCredentialCreationOptions = {
                challenge: Uint8Array.from("random-challenge-from-server", c => c.charCodeAt(0)),
                rp: {
                    name: "wallet",
                    id: window.location.hostname
                },
                user: {
                    id: Uint8Array.from("user-id-123", c => c.charCodeAt(0)),
                    name: "wallet",
                    displayName: "wallet"
                },
                pubKeyCredParams: [
                    { alg: -7, type: "public-key" },   // ES256
                    { alg: -257, type: "public-key" }  // RS256
                ],
                authenticatorSelection: { userVerification: "preferred" },
                timeout: 60000
            };

            const credential = await navigator.credentials.create({
                publicKey: publicKeyCredentialCreationOptions
            });

            const response = credential.response;
            const publicKey = response.getPublicKey();
            const credentialId = credential.id;

            const { x, y } = await parsePublicKey(publicKey);

            const passkeyData = {
                credentialId: credentialId,
                publicKeyX: arrayBufferToBase64(x),
                publicKeyY: arrayBufferToBase64(y)
            };
            localStorage.setItem('userPasskey', JSON.stringify(passkeyData));

            // Send data to WordPress via AJAX
            await savePasskeyToWordPress(passkeyData);

            resultDisplay.innerHTML = `
                <p><strong>Credential ID:</strong> ${credentialId}</p>
                <p><strong>Public Key X:</strong> ${passkeyData.publicKeyX}</p>
                <p><strong>Public Key Y:</strong> ${passkeyData.publicKeyY}</p>
            `;
            resultDisplay.style.display = 'block';
            alert('Passkey created and stored successfully!');

        } catch (error) {
            console.error('Error:', error);
            alert('Failed to create passkey: ' + error.message);
        }
    }

    async function savePasskeyToWordPress(passkeyData) {
        return new Promise((resolve, reject) => {
            jQuery.ajax({
                url: webauthnAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_webauthn_passkey',
                    nonce: webauthnAjax.nonce,
                    credentialId: passkeyData.credentialId,
                    publicKeyX: passkeyData.publicKeyX,
                    publicKeyY: passkeyData.publicKeyY
                },
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        console.log('Passkey saved to user meta:', response.data);
                        resolve();
                    } else {
                        console.error('Failed to save passkey:', response.data);
                        reject(new Error(response.data));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    reject(new Error('AJAX request failed'));
                }
            });
        });
    }

    async function parsePublicKey(publicKey) {
        const key = await crypto.subtle.importKey(
            "spki",
            publicKey,
            { name: "ECDSA", namedCurve: "P-256" },
            true,
            ["verify"]
        );
        const jwk = await crypto.subtle.exportKey("jwk", key);
        return {
            x: base64UrlToArrayBuffer(jwk.x),
            y: base64UrlToArrayBuffer(jwk.y)
        };
    }

    function base64UrlToArrayBuffer(base64url) {
        const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
        const binary = atob(base64);
        const buffer = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            buffer[i] = binary.charCodeAt(i);
        }
        return buffer;
    }

    function arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }
});
