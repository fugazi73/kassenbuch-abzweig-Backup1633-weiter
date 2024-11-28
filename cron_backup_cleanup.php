<?php
require_once 'config.php';
require_once 'backup.php';

// Behalte Backups für 30 Tage
cleanupOldBackups(30);
?> 