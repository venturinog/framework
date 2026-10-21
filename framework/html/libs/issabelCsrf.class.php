<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 6                                                    |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
*/

/**
 * Helper CSRF global de Issabel.
 *
 * Un único token por sesión (32 bytes aleatorios) que se publica en los
 * formularios como campo oculto `issabel_csrf_token` y en los requerimientos
 * AJAX como cabecera `X-Issabel-CSRF-Token`. El token se comparte con el
 * gestor de pestañas de la GUI (antes `issabel_tabs_csrf`), de modo que hay un
 * solo ciclo de vida: se genera al abrir la sesión y se rota al autenticar.
 *
 * La validación global vive en `issabel_csrf_guard()` (misc.lib.php), que se
 * invoca desde index.php antes de despachar el módulo. El modo se controla con
 * `$arrConf['csrf_mode']`:
 *
 *   off      no hace nada.
 *   audit    registra en /var/log/issabel/audit.log los requerimientos
 *            mutantes que llegan sin token válido, sin bloquearlos.
 *   enforce  además responde 403 a esos requerimientos.
 *
 * Los módulos nuevos deben usar este helper: los formularios generados con
 * paloForm/paloSantoGrid lo reciben automáticamente; el resto de plantillas
 * deben imprimir `{$ISSABEL_CSRF_FIELD}` dentro del <form>, o llamar a
 * `issabel_csrf_field()`.
 */
class issabelCsrf
{
    /** Clave del token dentro de $_SESSION. */
    const SESSION_KEY = 'issabel_csrf';

    /** Nombre del campo oculto en formularios. */
    const FIELD_NAME = 'issabel_csrf_token';

    /** Cabecera aceptada en requerimientos AJAX. */
    const HEADER_NAME = 'HTTP_X_ISSABEL_CSRF_TOKEN';

    /**
     * Verbos de acción de solo lectura. Una petición GET que lleva `action` o
     * `task` con uno de estos valores no cambia estado y no exige token; así se
     * evita ruido innecesario en la auditoría (Issabel hereda el patrón de
     * actuar sobre $_REQUEST también por GET).
     */
    private static $readOnlyActions = array(
        'list', 'show', 'view', 'display', 'get', 'read', 'search',
        'index', 'default',
        // Navegaciones de solo lectura del módulo de red (abren formularios).
        'editinterfase', 'route_new', 'route_edit',
        // Navegaciones de solo lectura de usuarios y permisos: abren el
        // formulario de edición/alta pero no guardan nada (el POST es el que
        // muta). Sin esto, un GET con action=edit genera ruido CSRF-AUDIT y
        // bajo modo enforce bloquearía la apertura del formulario.
        'new', 'edit', 'edituserextension', 'assigncustom',
        // Verbos de solo lectura de los modulos de reportes: devuelven una
        // imagen o una ventana con los datos del reporte, no guardan nada.
        'grafic', 'grafic_queue', 'grafic_trunk', 'grafic_trunk2',
        'image', 'graph', 'view_edit',
        // Selector de numeros de telefono de la agenda: solo consulta.
        'phone_numbers',
    );

