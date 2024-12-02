<?php
function check_permission($permission) {
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Hole Berechtigungen aus der Datenbank
    global $conn;
    static $permissions = null;
    
    if ($permissions === null) {
        $permissions = [
            'user' => [],
            'chef' => [],
            'admin' => []
        ];
        
        $sql = "SELECT role, permission, allowed FROM role_permissions";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $permissions[$row['role']][$row['permission']] = (bool)$row['allowed'];
        }
        
        // Fallback auf Standard-Berechtigungen wenn keine DB-Einträge existieren
        if (empty($permissions['user']) && empty($permissions['chef']) && empty($permissions['admin'])) {
            $permissions = [
                'user' => [
                    'filter_month' => true,
                    'filter_remarks' => true,
                    'view_cashbook' => true
                ],
                'chef' => [
                    'filter_month' => true,
                    'filter_remarks' => true,
                    'filter_date' => true,
                    'filter_type' => true,
                    'export' => true,
                    'add_entries' => true,
                    'edit_entries' => true,
                    'view_cashbook' => true,
                    'view_statistics' => true
                ],
                'admin' => [
                    'filter_month' => true,
                    'filter_remarks' => true,
                    'filter_date' => true,
                    'filter_type' => true,
                    'export' => true,
                    'add_entries' => true,
                    'edit_entries' => true,
                    'delete_entries' => true,
                    'manage_users' => true,
                    'view_cashbook' => true,
                    'view_statistics' => true,
                    'system_settings' => true,
                    'manage_permissions' => true
                ]
            ];
        }
    }

    return isset($permissions[$user_role]) && 
           isset($permissions[$user_role][$permission]) && 
           $permissions[$user_role][$permission] === true;
}

// Hilfsfunktion zum Anzeigen/Verstecken von Elementen
function render_if_allowed($permission, $content) {
    if (check_permission($permission)) {
        return $content;
    }
    return '';
}

// Funktion zum Abrufen aller verfügbaren Berechtigungen
function get_all_permissions() {
    return [
        'view_cashbook' => 'Kassenbuch anzeigen',
        'filter_month' => 'Nach Monat filtern',
        'filter_remarks' => 'Nach Bemerkungen filtern',
        'filter_date' => 'Nach Datum filtern',
        'filter_type' => 'Nach Typ filtern',
        'export' => 'Export-Funktion',
        'add_entries' => 'Neue Einträge erstellen',
        'edit_entries' => 'Einträge bearbeiten',
        'delete_entries' => 'Einträge löschen',
        'view_admin_menu' => 'Administrationsmenü anzeigen',
        'view_dashboard' => 'Dashboard anzeigen',
        'manage_users' => 'Benutzerverwaltung',
        'manage_permissions' => 'Berechtigungen verwalten',
        'manage_backup' => 'Backup & Restore',
        'manage_settings' => 'Einstellungen verwalten',
        'import_excel' => 'Excel Import',
        'view_statistics' => 'Statistiken anzeigen'
    ];
}

// Funktion zum Abrufen aller Rollen
function get_all_roles() {
    return [
        'user' => 'Benutzer',
        'chef' => 'Chef',
        'admin' => 'Administrator'
    ];
}

// Am Ende der Datei hinzufügen:
function initialize_default_permissions() {
    global $conn;
    
    // Prüfe ob bereits Berechtigungen existieren
    $result = $conn->query("SELECT COUNT(*) as count FROM role_permissions");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $default_permissions = [
            'user' => [
                'filter_month' => true,
                'filter_remarks' => true,
                'view_cashbook' => true
            ],
            'chef' => [
                'filter_month' => true,
                'filter_remarks' => true,
                'filter_date' => true,
                'filter_type' => true,
                'export' => true,
                'add_entries' => true,
                'edit_entries' => true,
                'view_cashbook' => true,
                'view_statistics' => true,
                'view_admin_menu' => true,
                'view_dashboard' => true
            ],
            'admin' => [
                'filter_month' => true,
                'filter_remarks' => true,
                'filter_date' => true,
                'filter_type' => true,
                'export' => true,
                'add_entries' => true,
                'edit_entries' => true,
                'delete_entries' => true,
                'manage_users' => true,
                'view_cashbook' => true,
                'view_statistics' => true,
                'system_settings' => true,
                'manage_permissions' => true,
                'manage_backup' => true,
                'manage_settings' => true,
                'import_excel' => true
            ]
        ];
        
        $stmt = $conn->prepare("INSERT INTO role_permissions (role, permission, allowed) VALUES (?, ?, ?)");
        
        foreach ($default_permissions as $role => $permissions) {
            foreach ($permissions as $permission => $allowed) {
                $stmt->bind_param("ssi", $role, $permission, $allowed);
                $stmt->execute();
            }
        }
    }
}
?> 