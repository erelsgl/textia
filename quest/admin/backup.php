<?php
error_reporting(E_ALL);

/**
 * @file backup.php
 * backup and restore utility
 * @author Erel Segal
 * @date 2007-01-28
 */

set_time_limit(0);
ini_set("memory_limit","80M");

require "db_connect.php";

require_once "$SCRIPTFOLDER/sql_backup.php";
require_once "$SCRIPTFOLDER/system.php";
require_once "tables.php";

function restore_page() {
	set_time_limit(0);  // If there is a time limit, the restore might stop at an unstable state!

	print "<h1>Restore</h1>\n";

	if ($GLOBALS['users']) {
		$GLOBALS['RESTORE_TABLES_NEWER_THAN_THEIR_BACKUPS'] = true;
		foreach (array_keys($GLOBALS['USER_TABLES']) as $table)
			restore_table($table);
	}
	if ($GLOBALS['configurations']) { // after users, so the tables will exist
		$GLOBALS['RESTORE_TABLES_NEWER_THAN_THEIR_BACKUPS'] = !empty($_GET['unchanged']); 
		foreach (array_keys($GLOBALS['CONFIGURATION_TABLES']) as $table)
			restore_table($table);
	}
}


function backup_page($retest=false) {
	print "<h1>Backup</h1>\n";
	if ($GLOBALS['users']) {
		foreach (array_keys($GLOBALS['USER_TABLES']) as $table) {
			print "<p>backup $table</p>\n";
			backup_table($table);
		}
	}
	if ($GLOBALS['configurations']) {
		foreach (array_keys($GLOBALS['CONFIGURATION_TABLES']) as $table) {
			print "<p>backup $table</p>\n";
			backup_table($table);
		}
	}

	if (isset($_GET['dir'])) {
		global $BACKUP_FILEROOT;
		$path_to_tar_gz_without_extension = str_replace("admin","_magr",dirname(__FILE__))."/$_GET[dir]";
		create_tar_gz($path_to_tar_gz_without_extension, $BACKUP_FILEROOT, isset($_GET['verbose']));
		$zipfile = str_replace("\\","/","$path_to_tar_gz_without_extension.tar.gz");

		print "<p><a href='../_magr/$_GET[dir].tar.gz'>Download the backup file</a></p>\n";
	}
}


/**
 * @note before running a test, you should have a working database with some interesting data (e.g. by running a simulation...)
 */
function test_page() {
	sql_backup_test ($GLOBALS['TESTED_TABLES'], isset($_GET['retest']));
}

function compare_page() {
	sql_backup_compare($GLOBALS['USER_TABLES'], $_GET['dir1'], $_GET['dir2']);
}

run_backup_site();
?>