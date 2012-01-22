<?php
	
	class Xro_config
	{
		
		protected $xro_app_type = "Public";
		
		// local
		protected $oauth_callback = '';
		
		// staging
		//protected $oauth_callback = '';
		
		// production
		//protected $oauth_callback = '';
		                       	 
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
			// 'rsa_private_key' => BASE_PATH . '/certs/rq-partner-app-2-privatekey.pem',
			// 'rsa_public_key'	=> BASE_PATH . '/certs/rq-partner-app-2-publickey.cer'
		 );
	}