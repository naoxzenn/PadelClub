// assets/js/theme.js

document.addEventListener('DOMContentLoaded', () => {
    const themeStorageKey = 'padelclub_theme';
    const docHtml = document.documentElement;
    
    // Select all potential toggle buttons in DOM
    const toggleBtns = document.querySelectorAll('#theme-toggle, #theme-toggle-sidebar');

    // Function to apply theme classes
    function applyTheme(theme) {
        if (theme === 'dark') {
            docHtml.classList.add('dark-mode');
            docHtml.classList.remove('light-mode');
            docHtml.setAttribute('data-theme', 'dark');
            localStorage.setItem(themeStorageKey, 'dark');
            updateToggleUI('dark');
        } else {
            docHtml.classList.remove('dark-mode');
            docHtml.classList.add('light-mode');
            docHtml.setAttribute('data-theme', 'light');
            localStorage.setItem(themeStorageKey, 'light');
            updateToggleUI('light');
        }
    }

    // Function to update Toggle Buttons UI icons and labels
    function updateToggleUI(theme) {
        toggleBtns.forEach(btn => {
            const icon = btn.querySelector('.theme-icon');
            const label = btn.querySelector('.theme-label');
            
            if (theme === 'dark') {
                if (icon) icon.textContent = 'light_mode';
                if (label) label.textContent = 'Mode Terang';
            } else {
                if (icon) icon.textContent = 'dark_mode';
                if (label) label.textContent = 'Mode Gelap';
            }
        });
    }

    // Initialize theme based on preference or system settings
    const savedTheme = localStorage.getItem(themeStorageKey);
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(systemPrefersDark ? 'dark' : 'light');
    }

    // Handle theme toggle action
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const isDark = docHtml.classList.contains('dark-mode');
            applyTheme(isDark ? 'light' : 'dark');
        });
    });
});
