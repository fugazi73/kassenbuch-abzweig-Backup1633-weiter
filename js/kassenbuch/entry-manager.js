class EntryManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Neuer Eintrag Button
        document.getElementById('addEntryBtn')?.addEventListener('click', () => {
            this.addNewEntry();
        });

        // Edit und Delete Buttons für bestehende Einträge
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                this.editEntry(row);
            });
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                this.deleteEntry(row);
            });
        });
    }

    async addNewEntry() {
        const date = document.getElementById('new_date').value;
        const description = document.getElementById('new_description').value;
        const income = document.getElementById('new_income').value || '0';
        const expense = document.getElementById('new_expense').value || '0';

        if (!date || !description || (income === '0' && expense === '0')) {
            alert('Bitte füllen Sie alle erforderlichen Felder aus.');
            return;
        }

        try {
            const response = await fetch('api/entries.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    date: date,
                    description: description,
                    income: parseFloat(income),
                    expense: parseFloat(expense)
                })
            });

            if (!response.ok) throw new Error('Fehler beim Speichern');

            // Formular zurücksetzen und Seite neu laden
            this.resetForm();
            location.reload();

        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Speichern des Eintrags');
        }
    }

    resetForm() {
        document.getElementById('new_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('new_description').value = '';
        document.getElementById('new_income').value = '';
        document.getElementById('new_expense').value = '';
    }

    async editEntry(row) {
        const id = row.dataset.entryId;
        const date = row.querySelector('td:nth-child(1)').textContent;
        const description = row.querySelector('td:nth-child(3)').textContent;
        const income = row.querySelector('td:nth-child(4)').textContent;
        const expense = row.querySelector('td:nth-child(5)').textContent;

        // Setze Werte in die Eingabefelder
        document.getElementById('new_date').value = this.formatDate(date);
        document.getElementById('new_description').value = description;
        document.getElementById('new_income').value = this.parseAmount(income);
        document.getElementById('new_expense').value = this.parseAmount(expense);

        // Ändere Button temporär
        const addButton = document.getElementById('addEntryBtn');
        addButton.innerHTML = '<i class="bi bi-check-circle"></i> Aktualisieren';
        addButton.dataset.editId = id;

        // Füge Cancel Button hinzu
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'btn btn-secondary ms-2';
        cancelBtn.innerHTML = '<i class="bi bi-x-circle"></i> Abbrechen';
        cancelBtn.onclick = () => this.cancelEdit();
        addButton.parentNode.insertBefore(cancelBtn, addButton.nextSibling);
    }

    cancelEdit() {
        const addButton = document.getElementById('addEntryBtn');
        addButton.innerHTML = '<i class="bi bi-plus-circle"></i> Neuer Eintrag';
        delete addButton.dataset.editId;
        this.resetForm();
        
        // Entferne Cancel Button
        const cancelBtn = addButton.nextElementSibling;
        if (cancelBtn) cancelBtn.remove();
    }

    formatDate(dateStr) {
        const [day, month, year] = dateStr.split('.');
        return `${year}-${month}-${day}`;
    }

    parseAmount(amountStr) {
        return amountStr.replace('€', '').trim().replace('.', '').replace(',', '.');
    }

    async deleteEntry(row) {
        if (!confirm('Möchten Sie diesen Eintrag wirklich löschen?')) return;

        const id = row.dataset.entryId;
        try {
            const response = await fetch(`api/entries.php?id=${id}`, {
                method: 'DELETE'
            });

            if (!response.ok) throw new Error('Fehler beim Löschen');

            location.reload();
        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Löschen des Eintrags');
        }
    }
}

// Initialisierung
document.addEventListener('DOMContentLoaded', () => {
    window.entryManager = new EntryManager();
}); 