/*
 * issabel-charts.js — Issabel Chart.js helper (theme-aware defaults)
 *
 * Parte del tema `modern`. NO es una librería en sí: asume que Chart.js ya está
 * cargado en la página y solo aplica los defaults de color/fuente del tema para
 * que las gráficas funcionen en claro y oscuro sin que cada módulo repita el
 * cálculo de colores.
 *
 * Orden de carga recomendado:
 *   moment.min.js
 *   chart.umd.min.js
 *   chartjs-adapter-moment.min.js   (solo si se usan escalas de tiempo)
 *   chartjs-plugin-datalabels.min.js (solo si se usan etiquetas de datos)
 *   issabel-charts.js               (este archivo)
 *
 * El plugin de datalabels NO se registra aquí a propósito: registrarlo sería
 * global y agregaría etiquetas a todas las gráficas. Cada módulo lo usa en su
 * propia gráfica con `plugins: [ChartDataLabels]`.
 *
 * Licencia: GPLv2+ (código Issabel). Chart.js y los plugins son MIT.
 */
(function () {
    'use strict';

    if (typeof window.Chart === 'undefined') return;

    var C = window.Chart;

    function isDark() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    function colors() {
        var dark = isDark();
        return {
            fg:   dark ? '#e6e9ee' : '#667382',
            grid: dark ? 'rgba(255,255,255,0.12)' : 'rgba(0,0,0,0.10)',
            font: "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
        };
    }

    function applyTheme() {
        var c = colors();
        C.defaults.color = c.fg;
        C.defaults.borderColor = c.grid;
        if (C.defaults.font) C.defaults.font.family = c.font;
    }

    applyTheme();

    // Re-aplica los defaults cuando cambia el modo claro/oscuro.
    if (window.MutationObserver) {
        new MutationObserver(function () { applyTheme(); })
            .observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-bs-theme']
            });
    }

    /* Paleta categorica para graficas con varias series (torta, barras).
     * Los colores se leen de las variables CSS del tema, de modo que el modulo
     * no lleva ningun color escrito y la paleta sigue al tema. */
    var PALETTE_TOKENS = [
        '--iss-primary', '--tblr-blue', '--tblr-teal', '--tblr-orange',
        '--tblr-pink', '--tblr-cyan', '--tblr-green', '--tblr-yellow',
        '--tblr-indigo', '--tblr-purple', '--tblr-red', '--tblr-azure'
    ];

    function palette(count) {
        var n = Math.max(1, parseInt(count, 10) || 1);
        var styles = window.getComputedStyle(document.documentElement);
        var colors = [];
        for (var i = 0; i < PALETTE_TOKENS.length; i++) {
            var value = styles.getPropertyValue(PALETTE_TOKENS[i]);
            if (value) colors.push(value.trim());
        }
        /* Sin tokens resueltos (tema sin las variables) se usa el color de
         * marca, que es el valor de --iss-primary en el tema Modern. */
        if (colors.length === 0) colors.push('#572364');
        var out = [];
        for (var j = 0; j < n; j++) out.push(colors[j % colors.length]);
        return out;
    }

    window.IssabelCharts = {
        colors: colors,
        palette: palette,
        applyTheme: applyTheme
    };
})();
