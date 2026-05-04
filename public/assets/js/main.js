document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    
    // Check if the user already has a saved preference, otherwise default to light
    const currentTheme = localStorage.getItem('theme') || 'light';

    // Function to apply the theme to the HTML tag and update the icon
    const applyTheme = (theme) => {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (theme === 'dark') {
            themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
            themeIcon.classList.replace('text-dark', 'text-warning'); // Make the sun yellow
        } else {
            themeIcon.classList.replace('bi-sun-fill', 'bi-moon-fill');
            themeIcon.classList.remove('text-warning');
        }
    };

    // Apply the saved theme immediately when the page loads
    applyTheme(currentTheme);

    // Listen for button clicks to toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        });
    }
});