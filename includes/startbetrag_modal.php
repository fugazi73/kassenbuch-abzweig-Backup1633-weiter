<!-- Modal für Startbetrag -->
<div class="modal fade" id="startbetragModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Startbetrag festlegen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="startbetragForm" onsubmit="return saveStartbetrag(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Datum</label>
                        <input type="date" name="datum" class="form-control" required 
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Startbetrag (€)</label>
                        <input type="number" name="betrag" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function saveStartbetrag(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('save_startbetrag.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('startbetragModal'));
            modal.hide();
            location.reload();
        } else {
            alert(data.message || 'Fehler beim Speichern des Startbetrags');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    });

    return false;
}
</script> 