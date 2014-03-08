<?php
/**
 * Sharif Judge online judge
 * @file Assignments.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Assignments extends CI_Controller
{

	private $error_messages;
	private $success_messages;
	private $edit_assignment;
	private $edit;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');

		$this->error_messages = array();
		$this->success_messages = array();
		$this->edit_assignment = array();
		$this->edit = FALSE;
	}


	// ------------------------------------------------------------------------


	public function index()
	{
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'success_messages' => $this->success_messages,
			'error_messages' => $this->error_messages
		);

		foreach ($data['all_assignments'] as &$item)
		{
			$extra_time = $item['extra_time'];
			$delay = shj_now()-strtotime($item['finish_time']);;
			ob_start();
			if ( eval($item['late_rule']) === FALSE )
				$coefficient = "error";
			if (!isset($coefficient))
				$coefficient = "error";
			ob_end_clean();
			$item['coefficient'] = $coefficient;
			$item['delay'] = $delay;
			$item['extra_time'] = $extra_time;
			$item['start_time'] = date("Y-m-d H:i", strtotime($item['start_time']));
			$item['finish_time'] = date("Y-m-d H:i", strtotime($item['finish_time']));
		}

		$this->twig->display('pages/assignments.twig', $data);

	}


	// ------------------------------------------------------------------------


	/**
	 * Used by ajax request (for select assignment from top bar)
	 */
	public function select()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();

		$this->form_validation->set_rules('assignment_select', 'Assignment', 'required|integer|greater_than[0]');

		if ($this->form_validation->run())
		{
			$this->user->select_assignment($this->input->post('assignment_select'));
			$this->assignment = $this->assignment_model->assignment_info($this->input->post('assignment_select'));
			$json_result = array(
				'done' => 1,
				'finish_time' => $this->assignment['finish_time'],
				'extra_time' => $this->assignment['extra_time'],
			);
		}
		else
			$json_result = array('done' => 0, 'message' => 'Input Error');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}


	// ------------------------------------------------------------------------


	/**
	 * Compressing and downloading test data and descriptions of an assignment to the browser
	 */
	public function downloadtestsdesc($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		if ( $this->user->level <= 1) // permission denied
			show_404();

		$this->load->library('zip');

		$assignment = $this->assignment_model->assignment_info($assignment_id);

		$number_of_problems = $assignment['problems'];

		for ($i=1 ; $i<=$number_of_problems ; $i++)
		{
			$root_path = rtrim($this->settings_model->get_setting('assignments_root'),'/').
				"/assignment_{$assignment_id}";

			$path = $root_path."/p{$i}/in";
			$this->zip->read_dir($path, FALSE, $root_path);

			$path = $root_path."/p{$i}/out";
			$this->zip->read_dir($path, FALSE, $root_path);

			$path = $root_path."/p{$i}/tester.cpp";
			if (file_exists($path))
				$this->zip->add_data("p{$i}/tester.cpp", file_get_contents($path));

			$path = $root_path."/p{$i}/desc.html";
			if (file_exists($path))
				$this->zip->add_data("p{$i}/desc.html", file_get_contents($path));

			$path = $root_path."/p{$i}/desc.md";
			if (file_exists($path))
				$this->zip->add_data("p{$i}/desc.md", file_get_contents($path));
		}

		$this->zip->download("assignment{$assignment_id}_tests_desc_".date('Y-m-d_H-i',shj_now()).'.zip');
	}


	// ------------------------------------------------------------------------


	/**
	 * Compressing and downloading final codes of an assignment to the browser
	 */
	public function download($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		if ( $this->user->level == 0) // permission denied
			show_404();

		$this->load->model('submit_model');
		$items = $this->submit_model->get_final_submissions($assignment_id, $this->user->level, $this->user->username);

		$this->load->library('zip');

		foreach ($items as $item)
		{
			$file_path = rtrim($this->settings_model->get_setting('assignments_root'),'/').
				"/assignment_{$item['assignment']}/p{$item['problem']}/{$item['username']}/{$item['file_name']}.".filetype_to_extension($item['file_type']);
			if ( ! file_exists($file_path))
				continue;
			$file = file_get_contents($file_path);
			$this->zip->add_data("by_user/{$item['username']}/p{$item['problem']}.".filetype_to_extension($item['file_type']), $file);
			$this->zip->add_data("by_problem/problem_{$item['problem']}/{$item['username']}.".filetype_to_extension($item['file_type']), $file);
		}

		$this->zip->download("assignment{$assignment_id}_codes_".date('Y-m-d_H-i',shj_now()).'.zip');
	}


	// ------------------------------------------------------------------------


	/**
	 * Delete assignment
	 */
	public function delete($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		if ($this->user->level <= 1) // permission denied
			show_404();

		$assignment = $this->assignment_model->assignment_info($assignment_id);

		if ($assignment['id'] === 0)
			show_404();

		if ($this->input->post('delete') === 'delete')
		{
			$this->assignment_model->delete_assignment($assignment_id);
			redirect('assignments');
		}

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'id' => $assignment_id,
			'name' => $assignment['name']
		);

		$this->twig->display('pages/admin/delete_assignment.twig', $data);

	}



	// ------------------------------------------------------------------------


	/**
	 * This method gets inputs from user for adding/editing assignment
	 */
	public function add()
	{

		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->load->library('upload');

		if ( ! empty($_POST) )
			if ($this->_add()){ // add/edit assignment
				if ( ! $this->edit) // if adding assignment (not editing)
				{
					// goto Assignment page
					$this->index();
					return;
				}
			}

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'error_messages' => $this->error_messages,
			'success_messages' => $this->success_messages,
			'edit' => $this->edit,
			'upload_error' => $this->upload->display_errors('', ''),
			'default_late_rule' => $this->settings_model->get_setting('default_late_rule'),
		);

		if ($this->edit)
		{
			$data['edit_assignment'] = $this->assignment_model->assignment_info($this->edit_assignment);
			if ($data['edit_assignment']['id'] === 0)
				show_404();
			$data['problems'] = $this->assignment_model->all_problems($this->edit_assignment);
		}
		else
		{
			$names = $this->input->post('name');
			if ($names === NULL)
				$data['problems'] = array(
					array(
						'id' => 1,
						'name' => 'Problem ',
						'score' => 100,
						'c_time_limit' => 500,
						'python_time_limit' => 1500,
						'java_time_limit' => 2000,
						'memory_limit' => 50000,
						'allowed_languages' => 'C,C++,Python 2,Python 3,Java',
						'diff_cmd' => 'diff',
						'diff_arg' => '-bB',
						'is_upload_only' => 0
					)
				);
			else
			{
				$names = $this->input->post('name');
				$scores = $this->input->post('score');
				$c_tl = $this->input->post('c_time_limit');
				$py_tl = $this->input->post('python_time_limit');
				$java_tl = $this->input->post('java_time_limit');
				$ml = $this->input->post('memory_limit');
				$ft = $this->input->post('languages');
				$dc = $this->input->post('diff_cmd');
				$da = $this->input->post('diff_arg');
				$data['problems'] = array();
				$uo = $this->input->post('is_upload_only');
				if ($uo === NULL)
					$uo = array();
				for ($i=0; $i<count($names); $i++){
					array_push($data['problems'], array(
						'id' => $i+1,
						'name' => $names[$i],
						'score' => $scores[$i],
						'c_time_limit' => $c_tl[$i],
						'python_time_limit' => $py_tl[$i],
						'java_time_limit' => $java_tl[$i],
						'memory_limit' => $ml[$i],
						'allowed_languages' => $ft[$i],
						'diff_cmd' => $dc[$i],
						'diff_arg' => $da[$i],
						'is_upload_only' => in_array($i+1,$uo)?1:0,
					));
				}
			}
		}

		$this->twig->display('pages/admin/add_assignment.twig', $data);
	}


	// ------------------------------------------------------------------------


	/**
	 * Add/Edit assignment
	 */
	private function _add()
	{

		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->form_validation->set_rules('assignment_name', 'assignment name', 'required|max_length[50]');
		$this->form_validation->set_rules('start_time', 'start time', 'required');
		$this->form_validation->set_rules('finish_time', 'finish time', 'required');
		$this->form_validation->set_rules('extra_time', 'extra time', 'required');
		$this->form_validation->set_rules('participants', 'participants', '');
		$this->form_validation->set_rules('late_rule', 'coefficient rule', 'required');
		$this->form_validation->set_rules('name[]', 'problem name', 'required|max_length[50]');
		$this->form_validation->set_rules('score[]', 'problem score', 'required|integer');
		$this->form_validation->set_rules('c_time_limit[]', 'C/C++ time limit', 'required|integer');
		$this->form_validation->set_rules('python_time_limit[]', 'python time limit', 'required|integer');
		$this->form_validation->set_rules('java_time_limit[]', 'java time limit', 'required|integer');
		$this->form_validation->set_rules('memory_limit[]', 'memory limit', 'required|integer');
		$this->form_validation->set_rules('languages[]', 'languages', 'required');
		$this->form_validation->set_rules('diff_cmd[]', 'diff command', 'required');
		$this->form_validation->set_rules('diff_arg[]', 'diff argument', 'required');

		if ( ! $this->form_validation->run())
			return FALSE;

		if ($this->edit)
			$the_id = $this->edit_assignment;
		else
			$the_id = $this->assignment_model->new_assignment_id();

		$config['upload_path'] = rtrim($this->settings_model->get_setting('assignments_root'), '/');
		shell_exec('rm '.$config['upload_path'].'/*.zip');

		$config['allowed_types'] = 'zip';
		$this->upload->initialize($config);

		$assignment_dir = $config['upload_path']."/assignment_{$the_id}";


		// If all problems are Upload-Only, we do not need a zip file
		if ( ! $this->edit && count($this->input->post('is_upload_only')) == $this->input->post('number_of_problems'))
		{
			if ( ! file_exists($assignment_dir))
				mkdir($assignment_dir, 0700);

			// Remove previous test cases and description
			shell_exec("cd $assignment_dir; rm -rf */in; rm -rf */out; rm -f */tester.cpp; rm -f */tester.executable; rm -f */desc.html; rm -f */desc.md;");

			for ($i=1; $i <= $this->input->post('number_of_problems'); $i++)
				if ( ! file_exists("$assignment_dir/p$i"))
					mkdir("$assignment_dir/p$i", 0700);

			if($this->assignment_model->add_assignment($the_id, $this->edit)){
				$this->success_messages[] = 'Assignment '.($this->edit?'updated':'added').' successfully.';
				return TRUE;
			}
			else{
				$this->error_messages[] = 'Error '.($this->edit?'updating':'adding').' assignment.';
				return FALSE;
			}
		}

		elseif ($this->upload->do_upload('tests_desc'))
		{
			$this->load->library('unzip');
			$this->unzip->allow(array('txt', 'cpp', 'html', 'md'));
			if ( ! file_exists($assignment_dir))
				mkdir($assignment_dir, 0700);
			$u_data = $this->upload->data();

			// Remove previous test cases and descriptions
			shell_exec("cd $assignment_dir; rm -rf */in; rm -rf */out; rm -f */tester.cpp; rm -f */tester.executable; rm -f */desc.html; rm -f */desc.md;");

			// Extract and save new test cases and descriptions
			$extract_result = $this->unzip->extract($u_data['full_path'], $assignment_dir);

			// Remove the zip file
			unlink($u_data['full_path']);

			if ( $extract_result !== FALSE){
				for ($i=1; $i <= $this->input->post('number_of_problems'); $i++)
				{
					if ( ! file_exists("$assignment_dir/p$i"))
						mkdir("$assignment_dir/p$i", 0700);
					elseif (file_exists("$assignment_dir/p$i/desc.md"))
					{
						$this->load->library('parsedown');
						$html = $this->parsedown->parse(file_get_contents("$assignment_dir/p$i/desc.md"));
						file_put_contents("$assignment_dir/p$i/desc.html", $html);
					}
				}

				if ($this->assignment_model->add_assignment($the_id, $this->edit))
				{
					$this->success_messages[] = 'Assignment '.($this->edit?'updated':'added').' successfully.';
					$this->success_messages[] = 'Tests and descriptions uploaded successfully.';
					return TRUE;
				}
				else
				{
					$this->error_messages[] = 'Error '.($this->edit?'updating':'adding').' assignment.';
					return FALSE;
				}
			}
			else
			{
				$this->error_messages[] = 'Error extracting zip archive.';
				$this->error_messages = array_merge($this->error_messages , $this->unzip->errors_array());
				rmdir($assignment_dir);
				return FALSE;
			}
		}
		elseif ($this->edit)
		{
			for ($i=1; $i <= $this->input->post('number_of_problems'); $i++)
				if ( ! file_exists($assignment_dir."/p$i"))
					mkdir($assignment_dir."/p$i", 0700);

			if ($this->assignment_model->add_assignment($the_id, $this->edit))
			{
				$this->success_messages[] = 'Assignment '.($this->edit?'updated':'added').' successfully.';
				return TRUE;
			}
			else
			{
				$this->error_messages[] = 'Error '.($this->edit?'updating':'adding').' assignment.';
				return FALSE;
			}
		}
		return FALSE;
	}


	// ------------------------------------------------------------------------


	public function edit($assignment_id)
	{

		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->edit_assignment = $assignment_id;
		$this->edit = TRUE;
		$this->add();
	}


}
