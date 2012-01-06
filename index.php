<?php 
	require('setup.php');
	session_start();
	if (isset($_GET['logoff'])) {
		session_unset();
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Xero Library</title>
	<link rel="stylesheet" type="text/css" href="<?=$web_root?>/css/style.css" />
</head>
<body>
	<div id="container">
		<h1>Xero PHP Library Demo</h1>
		<div id="body">
			<?php if (isset($_SESSION['access_token'])): ?>
				<p><a href="<?php echo $web_root?>/list_contacts.php">List Contacts</a></p>
				<p><a href="<?php echo $web_root?>/edit_contact.php?method=add">Add Contact</a></p>
				<p><a href="<?php echo $web_root?>?logoff=true">Logoff Xero</a></p>
			<?php else: ?>
				<p><a href="<? echo $web_root?>/authorise.php"><img src="<?php echo $web_root?>/images/connect_xero_button_blue.png" border="0"></a></p>
			<?php endif ?>
		</div>
	</div>
</body>
</html>