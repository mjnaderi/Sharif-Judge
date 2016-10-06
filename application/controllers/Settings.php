<?php
/**
 * Sharif Judge online judge
 * @file Settings.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller
{

	private $form_status;
	private $errors;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		if ( $this->user->level <= 2) // permission denied
			show_404();
		$this->form_status = '';
		$this->errors = array();
	}


	// ------------------------------------------------------------------------


	public function index()
	{
		$settings = $this->settings_model->get_all_settings();
		$data = array_merge($settings,
			array(
				'all_assignments' => $this->assignment_model->all_assignments(),
				'sandbox_built' => file_exists(rtrim($settings['tester_path'], '/').'/easysandbox/EasySandbox.so'),
				'form_status' => $this->form_status,
				'errors' => $this->errors
			)
		);
		ob_start();
		$data ['defc'] = file_get_contents(rtrim($settings['tester_path'], '/').'/shield/defc.h');
		$data ['defcpp'] = file_get_contents(rtrim($settings['tester_path'], '/').'/shield/defcpp.h');
		$data ['shield_py2'] = file_get_contents(rtrim($settings['tester_path'], '/').'/shield/shield_py2.py');
		$data ['shield_py3'] = file_get_contents(rtrim($settings['tester_path'], '/').'/shield/shield_py3.py');
		ob_end_clean();
		$this->twig->display('pages/admin/settings.twig', $data);
	}


	// ------------------------------------------------------------------------


	public function update()
	{
		$this->form_validation->set_rules('timezone', 'timezone', 'required');
		$this->form_validation->set_rules('file_size_limit', 'File size limit', 'integer|greater_than_equal_to[0]');
		$this->form_validation->set_rules('output_size_limit', 'Output size limit', 'integer|greater_than_equal_to[0]');
		$this->form_validation->set_rules('rpp_all', 'results per page (all submissions)', 'integer|greater_than_equal_to[0]');
		$this->form_validation->set_rules('rpp_final', 'results per page (final submissions)', 'integer|greater_than_equal_to[0]');
		$this->form_validation->set_rules('mail_from', 'email', 'valid_email');
		if($this->form_validation->run()){
			ob_start();
			$this->form_status = 'ok';
			$tester_path = rtrim($this->settings_model->get_setting('tester_path'), '/');
			$defc_path = $tester_path.'/shield/defc.h';
			$defcpp_path = $tester_path.'/shield/defcpp.h';
			$shpy2_path = $tester_path.'/shield/shield_py2.py';
			$shpy3_path = $tester_path.'/shield/shield_py3.py';
			if ($this->input->post('def_c') !== file_get_contents($defc_path))
				if (file_exists($defc_path) && file_put_contents($defc_path,$this->input->post('def_c')) === FALSE)
					array_push($this->errors, 'File defc.h is not writable. Edit it manually.');
			if ($this->input->post('def_cpp') !== file_get_contents($defcpp_path))
				if (file_exists($defcpp_path) && file_put_contents($defcpp_path,$this->input->post('def_cpp')) === FALSE)
					array_push($this->errors, 'File defcpp.h is not writable. Edit it manually.');
			if ($this->input->post('shield_py2') !== file_get_contents($shpy2_path))
				if (file_exists($shpy2_path) && file_put_contents($shpy2_path,$this->input->post('shield_py2')) === FALSE)
					array_push($this->errors, 'File shield_py2.py is not writable. Edit it manually.');
			if ($this->input->post('shield_py3') !== file_get_contents($shpy3_path))
				if (file_exists($shpy3_path) && file_put_contents($shpy3_path,$this->input->post('shield_py3')) === FALSE)
					array_push($this->errors, 'File shield_py3.py is not writable. Edit it manually.');
			ob_end_clean();
			$timezone = $this->input->post('timezone');
			// if timezone is invalid, set it to 'Asia/Tehran' :
			if ( ! in_array($timezone, DateTimeZone::listIdentifiers()) )
				$timezone='Asia/Tehran';

			$this->settings_model->set_settings(
				array(
					'timezone' => $timezone,
					'tester_path' => $this->input->post('tester_path'),
					'assignments_root' => $this->input->post('assignments_root'),
					'file_size_limit' => $this->input->post('file_size_limit'),
					'output_size_limit' => $this->input->post('output_size_limit'),
					'default_late_rule' => $this->input->post('default_late_rule'),
					'enable_easysandbox' => $this->input->post('enable_easysandbox')===NULL?0:1,
					'enable_c_shield' => $this->input->post('enable_c_shield')===NULL?0:1,
					'enable_cpp_shield' => $this->input->post('enable_cpp_shield')===NULL?0:1,
					'enable_py2_shield' => $this->input->post('enable_py2_shield')===NULL?0:1,
					'enable_py3_shield' => $this->input->post('enable_py3_shield')===NULL?0:1,
					'enable_java_policy' => $this->input->post('enable_java_policy')===NULL?0:1,
					'enable_log' => $this->input->post('enable_log')===NULL?0:1,
					'enable_registration' => $this->input->post('enable_registration')===NULL?0:1,
					'registration_code' => $this->input->post('registration_code'),
					'mail_from' => $this->input->post('mail_from'),
					'mail_from_name' => $this->input->post('mail_from_name'),
					'reset_password_mail' => $this->input->post('reset_password_mail'),
					'add_user_mail' => $this->input->post('add_user_mail'),
					'results_per_page_all' => $this->input->post('rpp_all'),
					'results_per_page_final' => $this->input->post('rpp_final'),
					'week_start' => $this->input->post('week_start'),
				)
			);
			
		}
		else
			$this->form_status = 'error';
		$this->index();
	}


}