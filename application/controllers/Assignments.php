<?php
/**
 * Sharif Judge online judge
 * @file Assignments.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Assignments extends CI_Controller
{

	private $messages;
	private $edit_assignment;
	private $edit;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');

		$this->messages = array();
		$this->edit_assignment = array();
		$this->edit = FALSE;
	}


	// ------------------------------------------------------------------------


	public function index()
	{
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'messages' => $this->messages,
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
			$item['finished'] = ($delay > $extra_time);
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
	 * Download pdf file of an assignment (or problem) to browser
	 */
	public function pdf($assignment_id, $problem_id = NULL)
	{
		// Find pdf file
		if ($problem_id === NULL)
			$pattern = rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/*.pdf";
		else
			$pattern = rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/p{$problem_id}/*.pdf";
		$pdf_files = glob($pattern);
		if ( ! $pdf_files )
			show_error("File not found");

		// Download the file to browser
		$this->load->helper('download')->helper('file');
		$filename = shj_basename($pdf_files[0]);
		force_download($filename, file_get_contents($pdf_files[0]), TRUE);
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

		$root_path = rtrim($this->settings_model->get_setting('assignments_root'),'/').
			"/assignment_{$assignment_id}";

		for ($i=1 ; $i<=$number_of_problems ; $i++)
		{

			$path = "$root_path/p{$i}/in";
			$this->zip->read_dir($path, FALSE, $root_path);

			$path = "$root_path/p{$i}/out";
			$this->zip->read_dir($path, FALSE, $root_path);

			$path = "$root_path/p{$i}/tester.cpp";
			if (file_exists($path))
				$this->zip->add_data("p{$i}/tester.cpp", file_get_contents($path));

			$pdf_files = glob("$root_path/p{$i}/*.pdf");
			if ($pdf_files)
			{
				$path = $pdf_files[0];
				$this->zip->add_data("p{$i}/".shj_basename($path), file_get_contents($path));
			}

			$path = "$root_path/p{$i}/desc.html";
			if (file_exists($path))
				$this->zip->add_data("p{$i}/desc.html", file_get_contents($path));

			$path = "$root_path/p{$i}/desc.md";
			if (file_exists($path))
				$this->zip->add_data("p{$i}/desc.md", file_get_contents($path));
		}

		$pdf_files = glob("$root_path/*.pdf");
		if ($pdf_files)
		{
			$path = $pdf_files[0];
			$this->zip->add_data(shj_basename($path), file_get_contents($path));
		}

		$this->zip->download("assignment{$assignment_id}_tests_desc_".date('Y-m-d_H-i', shj_now()).'.zip');
	}


	// ------------------------------------------------------------------------


	/**
	 * Compressing and downloading final codes of an assignment to the browser
	 */
	public function download_submissions($type = FALSE, $assignment_id = FALSE)
	{
		if ($type !== 'by_user' && $type !== 'by_problem')
			show_404();
		if ($assignment_id === FALSE || ! is_numeric($assignment_id))
			show_404();
		if ( $this->user->level == 0) // permission denied
			show_404();

		$this->load->model('submit_model');
		$items = $this->submit_model->get_final_submissions($assignment_id, $this->user->level, $this->user->username);

		$this->load->library('zip');

		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');

		foreach ($items as $item)
		{
			$file_path = $assignments_root.
				"/assignment_{$item['assignment']}/p{$item['problem']}/{$item['username']}/{$item['file_name']}."
				.filetype_to_extension($item['file_type']);
			if ( ! file_exists($file_path))
				continue;
			$file = file_get_contents($file_path);
			if ($type === 'by_user')
				$this->zip->add_data("{$item['username']}/p{$item['problem']}.".filetype_to_extension($item['file_type']), $file);
			elseif ($type === 'by_problem')
				$this->zip->add_data("problem_{$item['problem']}/{$item['username']}.".filetype_to_extension($item['file_type']), $file);
		}

		$this->zip->download("assignment{$assignment_id}_submissions_{$type}_".date('Y-m-d_H-i',shj_now()).'.zip');
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
			if ($this->_add()) // add/edit assignment
			{
				//if ( ! $this->edit) // if adding assignment (not editing)
				//{
				//   goto Assignments page
					$this->index();
					return;
				//}
			}

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'messages' => $this->messages,
			'edit' => $this->edit,
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

		// Check permission

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

		// Validate input data

		if ( ! $this->form_validation->run())
			return FALSE;


		// Preparing variables

		if ($this->edit)
			$the_id = $this->edit_assignment;
		else
			$the_id = $this->assignment_model->new_assignment_id();

		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'), '/');
		$assignment_dir = "$assignments_root/assignment_{$the_id}";



		// Adding/Editing assignment in database

		if ( ! $this->assignment_model->add_assignment($the_id, $this->edit))
		{
			$this->messages[] = array(
				'type' => 'error',
				'text' => 'Error '.($this->edit?'updating':'adding').' assignment.'
			);
			return FALSE;
		}

		$this->messages[] = array(
			'type' => 'success',
			'text' => 'Assignment '.($this->edit?'updated':'added').' successfully.'
		);

		// Create assignment directory
		if ( ! file_exists($assignment_dir) )
			mkdir($assignment_dir, 0700);



		// Upload Tests (zip file)

		shell_exec('rm -f '.$assignments_root.'/*.zip');
		$config = array(
			'upload_path' => $assignments_root,
			'allowed_types' => 'zip',
		);
		$this->upload->initialize($config);
		$zip_uploaded = $this->upload->do_upload('tests_desc');
		$u_data = $this->upload->data();
		if ( $_FILES['tests_desc']['error'] === UPLOAD_ERR_NO_FILE )
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any zip file for tests. If needed, upload by editing assignment."
			);
		elseif ( ! $zip_uploaded )
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading tests zip file: ".$this->upload->display_errors('', '')
			);
		else
			$this->messages[] = array(
				'type' => 'success',
				'text' => "Tests (zip file) uploaded successfully."
			);



		// Upload PDF File of Assignment

		$config = array(
			'upload_path' => $assignment_dir,
			'allowed_types' => 'pdf',
		);
		$this->upload->initialize($config);
		$old_pdf_files = glob("$assignment_dir/*.pdf");
		$pdf_uploaded = $this->upload->do_upload("pdf");
		if ($_FILES['pdf']['error'] === UPLOAD_ERR_NO_FILE)
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any pdf file for assignment. If needed, upload by editing assignment."
			);
		elseif ( ! $pdf_uploaded)
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading pdf file of assignment: ".$this->upload->display_errors('', '')
			);
		else
		{
			foreach($old_pdf_files as $old_name)
				shell_exec("rm -f $old_name");
			$this->messages[] = array(
				'type' => 'success',
				'text' => 'PDF file uploaded successfully.'
			);
		}



		// Extract Tests (zip file)

		if ($zip_uploaded) // if zip file is uploaded
		{
			// Create a temp directory
			$tmp_dir_name = "shj_tmp_directory";
			$tmp_dir = "$assignments_root/$tmp_dir_name";
			shell_exec("rm -rf $tmp_dir; mkdir $tmp_dir;");

			// Extract new test cases and descriptions in temp directory
			$this->load->library('unzip');
			$this->unzip->allow(array('txt', 'cpp', 'html', 'md', 'pdf'));
			$extract_result = $this->unzip->extract($u_data['full_path'], $tmp_dir);

			// Remove the zip file
			unlink($u_data['full_path']);

			if ( $extract_result )
			{
				// Remove previous test cases and descriptions
				shell_exec("cd $assignment_dir;"
					." rm -rf */in; rm -rf */out; rm -f */tester.cpp; rm -f */tester.executable;"
					." rm -f */desc.html; rm -f */desc.md; rm -f */*.pdf;");
				if (glob("$tmp_dir/*.pdf"))
					shell_exec("cd $assignment_dir; rm -f *.pdf");
				// Copy new test cases from temp dir
				shell_exec("cd $assignments_root; cp -R $tmp_dir_name/* assignment_{$the_id};");
				$this->messages[] = array(
					'type' => 'success',
					'text' => 'Tests (zip file) extracted successfully.'
				);
			}
			else
			{
				$this->messages[] = array(
					'type' => 'error',
					'text' => 'Error: Error extracting zip archive.'
				);
				foreach($this->unzip->errors_array() as $msg)
					$this->messages[] = array(
						'type' => 'error',
						'text' => " Zip Extraction Error: ".$msg
					);
			}

			// Remove temp directory
			shell_exec("rm -rf $tmp_dir");
		}



		// Create problem directories and parsing markdown files

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

		return TRUE;
	}


	// ------------------------------------------------------------------------


	public function edit($assignment_id)
	{

		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->edit_assignment = $assignment_id;
		$this->edit = TRUE;

		// redirect to add function
		$this->add();
	}



}
