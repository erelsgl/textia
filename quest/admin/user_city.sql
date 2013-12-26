DROP TABLE IF EXISTS user_city;
CREATE TABLE user_city (
  land varchar(32) NOT NULL, 
  city varchar(32) NOT NULL, 
  userid varchar(32) DEFAULT NULL,
  PRIMARY KEY(land,city),
  KEY(userid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
