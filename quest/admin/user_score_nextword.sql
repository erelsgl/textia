DROP TABLE IF EXISTS `user_score_nextword`;
CREATE TABLE `user_score_nextword` (
  `userid` varchar(255) NOT NULL default '',
  `book` varchar(15) NOT NULL,
  `chapter` varchar(3) NOT NULL,
  `verse_number` int(11) NOT NULL,
  `correct_words` int(11) NOT NULL,
  `incorrect_words` int(11) NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `is_last_for_user` boolean DEFAULT NULL,
  PRIMARY KEY  (`userid`,`is_last_for_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
