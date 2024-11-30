document.addEventListener('DOMContentLoaded', function() {
    // Kassenstart-Formular
    const kassenstartForm = document.getElementById('kassenstartForm');
    if (kassenstartForm) {
        kassenstartForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            const startdatumInput = document.getElementById('startdatum');
            const startbetragInput = document.getElementById('startbetrag');
            
            if (!startdatumInput || !startbetragInput) {
                alert('Formularfelder konnten nicht gefunden werden.');
                return;
            }

            const startdatum = startdatumInput.value;
            const startbetrag = startbetragInput.value;
            
            if (!startdatum || !startbetrag) {
                alert('Bitte füllen Sie alle Felder aus.');
                return;
            }

            formData.append('datum', startdatum);
            formData.append('betrag', startbetrag);
            
            try {
                const response = await fetch('save_kassenstart.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Kassenstart wurde erfolgreich gespeichert');
                    window.location.reload();
                } else {
                    alert('Fehler: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten');
            }
        });
    }

    // Logo-Vorschau
    function handleFileSelect(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        
        if (input && preview) {
            input.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (e.target && typeof e.target.result === 'string') {
                            preview.src = e.target.result;
                        }
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }
    }

    handleFileSelect('logoLightInput', 'logoLightPreview');
    handleFileSelect('logoDarkInput', 'logoDarkPreview');

    // Logo-Upload-Formular
    const logoForm = document.getElementById('logoForm');
    if (logoForm) {
        logoForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('save_logo.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Logos wurden erfolgreich gespeichert');
                    window.location.reload();
                } else {
                    alert('Fehler: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten');
            }
        });
    }
}); 