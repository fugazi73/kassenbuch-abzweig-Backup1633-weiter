// Kassenstart speichern
async function saveKassenstart() {
    const betrag = document.getElementById('kassenstart').value;
    const datum = document.getElementById('kassenstart_datum').value;

    if (!betrag || !datum) {
        alert('Bitte geben Sie einen Betrag und ein Datum ein.');
        return;
    }

    try {
        const response = await fetch('save_kassenstart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                betrag: parseFloat(betrag),
                datum: datum
            })
        });

        const result = await response.json();
        
        if (result.success) {
            location.reload(); // Seite neu laden um die Änderungen zu sehen
        } else {
            throw new Error(result.message || 'Fehler beim Speichern des Kassenstarts');
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Speichern des Kassenstarts: ' + error.message);
    }
}

// Event Listener für den Speichern-Button
document.addEventListener('DOMContentLoaded', function() {
    const saveButton = document.getElementById('saveKassenstartBtn');
    if (saveButton) {
        saveButton.addEventListener('click', saveKassenstart);
    }
}); 