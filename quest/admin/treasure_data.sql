DROP TABLE IF EXISTS treasure_data;
CREATE TABLE treasure_data (
  name varchar(32) NOT NULL PRIMARY KEY,
  image varchar(32) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8
