<?php
/**
 * Sharif Judge online judge
 * @file Problems.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problems extends CI_Controller
{

	private $all_assignments;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');

		$this->all_assignments = $this->assignment_model->all_assignments();
	}


	// ------------------------------------------------------------------------


	/**
	 * Displays detail description of given problem
	 *
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function index($assignment_id = NULL, $problem_id = 1)
	{

		// If no assignment is given, use selected assignment
		if ($assignment_id === NULL)
			$assignment_id = $this->user->selected_assignment['id'];
		if ($assignment_id == 0)
			show_error('No assignment selected.');

		$assignment = $this->assignment_model->assignment_info($assignment_id);

		$data = array(
			'all_assignments' => $this->all_assignments,
			'all_problems' => $this->assignment_model->all_problems($assignment_id),
			'description_assignment' => $assignment,
			'can_submit' => TRUE,
		);

		if ( ! is_numeric($problem_id) || $problem_id < 1 || $problem_id > $data['description_assignment']['problems'])
			show_404();

		$languages = explode(',',$data['all_problems'][$problem_id]['allowed_languages']);

		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');
		$problem_dir = "$assignments_root/assignment_{$assignment_id}/p{$problem_id}";
		$data['problem'] = array(
			'id' => $problem_id,
			'description' => '<p>Description not found</p>',
			'allowed_languages' => $languages,
			'has_pdf' => glob("$problem_dir/*.pdf") != FALSE
		);

		$path = "$problem_dir/desc.html";
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);

		if ( $assignment['id'] == 0
			OR ( $this->user->level == 0 && ! $assignment['open'] )
			OR shj_now() < strtotime($assignment['start_time'])
			OR shj_now() > strtotime($assignment['finish_time'])+$assignment['extra_time'] // deadline = finish_time + extra_time
			OR ! $this->assignment_model->is_participant($assignment['participants'], $this->user->username)
		)
			$data['can_submit'] = FALSE;

		$this->twig->display('pages/problems.twig', $data);
	}


	// ------------------------------------------------------------------------


	/**
	 * Edit problem description as html/markdown
	 *
	 * $type can be 'md', 'html', or 'plain'
	 *
	 * @param string $type
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function edit($type = 'md', $assignment_id = NULL, $problem_id = 1)
	{
		if ($type !== 'html' && $type !== 'md' && $type !== 'plain')
			show_404();

		if ($this->user->level <= 1)
			show_404();

		switch($type)
		{
			case 'html':
				$ext = 'html'; break;
			case 'md':
				$ext = 'md'; break;
			case 'plain':
				$ext = 'html'; break;
		}

		if ($assignment_id === NULL)
			$assignment_id = $this->user->selected_assignment['id'];
		if ($assignment_id == 0)
			show_error('No assignment selected.');

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'description_assignment' => $this->assignment_model->assignment_info($assignment_id),
		);

		if ( ! is_numeric($problem_id) || $problem_id < 1 || $problem_id > $data['description_assignment']['problems'])
			show_404();

		$workdir = rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/p{$problem_id}";
		$this->form_validation->set_rules('text', 'text' ,''); /* todo: xss clean */
		if ($this->form_validation->run())
		{
			$this->assignment_model->save_problem_description($assignment_id, $problem_id, $this->input->post('text'), $ext);
			$this->assignment_model->save_problem_name($assignment_id, $problem_id, $this->input->post('problem_name'));
			$files = glob("$workdir/in/*"); // get all file names
			foreach($files as $file){ // iterate files
				if(is_file($file))
					unlink($file); // delete file
			}
			$files = glob("$workdir/out/*"); // get all file names
			foreach($files as $file){ // iterate files
				if(is_file($file))
					unlink($file); // delete file
			}
			$c=1;
			for ($i=1;$i<=$this->input->post('num_test_cases');$i++) {
				echo "<script>alert('".strlen(trim($this->input->post("in_{$i}")))."');</script>";
				if (strlen(trim($this->input->post("in_{$i}"))) > 0 || strlen(trim($this->input->post("out_{$i}"))) > 0) {
					$this->assignment_model->save_test_case($assignment_id, $problem_id,$c,$this->input->post("in_{$i}"),$this->input->post("out_{$i}"));
					$c++;
				}
			}
			redirect('problems/'.$assignment_id.'/'.$problem_id);
		}

		$problem_info = $this->assignment_model->problem_info($assignment_id, $problem_id);
		$data['problem'] = array(
			'id' => $problem_id,
			'name' => $problem_info['name'],
			'description' => '',
			'tests' => array()
		);
		$path = "{$workdir}/desc.".$ext;
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);
		if (is_dir("{$workdir}/in") && is_dir("{$workdir}/out")) {
			$test=1;
			while(true) {
				if (is_file("{$workdir}/in/input{$test}.txt") && is_file("{$workdir}/out/output{$test}.txt")) {
					$data['problem']['tests'][] = array("in"=>file_get_contents("{$workdir}/in/input{$test}.txt"), "out"=>file_get_contents("{$workdir}/out/output{$test}.txt"));
					$test++;
				} else {
					break;
				}
			}				
		}


		$this->twig->display('pages/admin/edit_problem_'.$type.'.twig', $data);

	}


}
