function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));

    document.getElementById(tab).classList.add('active');
    event.target.classList.add('active');
}

async function encryptMessage() {
    const plaintext = document.getElementById('plaintext').value;
    const key = document.getElementById('encrypt-key').value;
    const user_key = document.getElementById('encrypt-user-key').value;

    const loading = document.getElementById('encrypt-loading');
    const intermediateBox = document.getElementById('intermediate-result');
    const resultBox = document.getElementById('encrypt-result');
    const errorBox = document.getElementById('encrypt-error');

    if (!plaintext || !key || !user_key) {
        showError('encrypt', 'Please fill in all fields');
        return;
    }

    loading.style.display = 'block';
    intermediateBox.style.display = 'none';
    resultBox.style.display = 'none';
    errorBox.style.display = 'none';

    try {
        const response = await fetch('/encrypt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                plaintext: plaintext,
                key: key,
                user_key: user_key
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Encryption failed');
        }

        document.getElementById('intermediate-output').textContent = data.intermediate;
        intermediateBox.classList.add('show');
        intermediateBox.style.display = 'block';

        document.getElementById('encrypted-output').textContent = data.encrypted;
        resultBox.classList.add('show');
        resultBox.style.display = 'block';
    } catch (error) {
        showError('encrypt', error.message);
    } finally {
        loading.style.display = 'none';
    }
}

async function decryptMessage() {
    const ciphertext = document.getElementById('ciphertext').value;
    const key = document.getElementById('decrypt-key').value;
    const user_key = document.getElementById('decrypt-user-key').value;

    const loading = document.getElementById('decrypt-loading');
    const resultBox = document.getElementById('decrypt-result');
    const errorBox = document.getElementById('decrypt-error');

    if (!ciphertext || !key || !user_key) {
        showError('decrypt', 'Please fill in all fields');
        return;
    }

    loading.style.display = 'block';
    resultBox.style.display = 'none';
    errorBox.style.display = 'none';

    try {
        const response = await fetch('/decrypt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ciphertext: ciphertext,
                key: key,
                user_key: user_key
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Decryption failed');
        }

        document.getElementById('decrypted-output').textContent = data.decrypted;
        resultBox.classList.add('show');
        resultBox.style.display = 'block';
    } catch (error) {
        showError('decrypt', error.message);
    } finally {
        loading.style.display = 'none';
    }
}

function showError(tab, message) {
    const errorBox = document.getElementById(`${tab}-error`);
    const errorMessage = document.getElementById(`${tab}-error-message`);
    errorMessage.textContent = message;
    errorBox.style.display = 'block';
}

function copyToClipboard(elementId) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}