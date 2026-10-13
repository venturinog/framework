<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
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
  $Id: index.php, Thu 14 Mar 2024 05:42:23 PM EDT, nicolas@issabel.com
*/

function spl_issabel_class_autoload($sNombreClase)
{
    if (!preg_match('/^\w+$/', $sNombreClase)) return;

    $sNombreBase = $sNombreClase.'.class.php';
    foreach (explode(':', ini_get('include_path')) as $sDirInclude) {
        if (file_exists($sDirInclude.'/'.$sNombreBase)) {
            require_once($sNombreBase);
            return;
        }
    }
}
spl_autoload_register('spl_issabel_class_autoload');

function get_post_login_redirect($pACL, $sUsername)
{
    if (isset($_SESSION['rd']) && $_SESSION['rd'] === 'phone') {
        unset($_SESSION['rd']);
        return '/phone';
    }
    $idUser = $pACL->getIdUser($sUsername);
    $groups = ($idUser !== FALSE) ? $pACL->getMembership($idUser) : NULL;
    if (is_array($groups) && isset($groups['Webphone'])) {
        return '/phone';
    }
    return 'index.php';
}

function issabel_tabs_json_response($status, array $payload)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload);
    exit;
}

function issabel_tabs_same_origin_request()
{
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if ($origin === '' && !empty($_SERVER['HTTP_REFERER'])) {
        $parts = parse_url($_SERVER['HTTP_REFERER']);
        if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
            $origin = $parts['scheme'].'://'.$parts['host'];
            if (isset($parts['port'])) $origin .= ':'.$parts['port'];
        }
    }

    $https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
    $expected = ($https ? 'https' : 'http').'://'.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
    return $origin !== '' && hash_equals($expected, $origin);
}

/** Recover tab context when a module's relative redirect drops iframe params. */
function issabel_tabs_iframe_referer_context()
{
    if (empty($_SERVER['HTTP_REFERER'])) return null;
    if (!empty($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] !== 'iframe') return null;

    $referer = parse_url($_SERVER['HTTP_REFERER']);
    if (!is_array($referer) || empty($referer['scheme']) || empty($referer['host']) || empty($referer['path'])) return null;

    $https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
    $expectedScheme = $https ? 'https' : 'http';
    $expectedHost = isset($_SERVER['HTTP_HOST']) ? parse_url($expectedScheme.'://'.$_SERVER['HTTP_HOST']) : false;
    if (!is_array($expectedHost) || strtolower($referer['scheme']) !== $expectedScheme ||
        strtolower($referer['host']) !== strtolower($expectedHost['host'])) return null;

    $refererPort = isset($referer['port']) ? (int)$referer['port'] : (($referer['scheme'] === 'https') ? 443 : 80);
    $expectedPort = isset($expectedHost['port']) ? (int)$expectedHost['port'] : ($https ? 443 : 80);
    if ($refererPort !== $expectedPort) return null;
    if (isset($_SERVER['SCRIPT_NAME']) && $referer['path'] !== $_SERVER['SCRIPT_NAME']) return null;

    $query = array();
    parse_str(isset($referer['query']) ? $referer['query'] : '', $query);
    if (!isset($query['rawmode']) || $query['rawmode'] !== 'iframe' ||
        !isset($query['iss_instance']) || !issabelTabs::isValidInstanceId($query['iss_instance'])) return null;

    return array(
        'instanceId' => $query['iss_instance'],
        'menu' => isset($query['menu']) && is_string($query['menu']) ? $query['menu'] : '',
        'tabId' => isset($query['iss_tab']) && ctype_digit((string)$query['iss_tab'])
            ? (int)$query['iss_tab'] : null,
    );
}

