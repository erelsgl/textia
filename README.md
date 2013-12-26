# textia

This is the first **Wiki-Game** - a game that is entirely based on texts in a Wikimedia site ([Hebrew Wikisource](http://he.wikisource.org)).

This is a **multi-player persistent browser-based strategy game**. The players conquer cities and lands by answering automatically-generated questions about open-source texts.

## Requirements
* Apache 2+
* PHP 5+
* MySQL 5+

## Installation
* Clone the repository.
* In your Apache2 configuration, create an alias "/quest" that points to the "quest" folder.
* Go to http://localhost/quest/admin/create.php
* Enter the root username and password of your MySQL installation.
* Select a database name, username and password for the new database.
* Submit.

## Play
* Go to http://localhost/quest

## TODO
* Facebook login support used to work, but now it doesn't. This requires someone that knows the details of the new Facebook API.
* The "virtue quest" currently works in a small number of cities, and its quality is insufficient. This requires more thinking about the best way to implement. 
