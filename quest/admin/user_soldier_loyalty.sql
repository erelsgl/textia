DROP TABLE IF EXISTS user_soldier_loyalty;
CREATE TABLE user_soldier_loyalty (
  land varchar(32) NOT NULL, 
  city varchar(32) NOT NULL, 
  soldier int NOT NULL,
  userid varchar(32) NOT NULL,
  loyalty int NOT NULL DEFAULT 0,
  updated_at datetime NOT NULL,
  forgotten_at datetime NOT NULL,
  PRIMARY KEY(land,city,soldier,userid),
  KEY(userid),
  KEY(land,city,soldier)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
