<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

require('OAuthSimple.php');

define("XRO_APP_TYPE", "Public");
define('BASE_PATH',realpath('.'));

class Xero {
	
	// different app method defaults
	private $xro_defaults = array( 'xero_url'     => 'https://api.xero.com/api.xro/2.0',
	                     'site'    => 'https://api.xero.com',
	                     'authorize_url'    => 'https://api.xero.com/oauth/Authorize',
	                     'signature_method'    => 'HMAC-SHA1');
	                     
	private $xro_private_defaults = array( 'xero_url'     => 'https://api.xero.com/api.xro/2.0',
	                     'site'    => 'https://api.xero.com',
	                     'authorize_url'    => 'https://api.xero.com/oauth/Authorize',
	                     'signature_method'    => 'RSA-SHA1');
	                     
	private $xro_partner_defaults = array( 'xero_url'     => 'https://api-partner.network.xero.com/api.xro/2.0',
	                     'site'    => 'https://api-partner.network.xero.com',
	                     'authorize_url'    => 'https://api.xero.com/oauth/Authorize',
	                     'accesstoken_url'    => 'https://api-partner.xero.com/oauth/AccessToken',
	                     'signature_method'    => 'RSA-SHA1');
	                     
	private $xro_partner_mac_defaults = array( 'xero_url'     => 'https://api-partner2.network.xero.com/api.xro/2.0',
	                     'site'    => 'https://api-partner2.network.xero.com',
	                     'authorize_url'    => 'https://api.xero.com/oauth/Authorize',
	                     'accesstoken_url'    => 'https://api-partner2.xero.com/oauth/AccessToken',
	                     'signature_method'    => 'RSA-SHA1');
	                     
	// standard Xero OAuth stuff
	private $xro_consumer_options = array( 'request_token_path'    => '/oauth/RequestToken',
	                     'access_token_path'    => '/oauth/AccessToken',
	                     'authorize_path'    => '/oauth/Authorize');
	
	// local
	//private $oauth_callback = 'http://ci.loc/xero_authorize';
	
	// production
	private $oauth_callback = 'https://ablitica.com/dev/codeigniter/xero_authorize';
	                       	 
	private $signatures = array(
		// local
		//'consumer_key' => 'FZ4FXWY66I5ZX6CBIEJWHYQW2HZQRJ',
		//'shared_secret' => 'OISWUWMJYIR1KSROLV15BCQH4BL8RZ',
		
		// production
		'consumer_key' => 'B8DNMTXCJAHO77QTIXVEPNKFTD41YG',
		'shared_secret' => 'WQPPQBPKH9F11XSAILZMQL71F6AEDH',
		// 'rsa_private_key' => BASE_PATH . '/certs/rq-partner-app-2-privatekey.pem',
		// 'rsa_public_key'	=> BASE_PATH . '/certs/rq-partner-app-2-publickey.cer'
	 );

