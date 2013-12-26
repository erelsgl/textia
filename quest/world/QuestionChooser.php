<?php
error_reporting(E_ALL);

/**
 * @file QuestionChooser.php - A question-generator that chooses a random question from a database table.
 * @see whatphrase.php
 * @author Erel Segal
 * @date 2009-04-24
 * @copyright GPL 
 */

abstract class QuestionChooser  /*extends QuestionGenerator*/ {
	/** Name of a database table, with fields:
	 *	id
	 *	source_title
	 *	question_type
	 *	question
	 *	answer
	 *	answer_details
	 *	all_answers
	 *  times_asked
	 */
	var $table_name; 
	var $source_titles;
	var $question_type;
	var $condition;
	var $word_count;

	var $insertion_query;
	var $question_count;

	/**
	 * @param string $table_name name of a database table where the questions are kept.
	 * @param mixed $source_titles string or array of strings: title[s] of Wikisource page.
	 * @param string $question_type type of question to read from table.
	 */
	function __construct($table_name, $source_titles, $question_type) {
		$this->table_name = $table_name;
		$this->source_titles = is_array($source_titles)? $source_titles: array($source_titles);
		$this->question_type = $question_type;

		$this->word_count = NULL;

		$this->condition = 
			"source_title IN (".implode(",",quote_smart_array($this->source_titles)).
			") AND question_type=".quote_smart($this->question_type);
	}

	function random_question_query() {
		return "
			SELECT ceil(LENGTH(question)/100) AS approximated_question_count, {$this->table_name}.* 
			FROM {$this->table_name}
			WHERE {$this->condition}
			ORDER BY times_asked/approximated_question_count, RAND()
			LIMIT 1
			";
	}


	function set_word_count($word_count) {
		$this->word_count = $word_count;
	}


	function initialize_questions() {
		sql_query_or_die("
			DELETE FROM {$this->table_name}
			WHERE {$this->condition}
			");
		require_once("$GLOBALS[SCRIPTFOLDER]/sql_delayed_insert.php");
		$GLOBALS['FLUSH_QUERY_AT'] = 100000;
		$this->insertion_query = new SqlDelayedInsertQuery("INSERT INTO {$this->table_name} (
			source_title,
			question_type,
			question,
			answer,
			answer_details,
			all_answers)");
		$this->question_count=0;
	}

	function add_question($source_title, $question, $answer, $answer_details, $all_answers) {
		$this->insertion_query->add(
			quote_smart($source_title).",".
			quote_smart($this->question_type).",".
			quote_smart($question).",".
			quote_smart($answer).",".
			quote_smart($answer_details).",".
			quote_smart(implode("|",$all_answers)));
		++$this->question_count;
	}

	function commit_questions() {
		if (!$this->question_count) {
			user_error("No questions created!", E_USER_WARNING);
			print "\n<pre dir='ltr'>\n"; print_r($this); print "\n</pre>\n";
		} else {
			$this->insertion_query->commit();
		}
	}


	abstract function create_questions();

	function table_row_to_question_data($row) {
		return array(
			$row['question'], 
			$row['answer'], 
			$row['answer_details'], 
			explode("|",$row['all_answers']));
	}

	function verify_question_count() {
		$query = $this->random_question_query();
		if (!sql_evaluate_assoc($query)) 
			user_error("No questions! query='$query'",E_USER_WARNING);
	}

	function question_and_answer() {
		$query = $this->random_question_query();
		$row = sql_evaluate_assoc($query);
		if (!$row) {
			user_error("No questions! query='$query'",E_USER_WARNING);
			return NULL;
		}
		sql_query_or_die("
			UPDATE {$this->table_name}
			SET times_asked=times_asked+1
			WHERE id=$row[id]
			");
		return $this->table_row_to_question_data($row);
	}

	function question_count() {
		return sql_evaluate("
			SELECT COUNT(*) FROM {$this->table_name}
			WHERE {$this->condition}
			");
	}

	function all_answers() {
		return sql_evaluate_array("
			SELECT DISTINCT answer FROM {$this->table_name}
			WHERE {$this->condition}
			");
	}
}

?>