<?php
/** קידוד אחיד
 * @file game.php - basic game-related routines and variables
 * @author Erel Segal http://tora.us.fm
 * @date 2009-04-13
 * @copyright GPL 
 */

error_reporting(E_ALL);


$linkroot = "..";
$fileroot = dirname(__FILE__)."/$linkroot";
$SCRIPTFOLDER = "$fileroot/../script";

require_once("$SCRIPTFOLDER/system.php");
require_once("$SCRIPTFOLDER/coalesce.php");
require_once("$SCRIPTFOLDER/session_forever.php");
require_once("$SCRIPTFOLDER/remove_magic_quotes.php");

//var_dump($_SESSION);

require_once("$fileroot/../sites/MediawikiClient.php");
$GLOBALS['MediawikiClient'] = new MediawikiClient("he.wikisource.org");


require_once("$SCRIPTFOLDER/html.php");
global $HTML_DIRECTION, $HTML_LANGUAGE, $HTML_ENCODING;
$HTML_DIRECTION = 'rtl';
$HTML_LANGUAGE = 'he';
$HTML_ENCODING = 'utf-8';

require_once("$fileroot/../quest/admin/db_connect.php");
if (isset($_GET['debug_times']))
	$GLOBALS['DEBUG_QUERY_TIMES']=TRUE;
sql_set_charset('utf8');

require_once("$SCRIPTFOLDER/fix_include_path.php");
require('error_handler.php');
if (!$GLOBALS['is_local']) {
	$GLOBALS['error_handler_before_log_system']=set_error_handler("error_handler");
}


global $login, $logout, $link_without_logout, $base;
$login = isset($_GET['to']) && $_GET['to']=='login';
$logout = isset($_GET['to']) && $_GET['to']=='logout';
@$link_without_logout =  "$_SERVER[PHP_SELF]?".preg_replace("/&?to=[^&]*/","",$_SERVER['QUERY_STRING']); // ignore error when query_string not defined

$base_for_links = (isset($_SERVER['PHP_SELF'])? 
		dirname($_SERVER['PHP_SELF']): 
		"/quest/world");

if (!isset($is_facebook))
	$is_facebook = false;
//$is_facebook = isset($_GET['fb_sig_api_key']) && ($_GET['fb_sig_api_key']==$GLOBALS['FACEBOOK_API_KEY']);

if (empty($current_user_details)) {
	require_once('CurrentUser.php');
	if ($logout) {
		logout_current_user();
		$current_user_details = NULL;
		$current_userid = NULL;

		show_html_header('logout');
		print "<p dir='rtl'>
			התנתקת מטקסטיה. 
			<a href='$link_without_logout'>
			חזרה לטקסטיה
			</a>
		</p>
		";
		show_html_footer();
		die;

	} else {
		$current_user_details = read_current_user_details();
		$current_userid = $current_user_details['id'];
	}
	$current_userid_quoted = quote_all($current_userid);
}

/*
   Read the current user - define the variables:
   $current_userid
   $current_user_details
*/


require_once("$SCRIPTFOLDER/pack.php");

require_once("language_system.php");

function variable_from_get_or_session($name, $default_value=NULL) {
	if (isset($_GET[$name]))
		$_SESSION[$name] = $_GET[$name];
	if (isset($_SESSION[$name]))
		return $_SESSION[$name];
	else
		return $default_value;
}

/**
 * @param string $class_name name of a PHP class.
 * @return If $_GET['title'] is defined - create the given class from the given Wikisource title, put in in the appropriate session variable, and return it. 
 *  Otherwise, if it in the $_SESSION[$class_name], return it. 
 *  Otherwise, return NULL.
 */
function object_from_get_or_session($class_name, $default_title=NULL) {
	if (isset($_GET['title'])) {  // a wikisource title
		$object = new $class_name($_GET['title']);
		//print_r($object);
		$_SESSION[$class_name] = serialize($object);
	} elseif (isset($_SESSION[$class_name])) {
		if (isset($_SESSION[$class_name."_require"])) {
			require($_SESSION[$class_name."_require"]); // define necessary classes
		}
		$object = unserialize($_SESSION[$class_name]);
	} elseif ($default_title) {  // a wikisource title
		$object = new $class_name($default_title);
		$_SESSION[$class_name] = serialize($object);
	} else {
		print "<meta charset='utf-8'/>
	<p dir='rtl'>
	הגעת לאזור לא ממופה.
	<a href='world.php'>חזרה למפת העולם המוכר</a>
	<!--a href='http://he.wikisource.org/wiki/קטגוריה:משחקים'>יש להיכנס למשחק מאתר ויקיטקסט</a-->
	</p>";
		die;
	}

	return $object;
}

