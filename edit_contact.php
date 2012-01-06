<?php

	include('lib/Xero.php');
	include('setup.php');
	
	$xero = new Xero;
	
	function check_for_exception($client_data) {
		// if this data structure is not returned then we have an exception; dump exception and exit.
		if (! isset($client_data->Contacts->Contact->ContactID)) {
			echo "<pre>"; print_r($client_data); echo "</pre>"; exit;
		}
		else {
			// all good
			$id = $client_data->Contacts->Contact->ContactID;
			return $id;
		}
	}
	
	// when receiving id from list_contacts.php
	if (isset($_GET['id'])) {
		$id = $_GET['id'];
		$label = 'Update Client';
		$method = 'edit';
		
	}
	else {
		$label = 'Add Client';
		$method = 'add';
	}
	
	// when form posts to itself
	if (isset($_POST['method'])) {
		// when adding a new client
		if ($_POST['method'] == 'add') {
			
			// post new contact to Xero
			// if contact name is same as existing contact name then an error will be returned by the library
			$client_data = simplexml_load_string($xero->api_put($_POST));
			
			$id = check_for_exception($client_data);
	
			$new_client = true;
			$label = 'Update Client';
			$method = 'edit';
		}
		elseif ($_POST['method'] == 'edit') {
			// when editing a client
			$client_data = simplexml_load_string($xero->api_post($_POST));
			
			$id = check_for_exception($client_data);
			
			$edited_client = true;
			$label = 'Update Client';
			$method = 'edit';
		}

	}
	
	// get contact from xero if we have an id
	if (isset($id)) {
		// get contact data for form
		$contactData = simplexml_load_string($xero->api_call('contacts', $id));
		$contact = $contactData->Contacts->Contact;
	}
	
	
	
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="<?php echo $web_root?>/css/style.css" />
	<title><?php echo $label ?></title>
</head>
<body>
	<div id="container">
		<h1><?php echo $label ?></h1>
		<p style="margin:15px"><a href="<?php echo $web_root?>/">home</a></p>
	
		<div id="body">
			<?php if (isset($new_client)):?>
				<p>New Client added: <b><?php echo $contact->Name ?></b></p>
			<?php endif ?>
			<?php if (isset($edited_client)):?>
				<p>Client edited: <b><?php echo $contact->Name ?></b></p>
			<?php endif ?>
				
			<form action="<?php echo $web_root . '/' . 'edit_contact.php'?>" method="post">
				<p>
					<label>Name</label><br>
					<input type="text" name="name" value="<?=isset($contact->Name) ? $contact->Name : ''?>">
				</p>
				<p>
					<label>First Name</label><br>
					<input type="text" name="first_name" value="<?=isset($contact->FirstName) ? $contact->FirstName : ''?>">
				</p>
				<p>
					<label>Last Name</label><br>
					<input type="text" name="last_name" value="<?=isset($contact->LastName) ? $contact->LastName : ''?>">
				</p>
				<p>
					<label>email</label><br>
					<input type="text" name="email" value="<?=isset($contact->EmailAddress) ? $contact->EmailAddress : ''?>">
				</p>
	
				<p>
					<input type="hidden" name="method" value="<?php echo $method?>">
					<input type="submit" value="<?php echo $label?>">
				</p>	
		</div>
	</div>
</body>
</html>