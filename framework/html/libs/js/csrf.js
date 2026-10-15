/* Token CSRF global de Issabel.
 *
 * El token se publica en el <meta name="issabel-csrf-token"> de la página
 * (plantillas index.tpl e iframe_module.tpl de cada tema; ver
 * libs/issabelCsrf.class.php). Este archivo lo agrega como cabecera "X-Issabel-CSRF-Token" a todas las
 * peticiones AJAX de jQuery, de modo que los módulos no tengan que enviarlo a
 * mano. Los formularios normales lo llevan como campo oculto
 * "issabel_csrf_token".
 */
(function () {
    'use strict';

    if (typeof window.jQuery === 'undefined') return;

    var meta = document.querySelector('meta[name="issabel-csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    if (!token) return;

    window.jQuery.ajaxPrefilter(function (options, originalOptions, jqXHR) {
        if (!options.crossDomain) {
            jqXHR.setRequestHeader('X-Issabel-CSRF-Token', token);
        }
    });
})();
