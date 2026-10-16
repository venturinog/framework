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

    window.IssabelCharts = {
        colors: colors,
        applyTheme: applyTheme
    };
})();
