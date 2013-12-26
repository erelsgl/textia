<?php
/**
 * @file fix_include_path.php
 * fix PHP's include path to include PEAR
 */
set_include_path(realpath(dirname(__FILE__) . "/../sites") . PATH_SEPARATOR . get_include_path());
set_include_path(realpath(dirname(__FILE__) . "/../script") . PATH_SEPARATOR . get_include_path());
set_include_path(realpath(dirname(__FILE__) . "/PEAR") . PATH_SEPARATOR . get_include_path());
?>