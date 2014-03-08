<?php
/**
 * Sharif Judge online judge
 * @file Login.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	/**
	 * checks whether the entered registration code is correct or not
	 *
	 */
	public function _registration_code($code){
		$rc = $this->settings_model->get_setting('registration_code');
		if ($rc == '0')
			return TRUE;
		if ($rc == $code)
			return TRUE;
		return FALSE;
	}


	// ------------------------------------------------------------------------


	/**
	 * Login
	 */
	public function index()
	{
		if ($this->session->userdata('logged_in')) // if logged in
			redirect('dashboard');
		$this->form_validation->set_rules('username', 'Username', 'required|min_length[3]|max_length[20]|alpha_numeric|lowercase');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|max_length[200]');
		$data = array(
			'error' => FALSE,
			'registration_enabled' => $this->settings_model->get_setting('enable_registration'),
		);

		if($this->form_validation->run()){
			$username = $this->input->post('username');
			$password = $this->input->post('password');
			if($this->user_model->validate_user($username, $password)){
				// setting the session and redirecting to dashboard:
				$login_data = array(
					'username'  => $username,
					'logged_in' => TRUE
				);
				$this->session->set_userdata($login_data);
				$this->user_model->update_login_time($username);
				redirect('/');
			}
			else
				// for displaying error message in 'pages/authentication/login' view
				$data['error'] = TRUE;
		}

		$this->twig->display('pages/authentication/login.twig', $data);
	}


	// ------------------------------------------------------------------------


	public function register()
	{
		if ($this->session->userdata('logged_in')) // if logged in
			redirect('dashboard');
		if ( ! $this->settings_model->get_setting('enable_registration'))
			show_error('Registration is closed.');
		$this->form_validation->set_rules('registration_code', 'registration code', 'callback__registration_code', array('_registration_code' => 'Invalid %s'));
		$this->form_validation->set_rules('username', 'username', 'required|min_length[3]|max_length[20]|alpha_numeric|lowercase|is_unique[users.username]', array('is_unique' => 'This %s already exists.'));
		$this->form_validation->set_rules('email', 'email address', 'required|max_length[40]|valid_email|lowercase|is_unique[users.email]', array('is_unique' => 'This %s already exists.'));
		$this->form_validation->set_rules('password', 'password', 'required|min_length[6]|max_length[200]');
		$this->form_validation->set_rules('password_again', 'password confirmation', 'required|matches[password]');
		$data = array(
			'registration_code_required' => $this->settings_model->get_setting('registration_code')=='0'?FALSE:TRUE
		);
		if ($this->form_validation->run()){
			$this->user_model->add_user(
				$this->input->post('username'),
				$this->input->post('email'),
				$this->input->post('password'),
				'student'
			);
			$this->twig->display('pages/authentication/register_success.twig');
		}
		else
			$this->twig->display('pages/authentication/register.twig', $data);

	}


	// ------------------------------------------------------------------------


	/**
	 * Logs out and redirects to login page
	 */
	public function logout()
	{
		$this->session->sess_destroy();
		redirect('login');
	}


	// ------------------------------------------------------------------------


	public function lost()
	{
		if ($this->session->userdata('logged_in')) // if logged in
			redirect('dashboard');
		$this->form_validation->set_rules('email', 'email', 'required|max_length[40]|lowercase|valid_email');
		$data = array(
			'sent' => FALSE
		);
		if ($this->form_validation->run())
		{
			$this->user_model->send_password_reset_mail($this->input->post('email'));
			$data['sent'] = TRUE;
		}

		$this->twig->display('pages/authentication/lost.twig', $data);
	}


	// ------------------------------------------------------------------------


	public function reset($passchange_key = FALSE)
	{
		if ($passchange_key === FALSE)
			show_404();
		$result = $this->user_model->passchange_is_valid($passchange_key);
		if ($result !== TRUE)
			show_error($result);
		$this->form_validation->set_rules('password', 'password', 'required|min_length[6]|max_length[200]');
		$this->form_validation->set_rules('password_again', 'password confirmation', 'required|matches[password]');
		$data = array(
			'key' => $passchange_key,
			'result' => $result,
			'reset' => FALSE
		);
		if ($this->form_validation->run()){
			$this->user_model->reset_password($passchange_key, $this->input->post('password'));
			$data['reset'] = TRUE;
		}

		$this->twig->display('pages/authentication/reset_password.twig', $data);
	}



}