function issabel_tabs_handle_request($pdbACL, $pACL, $idUser, $maxTabs, $csrfToken)
{
    $action = isset($_GET['tabmgr']) ? $_GET['tabmgr'] : '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        issabel_tabs_json_response(405, array('error' => 'method_not_allowed'));
    }

    $instanceId = isset($_SERVER['HTTP_X_ISSABEL_INSTANCE_ID'])
        ? $_SERVER['HTTP_X_ISSABEL_INSTANCE_ID'] : '';
    if (!issabelTabs::isValidInstanceId($instanceId)) {
        issabel_tabs_json_response(400, array('error' => 'invalid_instance'));
    }

    $requestToken = isset($_SERVER['HTTP_X_ISSABEL_TABS_CSRF'])
        ? $_SERVER['HTTP_X_ISSABEL_TABS_CSRF']
        : (isset($_POST['tabs_csrf']) ? $_POST['tabs_csrf'] : '');
    if (!is_string($requestToken) || !hash_equals($csrfToken, $requestToken) ||
        !issabel_tabs_same_origin_request()) {
        issabel_tabs_json_response(403, array('error' => 'request_rejected'));
    }

    // Release PHP's session lock before taking the independent JSON store lock.
    $sessionId = session_id();
    session_write_close();

    try {
        $tabs = new issabelTabs($sessionId, $instanceId, null, $maxTabs);
        switch ($action) {
            case 'init':
                $state = $tabs->initialize();
                issabel_tabs_json_response(200, $state);

            case 'create':
                $result = $tabs->createDashboardLanding();
                issabel_tabs_json_response($result['created'] ? 200 : 409, $result);

            case 'activate':
                $state = $tabs->setActive(isset($_POST['id']) ? $_POST['id'] : 0);
                if ($state === null) issabel_tabs_json_response(404, array('error' => 'tab_not_found'));
                issabel_tabs_json_response(200, $state);

            case 'close':
                $result = $tabs->close(isset($_POST['id']) ? $_POST['id'] : 0);
                issabel_tabs_json_response($result['closed'] ? 200 : 409, $result);

            case 'update':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $key = isset($_POST['key']) && is_string($_POST['key']) ? $_POST['key'] : '';
                $kind = isset($_POST['kind']) && $_POST['kind'] === 'pbx' ? 'pbx' : 'issabel';
                $label = isset($_POST['label']) && is_string($_POST['label'])
                    ? trim(strip_tags($_POST['label'])) : '';
                $label = function_exists('mb_substr') ? mb_substr($label, 0, 120, 'UTF-8') : substr($label, 0, 120);
                $title = isset($_POST['title']) && is_string($_POST['title'])
                    ? trim(strip_tags($_POST['title'])) : $label;
                $title = function_exists('mb_substr') ? mb_substr($title, 0, 120, 'UTF-8') : substr($title, 0, 120);
                $menuId = isset($_POST['menuId']) && is_string($_POST['menuId'])
                    ? $_POST['menuId'] : (($kind === 'pbx') ? 'pbxadmin' : $key);
                if (!preg_match('/^[a-zA-Z0-9_.-]{1,80}$/D', $menuId)) $menuId = '';
                $breadcrumb = array();
                if (isset($_POST['breadcrumb']) && is_string($_POST['breadcrumb'])) {
                    $decodedBreadcrumb = json_decode($_POST['breadcrumb'], true);
                    if (is_array($decodedBreadcrumb)) {
                        foreach (array_slice($decodedBreadcrumb, 0, 8) as $crumb) {
                            if (!is_string($crumb)) continue;
                            $crumb = trim(strip_tags($crumb));
                            if ($crumb !== '') {
                                $breadcrumb[] = function_exists('mb_substr')
                                    ? mb_substr($crumb, 0, 80, 'UTF-8') : substr($crumb, 0, 80);
                            }
                        }
                    }
                }

                $isLanding = $kind === 'issabel' && preg_match('/^landing-dashboard-[0-9]+$/D', $key);
                $pbxLanding = $kind === 'pbx' && $key === 'pbx|landing';
                $pbxIdentity = null;
                if ($kind === 'pbx' && !$pbxLanding) {
                    $parts = explode('|', $key, 2);
                    if (count($parts) === 2) $pbxIdentity = $tabs->normalizeKey($parts[0], $parts[1]);
                }
                if (($kind === 'issabel' && !$isLanding && !preg_match('/^[a-zA-Z0-9_.-]{1,80}$/D', $key)) ||
                    ($kind === 'pbx' && !$pbxLanding && ($pbxIdentity === null || $pbxIdentity !== $key))) {
                    issabel_tabs_json_response(400, array('error' => 'invalid_tab_identity'));
                }
                $authKey = ($kind === 'pbx') ? 'pbxadmin' : ($isLanding ? 'dashboard' : $key);
                if (!$pACL->isUserAuthorizedById($idUser, 'access', $authKey)) {
                    issabel_tabs_json_response(403, array('error' => 'module_not_authorized'));
                }

                if ($kind === 'pbx') {
                    $query = array();
                    if (!$pbxLanding) {
                        list($display, $extdisplay) = explode('|', $key, 2);
                        $query[] = 'display='.rawurlencode($display);
                        if ($extdisplay !== '') $query[] = 'extdisplay='.rawurlencode($extdisplay);
                    }
                    $query[] = 'iss_instance='.rawurlencode($instanceId);
                    $query[] = 'iss_tab='.$id;
                    $src = '/admin/config.php?'.implode('&', $query);
                } else {
                    $srcKey = $isLanding ? 'dashboard' : $key;
                    $src = 'index.php?menu='.rawurlencode($srcKey)
                        .'&rawmode=iframe&iss_instance='.rawurlencode($instanceId)
                        .'&iss_tab='.$id;
                }
                $result = $tabs->update($id, array(
                    'kind' => $kind,
                    'key' => $key,
                    'label' => ($label === '') ? $key : $label,
                    'title' => ($title === '') ? (($label === '') ? $key : $label) : $title,
                    'breadcrumb' => $breadcrumb,
                    'menuId' => $menuId,
                    'src' => $src,
                    'loaded' => true,
                ));

                if (!empty($result['updated']) && $kind === 'issabel' && !$isLanding &&
                    isset($_POST['history']) && $_POST['history'] === '1') {
                    putMenuAsHistory($pdbACL, $pACL, $idUser, $key);
                }
                $status = (!empty($result['updated']) || isset($result['duplicate'])) ? 200 : 404;
                issabel_tabs_json_response($status, $result);

            case 'setDirty':
                $dirtyValue = isset($_POST['dirty']) ? strtolower((string)$_POST['dirty']) : '';
                $result = $tabs->update(isset($_POST['id']) ? $_POST['id'] : 0, array(
                    'dirty' => in_array($dirtyValue, array('1', 'true', 'yes'), true),
                ));
                issabel_tabs_json_response(!empty($result['updated']) ? 200 : 404, $result);

            default:
                issabel_tabs_json_response(400, array('error' => 'unknown_action'));
        }
    } catch (Throwable $e) {
        error_log('Issabel tabs request failed: '.$e->getMessage());
        issabel_tabs_json_response(500, array('error' => 'tab_store_error'));
    }
}

