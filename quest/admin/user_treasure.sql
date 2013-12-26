DROP TABLE IF EXISTS user_treasure;
CREATE TABLE user_treasure (
  land varchar(32) NOT NULL, 
  city varchar(32) NOT NULL, 
  treasure varchar(32) NOT NULL,
  userid varchar(32) DEFAULT NULL,
  PRIMARY KEY(land,city,treasure),
  KEY(userid,land,city),
  KEY(userid,treasure)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
