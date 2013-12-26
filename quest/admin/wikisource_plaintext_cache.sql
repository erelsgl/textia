/* 
 Caches parsed wikisource pages in plain text - no HTML tags.
 Used with compile_function=clean_html_tags.
*/
DROP TABLE IF EXISTS wikisource_plaintext_cache;
CREATE TABLE wikisource_plaintext_cache (
	title varchar(255) NOT NULL,
	parsed boolean NOT NULL default TRUE,
	PRIMARY KEY (title,parsed),

	compiled_content longtext,
	compiled_at datetime
)
CHARACTER SET utf8;
