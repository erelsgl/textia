<?php
/** קידוד אחיד
 * @file treasurehunt.php - looking for Treasures in a City inside a Land in the World of Textia.
 * @author Erel Segal http://tora.us.fm
 * @date 2009-06-26
 * @copyright GPL 
 */
require_once('game.php');

class TreasureHunt {
	var $land_quoted;
	var $city_quoted;

	var $treasure_name;
	var $word_count; // number of words to show after the current position 

	var $subsource_words;

	/**
	 * @param string $name name of the treasure.
	 */
	function __construct ($name, $word_count=10) {
		global $land, $city, $current_userid_quoted;
		$this->treasure_name = $name;

		$treasure_data = $land->treasure_data($name);
		if ($treasure_data) {
			$type = 'treasure';
		} else {
			$treasure_data = $city->virtue_data($name);
			$type = 'virtue';
		}
		if (!$treasure_data) {
			user_error("No data for treasure '$name'!",E_USER_WARNING);
			return;
		}
		$this->treasure_data = $treasure_data;
		$this->type = $type;

		$this->land_quoted = quote_all($land->title_for_display);
		$this->city_quoted = quote_all($city->title_for_display);

		$this->word_count = $word_count;


		$subsources_as_keys = $city->data("subsources");
		if (!$subsources_as_keys) {
			user_error("No subsources for '".$city->title_for_display."'!", E_USER_WARNING);
			die;
		}
		$subsources = array_keys($subsources_as_keys);

		$current_subsource = 0; // TODO: TEMPVER

		/* Read the text of the current subsource */
		$subsource_title = $subsources[$current_subsource];
		$subsource_text = $GLOBALS['MediawikiClient']->page_cached($subsource_title, "wikisource_plaintext_cache", 
			/*$compile_function (see script/html.php) =*/"clean_wikisource_tags", 
			/*$refresh=*/false, /*parsed=*/true);
		
		$this->subsource_words = preg_split("/\s+/",$subsource_text);
	}

	function position_indicator() {
		$inner_width_px = 600;

		$max_words = count($this->subsource_words);
		$first_px = floor($_SESSION['first_word_index']*$inner_width_px/$max_words);
		$position_px = $_SESSION['search_word_index']*$inner_width_px/$max_words;
		$last_px = floor(($max_words-$_SESSION['last_word_index'])*$inner_width_px/$max_words);

		$height_px = 20;

		$outside_color='#ccc';
		$inside_color='#0f8';

		$about_start = "תנועה לכיוון התחלת הטקסט";
		$about_end = "תנועה לכיוון סוף הטקסט";
		$about_outside = "התחום האפור הוא תחום שכבר לא מחפשים בו, בעקבות ההנחיות הקודמות שלך.";
		$about_inside_1 = "התחום הירוק הוא תחום שבו עדיין נשאר לחפש. מיקום המשלחת מסומן בקו.";
		$about_inside_2 = "התחום הירוק מצטמצם לפי ההנחיות שלך, עד שמוצאים את האוצר או עד שמגלים שהאוצר לא בתחום.";




		$arrow_size_px = 13;
		$outer_width_px = $inner_width_px + 2*$arrow_size_px;
	
		$before_position_px = floor(max(0,$position_px-$first_px-1));
		$after_position_px = floor(max(0,($inner_width_px-$position_px)-$last_px-1));
	
		$arrow_right = 'url("../style/icons/arrow_right.gif") no-repeat'." 100% 50%";
		$arrow_left  = 'url("../style/icons/arrow_left.gif") no-repeat'." 100% 50%";
	
		$pixel = "";//"<img src='../style/icons/pixel.gif' alt='pixel'/>";
	
		$height_css = "height:{$height_px}px";
		$border_css = 'border:solid 1px blue;';
	
		return "
	<div style='width:{$outer_width_px}px; $height_css'>
		<div style='position:relative; width:{$outer_width_px}px; $height_css' title=''>
			<a href='?direction=0' style='width:{$arrow_size_px}px; background:$arrow_right; float:right; $height_css;' title='$about_start'>$pixel</a>
			<span style='width:{$first_px}px; background:$outside_color; float:right; $height_css;' title='$about_outside'>$pixel</span>
			<span style='width:{$before_position_px}px; background:$inside_color; border-left:solid 1px black; float:right; text-align:left; $height_css' title='$about_inside_1'>$pixel</span>
			<span style='width:{$after_position_px}px; background:$inside_color; float:right; text-align:right; $height_css' title='$about_inside_2'>$pixel</span>
			<span style='width:{$last_px}px; background:$outside_color; float:right; $height_css' title='$about_outside'>$pixel</span>
			<a href='?direction=1' style='width:{$arrow_size_px}px; background:$arrow_left; float:right; $height_css;' title='$about_end'>$pixel</a>
		</div>
	</div>
		";
	}

