DROP TABLE IF EXISTS gfc_users;
CREATE TABLE gfc_users (
	id varchar(255) primary key,
	name varchar(255),
	thumbnail text,
	profile text,
	created_at datetime
) ENGINE=MyISAM DEFAULT CHARSET=utf8
