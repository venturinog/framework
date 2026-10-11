<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel 6 - tema "Modern"                                            |
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
  $Id: themesetup.php $
*/
function themeSetup(&$smarty, $selectedMenu, $pdbACL, $pACL, $idUser)
{
    $lang = get_language();

    /* RTL según el idioma seleccionado (D15) */
    $isRtl = in_array(strtolower(substr($lang, 0, 2)), array('fa', 'ar', 'he', 'iw', 'ur', 'yi'));

    $arrMainMenu = $smarty->get_template_vars('arrMainMenu');
    foreach ($arrMainMenu as $idMenu => $arrMenuItem) {
        $arrMainMenu[$idMenu]['icon'] = setIconModern($idMenu);
    }

    $smarty->assign('arrMainMenu', $arrMainMenu);
    $smarty->assign('LANG', $lang);
    $smarty->assign('IS_RTL', $isRtl);

    /* Cabecera de página: breadcrumb + título grande (mockup .iss-pagehead).
     * El último elemento del breadcrumb es el módulo actual. Si no hay
     * breadcrumb (p.ej. Dashboard) se usa el nombre del menú seleccionado. */
    $breadcrumb = setBreadcrumbModern($arrMainMenu, $selectedMenu);
    if (!is_array($breadcrumb)) $breadcrumb = array();
    $pageTitle = count($breadcrumb) ? end($breadcrumb) : '';
    if ($pageTitle === '') {
        foreach (array('nameSubMenu2Selected', 'nameSubMenuSelected', 'nameMainMenuSelected') as $navVar) {
            $candidate = $smarty->get_template_vars($navVar);
            if (!empty($candidate)) { $pageTitle = $candidate; break; }
        }
    }

    $extension = $pACL->getUserExtension($_SESSION['issabel_user']);
    $smarty->assign(array(
        "ABOUT_ISSABEL2"            =>  _tr('About Issabel'),
        "HELP"                      =>  _tr('HELP'),
        "USER_LOGIN"                =>  $_SESSION['issabel_user'],
        "USER_ID"                   =>  $idUser,
        "EXTENSION"                 =>  $extension,
        "CHANGE_PASSWORD"           =>  _tr("Change Issabel Password"),
        "MODULES_SEARCH"            =>  _tr("Search modules"),
        "ADD_BOOKMARK"              =>  _tr("Add Bookmark"),
        "REMOVE_BOOKMARK"           =>  _tr("Remove Bookmark"),
        "ADDING_BOOKMARK"           =>  _tr("Adding Bookmark"),
        "REMOVING_BOOKMARK"         =>  _tr("Removing Bookmark"),
        "HIDING_IZQTAB"             =>  _tr("Hiding left panel"),
        "SHOWING_IZQTAB"            =>  _tr("Loading left panel"),
        "HIDE_IZQTAB"               =>  _tr("Hide left panel"),
        "SHOW_IZQTAB"               =>  _tr("Load left panel"),
        "APPEARANCE"                =>  _tr("Appearance"),
        "THEME_AUTO"                =>  _tr("Auto"),
        "THEME_LIGHT"               =>  _tr("Light"),
        "THEME_DARK"                =>  _tr("Dark"),

        'viewMenuTab'               =>  getStatusNeoTabToggle($pdbACL, $idUser),
        'MENU_COLOR'                =>  getMenuColorByMenu($pdbACL, $idUser),
        'IMG_BOOKMARKS'             =>  menuIsBookmark($pdbACL, $idUser, $selectedMenu) ? 'bookmarkon.png' : 'bookmark.png',
        'SHORTCUT'                  =>  loadShortcut($pdbACL, $idUser, $smarty),
        'BREADCRUMB'                =>  $breadcrumb,
        'PAGE_TITLE'                =>  $pageTitle,
    ));

    $notifications = loadSystemNotifications($pdbACL, $idUser);
    $smarty->assign('NOTIFICATIONS', $notifications);
    $smarty->assign('NOTIF_COUNT',
        count($notifications['NOTIFICATIONS_PUBLIC']) + count($notifications['NOTIFICATIONS_PRIVATE']));
}

/* Iconos Tabler para los items de primer nivel del menú */
function setIconModern($idMenu)
{
    switch ($idMenu) {
        case 'manager':     return 'ti ti-adjustments';
        case 'system':      return 'ti ti-settings';
        case 'email_admin': return 'ti ti-mail';
        case 'security':    return 'ti ti-shield-lock';
        case 'pbxconfig':   return 'ti ti-phone';
        case 'fax':         return 'ti ti-printer';
        case 'reports':     return 'ti ti-chart-bar';
        case 'im':          return 'ti ti-message-circle';
        case 'agenda':      return 'ti ti-calendar';
        case 'my_extension':return 'ti ti-phone-call';
        case 'addons':      return 'ti ti-box';
        case 'extras':      return 'ti ti-puzzle';
        case 'storage':     return 'ti ti-database';
        default:            return 'ti ti-chevron-right';
    }
}

function setBreadcrumbModern($arrMainMenu, $currentMenu)
{
    foreach ($arrMainMenu as $key => $value) {
        $breadcrumb = array();
        array_push($breadcrumb, $value["Name"]);
        if ($key == $currentMenu) return $breadcrumb;

        foreach ($arrMainMenu[$key]["children"] as $akey) {
            if (count($breadcrumb) > 1) array_pop($breadcrumb);
            array_push($breadcrumb, $akey["Name"]);
            if ($akey["id"] == $currentMenu) return $breadcrumb;

            if ($akey["HasChild"]) {
                foreach ($akey["children"] as $bkey => $bvalue) {
                    if ($bkey == $currentMenu) {
                        array_push($breadcrumb, $bvalue["Name"]);
                        return $breadcrumb;
                    }
                }
            }
        }
    }
}

function loadSystemNotifications($pdbACL, $idUser)
{
    require_once 'libs/paloSantoNotification.class.php';

    $pNot = new paloNotification($pdbACL);
    $a = array(
        'LBL_NOTIFICATION_SYSTEM'   =>  _tr('System'),
        'LBL_NOTIFICATION_USER'     =>  _tr('User'),
        'NOTIFICATIONS_PUBLIC'      =>  $pNot->listPublicNotifications(3),
        'NOTIFICATIONS_PRIVATE'     =>  $pNot->listUserNotifications($idUser, 3),
        'TXT_NO_NOTIFICATIONS'      =>  _tr('No notifications'),
    );
    return $a;
}
?>
