DROP TABLE IF EXISTS wikisource_cache;
CREATE TABLE wikisource_cache (
	title varchar(255) NOT NULL,
	parsed boolean NOT NULL default FALSE,
	PRIMARY KEY (title,parsed),

	compiled_content longtext,
	compiled_at datetime
)
CHARACTER SET utf8;
