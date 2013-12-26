<?php
/** קידוד אחיד
 * @file land.php - Define a structure of a Land in the World of Textia
 * @see land_definition_to_cities() - the main function that converts the definition in Wikisource to our structures
 * @author Erel Segal http://tora.us.fm
 * @date 2009-04-13
 * @copyright GPL 
 */
require_once('game.php');

class Land {
	var $title_in_wikisource;
	var $title_for_display;
	var $cities;

	/**
	 * @param string $title title of a Wikisource page where game definition is kept
	 */
	function __construct ($title) {
		$this->title_in_wikisource = $title;
		$this->title_for_display = title_for_display($title);
		$this->land_condition = "land=".quote_smart($this->title_for_display);
		if ($title) {
			$this->cities = $GLOBALS['MediawikiClient']->page_cached(
				$title, 
				/*table=*/ "wikisource_cache", 
				/*function=*/"land_definition_to_cities", 
				/*$refresh=*/false, 
				/*parsed=*/false);
		} else {
			$this->cities = array();
		}
	}

	/**
	 * @return the given property of the given city, or NULL if no such property
	 */
	function city_data($city, $key) {
		return coalesce($this->cities[$city][$key],NULL);
	}

	function random_city_title() {
		return array_rand($this->cities);
	}

	function all_city_titles() {
		return array_keys($this->cities);
	}

	function has_treasures() {
		return !empty($this->cities['אוצרות']);
	}

	function all_treasures() {
		return $this->cities['אוצרות'];
	}

	function in_construction() {
		return !empty($this->cities['בעבודה']);
	}

	function treasure_data($treasure_name) {
		$treasures = $this->all_treasures();
		return coalesce($treasures[$treasure_name],NULL);
	}

