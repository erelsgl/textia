DROP TABLE IF EXISTS `user_score_whatbook`;
CREATE TABLE `user_score_whatbook` (
  `userid` varchar(255) NOT NULL default '',
  `question_type` varchar(15) NOT NULL,
  `book` varchar(63) NOT NULL,
  `correct_verses` int(11) NOT NULL,
  `incorrect_verses` int(11) NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `is_last_for_user` boolean DEFAULT NULL,
  UNIQUE KEY  (`userid`,`is_last_for_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
/*
update user_score_whatbook set userid=right(concat('00',userid),20)
*/