function redirect_if_requested() {
	if (!empty($_GET['redirect'])) {
		require_once("$GLOBALS[SCRIPT]/callback.php");
		$GLOBALS['RedirectSystem']->redirect("http://$_SERVER[HTTP_HOST]/quest/index.php?mslul=quest/world/".basename($_SERVER['PHP_SELF']));
		die;
	}
}

/**
 * @param string $class_name name of a PHP class.
 * @return If $_GET[$class_name] is defined - create the given class from the given Wikisource title, put in in the appropriate session variable, and return it. Otherwise, if $_SESSION[$class_name] is defined, return it. Otherwise, create an object using the given $default_title.
 */
function secnodary_object_from_get_or_session($class_name, $default_title=NULL) {
	if (isset($_GET[strtolower($class_name)])) {  // a wikisource title
		$object = new $class_name($_GET[strtolower($class_name)]);
		$_SESSION[$class_name] = serialize($object);
		return $object;
	} elseif (isset($_SESSION[$class_name])) {
		return unserialize($_SESSION[$class_name]);
	} else {
		return new $class_name($default_title); // default class
	}
}

/**
 * @param string $class_name name of a PHP class.
 * @return If $_SESSION[$class_name] is defined - return it. Other wise, if $_GET[$class_name] is defined, create the given class from the given Wikisource title, put in in the appropriate session variable, and return it. Otherwise, return NULL.
 */
function object_from_session_or_get($class_name, $default_title=NULL) {
	if (isset($_SESSION[$class_name])) {
		return unserialize($_SESSION[$class_name]);
	} elseif (isset($_GET[strtolower($class_name)])) {  // a wikisource title
		$object = new $class_name($_GET[strtolower($class_name)]);
		$_SESSION[$class_name] = serialize($object);
		return $object;
	} else {
		return new $class_name($default_title); // default class
	}
}

function refresh_object($class_name, $fresh_instance) {
	$_SESSION[$class_name] = serialize($fresh_instance);
	return $fresh_instance;
}

function show_html_header($title, $class=NULL, $virtue_learned=NULL, $additional_header='') {
	global $linkroot, $current_userid, $current_user_details, $logout, $link_without_logout, $is_facebook;
	$jquery = 'https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js';
	echo xhtml_header(
		$title,
		$class? $class: $title,
		array(/*"$linkroot/../_script/klli.css", */"world.css"),
		"
		<script type='text/javascript' src='$linkroot/../_script/elements.js'></script>
		<script type='text/javascript' src='$linkroot/../_script/jquery.taconite.js'></script>
		<script type='text/javascript' src='$jquery'></script>
		".$additional_header
		);
	if ($logout) {
		// temporary page for logging out - don't print anything
	}

	if ($current_userid) {
		print links_for_logged_in_user();
		print "<a name='top' class='greeting'>שלום ".user_name_with_link($current_user_details)."!</a>";
		$current_user_stats = calculate_current_user_stats();
		print user_stats_html($current_user_stats, $virtue_learned);
	} else {
		if (!$logout) print links_for_logged_out_user();
	}

	echo "
		<div class='world'>
	";
}

function show_html_footer($switch_view=TRUE) {
	global $practice, $logout, $link_without_logout, $current_userid, $is_facebook;
	$switch_view_html = ($switch_view? 
		"תצוגה: ". ($practice? 
			"<a href='?practice=0'>משחק</a> / <b>חידון</b>":
			"<b>משחק</b> / <a href='?practice=1'>חידון</a>"):
		"");
	
	print "
		</div><!--world-->
		<p class='details'>$switch_view_html</p>
		";
	if (!$logout) print login_or_logout_script();
	xhtml_footer();
}

function title_for_display($title_in_wikisource) {
	$title_for_display = (preg_match("@[/](.+)$@",$title_in_wikisource,$matches)? $matches[1]: (preg_match("@[:](.+)$@",$title_in_wikisource,$matches)? $matches[1]: $title_in_wikisource));
	$title_for_display = preg_replace("/-/"," ",$title_for_display);
	return $title_for_display;
}