// Agregar directorio libs de script a la lista de rutas a buscar para require()
ini_set('include_path', dirname($_SERVER['SCRIPT_FILENAME'])."/libs:".ini_get('include_path'));

include_once("libs/misc.lib.php");
include_once "configs/default.conf.php";
include_once "libs/paloSantoDB.class.php";
include_once "libs/paloSantoMenu.class.php";
include_once "libs/paloSantoNotification.class.php";
include_once("libs/paloSantoACL.class.php");// Don activate unless you know what you are doing. Too risky!

$pp = new IssabelFirstBoot();
load_default_timezone();

session_name("issabelSession");
session_start();

if (isset($_GET['rd']) && $_GET['rd'] === 'phone') {
    $_SESSION['rd'] = 'phone';
}

if(isset($_GET['logout']) && $_GET['logout']=='yes') {
    $user = isset($_SESSION['issabel_user'])?$_SESSION['issabel_user']:"unknown";
    writeLOG("audit.log", "LOGOUT $user: Web Interface logout successful. Accepted logout for $user from $_SERVER[REMOTE_ADDR].");
    session_destroy();
    session_name("issabelSession");
    session_start();
    header("Location: index.php");
    exit;
}

//cargar el archivo de idioma
load_language();
$lang = get_language();

if (!isset($_COOKIE['lang'])) { setcookie("lang", $lang); }

$it = new RecursiveDirectoryIterator("modules/");
foreach(new RecursiveIteratorIterator($it) as $file) {
    if(preg_match("/langmenu\/$lang\.lang/ui",$file)) {
        include_once "$file";
        global $arrLangMenu;
        global $arrLang;
        $arrLang = array_merge($arrLang,$arrLangMenu);
    } 
}

if(file_exists("langmenus/$lang.lang")){
    include_once "langmenus/$lang.lang";
    global $arrLangMenu;
    global $arrLang;
    $arrLang = array_merge($arrLang,$arrLangMenu);
}

if(file_exists("admin/modules")) {
// cargar traducciones de modulos issabelPBX para menu
$it = new RecursiveDirectoryIterator("admin/modules/");
foreach(new RecursiveIteratorIterator($it) as $file) {
    if(preg_match("/titlelang\.php$/ui",$file)) {
        include_once $file;
        if(isset($modTitle[$lang])) {
            foreach($modTitle[$lang] as $title=>$translatedtitle) {
                $arrLang[$title]=$translatedtitle;
            }
        }
    }
}
}

$pdbACL = new paloDB($arrConf['issabel_dsn']['acl']);
$pACL = new paloACL($pdbACL);

