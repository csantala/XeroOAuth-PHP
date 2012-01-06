<?php
	include('setup.php');
	include('lib/Xero.php');

	$xero = new Xero; 
	
	$contactsData = simplexml_load_string($xero->api_call('contacts'));
	$contacts = $contactsData->Contacts->Contact;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="<?php echo $web_root?>/css/style.css" />
	<title>Xero Library</title>
</head>
<body>
	<div id="container">
		<h1>Xero Contacts</h1>
		<p style="margin:15px"><a href="<?php echo $web_root?>">home</a></p>
	
		<div id="body">	
			<p>
				<table>
		<?php foreach($contacts as $contact): ?>
					<tr>
						<td><a href="<?php echo $web_root?>/edit_contact.php?&id=<?php echo $contact->ContactID; ?>">edit</a></td>
					 	<td><?php echo $contact->Name ?></td>
					 	<td><?php echo $contact->lastName ?></td>
					 	<td><?php echo $contact->EdmailAddress ?></td>
					</tr>
		<?php endforeach ?>
				</table>
			</p>	
		</div>
	</div>
</body>
</html>