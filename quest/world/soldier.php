<?php
/** קידוד אחיד
 * @file soldier.php - Define a Soldier that defends a City inside a Land in the World of Textia.
 * @author Erel Segal http://tora.us.fm
 * @date 2009-04-14
 * @copyright GPL 
 */
require_once('game.php');
require_once(dirname(__FILE__).'/QuestionGeneratorFromString.php');

class Soldier {
	var $id;

	var $land_quoted;
	var $city_quoted;
	var $soldier_condition;

	var $title_for_display;
	var $question_generator;

	/**
	 * @param int $id personal number of the soldier in the city.
	 */
	function __construct ($id) {
		global $land, $city;
		$this->id = $id;
		$data = $city->soldiers[$id];

		$this->title_for_display = $data["name"];
		$question_type = $data["question_type"];

		$this->land_quoted = quote_smart($land->title_for_display);
		$this->city_quoted = quote_smart($city->title_for_display);
		$this->soldier_condition = "land={$this->land_quoted} AND city={$this->city_quoted} AND soldier={$this->id}";

		list($this->question_generator, $_SESSION["Soldier_require"]) = 
			QuestionGeneratorFromString($question_type, $city);
	}

	function show_question() {
		global $practice;

		list($question,$_SESSION['answer'],$_SESSION['answer_details'],$all_answers) = $this->question_generator->question_and_answer();

		require_once("$GLOBALS[SCRIPTFOLDER]/forms.php");
		$all_answers_form = ($all_answers=='NO_YES'?
			yes_no_form('answer', array("לא","כן") ):
			"<form action='' method='post'>
			" . form_row_callback('select', '', $all_answers, 'answer') . "
			<input type='submit' name='submit_button' value='שלח!' />
			</form>
			");
		if ($practice) // don't show soldiers in practice mode
			print "
				$question
				$all_answers_form
				";
		else // real game - show soldiers
			print "
				".$this->title_for_display." שואל: 
				<blockquote>
					$question
				</blockquote>
				$all_answers_form
			";
	}

	/**
	 * calculate the loyalties of this soldier to all users
	 */
	function get_loyalties() {
		global $current_userid, $current_userid_quoted;
		$this->loyalties = sql_evaluate_array_key_value("
			SELECT userid, loyalty
			FROM user_soldier_loyalty
			WHERE {$this->soldier_condition}
			ORDER BY loyalty DESC
			");
		$this->userid_with_leadership = sql_evaluate("
			SELECT userid
			FROM user_soldier
			WHERE {$this->soldier_condition}
			");
		/*
		print "<pre dir='ltr'>";
		var_dump($this->soldier_condition);
		var_dump($this->loyalties);
		print "</pre>";
		*/
	}

	function userid_with_max_loyalty() {
		global $current_userid;
		$max_userid=NULL;
		$max_loyalty=0;
		foreach ($this->loyalties as $userid=>$loyalty) {
			if ($max_loyalty<$loyalty || ($loyalty && $max_loyalty==$loyalty&&$userid!=$current_userid)) {
				$max_loyalty=$loyalty;
				$max_userid=$userid;
			}
		}
		return array($max_userid,$max_loyalty);
	}

	/**
	 * Set the loyalty-level of this soldier to the current user
	 * @param int $new_loyalty
	 * @return TRUE if loyalty of soldier changed, FALSE if not
	 */
	function set_loyalty($new_loyalty) {
		global $current_userid, $current_userid_quoted, $current_time_quoted, $land;

		//$GLOBALS['DEBUG_QUERY_TIMES']=true;
		sql_query_or_die(sql_update_or_insert_query("user_soldier_loyalty",
			array("land"=>$this->land_quoted, "city"=>$this->city_quoted, "soldier"=>$this->id, "userid"=>$current_userid_quoted),
			array("loyalty"=>$new_loyalty, "updated_at"=>$current_time_quoted)));

		$max_userid_before = $this->userid_with_leadership;
		$this->loyalties[$current_userid] = $new_loyalty;
		list($max_userid_after,$max_loyalty_after) = $this->userid_with_max_loyalty();

		if ($max_userid_before!=$max_userid_after || $new_loyalty%10==0) {
			calculate_leader('loyalty', $land->title_for_display); // calculate here to save time
		}

		if ($max_userid_before!=$max_userid_after) {
			$max_userid_after_quoted = quote_all($max_userid_after); // leading zeros!
			sql_query_or_die("
				REPLACE INTO user_soldier (land, city, soldier, userid)
				VALUES ({$this->land_quoted}, {$this->city_quoted}, {$this->id}, $max_userid_after_quoted)
				");
			calculate_current_user_stats('soldiers');
			calculate_leader('soldiers', $land->title_for_display);
			return TRUE;
		}
		return FALSE;
	}

	function show_loyalty() {
		global $current_userid, $practice;
		$current_loyalty = coalesce($this->loyalties[$current_userid],0);

		if ($practice) { // show results only for current user
			if ($current_loyalty)
				print "<p class='loyalty'>ענית כבר $current_loyalty תשובות נכונות ברצף.</p>\n";
		} else { // real game - show results for current user and max loyalty user
			list($max_userid,$max_loyalty) = $this->userid_with_max_loyalty();
			if ($max_userid) {
				$owner_name = ($max_userid==$current_userid?
					"YOU":
					sql_evaluate("SELECT name FROM users WHERE id=".quote_all($max_userid),"$max_userid")); // leading zeros
				$loyalty_string = soldier_loyalty_string($owner_name, $max_loyalty, $current_loyalty);
				print "<p class='loyalty'>{$this->title_for_display} $loyalty_string.</p>\n";
			}
		}
	}

	function check_answer($users_answer, $expected_answer, $expected_answer_details) {
		global $current_userid, $city, $practice;
		$options=array();
		$next = NULL;
		$current_loyalty = $current_userid? (int)coalesce($this->loyalties[$current_userid],0): 0;
		if ($users_answer==$expected_answer) {
			$next = $city;
			$response = "צדקת";

			if ($current_userid) {
				//$city->set_ruler_test();

				$loyalty_changed = $this->set_loyalty($current_loyalty+1);
				if ($loyalty_changed) {
					$options[] = "כל הכבוד, אני עובר לצדך!";

					$ruler_changed = $city->set_ruler();
					if ($ruler_changed) {
						$options[] = "כבשת את העיר!";
						$options[] = "<a href='land.php'>חזרה לארץ</a>";
					}
				} else {
					//$options[] = "<a href='soldier.php'>עוד שאלה</a>"; Do this automatically
					$options[] = "<a href='city.php'>חזרה לעיר</a>";
					$next = $this;
				}
			} else {
				$options[] = "אבל התוצאות לא נרשמות כי לא התחברת. אפשר להתחבר בקישור התחברות בראש העמוד.";
				$options[] = "<a href='city.php'>חזרה לעיר</a>";
				$next = $this;
			}
		} else {
			$response = "טעית";

			if ($current_userid) {
				$this->set_loyalty(max(0,floor($current_loyalty/2)-1));
			}

			$options[] = "<a href='land.php'>לצאת מהעיר מייד!</a>";
		}

		if ($practice) // in practice mode, show only the evaluation right/wrong, no soldiers
			print "
				<p>$response <span class='answer_details'>($expected_answer_details)</span>!</p>
			";
		else
			print "
				".$this->title_for_display." אומר: 
				<blockquote>
					<p>$response <span class='answer_details'>($expected_answer_details)</span>!</p>
					<p>".implode("</p><p>",$options)."</p>
				</blockquote>
			";

		if ($practice) // in practice mode, always show another similar question
			$this->play();
		elseif ($next)
			// save the "back to city" click
			$next->play(/*$treasures_and_virtues=*/false);
	}

	function play() {
		global $world, $land, $city;

		$this->get_loyalties();
		if (count($_POST)==0||!isset($_SESSION['answer'])) {
			$this->show_loyalty();
			$this->show_question();
		} else {
			$correct_answer = $_SESSION['answer'];
			unset($_SESSION['answer']); // prevent duplicate scoring by clicking 'refresh'
			$this->check_answer($_POST['answer'], $correct_answer, $_SESSION['answer_details']);
		}
	}
}

if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	show_html_header('soldier',NULL,NULL, '<meta name="robots" content="noindex" />');
	require_once('world.php');
	$world = secnodary_object_from_get_or_session('World');
	require_once('land.php');
	$land = secnodary_object_from_get_or_session('Land');
	require_once('city.php');
	$city = secnodary_object_from_get_or_session('City');
	$soldier = object_from_get_or_session('Soldier');
	$practice = variable_from_get_or_session('practice', /*default=*/false);
	redirect_if_requested();

	print "<a class='back' href='world.php'>".($world->title_for_display? $world->title_for_display: 'העולם')."</a>";
	print "<div class='land'>";
	print "<a class='back' href='land.php'>".$land->title_for_display."</a>";
	print "<div class='city'>";
	print "<a class='back' href='city.php'>".$city->title_for_display."</a>";
	print "<div class='soldier'>";
	$soldier->play();
	print "
		<p class='details'><a href='city.php?refresh=1'>ריענון מאגר השאלות</a></p>
		</div><!--soldier-->
		</div><!--city-->
		</div><!--land-->
		";

	show_html_footer();
}

?>

