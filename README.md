# textia

This is the first **Wiki-Game** - a game that is entirely based on texts in a Wikimedia site ([Hebrew Wikisource](http://he.wikisource.org)).

This is a **multi-player persistent browser-based strategy game**. The players conquer cities and lands by answering automatically-generated questions about open-source texts.

## Requirements
* Apache 2+
* MySQL 5+
* PHP 5+
* PHP-MySQL extension
* PHP CURL module

## Installation
* Clone the repository.
* In your Apache2 configuration, create an alias "/quest" that points to the "quest" folder. This can be done, for example, with the following Linux command:

	sudo ln -s [full-path-to-quest-folder] /var/www/quest

* Run the create script:

	php quest/admin/create.php
	
* Enter the root username and password of your MySQL installation.
* Select a database name, username and password for the new database.

## Play
* Go to http://localhost/quest

## TODO
* Login with Google and Facebook don't work anymore. This requires someone that knows the details of the new login APIs.
* The "virtue quest" currently works in a small number of cities, and its quality is insufficient. This requires more thinking about the best way to implement. 