/**
 * @return string that describes who rules the city. used in both city view and land view.
 */ 
function city_ruler_string($user_that_rules_this_city) {
	return (
		$user_that_rules_this_city=='YOU'? " בשליטתך": (
		$user_that_rules_this_city? " בשליטת ".$user_that_rules_this_city: 
		" ללא שליט"));
}

function city_ruler_explanation_string($user_that_rules_this_city) {
	return (
		$user_that_rules_this_city=='YOU'? "העיר תישאר בשליטתך עד ששחקן אחר יעביר את כל החיילים לצדו": 
		"כדי לכבוש את העיר עליך לשכנע את כל החיילים לעבור לצדך");
}

function article_url($wikisource_title) {
	global $base_for_links;
	return "$base_for_links/article.php?title=".urlencode($wikisource_title);
}


/**
 * @param array $ruler_data (id, name, thumbnail)
 */
function city_ruler_image_html($ruler_data) {
	if (!$ruler_data) {
		$ruler_data = array(
			"id"=>0, 
			"name"=>"אין שליט", 
			"thumbnail"=>"question_mark.png");
	}
	return user_image_with_link($ruler_data);
}

function soldier_loyalty_string($user_this_soldier_is_loyal_to, $loyalty_to_user=NULL, $loyalty_to_current_user=NULL) {
	return (
		$user_this_soldier_is_loyal_to=='YOU'?
			($loyalty_to_user? " - נאמן לך ברמה $loyalty_to_user": ""): 
			(
				$user_this_soldier_is_loyal_to? " - נאמן ל-".$user_this_soldier_is_loyal_to .
				($loyalty_to_user? " ברמה $loyalty_to_user": ""): 
				""
			) . 
			($loyalty_to_current_user? 
				" ונאמן לך ברמה ".$loyalty_to_current_user: ""
			)
	);
}

function city_image_html($image, $city) {
	return ($image?
		"<img class='city' src='http://upload.wikimedia.org/wikipedia/commons/thumb/5/5d/$image/200px-$image.jpg' alt='$city' title='$city' /><br/>":
		"");
}

function treasure_image_html($treasure_data) {
	//print_r($treasure_data);
	$treasure_image = coalesce($treasure_data['image'],'');
	$treasure_name  = $treasure_data['name'];
	return ($treasure_image?
		"<img class='treasure' src='http://upload.wikimedia.org/wikipedia/commons/thumb/3/33/$treasure_image/120px-$treasure_image.jpg' alt='$treasure_name' title='$treasure_name' />":
		"$treasure_name");
}

function treasure_anchor($treasure_name) {
	global $land;
	$treasure_data = $land? $land->treasure_data($treasure_name): NULL;
	$treasure_anchor = $treasure_data? treasure_image_html($treasure_data): "$treasure_name";
	return $treasure_anchor;
}

function land_anchor($main_land) {
	$world = "טקסטיה";
	global $base_for_links;

	$main_land_encoded = htmlspecialchars($main_land);
	$main_land_argument_encoded = urlencode(str_replace(' ','-',htmlspecialchars("משחק:$world/$main_land")));

	return "<a href='$base_for_links/land.php?title=$main_land_argument_encoded'>$main_land_encoded</a>";
}

