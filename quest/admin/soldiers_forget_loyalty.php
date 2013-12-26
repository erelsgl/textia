<?php
/**
 * @file soldiers_forget_loyalty.php 
 * 
 * Soldiers forget 1 unit of loyalty after a week of being inactive. 
 * 
 * This file updates the loyalty. 
 * 
 * It should be called via "cron" once a week.
 */
$_SESSION='NOT NEEDED';
$current_user_details='NOT NEEDED';
require_once(dirname(__FILE__).'/../world/game.php');
sql_query_or_die("SET @a_week_ago:=ADDDATE(NOW(), INTERVAL -7 DAY);");
sql_query_or_die("
	UPDATE user_soldier_loyalty
	SET loyalty=loyalty-1, forgotten_at=NOW()
	WHERE loyalty>1
	AND updated_at<@a_week_ago
	AND (forgotten_at IS NULL OR forgotten_at<@a_week_ago)
	");
calculate_leader('loyalty');
?>