function deleteUser(userId) {
    if (!confirm('Möchten Sie diesen Benutzer wirklich löschen?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', userId);

    fetch('delete_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Fehler beim Löschen des Benutzers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    });
}

async function saveNewUser(event) {
    event.preventDefault();
    
    try {
        const form = event.target;
        const formData = new FormData(form);

        const response = await fetch('save_user.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server Response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            form.reset();
            location.reload();
        } else {
            throw new Error(data.message || 'Unbekannter Fehler');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Fehler beim Speichern des Benutzers: ' + error.message);
    }
}

// Event-Listener wenn DOM geladen ist
document.addEventListener('DOMContentLoaded', function() {
    const newUserForm = document.getElementById('newUserForm');
    if (newUserForm) {
        newUserForm.addEventListener('submit', saveNewUser);
    }
}); 