function calculate_current_user_stats($value_to_refresh=NULL) {
	global $current_userid;
	if (!$current_userid)
		return NULL;

	$current_user_stats = coalesce($_SESSION['current_user_stats'],array());

	if ($value_to_refresh) {
		unset($current_user_stats[$value_to_refresh]);
	} elseif (
		!isset($current_user_stats['userid']) || 
		$current_user_stats['userid']!=$current_userid || // update if userid is different
		!isset($current_user_stats['updated_at']) || 
		time() - $current_user_stats['updated_at'] > 3600) // update each hour
	{
			$current_user_stats = array();
			$current_user_stats['userid'] = $current_userid;
			$current_user_stats['updated_at'] = time();
	}

	$current_userid_quoted = quote_all($current_userid);
	if (!isset($current_user_stats['cities']))
		$current_user_stats['cities'] = sql_evaluate("SELECT COUNT(*) FROM user_city WHERE userid=$current_userid_quoted");
	if (!isset($current_user_stats['soldiers']))
		$current_user_stats['soldiers'] = sql_evaluate("SELECT COUNT(*) FROM user_soldier WHERE userid=$current_userid_quoted");
	if (!isset($current_user_stats['treasures']))
		$current_user_stats['treasures'] = sql_evaluate_array_key_value("SELECT treasure, COUNT(*) FROM user_treasure WHERE userid=$current_userid_quoted GROUP BY treasure");
	if (!isset($current_user_stats['virtues']))
		$current_user_stats['virtues'] = sql_evaluate_array("
			SELECT 
				virtue, 
				COUNT(virtue) AS user_count,
				virtue_count.count AS total_count
			FROM user_article_virtue 
			LEFT JOIN virtue_count USING(virtue)
			WHERE userid=$current_userid_quoted 
			GROUP BY virtue
			");

	$_SESSION['current_user_stats'] = $current_user_stats;
	return $current_user_stats;
}

function table_and_group_function_for_domain($domain, $where_clause=1) {
	$group_function = "COUNT(*)";
	switch ($domain) {
		case 'cities':    $table = "user_city"; break;
		case 'treasures': $table = "user_treasure"; break;
		case 'virtues':   $table = "user_article_virtue"; break;
		case 'soldiers':  $table = "user_soldier"; break;
		case 'loyalty':   $table = "user_soldier_loyalty"; $group_function = "SUM(loyalty)"; break;
		default:          user_error("Unknown domain $domain", E_USER_WARNING); return NULL;
	}
	return array($table, $group_function);
}

function calculate_leader($domain, $land=NULL) {
	global $current_time_quoted;

	list($table, $group_function) = table_and_group_function_for_domain($domain);
	if (!$table) return;

	$query_template = "
		SELECT userid, $group_function AS count 
		FROM $table
		%s
		GROUP BY userid
		ORDER BY 2 DESC
		LIMIT 1
		";
	$world_leader_stats = sql_evaluate_assoc(sprintf($query_template,""));
	if (!$world_leader_stats) {
		user_error("No leader data for domain $domain", E_USER_WARNING);
		return;
	}
	sql_query_or_die("
		REPLACE INTO world_leaders(domain, userid, count, updated_at) VALUES (
			'$domain',
			".quote_all($world_leader_stats['userid']).",
			$world_leader_stats[count],
			$current_time_quoted
		)
		");

	if ($land) {
		$land_quoted = quote_all($land);
		$land_leader_stats = sql_evaluate_assoc(sprintf($query_template,"WHERE land=$land_quoted"));
	
		if (!$land_leader_stats) {
			user_error("No leader data for domain $domain, land $land_quoted", E_USER_WARNING);
			return;
		}
	
		if (!empty($land_leader_stats['userid']))
			sql_query_or_die("
				REPLACE INTO land_leaders(land, domain, userid, count, updated_at) VALUES (
					$land_quoted,
					'$domain',
					".quote_all($land_leader_stats['userid']).",
					$land_leader_stats[count],
					$current_time_quoted
				)
				");
	} else { /* calculate stats for all lands */
		$lands = sql_evaluate_array("SELECT DISTINCT land FROM user_soldier_loyalty");
		foreach ($lands as $land) {
			$land_quoted = quote_all($land);
			$land_leader_stats = sql_evaluate_assoc(sprintf($query_template,"WHERE land=$land_quoted"));
		
			if (!$land_leader_stats) continue; // this is an error above but not here
		
			if (!empty($land_leader_stats['userid']))
				sql_query_or_die("
					REPLACE INTO land_leaders(land, domain, userid, count, updated_at) VALUES (
						$land_quoted,
						'$domain',
						".quote_all($land_leader_stats['userid']).",
						$land_leader_stats[count],
						$current_time_quoted
					)
					");
		}
	}
}

function user_stats_html($user_stats, $virtue_learned=NULL) {
	$space = "&nbsp;&nbsp;";
	$updated_at_text = "עודכן ב: ".date('Y-m-d H:i:s',$user_stats['updated_at']);
	$html = "
		<div class='stats' dir='rtl' title='$updated_at_text'>
		יש לך:
		$space
		<b>$user_stats[cities]</b> ערים
		$space
		<b>$user_stats[soldiers]</b> חיילים
		$space";
	foreach ($user_stats['treasures'] as $treasure=>$count) {
		$html .= "
			<b>$count</b> $treasure 
			$space";
	}

	$html_virtues = '';
	foreach ($user_stats['virtues'] as $stats) {
		$virtue = $stats['virtue'];
		$class = ($virtue==$virtue_learned? "learned": "");
		$count_html = "<span class='$class' title='צברת $stats[user_count] יחידות $virtue מתוך $stats[total_count] אפשריות בעולם'><span class='virtue'>$stats[user_count]/$stats[total_count]</span> $virtue</span>";
		$html_virtues .= "
			$count_html
			$space";
	}
	if ($html_virtues)
		$html .= "<br/>מידות:$space$html_virtues";
	$html .= "
		</div> ";
	return $html;
}

function add_virtue_to_user($name, $article, $news_parameter=NULL) {
	global $current_time_quoted, $current_userid_quoted, $land, $city;
	$name_quoted = quote_all($name);
	$article_quoted = quote_all($article);
	$land_quoted = quote_all($land->title_for_display);
	$city_quoted = quote_all($city->title_for_display);
	$news_parameter_quoted = $news_parameter? quote_all($news_parameter): $name_quoted;
	sql_query_or_die("
		REPLACE INTO user_article_virtue(userid, article, virtue, land)
		VALUES($current_userid_quoted, $article_quoted, $name_quoted, $land_quoted)
		");
	calculate_current_user_stats('virtues');
	calculate_leader('virtues', $land->title_for_display);

	sql_query_or_die("
		DELETE FROM user_news
		WHERE land=$land_quoted 
		AND   city=$city_quoted
		AND   userid=$current_userid_quoted
		AND   parameter=$news_parameter_quoted
		AND   type='user_found_virtue'
		");
	require_once('news_add.php');
	news_add($land_quoted, $city_quoted, 'user_found_virtue', $news_parameter_quoted);
	news_add_facebook($land->title_for_display, $city->title_for_display, 'i_found_virtue', $name);
}

function add_treasure_to_user($name, $news_parameter=NULL) {
	global $current_time_quoted, $current_userid_quoted, $land, $city;
	$name_quoted = quote_all($name);
	$land_quoted = quote_all($land->title_for_display);
	$city_quoted = quote_all($city->title_for_display);
	$news_parameter_quoted = $news_parameter? quote_all($news_parameter): $name_quoted;

	sql_query_or_die("
		REPLACE INTO user_treasure(land, city, treasure, userid)
		VALUES($land_quoted, $city_quoted, $name_quoted, $current_userid_quoted)
		");
	calculate_current_user_stats('treasures');
	calculate_leader('treasures', $land->title_for_display);
	require_once('news_add.php');
	news_add($land_quoted, $city_quoted, 'user_found_treasure', $news_parameter_quoted);
	news_add_facebook($land->title_for_display, $city->title_for_display, 'i_found_treasure', $name);
}



/* User data, images and links */

function user_data($id_quoted) {
	return sql_evaluate_assoc("
		SELECT * FROM users
		WHERE id=$id_quoted");
}

/**
 * @param $row array from the `users` table
 */
function user_name_for_display_encoded($row) {
	$name_for_display = (
		!empty($row['name'])? $row['name']: (
		!empty($row['id'])? $row['id']: "anonymous"));
	if ($name_for_display=="YOU")
		$name_for_display = static_text('you');
	return htmlspecialchars($name_for_display);
}

/**
 * @param $row array from the `users` table
 */
function user_image($row) {
	$name_for_display_encoded = user_name_for_display_encoded($row);
	$name_with_image = (
		!empty($row['thumbnail'])? 
			"<img class='thumbnail' src='$row[thumbnail]' alt='$name_for_display_encoded' title='$name_for_display_encoded' />": 
			"<img class='thumbnail' src='/images/NoPictureGreen.png' alt='$name_for_display_encoded' title='$name_for_display_encoded' />");
			//"<div class='thumbnail'>$name_for_display_encoded</div>");
	return $name_with_image;
}

/**
 * @param $row array from the `users` table
 */
function user_name_with_link($row) {
		global $base_for_links;
		$name_for_display_encoded = user_name_for_display_encoded($row);
		return $row['id']? "<a href='$base_for_links/adventurer.php?id=$row[id]'>$name_for_display_encoded</a>": $name_for_display_encoded;
}

/**
 * @param $row array from the `users` table
 */
function user_image_with_link($row) {
		global $base_for_links;
		$name_with_image = user_image($row);
		return $row['id']? "<a href='$base_for_links/adventurer.php?id=$row[id]'>$name_with_image</a>": $name_with_image;
}



?>