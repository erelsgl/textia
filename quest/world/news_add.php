<?php

function news_add($land_quoted, $city_quoted, $event_type, $event_parameter_quoted="NULL") {
	global $current_userid_quoted, $current_time_quoted;
	sql_query_or_die("
		INSERT INTO user_news(land, city, userid, happened_at, type, parameter)
		VALUES($land_quoted, $city_quoted, $current_userid_quoted, $current_time_quoted, '$event_type', $event_parameter_quoted)
		");
}

function news_add_facebook($land, $city, $event_type, $event_parameter=NULL) {
	global $current_user_details, $base_for_links;

	if ($current_user_details['external_site']!='Facebook')
		return;

	$prompt = static_text('facebook stream publish prompt');
	$land = str_replace("'","",$land);
	$city = str_replace("'","",$city);
	$event_parameter = str_replace("'","",$event_parameter);
	global $base_for_links;
	$world = "טקסטיה";
	$city_argument_encoded = urlencode($city);
	$land_argument_encoded = urlencode(str_replace(' ','-',htmlspecialchars("משחק:$world/$land")));
	$city_href = "http://$_SERVER[HTTP_HOST]$base_for_links/city.php?title=$city_argument_encoded&amp;land=$land_argument_encoded";

	$news_description = static_text($event_type, NULL, $current_user_details['name'], $event_parameter, "$city ב$land")."!";

	require_once(dirname(__FILE__)."/../../sites/facebook_login.php");
	$GLOBALS['FACEBOOK_ADDITIONAL_SCRIPTS'] .= "
		FB.ui(
			{
				method: 'stream.publish',
				user_message_prompt: '$prompt',
				message: '$news_description',
				attachment: {
					name: 'חדשות טקסטיה',  /* large, linked title */
					caption: '',           /* smaller, unlinked subtitle */   
					description: (         /* smaller, unlinked text */   
						'$news_description'
					),
					href: 'http://apps.facebook.com/textiaworld?land=$land_argument_encoded',
					'media': [{ 
							'type': 'image', 
							'src': 'http://tora.us.fm/quest/style/icons/128px-Globe_of_letters.svg.png',
							'href': 'http://apps.facebook.com/textiaworld'}] 
				},
				action_links: [
					{ 'text': 'היכנסו לעיר', 'href': '$city_href' }
				]
			},
			function(response) {
				if (response && response.post_id) {
					alert('פורסם!');
				} else {
					alert('לא פורסם.');
				}
			}
		);


		//FB.ui(
		//	{
		//		method: 'stream.share',
		//		u: '$city_href'
		//	}
		//);
	";
}

?>