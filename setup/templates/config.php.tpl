<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Defines all general variables of CONTENIDO.
 *
 * NOTE: This configuration file was generated by CONTENIDO setup!
 *       If you want to modify the configurations for some reason, create a file
 *       "config.local.php" in "data/config/{environment}/" and define your own settings.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO configuration
 * @version    1.1.4
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2010-05-16
 *   $Id$
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


global $cfg;

/* Section 1: Path settings
 * ------------------------
 *
 * Path settings which will vary along different CONTENIDO settings.
 *
 * A little note about web and server path settings:
 * - A Web Path can be imagined as web addresses. Example:
 *   http://192.168.1.1/test/
 * - A Server Path is the path on the server's hard disk. Example:
 *   /var/www/html/contenido    for Unix systems OR
 *   c:/htdocs/contenido        for Windows systems
 */

/* The root server path where all frontends reside */
$cfg['path']['frontend']                = '{CONTENIDO_ROOT}';

/* The root server path to the CONTENIDO backend */
$cfg['path']['contenido']               = $cfg['path']['frontend'] . '/contenido/';

/* The root server path to the data directory */
$cfg['path']['data']                    = $cfg['path']['frontend'] . '/data/';

/* The root server path to the conlib directory */
$cfg['path']['phplib']                  = $cfg['path']['frontend'] . '/conlib/';

/* The root server path to the pear directory */
$cfg['path']['pear']                    = $cfg['path']['frontend'] . '/pear/';

/* The server path to all WYSIWYG-Editors */
$cfg['path']['all_wysiwyg']             = $cfg['path']['contenido']  . 'external/wysiwyg/';

/* The server path to the desired WYSIWYG-Editor */
$cfg['path']['wysiwyg']                 = $cfg['path']['all_wysiwyg'] . 'tinymce3/';

/* The web server path to the CONTENIDO backend */
$cfg['path']['contenido_fullhtml']      = '{CONTENIDO_WEB}/contenido/';

/* The web path to all WYSIWYG-Editors */
$cfg['path']['all_wysiwyg_html']        = $cfg['path']['contenido_fullhtml'] . 'external/wysiwyg/';

/* The web path to the desired WYSIWYG-Editor */
$cfg['path']['wysiwyg_html']            = $cfg['path']['all_wysiwyg_html'] . 'tinymce3/';



/* Section 2: Database settings
 * ----------------------------
 *
 * Database settings for MySQL/MySQLi. Note that we don't support other databases.
 */

/* The prefix for all CONTENIDO system tables, usually 'con' */
$cfg['sql']['sqlprefix'] = '{MYSQL_PREFIX}';

/* Database extension/driver to use, feasible values are 'mysql' or 'mysqli' */
$cfg['database_extension'] = '{DB_EXTENSION}';

/**
 * Extended database settings. This settings will be used from CONTENIDO 4.9.0.
 *
 * @since  CONTENIDO version 4.9.0
 */
$cfg['db'] = array(
    'connection' => array(
        'host'     => '{MYSQL_HOST}', // (string) The host where your database runs on
        'database' => '{MYSQL_DB}',   // (string) The database name which you use
        'user'     => '{MYSQL_USER}', // (string) The username to access the database
        'password' => '{MYSQL_PASS}', // (string) The password to access the database
        'charset'  => '',             // (string) The charset of connection to database
    ),
    'haltBehavior'    => 'report', // (string) Feasible values are 'yes', 'no' or 'report'
    'haltMsgPrefix'   => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] . ' ' : '',
    'enableProfiling' => false,    // (bool) Flag to enable profiling
);

/**
 * Following database settings ($contenido_host, $contenido_database, etc.)
 * are still available because of downwards compatibility.
 *
 * @deprecated [2011-08-23] Use new DB settings $cfg['db'] from above
 */
$contenido_host = $cfg['db']['connection']['host'];
$contenido_database = $cfg['db']['connection']['database'];
$contenido_user = $cfg['db']['connection']['user'];
$contenido_password = $cfg['db']['connection']['password'];

?>