<?php
/**
 * Sharif Judge online judge
 * @file Moss.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Moss extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		if ($this->user->level <= 1) // permission denied
			show_404();
	}


	// ------------------------------------------------------------------------


	public function index($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		$this->form_validation->set_rules('detect', 'detect', 'required');
		if ($this->form_validation->run())
		{
			if ($this->input->post('detect') !== 'detect')
				exit;
			$this->_detect($assignment_id);
		}
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'moss_userid' => $this->settings_model->get_setting('moss_userid'),
			'moss_assignment' => $this->assignment_model->assignment_info($assignment_id),
			'update_time' => $this->assignment_model->get_moss_time($assignment_id)
		);

		$data['moss_problems'] = array();
		$assignments_path = rtrim($this->settings_model->get_setting('assignments_root'), '/');
		for($i=1; $i<=$data['moss_assignment']['problems']; $i++){
			$data['moss_problems'][$i] = NULL;
			$path = $assignments_path."/assignment_{$assignment_id}/p{$i}/moss_link.txt";
			if (file_exists($path))
				$data['moss_problems'][$i] = file_get_contents($path);
		}

		$this->twig->display('pages/admin/moss.twig', $data);
	}


	// ------------------------------------------------------------------------


	public function update($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		$userid = $this->input->post('moss_userid');
		$this->settings_model->set_setting('moss_userid', $userid);
		$moss_original = trim( file_get_contents(rtrim($this->settings_model->get_setting('tester_path'), '/').'/moss_original') );
		$moss_path = rtrim($this->settings_model->get_setting('tester_path'), '/').'/moss';
		file_put_contents($moss_path, str_replace('MOSS_USER_ID', $userid, $moss_original));
		shell_exec("chmod +x {$moss_path}");
		$this->index($assignment_id);
	}


	// ------------------------------------------------------------------------


	private function _detect($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		$this->load->model('submit_model');
		$assignments_path = rtrim($this->settings_model->get_setting('assignments_root'), '/');
		$tester_path = rtrim($this->settings_model->get_setting('tester_path'), '/');
		shell_exec("chmod +x {$tester_path}/moss");
		$items = $this->submit_model->get_final_submissions($assignment_id, $this->user->level, $this->user->username);
		$groups = array();
		foreach ($items as $item) {
			if (!isset($groups[$item['problem']]))
				$groups[$item['problem']] = array($item);
			else
				array_push($groups[$item['problem']], $item);
		}
		foreach ($groups as $problem_id => $group) {
			$list = '';
			$assignment_path = $assignments_path."/assignment_{$assignment_id}";
			foreach ($group as $item)
				if ($item['file_type'] !== 'zip' && $item['file_type'] !== 'pdf')
					$list .= "p{$problem_id}/{$item['username']}/{$item['file_name']}".'.'.filetype_to_extension($item['file_type']). " ";
			shell_exec("list='$list'; cd $assignment_path; $tester_path/moss \$list | grep http >p{$problem_id}/moss_link.txt;");
		}
		$this->assignment_model->set_moss_time($assignment_id);
	}


}