<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 *
 * @filesource $RCSfile: reqSpecEdit.php,v $
 * @version $Revision: 1.30 $
 * @modified $Date: 2009/11/19 17:52:13 $ $Author: franciscom $
 *
 * @author Martin Havlat
 *
 * View existing and create a new req. specification.
 *
 * rev: 
 *	20091119 - franciscom - doc_id
 *	20080830 - franciscom - added code to manage unlimited depth tree
 *                         (will be not enabled yet)
 *
 *  20080827 - franciscom - BUGID 1692 
 *
 */
require_once("../../config.inc.php");
require_once("common.php");
require_once('requirements.inc.php');
require_once("web_editor.php");
$editorCfg = getWebEditorCfg('requirement_spec');
require_once(require_web_editor($editorCfg['type']));

testlinkInitPage($db,false,false,"checkRights");

$templateCfg = templateConfiguration();
$args = init_args();

new dBug($args);

$gui = initialize_gui($db);
$commandMgr = new reqSpecCommands($db);

$auditContext = new stdClass();
$auditContext->tproject = $args->tproject_name;
$commandMgr->setAuditContext($auditContext);

$pFn = $args->doAction;
$op = null;
if(method_exists($commandMgr,$pFn))
{
	$op = $commandMgr->$pFn($args,$_REQUEST);
}
renderGui($args,$gui,$op,$templateCfg,$editorCfg);

function init_args()
{
	$args = new stdClass();
	$iParams = array("countReq" => array(tlInputParameter::INT_N,99999),
			         "req_spec_id" => array(tlInputParameter::INT_N),
					 "reqParentID" => array(tlInputParameter::INT_N),
					 "doAction" => array(tlInputParameter::STRING_N,0,250),
					 "title" => array(tlInputParameter::STRING_N,0,100),
					 "scope" => array(tlInputParameter::STRING_N),
					 "doc_id" => array(tlInputParameter::STRING_N,1,32),
					 "nodes_order" => array(tlInputParameter::ARRAY_INT),
	);	
		
	$args = new stdClass();
	R_PARAMS($iParams,$args);

	$args->tproject_id = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
	$args->tproject_name = isset($_SESSION['testprojectName']) ? $_SESSION['testprojectName'] : "";
	$args->user_id = isset($_SESSION['userID']) ? $_SESSION['userID'] : 0;
	$args->basehref = $_SESSION['basehref'];
	$args->reqParentID = is_null($args->reqParentID) ? $args->tproject_id : $args->reqParentID;

	return $args;
}

function renderGui(&$argsObj,$guiObj,$opObj,$templateCfg,$editorCfg)
{
    $smartyObj = new TLSmarty();
    $actionOperation = array('create' => 'doCreate', 'edit' => 'doUpdate',
                           'doDelete' => '', 'doReorder' => '', 'reorder' => '',
                           'doCreate' => 'doCreate', 'doUpdate' => 'doUpdate',
                           'createChild' => 'doCreate');

    $owebEditor = web_editor('scope',$argsObj->basehref,$editorCfg) ;
    $owebEditor->Value = $argsObj->scope;
	$guiObj->scope = $owebEditor->CreateHTML();
    $guiObj->editorType = $editorCfg['type'];  
      
    $renderType = 'none';
    
    $tpl = $tpd = null;
    switch($argsObj->doAction)
    {
        case "edit":
        case "create":
        case "createChild":
        case "reorder":
        case "doDelete":
        case "doReorder":
	    case "doCreate":
	    case "doUpdate":
        	$renderType = 'template';
            $key2loop = get_object_vars($opObj);
            foreach($key2loop as $key => $value)
            {
                $guiObj->$key = $value;
            }
            $guiObj->operation = $actionOperation[$argsObj->doAction];
            $tpl = is_null($opObj->template) ? $templateCfg->default_template : $opObj->template;
            $tpd = isset($key2loop['template_dir']) ? $opObj->template_dir : $templateCfg->template_dir;
    		break;
    }
	switch($argsObj->doAction)
    {
        case "edit":
        case "create":
        case "createChild":
        case "reorder":
        case "doDelete":
        case "doReorder":
        	$tpl = $tpd . $tpl;
            break;
        case "doCreate":
	    case "doUpdate": 
	    	$pos = strpos($tpl, '.php');
            if($pos === false)
            	$tpl = $templateCfg->template_dir . $tpl;      
            else
                $renderType = 'redirect';  
			break;  
    }
    switch($renderType)
    {
        case 'template':
			$smartyObj->assign('mgt_view_events',has_rights($db,"mgt_view_events"));
 		    $smartyObj->assign('gui',$guiObj);
		    $smartyObj->display($tpl);
        	break;  
 
        case 'redirect':
		    header("Location: {$tpl}");
	  		exit();
        	break;

        default:
        	break;
    }
}

function initialize_gui(&$dbHandler)
{
    $gui = new stdClass();
    $gui->user_feedback = null;
    $gui->main_descr = null;
    $gui->action_descr = null;
    $gui->refresh_tree = 'no';

    $gui->grants = new stdClass();
    $gui->grants->req_mgmt = has_rights($dbHandler,"mgt_modify_req");

    return $gui;
}

function checkRights(&$db,&$user)
{
	return ($user->hasRight($db,'mgt_view_req') && $user->hasRight($db,'mgt_modify_req'));
}
?>