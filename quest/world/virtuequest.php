<?php
/** קידוד אחיד
 * @file virtuequest.php - looking for Virtues in a City inside a Land in the World of Textia.
 * @author Erel Segal http://tora.us.fm
 * @date 2009-07-17
 * @copyright GPL 
 */
require_once('game.php');

class VirtueQuest {
	var $land_quoted;
	var $city_quoted;

	var $virtue_data;
	var $articles_already_learned_here;
	var $char_radius; // number of chars to show before and after the current position 

	var $subsource_text;
	var $subsource_char_count;

	var $can_go_back;
	var $can_go_forward;

	var $title_for_display;

	/**
	 * @param string $name name of the virtue.
	 */
	function __construct ($virtue_name, $char_radius=75) {
		global $land, $city, $current_userid_quoted;

		if (!$virtue_name) {
			$this->title_for_display = '';
			return;
		}
		$this->title_for_display = "משלחת לחיפוש $virtue_name";

		$virtue_data = $city->virtue_data($virtue_name);
		if (!$virtue_data) {
			user_error("No data for virtue '$virtue_name'!",E_USER_WARNING);
			return;
		}
		$this->virtue_data = $virtue_data;

		$this->land_quoted = quote_all($land->title_for_display);
		$this->city_quoted = quote_all($city->title_for_display);

		$this->char_radius = $char_radius;

		$this->refresh_articles_already_leanred();

		$subsources_as_keys = $city->data("subsources");
		if (!$subsources_as_keys) {
			user_error("No subsources for '".$city->title_for_display."'!", E_USER_WARNING);
			die;
		}

		$subsources_text = '';
		foreach ($subsources_as_keys as $subsource_title=>$data) {
			/* Read the text of the current subsource */
			$subsource_text = $GLOBALS['MediawikiClient']->page_cached($subsource_title, "wikisource_plaintext_cache", 
				/*$compile_function (see script/html.php) =*/"clean_wikisource_tags", 
				/*$refresh=*/false, /*parsed=*/true);
			$subsources_text .= " ".$subsource_text;
		}
		$this->subsource_text = $subsources_text;
		$this->subsource_char_count = strlen($subsources_text);
	}

	function fresh_instance() {
		return new VirtueQuest($this->virtue_data['name'], $this->char_radius);
	}

