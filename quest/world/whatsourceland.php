<?php
/*קידוד אחיד!*/
error_reporting(E_ALL);

/**
 * @file whatsourceland.php
 * A question-generator that asks whether the source of a given quotation is  given source (yes/no question).
 * Uses the global variable "$land", which containt a list of "cities" with subsources.
 * @see issource.php, whatsource.php
 * @link http://he.wikisource.org
 * @author Erel Segal
 * @date 2009-04-29
 * @copyright GPL 
 */

require_once(dirname(__FILE__)."/../../sites/MediawikiClient.php");
require_once("$GLOBALS[SCRIPTFOLDER]/random_sentence.php");

class WhatsourceQuestionGenerator /*extends QuestionGenerator*/ {
	var $word_count;
	var $sources_list;

	/**
	 * @param string $quiz_title a title of a Wikisource page where the quiz definition is written.
	 * @param int $word_count number of random words to include in question
	 * @param boolean $refresh FALSE to use the cached definition if exists; TRUE to reload the definition from wikisource
	 */
	function __construct($word_count=40) {
		global $land;
		$this->set_word_count($word_count);
		$this->set_sources_list($land->all_city_titles());
	}

	function set_word_count($word_count) {
		$this->word_count = $word_count;
	}

	function set_sources_list($list) {
		$this->sources_list = $list;
	}


	function question_and_answer() {
		global $land;

		$source_title_index = array_rand($this->sources_list);
		$source_title = $this->sources_list[$source_title_index];

		$subsources = $land->city_data($source_title,"subsources");

		// select a subsource at random, and select a phrase at random from that subsource:
		$subsource_title = array_rand($subsources);
		if (!$subsource_title) {
			user_error("Empty random title chosen from ".implode(",",$subsources),E_USER_WARNING);
			return NULL;
		}

		$subsource_text = $GLOBALS['MediawikiClient']->page_cached($subsource_title, "wikisource_plaintext_cache", 
			/*$compile_function (see script/html.php) =*/"clean_wikisource_tags", 
			/*$refresh=*/false, /*parsed=*/true);
		if (!$subsource_text) {
			user_error("Text $subsource_title not found",E_USER_WARNING);
			return NULL;
		}

		$question = "איפה כתוב '<q>...".random_subsentence($subsource_text, $this->word_count)."...</q>'?";

		$link_to_subsource = $GLOBALS['MediawikiClient']->link_by_title($subsource_title);
		$answer_details = "<a target='_blank' href='$link_to_subsource'>$subsource_title</a>";

		$all_answers = $this->sources_list;

		return array($question, $source_title, $answer_details, $all_answers);
	}
}

?>