<div class="custom-alert-backdrop"></div>
<div class="custom-alert">
    <div class="custom-alert-header">
        Auf kassenbuch1 wird Folgendes angezeigt:
    </div>
    <div class="custom-alert-body">
        Bitte entweder eine Einnahme oder eine Ausgabe eingeben
    </div>
    <div class="custom-alert-footer">
        <button class="custom-alert-btn custom-alert-btn-primary" onclick="closeAlert()">Ok</button>
    </div>
</div>

<script>
function closeAlert() {
    const alert = document.querySelector('.custom-alert');
    const backdrop = document.querySelector('.custom-alert-backdrop');
    
    if (alert && backdrop) {
        alert.style.opacity = '0';
        backdrop.style.opacity = '0';
        setTimeout(() => {
            alert.remove();
            backdrop.remove();
        }, 300);
    }
}

// Schließen mit Escape-Taste
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAlert();
    }
});
</script> 