	function oauth() {
	
		$oauthObject = new OAuthSimple();
		$output = 'Authorizing...';
		
		# Set some standard curl options....
		$options = $this->set_curl_options();
		                    
		switch (XRO_APP_TYPE) {
		    case "Private":
		        $xro_settings = $this->xro_private_defaults;
		        $_GET['oauth_verifier'] = 1;
		       	$_COOKIE['oauth_token_secret'] = $this->signatures['shared_secret'];
		       	$_GET['oauth_token'] =  $this->signatures['consumer_key'];
		        break;
		    case "Public":
		        $xro_settings = $this->xro_defaults;
		        break;
		    case "Partner":
		        $xro_settings = $this->xro_partner_defaults;
		        break;
		    case "Partner_Mac":
		        $xro_settings = $this->xro_partner_mac_defaults;
		        break;
		}
		
		// In step 3, a verifier will be submitted.  If it's not there, we must be
		// just starting out. Let's do step 1 then.
		if (!isset($_GET['oauth_verifier'])) {
		    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		    // Step 1: Get a Request Token
		    //
		    // Get a temporary request token to facilitate the user authorization 
		    // in step 2. We make a request to the OAuthGetRequestToken endpoint,
		    // submitting the scope of the access we need (in this case, all the 
		    // user's calendars) and also tell Google where to go once the token
		    // authorization on their side is finished.
		    //
		    $result = $oauthObject->sign(array(
		        'path' => $xro_settings['site'].$this->xro_consumer_options['request_token_path'],
		        'parameters' => array(
		        'scope' => $xro_settings['xero_url'],
		        'oauth_callback' => $this->oauth_callback,
		        'oauth_signature_method' => $xro_settings['signature_method']),
		        'signatures'=> $this->signatures));
		
		    // The above object generates a simple URL that includes a signature, the 
		    // needed parameters, and the web page that will handle our request.  I now
		    // "load" that web page into a string variable.
		    $ch = curl_init();
		    
			curl_setopt_array($ch, $options);
		
		    if(isset($_GET['debug'])){
		    	echo 'signed_url: ' . $result['signed_url'] . '<br/>';
		    }
		    
		    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		    $r = curl_exec($ch);
		    if(isset($_GET['debug'])){
		  	  echo 'CURL ERROR: ' . curl_error($ch) . '<br/>';
		    }
		
		    curl_close($ch);
		
			if(isset($_GET['debug'])){
		    	echo 'CURL RESULT: ' . print_r($r) . '<br/>';
		    }
		    // We parse the string for the request token and the matching token
		    // secret. Again, I'm not handling any errors and just plough ahead 
		    // assuming everything is hunky dory.
		    parse_str($r, $returned_items);
		    $request_token = $returned_items['oauth_token'];
		    $request_token_secret = $returned_items['oauth_token_secret'];
		
			if(isset($_GET['debug'])){
		    	echo 'request_token: ' . $request_token . '<br/>';
		    }
		    
		    // We will need the request token and secret after the authorization.
		    // Google will forward the request token, but not the secret.
		    // Set a cookie, so the secret will be available once we return to this page.
		    setcookie("oauth_token_secret", $request_token_secret, time()+1800);
		    //
		    //////////////////////////////////////////////////////////////////////
		    
		    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		    // Step 2: Authorize the Request Token
		    //
		    // Generate a URL for an authorization request, then redirect to that URL
		    // so the user can authorize our access request.  The user could also deny
		    // the request, so don't forget to add something to handle that case.
		    $result = $oauthObject->sign(array(
		        'path'      => $xro_settings['authorize_url'],
		        'parameters'=> array(
		        'oauth_token' => $request_token,
		        'oauth_signature_method' => $xro_settings['signature_method']),
		        'signatures'=> $this->signatures));
		
		    // See you in a sec in step 3.
		    if(isset($_GET['debug'])){
		    echo 'signed_url: ' . $result[signed_url];
		    }else{
		    header("Location:$result[signed_url]");
		    }
		    exit;
		    //////////////////////////////////////////////////////////////////////
		}
		else {
		    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		    // Step 3: Exchange the Authorized Request Token for an
		    //         Access Token.
		    //
		    // We just returned from the user authorization process on Google's site.
		    // The token returned is the same request token we got in step 1.  To 
		    // sign this exchange request, we also need the request token secret that
		    // we baked into a cookie earlier. 
		    //
		
		    // Fetch the cookie and amend our signature array with the request
		    // token and secret.
		    $this->signatures['oauth_secret'] = $_COOKIE['oauth_token_secret'];
		    $this->signatures['oauth_token'] = $_GET['oauth_token'];
		    
		    // only need to do this for non-private apps
		    if(XRO_APP_TYPE!='Private') {
				// Build the request-URL...
				$result = $oauthObject->sign(array(
					'path'		=> $xro_settings['site'].$this->xro_consumer_options['access_token_path'],
					'parameters'=> array(
					'oauth_verifier' => $_GET['oauth_verifier'],
					'oauth_token'	 => $_GET['oauth_token'],
					'oauth_signature_method' => $xro_settings['signature_method']),
					'signatures'=> $this->signatures));
			
				// ... and grab the resulting string again. 
				$ch = curl_init();
				curl_setopt_array($ch, $options);
				curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
				$r = curl_exec($ch);
		
				// Voila, we've got an access token.
				parse_str($r, $returned_items);		   
					$access_token = $returned_items['oauth_token'];
					$access_token_secret = $returned_items['oauth_token_secret'];
				//	$oauth_session_handle = $returned_items['oauth_session_handle'];
			} 
			else {
				$access_token = $this->signatures['oauth_token'];
				$access_token_secret = $this->signatures['oauth_secret'];
			}
				
		}
	    
	    $this->signatures['oauth_token'] = $access_token;
	    $this->signatures['oauth_secret'] = $access_token_secret;
	    //$this->signatures['oauth_session_handle'] = $oauth_session_handle;
	    //////////////////////////////////////////////////////////////////////
	
		return $this->signatures; 
		//$_SESSION['oauth_token'] = $access_token;
		//$_SESSION['oauth_secret'] = $access_token_secret;
				
	}
	
		
	function api_call($end_point, $id = null) {
			
		$options[CURLOPT_VERBOSE] = 1;
		$options[CURLOPT_RETURNTRANSFER] = 1;
		$options[CURLOPT_SSL_VERIFYHOST] = 0;
		$options[CURLOPT_SSL_VERIFYPEER] = 0;
		$useragent = (isset($useragent)) ? (empty($useragent) ? 'XeroOAuth-PHP' : $useragent) : 'XeroOAuth-PHP'; 
		$options[CURLOPT_USERAGENT] = $useragent;
				
		$ci=& get_instance(); 
		$oauthObject = new OAuthSimple();
		
		//ds($ci->session->userdata,1);
		$this->signatures['oauth_token'] = $ci->session->userdata['oauth_token'];
	    $this->signatures['oauth_secret'] = $ci->session->userdata['oauth_secret'];
	    //$signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
	    //////////////////////////////////////////////////////////////////////
	    
	    $xro_settings = $this->xro_defaults;
	    
	    // Xero API Access:
	    $oauthObject->reset();
	    $result = $oauthObject->sign(array(
	        'path' => $xro_settings['xero_url'].'/'.$end_point.'/'.$id,
	        'parameters' => array(
	    	//	'order' => urlencode($_REQUEST['order']),
				'oauth_signature_method' => $xro_settings['signature_method']),
	       		'signatures'=> $this->signatures
			));
		$ch = curl_init();
		curl_setopt_array($ch, $options);
	    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		$r = curl_exec($ch);
		curl_close($ch);
		
		parse_str($r, $returned_items);		   
		
		// error handling
		if (isset($returned_items['oauth_problem'])) {
			$ci->session->sess_destroy();
			// dump to secreen error and terminate.
			ds($r,1);
		}

		// all good return results from API
		return $r;

	}
		
	
	function api_put($data) {
		
		$options[CURLOPT_VERBOSE] = 1;
		$options[CURLOPT_RETURNTRANSFER] = 1;
		$options[CURLOPT_SSL_VERIFYHOST] = 0;
		$options[CURLOPT_SSL_VERIFYPEER] = 0;
		$useragent = (isset($useragent)) ? (empty($useragent) ? 'XeroOAuth-PHP' : $useragent) : 'XeroOAuth-PHP'; 
		$options[CURLOPT_USERAGENT] = $useragent;
				
		$ci=& get_instance(); 
		$oauthObject = new OAuthSimple();
		
		//ds($ci->session->userdata,1);
		$this->signatures['oauth_token'] = $ci->session->userdata['oauth_token'];
	    $this->signatures['oauth_secret'] = $ci->session->userdata['oauth_secret'];
	   // $signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
	    //////////////////////////////////////////////////////////////////////
	    
	    $xro_settings = $this->xro_defaults;
			
	    // Example Xero API PUT:
	    $oauthObject->reset();
	    $result = $oauthObject->sign(array(
	        'path'      => $xro_settings['xero_url'].'/Contacts/',
	        'action'	=> 'PUT',
	        'parameters'=> array(
			'oauth_signature_method' => $xro_settings['signature_method']),
	        'signatures'=> $this->signatures));
	        
	    $xml = "<Contact>
	<Name>".$data['name']."</Name>
	<FirstName>".$data['first_name']."</FirstName>
	<LastName>".$data['last_name']."</LastName>
	<EmailAddress>".$data['email']."</EmailAddress>
	<Phones>
		<Phone><PhoneType>MOBILE</PhoneType><PhoneNumber>".$data['mobile']."</PhoneNumber></Phone>
		<Phone><PhoneType>DEFAULT</PhoneType><PhoneNumber>".$data['landline']."</PhoneNumber></Phone>
		<Phone><PhoneType>DDI</PhoneType><PhoneNumber>".$data['alt']."</PhoneNumber></Phone>
	</Phones>
	<Addresses>
		<Address>
			<AddressType>STREET</AddressType>
			<AddressLine1>".$data['a_line_1']."</AddressLine1>
			<City>".$data['a_city']."</City>
			<Region>".$data['a_region']."</Region>
			<PostalCode>".$data['a_code']."</PostalCode>
			<Country>".$data['a_country']."</Country>
		</Address>
		<Address>
			<AddressType>POBOX</AddressType>
			<AddressLine1>".$data['ma_line_1']."</AddressLine1>
			<City>".$data['ma_city']."</City>
			<Region>".$data['ma_region']."</Region>
			<PostalCode>".$data['ma_code']."</PostalCode>
			<Country>".$data['ma_country']."</Country>
		</Address>
	</Addresses>
	</Contact>";
	
		$fh  = fopen('php://memory', 'w+');
		fwrite($fh, $xml);
		rewind($fh);
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, strlen($xml));
	    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		$r = curl_exec($ch);
		curl_close($ch);
		
