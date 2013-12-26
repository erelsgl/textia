DROP TABLE IF EXISTS wikisource_question_index;
CREATE TABLE wikisource_question_index (
	id int NOT NULL AUTO_INCREMENT PRIMARY KEY,

	source_title varchar(63) NOT NULL,
	question_type varchar(15) NOT NULL,
	
	KEY (source_title,question_type),

	question text,
	answer varchar(31),
	answer_details text,
	all_answers text,
	times_asked int NOT NULL DEFAULT 0
)
CHARACTER SET utf8;
