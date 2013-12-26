<?php 

/**
 * @file sql_delayed_delete.php - Perform many delete queries in a single operation, to save I/O time.
 * @see sql_delayed_insert.php
 * @author Erel Segal
 * @date 2007-11-18
 */

require_once('sql.php');

/**
 * more bytes = more efficient; too many bytes = you might get an SQL error ( http://dev.mysql.com/doc/refman/5.0/en/packet-too-large.html ) or a memory error (Fatal PHP error - not logged!!).
 */
if (!isset($GLOBALS['FLUSH_QUERY_AT']))
	$GLOBALS['FLUSH_QUERY_AT'] = 500000;
//$GLOBALS['FLUSH_QUERY_AT'] = sql_evaluate("select @@max_allowed_packet")-5000;

class SqlDelayedDeleteQuery {
	var $prefix;
	var $conditions;

	function SqlDelayedDeleteQuery($prefix) {
		$this->prefix = $prefix;
	}

	function add($conditions) {
		$current_query_length = strlen($this->conditions); // ignore prefix (approximation only)
		$new_length = strlen($conditions);

		if ($current_query_length+$new_length >= $GLOBALS['FLUSH_QUERY_AT'])
			$this->commit();

		if ($this->conditions)
			$this->conditions .= " OR ";
		$this->conditions .= "($conditions)";
	}

	function commit() {
		if ($this->conditions) {
			sql_query_or_die("
				{$this->prefix}
				WHERE
				{$this->conditions}
				"
				);
			$this->conditions = '';
		}
	}
}

?>