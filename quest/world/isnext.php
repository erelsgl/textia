<?php
/*קידוד אחיד!*/
error_reporting(E_ALL);

/**
 * @file isnext.php
 * A question-generator that asks two given quotations are near each other in the given source.
 * @link http://he.wikisource.org
 * @author Erel Segal
 * @date 2011-08-12
 * @copyright GPL 
 */

require_once(dirname(__FILE__)."/../../sites/MediawikiClient.php");

class IsnextQuestionGenerator /*extends QuestionGenerator*/ {
	var $source_title; // the title of the source of the sentences
	var $subsources;
	var $sentence_count;
	var $single_subsource; // if true - choose random sentences from the same subsource. if false - choose the subsources at random, too. This is more challenging, but takes more time to load.


	/**
	 * @param string $quiz_title a title of a Wikisource page where the quiz definition is written.
	 * @param int $word_count number of random words to include in question
	 * @param boolean $refresh FALSE to use the cached definition if exists; TRUE to reload the definition from wikisource
	 */
	function __construct($source_title, $sentence_count=2, $single_subsource=false) {
		global $land;
		$this->source_title = $source_title;
		$this->set_sentence_count($sentence_count);
		$this->set_single_subsource($single_subsource);
		$this->subsources = $land->city_data($source_title, "subsources");
		if (!$this->subsources) {
			user_error("No subsources for '$source_title'!", E_USER_WARNING);
		}
	}

	function set_sentence_count($count) {
		$this->sentence_count = $count;
	}

	function set_single_subsource($single_subsource) {
		$this->single_subsource = $single_subsource;
	}

	function question_and_answer() {
		require_once("$GLOBALS[SCRIPTFOLDER]/random_sentence.php");

		if (!$this->subsources) {
			user_error("No subsources for '$source_title'!", E_USER_WARNING);
			return NULL;
		}

		$subsource_title = array_rand($this->subsources);
		if (!$subsource_title) {
			user_error("No subsource title!", E_USER_WARNING);
			return NULL;
		}

		$subsource_text = $GLOBALS['MediawikiClient']->page_cached($subsource_title, "wikisource_plaintext_cache", 
			/*$compile_function (see script/html.php) =*/"clean_wikisource_tags", 
			/*$refresh=*/false, /*parsed=*/true);

		//if (strpos($subsource_text,"."===false))
		//	print($subsource_text);
		$answer_yes = rand(0,1);  // random integer from 0 to 1. 0=no, 1=yes.ss	ss
		if ($answer_yes)
			$sentences = random_section($subsource_text, $this->sentence_count);
		else {
			if ($this->single_subsource)
				$sentences = random_sentences($subsource_text, $this->sentence_count);
			else { // choose from two different subsources
				$other_subsource_title = array_rand($this->subsources);
				$other_subsource_text = $GLOBALS['MediawikiClient']->page_cached($other_subsource_title, "wikisource_plaintext_cache", 
					/*$compile_function (see script/html.php) =*/"clean_wikisource_tags", 
					/*$refresh=*/false, /*parsed=*/true);
				$sentences = random_sentences($subsource_text.". ".$other_subsource_text, $this->sentence_count);
			}
		}
		if (count($sentences)<2) {
			user_error("Not enough sentences in $subsource_text!", E_USER_WARNING);
			return NULL;
		}

		$question = "האם המשפטים הבאים, מתוך ".$this->source_title.", נמצאים ברצף, זה אחר זה?\n<ul>\n";
		foreach ($sentences as $sentence) {
			$question .= "<li><q>$sentence.</q></li>\n";
		}
		$question .= "</ul>\n";

		$answer_details = "<a target='_blank' href='".$GLOBALS['MediawikiClient']->link_by_title($subsource_title)."'>$subsource_title</a>";
		if (isset($other_subsource_title))
			$answer_details .= ", <a target='_blank' href='".$GLOBALS['MediawikiClient']->link_by_title($other_subsource_title)."'>$other_subsource_title</a>";

		$all_answers = 'NO_YES';
		return array($question, $answer_yes, $answer_details, $all_answers);
	}
}

?>