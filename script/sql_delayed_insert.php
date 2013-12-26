<?php 

/**
 * @file sql_delayed_insert.php - Perform many insert queries in a single operation, to save I/O time.
 * @see sql_delayed_delete.php
 * @author Erel Segal
 * @date 2007-11-14
 */

require_once('sql.php');

/**
 * more bytes = more efficient; too many bytes = you might get an SQL error ( http://dev.mysql.com/doc/refman/5.0/en/packet-too-large.html ) or a memory error (Fatal PHP error - not logged!!).
 */
if (!isset($GLOBALS['FLUSH_QUERY_AT']))
	$GLOBALS['FLUSH_QUERY_AT'] = 500000;
//$GLOBALS['FLUSH_QUERY_AT'] = sql_evaluate("select @@max_allowed_packet")-5000;

class SqlDelayedInsertQuery {
	var $prefix;
	var $values;

	/**
	 * @example 1 $query=new SqlDelayedInsertQuery("REPLACE INTO treasure_data")
	 */
	function SqlDelayedInsertQuery($prefix) {
		$this->prefix = $prefix;
	}

	/**
	 * @example 2 $query->add ("'abc',123")
	 */
	function add($values) {
		$current_query_length = strlen($this->values); // ignore prefix (approximation only)
		$new_length = strlen($values);

		if ($current_query_length+$new_length >= $GLOBALS['FLUSH_QUERY_AT'])
			$this->commit();

		if ($this->values)
			$this->values .= ",";
		$this->values .= "\n\t($values)";
	}

	function is_empty() {
		return (!$this->values);
	}

	function commit() {
		if ($this->values) {
			sql_query_or_die("
				{$this->prefix}
				 VALUES
				{$this->values}
				"
				);
			$this->values = '';
		}
	}
}

?>