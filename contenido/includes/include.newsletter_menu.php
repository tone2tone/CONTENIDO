<?php
/*****************************************
* File      :   $RCSfile: include.newsletter_menu.php,v $
* Project   :   Contenido
* Descr     :   Frontend user list
* Modified  :   $Date: 2007/08/02 23:15:00 $
*
* � four for business AG, www.4fb.de, updated by HerrB
*
* $Id: include.newsletter_menu.php,v 1.20 2007/08/02 23:15:00 bjoern.behrens Exp $
******************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "class.newsletter.php");
cInclude("classes", "class.newsletter.groups.php");
cInclude("classes", "contenido/class.user.php");
cInclude("classes", "contenido/class.clientslang.php");
cInclude("classes", "class.ui.php");

$oPage			= new cPage;
$oMenu			= new UI_Menu;

$oClientLang 	= new cApiClientLanguage(false, $client, $lang);
$lIDCatArt		= (int)$oClientLang->getProperty("newsletter", "idcatart"); // Get idCatArt to check, if we may send a test newsletter

if (!is_object($oDB)) {
	$oDB = new DB_Contenido;
}

$oUser = new cApiUser($auth->auth["uid"]);
$oClientLang 	= new cApiClientLanguage(false, $client, $lang);

// HTML Newsletter: Store settings
if (isset($_REQUEST['action_html']) && $_REQUEST['action_html'] == 'save_newsletter_properties' && $perm->have_perm_area_action($area, "news_html_settings")) {
    if (isset($_REQUEST["ckbHTMLNewsletter"])) {
        $oClientLang->setProperty("newsletter", "html_newsletter", "true");
    } else {
        $oClientLang->setProperty("newsletter", "html_newsletter", "false");
    }
    $oClientLang->setProperty("newsletter", "html_template_idcat",   $_REQUEST["selHTMLTemplateCat"]);
	$oClientLang->setProperty("newsletter", "html_newsletter_idcat", $_REQUEST["selHTMLNewsletterCat"]);
    $oUser->setProperty("newsletter", "test_idnewsgrp", $_REQUEST["selTestDestination"]);
}

// Initialization
$sDelTitle		= i18n("Delete newsletter");
$sDelDescr		= i18n("Do you really want to delete the following newsletter:<br>");

$sSendTestTitle		= i18n("Send test newsletter");
$sSendTestTitleOff  = i18n("Send test newsletter (disabled, check newsletter sender e-mail address and handler article selection)");
$sAddJobTitle       = i18n("Add newsletter dispatch job");
$sAddJobTitleOff    = i18n("Add newsletter dispatch job (disabled, check newsletter sender e-mail address and handler article selection)");
$sCopyTitle			= i18n("Duplicate newsletter");

##################################
# Getting values for sorting, etc.
##################################
// Items per page (value stored per area in user property)
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST["elemperpage"]) || $_REQUEST["elemperpage"] < 0) {
	$_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST["elemperpage"])) {
	$_REQUEST["elemperpage"] = 25;
}
if ($_REQUEST["elemperpage"] > 0) { 
	$oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}
if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST["page"]) || $_REQUEST["page"] <= 0 || $_REQUEST["elemperpage"] == 0) {
	$_REQUEST["page"] = 1;
}
// Sort order
if ($_REQUEST["sortorder"] != "DESC") {
	$_REQUEST["sortorder"]  = "ASC";
}
// Sort By
if (!isset($_REQUEST["sortby"]) || $_REQUEST["sortby"] == "") {
	$_REQUEST["sortby"]  = "Name";
}


// Request data
$oNewsletters = new NewsletterCollection;
$oNewsletters->setWhere("idclient", $client);
$oNewsletters->setWhere("idlang", $lang);

$aFields = array();
$aFields["name"]  		= array("field" => "name", "caption" => i18n("Name"), "type" => "base,sort,search");

if ($_REQUEST["filter"] != "") {
	if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
		foreach ($aFields as $sKey => $aData) {
			if (strpos($aData["type"], "search") !== false) {
				$oNewsletters->setWhereGroup("filter", $aData["field"], $_REQUEST["filter"], "LIKE");
			}
		}
		$oNewsletters->setInnerGroupCondition("filter", "OR");
	} else {
		$oNewsletters->setWhere('name', $_REQUEST["filter"], "LIKE");
	}
}

if ($_REQUEST["elemperpage"] > 0) {
	$oNewsletters->query();
    $iItemCount = $oNewsletters->count(); // Getting item count without limit (for page function) - better idea anyone (performance)?
    
    if ($_REQUEST["elemperpage"]*($_REQUEST["page"]) >= $iItemCount+$_REQUEST["elemperpage"] && $_REQUEST["page"]  != 1) {
        $_REQUEST["page"]--;
    }

	$oNewsletters->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
	$iItemCount = 0;
}

$oNewsletters->setOrder("welcome DESC, " . $_REQUEST["sortby"]." ".$_REQUEST["sortorder"]);
$oNewsletters->query();

// Output data
$oMenu	= new UI_Menu;
$iMenu	= 0;
while ($oNewsletter = $oNewsletters->next()) {
	$idnewsletter = $oNewsletter->get("idnews");
	$iMenu++;
	
	$sName = $oNewsletter->get("name");
	if ($oNewsletter->get("welcome")) {
		$sName = $sName . "*";
	}

	// Create the link to show/edit the newsletter
	$oLnk = new cHTMLLink;
	$oLnk->setMultiLink($area, "", $area, "");
	$oLnk->setCustom("idnewsletter", $idnewsletter);
	
	$oMenu->setTitle($iMenu, $sName);
	$oMenu->setLink($iMenu, $oLnk);
	
	if ($perm->have_perm_area_action($area, "news_add_job") ||
	    $perm->have_perm_area_action($area, "news_create") ||
	    $perm->have_perm_area_action($area, "news_save"))
	{
		// Rights: If you are able to add a job, you should be able to test it
		//         If you are able to add or change a newsletter, you should be able to test it
		// Usability: If no e-mail has been specified, you can't send a test newsletter
		if (isValidMail($oNewsletter->get("newsfrom")) && $lIDCatArt > 0)
		{
			$sLnkSendTest = '<a title="'.$sSendTestTitle.'" href="javascript://" onclick="showSendTestMsg('.$idnewsletter.')"><img src="'.$cfg['path']['images'].'newsletter_sendtest_16.gif" border="0" title="'.$sSendTestTitle.'" alt="'.$sSendTestTitle.'" /></a>';
		} else {
			$sLnkSendTest = '<img src="'.$cfg['path']['images'].'newsletter_sendtest_16_off.gif" border="0" title="'.$sSendTestTitleOff.'" alt="'.$sSendTestTitleOff.'" />';
		}
		$oMenu->setActions($iMenu, 'test', $sLnkSendTest);
	}
	
	if ($perm->have_perm_area_action($area, "news_add_job"))
	{
		if (isValidMail($oNewsletter->get("newsfrom")) && $lIDCatArt > 0)
		{
			$oLnkAddJob = new Link;
			$oLnkAddJob->setMultiLink("news","","news","news_add_job");
			$oLnkAddJob->setCustom("idnewsletter", $idnewsletter);
			$oLnkAddJob->setAlt($sAddJobTitle);
			$oLnkAddJob->setContent('<img src="'.$cfg['path']['images'].'newsletter_dispatch_16.gif" border="0" title="'.$sAddJobTitle.'" alt="'.$sAddJobTitle.'">');
			
			$sLnkAddJob = $oLnkAddJob->render();
		} else {
			$sLnkAddJob = '<img src="'.$cfg['path']['images'].'newsletter_dispatch_16_off.gif" border="0" title="'.$sAddJobTitleOff.'" alt="'.$sAddJobTitleOff.'" />';
		}
		
		$oMenu->setActions($iMenu, 'dispatch', $sLnkAddJob);
	}
	
	if ($perm->have_perm_area_action($area, "news_create"))
	{
		$oLnkCopy = new Link;
		$oLnkCopy->setMultiLink("news", "", "news", "news_duplicate");
		$oLnkCopy->setCustom("idnewsletter", $idnewsletter);
		$oLnkCopy->setAlt($sCopyTitle);
		$oLnkCopy->setContent('<img src="'.$cfg['path']['images'].'but_copy.gif" border="0" title="'.$sCopyTitle.'" alt="'.$sCopyTitle.'">');
	
		$oMenu->setActions($iMenu, 'copy', $oLnkCopy->render());
	}
	
	if ($perm->have_perm_area_action($area, "news_delete"))
	{ 
		$sDelete = '<a title="'.$sDelTitle.'" href="javascript://" onclick="showDelMsg('.$idnewsletter.',\''.addslashes($sName).'\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'"></a>';
		$oMenu->setActions($iMenu, 'delete', $sDelete);
	}
}

// Title for messageboxes
$sSendTestTitle		= i18n("Send test newsletter");

// Destination for sending test newsletter
$oSelTestDestination	= new cHTMLSelectElement("selTestDestination");

$oOption = new cHTMLOptionElement(i18n("My mail address"), 0);
$oSelTestDestination->addOptionElement(0, $oOption);

$oRcpGroups = new RecipientGroupCollection;
$oRcpGroups->setWhere("idclient", $client);
$oRcpGroups->setWhere("idlang", $lang);
$oRcpGroups->setOrder("groupname");
$oRcpGroups->query();

$sSendTestTarget = "";
while ($oRcpGroup = $oRcpGroups->next())
{
	$iID = $oRcpGroup->get($oRcpGroup->primaryKey);
	
	if ($_REQUEST["selTestDestination"] == $iID) {
		$sSendTestTarget = sprintf(i18n("Recipient group: %s"), $oRcpGroup->get("groupname"));
	}
	
	$oOption = new cHTMLOptionElement($oRcpGroup->get("groupname"), $iID);
	$oSelTestDestination->addOptionElement($iID, $oOption);
}

$sCurrentUser		= $oUser->get("realname"). " (" . $oUser->get("email") . ")";
if ($sSendTestTarget == "")
{
	$_REQUEST["selTestDestination"] = 0;
	$sSendTestTarget = $sCurrentUser;
} 

$oSelTestDestination->setStyle("width: 220px;");
$oSelTestDestination->setDefault($_REQUEST["selTestDestination"]);
if (!$perm->have_perm_area_action($area, "news_send_test"))
{
	$oSelTestDestination->setDisabled("disabled"); // No right to send somewhere else than to yourself
}

$sSendTestDescr = sprintf(i18n("Do you really want to send the newsletter to:<br><strong>%s</strong>"), $sSendTestTarget);


$execScript = '
    <script type="text/javascript">
        var sid = "'.$sess->id.'";

        // Create messageBox instance
        box = new messageBox("", "", "", 0, 0);

        function showSendTestMsg(lngId) {
            box.confirm("'.$sSendTestTitle.'", "'.$sSendTestDescr.'", "sendTestNewsletter(\'" + lngId + "\')");
        }

        function showDelMsg(lngId, strElement) {
            box.confirm("'.$sDelTitle.'", "'.$sDelDescr.'<b>" + strElement + "</b>", "deleteNewsletter(\'" + lngId + "\')");
        }

				//
        function checkSelection(strValue)
        {
            if (strValue == "selection") {
                document.getElementById("groupselect").disabled = false;
            } else {
                document.getElementById("groupselect").disabled = true;
            }
        }

        // Function for sending test newsletter
        function sendTestNewsletter(idnewsletter)
        {
        		oForm = top.content.left.left_top.document.getElementById("newsletter_listoptionsform");

            url  = "main.php?area=news";
            url += "&action=news_send_test";
            url += "&frame=4";
            url += "&idnewsletter=" + idnewsletter;
            url += "&contenido=" + sid;
            url += get_registered_parameters();
            url += "&sortby=" + oForm.sortby.value;
            url += "&sortorder=" + oForm.sortorder.value;
            url += "&filter=" + oForm.filter.value;
            url += "&elemperpage=" + oForm.elemperpage.value;

            parent.parent.right.right_bottom.location.href = url;
        }

        // Function for deleting newsletters 
        function deleteNewsletter(idnewsletter)
        {
            oForm = top.content.left.left_top.document.getElementById("newsletter_listoptionsform");

            url  = "main.php?area=news";
            url += "&action=news_delete";
            url += "&frame=4";
            url += "&idnewsletter=" + idnewsletter;
            url += "&contenido=" + sid;
            url += get_registered_parameters();
            url += "&sortby=" + oForm.sortby.value;
            url += "&sortorder=" + oForm.sortorder.value;
            url += "&filter=" + oForm.filter.value;
            url += "&elemperpage=" + oForm.elemperpage.value;

            parent.parent.right.right_bottom.location.href = url;
        }
		</script>';

$oPage->setMargin(0);
$oPage->addScript('messagebox', '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>');
$oPage->addScript('exec', $execScript);
$oPage->addScript('cfoldingrow.js', '<script language="JavaScript" src="scripts/cfoldingrow.js"></script>');
$oPage->addScript('parameterCollector.js', '<script language="JavaScript" src="scripts/parameterCollector.js"></script>');

//generate current content for Object Pager
$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $_REQUEST["elemperpage"]);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("frame", 2);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);
$oPager = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e302", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $pagerl);

//add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

//send new object pager to left_top
$sRefreshPager = '
    <script type="text/javascript">
        var sNavigation = \''.$sPagerContent.'\';
        var left_top = parent.left_top;
        
        if (left_top.document) {
            var oPager = left_top.document.getElementById(\'0ed6d632-6adf-4f09-a0c6-1e38ab60e302\');
            var sDisplay = oPager.style.display;
            if (oPager) {
                oInsert = oPager.firstChild;
                oInsert.innerHTML = sNavigation;
                left_top.newsletter_listoptionsform_curPage = '.$_REQUEST["page"].';
                left_top.toggle_pager(\'0ed6d632-6adf-4f09-a0c6-1e38ab60e302\');
                if (sDisplay == \'none\') {
                    oPager.style.display = sDisplay;
                }
            }
        }
    </script>';
    
$oPage->addScript('refreshpager', $sRefreshPager);    

$oPage->setContent($oMenu->render(false));
$oPage->render();


?>