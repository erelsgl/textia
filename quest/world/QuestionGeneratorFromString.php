<?php

/*קידוד אחיד!*/
/**
 * @file QuestionGeneratorFromString.php - create a QuestionGenerator from a string that defines its type.
 * @author Erel Segal
 * @date 2009-06-20
 * @copyright GPL 
 */


/**
 * @return an array with two elements:
 *    $question_generator - an object of type QuestionGenerator.
 *    $required_file      - a string that contains the path to a file that needs to be required to define the generator.
 */
function QuestionGeneratorFromString($question_type, $city) {
	$question_generator = NULL; $required_file = NULL;

	if (preg_match("/האם כתוב\s*(\d+)?\s*(.+)?/",$question_type,$matches)) {
		require_once($required_file=dirname(__FILE__).'/issource.php');
		$question_generator = new IssourceQuestionGenerator($city->title_for_display);
		if (!empty($matches[1]))
			$question_generator->set_word_count($matches[1]);
		if (!empty($matches[2]))
			$question_generator->set_other_sources_list(explode("|",$matches[2]));
	} elseif (preg_match("/האם רצף\s*(בדף אחד)?\s*(\d+)?/",$question_type,$matches)) {
		require_once($required_file=dirname(__FILE__).'/isnext.php');
		$question_generator = new IsnextQuestionGenerator($city->title_for_display);
		if (!empty($matches[1]))
			$question_generator->set_single_subsource(true);
		if (!empty($matches[2]))
			$question_generator->set_sentence_count($matches[2]);
	} elseif (preg_match("/איפה כתוב\s*(\d+)?\s*(.+)?/",$question_type,$matches)) {
		require_once($required_file=dirname(__FILE__).'/whatsourceland.php');
		$question_generator = new WhatsourceQuestionGenerator();
		if (!empty($matches[1]))
			$question_generator->set_word_count($matches[1]);
		if (!empty($matches[2]))
			$question_generator->set_sources_list(explode("|",$matches[2]));
	} elseif (preg_match("/מה חסר\s*(\d+)?\s*(.+)?/",$question_type,$matches)) {
		/// @see whatphrase.php
		$question_chooser_serialized = $city->data("question_chooser_serialized");
		if (!$question_chooser_serialized) {
			user_error("<br/>לעיר שלי אין מפתח ביטויים<br/>", E_USER_ERROR);
		}
		$question_generator = unserialize($question_chooser_serialized);
		if (!empty($matches[1]))
			$question_generator->set_word_count($matches[1]);
		if (!empty($matches[2]))
			$question_generator->set_answer_list(explode("|",$matches[2]));
	} elseif (preg_match("/תבנית\s+(.*?)(\d*)$/",$question_type,$matches)) {
		$question_chooser_serialized = $city->data("question_chooser_serialized");
		if (!$question_chooser_serialized) {
			user_error("<br/>לעיר שלי אין מפתח ביטויים<br/>", E_USER_ERROR);
		}
		$question_generator = unserialize($question_chooser_serialized);
		$question_generator->set_question_prefix($matches[1]);
		if ($matches[2])
			$question_generator->set_word_count($matches[2]);
		$question_generator->verify_question_count();
	} else {
		/// @see whatemplate.php
		user_error("<br/>אני לא מכיר את סוג השאלות '$question_type'<br/>", E_USER_ERROR);
	}

	return array($question_generator,$required_file);
}

?>