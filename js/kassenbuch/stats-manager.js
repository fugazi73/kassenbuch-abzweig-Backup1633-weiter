class StatsManager {
    constructor(updateInterval = 60000) {
        this.updateInterval = updateInterval;
        this.stats = ['eintraege_heute', 'umsatz_heute', 'eintraege_monat', 'umsatz_monat'];
        
        this.init();
    }

    init() {
        this.loadStats();
        setInterval(() => this.loadStats(), this.updateInterval);
    }

    async loadStats() {
        this.stats.forEach(async (stat) => {
            try {
                const response = await fetch('get_stats.php?stat=' + stat);
                const data = await response.json();
                
                if (data.success) {
                    const element = document.getElementById('stats_' + stat);
                    if (element) {
                        element.textContent = data.value;
                    }
                }
            } catch (error) {
                console.error('Error loading stat:', stat, error);
            }
        });
    }
} 