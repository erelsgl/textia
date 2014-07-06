<?php
/** קידוד אחיד
 * @file world.php - defines the World of Textia - contains Lands that contain Cities that contain various adventures (Soldires, Virtue quests, Treasure hunts).
 * @author Erel Segal-Halevi http://tora.us.fm
 * @date 2009-04-13
 * @copyright GPL 
 */
require_once('game.php');

class World {
	var $title_in_wikisource;
	var $title_for_display;
	var $html;

	/**
   * Construct the object from the given title. Called when the "title" argument is passed in the URL.
	 * @param string $title title of a Wikisource page where game definition is kept
	 */
	function __construct ($title="") {
		$this->title_in_wikisource = $title;
		$this->title_for_display = title_for_display($title);
		if ($title) {
			$this->html = $GLOBALS['MediawikiClient']->page_cached($title, 
				"wikisource_cache", "world_definition_to_html", 
				/*$refresh=*/false, /*parsed=*/true);
		} else {
			$this->html = "";
		}
	}

	function play() {
		require_once('news.php');
		print "
		<div id='news_on_world_map'>
		".news_and_leaders(/*count=*/20, /*lsnd=*/NULL, /*count in link=*/20, /*$start_minimized=*/TRUE)."
		</div><!--news_on_world_map-->
		";
		print "<h1>".$this->title_for_display." <a class='help' href='".htmlspecialchars(article_url("משחק:טקסטיה/עזרה"))."' target='_blank'>(עזרה)</a></h1>";

		print $this->html;
		print "<div class='spacer'>&nbsp;</div>\n<p class='details'><a href='".$GLOBALS['MediawikiClient']->link_by_title($this->title_in_wikisource)."'>קוד המקור של המשחק באתר ויקיטקסט</a>, <a href='?refresh=1'>ריענון</a></p>";
	}


	/**
	 * Delete the cached data, so that it will be re-read from Wikisource
	 */
	function refresh() {
		sql_query_or_die("
			DELETE FROM wikisource_cache
			WHERE title=".quote_smart($this->title_in_wikisource)."
			");
	}

	function fresh_instance() {
		return new World($this->title_in_wikisource);
	}
}

function world_definition_to_html($definition, $client) {
	$definition = preg_replace("@href=[\"']/wiki/(.*?)[\"']@i", "href='land.php?title=$1'",$definition);
	$definition = preg_replace("@href=[\"']/w/index[.]php[?]title[=](.*?)[\"']@i", "href='land.php?title=$1'",$definition);
	return $definition;
}

if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	show_html_header('world');
	$world = object_from_get_or_session('World', /*default=*/"משחק:טקסטיה");
	$practice = variable_from_get_or_session('practice', /*default=*/false);
	redirect_if_requested();

	if (!empty($_GET['refresh'])) {
		$world->refresh();  // refresh the wikisource_cache
		$world = refresh_object('World', $world->fresh_instance()); // refresh the session object
	}

	$world->play();
	show_html_footer();
}

?>