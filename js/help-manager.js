class HelpManager {
    constructor() {
        this.initializeNavigation();
        this.setupSearchFunctionality();
        this.setupPrintButton();
    }

    initializeNavigation() {
        // Smooth Scrolling für Anker-Links
        document.querySelectorAll('.help-nav a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    targetElement.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Aktiven Link markieren beim Scrollen
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('section');
            let currentSection = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (window.pageYOffset >= sectionTop - 100) {
                    currentSection = section.getAttribute('id');
                }
            });

            document.querySelectorAll('.help-nav a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${currentSection}`) {
                    link.classList.add('active');
                }
            });
        });
    }

    setupSearchFunctionality() {
        const searchInput = document.getElementById('helpSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('.help-section').forEach(section => {
                    const content = section.textContent.toLowerCase();
                    section.style.display = content.includes(searchTerm) ? 'block' : 'none';
                });
            });
        }
    }

    setupPrintButton() {
        const printBtn = document.getElementById('printHelp');
        if (printBtn) {
            printBtn.addEventListener('click', () => {
                window.print();
            });
        }
    }
} 