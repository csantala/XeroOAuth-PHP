<?php
 /**
  * Xero.php
  * 
  * A Xero API authentication and usage library.
  * 
  * Constructed from code by Ronan Quirke
  * @link https://github.com/XeroAPI/XeroOAuth-PHP 
  *
  * @author Chris Santala <csantala@gmail.com>
  * @author Ronan Quirke
  *
  * DISCLAIMER OF WARRANTY
  * 
  * This source code is provided "as is" and without warranties as to performance or merchantability. 
  * The author and/or distributors of this source code may have made statements about this source code.
  * Any such statements do not constitute warranties and shall not be relied on by the user in deciding whether to use this source code.
  * This source code is provided without any express or implied warranties whatsoever. 
  * Because of the diversity of conditions and hardware under which this source code may be used, no warranty of fitness for a particular purpose is offered.
  * The user is advised to test the source code thoroughly before relying on it. The user must assume the entire risk of using the source code. Have fun.
  */

require('OAuthSimple.php');
require('Xro_config.php');
session_start();

define('BASE_PATH',realpath('.'));

class Xero extends Xro_config {
	
	// different app method defaults
	protected $xro_defaults = array('xero_url' => 'https://api.xero.com/api.xro/2.0',
		'site' => 'https://api.xero.com',
		'authorize_url' => 'https://api.xero.com/oauth/Authorize',
		'signature_method' => 'HMAC-SHA1');
	                     
	protected $xro_private_defaults = array('xero_url' => 'https://api.xero.com/api.xro/2.0',
		'site' => 'https://api.xero.com',
		'authorize_url' => 'https://api.xero.com/oauth/Authorize',
		'signature_method' => 'RSA-SHA1');
	                     
	protected $xro_partner_defaults = array( 'xero_url' => 'https://api-partner.network.xero.com/api.xro/2.0',
		'site' => 'https://api-partner.network.xero.com',
		'authorize_url' => 'https://api.xero.com/oauth/Authorize',
		'accesstoken_url' => 'https://api-partner.xero.com/oauth/AccessToken',
		'signature_method' => 'RSA-SHA1');
	                     
	protected $xro_partner_mac_defaults = array('xero_url' => 'https://api-partner2.network.xero.com/api.xro/2.0',
		'site' => 'https://api-partner2.network.xero.com',
		'authorize_url' => 'https://api.xero.com/oauth/Authorize',
		'accesstoken_url' => 'https://api-partner2.xero.com/oauth/AccessToken',
		'signature_method' => 'RSA-SHA1');
	                     
	// standard Xero OAuth stuff
	protected $xro_consumer_options = array( 'request_token_path' => '/oauth/RequestToken',
		'access_token_path' => '/oauth/AccessToken',
		'authorize_path' => '/oauth/Authorize');
									 
	function __construct() {
		
		switch ($this->xro_app_type) {
		case "Private":
		    $this->xro_settings = $this->xro_private_defaults;
		    $_GET['oauth_verifier'] = 1;
		   	$_COOKIE['oauth_token_secret'] = $this->signatures['shared_secret'];
		   	$_GET['oauth_token'] =  $this->signatures['consumer_key'];
		    break;
		case "Public":
		    $this->xro_settings = $this->xro_defaults;
		    break;
		case "Partner":
		    $this->xro_settings = $this->xro_partner_defaults;
		    break;
		case "Partner_Mac":
		    $this->xro_settings = $this->xro_partner_mac_defaults;
		    break;
		}
	}

