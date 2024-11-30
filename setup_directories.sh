#!/bin/bash

# Erstelle Verzeichnisse
mkdir -p uploads backups cache logs

# Setze Berechtigungen
chmod 775 uploads backups cache logs

# Erstelle .htaccess Dateien
for dir in uploads backups cache logs; do
  echo "Deny from all" > $dir/.htaccess
done

# Erstelle .gitkeep Dateien
for dir in uploads backups cache logs; do
  touch $dir/.gitkeep
done

echo "Verzeichnisse wurden erstellt!" 