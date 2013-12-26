<?php
/*קידוד אחיד!*/
error_reporting(E_ALL);

/**
 * @file whatemplate.php
 * A question-generator that matches a regular-expression (template) to a source file and extracts a question and an answer from it.
 * Useful for questions such as "Who said to Who"
 * @link http://he.wikisource.org
 * @author Erel Segal
 * @date 2009-04-29
 * @copyright GPL 
 */

require_once(dirname(__FILE__)."/../../sites/MediawikiClient.php");
require_once("$GLOBALS[SCRIPTFOLDER]/random_sentence.php");

require_once('QuestionChooser.php');

class WhatemplateQuestionGenerator extends QuestionChooser {

	var $templates;
	var $question_prefix; // optional, for nicer display only

	/**
	 * @param mixed $source_titles string or array of strings. a title of a Wikisource page from where the questions will be taken.
	 */
	function __construct($source_titles, $templates, $question_prefix="", $word_count=NULL) {
		parent::__construct("wikisource_question_index", $source_titles, "תבנית");
		$this->templates = $templates;
		$this->set_question_prefix($question_prefix);
		if ($word_count)
			$this->set_word_count($word_count);
	}

	function set_question_prefix($question_prefix) {
		$this->question_prefix = $question_prefix;
	}

	// Override QuestionChooser function to add some formatting and use $this->all_answers
	function table_row_to_question_data($row) {
		$question = str_replace($row['answer'],"...",$row['question']); // remove the answer from the question;

		require_once("$GLOBALS[SCRIPTFOLDER]/random_sentence.php");

		if ($this->word_count)
			$question = random_subsentence($question, $this->word_count);

		return array(
			"{$this->question_prefix} '$question'?",
			$row['answer'],
			$row['answer_details'],
			$this->all_answers());
	}

	function create_questions() {
		if (!empty($GLOBALS['DEBUG_QUERY_TIMES'])) {
			print "<h4 dir='ltr'>WhatemplateQuestionGenerator::create_questions()</h4>";
		}
		$this->initialize_questions();
		$templates = $this->templates;
		$all_answers = array(); // not needed here - calculated online after all questions are generated.
		foreach ($this->source_titles as $source_title) {
			$link_to_source = $GLOBALS['MediawikiClient']->link_by_title($source_title);
			$anchor_to_source = "<a target='_blank' href='$link_to_source'>$source_title</a>"; // link to the source

			$source = $GLOBALS['MediawikiClient']->page_cached(
				$source_title, "wikisource_cache",
				/*$compile_function=*/NULL, 
				/*$refresh=*/false, 
				/*$parsed=*/false, // use the SOURCE of the wikipage to search by template!
				/*check_timestamp=*/false  // save network time; assume pages are uptodate
				);

			foreach ($templates as $template) {
				list($replace_from, $replace_to) = preg_split("/\s*->\s*/",$template);
				//print "<p>preg_replace(/$replace_from/i,$replace_to,source)</p>\n";

				$source = preg_replace("/$replace_from/is",$replace_to,$source);
			}

			//print("<div dir='rtl'>$source</div>");

			$question="שאלה";
			$answer="תשובה";
			preg_match_all("/$question=\"(.*?)\" $answer=\"(.*?)\"[ \t\n\r,;:.-]/s",$source,$matches,PREG_SET_ORDER);
					// $matches[0] is an array of first set of matches, $matches[1] is an array of second set of matches, and so on. 
			//print "<pre>"; print "matches:"; print_r($matches); print "</pre>";
			foreach ($matches as $index=>$match) {
				//print("<div>$match[1]</div>");
				$this->add_question(
					$source_title, 
					/*$question=*/$match[1], 
					/*answer=*/$match[2], 
					/*$answer_details=*/"$match[2], $anchor_to_source", 
					$all_answers);
			}
		}
		$this->commit_questions();	
	}
}



?>