<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<script>
    // Protect the 'dark' class on the <html> tag during Livewire SPA navigation
    // Livewire manually syncs attributes and would otherwise strip the class, triggering a visual flash (especially with View Transitions).
    document.addEventListener('livewire:navigating', () => {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        
        html.__originalSetAttribute = html.setAttribute;
        html.setAttribute = function(name, value) {
            if (name === 'class') {
                let hasDark = value.split(' ').includes('dark');
                if (isDark && !hasDark) value += ' dark';
                if (!isDark && hasDark) value = value.replace(/\bdark\b/g, '').trim();
            }
            html.__originalSetAttribute.call(this, name, value);
        };

        html.__originalRemoveAttribute = html.removeAttribute;
        html.removeAttribute = function(name) {
            if (name === 'class') {
                html.__originalSetAttribute.call(this, 'class', isDark ? 'dark' : '');
                return;
            }
            html.__originalRemoveAttribute.call(this, name);
        };
    });

    document.addEventListener('livewire:navigated', () => {
        const html = document.documentElement;
        if (html.__originalSetAttribute) {
            html.setAttribute = html.__originalSetAttribute;
            html.removeAttribute = html.__originalRemoveAttribute;
            delete html.__originalSetAttribute;
            delete html.__originalRemoveAttribute;
        }
    });
</script>

<style>
    /* Remove glowing focus ring/shadow effects globally on focus (Light & Dark mode) */
    [data-flux-control]:focus-within,
    [data-flux-control]:focus,
    [data-flux-checkbox]:focus-within [data-flux-checkbox-indicator],
    [data-flux-input]:focus-within,
    [data-flux-input]:focus {
        box-shadow: none !important;
        outline: none !important;
    }

    /* Remove the default bright border on checkboxes in dark mode */
    html.dark [data-flux-checkbox-indicator] {
        border-color: transparent !important;
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
</style>
