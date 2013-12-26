DROP TABLE IF EXISTS table_editor_cfg;
CREATE TABLE `table_editor_cfg` ( 
	`table_name` varchar(127) NOT NULL default '', 
	`field_name` varchar(127) NOT NULL default '', 
	`param_type` varchar(20) NOT NULL default '', 
	`param_value` varchar(254) NOT NULL default '', 
	PRIMARY KEY (`table_name`,`field_name`,`param_type`) 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

