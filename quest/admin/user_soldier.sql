DROP TABLE IF EXISTS user_soldier;
CREATE TABLE user_soldier (
  land varchar(32) NOT NULL, 
  city varchar(32) NOT NULL, 
  soldier int NOT NULL,
  userid varchar(32) DEFAULT NULL,
  PRIMARY KEY(land,city,soldier),
  KEY(userid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