	function refresh_articles_already_leanred() {
		global $current_userid_quoted;
		$virtue_name_quoted = quote_smart($this->virtue_data['name']);
		$articles_quoted = implode(",",quote_smart_array(array_values($this->virtue_data['regexps'])));

		$this->articles_already_learned_here = sql_evaluate_array_key_value("
			SELECT article,1 
			FROM user_article_virtue 
			WHERE userid=$current_userid_quoted
			AND virtue=$virtue_name_quoted
			AND article IN ($articles_quoted)
			");
	}

	function position_indicator() {
		$inner_width_px = 600;

		$first_px = 0;
		$position_px = $_SESSION['current_char_index']*$inner_width_px/$this->subsource_char_count;
		$last_px = $inner_width_px;

		$height_px = 20;

		$outside_color='#ccc';
		$inside_color='#0f8';

		$about_start = "תנועה לכיוון התחלת הטקסט";
		$about_end = "תנועה לכיוון סוף הטקסט";
		$about_inside = "התחום הירוק הוא מפת העיר. מיקום המשלחת מסומן בקו.";

		$pixel = '';

		$arrow_size_px = 13;
		$outer_width_px = $inner_width_px + 2*$arrow_size_px;
	
		$before_position_px = $position_px-1;
		$after_position_px = $inner_width_px-$position_px-1;
	
		$arrow_right = 'url("../style/icons/arrow_right.gif") no-repeat'." 100% 50%";
		$arrow_left  = 'url("../style/icons/arrow_left.gif") no-repeat'." 100% 50%";
	
		$height_css = "height:{$height_px}px";
		$border_css = 'border:solid 1px blue;';

		return "
	<div style='width:{$outer_width_px}px; $height_css'>
		<div style='position:relative; width:{$outer_width_px}px; $height_css' title=''>
			<a href='?direction=0' style='width:{$arrow_size_px}px; background:$arrow_right; float:right; $height_css;' title='$about_start'>$pixel</a>
			<span style='width:{$before_position_px}px; background:$inside_color; border-left:solid 1px black; float:right; text-align:left; $height_css' title='$about_inside'>$pixel</span>
			<span style='width:{$after_position_px}px; background:$inside_color; float:right; text-align:right; $height_css' title='$about_inside'>$pixel</span>
			<a href='?direction=1' style='width:{$arrow_size_px}px; background:$arrow_left; float:right; $height_css;' title='$about_end'>$pixel</a>
		</div>
	</div>
		";

	}

	function search() {
		require("$GLOBALS[SCRIPTFOLDER]/string.php");
		global $land, $city, $current_userid_quoted;
		/* Initialize the session, if needed */
		if (
			!isset($_SESSION['current_phrase'])||
			!isset($_SESSION['current_char_index'])||
			!empty($_GET['restart'])||
			0) {
			$_SESSION['current_phrase'] = '';
			$_SESSION['current_char_index'] = 0;
		}

		$phrase=$_SESSION['current_phrase'];
		$found=FALSE;
		if ($phrase) {
			$search_sentence = substring_around($this->subsource_text, $_SESSION['current_char_index'], $this->char_radius);
	
			$search_sentence_html = str_replace($phrase, "<b>$phrase</b>", $search_sentence);

			print "<p>"."המשלחת מצאה את המילים '...".$search_sentence_html."...'.</p>";

			$regexps = $this->virtue_data['regexps'];
			foreach ($regexps as $regexp=>$article) {
				if (preg_match("/$regexp/", $search_sentence)) {
					
					$virtue_already_learned_here = isset($this->articles_already_learned_here[$article]);
		
					if (!$virtue_already_learned_here) {
						$announcement = "ראש המשלחת אומר: 'אני רואה ".$this->virtue_data['name']." באופק! בואו אחריי!' <small>(קראו עד הסוף)</small>
						";
						$found=TRUE;
					} else {
						$announcement = "ראש המשלחת אומר: 'אני רואה ".$this->virtue_data['name']." באופק, אבל כבר היית כאן (אפשר להיכנס שוב)'
						";
					}
					$link = htmlspecialchars(article_url($article));
					$entrance_html = "
					<p>$announcement</p>
					<div class='direction_form entrance_form'>
					<a href='$link'>כניסה</a>
					</div>";
					if ($found) {
						print $entrance_html;
						unset($entrance_html);
					}
					break;   // break even if not found - don't show double entries
				}
			}
		}
		if (!$found) {
			$new_phrase_form = "<form class='phrase' action='' method='get'>
						<input name='phrase' value='$phrase'/>
						<input type='submit' value='חפש'/>
					</form>
			";
			if (!$phrase) {
				$question="ראש המשלחת שואל: 'איזה ביטוי לחפש?'";
				print "
					<p>$question</p>
					$new_phrase_form
					";
			} else {
				$question = "ראש המשלחת שואל: 'לאן להמשיך מכאן?'";
				print "
					<p>$question</p>
					" . $this->position_indicator()."
					<p>ביטוי חדש:</p>
					$new_phrase_form
					";
			}
			if (isset($entrance_html))
				print $entrance_html; // not printed above if not found
		}

		$about_regret = "אם התחרטת ונראה לך שהאוצר נמצא בתחום האפור - עליך להתחיל מחדש!";
		print "
		<div class='direction_form'>
		<a href='virtuequest.php?restart=1' title='$about_regret'>התחל מחדש</a>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a href='city.php'>"."חזרה לעיר"."</a>
		</div>";
	}

	/**
	 * @param int $direction 0 to go back, 1 to go forward
	 */
	function change_direction($direction) {
		$phrase=$_SESSION['current_phrase'];
		$current_index = $_SESSION['current_char_index'];
		switch($direction) {
			case 0:
				$start_reverse_search_index = max(1-$this->subsource_char_count, $current_index - $this->char_radius - $this->subsource_char_count);
				$new_index = strrpos($this->subsource_text, $phrase, $start_reverse_search_index);  //"offset - May be specified to begin searching an arbitrary number of characters into the string. Negative values will stop searching at an arbitrary point prior to the end of the string."
			//print "<p>$new_index = strrpos(subsource_text, $phrase, $start_reverse_search_index);</p>\n";
			break;
			case 1:
				$start_search_index = min($this->subsource_char_count-1,$current_index+$this->char_radius);
				$new_index = strpos($this->subsource_text, $phrase, $start_search_index); 
				break;
			default: user_error("unknown direction '$direction'",E_USER_WARNING);
		}
		if ($new_index===false) {
			print "<p>ראש המשלחת אומר: 'לא מצאנו $phrase בכיוון זה! אולי ננסה ביטוי חדש?'</p>";
			switch($direction) {
				case 0: $this->can_go_back=false; break;
				case 1: $this->can_go_forward=false; break;
			}
		} else {
			$_SESSION['current_char_index'] = $new_index;
		}
	}

	/**
	 * @param int $direction 0 to go back, 1 to go forward
	 */
	function change_phrase($phrase) {
		if (!$phrase) {
			user_error("Empty phrase", E_USER_WARNING);
			return;
		}
		$_SESSION['current_phrase']=$phrase;
		$new_index=strpos($this->subsource_text, $phrase, 0);
		if ($new_index===false) {
			print "<p>ראש המשלחת אומר: 'לא מצאנו $phrase בכל העיר!'</p>";
			$_SESSION['current_phrase']='';
			$this->can_go_back=false;
			$this->can_go_forward=false;
		} else {
			$_SESSION['current_char_index'] = $new_index;
		}
	}

	function play() {
		global $world, $land, $city;
	
		$total_count_in_city = count($this->virtue_data['regexps']);
		$user_count_in_city =  count($this->virtue_data['regexps']);
		$virtue_name = $this->virtue_data['name'];
		print "
			<h2>"."משלחת לחיפוש $virtue_name <a class='help' href='".htmlspecialchars(article_url("משחק:טקסטיה/מידות"))."' target='_blank'>(עזרה)</a></h2>
			<p>תושבי העיר אומרים: 'אפשר למצוא כאן <b>$total_count_in_city</b> יחידות של $virtue_name', מתוכן מצאת כבר <b>".count($this->articles_already_learned_here)."</b>.</p>
			";
		$this->can_go_back=$this->can_go_forward=TRUE;
		if (!empty($_GET['phrase'])) {
			$this->change_phrase($_GET['phrase']);
		}
		if (!empty($_GET['direction'])) {
			$this->change_direction($_GET['direction']);
		}
		$this->search();
	}
}

if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	show_html_header('virtuequest');
	require_once('world.php');
	$world = secnodary_object_from_get_or_session('World');
	require_once('land.php');
	$land = secnodary_object_from_get_or_session('Land');
	require_once('city.php');
	$city = secnodary_object_from_get_or_session('City');
	$virtuequest = object_from_get_or_session('VirtueQuest');
	if (!empty($_GET['refresh'])) {
		$virtuequest = refresh_object('VirtueQuest', $virtuequest->fresh_instance());
	}

	$practice = variable_from_get_or_session('practice', /*default=*/false);
	redirect_if_requested();

	print "<a class='back' href='world.php'>".($world->title_for_display? $world->title_for_display: 'העולם')."</a>";
	print "<div class='land'>";
	print "<a class='back' href='land.php'>".$land->title_for_display."</a>";
	print "<div class='city'>";
	print "<a class='back' href='city.php'>".$city->title_for_display."</a>";
	print "<div class='treasurehunt'>";
	$virtuequest->play();
	print "
		</div><!--treasurehunt-->
		</div><!--city-->
		</div><!--land-->
		";

	show_html_footer();
}

/* Renaming a virtue:

UPDATE city_virtue_article
SET virtue='זריזות'
WHERE virtue='חריצות'
;

UPDATE user_article_virtue
SET virtue='זריזות'
WHERE virtue='חריצות'
;

DELETE FROM virtue_count
WHERE virtue='חריצות'
;

DELETE FROM virtue_data
WHERE name='חריצות'
;


*/
?>
