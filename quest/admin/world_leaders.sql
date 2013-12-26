DROP TABLE IF EXISTS world_leaders;
CREATE TABLE world_leaders (
  domain varchar(32) NOT NULL,
  userid varchar(32) NOT NULL,
  count int NOT NULL,
  updated_at datetime,
  PRIMARY KEY (domain)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
