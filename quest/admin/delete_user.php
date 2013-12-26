<?php
error_reporting(E_ALL);

/**
 * A one-time script to create the generalized users and user_identities tables from the gfc_users table
 */

require_once('db_connect.php');
require_once('../world/CurrentUser.php');
$GLOBALS['DEBUG_QUERY_TIMES']=TRUE;

$tables_to_update = array('land_leaders','user_article_virtue','user_city','user_news','user_soldier','user_soldier_loyalty','user_treasure','world_leaders', 'user_identities');

if (empty($_GET['userid'])) {
	print "<p>Please enter userid!";
	die;
}
$id_quoted = (int)$_GET['userid'];
$row = sql_evaluate_assoc("SELECT * FROM users WHERE id=$id_quoted");
print "<pre>"; print_r($row); print "</pre>"; 

if (empty($_POST)) {
	print "
	<form method='post' action=''>
	Are you sure you want to delete this user? <input name='confirm' type='checkbox' />
	<input type='submit' />
	</form>
	";
} else {
	if (!empty($_POST['confirm'])) {
		sql_query_or_die("DELETE FROM users WHERE id=$id_quoted");
		foreach ($tables_to_update as $table) {
			sql_query_or_die("DELETE FROM $table WHERE userid=$id_quoted");
		}
	}
	print "Deleted!";
}

?>