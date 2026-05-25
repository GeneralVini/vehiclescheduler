/**
 * Handle language selection via AJAX for real-time updates
 */
document.addEventListener('DOMContentLoaded', function () {
    const languageButtons = document.querySelectorAll('[name="set_plugin_locale"]');

    languageButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const locale = button.value;

            // Build the correct AJAX URL
            const ajaxUrl = new URL(window.location.href);
            ajaxUrl.pathname = ajaxUrl.pathname.replace(/front\/.*/, 'ajax/set_locale.php');

            console.log('[SisViaturas] Changing locale to:', locale);
            console.log('[SisViaturas] AJAX URL:', ajaxUrl.toString());

            // Show loading state
            button.classList.add('is-loading');
            button.disabled = true;

            // Use FormData for traditional form submission
            const formData = new FormData();
            formData.append('locale', locale);

            // Add CSRF token if available
            const csrfToken = document.querySelector('[name="_glpi_csrf_token"]')?.value;
            if (csrfToken) {
                formData.append('_glpi_csrf_token', csrfToken);
            }

            fetch(ajaxUrl.toString(), {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            })
            .then(response => {
                console.log('[SisViaturas] Response status:', response.status);
                
                return response.text().then(text => {
                    console.log('[SisViaturas] Response body length:', text.length);
                    if (text.length > 500) {
                        console.log('[SisViaturas] Response (first 200 chars):', text.substring(0, 200));
                    } else {
                        console.log('[SisViaturas] Response:', text);
                    }
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(text);
                        return { ok: response.ok, status: response.status, data };
                    } catch (e) {
                        // Response is not JSON - might be HTML error page
                        return { ok: response.ok, status: response.status, error: text.substring(0, 100) };
                    }
                });
            })
            .then(result => {
                console.log('[SisViaturas] Parsed result:', result);
                
                if (result.data && result.data.success) {
                    console.log('[SisViaturas] Language changed successfully, reloading...');
                    window.location.reload();
                } else if (result.data && result.data.error) {
                    alert('Erro: ' + result.data.error);
                    button.classList.remove('is-loading');
                    button.disabled = false;
                } else if (!result.ok) {
                    alert('Erro ao mudar de idioma (HTTP ' + result.status + ')');
                    button.classList.remove('is-loading');
                    button.disabled = false;
                } else {
                    alert('Erro: resposta inválida');
                    button.classList.remove('is-loading');
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('[SisViaturas] Error:', error);
                alert('Erro ao mudar de idioma: ' + error.message);
                button.classList.remove('is-loading');
                button.disabled = false;
            });
        });
    });
});
