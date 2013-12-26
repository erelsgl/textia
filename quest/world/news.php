<?php
/** קידוד אחיד
 * @file news.php - Record and show interesting events that happened lately in the World of Textia.
 * @author Erel Segal http://tora.us.fm
 * @date 2009-05-24
 * @copyright GPL 
 */
require_once('game.php');

function news_box($id, $news, $heading, $start_minimized=FALSE, $javascript=TRUE, $more_url=NULL) {
	if (!$news) return $news;

	$about_encoded = static_html("about")."."; // don't use urlencode

	if ($javascript) {
		$toggle_char = $start_minimized? "+": "-";
		$toggle = "<span class='toggle'>[$toggle_char]</span> ";
	} else {
		$toggle = "";
	}

	$news = "
	<div id='$id' class='news'>
	<h1 title='$about_encoded'>$toggle$heading</h1>
	<table><tbody>
	$news
	</tbody></table>
	".($more_url? "<p class='more'><a  href='$more_url'>".static_text('more')."...</a></p>": "")."
	</div><!--news-->
	".($javascript? "
	<script type='text/javascript'>
		function toggle_$id(heading) {
			$('div#$id table').toggle();
			if (/\[[+]\]/.test(heading.innerHTML)) {
				heading.innerHTML = heading.innerHTML.replace(/\[[+]\]/,'[-]');
			} else {
				heading.innerHTML = heading.innerHTML.replace(/\[[-]\]/,'[+]');
			}
		}

		".($start_minimized? "$('div#$id table').hide()": "")."
		$('div#$id h1').bind('click', function() {
			toggle_$id(this);
		});
		$('div#$id h1 a').removeAttr('href'); // don't go to the link target
	</script>
	": "");

	return $news;
}

/**
 * @param int $item_count
 * @param string $main_land if given, show news of the given land only.
 * @param mixed $link_from_heading either a number greater than item_count, or the string "world" to link to the world page.
 */