	function oauth() {

		$oauthObject = new OAuthSimple();
		$output = 'Authorizing...';
		
		# Set some standard curl options....
		$options = $this->set_curl_options();
		                    
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
		        'path' => $this->xro_settings['site'].$this->xro_consumer_options['request_token_path'],
		        'parameters' => array(
		        'scope' => $this->xro_settings['xero_url'],
		        'oauth_callback' => $this->oauth_callback,
		        'oauth_signature_method' => $this->xro_settings['signature_method']),
		        'signatures'=> $this->signatures));

		    // The above object generates a simple URL that includes a signature, the 
		    // needed parameters, and the web page that will handle our request.  I now
		    // "load" that web page into a string variable.
		    $ch = curl_init();
		    
			curl_setopt_array($ch, $options);
		
		   if($this->debug){
		    	echo 'signed_url: ' . $result['signed_url'] . '<br/>';
		   }
		
		    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		    $r = curl_exec($ch);
		    if($this->debug){
		  	  echo 'CURL ERROR: ' . curl_error($ch) . '<br/>';
		    }
		
		    curl_close($ch);
		
			if($this->debug){
		    	echo 'CURL RESULT: ' . print_r($r) . '<br/>';
		    }
		    
		    // We parse the string for the request token and the matching token
		    // secret. Again, I'm not handling any errors and just plough ahead 
		    // assuming everything is hunky dory.
		    parse_str($r, $returned_items);
		    $request_token = $returned_items['oauth_token'];
		    $request_token_secret = $returned_items['oauth_token_secret'];
		
			if($this->debug){
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
		        'path' => $this->xro_settings['authorize_url'],
		        'parameters' => array(
		        'oauth_token' => $request_token,
		        'oauth_signature_method' => $this->xro_settings['signature_method']),
		        'signatures' => $this->signatures));
		
		    // See you in a sec in step 3.
		    if($this->debug){
		  	  echo 'signed_url: ' . $result['signed_url'];
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
		    if($this->xro_app_type != 'Private') {
				// Build the request-URL...
				$result = $oauthObject->sign(array(
					'path' => $this->xro_settings['site'].$this->xro_consumer_options['access_token_path'],
					'parameters' => array(
					'oauth_verifier' => $_GET['oauth_verifier'],
					'oauth_token' => $_GET['oauth_token'],
					'oauth_signature_method' => $this->xro_settings['signature_method']),
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
				// $oauth_session_handle = $returned_items['oauth_session_handle'];
			} 
			else {
				$access_token = $this->signatures['oauth_token'];
				$access_token_secret = $this->signatures['oauth_secret'];
			}
				
		}
	    
	    $this->signatures['oauth_token'] = $access_token;
	    $this->signatures['oauth_secret'] = $access_token_secret;
	    if ($this->xro_app_type =! "Public") {
			$this->signatures['oauth_session_handle'] = $oauth_session_handle;
		}
	    //////////////////////////////////////////////////////////////////////
	    
	    $_SESSION['access_token'] = $access_token;
		$_SESSION['access_token_secret'] = $access_token_secret;
		$_SESSION['oauth_session_handle'] = $access_token_secret;
	    
		return $this->signatures; 
				
	}
	
	function api_call($end_point, $id = NULL) {
			
		# Set some standard curl options....
		$options = $this->set_curl_options();
				
		$oauthObject = new OAuthSimple();
		
		$this->signatures['oauth_token'] = $_SESSION['access_token'];
	    $this->signatures['oauth_secret'] = $_SESSION['access_token_secret'];
	    if ($this->xro_app_type =! "Public") {
	     	$this->signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
		 }
	    //////////////////////////////////////////////////////////////////////

	    // Xero API Access:
	    $oauthObject->reset();
	    $result = $oauthObject->sign(array(
	        'path' => $this->xro_settings['xero_url'].'/'.$end_point.'/'.$id,
	        'parameters' => array(
	    	//   'order' => urlencode(),
				'oauth_signature_method' => $this->xro_settings['signature_method']),
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
			// check for expired access token and renew (partner app only)
		    // then re-call API
			if ($this->xro_app_type == "Partner" && $returned_items['oauth_problem'] == 'token_expired') {
				$this->renew_access_token();
				// this is probably a bad idea
				$r = $this->api_call($end_point, $id);
			}
			else {
				// dump to screen error and terminate.
				session_unset(); 
				echo "<pre>"; print_r($returned_items); echo "</pre>"; exit;
			}
		}

		// all good return results from API
		return $r;

	}
		
	
	function api_put($data) {
		
		# Set some standard curl options....
		$options = $this->set_curl_options();
				
		$oauthObject = new OAuthSimple();
		
		$this->signatures['oauth_token'] = $_SESSION['access_token'];
	    $this->signatures['oauth_secret'] = $_SESSION['access_token_secret'];
	    if ($this->xro_app_type =! "Public") {
	     	$this->signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
		 }
		else {
			$this->signatures['oauth_session_handle'] = NULL;
		} 
	    //////////////////////////////////////////////////////////////////////
			
	    // Xero API PUT:
	    $oauthObject->reset();
	    $result = $oauthObject->sign(array(
	        'path' => $this->xro_settings['xero_url'].'/Contacts/',
	        'action' => 'PUT',
	        'parameters'=> array(
			'oauth_signature_method' => $this->xro_settings['signature_method']),
	        'signatures'=> $this->signatures));
	    
		// generate contact xml
		$xml = $this->contact_xml($data);
	
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
			// check for expired access token and renew (partner app only)
		    // then re-call API
			if ($this->xro_app_type == "partner" && $returned_items['oauth_problem'] == 'token_expired') {
				$this->renew_access_token();
				// this is probably a bad idea
				$r = $this->api_put($data);
			}
			else {
				// dump to secreen error and terminate.
				session_unset(); 
				echo "<pre>"; print_r($returned_items); echo "</pre>"; exit;
			}
		}

		// all good return results from API
		return $r;
	}
	
	function api_post($data) {
		
		# Set some standard curl options....
		$options = $this->set_curl_options();
				
		$oauthObject = new OAuthSimple();

		$this->signatures['oauth_token'] = $_SESSION['access_token'];
	    $this->signatures['oauth_secret'] = $_SESSION['access_token_secret'];
	    if ($this->xro_app_type =! "Public") {
	     	$this->signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
		 }
		else $this->signatures['oauth_session_handle'] = NULL;
	    //////////////////////////////////////////////////////////////////////

	    // update contact 
	    $xml = $this->contact_xml($data);
	
	    $oauthObject->reset();
	    $result = $oauthObject->sign(array(
	        'path' => $this->xro_settings['xero_url'].'/Contacts/',
	        'action' => 'POST',
	        'parameters'=> array(
			'oauth_signature_method' => $this->xro_settings['signature_method'],
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
			// check for expired access token and renew (partner app only)
		    // then re-call API
			if ($this->xro_app_type == "Partner" && $returned_items['oauth_problem'] == 'token_expired') {
				$this->renew_access_token();
				// this is probably a bad idea
				$r = $this->api_post($data);
			}
			else {
				// dump to screen error and terminate.
				session_unset(); 
				echo "<pre>"; print_r($returned_items); echo "</pre>"; exit;
			}
		}

		// all good return results from API
		return $r;
	}
	
	function renew_access_token() {
		
		# Set some standard curl options....
		$options = $this->set_curl_options();
		
		$this->signatures['oauth_token'] = $_SESSION['access_token'];
	    $this->signatures['oauth_secret'] = $_SESSION['access_token_secret'];
	    if ($this->xro_app_type =! "Public") {
	     	$this->signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
		 }
		else $this->signatures['oauth_session_handle'] = NULL;
		
		$oauthObject = new OAuthSimple();
	
		$oauthObject->reset();
    	$result = $oauthObject->sign(array(
        	'path' => $this->xro_settings['site'].$this->xro_consumer_options['access_token_path'],
        	'parameters'=> array(
            'scope' => $this->xro_settings['xero_url'],
            'oauth_session_handle' => $this->signatures['oauth_session_handle'],
            'oauth_token' => $this->signatures['oauth_token'],
            'oauth_signature_method' => $this->xro_settings['signature_method']),
        'signatures'=> $this->signatures));
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
	    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		$r = curl_exec($ch);
		parse_str($r, $returned_items);	

		// pop new access token into session
		$_SESSION['access_token'] = $returned_items['oauth_token'];
		$_SESSION['access_token_secret'] = $returned_items['oauth_token_secret'];
		$_SESSION['oauth_session_handle'] = $returned_items['oauth_session_handle'];	   

		curl_close($ch);
	}

	protected function set_curl_options() {
		
		if ($this->xro_app_type == "Partner") {
			$options[CURLOPT_SSLCERT] = '/[path]/entrust-cert.pem';
			$options[CURLOPT_SSLKEYPASSWD] = '[password]'; 
			$options[CURLOPT_SSLKEY] = '/[path]/entrust-private.pem';
		}
		$options[CURLOPT_VERBOSE] = 1;
    	$options[CURLOPT_RETURNTRANSFER] = 1;
    	$options[CURLOPT_SSL_VERIFYHOST] = 0;
    	$options[CURLOPT_SSL_VERIFYPEER] = 0;
    	$useragent = (isset($useragent)) ? (empty($useragent) ? 'XeroOAuth-PHP' : $useragent) : 'XeroOAuth-PHP'; 
    	$options[CURLOPT_USERAGENT] = $useragent;
		return $options;
	}
	
	// partial contact xml 
	// see http://blog.xero.com/developer/api/Contacts/ for full set
	private function contact_xml($data) {
		return "<Contact>
<Name>".$data['name']."</Name>
<FirstName>".$data['first_name']."</FirstName>
<LastName>".$data['last_name']."</LastName>
<EmailAddress>".$data['email']."</EmailAddress>
</Contact>";
	}

}

?>