<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 6                                                    |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2026 Issabel Foundation                                |
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
  | The Initial Developer of the Original Code is Issabel Foundation     |
  +----------------------------------------------------------------------+
*/

/**
 * Aviso de página del tema Modern.
 *
 * Las plantillas de los módulos imprimen el HTML que devuelve este helper
 * dentro de la propia página. Así el aviso conserva su variante (éxito, error,
 * aviso, información) en lugar de reutilizar `mb_message`, que la shell del
 * iframe pinta siempre como error.
 *
 * Uso desde PHP:
 *
 *   $smarty->assign('SECURITY_ALERT', issabel_alert_html('success', _tr('Message'), _tr('Saved')));
 *
 * Uso desde una plantilla:
 *
 *   {if $SECURITY_ALERT}{$SECURITY_ALERT}{/if}
 *
 * El mensaje se escapa por defecto. Si el módulo construye HTML controlado
 * (por ejemplo varias líneas con <br>), debe pasar array('html' => true) y
 * escapar cada dato interpolado por su cuenta.
 */
class issabelAlert
{
    /** Variantes válidas de la familia `.iss-alert`. */
    private static $types = array('error', 'success', 'warning', 'info');

    /**
     * Devuelve el HTML de un aviso del tema.
     *
     * @param string $type    error|success|warning|info
     * @param string $title   Título corto (se escapa).
     * @param string $message Cuerpo del aviso.
     * @param array  $options id, dismiss (bool), html (bool)
     * @return string
     */
    public static function render($type, $title = '', $message = '', $options = array())
    {
        $type = strtolower(trim((string)$type));
        if (!in_array($type, self::$types, true)) $type = 'info';

        $id      = isset($options['id']) ? (string)$options['id'] : '';
        $dismiss = !isset($options['dismiss']) || $options['dismiss'];
        $isHtml  = !empty($options['html']);

        $html = '<div class="iss-alert iss-alert--'.$type.'"';
        if ($id !== '') {
            $html .= ' id="'.htmlspecialchars($id, ENT_COMPAT, 'UTF-8').'"';
        }
        $html .= ' role="alert">';

        if ($title !== '') {
            $html .= '<div class="iss-alert__title">'
                   . htmlspecialchars($title, ENT_COMPAT, 'UTF-8')
                   . '</div>';
        }

        if ($dismiss) {
            $closeLabel = function_exists('_tr') ? _tr('Close') : 'Close';
            $html .= '<div class="iss-alert__dismiss">'
                   . '<i class="ti ti-x" role="button" tabindex="0" aria-label="'
                   . htmlspecialchars($closeLabel, ENT_COMPAT, 'UTF-8').'"'
                   . ' onclick="this.parentNode.style.display=\'none\';"></i>'
                   . '</div>';
        }

        $body = $isHtml ? (string)$message : htmlspecialchars((string)$message, ENT_COMPAT, 'UTF-8');
        $html .= '<div class="iss-alert__content">'.$body.'</div>';
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('issabel_alert_html')) {
    /**
     * Atajo funcional de issabelAlert::render() para el código de los módulos.
     */
    function issabel_alert_html($type, $title = '', $message = '', $options = array())
    {
        return issabelAlert::render($type, $title, $message, $options);
    }
}
