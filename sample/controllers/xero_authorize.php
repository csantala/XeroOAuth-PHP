<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Xero_authorize extends CI_Controller {
	
	function index()
	{
		$this->load->library('xero/Xero');
		$authorization = $this->xero->oauth();		
		if (isset($authorization)) {
			
			// save Xero access_token to session
			$this->session->set_userdata($authorization);

			redirect(site_url() . 'xero_demo');
		}
	}
}

/* End of file xero_authorize.php */
/* Location: ./application/controllers/xero_authorize.php */