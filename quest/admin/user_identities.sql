DROP TABLE if exists user_identities;
CREATE TABLE user_identities (
	identity_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	external_site varchar(32) DEFAULT NULL,
	external_userid varchar(255) DEFAULT NULL,
	userid int,
	is_primary_for_userid_and_site boolean,  
	is_primary_for_userid boolean,
	is_validated boolean NOT NULL,
	is_indexed boolean NOT NULL DEFAULT TRUE,
	is_active boolean NOT NULL DEFAULT TRUE,
	validation_code char(32) DEFAULT NULL,
	PRIMARY KEY (identity_id),
	UNIQUE KEY external_identity (external_site,external_userid),
	/*PRIMARY KEY (external_site,external_userid,is_validated), - is_validated SHOULD NOT be a part of the key. If you change this, read all relevant code again! */
	UNIQUE KEY (userid,external_site,is_primary_for_userid_and_site),
	UNIQUE KEY (userid,is_primary_for_userid),
	UNIQUE KEY (validation_code),

	updated_at timestamp

) CHARACTER SET utf8 ;
