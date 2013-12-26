<?php
$protocol = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']==443 ? 'https' : 'http');
$host = $_SERVER['HTTP_HOST'];
$folder = preg_replace("#/index[.]php#i","",$_SERVER['PHP_SELF']);
// print_r($_SERVER);
// print $folder;
// die;
$querystring = $_SERVER['QUERY_STRING'];
$script = "world.php?title=משחק:טקסטיה&".$_SERVER['QUERY_STRING'];
if (isset($_GET['fb_sig'])) {
	header("Location: $protocol://$host$folder/facebook/$script");
} else {
	header("Location: $protocol://$host$folder/world/$script");
}
?>