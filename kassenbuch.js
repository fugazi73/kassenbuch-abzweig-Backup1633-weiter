// Im Formular die Felder überwachen
document.addEventListener('DOMContentLoaded', function() {
    const einnahmeField = document.getElementById('einnahme');
    const ausgabeField = document.getElementById('ausgabe');
    
    // Funktion zum Umschalten der Felder
    function toggleFields(event) {
        const sourceField = event.target;
        const targetField = sourceField === einnahmeField ? ausgabeField : einnahmeField;
        
        // Wenn ein Wert eingegeben wird, das andere Feld deaktivieren
        if (sourceField.value && sourceField.value !== '0' && sourceField.value !== '0,00') {
            targetField.disabled = true;
            targetField.value = '';
        } else {
            targetField.disabled = false;
        }
    }
    
    // Event-Listener für beide Felder
    einnahmeField.addEventListener('input', toggleFields);
    ausgabeField.addEventListener('input', toggleFields);
    
    // Beim Laden des Formulars prüfen
    if (einnahmeField.value && einnahmeField.value !== '0' && einnahmeField.value !== '0,00') {
        ausgabeField.disabled = true;
    } else if (ausgabeField.value && ausgabeField.value !== '0' && ausgabeField.value !== '0,00') {
        einnahmeField.disabled = true;
    }
}); 