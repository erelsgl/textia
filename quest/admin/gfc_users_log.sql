DROP TABLE IF EXISTS gfc_users_log;
CREATE TABLE gfc_users_log (
	id varchar(255),
	name varchar(255),
	thumbnail varchar(255),
	profile varchar(255),
	application varchar(255),
	action varchar(255),
	followup varchar(255),
	created_at datetime
) ENGINE=MyISAM DEFAULT CHARSET=utf8
