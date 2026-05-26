/**
 * Handle language selection via AJAX for real-time updates
 */
document.addEventListener('DOMContentLoaded', function () {
    const languageButtons = document.querySelectorAll('[name="set_plugin_locale"]');

    languageButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const locale = button.value;
            const form = button.closest('form');
            const ajaxUrl = form?.dataset.localeUrl;

            if (!form || !ajaxUrl) {
                form?.requestSubmit(button);
                return;
            }

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
                return response.text().then(text => {
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
                if (result.data && result.data.success) {
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
                alert('Erro ao mudar de idioma: ' + error.message);
                button.classList.remove('is-loading');
                button.disabled = false;
            });
        });
    });
});
