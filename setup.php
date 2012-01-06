<?php

	$protocol = "http://";
	if (isset($_SERVER['HTTPS'])) {
	   $protocol = "https://";
	}
	$web_root = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
?>