// Zentrale Alert-Funktion
function showAlert(message, type = 'error') {
    // Entferne existierende Alerts
    const existingAlerts = document.querySelectorAll('.custom-alert');
    existingAlerts.forEach(alert => alert.remove());

    // Erstelle Alert-Container falls nicht vorhanden
    let alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        document.body.appendChild(alertContainer);
    }

    // Erstelle Alert-Element
    const alertDiv = document.createElement('div');
    alertDiv.className = 'custom-alert';
    
    // Alert-Inhalt
    alertDiv.innerHTML = `
        <div class="alert-content">
            <span class="alert-message">${message}</span>
            <button type="button" class="alert-close" onclick="this.parentElement.parentElement.remove()">OK</button>
        </div>
    `;

    // Füge Alert zum Container hinzu
    alertContainer.appendChild(alertDiv);

    // Style für den Alert-Container
    alertContainer.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    `;

    // Style für den Alert
    alertDiv.style.cssText = `
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        min-width: 300px;
        max-width: 90vw;
        animation: alertSlideIn 0.3s ease-out;
    `;

    // Style für den Alert-Inhalt
    const alertContent = alertDiv.querySelector('.alert-content');
    alertContent.style.cssText = `
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        text-align: center;
    `;

    // Style für die Nachricht
    const alertMessage = alertDiv.querySelector('.alert-message');
    alertMessage.style.cssText = `
        color: var(--bs-body-color);
        font-size: 1rem;
        margin: 0;
        padding: 0;
    `;

    // Style für den Close-Button
    const closeButton = alertDiv.querySelector('.alert-close');
    closeButton.style.cssText = `
        background: var(--bs-primary);
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 20px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background-color 0.2s;
    `;

    // Hover-Effekt für den Button
    closeButton.onmouseover = () => {
        closeButton.style.backgroundColor = 'var(--bs-primary-dark, #0056b3)';
    };
    closeButton.onmouseout = () => {
        closeButton.style.backgroundColor = 'var(--bs-primary)';
    };

    // Füge CSS-Animation hinzu
    const style = document.createElement('style');
    style.textContent = `
        @keyframes alertSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);

    // Dark Mode Anpassungen
    function updateAlertTheme() {
        if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
            alertDiv.style.backgroundColor = 'var(--bs-dark)';
            alertDiv.style.borderColor = 'var(--bs-border-color)';
            alertMessage.style.color = 'var(--bs-light)';
            closeButton.style.backgroundColor = 'var(--bs-primary)';
        } else {
            alertDiv.style.backgroundColor = 'var(--bs-light)';
            alertDiv.style.borderColor = 'var(--bs-border-color)';
            alertMessage.style.color = 'var(--bs-dark)';
            closeButton.style.backgroundColor = 'var(--bs-primary)';
        }
    }

    // Initial theme update
    updateAlertTheme();

    // Beobachte Theme-Änderungen
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-bs-theme') {
                updateAlertTheme();
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme']
    });

    // Automatisches Schließen nach 5 Sekunden
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

// Globale Fehlerbehandlung für AJAX-Anfragen
$(document).ajaxError(function(event, jqXHR, settings, error) {
    let errorMessage = 'Ein Fehler ist aufgetreten';
    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
        errorMessage = jqXHR.responseJSON.message;
    } else if (error) {
        errorMessage = error;
    }
    showAlert(errorMessage);
}); 