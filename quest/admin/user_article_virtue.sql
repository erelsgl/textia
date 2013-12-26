DROP TABLE IF EXISTS user_article_virtue;
CREATE TABLE user_article_virtue (
  land varchar(32) NOT NULL,
  article varchar(255) NOT NULL, 
  virtue varchar(32) NOT NULL,
  userid varchar(32) DEFAULT NULL,
  PRIMARY KEY(userid,article,virtue), /* see virtuequest.php, article.php */
  KEY(userid,virtue)                  /* see game.php:calculate_current_user_stats */
) ENGINE=MyISAM DEFAULT CHARSET=utf8
