<?php
/** קידוד אחיד
 * @file article.php - define an Article that teaches a certain Virtue in the World of Textia.
 * @author Erel Segal http://tora.us.fm
 * @date 2009-07-12
 * @copyright GPL 
 */
require_once('game.php');

global $current_userid, $current_userid_quoted;
if (!isset($_GET['title'])) {
	user_error("No title given",E_USER_WARNING);
	return;
}
$title = str_replace("_"," ",$_GET['title']);


require_once('world.php');
$world = secnodary_object_from_get_or_session('World');
require_once('land.php');
$land = secnodary_object_from_get_or_session('Land');
require_once('city.php');
$city = secnodary_object_from_get_or_session('City');
require_once('virtuequest.php');
$virtuequest = secnodary_object_from_get_or_session('VirtueQuest');

$virtue_learned = NULL;
if (isset($_GET['virtue']) && $current_userid) {
	$virtue_learned = $_GET['virtue'];
	add_virtue_to_user($_GET['virtue'], $title);
	//$virtuequest->refresh_articles_already_leanred(); // useless - not written to session!
	//$city->refresh_articles_already_leanred();  // useless - not written to session!

	$virtuequest = refresh_object('VirtueQuest', $virtuequest->fresh_instance());
	$city = refresh_object('City', $city->fresh_instance());
}


show_html_header($title, 'article', $virtue_learned, '<meta name="robots" content="noindex" />');

print "<a class='back' href='world.php'>".($world->title_for_display? $world->title_for_display: 'העולם')."</a>";
print "<div class='land'>";
print "<a class='back' href='land.php'>".$land->title_for_display."</a>";
print "<div class='city'>";
print "<a class='back' href='city.php'>".$city->title_for_display."</a>";
print "<div class='article'>";
print "<a class='back' href='virtuequest.php'>".$virtuequest->title_for_display."</a>";

$refresh = !empty($_GET['refresh']);
$article_html = $GLOBALS['MediawikiClient']->page_cached($title, "wikisource_cache", "article_from_wikisource", $refresh, /*parsed=*/true);

$title_quoted = quote_all($title);
$virtues = sql_evaluate_array("SELECT DISTINCT virtue FROM city_virtue_article WHERE article=$title_quoted");

$virtues_already_learned_here = sql_evaluate_array_key_value("SELECT virtue,1 FROM user_article_virtue WHERE userid=$current_userid_quoted AND article=$title_quoted");

//$virtues_not_learned_yet = array_diff($virtues,$virtues_already_learned_here); // should be quite small - can use numeric arrays

$virtues_html = '';
foreach ($virtues as $virtue) {
	if (!$current_userid) {
		$tooltip = "למדת על $virtue אך התוצאות לא נשמרות כי אינך מחובר/ת";
		$virtues_html .= "
			<div class='virtue learned'>
			<a title='$tooltip'>".htmlspecialchars($virtue)."</a>
			</div>";
	} elseif (isset($virtues_already_learned_here[$virtue])) {
		$tooltip = "כבר למדת כאן $virtue!";
		$virtues_html .= "
			<div class='virtue learned'>
			<a title='$tooltip'>".htmlspecialchars($virtue)."</a>
			</div>";
	} else {
		$title_encoded = urlencode($title);
		$virtue_encoded = urlencode($virtue);
		$tooltip = "+1 $virtue";
		$virtues_html .= "
			<div class='virtue to_learn'>
			<a title='$tooltip' href='".htmlspecialchars("article.php?title=$title_encoded&virtue=$virtue_encoded")."'>
			<img src='../style/icons/lightbulb.gif' /><!--gif from http://commons.wikimedia.org/wiki/File:Lightbulb.gif by Alex43223-->
			".htmlspecialchars($virtue)."
			<img src='../style/icons/lightbulb.gif' /><!--gif from http://commons.wikimedia.org/wiki/File:Lightbulb.gif by Alex43223-->
			</a>
			</div>";
	}
}

print "
	$article_html
	$virtues_html
	<br style='clear:both' />
	";


print "
	<p class='details'><a target='_blank' href='".$GLOBALS['MediawikiClient']->link_by_title($title,"edit")."'>עריכת המאמר באתר ויקיטקסט</a> , <a href='?title=".htmlspecialchars(urlencode($title))."&amp;refresh=1'>ריענון</a></p>
	</div><!--article-->
	</div><!--city-->
	</div><!--land-->
	";
show_html_footer();


function article_from_wikisource($article, $client) {
	return preg_replace("/<!--.*?-->/s","",$article);
}

?>