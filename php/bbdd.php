<?php
	$whitelist = array('127.0.0.1', '::1');
	$dbh;
	if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
    		$dbh = new PDO('mysql:host=localhost;dbname=transportreus', "root", "root");
	} else {
		$dbh = new PDO(getenv('PDO_URL'), getenv('PDO_USER'), getenv('PDO_PASS'));
	}
	$dbh->exec("set names utf8");
?>
