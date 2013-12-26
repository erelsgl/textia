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

require_once dirname(__FILE__)."/../../_script/sql_backup.php";
require_once dirname(__FILE__)."/../../_script/system.php";
require_once('tables.php');

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

		require_once(dirname(__FILE__)."/../../_script/updates.php");
		run_all_new_updates_in_file(str_replace("admin","updates",dirname(__FILE__)), "todo.txt");

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

		$encrypt_on_server = false;
		if ($encrypt_on_server && preg_match("|/meezoog.com/|",__FILE__)) {
			if (!file_exists($zipfile)) {
				user_error("Couldn't create zipfile $zipfile!", E_USER_WARNING);
				return;
			}

			if ($encrypt_on_server) {
				shell_exec_verbose("mv $zipfile.gpg $zipfile.old.gpg");
				shell_exec_verbose("gpg  --recipient meezoog@meezoog.com --output $zipfile.gpg --encrypt $zipfile");
			}
	
		    if (!file_exists("$zipfile.gpg")) {
	            if ($encrypt_on_server) user_error("Couldn't create encrypted zipfile $zipfile.gpg 
	                    (this is not an error if you run this script from a browser,
	                    because the apache user doesn't 
	                    have permissions to the .gnupg folder)", E_USER_WARNING);
	            print "<p><a href='../_magr/$_GET[dir].tar.gz'>Download the backup file</a></p>\n";
	            return;
	    	}
	    	print "<p><a href='../_magr/$_GET[dir].tar.gz.gpg'>Download the encrypted backup file</a></p>\n";
		} else {
		    print "<p><a href='../_magr/$_GET[dir].tar.gz'>Download the backup file</a></p>\n";
		}
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


require_once('db_connect.php');
run_backup_site();
?>