<?php
	require('setup.php');
	require('lib/Xero.php');

	$xero = new Xero; 

	$authorization = $xero->oauth();
	
	if (isset($authorization)) {
		header('Location: ' . $web_root);
	}
?>