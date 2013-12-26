<?php

/**
 * A one-time script to create the generalized users and user_identities tables from the gfc_users table
 */

require_once('db_connect.php');
require_once('../world/CurrentUser.php');
$GLOBALS['DEBUG_QUERY_TIMES']=TRUE;

$gfc_users_rows = sql_query_or_die("SELECT * FROM gfc_users");
while ($row = sql_fetch_assoc($gfc_users_rows)) {
	$external_userid = $row['id'];
	$internal_userid = add_user('GFC', $external_userid, $row['name'], $row['thumbnail']);

	$external_userid_quoted = quote_all($external_userid);
	$tables_to_update = array('land_leaders','user_article_virtue','user_city','user_news','user_soldier','user_soldier_loyalty','user_treasure','world_leaders');
	foreach ($tables_to_update as $table) {
		sql_query_or_die("UPDATE $table SET userid=$internal_userid WHERE userid=$external_userid_quoted");
	}
}

?>