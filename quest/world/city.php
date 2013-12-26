<?php
/**
 * @file city.php - define a structure of a City inside a Land in the World of Textia.
 * @see land.php::land_definition_to_cities() - the main function that converts the definition in Wikisource to our structures
 * @author Erel Segal http://tora.us.fm
 * @date 2009-04-14
 * @copyright GPL 
 */
require_once('game.php');

class City {
	var $title_for_display;
	var $soldiers;

	var $city_quoted;
	var $land_quoted;
	var $city_condition;

	var $articles_already_learned_here;

	/**
	 * @param string $title title of a Wikisource page where game definition is kept
	 */
	function __construct ($title) {
		global $land;
		$this->title_for_display = $title;

		$this->city_quoted = quote_smart($this->title_for_display);
		$this->land_quoted = quote_smart($land->title_for_display);
		$this->city_condition = "land={$this->land_quoted} AND city={$this->city_quoted}";

		if ($title) {
			$this->soldiers = $land->city_data($title, "soldiers");
		} else {
			$this->soldiers = array();
		}

		$this->refresh_articles_already_leanred();

		$this->require_required_file();
	}

	function refresh_articles_already_leanred() {
		global $current_userid_quoted;
		$this->articles_already_learned_here = sql_evaluate_array_key_value("
			SELECT user_article_virtue.virtue,count(*)
			FROM user_article_virtue
			INNER JOIN city_virtue_article ON(
				userid=$current_userid_quoted AND city={$this->city_quoted} AND user_article_virtue.virtue=city_virtue_article.virtue AND user_article_virtue.article=city_virtue_article.article)
			GROUP BY virtue
			");
	}

	function require_required_file() {
		$required_file = $this->data("required_file");
		if ($required_file)
			require_once($required_file);
	}

	function __wakeup() {
		$this->require_required_file();
	}

	function fresh_instance() {
		return new City($this->title_for_display);
	}

	/**
	 * @return the given property of this city.
	 */
	function data($key) {
		//return $this->data[$key];
		global $land;
		return $land->city_data($this->title_for_display, $key);
	}

	/**
	 * @return array('name', 'image', 'regexps') 
	 */
	function virtue_data($virtue) {
		$virtues = $this->data("virtues");
		return ($virtues? coalesce($virtues[$virtue],NULL): NULL);
	}

	function is_ruled_by($userid_quoted) {
		return sql_evaluate("
			SELECT COUNT(*)
			FROM user_city
			WHERE {$this->city_condition}
			AND userid=$userid_quoted
			");
	}

	/**
	 * calculate the loyalties of all soldiers of this city, and the ruler of this city.
	 */
	function get_ruler_data() {
		global $current_userid, $current_userid_quoted;
		$ruler_data = sql_evaluate_assoc("
			SELECT IF(userid=$current_userid_quoted,'YOU',users.name) name, users.thumbnail, users.id
			FROM user_city
			INNER JOIN users ON(userid=users.id)
			WHERE {$this->city_condition}
			",NULL);
		if ($ruler_data) {
			$this->ruler=$ruler_data["name"];
			$this->ruler_data = $ruler_data;
		} else {
			//user_error("No ruler data for city",E_USER_WARNING);
			$this->ruler='';
			$this->ruler_data = NULL;
		}
	}

	function loyalties_to_all_users() {
		global $current_userid_quoted;
		$loyalties = array();
		$rows = sql_query_or_die("
			SELECT soldier, IF(userid=$current_userid_quoted,'YOU',users.name) AS user, loyalty
			FROM user_soldier
			INNER JOIN user_soldier_loyalty USING(land, city, soldier, userid)
			INNER JOIN users ON(userid=users.id)
			WHERE {$this->city_condition}
			");
		while ($row=sql_fetch_assoc($rows)) {
			$loyalties[$row['soldier']]=$row;
		}
		sql_free_result($rows);
		return $loyalties;
	}

	function loyalties_to_current_user() {
		global $current_userid, $current_userid_quoted;
		return sql_evaluate_array_key_value("
			SELECT soldier, loyalty
			FROM user_soldier_loyalty
			INNER JOIN users ON(userid=users.id)
			WHERE {$this->city_condition}
			AND userid=$current_userid_quoted
			");
	}

	/**
	 * If all soldiers are loyal to the current user - set him as the ruler of this city. Otherwise, leave the ruler unchanged.
	 * @return TRUE if changed, FALSE if not
	 */
	function set_ruler() {
		global $current_userid, $current_userid_quoted, $current_time_quoted, $land;

		$loyal_soldier_count = sql_evaluate("
			SELECT COUNT(*)
			FROM user_soldier
			WHERE {$this->city_condition}
			AND userid=$current_userid_quoted
			");
		if ($loyal_soldier_count < count($this->soldiers))
			return FALSE; // not all soldiers convinced - no change.

		$current_ruler_userid = sql_evaluate("
			SELECT userid
			FROM user_city
			WHERE {$this->city_condition}
			",NULL);
		if ($current_ruler_userid==$current_userid)
			return FALSE; // current user is already the ruler - no change

		sql_query_or_die("
			REPLACE INTO user_city(land, city, userid)
			VALUES({$this->land_quoted}, {$this->city_quoted}, $current_userid_quoted)
			");
		calculate_current_user_stats('cities');
		calculate_leader('cities', $land->title_for_display);

		require_once('news_add.php');
		news_add($this->land_quoted, $this->city_quoted, 'user_conquered_city');
		news_add_facebook($this->land_quoted, $this->city_quoted, 'i_conquered_city');
		return TRUE;
	}

	function set_ruler_test() {
		require_once('news_add.php');
		//news_add($this->land_quoted, $this->city_quoted, 'user_conquered_city');
		news_add_facebook($this->land_quoted, $this->city_quoted, 'i_conquered_city');
	}

	function play_no_soldiers() {
		print "
		<p>"."
		נכנסת לעיר '".$this->title_for_display."'.
		עיר ללא חיילים - אין לך מה לחפש כאן!"."</p>
		<p>
		<a href='land.php'>"."בחזרה לארץ"."</a>
		</p>
		";
	}

	function play_practice() {
		$loyalties = $this->loyalties_to_current_user();
		print "
		<p>"."חידון על '".$this->title_for_display."' - יש לבחור סוג שאלה ורמת קושי:"."</p>
		";

		$question_types = array();
		$string = "";
		foreach ($this->soldiers as $id=>$data) {
			$loyalty=coalesce($loyalties[$id], 0);
			$loyalty_string = " - $loyalty תשובות נכונות ברצף";
			$soldier_question_type = $data["question_type"];
			if (mb_strlen($soldier_question_type)>50)
				$soldier_question_type=mb_substr($soldier_question_type,0,50)." ...";
			if (isset($question_types[$soldier_question_type]))
				continue; 	// no need for duplicates in practice mode
			$question_types[$soldier_question_type]=TRUE;
			$bookmark = (preg_match("/soldier/",$_SERVER['PHP_SELF'])? "&dummy=".rand()."#top": "#top");
			$string .= "<li><a href='soldier.php?title=".urlencode($id)."$bookmark'>$soldier_question_type</a>$loyalty_string</li>\n";
		}

		print "
		<div class='soldiers'>
			<ul>
				$string
			</ul>
		</div>
		";
	}

	function play_conquest() {
		$loyalties = $this->loyalties_to_all_users();
		$this->get_ruler_data();
		$city_image_html = city_image_html(coalesce($this->data("image"),NULL),$this->title_for_display);
		$ruler_image_html = city_ruler_image_html($this->ruler_data);
		print "
		$ruler_image_html
		$city_image_html
		<h2>העיר ".$this->title_for_display." <a class='help' href='".htmlspecialchars(article_url("משחק:טקסטיה/ערים"))."' target='_blank'>(עזרה)</a></h2>
		<p>
		העיר
		".city_ruler_string($this->ruler).".</p>
		<p>".count($this->soldiers)." חיילים שומרים על העיר.
		".city_ruler_explanation_string($this->ruler).":</p>
		</p>
		";

		$loyal_to_current_user="";
		$loyal_to_other_users="";
		foreach ($this->soldiers as $id=>$data) {
			$user_and_loyalty=coalesce($loyalties[$id], NULL);
			$loyalty_to_current_user = NULL; // We currently don't calculate the loyalty to the current user when it is not the maximum
			$loyalty_string = soldier_loyalty_string($user_and_loyalty['user'],$user_and_loyalty['loyalty'], $loyalty_to_current_user);

			$soldier_name = $data["name"];
			$bookmark = (preg_match("/soldier/",$_SERVER['PHP_SELF'])? "&dummy=".rand()."#top": "#top");
			$string = "<li><a href='soldier.php?title=".urlencode($id)."$bookmark'>$soldier_name</a>$loyalty_string</li>\n";

			if ($user_and_loyalty['user']=='YOU')
				$loyal_to_current_user.=$string;
			else
				$loyal_to_other_users.=$string;
		}
		print "
		<div class='soldiers'>
			<ul>
				$loyal_to_other_users
			</ul>
			<h2>חיילים שעברו לצד שלך</h2>
			<ul>
				$loyal_to_current_user
			</ul>
		</div>
		";
	}

	function play_treasures($treasures) {
		global $current_userid_quoted; 

		if ($this->data("אוצרות")=='לא'
		 ||!$this->data("subsources"))
			return; // no subsources - nowhere to look for treasures!

		print "
			<div class='treasures'>
			<h2>"."אוצרות"."</h2>
			";
		if ($this->ruler=='YOU') {
			$treasures_owned_html = '';
			$treasures_not_owned_html = '';

			$treasures_owned = sql_evaluate_array_key_value("
				SELECT treasure,1 
				FROM user_treasure
				WHERE userid=$current_userid_quoted
				AND land={$this->land_quoted}
				AND city={$this->city_quoted}
				");
			foreach ($treasures as $title=>$data) {
				$image = treasure_image_html($data);
				if (isset($treasures_owned[$title]))
					$treasures_owned_html .= "<a class='treasure'>$image</a>
						";
				else
					$treasures_not_owned_html .= "<a class='treasure' href='treasurehunt.php?title=$title&restart=1'>$image</a>
						";
			}
			if ($treasures_not_owned_html)
				$treasures_not_owned_html = "<p style='clear:both'>באפשרותך לשלוח משלחת לחיפוש אחד האוצרות בשטחים הנתונים לשליטתך:</p>
				$treasures_not_owned_html";
			if ($treasures_owned_html)
				$treasures_owned_html = "<p style='clear:both'>אוצרות שכבר מצאת כאן:</p>
				$treasures_owned_html";

			print "
				$treasures_not_owned_html
				$treasures_owned_html
				";
		} else {
			print "
			<p>".static_text('only ruler can treasurehunt')."</p>
				";
		}
		print "</div><!--treasures-->";
	}

	function play_virtues() {
		global $land, $current_userid_quoted; 

		$virtues = $this->data("virtues");
		if (!$virtues)
			return;

		print "
			<div class='virtues'>
			<h2>"."מידות טובות"."</h2>
			";

		$virtues_html = '';
		foreach ($virtues as $virtue_name=>$data) {
			$image = $virtue_name; //treasure_image_html($data);
			$virtues_html .= "<tr>
				<td><a class='treasure' href='".htmlspecialchars("virtuequest.php?title=$virtue_name&restart=1")."'>$image</a></td>
				<td>מצאת <b>".coalesce($this->articles_already_learned_here[$virtue_name],0)."</b> מתוך <b>".count($data['regexps'])."</b></td>
				";
		}
		if ($virtues_html)
			$virtues_html = "<p style='clear:both'>באפשרותך לצאת למסע לחיזוק מידות טובות באישיותך:</p>
			<table><tbody>
			$virtues_html
			</tbody></table>
			";
		print "
			$virtues_html
			</div><!--virtues-->
			";
	}

	function play($treasures_and_virtues=true) {
		global $land, $practice;

 		if (!$this->soldiers) {
			$this->play_no_soldiers();
		} elseif ($practice) {
			$this->play_practice();
		} else {
			$this->play_conquest();
			if ($treasures_and_virtues) {
				if ($land->has_treasures()) {
					$this->play_treasures($land->all_treasures());
				}
				$this->play_virtues();
			}
		}
		print "
				<div class='spacer'>&nbsp;</div>
			";
	}

	/**
	 * Delete the cached computation results for all sources in this city
	 */
	function refresh() {
		$question_chooser_serialized = $this->data("question_chooser_serialized");
		if ($question_chooser_serialized) {
			$question_chooser = unserialize($question_chooser_serialized);
			$question_chooser->create_questions();
			return $question_chooser->question_count();
		}
		else return 0;
	}
}

if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	show_html_header('city');
	require_once('world.php');
	$world = secnodary_object_from_get_or_session('World');
	require_once('land.php');
	$land = secnodary_object_from_get_or_session('Land');
	$city = object_from_get_or_session('City');
	$practice = variable_from_get_or_session('practice', /*default=*/false);
	redirect_if_requested();

	if (!empty($_GET['refresh'])) {
		if (empty($_GET['city_only'])) {
			$land->refresh();
			$land = refresh_object('Land', $land->fresh_instance());
		}

		$question_count = $city->refresh();
		$city = refresh_object('City', $city->fresh_instance());
	}

	print "<a class='back' href='world.php'>".($world->title_for_display? $world->title_for_display: 'העולם')."</a>";
	print "<div class='land'>";
	print "<a class='back' href='land.php'>".$land->title_for_display."</a>";
	print "<div class='city'>";
	$city->play();
	$question_count_string = isset($question_count)? "$question_count שאלות. ": "";
	print "
		<p class='details'>$question_count_string<a href='?refresh=1'>ריענון מאגר השאלות</a></p>
		</div><!--city-->
		</div><!--land-->
		";

	show_html_footer();
}
?>