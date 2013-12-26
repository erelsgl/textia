<?php

/**
 * @return a section with N words, chosen at random from the given content.
 */
function random_subsentence($content, $word_count) {
	$content_words = preg_split("/\s+/",$content);
	if (count($content_words)<=$word_count)
		return $content;
	$first_word_index = rand(0,count($content_words) - $word_count);
	$question_words = array_slice($content_words, $first_word_index, $word_count);
	return implode(" ", $question_words);
}

global $default_words_per_sentence;
$default_words_per_sentence = 20;  // used when there is no sentence split

function sentence_split($content) {
	global $default_words_per_sentence;
	$content_sentences = preg_split("/[.?!]\s+/",$content);
	if (count($content_sentences)<2) {
		$content_sentences = array();
		$content_words = preg_split("/\s+/",$content);
		$sentence_index=0;
		$sentence = "";
		$word_index = 0;
		foreach ($content_words as $word) {
			$sentence .= $word;
			$word_index++;
			if ($word_index >= $default_words_per_sentence) {
				$content_sentences[$sentence_index++] = $sentence;
				$sentence = "";
				$word_index = 0;
			} else {
				$sentence .= " ";
			}
		}
		$content_sentences[$sentence_index++] = $sentence;
	}
	return $content_sentences;
}

/**
 * @return a section with N sentences, chosen at random from the given content.
 */
function random_section($content, $sentence_count) {
	$content_sentences = sentence_split($content);
	if (count($content_sentences)<=$sentence_count)
		return $content_sentences;
	$first_sentence_index = rand(0,count($content_sentences) - $sentence_count);
	$question_sentences = array_slice($content_sentences, $first_sentence_index, $sentence_count);
	return $question_sentences;
}

/**
 * @return N random sentences, chosen at random from the given content.
 */
function random_sentences($content, $sentence_count) {
	$content_sentences = sentence_split($content);
	if (count($content_sentences)<=$sentence_count)
		return $content_sentences;
	$question_sentences = array();
	$random_indices_already_chosen = array();
	for ($i=0; $i<$sentence_count; ++$i) {
		for ($retry=0; $retry<3; ++$retry) {
			$index = rand(0,count($content_sentences)-1);
			if (!isset($random_indices_already_chosen[$index]) && !empty($random_indices_already_chosen[$index])) {
				$random_indices_already_chosen[$index] = true;
				break;
			}
		}
		$question_sentences[$i] = $content_sentences[$index];
	}
	return $question_sentences;
}

?>