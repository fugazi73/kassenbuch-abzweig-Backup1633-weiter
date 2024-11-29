<!-- Modal für Startbetrag -->
<div class="modal fade" id="startbetragModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Startbetrag festlegen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="startbetragForm">
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
            </form>
        </div>
    </div>
</div> 