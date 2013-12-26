<?php
/**
 * @file CurrentUser.php - read current user information from $_SESSION, Google Friend Connect, or Facebook API
 * @author Erel Segal http://tora.us.fm
 * @date 2010-08-13
 * @copyright GPL 
 */

require_once(dirname(__FILE__)."/../../sites/AnExternalSiteIdentity.php");
require_once(dirname(__FILE__)."/../../sites/openid/local.php");


/*
 *                        Add Users
 */


function add_user($external_site, $external_userid, $name_for_display, $thumbnail_url) {
	$internal_userid = getValidatedInternalUserid($external_site, $external_userid); // either we have an internal user that matches that identity...
	if ($internal_userid) {                                                         // ... or we create a new user
		sql_query_or_die("
			UPDATE users
			SET name=".quote_all($name_for_display).", thumbnail=".quote_all($thumbnail_url)."
			WHERE id=$internal_userid
			");
	} else {
		sql_query_or_die("LOCK TABLES users WRITE");
		$internal_userid = sql_new_id('users', 'id');
		sql_query_or_die("
			INSERT INTO users(id,name,thumbnail) 
			VALUES ($internal_userid,".quote_all($name_for_display).",".quote_all($thumbnail_url).")");
		sql_query_or_die("UNLOCK TABLES");
		addNewExternalSiteUserid($external_site, $internal_userid, $external_userid, /*$is_validated=*/TRUE);
	}
	return $internal_userid;
}





/*
 *                        Login, Current User, Logout
 */


function logout_current_user() {
	global $current_userid, $current_user_details;

	if (!empty($_SESSION['current_user_details'])) {
		$current_user_details = $_SESSION['current_user_details'];
		if ($current_user_details['external_site']=='Facebook') {
			require_once(dirname(__FILE__)."/../../sites/facebook_login.php");
			facebook_logout();
		} elseif ($current_user_details['external_site']=='Google') {
			//require_once(dirname(__FILE__)."/../../sites/gfc_database.php");
			//gfc_logout_and_log('world');
			unset($_SESSION['openid']);
		}
	}
	unset($_SESSION['current_user_details']);
}




function read_current_user_details() {
	global $is_facebook;
	$external_userid = $external_site = NULL;

	// Case A:   If user is not in local session - we try to get it from Google OpenID or Facebook API:
	if (empty($_SESSION['current_user_details'])) {

		// We try to get a userid from GoogleFriendConnect, but only if we are not inside a Facebook frame:
		if (!$is_facebook) {

			$login = isset($_GET['to']) && $_GET['to']=='login';
			$logout = isset($_GET['to']) && $_GET['to']=='logout';
			$attributes = google_attributes($login, $logout, /*$followup=*/"");
			//print_r($attributes);
			$external_site = 'Google';
			$external_userid = $attributes['current_userid'];
			$name_for_display = $attributes['name_for_display'];
			$current_email =  $attributes['current_email'];
			$thumbnail_url = "";

			//require_once(dirname(__FILE__)."/../../sites/gfc_database.php");
			//gfc_login('world');
			//$gfc_user_details = gfc_user_details_from_session_or_cookie();
			//if ($gfc_user_details) {
			//	$external_site = 'GFC';
			//	$external_userid = $gfc_user_details['id'];
			//	$name_for_display = $gfc_user_details['displayName'];
			//	$thumbnail_url = $gfc_user_details['thumbnailUrl'];
			//}
		}

// 		// We try to get a userid from Facebook:
// 		require_once(dirname(__FILE__)."/../../sites/facebook_login.php");
// 		$facebook_user_details = facebook_user_details_from_cookie();
// 		if ($facebook_user_details) {
// 			$external_userid = $facebook_user_details['id'];
// 			$external_site = 'Facebook';
// 			$name_for_display = $facebook_user_details['name'];
// 			$thumbnail_url = "http://graph.facebook.com/$external_userid/picture?type=square";
// 		}

		// If we have an external userid from either Google or Facebook, we proceed to convert it to an internal userid:
		if ($external_userid) {
			$internal_userid = add_user($external_site, $external_userid, $name_for_display, $thumbnail_url); 

			$_SESSION['current_user_details'] = array(
				'external_site' => $external_site,
				'external_userid' => $external_userid,
				'id' => $internal_userid,
				'name' => $name_for_display,
				'thumbnail' => $thumbnail_url
				);
		}


	// Case B:   If user is in local session - we try to connect it with other available identities
	} else {
		$current_user_details = $_SESSION['current_user_details'];
		if (!is_array($current_user_details)) {
			user_error("not an array: ".print_r($current_user_details,TRUE),E_USER_WARNING);
			unset($_SESSION['current_user_details']);
		}

// 		if ($current_user_details['external_site']!='Facebook') {
// 			// We try to get a userid from Facebook and connect it to the current user:
// 			$internal_userid = $current_user_details['id'];
// 			require_once(dirname(__FILE__)."/../../sites/facebook_login.php");
// 			$facebook_user_details = facebook_user_details_from_cookie();
// 				// If the user 'secretly' logged out of Facebook, this will return: Array ( [error] => Array ( [type] => OAuthException [message] => Error processing access token. )
// 			if ($facebook_user_details) {
// 				if (!isset($facebook_user_details['id'])) {
// 					user_error('Missing Facebook details: '.print_r($facebook_user_details,TRUE),E_USER_WARNING);
// 				} else {
// 					$external_userid = $facebook_user_details['id'];
// 					$external_site = 'Facebook';
// 					addNewExternalSiteUserid($external_site, $internal_userid, $external_userid, /*$is_validated=*/TRUE);
// 				}
// 			}
// 		}
	}

	return (isset($_SESSION['current_user_details'])? $_SESSION['current_user_details']: NULL);
}





function links_for_logged_in_user() {
	global $current_user_details, $openid_logout_link;
	
	$logout_text = static_text('logout');
	//print_r($current_user_details);
	switch ($current_user_details['external_site']) {
		case 'Google':
		case 'Facebook': 
			return "
			<div id='user_links' class='logout'>	
				<a href='".htmlspecialchars($openid_logout_link)."'>$logout_text</a>
			</div>
			"; // TODO: Go to Facebook/GFC, disconnect, then return here
		default: return "";
	}
}


function links_for_logged_out_user() {
	global $is_facebook, $is_local, $openid_login_link;
	if ($is_facebook) {
		return "<fb:login-button perms='publish_stream'>התחברות לטקסטיה</fb:login-button>";
	} else {
		require_once(dirname(__FILE__)."/../../sites/gfc_login.php");
		return 
			//gfc_login_link("התחברות דרך גוגל").
			"<a target='_top' href='".htmlspecialchars($openid_login_link)."'>"."התחברות דרך גוגל"."</a>".
			"&nbsp;".
			(in_array($_SERVER['REMOTE_ADDR'],array('::1','212.76.123.59'))? "<fb:login-button perms='publish_stream'>התחברות דרך פייסבוק</fb:login-button>": "").
			"";
	}
}


function login_or_logout_script() {
	global $link_without_logout, $logout, $current_userid, $is_facebook;
	//require_once(dirname(__FILE__)."/../../sites/facebook_login.php");
	require_once(dirname(__FILE__)."/../../sites/gfc_login.php");
	//$facebook_script = facebook_script('http://apps.facebook.com/textiaworld');
	//$facebook_script = facebook_script('');
	$facebook_script = "";
	$is_facebook = false;
	if ($is_facebook) {
		return $facebook_script;
	} else {
		$gfc_script = ($logout? 
			gfc_logout_script(/*$load_jsapi=*/true, /*$action_on_logout=*/"window.top.location.href='$link_without_logout'"):
			gfc_login_script(/*$load_jsapi=*/true, /*$graphic_button_text=*/$current_userid? "": "התחברות")
		);
		return "$gfc_script\n$facebook_script";
	}
}


?>