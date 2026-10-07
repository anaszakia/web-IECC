import './bootstrap';
import { Passkeys } from '@laravel/passkeys';

function rememberValue() {
    return Boolean(document.querySelector('[data-passkey-remember]')?.checked);
}

function showPasskeyMessage(element, message, type = 'danger') {
    if (!element) {
        if (message) {
            alert(message);
        }

        return;
    }

    element.className = `alert alert-${type} py-2 small`;
    element.textContent = message;
    element.classList.remove('d-none');
}

function errorMessage(error) {
    return error?.message || 'Passkey gagal diproses. Coba lagi.';
}

// Helper untuk menunggu Bootstrap tersedia
function waitForBootstrap(callback, attempts = 0) {
    if (window.bootstrap) {
        callback();
    } else if (attempts < 50) {
        setTimeout(() => waitForBootstrap(callback, attempts + 1), 100);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const promptModal = document.getElementById('passkeyPromptModal');

    if (promptModal) {
        waitForBootstrap(() => {
            if (window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(promptModal).show();
            }
        });
    }

    const loginButton = document.querySelector('[data-passkey-login]');
    const loginMessage = document.querySelector('[data-passkey-login-message]');

    if (loginButton) {
        loginButton.disabled = !Passkeys.isSupported();

        if (!Passkeys.isSupported()) {
            showPasskeyMessage(loginMessage, 'Browser atau koneksi ini belum mendukung passkey.', 'warning');
        }

        loginButton.addEventListener('click', async () => {
            loginButton.disabled = true;
            loginButton.dataset.originalText ??= loginButton.textContent;
            loginButton.textContent = 'Memeriksa passkey...';

            try {
                const response = await Passkeys.verify({ remember: rememberValue });

                if (response?.redirect) {
                    window.location.href = response.redirect;
                    return;
                }

                window.location.href = '/dashboard';
            } catch (error) {
                showPasskeyMessage(loginMessage, errorMessage(error));
            } finally {
                loginButton.disabled = false;
                loginButton.textContent = loginButton.dataset.originalText;
            }
        });

        Passkeys.isAutofillSupported().then((supported) => {
            if (!supported) {
                return;
            }

            Passkeys.autofill({ remember: rememberValue }).then((response) => {
                if (response?.redirect) {
                    window.location.href = response.redirect;
                }
            }).catch(() => {});
        });
    }

    const registerButton = document.querySelector('[data-passkey-register]');
    const registerName = document.querySelector('[data-passkey-name]');
    const registerMessage = document.querySelector('[data-passkey-register-message]');

    if (registerButton) {
        registerButton.disabled = !Passkeys.isSupported();

        if (!Passkeys.isSupported()) {
            showPasskeyMessage(registerMessage, 'Browser atau koneksi ini belum mendukung passkey.', 'warning');
        }

        registerButton.addEventListener('click', async () => {
            const name = registerName?.value?.trim() || 'Perangkat saya';

            registerButton.disabled = true;
            registerButton.dataset.originalText ??= registerButton.textContent;
            registerButton.textContent = 'Mendaftarkan...';

            try {
                await Passkeys.register({ name });
                showPasskeyMessage(registerMessage, 'Passkey berhasil didaftarkan.', 'success');
                window.setTimeout(() => window.location.reload(), 800);
            } catch (error) {
                showPasskeyMessage(registerMessage, errorMessage(error));
            } finally {
                registerButton.disabled = false;
                registerButton.textContent = registerButton.dataset.originalText;
            }
        });
    }
});
