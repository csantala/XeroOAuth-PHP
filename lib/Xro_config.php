<?php
	
	class Xro_config
	{	
		protected $debug = FALSE;
		
		// public, partner, or private
		protected $xro_app_type = ""; 
		
		// local
		protected $oauth_callback = '';
		
		// staging
		//protected $oauth_callback = '';
	
		// production
		//private $oauth_callback = '';
		                       	 
		protected $signatures = array(
			// local
			'consumer_key' => '',
			'shared_secret' => '',
			
			// staging
			//'consumer_key' => '',
			//'shared_secret' => '',			
				
			// production
			//'consumer_key' => '',
			//'shared_secret' => '',
			// 'rsa_private_key' => '/[path]/[privatekey].pem',
			// 'rsa_public_key'	=> '/[path]/[publickey].cer'
		 );
	}
?>