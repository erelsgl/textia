DROP TABLE IF EXISTS users;
CREATE TABLE users (
	id int PRIMARY KEY,
	name varchar(255),
	thumbnail text
)
ENGINE=MyISAM DEFAULT CHARSET=utf8;
