<?php


/** 
 * $USER_TABLES are tables that contain information about specific users.
 * They change due to user activity, and should be backed up regularly.
 *    
 * key = table name. value = array of key fields. 
 */
$GLOBALS['USER_TABLES'] = array(
		'users' => array('userid'),
		'user_identities' => array('identity_id'),
		'city_virtue_article' => array("city","virtue","article"),
		'gfc_users' => array("id"),
		'land_leaders' => array("land","domain"),
		'treasure_data' => array("name"),
		'user_article_virtue' => array("userid","article","virtue"),
		'user_city' => array("land","city"),
		'user_news' => array("land","city","happened_at","type"),
		'user_soldier_loyalty' => array("land","city","soldier","userid"),
		'user_soldier' => array("land","city","soldier"),
		'user_treasure' => array("land","city","treasure"),
		'virtue_count' => array("virtue"),
		'virtue_data' => array("virtue"),
		'world_leaders' => array("land","domain"),
		);

/** 
 * $CONFIGURATION_TABLES are tables that contain general system information.
 * They change only when the developers change them, and should be backed up in the version control system.
 *    
 * key = table name. value = array of key fields. 
 */
$GLOBALS['CONFIGURATION_TABLES'] = array(
		'wikisource_cache' => array("title"),
		'wikisource_plaintext_cache' => array("title"),
		'wikisource_question_index' => array("id"),
		'table_editor_cfg' => array("table_name","field_name","param_type")
		);

/** 
 * $LOG_TABLES are tables that are NOT backed up.
 *
 * key = table name. value = array of key fields. 
 */
$GLOBALS['LOG_TABLES'] = array(
		'gfc_users_log' => array(),
		);

$GLOBALS['ALL_TABLES'] = array_merge(
		$GLOBALS['USER_TABLES'],
		$GLOBALS['CONFIGURATION_TABLES'],
		$GLOBALS['LOG_TABLES']
		);
?>