if(!empty($pACL->errMsg)) {
    echo "ERROR DE DB: $pACL->errMsg <br>";
}

// Load smarty
$smarty = getSmarty($arrConf['mainTheme'], $arrConf['basePath']);

$smarty->assign("ISSABEL_LICENSED", _tr("is licensed under"));
$smarty->assign("LOGIN_INCORRECT",  '');

// Two Factor Authentication Check
if(isset($_POST['input_code'])) {
    $sec  = $pACL->getTwoFactorSecret($_SESSION['2fa_user']);
    include('modules/sec_2fa/libs/TwoFactorAuth.class.php');
    $tfa  = new TwoFactorAuth('Issabel');
    $code = $tfa->getCode($sec);
    if($_POST['input_code']==$code) {
        $iauth = new IssabelAuth();
        list($access_token,$refresh_token) = $iauth->acquire_jwt_token($_SESSION['2fa_user'],$_SESSION['2fa_pass']);
        $_SESSION['access_token']  = $access_token;
        $_SESSION['refresh_token'] = $refresh_token;
        $_SESSION['issabel_user'] = $_SESSION['2fa_user'];
        $_SESSION['issabel_pass'] = $_SESSION['2fa_pass'];
        header("Location: ".get_post_login_redirect($pACL, $_SESSION['2fa_user']));
        $user = urlencode(substr($_SESSION['2fa_user'],0,20));
        writeLOG("audit.log", "LOGIN $user: Web Interface login successful. Accepted password for $user from $_SERVER[REMOTE_ADDR] using two factor authentication.");
        exit;
    } else {
        list ($color1,$color2) = $tfa->get_theme_colors();
       
        $oPn = new paloSantoNavigation(array(), $smarty);
        $oPn->putHEAD_JQUERY_HTML();
        $sCurYear = date('Y');
        if ($sCurYear < '2013') $sCurYear = '2013';
        $smarty->assign("currentyear", $sCurYear);
        $smarty->assign("THEMENAME", 'tenant');
        $smarty->assign("WEBPATH", '');
        $smarty->assign("SUBMIT", _tr('Submit'));
        $smarty->assign("PAGE_NAME", _tr('Two Factor Authentication'));
        $smarty->assign("WELCOME", _tr('Welcome to Issabel'));
        $smarty->assign("CODE", _tr('Code'));
        $smarty->assign("ISSABEL_LICENSED", _tr("is licensed under"));
        $smarty->assign("LOGIN_COLOR_1", $color1);
        $smarty->assign("LOGIN_COLOR_2", $color2);
        $smarty->display("modules/sec_2fa/templates/default/2fa.tpl");
        $user = urlencode(substr($_SESSION['2fa_user'],0,20));
        writeLOG("audit.log", "LOGIN $user: Authentication Failure to Web Interface login. Failed two factore code for $user from $_SERVER[REMOTE_ADDR].");
        die();
    }
}

//- 1) SUBMIT. Si se hizo submit en el formulario de ingreso
//-            autentico al usuario y lo ingreso a la sesion

