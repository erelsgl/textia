DROP TABLE IF EXISTS user_news;
CREATE TABLE user_news (
  land varchar(32) NOT NULL,
  city varchar(32) NOT NULL,
  happened_at datetime,
  type varchar(32) NOT NULL,
  parameter varchar(255) DEFAULT NULL,
  userid varchar(32) NOT NULL,
  PRIMARY KEY(land,city,happened_at,type),
  KEY(happened_at),
  KEY(userid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
