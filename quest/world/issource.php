<?php
/*קידוד אחיד!*/
error_reporting(E_ALL);

/**
 * @file issource.php
 * A question-generator that asks whether the source of a given quotation is  given source (yes/no question).
 * Uses the global variable "$land", which containt a list of "cities" with subsources.
 * @see whatsource.php
 * @link http://he.wikisource.org
 * @author Erel Segal
 * @date 2009-03-20
 * @copyright GPL 
 */

require_once(dirname(__FILE__)."/../../sites/MediawikiClient.php");

class IssourceQuestionGenerator /*extends QuestionGenerator*/ {
	var $source_title; // the title of the source for which the answer is "yes"
	var $other_sources_list; // an array of other sources, from which to choose when the answer is "no"
	var $word_count;

	/**
	 * @param string $quiz_title a title of a Wikisource page where the quiz definition is written.
	 * @param int $word_count number of random words to include in question
	 * @param boolean $refresh FALSE to use the cached definition if exists; TRUE to reload the definition from wikisource
	 */
	function __construct($source_title, $word_count=40) {
		global $land;
		$this->source_title = $source_title;
		$this->set_word_count($word_count);
		$this->set_other_sources_list($land->all_city_titles());
	}

	function set_word_count($count) {
		$this->word_count = $count;
	}

	function set_other_sources_list($list) {
		$this->other_sources_list = $list;
	}


	function question_and_answer() {
		global $land;
		require_once("$GLOBALS[SCRIPTFOLDER]/random_sentence.php");

		$answer_yes = rand(0,1);  // random integer from 0 to 1. 0=no, 1=yes.

		// If answer is yes, read text from current city; if answer is no, read text from another city in this land:
		if ($answer_yes) {
			$source_title = $this->source_title;
		} else { // answer_no
			for ($i=1; $i<=10; ++$i) {
				$source_title_index = array_rand($this->other_sources_list);
				$source_title = $this->other_sources_list[$source_title_index];
				if ($source_title!=$this->source_title && $land->city_data($source_title,"subsources")) break;
			}
			if ($source_title==$this->source_title) {
				user_error("Cannot find a random city!", E_USER_WARNING);
				return NULL;
			}
		}

		$subsources = $land->city_data($source_title,"subsources");
		if (!$subsources) {
			user_error("No subsources for '$source_title'!", E_USER_WARNING);
			return NULL;
		}

		// select a subsource at random, and select a phrase at random from that subsource:
		$subsource_title = array_rand($subsources);
		if (!$subsource_title) {
			user_error("No subsource title!", E_USER_WARNING);
			return NULL;
		}

		$subsource_text = $GLOBALS['MediawikiClient']->page_cached($subsource_title, "wikisource_plaintext_cache", 
			/*$compile_function (see script/html.php) =*/"clean_wikisource_tags", 
			/*$refresh=*/false, /*parsed=*/true);

		$question = "האם הקטע '<q>...".random_subsentence($subsource_text, $this->word_count)."...</q>' נמצא ב'$this->source_title'?";
		
		$link_to_subsource = $GLOBALS['MediawikiClient']->link_by_title($subsource_title);
		$answer_details = "<a target='_blank' href='$link_to_subsource'>$subsource_title</a>";

		$all_answers = 'NO_YES';
		return array($question, $answer_yes, $answer_details, $all_answers);
	}
}

?>