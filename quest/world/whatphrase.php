<?php
/*קידוד אחיד!*/
error_reporting(E_ALL);

/**
 * @file whatphrase.php
 * A question-generator that hides a phrase from a list and lets the user guess what it is (multiple-choice).
 * Good for questions such as "Complete the sentence: ___"
 * Uses the parsed version of the page.
 * 
 * @link http://he.wikisource.org
 * @author Erel Segal
 * @date 2009-04-20
 * @c GPL 
 */
require_once('QuestionChooser.php');

require_once(dirname(__FILE__)."/../../sites/MediawikiClient.php");
require_once("$GLOBALS[SCRIPTFOLDER]/random_sentence.php");


define('MISSING_WORD', "______");

class WhatphraseQuestionGenerator extends QuestionChooser {

	var $phrases_arrays;
	var $word_count; // optional, limit the number of words in question to make them more difficult
	var $answer_list; // optional, choose only questions with one of these answers.
	var $condition;  // string, where clause

	/**
	 * @param mixed $source_titles string or array of strings. a title of a Wikisource page from where the questions will be taken.
	 */
	function __construct($source_titles, $phrases_arrays, $word_count=20, $answer_list=NULL) {
		parent::__construct("wikisource_question_index", $source_titles, "מה חסר");
		$this->phrases_arrays = $phrases_arrays;
		$this->set_word_count($word_count);
		$this->set_answer_list($answer_list);
	}

	function set_answer_list($answer_list) {
		$this->answer_list = $answer_list;
		if ($answer_list) {
			$this->condition .= " AND answer IN (".implode(",",quote_smart_array($answer_list)).")";
		}
	}

	// Override QuestionChooser function to add some formatting
	function table_row_to_question_data($row) {
		//var_dump($row);
		require_once("$GLOBALS[SCRIPTFOLDER]/split_text.php");
		$possible_questions = split_text($row['question'], $this->word_count, MISSING_WORD);
		if (!$possible_questions) {
			user_error("Couldn't find missing word in '$row[question]'!", E_USER_WARNING);
			return NULL;
		}
		$i = array_rand($possible_questions);
		$question = $possible_questions[$i];
		$answer = preg_replace("/[?().*+]/",'',$row['answer']); // remove regexp
		$all_answers = explode("|",
			preg_replace("/[?().*+]/",'',$row['all_answers']) // remove regexp chars
			);
		return array(
			"מה חסר במשפט '".$question."'?",
			$answer,
			$row['answer_details'],
			$all_answers);
	}

	function create_questions() {
		$this->initialize_questions();
		$phrases_arrays = $this->phrases_arrays;
		//var_dump($phrases_arrays);
		foreach ($this->source_titles as $source_title) {
			$link_to_source = $GLOBALS['MediawikiClient']->link_by_title($source_title);
			$anchor_to_source = "<a target='_blank' href='$link_to_source'>$source_title</a>"; // link to the source

			$source = $GLOBALS['MediawikiClient']->page_cached($source_title, "wikisource_plaintext_cache",
				/*$compile_function (see script/html.php) =*/"clean_wikisource_tags", 
				/*$refresh=*/false, /*$parsed=*/true); // use the PARSED wikipage to search for phrases!

			$sentences = preg_split("/\s*[.]\s*/",$source);
			if (!$sentences) {
				user_error("No sentences in '$source_title'", E_USER_WARNING);
				continue; 
			}

			$short_sentences = array();
			// split long sentences to shorter sentences
			$MAX_WORDS_IN_SENTENCE = 20;
			$MAX_CHARS_IN_SENTENCE = 5*$MAX_WORDS_IN_SENTENCE; // save time - don't split every sentence
			require_once("$GLOBALS[SCRIPTFOLDER]/split_text.php");
			foreach ($sentences as $sentence) {
				if (strlen($sentence)<=$MAX_CHARS_IN_SENTENCE)
					$short_sentences[]=$sentence;
				else {
					$short_sentences = array_merge($short_sentences,
						split_text($sentence,$MAX_WORDS_IN_SENTENCE));
				}
			}
			if (!$short_sentences) {
				user_error("No short sentences in '$source_title'", E_USER_WARNING);
				continue; 
			}

			foreach ($short_sentences as $sentence) {
				foreach ($phrases_arrays as $phrases_array) {
					$found=false;
					$sentence_without_previous_phrases=$sentence;
					foreach ($phrases_array as $phrase) {
						if (preg_match("/$phrase/",$sentence_without_previous_phrases)) {
							$question = preg_replace("/$phrase/", MISSING_WORD,$sentence);
							$answer_details = preg_replace("/($phrase)/", "<b>$1</b>",$sentence).", $anchor_to_source";
							$this->add_question($source_title, $question, $phrase, $answer_details, $phrases_array);
							$sentence_without_previous_phrases = preg_replace("/$phrase/", '_', $sentence_without_previous_phrases);
							$found=true;
						}
					}
					if ($found)
						break;
				}
			}
		}
		$this->commit_questions();	
	}

	static function phrases_strings_to_phrases_arrays($phrases_strings) {
		$phrases_arrays = array();
		foreach ($phrases_strings as $string) {
			$array = explode("|",$string);
			// TODO: sort from long to short
			$phrases_arrays[]=$array;
		}
		return $phrases_arrays;
	}
}



?>