    /**
     * Devuelve el token de la sesión, generándolo si todavía no existe.
     *
     * @return string
     */
    public static function token()
    {
        if (!isset($_SESSION)) $_SESSION = array();
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            self::rotate();
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Genera un token nuevo, descartando el anterior. Se llama al autenticar
     * (session_regenerate_id) para que el token de la sesión anónima no siga
     * siendo válido una vez logueado el usuario.
     *
     * @return string
     */
    public static function rotate()
    {
        if (!isset($_SESSION)) $_SESSION = array();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Devuelve el campo oculto listo para incluir en un formulario.
     *
     * @return string
     */
    public static function field()
    {
        return sprintf('<input type="hidden" name="%s" value="%s" />',
            self::FIELD_NAME,
            htmlspecialchars(self::token(), ENT_COMPAT, 'UTF-8'));
    }

    /**
     * Devuelve el token enviado en la petición (campo de formulario o
     * cabecera), o cadena vacía si no vino ninguno.
     *
     * @return string
     */
    public static function requestToken()
    {
        if (isset($_POST[self::FIELD_NAME]) && is_string($_POST[self::FIELD_NAME])) {
            return $_POST[self::FIELD_NAME];
        }
        if (isset($_SERVER[self::HEADER_NAME]) && is_string($_SERVER[self::HEADER_NAME])) {
            return $_SERVER[self::HEADER_NAME];
        }
        return '';
    }

    /**
     * Compara el token enviado con el de la sesión en tiempo constante.
     *
     * @return bool
     */
    public static function validate()
    {
        $given = self::requestToken();
        if ($given === '') return false;
        return hash_equals(self::token(), $given);
    }

    /**
     * Indica si la petición actual puede modificar estado y por lo tanto exige
     * token. Los métodos mutantes siempre lo exigen; un GET solo cuando lleva
     * una acción de escritura (`action`/`task` fuera de la lista de solo
     * lectura).
     *
     * @return bool
     */
    public static function needsValidation()
    {
        $method = isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        if (in_array($method, array('POST', 'PUT', 'DELETE', 'PATCH'), true)) {
            return true;
        }
        if ($method !== 'GET') return false;

        foreach (array('action', 'task') as $param) {
            if (isset($_GET[$param]) && is_string($_GET[$param])) {
                $verb = strtolower(trim($_GET[$param]));
                if (!in_array($verb, self::$readOnlyActions, true)) return true;
            }
        }
        return false;
    }

    /**
     * Indica si la petición queda exenta de validación. Solo cubre casos en los
     * que no existe (o no puede existir) token de sesión, o acciones de solo
     * lectura, más la lista `$arrConf['csrf_exempt_modules']`.
     *
     * @return bool
     */
    public static function isExempt()
    {
        // Login y segundo factor: todavía no hay usuario autenticado.
        if (isset($_POST['submit_login']) || isset($_POST['input_code'])) return true;

        // El gestor de pestañas valida su propio token internamente.
        if (isset($_GET['tabmgr'])) return true;

        // Logout por enlace GET; solo destruye la sesión propia.
        if (isset($_GET['logout'])) return true;

        // Exportaciones de grillas (solo lectura).
        foreach (array('exportcsv', 'exportpdf', 'exportspreadsheet') as $param) {
            if ((isset($_GET[$param]) && $_GET[$param] === 'yes')
                || (isset($_POST[$param]) && $_POST[$param] === 'yes')) return true;
        }

        // Módulos exentos declarados en configs/default.conf.php.
        $exempt = self::exemptModules();
        if (!empty($exempt)) {
            $menu = isset($_GET['menu']) ? $_GET['menu']
                : (isset($_POST['menu']) ? $_POST['menu'] : '');
            if (is_string($menu) && $menu !== '' && in_array($menu, $exempt, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inserta el campo oculto justo después de la primera etiqueta <form> del
     * HTML recibido. Se usa desde paloForm y paloSantoGrid para no tener que
     * tocar cada plantilla. Si el campo ya está presente no duplica nada, y si
     * el formulario es GET no se inyecta nada (el token quedaría en la URL).
     *
     * @param string $html
     * @return string
     */
    public static function injectField($html)
    {
        if (!is_string($html) || $html === '') return $html;
        if (strpos($html, 'name="'.self::FIELD_NAME.'"') !== false) return $html;

        $field = self::field();
        $injected = preg_replace_callback('/<form\b[^>]*>/i',
            function ($match) use ($field) {
                // Un <form method="get"> no lleva token: acabaría en la URL.
                if (preg_match('/\bmethod\s*=\s*["\']?\s*get\b/i', $match[0])) {
                    return $match[0];
                }
                return $match[0]."\n".$field;
            },
            $html, 1, $count);
        return ($count > 0 && is_string($injected)) ? $injected : $html;
    }

    /**
     * Devuelve la lista de módulos exentos declarada en la configuración.
     *
     * @return array
     */
    public static function exemptModules()
    {
        global $arrConf;
        if (!isset($arrConf['csrf_exempt_modules']) || !is_array($arrConf['csrf_exempt_modules'])) {
            return array();
        }
        return $arrConf['csrf_exempt_modules'];
    }

    /**
     * Devuelve el modo configurado de validación CSRF normalizado.
     *
     * @return string 'off', 'audit' o 'enforce'
     */
    public static function mode()
    {
        global $arrConf;
        $mode = isset($arrConf['csrf_mode']) ? strtolower(trim((string)$arrConf['csrf_mode'])) : 'audit';
        return in_array($mode, array('off', 'audit', 'enforce'), true) ? $mode : 'audit';
    }
}