function news($item_count, $main_land=NULL, $link_from_heading='world', $start_minimized=FALSE, $javascript=TRUE, $more=FALSE) {
	global $base_for_links;
	$world = "טקסטיה";
	$world_encoded = htmlspecialchars($world);
	$world_argument_encoded = urlencode(str_replace(' ','-',htmlspecialchars("משחק:$world")));

	if ($main_land) {
		$main_land_encoded = htmlspecialchars($main_land);
		$main_land_argument_encoded = urlencode(str_replace(' ','-',htmlspecialchars("משחק:$world/$main_land")));
		$land_condition = "land=".quote_all($main_land);
		$main_land_url = "$base_for_links/land.php?title=$main_land_argument_encoded";
	} else {
		$main_land_encoded = '';
		$main_land_argument_encoded = '';
		$land_condition = "1";
		$main_land_url = "$base_for_links/world.php?title=$world_argument_encoded";
	}

	if ($link_from_heading=='world') {
		$link_to_world = $main_land_encoded?
			"<a href='$main_land_url'>$world_encoded - $main_land_encoded</a>": 
			"<a href='$main_land_url'>$world_encoded</a>";
		$heading = static_text("news",NULL, $link_to_world);
	} else {
		$link_to_news = $main_land_encoded? 
			"<a href='$base_for_links/news.php?count=$link_from_heading&amp;land=$main_land_encoded'>".static_text("news",NULL, "$world_encoded - $main_land_encoded")."</a>": 
			"<a href='$base_for_links/news.php?count=$link_from_heading'>".static_text("news",NULL, $world_encoded)."</a>";
		$heading = "$link_to_news";
	}

	$rows = sql_query_or_die("
		SELECT user_news.*, users.*, TIME_TO_SEC(TIMEDIFF(NOW(),happened_at)) AS seconds_before_now
		FROM user_news
		INNER JOIN users ON(users.id=user_news.userid)
		WHERE $land_condition
		ORDER BY happened_at DESC
		LIMIT $item_count
		");
	$news = '';
	while ($row = sql_fetch_assoc($rows)) {
		$news .= "<tr>";
	
		$link_to_user = user_image_with_link($row);
	
		if ($main_land) { // no need to show link to land
			$land_encoded = $main_land_encoded;
			$land_argument_encoded = $main_land_argument_encoded;
			$link_to_land = "";
		} else {
			$land_encoded = htmlspecialchars("$row[land]");
			$land_argument_encoded = urlencode(str_replace(' ','-',htmlspecialchars("משחק:$world/$row[land]")));
			$link_to_land = " ב<a href='$base_for_links/land.php?title=$land_argument_encoded'>$land_encoded</a>";
		}

		$city_encoded = htmlspecialchars($row['city']);
		$city_argument_encoded = urlencode($city_encoded);
		$link_to_city = "<a href='$base_for_links/city.php?title=$city_argument_encoded&amp;land=$land_argument_encoded'>$city_encoded</a>";
	
		$seconds = $row['seconds_before_now'];
		if ($seconds<60) {
			$number = $seconds;
			$units  = "שניות";
		} elseif ($seconds<60*60) {
			$number = round($seconds/60);
			$units  = "דקות";
		} elseif ($seconds<60*60*24) {
			$number = round($seconds/60/60);
			$units  = "שעות";
		} else {
			$number = round($seconds/60/60/24);
			$units  = "ימים";
		}
		$time_string = "לפני $number $units";

		$news_description = static_text(
			$row['type'], /*gender=*/NULL, 
			$row['name'], $row['parameter'], "'$link_to_city'$link_to_land");
	
		$news .= "
		<td class='user'>
		$link_to_user
		</td>
		<td class='text'>
		$news_description<br/><div class='time'>($time_string)</div>
		</td>
		";
		$news .= "</tr>\n";
	}
	sql_free_result($rows);


	return news_box('news', $news, $heading, $start_minimized, $javascript, $more? $main_land_url: NULL);
}





/**
 * @param string $main_land if given, show news of the given land only.
 * @param mixed $link_from_heading either a number greater than item_count, or the string "world" to link to the world page.
 */
function leaders($main_land=NULL, $link_from_heading='world', $start_minimized=FALSE, $javascript=TRUE) {
	global $base_for_links;
	$world = "טקסטיה";
	$world_encoded = htmlspecialchars($world);

	if ($main_land) {
		$main_land_encoded = htmlspecialchars($main_land);
		$main_land_argument_encoded = urlencode(str_replace(' ','-',htmlspecialchars("משחק:$world/$main_land")));
		$land_condition = "land=".quote_all($main_land);
		$leaders_table = "land_leaders";
	} else {
		$main_land_encoded = '';
		$main_land_argument_encoded = '';
		$land_condition = "1";
		$leaders_table = "world_leaders";
	}

	if ($link_from_heading=='world') {
		$world_argument_encoded = urlencode(str_replace(' ','-',htmlspecialchars("משחק:$world")));
		$link_to_world = $main_land_encoded? 
			"<a href='$base_for_links/land.php?title=$main_land_argument_encoded'>$main_land_encoded</a>": 
			"<a href='$base_for_links/world.php?title=$world_argument_encoded'>$world_encoded</a>";
		$heading = static_text("leaders",NULL, $link_to_world);
	} else {
		$link_to_news = $main_land_encoded? 
			"<a href='$base_for_links/news.php?to=leaders&land=$main_land_encoded'>".static_text("leaders",NULL, $main_land_encoded)."</a>": 
			"<a href='$base_for_links/news.php?to=leaders'>".static_text("leaders",NULL, $world_encoded)."</a>";
		$heading = "$link_to_news";
	}

	$rows = sql_query_or_die("
		SELECT $leaders_table.*, users.*
		FROM $leaders_table
		INNER JOIN users ON(users.id=$leaders_table.userid)
		WHERE $land_condition
		ORDER BY domain
		");
	$news = '';
	while ($row = sql_fetch_assoc($rows)) {
		$news .= "<tr>";

		$link_to_user = user_image_with_link($row);

		$domain=$row['domain'];
		$domain_text = static_text($domain);
		$leaders_link_tooltip = static_text('leaders link tooltip',NULL,$domain_text);
		$news_description = static_text('has the most', NULL, 
			"<a href='$base_for_links/leaders.php?domain=$domain&amp;land=$main_land_argument_encoded' title='$leaders_link_tooltip'>$domain_text</a>");

		$count_string = $row['count'];

		$news .= "
		<td class='user'>
		$link_to_user
		</td>
		<td class='text'>
		$news_description <span class='time'>($count_string)</span>
		</td>
		";
		$news .= "</tr>\n";
	}
	sql_free_result($rows);

	return news_box('leaders', $news, $heading, $start_minimized, $javascript, /*$more=*/NULL);
}



function news_and_leaders($item_count, $main_land=NULL, $link_from_heading='world', $start_minimized=FALSE, $javascript=TRUE) {
	return 
	news($item_count, $main_land, $link_from_heading, $start_minimized, $javascript).
	leaders(          $main_land, $link_from_heading, $start_minimized, $javascript);
}



if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	$format = coalesce($_GET['format'],'html');
	$main_land = coalesce($_GET['land'],NULL);
	$item_count = min(20,coalesce($_GET['count'],10));
	if ($format==='taconite') {
		require_once("GLOBALS[SCRIPTFOLDER]/taconite.php");
		print jquery_taconite_header($HTML_ENCODING)."
		<replaceContent select=\"#whatsnew_textia_news\">
		".news($item_count,$main_land,"world",/*$start_minimized=*/FALSE,/*javascript=*/FALSE,/*more=*/TRUE)."
		</replaceContent>
		<replaceContent select=\"#whatsnew_textia_leaders\">
		".leaders($main_land,"world",/*$start_minimized=*/FALSE,/*javascript=*/FALSE)."
		</replaceContent>
		".jquery_taconite_footer();
	} else {
		show_html_header(static_text('news'));
		print news_and_leaders(
			$item_count,
			$main_land,
			"world",
			/*$start_minimized=*/FALSE,
			/*javascript=*/TRUE);
		show_html_footer();
	}
}

?>