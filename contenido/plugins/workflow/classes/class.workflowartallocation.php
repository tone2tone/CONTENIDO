<?php
/**
 * This file contains the class for workflow art allocation management.
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for workflow art allocation management.
 *
 * @package Plugin
 * @subpackage Workflow
 * @method WorkflowArtAllocation createNewItem
 * @method WorkflowArtAllocation next
 */
class WorkflowArtAllocations extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["workflow_art_allocation"], "idartallocation");
        $this->_setItemClass("WorkflowArtAllocation");
    }

    /**
     * @param $idartlang
     *
     * @return bool|Item
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($idartlang) {
        global $cfg;

        $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idartlang = " . cSecurity::toInteger($idartlang);

        $this->db->query($sql);
        if (!$this->db->nextRecord()) {
            $this->lasterror = i18n("Article doesn't exist", "workflow");
            return false;
        }

        $this->select("idartlang = '$idartlang'");

        if ($this->next() !== false) {
            $this->lasterror = i18n("Article is already assigned to a usersequence step.", "workflow");
            return false;
        }

        $newitem = $this->createNewItem();
        $newitem->setField("idartlang", $idartlang);
        $newitem->store();

        return ($newitem);
    }

}

/**
 * Class WorkflowArtAllocation
 * Class for a single workflow allocation item
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowArtAllocation extends Item {

    /**
     * Constructor Function
     */
    public function __construct() {
        global $cfg;

        parent::__construct($cfg["tab"]["workflow_art_allocation"], "idartallocation");
    }

    /**
     * @return bool|WorkflowItem
     * @throws cDbException
     * @throws cException
     */
    public function getWorkflowItem() {
        $userSequence = new WorkflowUserSequence();
        $userSequence->loadByPrimaryKey($this->values["idusersequence"]);

        return ($userSequence->getWorkflowItem());
    }

    /**
     * Returns the current item position
     *
     * @throws cDbException
     * @throws cException
     */
    public function currentItemPosition() {
        $idworkflowitem = $this->get("idworkflowitem");

        $workflowItems = new WorkflowItems();
        $workflowItems->select("idworkflowitem = '$idworkflowitem'");

        if (($item = $workflowItems->next()) !== false) {
            return ($item->get("position"));
        }
    }

    /**
     * Returns the current user position
     */
    public function currentUserPosition() {
        return ($this->get("position"));
    }

    /**
     * Overriden store function to send mails
     *
     * @return bool
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function store() {
        global $cfg;

        $mailer = new cMailer();

        if (array_key_exists("idusersequence", $this->modifiedValues)) {
            $usersequence = new WorkflowUserSequence();
            $usersequence->loadByPrimaryKey($this->values["idusersequence"]);

            $email = $usersequence->get("emailnoti");
            $escal = $usersequence->get("escalationnoti");

            if ($email == 1 || $escal == 1) {
                // Grab the required informations
                $curEditor = getGroupOrUserName($usersequence->get("iduser"));
                $idartlang = $this->get("idartlang");
                $timeunit = $usersequence->get("timeunit");
                $timelimit = $usersequence->get("timelimit");

                $db = cRegistry::getDb();
                $sql = "SELECT author, title, idart FROM " . $cfg["tab"]["art_lang"] . " WHERE idartlang = " . (int) $idartlang;

                $db->query($sql);

                if ($db->nextRecord()) {
                    $idart = $db->f("idart");
                    $title = $db->f("title");
                    $author = $db->f("author");
                }

                // Extract category
                $sql = "SELECT idcat FROM " . $cfg["tab"]["cat_art"] . " WHERE idart = " . (int) $idart;
                $db->query($sql);

                if ($db->nextRecord()) {
                    $idcat = $db->f("idcat");
                }

                $sql = "SELECT name FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcat = " . (int) $idcat;
                $db->query($sql);

                if ($db->nextRecord()) {
                    $catname = $db->f("name");
                }

                $starttime = time();

                switch ($timeunit) {
                    case "Seconds":
                        $maxtime = $starttime + $timelimit;
                        break;
                    case "Minutes":
                        $maxtime = $starttime + ($timelimit * 60);
                        break;
                    case "Hours":
                        $maxtime = $starttime + ($timelimit * 3600);
                        break;
                    case "Days":
                        $maxtime = $starttime + ($timelimit * 86400);
                        break;
                    case "Weeks":
                        $maxtime = $starttime + ($timelimit * 604800);
                        break;
                    case "Months":
                        $maxtime = $starttime + ($timelimit * 2678400);
                        break;
                    case "Years":
                        $maxtime = $starttime + ($timelimit * 31536000);
                        break;
                    default:
                        $maxtime = $starttime + $timelimit;
                }

                if ($email == 1) {
                    $email = i18n("Hello %s,\n\n" . "you are assigned as the next editor for the Article %s.\n\n" . "More informations:\n" . "Article: %s\n" . "Category: %s\n" . "Editor: %s\n" . "Author: %s\n" . "Editable from: %s\n" . "Editable to: %s\n");

                    $filledMail = sprintf($email, $curEditor, $title, $title, $catname, $curEditor, $author, date("Y-m-d H:i:s", $starttime), date("Y-m-d H:i:s", $maxtime));
                    $user = new cApiUser();

                    if (isGroup($usersequence->get("iduser"))) {
                        $sql = "SELECT idgroupuser, user_id FROM " . $cfg["tab"]["groupmembers"] . " WHERE
                                group_id = '" . $db->escape($usersequence->get("iduser")) . "'";
                        $db->query($sql);

                        while ($db->nextRecord()) {
                            $user->loadByPrimaryKey($db->f("user_id"));
                            $mailer->sendMail(NULL, $user->getField("email"), stripslashes(i18n('Workflow notification')), $filledMail);
                        }
                    } else {
                        $user->loadByPrimaryKey($usersequence->get("iduser"));
                        $mailer->sendMail(NULL, $user->getField("email"), stripslashes(i18n('Workflow notification')), $filledMail);
                    }
                } else {
                    $email = i18n("Hello %s,\n\n" . "you are assigned as the escalator for the Article %s.\n\n" . "More informations:\n" . "Article: %s\n" . "Category: %s\n" . "Editor: %s\n" . "Author: %s\n" . "Editable from: %s\n" . "Editable to: %s\n");

                    $filledMail = sprintf($email, $curEditor, $title, $title, $catname, $curEditor, $author, date("Y-m-d H:i:s", $starttime), date("Y-m-d H:i:s", $maxtime));

                    $user = new cApiUser();

                    if (isGroup($usersequence->get("iduser"))) {

                        $sql = "SELECT idgroupuser, user_id FROM " . $cfg["tab"]["groupmembers"] . " WHERE
                                group_id = '" . $db->escape($usersequence->get("iduser")) . "'";
                        $db->query($sql);

                        while ($db->nextRecord()) {
                            $user->loadByPrimaryKey($db->f("user_id"));
                            $mailer->sendMail(NULL, $user->getField("email"), stripslashes(i18n('Workflow escalation')), $filledMail);
                        }
                    } else {
                        $user->loadByPrimaryKey($usersequence->get("iduser"));
                        $mailer->sendMail(NULL, $user->getField("email"), stripslashes(i18n('Workflow escalation')), $filledMail);
                    }
                }
            }
        }

        if (parent::store()) {
            $this->db->query("UPDATE " . $this->table . " SET `starttime`=NOW() WHERE `" . $this->getPrimaryKeyName() . "`='" . $this->get($this->getPrimaryKeyName()) . "'");
            return true;
        } else {
            return false;
        }
    }

}