if(isset($_POST['submit_login']) and !empty($_POST['input_user'])) {
    $pass_md5 = md5($_POST['input_pass']);
    if($pACL->authenticateUser($_POST['input_user'], $pass_md5)) {
        session_regenerate_id(TRUE);

        if(file_exists('modules/sec_2fa/libs/TwoFactorAuth.class.php')) {
            include('modules/sec_2fa/libs/TwoFactorAuth.class.php');
            $tfa  = new TwoFactorAuth('Issabel');
            $tfa_enabled = $tfa->is_licensed();
        } else {
            $tfa_enabled=false;
        }

        // two factor authentication form, if enabled for user, show it instead of login
        if(method_exists($pACL,'getTwoFactorSecret') && $tfa_enabled==true) {
            $sec = $pACL->getTwoFactorSecret($_POST['input_user']);
            if($sec<>'') {

                list ($color1,$color2) = $tfa->get_theme_colors();

                $oPn = new paloSantoNavigation(array(), $smarty);
                $oPn->putHEAD_JQUERY_HTML();
                $_SESSION['2fa_user'] = $_POST['input_user'];
                $_SESSION['2fa_pass'] = $pass_md5;
                $sCurYear = date('Y');
                if ($sCurYear < '2013') $sCurYear = '2013';
                $smarty->assign("currentyear", $sCurYear);
                $smarty->assign("THEMENAME", 'tenant');
                $smarty->assign("WEBPATH", '');
                $smarty->assign("SUBMIT", _tr('Submit'));
                $smarty->assign("PAGE_NAME", _tr('Two Factor Authentication'));
                $smarty->assign("WELCOME", _tr('Welcome to Issabel'));
                $smarty->assign("CODE", _tr('Code'));
                $smarty->assign("ISSABEL_LICENSED", _tr("is licensed under"));
                $smarty->assign("LOGIN_COLOR_1", $color1);
                $smarty->assign("LOGIN_COLOR_2", $color2);
                $smarty->display("modules/sec_2fa/templates/default/2fa.tpl");
                die();
            }
        }

        $iauth = new IssabelAuth();
        list($access_token,$refresh_token) = $iauth->acquire_jwt_token($_POST['input_user'],$_POST['input_pass']);
        $_SESSION['access_token']  = $access_token;
        $_SESSION['refresh_token'] = $refresh_token;
    
        $_SESSION['issabel_user'] = $_POST['input_user'];
        $_SESSION['issabel_pass'] = $pass_md5;
        header("Location: ".get_post_login_redirect($pACL, $_POST['input_user']));
        writeLOG("audit.log", "LOGIN $_POST[input_user]: Web Interface login successful. Accepted password for $_POST[input_user] from $_SERVER[REMOTE_ADDR].");
        exit;
    } else {
        $user = urlencode(substr($_POST['input_user'],0,20));
        if(!$pACL->getIdUser($_POST['input_user'])) {
            writeLOG("audit.log", "LOGIN $user: Authentication Failure to Web Interface login. Invalid user $user from $_SERVER[REMOTE_ADDR].");
        } else {
            writeLOG("audit.log", "LOGIN $user: Authentication Failure to Web Interface login. Failed password for $user from $_SERVER[REMOTE_ADDR].");
        }
        $smarty->assign("LOGIN_INCORRECT",_tr('Incorrect username or password. Please try again.'));
    }
}

