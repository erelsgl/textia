<?php
/**
 * @file language_system.php - define the different languages spoken in the World of Textia.
 * This is currently a stub that contains Hebrew only.
 * @copyright GPL 
 */

require_once("$SCRIPTFOLDER/language.php");
$GLOBALS['TRANSLATION_TABLE_FILE_TEMPLATE'] = dirname(__FILE__).'/generated-%s.php';
$GLOBALS['DEFAULT_LANGUAGE_CODE'] = 'he';
$GLOBALS['DEFAULT_LANGUAGE_NAME'] = 'Hebrew';
$GLOBALS['DEFAULT_LANGUAGE_DIRECTION'] = 'rtl';
set_current_language_by_name('Hebrew');
?>