		parse_str($r, $returned_items);
		
		// error handling
		if (isset($returned_items['oauth_problem'])) {
			$ci->session->sess_destroy();
			// dump to secreen error and terminate.
			ds($r,1);
		}

		// all good return results from API
		return $r;
	}
	
	// adds or updates a new client
	function api_post($data) {
		
		$options[CURLOPT_VERBOSE] = 1;
		$options[CURLOPT_RETURNTRANSFER] = 1;
		$options[CURLOPT_SSL_VERIFYHOST] = 0;
		$options[CURLOPT_SSL_VERIFYPEER] = 0;
		$useragent = (isset($useragent)) ? (empty($useragent) ? 'XeroOAuth-PHP' : $useragent) : 'XeroOAuth-PHP'; 
		$options[CURLOPT_USERAGENT] = $useragent;
				
		$ci=& get_instance(); 
		$oauthObject = new OAuthSimple();
		
		//ds($ci->session->userdata,1);
		$this->signatures['oauth_token'] = $ci->session->userdata['oauth_token'];
	    $this->signatures['oauth_secret'] = $ci->session->userdata['oauth_secret'];
	   // 	$this->signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
	    //////////////////////////////////////////////////////////////////////
	    
	       $xro_settings = $this->xro_defaults;
		
	    // update contact 
	    $xml = "<Contact>
	<Name>".$data['name']."</Name>
	<FirstName>".$data['first_name']."</FirstName>
	<LastName>".$data['last_name']."</LastName>
	<EmailAddress>".$data['email']."</EmailAddress>
	<Phones>
		<Phone><PhoneType>MOBILE</PhoneType><PhoneNumber>".$data['mobile']."</PhoneNumber></Phone>
		<Phone><PhoneType>DEFAULT</PhoneType><PhoneNumber>".$data['landline']."</PhoneNumber></Phone>
		<Phone><PhoneType>DDI</PhoneType><PhoneNumber>".$data['alt']."</PhoneNumber></Phone>
	</Phones>
	<Addresses>
		<Address>
			<AddressType>STREET</AddressType>
			<AddressLine1>".$data['a_line_1']."</AddressLine1>
			<City>".$data['a_city']."</City>
			<Region>".$data['a_region']."</Region>
			<PostalCode>".$data['a_code']."</PostalCode>
			<Country>".$data['a_country']."</Country>
		</Address>
		<Address>
			<AddressType>POSTBOX</AddressType>
			<AddressLine1>".$data['ma_line_1']."</AddressLine1>
			<City>".$data['ma_city']."</City>
			<Region>".$data['ma_region']."</Region>
			<PostalCode>".$data['ma_code']."</PostalCode>
			<Country>".$data['ma_country']."</Country>
		</Address>
	</Addresses>
	</Contact>";
	
	
	    $oauthObject->reset();
	    $result = $oauthObject->sign(array(
	        'path'      => $xro_settings['xero_url'].'/Contacts/',
	        'action'	=> 'POST',
	        'parameters'=> array(
			'oauth_signature_method' => $xro_settings['signature_method'],
			'xml' => $xml),
	        'signatures'=> $this->signatures));
	        
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		curl_setopt($ch, CURLOPT_POST, true);
		$post_body = urlencode($xml);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "xml=" . $post_body);
	
		$url = $result['signed_url'];
	    curl_setopt($ch, CURLOPT_URL, $url);
		$r = curl_exec($ch);
		curl_close($ch);
		
		parse_str($r, $returned_items);
		   
		// error handling
		if (isset($returned_items['oauth_problem'])) {
			$ci->session->sess_destroy();
			// dump to secreen error and terminate.
			ds($r,1);
		}

		// all good return results from API
		return $r;
	}
		
	function access_token_swap() {
		require 'XeroOAuth.php';
		require_once('_config.php');
		
		$options[CURLOPT_VERBOSE] = 1;
		$options[CURLOPT_RETURNTRANSFER] = 1;
		$options[CURLOPT_SSL_VERIFYHOST] = 0;
		$options[CURLOPT_SSL_VERIFYPEER] = 0;
		$useragent = (isset($useragent)) ? (empty($useragent) ? 'XeroOAuth-PHP' : $useragent) : 'XeroOAuth-PHP'; 
		$options[CURLOPT_USERAGENT] = $useragent;
				
		$xro_settings = $xro_defaults;
				
		$ci=& get_instance(); 
		$oauthObject = new OAuthSimple();
		
		$signatures['oauth_token'] = $ci->session->userdata['oauth_token'];
	    $signatures['oauth_secret'] = $ci->session->userdata['oauth_secret'];
	
			$oauthObject->reset();
	    	$result = $oauthObject->sign(array(
	        	'path'      => $xro_settings['site'].$this->xro_consumer_options['access_token_path'],
	        	'parameters'=> array(
	            'scope'         => $xro_settings['xero_url'],
	      //      'oauth_session_handle'	=> $signatures['oauth_session_handle'],
	            'oauth_token'	=> $signatures['oauth_token'],
	            'oauth_signature_method' => $xro_settings['signature_method']),
	        'signatures'=> $signatures));
		$ch = curl_init();
		curl_setopt_array($ch, $options);
	    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		$r = curl_exec($ch);
		parse_str($r, $returned_items);	
	  print_r($returned_items); die;
		$ci->session->userdata['oauth_token'] = $returned_items['oauth_token'];
		$ci->session->userdata['oauth_secret'] = $returned_items['oauth_token_secret'];
		//$_SESSION['oauth_session_handle']   = $returned_items['oauth_session_handle'];
		if($returned_items['oauth_token']){
	//		return $returned_items['oauth_token'];
		}
		curl_close($ch);
	}

	protected function set_curl_options() {
		$options[CURLOPT_VERBOSE] = 1;
    	$options[CURLOPT_RETURNTRANSFER] = 1;
    	$options[CURLOPT_SSL_VERIFYHOST] = 0;
    	$options[CURLOPT_SSL_VERIFYPEER] = 0;
    	$useragent = (isset($useragent)) ? (empty($useragent) ? 'XeroOAuth-PHP' : $useragent) : 'XeroOAuth-PHP'; 
    	$options[CURLOPT_USERAGENT] = $useragent;
		return $options;
	}
}
	
/* End of file Xero.php */