// 2) Autentico usuario
if (isset($_SESSION['issabel_user']) &&
    isset($_SESSION['issabel_pass']) &&
    $pACL->authenticateUser($_SESSION['issabel_user'], $_SESSION['issabel_pass'])) {

    $postLogin = get_post_login_redirect($pACL, $_SESSION['issabel_user']);
    if ($postLogin !== 'index.php') {
        header("Location: ".$postLogin);
        exit;
    }

    setcookie('issaUser',$_SESSION['issabel_user']);
    $idUser = $pACL->getIdUser($_SESSION['issabel_user']);
    if (empty($_SESSION['issabel_tabs_csrf'])) {
        $_SESSION['issabel_tabs_csrf'] = bin2hex(random_bytes(32));
    }
    $ISSABEL_MAX_TABS = isset($arrConf['issabel_max_tabs'])
        ? max(1, (int)$arrConf['issabel_max_tabs']) : 8;
    $ISSABEL_TABS_TOKEN = $_SESSION['issabel_tabs_csrf'];
    $smarty->assign('ISSABEL_MAX_TABS', $ISSABEL_MAX_TABS);
    $smarty->assign('ISSABEL_TABS_TOKEN', $ISSABEL_TABS_TOKEN);
    $tabAssetFiles = array(
        'modernJs' => $arrConf['basePath'].'/themes/modern/js/modern.js',
        'tabsJs' => $arrConf['basePath'].'/themes/modern/js/tabs.js',
        'tabsBridgeJs' => $arrConf['basePath'].'/themes/modern/js/tabs-bridge.js',
        'tabsCss' => $arrConf['basePath'].'/themes/modern/css/tabs.css',
    );
    $tabAssetVersions = array();
    foreach ($tabAssetFiles as $assetName => $assetPath) {
        $tabAssetVersions[$assetName] = is_file($assetPath) ? (string)filemtime($assetPath) : '0';
    }
    $smarty->assign('ISSABEL_TABS_ASSET_VERSIONS', $tabAssetVersions);
    $smarty->assign('ISSABEL_TABS_CONFIG_JSON', json_encode(array(
        'csrf' => $ISSABEL_TABS_TOKEN,
        'max' => $ISSABEL_MAX_TABS,
        'openedToast' => _tr('Opened in another tab'),
        'messages' => array(
            'openNewTab' => _tr('Open a new tab'),
            'closeTab' => _tr('Close %s'),
            'dismissNotice' => _tr('Dismiss tab notice'),
            'tab' => _tr('Tab'),
            'module' => _tr('Module'),
            'moduleFrame' => _tr('Issabel module'),
            'closeUnsaved' => _tr('This tab has unsaved changes. Close it anyway?'),
            'requestRejected' => _tr('The server rejected the tab request. Check your session and try again.'),
            'sessionExpired' => _tr('Your session expired. Reload the page and sign in again.'),
            'moduleNotAuthorized' => _tr('You are not allowed to open this module.'),
            'invalidInstance' => _tr('This tab instance is invalid. Reload the page.'),
            'invalidIdentity' => _tr('The page identity is invalid.'),
            'tabNotFound' => _tr('This tab no longer exists. Reload the page.'),
            'tabStoreError' => _tr('The tab registry could not be saved.'),
            'limit' => _tr('The maximum number of open tabs has been reached.'),
            'lastTab' => _tr('At least one tab must remain open.'),
            'requestFailed' => _tr('The tab request failed'),
            'initFailed' => _tr('Could not initialize tabs.'),
            'browserUnsupported' => _tr('This browser does not support separate tabs. Use a modern browser.'),
            'operationError' => _tr('The tab operation could not be completed.'),
            'retry' => _tr('Retry'),
        ),
    ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));

    // Endpoint sits after authentication and before menu/module dispatch.
    if (isset($_GET['tabmgr'])) {
        issabel_tabs_handle_request($pdbACL, $pACL, $idUser, $ISSABEL_MAX_TABS, $ISSABEL_TABS_TOKEN);
    }

    $pMenu = new paloMenu($arrConf['issabel_dsn']['menu']);
    $arrMenuFiltered = $pMenu->filterAuthorizedMenus($idUser);
    if (!is_array($arrMenuFiltered)) {
        die("FATAL: unable to filter module list for user: ".$pMenu->errMsg);
    }

    verifyTemplate_vm_email(); // para cambiar el template del email ue se envia al recibir un voicemail

    //traducir el menu al idioma correspondiente
    foreach($arrMenuFiltered as $idMenu=>$arrMenuItem) {
        $arrMenuFiltered[$idMenu]['Name'] = _tr($arrMenuItem['Name']);
    }

    $smarty->assign("WEBPATH", '');
    $smarty->assign("WEBCOMMON", 'libs/');
    $smarty->assign("THEMENAME", $arrConf['mainTheme']);

    /*agregado para register*/

    $smarty->assign("Register", addcslashes(_tr("Register"),"'"));
    $smarty->assign("lblRegisterCm", addcslashes(_tr("Register"),"'"));
    $smarty->assign("lblRegisteredCm", addcslashes(_tr("Registered"),"'"));
    $smarty->assign("Registered", addcslashes(_tr("Register"),"'"));

    $smarty->assign("md_message_title", _tr('md_message_title'));
    $sCurYear = date('Y');
    if ($sCurYear < '2013') $sCurYear = '2013';
    $smarty->assign("currentyear", $sCurYear);
    $smarty->assign("LOGOUT", _tr('Logout'));
    $smarty->assign("VersionDetails", _tr('VersionDetails'));
    $smarty->assign("VersionPackage", _tr('VersionPackage'));
    $smarty->assign("IssabelWebsite", _tr('Issabel Website'));
    $smarty->assign("AMOUNT_CHARACTERS", _tr("characters left"));
    $smarty->assign("SAVE_NOTE", _tr("Save Note"));
    $smarty->assign("MSG_SAVE_NOTE", _tr("Saving Note"));
    $smarty->assign("MSG_GET_NOTE", _tr("Loading Note"));
    $smarty->assign("LBL_NO_STICKY", _tr("Click here to leave a note."));
    $smarty->assign("ABOUT_ISSABEL", _tr('About Issabel')." ".$arrConf['issabel_version']);

    // notifications
    $pNot = new paloNotification($pdbACL);
    $notis = $pNot->listPublicNotifications();

    if(count($notis)>0) {
        $smarty->assign("ANIMATE_NOTIFICATION", "faa-shake animated");
    } else {
        $smarty->assign("ANIMATE_NOTIFICATION", "");
    }

    $selectedMenu = getParameter('menu');
    $rawmode = getParameter('rawmode');
    $tabsEnabled = isset($arrConf['mainTheme']) && $arrConf['mainTheme'] === 'modern';

    // Module redirects/forms can navigate this iframe to `?menu=...` and drop
    // rawmode plus the per-window ID. Reconstruct that context from its own
    // same-origin Referer, not from the session's last active menu.
    if (empty($rawmode) && $tabsEnabled) {
        $iframeContext = issabel_tabs_iframe_referer_context();
        if ($iframeContext !== null) {
            if (empty($selectedMenu) && $iframeContext['menu'] !== '') {
                $selectedMenu = $iframeContext['menu'];
            }
            if (!empty($selectedMenu)) {
                $redirectParams = $_GET;
                $redirectParams['menu'] = $selectedMenu;
                $redirectParams['rawmode'] = 'iframe';
                $redirectParams['iss_instance'] = $iframeContext['instanceId'];
                if ($iframeContext['tabId'] !== null) $redirectParams['iss_tab'] = $iframeContext['tabId'];
                $redirectPath = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'index.php';
                $redirectStatus = $_SERVER['REQUEST_METHOD'] === 'GET' ? 302 : 307;
                header('Location: '.$redirectPath.'?'.http_build_query($redirectParams), true, $redirectStatus);
                exit;
            }
        }
    }

    // Ejecutar una petición a un módulo global del framework
    if (!is_null($selectedMenu) && isset($arrConf['elx_framework_globalmodules']) &&
        in_array($selectedMenu, $arrConf['elx_framework_globalmodules']) &&
        file_exists("modules/$selectedMenu/index.php")) {
        require_once "modules/$selectedMenu/index.php";
        echo _moduleContent($smarty, $selectedMenu);
        return;
    }

    /* El módulo pbxadmin que integra a IssabelPBX no construye enlaces con
     * parámetros menu, ni con config.php en todos los casos. Por lo tanto, los
     * usos sucesivos de enlaces en IssabelPBX embebido requiren recordar que se
     * sirven a través de pbxadmin. */
    if (empty($selectedMenu) && !empty($_SESSION['menu']))
        $selectedMenu = $_SESSION['menu'];

    $oPn = new paloSantoNavigation($arrMenuFiltered, $smarty, $selectedMenu);
    $selectedMenu = $oPn->getSelectedModule();
    $smarty->assign('ISSABEL_MODULE_ID', $selectedMenu);

    if(!isset($rawmode) || $rawmode == 'iframe') {
       // Inicializa el objeto palosanto navigation
       $_SESSION['menu'] = $selectedMenu;

       // Guardar historial de la navegación
       if (!isset($rawmode)) putMenuAsHistory($pdbACL, $pACL, $idUser, $selectedMenu);
    }

    // Obtener contenido del módulo, si usuario está autorizado a él
    $bModuleAuthorized = $pACL->isUserAuthorizedById($idUser, "access", $selectedMenu);

    // The shell no longer renders module HTML; module content is loaded in
    // its tab iframe. Keep both raw modes working for existing callers.
    $sModuleContent = ($bModuleAuthorized && (isset($rawmode) || !$tabsEnabled))
        ? $oPn->showContent() : '';

    // try to open wizard modal
    if(isset($rawmode) && $selectedMenu=='pbxadmin') {
        if(is_file("/var/www/html/wizard/index.php")) {
        $pDB = new paloDB(generarDSNSistema('asteriskuser', 'asterisk'));
            $query = "SELECT count(*) FROM `users`";
            $row = $pDB->getFirstRowQuery($query, false, array());
            if($row[0]<1) {
                $sModuleContent.="<script>";
                $sModuleContent.="\$(document).ready( function() { \n";
                $sModuleContent.="ShowModalPopUP('"._tr('Initial Configuration Wizard')."','900',800,'<iframe src=\"/wizard/index.php\" frameborder=\"0\" style=\"overflow:hidden;height:100%;width:100%\" height=\"100%\" width=\"100%\" ></iframe>');\n";
                $sModuleContent.="$('.neo-modal-issabel-popup-content').css('left','0');$('.neo-modal-issabel-popup-content').css('right','0');$('.neo-modal-issabel-popup-content').css('bottom','0');\n";
                $sModuleContent.="});</script>";
            }
        }
    }

    // rawmode es un modo de operacion que pasa directamente a la pantalla la salida
    // del modulo. Esto es util en ciertos casos.
    if(isset($rawmode) && $rawmode=='yes') {
        echo $sModuleContent;
    } elseif (isset($rawmode) && $rawmode === 'iframe' && $tabsEnabled) {
        header('Referrer-Policy: same-origin');
        $issInstance = getParameter('iss_instance');
        if (!is_string($issInstance) || !issabelTabs::isValidInstanceId($issInstance)) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Invalid tab instance';
            exit;
        }

        $smarty->assign('WEBPATH', '');
        $smarty->assign('WEBCOMMON', 'libs/');
        $smarty->assign('THEMENAME', $arrConf['mainTheme']);
        $lang = get_language();
        $smarty->assign('LANG', $lang);
        $smarty->assign('IS_RTL', in_array(strtolower(substr($lang, 0, 2)), array('fa', 'ar', 'he', 'iw', 'ur', 'yi')));
        $smarty->assign('currentyear', date('Y'));
        $smarty->assign('CONTENT', $sModuleContent);
        $smarty->display('_common/iframe_module.tpl');
    } elseif (isset($rawmode) && $rawmode === 'iframe') {
        echo $sModuleContent;
    } else {
        if ($tabsEnabled) $oPn->putHEAD_JQUERY_HTML();
        $oPn->renderMenuTemplates();

        // Generar contenido principal de todos los paneles
        $panels = array();
        foreach (scandir("panels/") as $panelname) {
            if ($panelname != '.' && $panelname != '..' &&
                is_dir("panels/$panelname") &&
                file_exists("panels/$panelname/index.php")) {

                if (file_exists("panels/$panelname/lang/en.lang"))
                    load_language_module("../panels/$panelname");
                require_once "panels/$panelname/index.php";

                // No hay soporte de namespace en PHP 5.1, se simula con una clase
                $classname = 'Panel_'.ucfirst($panelname);
                if (class_exists($classname) && method_exists($classname, 'templateContent')) {
                    $tc = call_user_func(array($classname, 'templateContent'),
                        $smarty, $panelname);
                    if (is_array($tc) && isset($tc['content'])) {
                        $panels[$panelname] = $tc;
                    }
                }
            }
        }
        $smarty->assign(array(
            'LBL_ISSABEL_PANELS_SIDEBAR'    =>  _tr('Panels'),
            'ISSABEL_PANELS'                =>  $panels,
        ));

        if (file_exists('themes/'.$arrConf['mainTheme'].'/themesetup.php')) {
            require_once('themes/'.$arrConf['mainTheme'].'/themesetup.php');
            themeSetup($smarty, $selectedMenu, $pdbACL, $pACL, $idUser);
        }

        // La muestra de stickynote está ahora habilitada para todos los temas.
        // Se obtiene si ese menu tiene una nota agregada.
        $snvars = array('AUTO_POPUP' => 0, 'STATUS_STICKY_NOTE' => 'false');
        $statusStickyNote = getStickyNote($pdbACL, $idUser, $selectedMenu);
        if ($statusStickyNote['status'] && $statusStickyNote['data'] != "") {
            $snvars['STATUS_STICKY_NOTE'] = 'true';
            if ($statusStickyNote['popup'] == 1) $snvars['AUTO_POPUP'] = 1;
        }
        $smarty->assign($snvars);

        // Autorizacion
        if ($bModuleAuthorized) {
            $smarty->assign("CONTENT", $sModuleContent);
            $smarty->assign('MENU', (count($arrMenuFiltered) > 0)
                ? $smarty->fetch("_common/_menu.tpl")
                : _tr('No modules'));
        }
        $smarty->display("_common/index.tpl");
    }
} else {
    if (isset($_GET['tabmgr'])) {
        issabel_tabs_json_response(401, array('error' => 'session_expired'));
    }
    $rawmode = getParameter("rawmode");
    if(isset($rawmode) && $rawmode=='yes'){
        include_once "libs/paloSantoJSON.class.php";
        $jsonObject = new PaloSantoJSON();
        $jsonObject->set_status("ERROR_SESSION");
        $jsonObject->set_error(_tr("Your session has expired. If you want to do a login please press the button 'Accept'."));
        $jsonObject->set_message(null);
        Header('Content-Type: application/json');
        echo $jsonObject->createJSON();
    }
    else{
        $oPn = new paloSantoNavigation(array(), $smarty);
        $oPn->putHEAD_JQUERY_HTML();
        $smarty->assign("THEMENAME", $arrConf['mainTheme']);
        $smarty->assign("currentyear",date("Y"));
        $smarty->assign("PAGE_NAME", _tr('Login page'));
        $smarty->assign("WELCOME", _tr('Welcome to Issabel'));
        $smarty->assign("ENTER_USER_PASSWORD", _tr('Please enter your username and password'));
        $smarty->assign("USERNAME", _tr('Username'));
        $smarty->assign("PASSWORD", _tr('Password'));
        $smarty->assign("SUBMIT", _tr('Submit'));

        $smarty->display("_common/login.tpl");
    }
}
?>
