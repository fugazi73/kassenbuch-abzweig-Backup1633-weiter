<?php
// Verzeichnisse erstellen
if (!file_exists('templates')) {
    mkdir('templates', 0755, true);
}

echo "Verzeichnisse wurden erstellt!"; 