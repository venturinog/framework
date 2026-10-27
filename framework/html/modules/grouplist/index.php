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
  $Id: index.php, Mon 12 Jul 2021 04:34:51 PM EDT, nicolas@issabel.com
*/
require_once 'libs/issabelAclAudit.class.php';

function _moduleContent(&$smarty, $module_name)
{
    include_once("libs/paloSantoDB.class.php");
    include_once("libs/paloSantoGrid.class.php");
    include_once("libs/paloSantoACL.class.php");
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);


    /////conexion a php
    $pDB = new paloDB($arrConf['issabel_dsn']['acl']);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];


    if(!empty($pDB->errMsg)) {
        echo _tr('Database connection error').": $pDB->errMsg <br>";
    }

    $arrData = array();
    $pACL = new paloACL($pDB);
    if(!empty($pACL->errMsg)) {
        echo _tr('ACL connection error').": $pACL->errMsg <br>";
    }

    $arrFormElements = array("description" => array("LABEL"                  => _tr("Description"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                             "group"       => array("LABEL"                  => _tr("Group"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "")
    );

//description  id  name

    $contenidoModulo="";
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("APPLY_CHANGES", _tr("Apply changes"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("DELETE", _tr("Delete"));
    $smarty->assign("icon","modules/$module_name/images/system_groups.png");
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    if(isset($_POST['submit_create_group'])) {
        // Implementar
        include_once("libs/paloSantoForm.class.php");
        $arrFillGroup['group']       = '';
        $arrFillGroup['description'] = '';
        $smarty->assign('GROUP_ERRORS', array('group' => '', 'description' => ''));
        $oForm = new paloForm($smarty, $arrFormElements);
        $contenidoModulo=$oForm->fetchForm("$local_templates_dir/grouplist.tpl", _tr("New Group"),$arrFillGroup);
    } else if(isset($_POST['edit'])) {

        // Tengo que recuperar la data del usuario
        $pACL = new paloACL($pDB);

        $arrGroup = $pACL->getGroups($_POST['id_group']);
        if (!is_array($arrGroup)) {
            $contenidoModulo = '';
            Header("Location: ?menu=$module_name");
        } else {
            if($arrGroup[0][1]=='administrator')
                $arrGroup[0][1] = _tr('administrator');
            else if($arrGroup[0][1]=='operator')
                $arrGroup[0][1] = _tr('operator');
            else if($arrGroup[0][1]=='extension')
                $arrGroup[0][1] = _tr('extension');

            if($arrGroup[0][2]=='total access')
                $arrGroup[0][2] = _tr('total access');
            else if($arrGroup[0][2]=='operator')
                $arrGroup[0][2] = _tr('operator');
            else if($arrGroup[0][2]=='extension user')
                $arrGroup[0][2] = _tr('extension user');


            $arrFillGroup['group'] = $arrGroup[0][1];
            $arrFillGroup['description'] = $arrGroup[0][2];

            // Implementar
            include_once("libs/paloSantoForm.class.php");
            $oForm = new paloForm($smarty, $arrFormElements);

            $oForm->setEditMode();
            $smarty->assign("id_group", htmlspecialchars($_POST['id_group'], ENT_COMPAT, 'UTF-8'));
            $smarty->assign('GROUP_ERRORS', array('group' => '', 'description' => ''));
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/grouplist.tpl", _tr('Edit Group')." \"" . $arrFillGroup['group'] . "\"", $arrFillGroup);
        }
    } else if(isset($_POST['submit_save_group'])) {

        include_once("libs/paloSantoForm.class.php");

        $errors = validateGroupForm($_POST);
        markGroupFormErrors($arrFormElements, $errors);
        $oForm = new paloForm($smarty, $arrFormElements);
        $smarty->assign('GROUP_ERRORS', array_merge(
            array('group' => '', 'description' => ''), $errors));

        if(count($errors) == 0) {
            // Exito, puedo procesar los datos ahora.
            $pACL = new paloACL($pDB);

            // Creo el Grupo
            $postgroup = htmlspecialchars($_POST['group']);
            $postdescription = htmlspecialchars($_POST['description']);
            $ok = $pACL->createGroup($postgroup, $postdescription);
            issabelAclAudit::log('group.create', $ok ? 'ok' : 'error', array(
                'group'       => isset($_POST['group']) ? $_POST['group'] : '',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'msg'         => $ok ? '' : $pACL->errMsg,
            ));

            if(!empty($pACL->errMsg)) {
                // Ocurrio algun error aqui
                $smarty->assign("mb_message", _tr('ERROR').": $pACL->errMsg");
                $contenidoModulo=$oForm->fetchForm("$local_templates_dir/grouplist.tpl", _tr("New Group"), $_POST);
            } else {
                header("Location: ?menu=grouplist");
            }
        } else {
            // Errores por campo ya marcados: se re-renderiza el formulario.
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/grouplist.tpl", _tr("New Group"), $_POST);
        }

    } else if(isset($_POST['submit_apply_changes'])) {

        $arrGroup = $pACL->getGroups($_POST['id_group']);
        if (!is_array($arrGroup)) {
            $contenidoModulo = '';
            Header("Location: ?menu=$module_name");
        } else {
            $group = $arrGroup[0][1];
            $description = $arrGroup[0][2];

            include_once("libs/paloSantoForm.class.php");
            $errors = validateGroupForm($_POST);
            markGroupFormErrors($arrFormElements, $errors);
            $oForm = new paloForm($smarty, $arrFormElements);

            $oForm->setEditMode();
            $smarty->assign('GROUP_ERRORS', array_merge(
                array('group' => '', 'description' => ''), $errors));
            if(count($errors) == 0) {

                // Exito, puedo procesar los datos ahora.
                $pACL = new paloACL($pDB);

                $ok = $pACL->updateGroup($_POST['id_group'], $_POST['group'],$_POST['description']);
                $auditDetails = array(
                    'id'    => $_POST['id_group'],
                    'group' => isset($_POST['group']) ? $_POST['group'] : '',
                );
                if ($group != $auditDetails['group']) $auditDetails['old_group'] = $group;
                issabelAclAudit::log('group.update', $ok ? 'ok' : 'error',
                    $auditDetails + array('msg' => $ok ? '' : $pACL->errMsg));

                if(!$ok)
                {
                    // Ocurrio algun error aqui
                    $smarty->assign("mb_message", _tr('ERROR').": $pACL->errMsg");
                    $contenidoModulo=$oForm->fetchForm("$local_templates_dir/grouplist.tpl", _tr("Edit Group"), $_POST);
                } else {
                    header("Location: ?menu=grouplist");
                }
            } else {
                // Errores por campo ya marcados: se re-renderiza el formulario.
                $arrFillGroup['group']       = $_POST['group'];
                $arrFillGroup['description'] = $_POST['description'];
                $smarty->assign("id_group", htmlspecialchars($_POST['id_group'], ENT_COMPAT, 'UTF-8'));
                $contenidoModulo=$oForm->fetchForm("$local_templates_dir/grouplist.tpl", _tr("Edit Group"), $arrFillGroup);
            }
        }
    } else if(isset($_GET['action']) && $_GET['action']=="view") {

        include_once("libs/paloSantoForm.class.php");

        $oForm = new paloForm($smarty, $arrFormElements);

        //- TODO: Tengo que validar que el id sea valido, si no es valido muestro un mensaje de error

        $oForm->setViewMode(); // Esto es para activar el modo "preview"
        $arrGroup = $pACL->getGroups($_GET['id']);

        if (!is_array($arrGroup)) {
            $contenidoModulo = '';
            Header("Location: ?menu=$module_name");
        } else {
            // Conversion de formato
            if($arrGroup[0][1]=='administrator')
                $arrGroup[0][1] = _tr('administrator');
            else if($arrGroup[0][1]=='operator')
                $arrGroup[0][1] = _tr('operator');
            else if($arrGroup[0][1]=='extension')
                $arrGroup[0][1] = _tr('extension');

            if($arrGroup[0][2]=='total access')
                $arrGroup[0][2] = _tr('total access');
            else if($arrGroup[0][2]=='operator')
                $arrGroup[0][2] = _tr('operator');
            else if($arrGroup[0][2]=='extension user')
                $arrGroup[0][2] = _tr('extension user');

            $arrTmp['group']        = $arrGroup[0][1];
            $arrTmp['description']  = $arrGroup[0][2];

            $smarty->assign("id_group", htmlspecialchars($_GET['id'], ENT_COMPAT, 'UTF-8'));
            $smarty->assign('GROUP_VIEW', array(
                'group'       => $arrGroup[0][1],
                'description' => $arrGroup[0][2]));
            $contenidoModulo=$oForm->fetchForm("$local_templates_dir/grouplist.tpl", _tr("View Group"), $arrTmp); // hay que pasar el arreglo
        }
    } else {
        if (isset($_POST['delete']) && isset($_POST['id_group'])) {
            //- TODO: Validar el id de group
            $id_target = (int)$_POST['id_group'];
            $arrTargetGroup = $pACL->getGroups($id_target);
            $auditTarget = array(
                'id'    => $id_target,
                'group' => (is_array($arrTargetGroup) && count($arrTargetGroup) > 0)
                    ? $arrTargetGroup[0][1] : '',
            );

            if($_POST['id_group']=='1') {
                // No se puede eliminar al grupo admin
                $smarty->assign("mb_message", _tr("The administrator group cannot be deleted because is the default Issabel Group. You can delete any other group."));
                issabelAclAudit::log('group.delete', 'error',
                    $auditTarget + array('msg' => 'the administrator group cannot be deleted'));
            } else if ($pACL->HaveUsersTheGroup($_POST['id_group'])==TRUE){
                $smarty->assign("mb_message", _tr("The Group have users assigned. You can delete any group that does not have any users assigned in it."));
                issabelAclAudit::log('group.delete', 'error',
                    $auditTarget + array('msg' => 'the group has users assigned'));
            } else {
                $ok = $pACL->deleteGroup($_POST['id_group']);
                issabelAclAudit::log('group.delete', $ok ? 'ok' : 'error',
                    $auditTarget + array('msg' => $ok ? '' : $pACL->errMsg));
            }
        }

        $nav   = getParameter("nav");
    $start = getParameter("start");

    $total = $pACL->getNumGroups();
    $total = ($total == NULL)?0:$total;

    $limit  = 20;
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->pagingShow(true);
    $oGrid->setURL("?menu=grouplist");
    $offset = $oGrid->calculateOffset();
    $end = $oGrid->getEnd();

   $arrGroups = $pACL->getGroupsPaging($limit, $offset);


        $end = count($arrGroups);
        $arrData = array();
        foreach($arrGroups as $group) {
            $arrTmp    = array();

            if($group[1]=='administrator')
                $group[1] = _tr('administrator');
            else if($group[1]=='operator')
                $group[1] = _tr('operator');
            else if($group[1]=='extension')
                $group[1] = _tr('extension');

            if($group[2]=='total access')
                $group[2] = _tr('total access');
            else if($group[2]=='operator')
                $group[2] = _tr('operator');
            else if($group[2]=='extension user')
                $group[2] = _tr('extension user');

            $arrTmp[0] = "&nbsp;<a href='?menu=grouplist&action=view&id=" . $group[0] . "'>" . $group[1] . "</a>";//id_group   name
            $arrTmp[1] = $group[2];//description
            $arrData[] = $arrTmp;
        }

        $arrGrid = array("title"    => _tr("Group List"),
                         "icon"     => "/modules/$module_name/images/system_groups.png",
                         "columns"  => array(0 => array("name"      => _tr("Group"),
                                                        "property1" => ""),
                                             1 => array("name"      => _tr("Description"),
                                                       "property1" => "")
                                            )
                        );


        $oGrid->addNew("submit_create_group",_tr("Create New Group"));
        $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData);
    }
    return $contenidoModulo;
}

/**
 * Valida el formulario de grupo y devuelve los errores por campo.
 */
function validateGroupForm($post)
{
    $errors = array();
    if (!isset($post['group']) || trim($post['group']) === '')
        $errors['group'] = sprintf(_tr('%s is required'), _tr('Group'));
    if (!isset($post['description']) || trim($post['description']) === '')
        $errors['description'] = sprintf(_tr('%s is required'), _tr('Description'));
    return $errors;
}

/**
 * Marca con is-invalid los campos con error, conservando form-control.
 */
function markGroupFormErrors(&$arrFormElements, $errors)
{
    foreach (array('group', 'description') as $f) {
        if (!isset($errors[$f])) continue;
        if (!isset($arrFormElements[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormElements[$f]['INPUT_EXTRA_PARAM']))
            $arrFormElements[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormElements[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormElements[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormElements[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }
}
?>
