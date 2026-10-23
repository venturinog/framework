/*
 * Validación de cliente del tema Modern.
 *
 * El servidor es la fuente de verdad: este helper sólo da feedback inmediato
 * (borde verde/rojo) y bloquea el envío si algún campo no cumple. No sustituye
 * la revalidación del handler PHP.
 *
 * Uso desde una plantilla:
 *
 *   <script src="{$WEBPATH}libs/js/issabel-validate.js"></script>
 *   <script>
 *   {literal}
 *   IssabelValidate.attach(document.querySelector('form'), {
 *       ip_address: function (v) { return v !== '' && /^(\d{1,3}\.){3}\d{1,3}$/.test(v); },
 *       port:       /^[0-9]+$/
 *   });
 *   {/literal}
 *   </script>
 *
 * Reglas: función (recibe valor y elemento), RegExp o cualquier objeto con
 * test(). Un campo vacío sólo es válido si la regla lo acepta; los opcionales
 * deben comprobar `v === ''` y devolver true.
 *
 * El botón Cancelar queda exento: se reconoce por `name` que empiece por
 * "cancel" (cancel, cancel_save, cancel_edit...).
 */
(function (global) {
    'use strict';

    function matches(el, rule) {
        var value = el.value;
        if (typeof rule === 'function') return !!rule(value, el);
        if (rule instanceof RegExp) return rule.test(value);
        if (rule && typeof rule.test === 'function') return rule.test(value);
        return true;
    }

    function mark(el, valid) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(valid ? 'is-valid' : 'is-invalid');
    }

    function field(form, name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function attach(form, rules) {
        if (!form || !rules) return;

        Object.keys(rules).forEach(function (name) {
            var el = field(form, name);
            if (!el) return;
            ['input', 'blur'].forEach(function (ev) {
                el.addEventListener(ev, function () {
                    mark(el, matches(el, rules[name]));
                });
            });
        });

        form.addEventListener('submit', function (e) {
            var submitter = e.submitter;
            if (submitter && /^cancel/i.test(submitter.name || '')) return;

            var ok = true;
            Object.keys(rules).forEach(function (name) {
                var el = field(form, name);
                if (!el) return;
                var valid = matches(el, rules[name]);
                mark(el, valid);
                if (!valid) ok = false;
            });
            if (!ok) {
                e.preventDefault();
                return false;
            }
        });
    }

    global.IssabelValidate = {
        attach: attach,
        matches: matches,
        mark: mark
    };
})(window);
