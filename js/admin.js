document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

async function loadDashboardData() {
    try {
        const response = await fetch('get_dashboard_data.php');
        const data = await response.json();
        
        if (data.success) {
            updateDashboard(data);
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

function updateDashboard(data) {
    // Hier können die Dashboard-Werte aktualisiert werden
    if (data.active_users) {
        document.getElementById('active_users').textContent = data.active_users;
    }
    if (data.last_activity) {
        document.getElementById('last_activity').textContent = data.last_activity;
    }
    if (data.last_backup) {
        document.getElementById('last_backup').textContent = data.last_backup;
    }
}

function checkSystem() {
    // Implementierung des System-Checks
    alert('System-Check wird durchgeführt...');
} 