	function search() {
		global $land, $city;
		/* Initialize the session, if needed */
		if (
			!isset($_SESSION['first_word_index'])||
			!isset($_SESSION['last_word_index'])||
			$_SESSION['first_word_index']<0||
			$_SESSION['last_word_index']<0||
			!empty($_GET['restart'])||
			0) {

			$_SESSION['first_word_index'] = 0;
			$_SESSION['last_word_index'] = count($this->subsource_words) - $this->word_count;
			$_SESSION['step_count'] = 1;
		}

		/* perform a search by choosing a random sentence in the subsource */
		$search_word_index = rand($_SESSION['first_word_index'], $_SESSION['last_word_index']);
		$_SESSION['search_word_index'] = $search_word_index;

		$search_words = array_slice($this->subsource_words, $search_word_index, $this->word_count);
		$search_sentence = implode(" ", $search_words);

		print "<p>"."המשלחת מצאה את המילים '".$search_sentence."'.</p>";
		
		$found=FALSE;
		if ($this->type=='treasure') {
			$regexp = $this->treasure_data['regexp'];
			if (preg_match("/$regexp/", $search_sentence)) {
				$treasure_image_html = treasure_image_html($this->treasure_data);
				print "<p>
				".$treasure_image_html."
				"."הללויה, מצאנו ".$this->treasure_data['name']." תוך ".$_SESSION['step_count']." ימי חיפוש!</p>
				";

				add_treasure_to_user($this->treasure_data['name'], $treasure_image_html);

				$found=TRUE;
			}
		} elseif ($this->type=='virtue') {
			$regexps = $this->treasure_data['regexps'];
			foreach ($regexps as $regexp=>$article) {
				if (preg_match("/$regexp/", $search_sentence)) {
					print "<p>ראש המשלחת אומר: 'אני רואה ".$this->treasure_data['name']." באופק! בואו אחריי! <small>(קראו עד הסוף)</small>'</p>
					";

					$link = htmlspecialchars(article_url($article));
					print "
					<div class='direction_form entrance_form'>
					<a href='$link'>כניסה</a>
					</div>";

					$found=TRUE;
					break;
				}
			}
		}

		if (!$found) {
			$_SESSION['step_count']++;
			$can_go_back = ($search_word_index<$_SESSION['last_word_index'] || $_SESSION['first_word_index']<$_SESSION['last_word_index']);
			$can_go_forward = ($search_word_index>$_SESSION['first_word_index']|| $_SESSION['first_word_index']<$_SESSION['last_word_index']);

			if ($can_go_back||$can_go_forward) {
				$question = "ראש המשלחת שואל: 'לאן להמשיך מכאן?'";
			} else {
				$question = "ראש המשלחת אומר: 'אין לנו לאן להמשיך - אנחנו חוזרים!'";
			}
			print "
				<p>$question</p>
				";
			print $this->position_indicator();
		}

		$about_regret = "אם התחרטת ונראה לך שהאוצר נמצא בתחום האפור - עליך להתחיל מחדש!";
		print "
		<div class='direction_form'>
		<a href='treasurehunt.php?restart=1' title='$about_regret'>התחל מחדש</a>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a href='city.php'>"."חזרה לעיר"."</a>
		</div>";
	}

	/**
	 * @param int $direction 0 to go back, 1 to go forward
	 */
	function change_direction($direction) {
		$fwi=$_SESSION['first_word_index'];
		$lwi=$_SESSION['last_word_index'];
		$swi=$_SESSION['search_word_index'];
		switch($direction) {
			case 0: $_SESSION['last_word_index'] = max($fwi,$swi-1); break;
			case 1: $_SESSION['first_word_index'] = min($lwi,$swi+1); break;
			default: user_error("unknown direction '$direction'",E_USER_WARNING);
		}
	}

	function play() {
		global $world, $land, $city;

		$about_play = "בכל יום של חיפושים, המשלחת מגיעה לנקודה כלשהי בטקסט ועליך להנחות אותה לאן להתקדם.";

		print "
			<h2>"."משלחת לחיפוש ".$this->treasure_data['name']." <a class='help' href='".htmlspecialchars(article_url("משחק:טקסטיה/אוצרות"))."' target='_blank'>(עזרה)</a></h2>
			";
		if (isset($_GET['direction'])) {
			$this->change_direction($_GET['direction']);
		}
		$this->search();
	}
}

if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	show_html_header('treasurehunt');
	require_once('world.php');
	$world = secnodary_object_from_get_or_session('World');
	require_once('land.php');
	$land = secnodary_object_from_get_or_session('Land');
	require_once('city.php');
	$city = secnodary_object_from_get_or_session('City');

	if ($city->is_ruled_by($current_userid_quoted)) {
		$treasurehunt = object_from_get_or_session('TreasureHunt');
	} else {
		$treasurehunt = NULL;
	}

	$practice = variable_from_get_or_session('practice', /*default=*/false);
	redirect_if_requested();

	print "<a class='back' href='world.php'>".($world->title_for_display? $world->title_for_display: 'העולם')."</a>";
	print "<div class='land'>";
	print "<a class='back' href='land.php'>".$land->title_for_display."</a>";
	print "<div class='city'>";
	print "<a class='back' href='city.php'>".$city->title_for_display."</a>";
	print "<div class='treasurehunt'>";

	if ($treasurehunt) {
		$treasurehunt->play();
	} else {
		print "<p>".static_text('only ruler can treasurehunt')."</p>";
	}
	print "
		</div><!--treasurehunt-->
		</div><!--city-->
		</div><!--land-->
		";

	show_html_footer();
}


?>
