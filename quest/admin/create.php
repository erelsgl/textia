<?php
/**
 * @file create.php - create a new database and a new db_connect_params.php file
 * @author Erel Segal
 * @date 2007-09-25
 */
error_reporting(E_ALL);
set_time_limit(0);

print "
# Create a new database for textia

## Requirements
		
* Apache 2+
* MySQL 5+
* PHP 5+
* PHP-MySQL extension
* PHP CURL module
";

if (!function_exists("mysql_query"))
	die("Textia requires MySQL and PHP-MySQL extension, but they are not installed!\n");
if (!function_exists("curl_init"))
	die("Textia requires CURL and PHP-CURL module, but they are not installed!\n");


$SCRIPT = dirname(__FILE__) . '/../../script';
	
require_once("$SCRIPT/sql.php");
require_once("$SCRIPT/sql_backup.php");
require_once("$SCRIPT/mkpath.php");
require_once("$SCRIPT/coalesce.php");
require_once(dirname(__FILE__) . "/tables.php");

show_create_page();
update_create_page();

function read($vartitle, $varname) {
	$default = coalesce($GLOBALS[$varname],"");
	print "$vartitle [$default]: "; $varvalue = trim(fgets(STDIN));
	$_POST[$varname] = $varvalue? $varvalue: $default;
}

function show_create_page() {
	@include_once(dirname(__FILE__) . "/db_connect_params.php"); // only if it exists
	set_coalesce($GLOBALS['openid_realm'], trim(shell_exec('hostname -I'))?:'localhost');
	set_coalesce($GLOBALS['root_username'], coalesce($GLOBALS['root_username'],'root'));
	set_coalesce($GLOBALS['root_password'], coalesce($GLOBALS['root_password'],''));
	set_coalesce($GLOBALS['db_name'], coalesce($GLOBALS['db_name'],''));
	set_coalesce($GLOBALS['db_user'], coalesce($GLOBALS['db_user'],''));
	set_coalesce($GLOBALS['db_pass'], coalesce($GLOBALS['db_pass'],''));

	read("Realm for Open ID", "openid_realm");
	
	print "
## Credentials

";
	$_POST['db_host'] = $GLOBALS['db_host'];
	read("MySQL root username", "root_username");
	read("MySQL root password", "root_password");
	
	print "
## New database data

";
	read("New database name", "db_name");
	read("New user name", "db_user");
	read("New user password", "db_pass");
	print "Drop existing database if it exists? [no]: "; $drop_db = trim(fgets(STDIN));
	$_POST['drop_db']=($drop_db=='yes');
}

function update_create_page() {
	@include_once("db_connect_params.php"); // only if it exists

	print "
## New database creation

";

	print "* create_database_and_user();
";	create_database_and_user();

	print "* create_db_connect_params();
";	create_db_connect_params();
	
	print "* require('db_connect.php');
";	require('db_connect.php');

	if ($GLOBALS['db_created']) {
		print "* create_database_tables();
"; create_database_tables();
	}
	print "Create complete!
";
	
	print "

## Additional Tasks

* Make the script 'admin/soldiers_forget_loyalty.php' a weekly cron job.
* Create a link named 'quest' in /var/www to the 'textia/quest' folder.
* Start playing at http://$_POST[openid_realm]/quest

";
}


function create_database_and_user() {
	$link = sql_connect(
		$_POST['db_host'],
		$_POST['root_username'],
		$_POST['root_password']);

	if (!$link)
		die('Could not connect as root: ' . sql_get_last_message());

	if (isset($_POST['drop_db']))
		sql_query_or_die("DROP DATABASE IF EXISTS $_POST[db_name]");

	if (sql_database_exists($_POST['db_name'])) {
		echo "Database $_POST[db_name] already exists - won't create it\n";
		$GLOBALS['db_created'] = false;
	} 	else {
		echo "Creating database $_POST[db_name]\n";
		sql_query_or_die("
			CREATE DATABASE $_POST[db_name] 
			CHARACTER SET utf8");
		$GLOBALS['db_created'] = true;
	}

	$db_user_quoted = quote_smart($_POST['db_user'])."@".quote_smart($_POST['db_host']);
	sql_query_or_die("GRANT ALL PRIVILEGES ON $_POST[db_name].* 
		TO $db_user_quoted IDENTIFIED BY ".quote_all($_POST['db_pass'])." WITH GRANT OPTION");
	sql_query_or_die("GRANT RELOAD ON *.* 
		TO $db_user_quoted");

	sql_close($link); // root logs out
}

function create_db_connect_params() {
	$BACKUP_FILEROOT = str_replace('admin','backup',dirname(__FILE__));
	$BACKUP_WHATSNEW_FILEROOT = dirname(__FILE__) . '/../../../whatsnew/textia/backup';
	mkpath($BACKUP_FILEROOT);
	mkpath($BACKUP_WHATSNEW_FILEROOT);
	
	file_put_contents(dirname(__FILE__)."/db_connect_params.php", "<?php 
/**
 * @file parameters for db_connect.php and config.php
 * Automatically generated by $_SERVER[PHP_SELF] at $GLOBALS[current_time]
 */

\$GLOBALS['openid_realm'] = \$openid_realm = '$_POST[openid_realm]';
\$GLOBALS['db_host'] = \$db_host = '$_POST[db_host]';
\$GLOBALS['db_user'] = \$db_user = '$_POST[db_user]';
\$GLOBALS['db_pass'] = \$db_pass = '$_POST[db_pass]';
\$GLOBALS['db_name'] = \$db_name = '$_POST[db_name]';
\$GLOBALS['db_sock'] = '$_POST[db_sock]';
\$GLOBALS['BACKUP_FILEROOT'] = '$BACKUP_FILEROOT';
\$GLOBALS['BACKUP_WHATSNEW_FILEROOT'] = '$BACKUP_WHATSNEW_FILEROOT';
\$GLOBALS['CREATE_DAILY_BACKUPS'] = false;
?".">")  /* put dirname inside the ""! */
or die ("Can't create db_connect_params");
}

function create_database_tables() {
	$GLOBALS['BACKUP_MODIFICATION_QUERIES'] = FALSE;

	/* Create all tables before restoring - to use the most up to date definition */
	foreach (array_keys($GLOBALS['ALL_TABLES']) as $table)
		sql_queries_or_die(file_get_contents(dirname(__FILE__)."/$table.sql"));
			# Warning: table_editor_cfg.sql does not exist!

	// restore configuration tables from the general dir:
	$GLOBALS['BACKUP_FILEROOT'] = dirname(__FILE__) . "/../_magr";

	print "<h1>Restoring configuration tables</h1>\n";
	foreach (array_keys($GLOBALS['CONFIGURATION_TABLES']) as $table)
		restore_table($table);
}


?>