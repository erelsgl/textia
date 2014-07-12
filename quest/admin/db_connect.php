<?php
$SCRIPTURL=$GLOBALS['SCRIPTURL']="script";
$SCRIPTFOLDER=$GLOBALS['SCRIPTFOLDER']=realpath(dirname(__FILE__)."/../../$SCRIPTURL"); 

require_once("$SCRIPTFOLDER/sql.php");
require('db_connect_params.php');

$GLOBALS['CREATE_BACKUP_DIRECTORY'] = false; // already created by create.php
$GLOBALS['BACKUP_MODIFICATION_QUERIES'] = false;

$mysql_options = MYSQL_CLIENT_INTERACTIVE; // Allow interactive_timeout seconds (instead of wait_timeout) of inactivity before closing the connection - prevent the "MySQL client has gone away" after reading a long text from wikisource
$GLOBALS['link'] = sql_connect_and_select($db_host, $db_name, $db_user, $db_pass, false, $mysql_options);

sql_set_charset('utf8');
sql_query_or_die("SET storage_engine=MYISAM");
?>
