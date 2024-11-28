class AppManager {
    constructor() {
        this.themeManager = new ThemeManager();
        this.footerManager = new FooterManager();
        this.notificationManager = new NotificationManager();
    }

    init() {
        this.loadPageSpecificManager();
        this.setupCommonEventListeners();
    }

    loadPageSpecificManager() {
        const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
        switch(currentPage) {
            case 'kassenbuch':
                this.pageManager = new KassenbuchManager();
                break;
            case 'admin':
                this.pageManager = new AdminManager();
                break;
            // weitere Seiten...
        }
    }
} 