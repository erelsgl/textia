DROP TABLE IF EXISTS land_leaders;
CREATE TABLE land_leaders (
  land varchar(32) NOT NULL,
  domain varchar(32) NOT NULL,
  userid varchar(32) NOT NULL,
  count int NOT NULL,
  updated_at datetime,
  PRIMARY KEY (land,domain)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