	/**
	 * calculate the rulers of all cities in this land
	 */
	function get_rulers() {
		global $current_userid, $current_userid_quoted;
		$rows = sql_query_or_die("
 			SELECT city, IF(userid=$current_userid_quoted,'YOU',users.name) AS name, users.thumbnail, users.id
			FROM user_city
			INNER JOIN users ON(userid=users.id)
			WHERE {$this->land_condition}
			");
		$rulers = array();
		while ($row = sql_fetch_assoc($rows)) {
			$rulers[$row['city']] = $row; // name, thumbnail, id
		}
		$this->rulers = $rulers;
		//var_dump($this->rulers);
	}

	function play() {
		global $world, $current_userid, $practice;

		require_once('news.php');
		print "
		<div id='news_on_world_map'>
		".news_and_leaders(/*count=*/20, /*lsnd=*/$this->title_for_display, /*count in link=*/20, /*$start_minimized=*/TRUE)."
		</div><!--news_on_world_map-->
		";

		print "<h1>".$this->title_for_display." <a class='help' href='".htmlspecialchars(article_url("משחק:טקסטיה/עזרה"))."' target='_blank'>(עזרה)</a></h1>
			
		";

		if ($this->in_construction()) {
			print "<p class='in_construction'>".
				static_text('in_construction', NULL, $GLOBALS['MediawikiClient']->link_by_title($this->title_in_wikisource)).
				"</p>";
		}

		if ($practice) {
			print "
			<p>בחירת נושא:</p>";
			print "
			<ul>
			";
			foreach ($this->cities as $city=>$data) {
				if (empty($data['soldiers']))
					continue;
				print "
				<li>
					<a href='city.php?title=".urlencode($city)."#top'>$city</a>
				</li>
				";
			}
			print "
			</ul>
			";
		} else {
			$this->get_rulers();
			foreach ($this->cities as $city=>$data) {
				if (empty($data['soldiers']))
					continue;
				$ruler_string = city_ruler_string(isset($this->rulers[$city])? $this->rulers[$city]["name"]: NULL);
				$ruler_image_html = city_ruler_image_html(coalesce($this->rulers[$city],NULL));
				$city_image_html = city_image_html($data["image"], $city);
				print "
				<div class='city_in_land'>
					$ruler_image_html
					<a href='city.php?title=".urlencode($city)."#top'>
						$city_image_html
						$city</a>$ruler_string
				</div>
				";
			}
			print "
				<div class='spacer'>&nbsp;</div>
				<p class='details'><a href='".$GLOBALS['MediawikiClient']->link_by_title($this->title_in_wikisource)."'>קוד המקור של המשחק באתר ויקיטקסט</a> , <a href='?refresh=1'>ריענון</a></p>
				";
		}
	}


	/**
	 * Delete the cached computation results for all sources in this land
	 */
	function refresh() {
		sql_query_or_die("
			DELETE FROM wikisource_cache
			WHERE title=".quote_smart($this->title_in_wikisource)."
			");
	}

	function fresh_instance() {
		return new Land($this->title_in_wikisource);
	}

}

/**
 * Convert the land definition in Wikisource to a structure of cities in our world
 */
function land_definition_to_cities($definition, $client) {
	$rows = explode("\n",$definition);

	$current_city = $current_virtue = "אחר";

	$cities = $treasures = $constants = $virtues = array();

	require_once("$GLOBALS[SCRIPTFOLDER]/sql_delayed_insert.php");
	$treasure_data_query = new SqlDelayedInsertQuery("REPLACE INTO treasure_data");
	$virtue_data_query = new SqlDelayedInsertQuery("REPLACE INTO virtue_data");
	$city_virtue_article_query = new SqlDelayedInsertQuery("REPLACE INTO city_virtue_article");

	/* Read all cities */
	foreach ($rows as $row) {

		/* Replace constants */
		foreach ($constants as $constant_name=>$constant_value) {
			$row = str_replace($constant_name,$constant_value,$row);
		}

		$city_text = "עיר";
		$treasure_text = "אוצר";
		$virtue_text = "מידה";

		if (preg_match("/^\s*==\s*(.*?)\s*==\s*$/",$row,$matches)||
            preg_match("/{{.*?$city_text\|([^|{}]+)}}/",$row,$matches)) {
			// New city:
			$current_city = $matches[1];
			if (!isset($cities[$current_city]) && $current_city!='קבועים') {
				$cities[$current_city] = array(
					"image"=>NULL,
					"subsources"=>array(),
					"soldiers"=>array(),
					);
			}
			$current_virtue = ''; // virtues are specific for a city
		} elseif (preg_match("/{{.*?$treasure_text\|([^|{}]+)\|([^|{}]+)}}\s*(.*)$/",$row,$matches)) {
			// treasures are the same for all cities
			list($dummy, $treasure_data['image'], $treasure_data['name'], $treasure_data['regexp'])=$matches;
			$treasures[$treasure_data['name']]=$treasure_data;
			$treasure_data_query->add(
				quote_all($treasure_data['name']).",". quote_all($treasure_data['image']));
		} elseif (preg_match("/{{.*?$virtue_text\|([^|{}]*)\|([^|{}]+)}}\s*(.*)?$/",$row,$matches)) {
			// virtues are specific for a city
			list($dummy, $virtue_data['image'], $virtue_data['name'], $default_regexp)=$matches;
			$virtue_data['regexps']=array();
			//$virtue_data['regexps'][$default_regexp] = 'default'; // no default article yet!
			$current_virtue = $virtue_data['name'];
			$cities[$current_city]["virtues"][$current_virtue] = $virtue_data;
			$virtue_data_query->add(
				quote_all($virtue_data['name']).",". quote_all($virtue_data['image']));
			$virtues[$current_virtue]=TRUE; // for the count update
		} elseif (preg_match("/^[:]\[\[(Image|File|תמונה|קובץ)[:](.*?)(\|.+)?\]\]$/",$row,$matches)) {
			$current_image = $matches[2];
			$cities[$current_city]["image"]=$current_image;
		} elseif (preg_match("/^[:]\[\[(.*)\]\]$/",$row,$matches)) {
			// New subsource - add to current city:
			$current_subsource = $matches[1];
			$cities[$current_city]["subsources"][$current_subsource]=TRUE;
		} elseif ($current_virtue && preg_match("/^[:][:](.*?)\s*[=]\s*\[\[(.*?)\]\]$/",$row,$matches)) {
			// New article - add to current virtue:
			$current_regexp = $matches[1];
			$current_article   = $matches[2];
			$cities[$current_city]["virtues"][$current_virtue]["regexps"][$current_regexp] = $current_article;
			$city_virtue_article_query->add(
				quote_all($current_city).",".
				quote_all($current_virtue).",".
				quote_all($current_article).",".
				quote_all($current_regexp)
				);
		} elseif (preg_match("/^[:][:](.*?)\s*([+]?=)\s*(.*?)$/",$row,$matches)) {
			// New custom parameter - add to current city:
			$current_param_name = $matches[1];
			$operator = $matches[2];
			$current_param_value = $matches[3];
			if ($current_city=='קבועים') {
				$constants[$current_param_name] = $current_param_value;
			} else {
				if ($operator=='+=') { // add to current value
					if (!isset($cities[$current_city][$current_param_name]))
						$cities[$current_city][$current_param_name] = array();
					$cities[$current_city][$current_param_name][]=$current_param_value;
				} else { // replace current value
					$cities[$current_city][$current_param_name] = $current_param_value;
				}
			}
		}  elseif (preg_match("/^[:](.*)$/",$row,$matches)) {
			// New soldier - add to current city:
			$current_soldier = $matches[1];

			if (preg_match("/^(.*?)\s*\((.*)\)\s*$/",$current_soldier,$matches)) {
				$current_soldier_data = array(
					"name" => $matches[1],
					"question_type" => $matches[2]);
			} else {
				$current_soldier_data = array(
					"name" => $current_soldier,
					"question_type" => "האם כתוב 40");
			}

			$cities[$current_city]["soldiers"][]=$current_soldier_data;
		} elseif (preg_match("/{{בעבודה}}/",$row)) {
			$in_construction = TRUE;
		}
	}

	/* Insert treasures & virtues to tables */
	$treasure_data_query->commit();
	$virtue_data_query->commit();
	$city_virtue_article_query->commit();

	// Note: there may be virtues in other lands, so we must run the query after the update!
	if ($virtues)
		sql_query_or_die("
			REPLACE INTO virtue_count(virtue,count)
			SELECT virtue, COUNT(*)
			FROM city_virtue_article
			WHERE virtue IN (".implode(",",quote_smart_array(array_keys($virtues))).")
			GROUP BY virtue
			");

	/* Apply default values */
	if (isset($cities['ברירת מחדל'])) {
		$default_values = $cities['ברירת מחדל'];
		foreach ($cities as $city_name=>&$city_values) {
			if (isset($city_values['ללא ברירת מחדל'])) continue;
			foreach ($default_values as $key=>$value) {
				if (empty($city_values[$key])) {
					$city_values[$key]=$default_values[$key];
				} elseif (is_array($value)) {
					$city_values[$key]=array_merge($default_values[$key],$city_values[$key]);
				}
			}
		}
		unset($cities['ברירת מחדל']);
	}

	/* Pre-calculate indices, if needed */
	foreach ($cities as $city_name=>&$city_values) {
		if (isset($city_values['ביטויים'])) {
			$city_values["required_file"] = dirname(__FILE__)."/whatphrase.php";
			require_once($city_values["required_file"]);

			$phrases_arrays = WhatphraseQuestionGenerator::phrases_strings_to_phrases_arrays($city_values['ביטויים']);
			$question_chooser = new WhatphraseQuestionGenerator(array_keys($city_values["subsources"]), $phrases_arrays);

			if (!$question_chooser->question_count())
				$question_chooser->create_questions();

			$city_values["question_chooser_serialized"] = serialize($question_chooser);
		} elseif (isset($city_values['תבנית'])) {
			$city_values["required_file"] = dirname(__FILE__)."/whatemplate.php";
			require_once($city_values["required_file"]);

			$templates = $city_values['תבנית'];
			$question_chooser = new WhatemplateQuestionGenerator(array_keys($city_values["subsources"]), $templates);
				// Create a WhatemplateQuestionGenerator only to create questions - ignore question_prefix and word_count
				//	(they will be initialized later, in the Soldier constructor, by the function QuestionGeneratorFromString)

			if (!$question_chooser->question_count())
				$question_chooser->create_questions();

			$city_values["question_chooser_serialized"] = serialize($question_chooser);
		}
	}

	$cities["אוצרות"] = $treasures;
	$cities["בעבודה"] = !empty($in_construction);
	//print_r($cities);
	return $cities;
}


if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	show_html_header('land');
	require_once('world.php');

	$world = secnodary_object_from_get_or_session('World');
	$land = object_from_get_or_session('Land');
	$practice = variable_from_get_or_session('practice', /*default=*/false);
	redirect_if_requested();

	if (!empty($_GET['refresh'])) {
		$land->refresh();  // refresh the wikisource_cache
		$land = refresh_object('Land', $land->fresh_instance()); // refresh the session object
	}

	print "<a class='back' href='world.php'>".($world->title_for_display? $world->title_for_display: 'העולם')."</a>";
	print "<div class='land'>";
	$land->play();
	print "
		</div><!--land-->
		";

	show_html_footer();
}
?>