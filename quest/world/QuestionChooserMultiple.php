<?php
/*קידוד אחיד!*/
error_reporting(E_ALL);

/**
 * @file QuestionChooserMultiple.php - A question-generator that chooses a random question using several QuestionChoosers
 * @see QuestionChooser.php
 * @author Erel Segal
 * @date 2009-04-24
 * @copyright GPL 
 */

class QuestionChooserMultiple  /*extends QuestionGenerator*/ {
	var $choosers;

	/**
	 * @param string $source_title a title of a Wikisource page where the quiz definition is written.
	 * @param array $phrase_index array of questions and answers
	 */
	function __construct() {
		$this->choosers=array();
	}

	function add($chooser) {
		$this->choosers[]=$chooser;
	}

	function question_and_answer() {
		$chooser_index = array_rand($this->choosers);
		return $this->choosers[$chooser_index]->question_and_answer();
	}

	function create_questions() {
		foreach ($this->choosers as $chooser)
			$chooser->create_questions();
	}

	function question_count() {
		$count=0;
		foreach ($this->choosers as $chooser)
			 $count += $chooser->question_count();
		return $count;
	}
}

?>