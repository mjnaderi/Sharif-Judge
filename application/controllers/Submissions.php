<?php
/**
 * Sharif Judge online judge
 * @file Submissions.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Submissions extends CI_Controller
{

	private $problems;

	private $filter_user;
	private $filter_problem;
	private $page_number;

	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->load->model('submit_model');
		$this->problems = $this->assignment_model->all_problems($this->user->selected_assignment['id']);

		$input = $this->uri->uri_to_assoc();
		$this->filter_user = $this->filter_problem = NULL;
		$this->page_number = 1;
		if (array_key_exists('user', $input) && $input['user'])
			if ($this->user->level > 0) // students are not able to filter submissions by user
				$this->filter_user = $this->form_validation->alpha_numeric($input['user'])?$input['user']:NULL;
		if (array_key_exists('problem', $input) && $input['problem'])
			$this->filter_problem = is_numeric($input['problem'])?$input['problem']:NULL;
		if (array_key_exists('page', $input) && $input['page'])
			$this->page_number = is_numeric($input['page'])?$input['page']:1;

	}




	// ------------------------------------------------------------------------


	/**
	 * Uses PHPExcel library to generate excel file of submissions
	 */
	private function _download_excel($view)
	{
		if ( ! in_array($view, array('all', 'final')))
			exit;

		$now = shj_now_str(); // current time

		// Load PHPExcel library
		$this->load->library('phpexcel');

		// Set document properties
		$this->phpexcel->getProperties()->setCreator('Sharif Judge')
			->setLastModifiedBy('Sharif Judge')
			->setTitle('Sharif Judge Users')
			->setSubject('Sharif Judge Users')
			->setDescription('List of Sharif Judge users ('.$now.')');

		// Name of the file sent to browser
		$output_filename = 'judge_'.$view.'_submissions';

		// Set active sheet
		$this->phpexcel->setActiveSheetIndex(0);
		$sheet = $this->phpexcel->getActiveSheet();

		// Add current assignment, time, username filter, and problem filter to document
		$sheet->fromArray(array('Assignment:',$this->user->selected_assignment['name']), null, 'A1', true);
		$sheet->fromArray(array('Time:',$now), null, 'A2', true);
		$sheet->fromArray(array('Username Filter:', $this->filter_user?$this->filter_user:'No filter'), null, 'A3', true);
		$sheet->fromArray(array('Problem Filter:', $this->filter_problem?$this->filter_problem:'No filter'), null, 'A4', true);

		// Prepare header
		if ($this->user->level === 0)
			$header=array('Final','Problem','Submit Time','Score','Delay (HH:MM)','Coefficient','Final Score','Language','Status');
		else{
			$header=array('Final','Submit ID','Username','Name','Problem','Submit Time','Score','Delay (HH:MM)','Coefficient','Final Score','Language','Status');
			if ($view === 'final'){
				array_unshift($header, "#2");
				array_unshift($header, "#1");
			}
		}

		// Add header to document
		$sheet->fromArray($header, null, 'A6', true);
		$highest_column = $sheet->getHighestColumn();

		// Set custom style for header
		$sheet->getStyle('A6:'.$highest_column.'6')->applyFromArray(
			array(
				'fill' => array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array('rgb' => '173C45')
				),
				'font'  => array(
					'bold'  => true,
					'color' => array('rgb' => 'FFFFFF'),
					//'size'  => 14
				)
			)
		);

		// Prepare data (in $rows array)
		if ($view === 'final')
			$items = $this->submit_model->get_final_submissions($this->user->selected_assignment['id'], $this->user->level, $this->user->username, NULL, $this->filter_user, $this->filter_problem);
		else
			$items = $this->submit_model->get_all_submissions($this->user->selected_assignment['id'], $this->user->level, $this->user->username, NULL, $this->filter_user, $this->filter_problem);

		$names = $this->user_model->get_names();

		$finish = strtotime($this->user->selected_assignment['finish_time']);
		$i=0; $j=0; $un='';
		$rows = array();
		foreach ($items as $item){
			$i++;
			if ($item['username'] != $un)
				$j++;
			$un = $item['username'];

			$pi = $this->problems[$item['problem']];

			$pre_score = ceil($item['pre_score']*$pi['score']/10000);

			$checked='';
			if ($item['is_final'])
				$checked='*';

			$delay = strtotime($item['time'])-$finish;
			if ($item['coefficient'] === 'error')
				$final_score = 0;
			else
				$final_score = ceil($pre_score*$item['coefficient']/100);


			if ($this->user->level === 0)
				$row = array(
					$checked,
					$item['problem'].' ('.$pi['name'].')',
					$item['time'],
					$pre_score,
					($delay<=0?'No Delay':time_hhmm($delay)),
					$item['coefficient'],
					$final_score,
					filetype_to_language($item['file_type']),
					$item['status'],
				);
			else {
				$row = array(
					$checked,
					$item['submit_id'],
					$item['username'],
					$names[$item['username']],
					$item['problem'].' ('.$pi['name'].')',
					$item['time'],
					$pre_score,
					($delay<=0?'No Delay':time_hhmm($delay)),
					$item['coefficient'],
					$final_score,
					filetype_to_language($item['file_type']),
					$item['status'],
				);
				if ($view === 'final'){
					array_unshift($row,$j);
					array_unshift($row,$i);
				}
			}
			array_push($rows, $row);
		}

		// Add rows to document
		$sheet->fromArray($rows, null, 'A7', true);
		// Add alternative colors to rows
		for ($i=7; $i<count($rows)+7; $i++){
			$sheet->getStyle('A'.$i.':'.$highest_column.$i)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => (($i%2)?'F0F0F0':'FAFAFA'))
					)
				)
			);
		}

		// Set text align to center
		$sheet->getStyle( $sheet->calculateWorksheetDimension() )
			->getAlignment()
			->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		// Making columns autosize
		for ($i=2;$i<count($header);$i++)
			$sheet->getColumnDimension(chr(65+$i))->setAutoSize(true);

		// Set Border
		$sheet->getStyle('A7:'.$highest_column.$sheet->getHighestRow())->applyFromArray(
			array(
				'borders' => array(
					'outline' => array(
						'style' => PHPExcel_Style_Border::BORDER_THIN,
						'color' => array('rgb' => '444444'),
					),
				)
			)
		);

		// Send the file to browser

		$ext = 'xlsx';
		if ( ! class_exists('ZipArchive') ) // If class ZipArchive does not exist, export to excel5 instead of excel 2007
			$ext = 'xls';

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$output_filename.'.'.$ext.'"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, ($ext==='xlsx'?'Excel2007':'Excel5'));
		$objWriter->save('php://output');
	}




	// ------------------------------------------------------------------------




	public function final_excel()
	{
		$this->_download_excel('final');
	}



	public function all_excel()
	{
		$this->_download_excel('all');
	}




	// ------------------------------------------------------------------------




	public function the_final()
	{

		if ( ! is_numeric($this->page_number))
			show_404();

		if ($this->page_number<1)
			show_404();

		$config = array(
			'base_url' => site_url('submissions/final'.($this->filter_user?'/user/'.$this->filter_user:'').($this->filter_problem?'/problem/'.$this->filter_problem:'')),
			'cur_page' => $this->page_number,
			'total_rows' => $this->submit_model->count_final_submissions($this->user->selected_assignment['id'], $this->user->level, $this->user->username, $this->filter_user, $this->filter_problem),
			'per_page' => $this->settings_model->get_setting('results_per_page_final'),
			'num_links' => 5,
			'full_ul_class' => 'shj_pagination',
			'cur_li_class' => 'current_page'
		);
		if ($config['per_page'] == 0)
			$config['per_page'] = $config['total_rows'];
		$this->load->library('shj_pagination', $config);

		$submissions = $this->submit_model->get_final_submissions($this->user->selected_assignment['id'], $this->user->level, $this->user->username, $this->page_number, $this->filter_user, $this->filter_problem);

		$names = $this->user_model->get_names();

		foreach ($submissions as &$item)
		{
			$item['name'] = $names[$item['username']];
			$item['fullmark'] = ($item['pre_score'] == 10000);
			$item['pre_score'] = ceil($item['pre_score']*$this->problems[$item['problem']]['score']/10000);
			$item['delay'] = strtotime($item['time'])-strtotime($this->user->selected_assignment['finish_time']);
			$item['language'] = filetype_to_language($item['file_type']);
			if ($item['coefficient'] === 'error')
				$item['final_score'] = 0;
			else
				$item['final_score'] = ceil($item['pre_score']*$item['coefficient']/100);
		}


		$data = array(
			'view' => 'final',
			'all_assignments' => $this->assignment_model->all_assignments(),
			'problems' => $this->problems,
			'submissions' => $submissions,
			'excel_link' => site_url('submissions/final_excel'.($this->filter_user?'/user/'.$this->filter_user:'').($this->filter_problem?'/problem/'.$this->filter_problem:'')),
			'filter_user' => $this->filter_user,
			'filter_problem' => $this->filter_problem,
			'pagination' => $this->shj_pagination->create_links(),
			'page_number' => $this->page_number,
			'per_page' => $config['per_page'],
		);

		$this->twig->display('pages/submissions.twig', $data);
	}




	// ------------------------------------------------------------------------




	public function all()
	{

		if ( ! is_numeric($this->page_number))
			show_404();

		if ($this->page_number < 1)
			show_404();

		$config = array(
			'base_url' => site_url('submissions/all'.($this->filter_user?'/user/'.$this->filter_user:'').($this->filter_problem?'/problem/'.$this->filter_problem:'')),
			'cur_page' => $this->page_number,
			'total_rows' => $this->submit_model->count_all_submissions($this->user->selected_assignment['id'], $this->user->level, $this->user->username, $this->filter_user, $this->filter_problem),
			'per_page' => $this->settings_model->get_setting('results_per_page_all'),
			'num_links' => 5,
			'full_ul_class' => 'shj_pagination',
			'cur_li_class' => 'current_page'
		);
		if ($config['per_page']==0)
			$config['per_page'] = $config['total_rows'];
		$this->load->library('shj_pagination', $config);

		$submissions = $this->submit_model->get_all_submissions($this->user->selected_assignment['id'], $this->user->level, $this->user->username, $this->page_number, $this->filter_user, $this->filter_problem);

		$names = $this->user_model->get_names();

		foreach ($submissions as &$item)
		{
			$item['name'] = $names[$item['username']];
			$item['fullmark'] = ($item['pre_score'] == 10000);
			$item['pre_score'] = ceil($item['pre_score']*$this->problems[$item['problem']]['score']/10000);
			$item['delay'] = strtotime($item['time'])-strtotime($this->user->selected_assignment['finish_time']);
			$item['language'] = filetype_to_language($item['file_type']);
			if ($item['coefficient'] === 'error')
				$item['final_score'] = 0;
			else
				$item['final_score'] = ceil($item['pre_score']*$item['coefficient']/100);
		}

		$data = array(
			'view' => 'all',
			'all_assignments' => $this->assignment_model->all_assignments(),
			'problems' => $this->problems,
			'submissions' => $submissions,
			'excel_link' => site_url('submissions/all_excel'.($this->filter_user?'/user/'.$this->filter_user:'').($this->filter_problem?'/problem/'.$this->filter_problem:'')),
			'filter_user' => $this->filter_user,
			'filter_problem' => $this->filter_problem,
			'pagination' => $this->shj_pagination->create_links(),
		);

		$this->twig->display('pages/submissions.twig', $data);
	}




	// ------------------------------------------------------------------------




	/**
	 * Used by ajax request (for selecting final submission)
	 */
	public function select()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();

		// Students cannot change their final submission after finish_time + extra_time
		if ($this->user->level === 0)
			if ( shj_now() > strtotime($this->user->selected_assignment['finish_time'])+$this->user->selected_assignment['extra_time'])
			{
				$json_result = array(
					'done' => 0,
					'message' => 'This assignment is finished. You cannot change your final submissions.'
				);
				$this->output->set_header('Content-Type: application/json; charset=utf-8');
				echo json_encode($json_result);
				return;
			}

		$this->form_validation->set_rules('submit_id', 'Submit ID', 'integer|greater_than[0]');
		$this->form_validation->set_rules('problem', 'problem', 'integer|greater_than[0]');
		$this->form_validation->set_rules('username', 'Username', 'required|min_length[3]|max_length[20]|alpha_numeric');

		if ($this->form_validation->run())
		{
			$username = $this->input->post('username');
			if ($this->user->level === 0)
				$username = $this->user->username;

			$res = $this->submit_model->set_final_submission(
				$username,
				$this->user->selected_assignment['id'],
				$this->input->post('problem'),
				$this->input->post('submit_id')
			);

			if ($res) {
				// each time a user changes final submission, we should update scoreboard of that assignment
				$this->load->model('scoreboard_model');
				$this->scoreboard_model->update_scoreboard($this->user->selected_assignment['id']);
				$json_result = array('done' => 1);
			}
			else
				$json_result = array('done' => 0, 'message' => 'Selecting Final Submission Failed');
		}
		else
			$json_result = array('done' => 0, 'message' => 'Input Error');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}




	// ------------------------------------------------------------------------


	public function _check_type($type) {
		return ($type === 'code' || $type === 'result' || $type === 'log');
	}


	/**
	 * For "view code" or "view result" or "view log"
	 */
	public function view_code()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$this->form_validation->set_rules('type','type','callback__check_type');
		$this->form_validation->set_rules('username','username','required|min_length[3]|max_length[20]|alpha_numeric');
		$this->form_validation->set_rules('assignment','assignment','integer|greater_than[0]');
		$this->form_validation->set_rules('problem','problem','integer|greater_than[0]');
		$this->form_validation->set_rules('submit_id','submit_id','integer|greater_than[0]');

		if($this->form_validation->run())
		{
			$submission = $this->submit_model->get_submission(
				$this->input->post('username'),
				$this->input->post('assignment'),
				$this->input->post('problem'),
				$this->input->post('submit_id')
			);
			if ($submission === FALSE)
				show_404();

			$type = $this->input->post('type'); // $type is 'code', 'result', or 'log'

			if ($this->user->level === 0 && $type === 'log')
				show_404();

			if ($this->user->level === 0 && $this->user->username != $submission['username'])
				exit('Don\'t try to see submitted codes :)');

			if ($type === 'result')
				$file_path = rtrim($this->settings_model->get_setting('assignments_root'),'/').
					"/assignment_{$submission['assignment']}/p{$submission['problem']}/{$submission['username']}/result-{$submission['submit_id']}.html";
			elseif ($type === 'code')
				$file_path = rtrim($this->settings_model->get_setting('assignments_root'),'/').
					"/assignment_{$submission['assignment']}/p{$submission['problem']}/{$submission['username']}/{$submission['file_name']}.".filetype_to_extension($submission['file_type']);
			elseif ($type === 'log')
				$file_path = rtrim($this->settings_model->get_setting('assignments_root'),'/').
					"/assignment_{$submission['assignment']}/p{$submission['problem']}/{$submission['username']}/log";
			else
				$file_path = '/nowhere'; // This line is never reached!

			$result = array(
				'file_name' => $submission['main_file_name'].'.'.filetype_to_extension($submission['file_type']),
				'text' => file_exists($file_path)?file_get_contents($file_path):'File Not Found'
			);

			if ($type === 'code') {
				$result['lang'] = $submission['file_type'];
				if ($result['lang'] == 'py2' || $result['lang'] == 'py3')
					$result['lang'] = 'python';
			}

			$this->output->set_content_type('application/json')->set_output(json_encode($result));

		}
		else
			exit('Are you trying to see other users\' codes? :)');
	}




	// ------------------------------------------------------------------------




	public function download_file(){
		$username = $this->uri->segment(3);
		$assignment = $this->uri->segment(4);
		$problem = $this->uri->segment(5);
		$submit_id = $this->uri->segment(6);

		$submission = $this->submit_model->get_submission(
			$username,
			$assignment,
			$problem,
			$submit_id
		);
		if ($submission === FALSE)
			show_404();

		if ($this->user->level === 0 && $this->user->username != $submission['username'])
			exit('Don\'t try to see submitted codes :)');

		$file_path = rtrim($this->settings_model->get_setting('assignments_root'),'/').
		"/assignment_{$submission['assignment']}/p{$submission['problem']}/{$submission['username']}/{$submission['file_name']}.".filetype_to_extension($submission['file_type']);

		$this->load->helper('download');
		force_download(
			"{$submission['file_name']}.".filetype_to_extension($submission['file_type']),
			file_get_contents($file_path)
		);

	}


}