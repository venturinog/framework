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
 * Registro en /var/log/issabel/audit.log de los cambios sobre el ACL: usuarios
 * (módulo userlist), grupos (grouplist) y permisos por grupo
 * (group_permission).
 *
 * La línea sigue el mismo patrón que el resto de la auditoría:
 *
 *   ACL <usuario>: <evento> <clave=valor ...> result=<ok|error> from <ip>.
 *
 * Eventos y claves que se usan hoy:
 *
 *   user.create        login= id= group=
 *   user.update        login= id= fields=name,description,group,password
 *                      [new_login=] [new_group=]
 *   user.delete        login= id=
 *   group.create       group= description=
 *   group.update       id= group= [old_group=]
 *   group.delete       id= group=
 *   permissions.apply  group= [added=a,b] [removed=c,d]
 *   privileges.apply   group= resource= [granted=x,y] [revoked=z]
 *
 * Las claves "added", "removed", "granted" y "revoked" reciben un arreglo y se
 * escriben separadas por comas. Nunca se registran contraseñas, ni siquiera su
 * hash: en `user.update` sólo aparece "password" dentro de `fields`.
 *
 * Todos los valores se sanean antes de escribirse: se eliminan los caracteres
 * de control (incluidos CR y LF) y los no imprimibles, y se recortan. No es
 * cosmético: `audit.log` lo lee el jail `issabel-gui` de fail2ban, así que un
 * salto de línea en un login o en una descripción podría inyectar una línea
 * "LOGIN ... Failed password ..." y provocar el baneo de una IP arbitraria.
 */
class issabelAclAudit
{
    /** Longitud máxima de un valor normal (login, nombre de grupo, ...). */
    const MAX_VALUE = 64;

    /** Longitud máxima del mensaje de error. */
    const MAX_MSG = 200;

    /** Longitud máxima de una lista (módulos o privilegios). */
    const MAX_LIST = 300;

    /** Tope de la línea completa, para no crecer sin control. */
    const MAX_LINE = 500;

    /**
     * Escribe una línea de auditoría para una acción sobre el ACL.
     *
     * @param string $event   Identificador de la acción (p. ej. "user.create").
     * @param string $result  "ok" o "error".
     * @param array  $details Pares clave => valor que se añaden a la línea. Un
     *                        arreglo se escribe como lista separada por comas.
     *                        La clave "msg" se recorta a MAX_MSG.
     */
    public static function log($event, $result, array $details = array())
    {
        $line = sprintf('ACL %s: %s', self::user(), self::clean($event, 40));

        $fields = array();
        foreach ($details as $key => $value) {
            if ($value === NULL) continue;

            $key = preg_replace('/[^a-z_]/', '', strtolower((string)$key));
            if ($key === '') continue;

            if (is_array($value)) {
                $value = implode(',', array_map('strval', $value));
                $max = self::MAX_LIST;
            } else {
                $max = ($key === 'msg') ? self::MAX_MSG : self::MAX_VALUE;
            }

            $value = self::clean($value, $max);
            if ($value === '') continue;

            // Los valores con espacios (descripciones, mensajes) van entre
            // comillas; clean() ya ha convertido las comillas del valor.
            if (strpos($value, ' ') !== FALSE) $value = '"'.$value.'"';
            $fields[] = $key.'='.$value;
        }
        if (count($fields) > 0) $line .= ' '.implode(' ', $fields);

        $line .= ' result='.(($result === 'error') ? 'error' : 'ok');
        $line .= ' from '.self::remote().'.';

        if (strlen($line) > self::MAX_LINE) {
            $line = substr($line, 0, self::MAX_LINE - 3).'...';
        }

        // writeLOG() lo provee framework; el respaldo permite usar la clase en
        // contextos sin la GUI cargada (CLI, pruebas).
        if (function_exists('writeLOG')) writeLOG('audit.log', $line);
        else error_log($line);
    }

    /**
     * Arma la lista de campos modificados de un usuario para `fields=`.
     * El orden es estable para que la línea sea comparable entre ejecuciones.
     *
     * @param array $changed Mapa campo => bool con los campos que cambiaron.
     * @return string        Lista separada por comas, o cadena vacía.
     */
    public static function changedFields(array $changed)
    {
        $names = array();
        foreach (array('name', 'description', 'group', 'password') as $field) {
            if (!empty($changed[$field])) $names[] = $field;
        }
        return implode(',', $names);
    }

    /** Usuario de la sesión, restringido a caracteres seguros. */
    private static function user()
    {
        $user = isset($_SESSION['issabel_user']) ? (string)$_SESSION['issabel_user'] : '';
        $user = preg_replace('/[^A-Za-z0-9._@-]/', '', $user);
        if ($user === '') return 'unknown';
        return substr($user, 0, 32);
    }

    /** IP de origen, validada. */
    private static function remote()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return 'unknown';
        return $ip;
    }

    /**
     * Deja un valor en una sola línea, ASCII imprimible y acotado.
     */
    private static function clean($value, $max)
    {
        $value = (string)$value;
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
        $value = str_replace('"', "'", $value);
        $value = trim(preg_replace('/\s+/', ' ', $value));
        if (strlen($value) > $max) $value = substr($value, 0, $max).'...';
        return $value;
    }
}
