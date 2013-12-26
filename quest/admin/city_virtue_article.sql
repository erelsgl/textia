DROP TABLE IF EXISTS city_virtue_article;
CREATE TABLE city_virtue_article (
	city varchar(32) NOT NULL,
	virtue varchar(32) NOT NULL,
	article varchar(255) NOT NULL,
	regex varchar(255) NOT NULL,
	PRIMARY KEY(city,virtue,article),  /* see city.php    */
	KEY(article)                       /* see article.php */
) ENGINE=MyISAM DEFAULT CHARSET=utf8
