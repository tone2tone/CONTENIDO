<?php

/**
 * This file contains the backend page for managing module style files.
 *
 * @package Core
 * @subpackage Backend
 * @author Olaf Niemann
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.file.php');

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");

if($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

$contenidoModulHandler = new cModuleHandler($idmod);
$sFileType = 'css';

$sActionCreate = 'style_create';
$sActionEdit = 'style_edit';
$sFilename = '';

$tmp_file = $contenidoModulHandler->getCssFileName();
$file = $contenidoModulHandler->getCssFileName();

if (empty($action)) {
    $actionRequest = $sActionEdit;
} else {
    $actionRequest = $action;
}

$page = new cGuiPage('mod_style');

$tpl->reset();
$premCreate = false;

if (!$contenidoModulHandler->existFile('css', $contenidoModulHandler->getCssFileName())) {
    if (!$perm->have_perm_area_action('style', $sActionCreate)) {
        $premCreate = true;
    }
}

if (!$perm->have_perm_area_action('style', $actionRequest) || $premCreate) {
    $page->displayCriticalError(i18n('Permission denied'));
    $page->render();
    return;
}

// display critical error if no valid client is selected
if ((int) $client < 1) {
    $page->displayCriticalError(i18n("No Client selected"));
    $page->render();
    return;
}

$path = $contenidoModulHandler->getCssPath(); // $cfgClient[$client]['css']['path'];

// ERROR MESSAGE
if (!$contenidoModulHandler->moduleWriteable('css')) {
    $page->displayCriticalError(i18n('No write permissions in folder css for this module!'));
    $page->render();
    return;
}

$sTempFilename = stripslashes($tmp_file);
$sOrigFileName = $sTempFilename;

if (cFileHandler::getExtension($file) != $sFileType && cString::getStringLength(stripslashes(trim($file))) > 0) {
    $sFilename .= stripslashes($file) . '.' . $sFileType;
} else {
    $sFilename .= stripslashes($file);
}

if (stripslashes($file)) {
    $page->reloadLeftBottomFrame(['file' => $sFilename]);
}

// Content Type is css
$sTypeContent = 'css';
$fileInfoCollection = new cApiFileInformationCollection();
$aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, $sTypeContent);

if (true === cFileHandler::exists($path . $sFilename)
&& false === cFileHandler::writeable($path . $sFilename)) {
    $page->displayWarning(i18n("You have no write permissions for this file"));
}

// Create new css file
if ((!$readOnly) && $actionRequest == $sActionCreate && $_REQUEST['status'] == 'send') {
    $sTempFilename = $sFilename;
    $ret = cFileHandler::create($path . $sFilename);

    if (true === cFileHandler::validateFilename($sFilename)) {
        $contenidoModulHandler->createModuleFile('css', $sFilename, $_REQUEST['code']);
    }
    $bEdit = cFileHandler::read($path . $sFilename);

    if (false !== $bEdit) {
        // trigger a code cache rebuild if changes were saved
        $oApiModule = new cApiModule($idmod);
        $oApiModule->store();
    }

    $fileInfoCollection = new cApiFileInformationCollection();
    $fileInfoCollection->updateFile($sFilename, 'css', $_REQUEST['description'], $auth->auth['uid']);

    $page->reloadRightTopFrame(['file' => $sTempFilename]);

    if ($ret && $bEdit) {
        $page->displayOk(i18n('Created new css file successfully'));
    } else {
        $page->displayError(i18n('Could not create a new css file!'));
    }
}

// Edit selected file
if ((!$readOnly) && $actionRequest == $sActionEdit && $_REQUEST['status'] == 'send') {
    if ($sFilename != $sTempFilename) {
        cFileHandler::validateFilename($sFilename);
        if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
            $sTempFilename = $sFilename;
        } else {
            $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $path . $sTempFilename));
            exit();
        }

        $page->reloadRightTopFrame(['file' => $sTempFilename]);
    } else {
        $sTempFilename = $sFilename;
    }

    $fileInfoCollection = new cApiFileInformationCollection();
    $fileInfoCollection->updateFile($sOrigFileName, 'css', $_REQUEST['description'], $sFilename, $auth->auth['uid']);

    if (true === cFileHandler::validateFilename($sFilename)) {
        $contenidoModulHandler->createModuleFile('css', $sFilename, $_REQUEST['code']);
    }
    $bEdit = cFileHandler::read($path . $sFilename);

    if (false !== $bEdit) {
        // trigger a code cache rebuild if changes were saved
        $oApiModule = new cApiModule($idmod);
        $oApiModule->store();
    }

    if ($sFilename != $sTempFilename && $bEdit) {
        $page->displayOk(i18n('Renamed and saved changes successfully!'));
    } elseif (false === $bEdit) {
        $page->displayError(i18n("Can't save file!"));
    } else {
        $page->displayOk(i18n('Saved changes successfully!'));
    }
}

// Generate edit form
if (isset($actionRequest)) {

    $sAction = ($bEdit) ? $sActionEdit : $actionRequest;
    $module = new cApiModule($idmod);

    $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');

    if ($actionRequest == $sActionEdit
    && cFileHandler::exists($path . $sFilename)) {
        $sCode = cFileHandler::read($path . $sFilename);
        if ($sCode === false) {
            exit();
        }
        $sCode = cString::recodeString($sCode, $fileEncoding, cModuleHandler::getEncoding());
    } else {
        // stripslashes is required here in case of creating a new file
        $sCode = stripslashes($_REQUEST['code']);
    }
    $fileInfoCollection = new cApiFileInformationCollection();
    $aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, 'css');

    $form = new cGuiTableForm('file_editor');
    $form->setTableID('mod_style');
    $form->addHeader(i18n('Edit file') . " &quot;". conHtmlSpecialChars($module->get('name')). "&quot;");
    $form->setVar('area', $area);
    $form->setVar('action', $sAction);
    $form->setVar('frame', $frame);
    $form->setVar('status', 'send');
    $form->setVar('tmp_file', $sTempFilename);
    $form->setVar('idmod', $idmod);
    $label = new cHTMLLabel($sFilename, '');

    $code = new cHTMLTextarea('code', conHtmlSpecialChars($sCode), 100, 35, 'code');
    $code->setStyle('font-family: monospace;width: 100%;');
    $code->updateAttributes(array(
        'wrap' => getEffectiveSetting('style_editor', 'wrap', 'off')
    ));

    $form->add(i18n('Name'), $label);
    $form->add(i18n('Code'), $code);


    $oCodeMirror = new CodeMirror('code', 'css', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg);
    if($readOnly) {
        $oCodeMirror->setProperty("readOnly", "true");

        $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n('Overwriting files is disabled'), 's');
    }
    $page->setContent($form);
    $page->addScript($oCodeMirror->renderScript());

    $page->render();
}
