<?php

/**
 * @file copy_tables.php - copy all tables from one database to another
 * @author Erel Segal
 * @date 2009-06-20
 */

print "<h1>copy_tables.php</h1>\n";
error_reporting(E_ALL);

$db_host = 'localhost';
$db_user = 'tora_erel';
$db_pass = 't1tmne1nmp';

$source_database = "tora_erel";
$target_database = "tora_quest";

require_once("tables.php");
require_once("../../_script/sql.php");

$link = sql_connect($db_host, $db_user, $db_pass, false, 0);
if (!$link)
	die("Could not connect as $db_user: " . sql_get_last_message());


sql_print_query("SHOW DATABASES");

test_database($source_database);
test_database($target_database);

$GLOBALS['DEBUG_QUERY_TIMES']=TRUE;
foreach ($ALL_TABLES as $table=>$keys) {
	print "<h2>Copying from $source_database.$table to $target_database.$table</h2>\n";
	sql_copy_table("$source_database.$table", "$target_database.$table");
}


function test_database($database_name) {
	$database_exists = sql_evaluate("SHOW DATABASES LIKE ".quote_all($database_name),FALSE);
	if (!$database_exists)
		die("Database '$database_name' not found!");
}
?>