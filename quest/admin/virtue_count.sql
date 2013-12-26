DROP TABLE IF EXISTS virtue_count;
CREATE TABLE virtue_count (
  virtue varchar(32) NOT NULL PRIMARY KEY,
  count int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8
