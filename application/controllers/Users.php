<?php
/**
 * Sharif Judge online judge
 * @file Users.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		if ( $this->user->level <= 2) // permission denied
			show_404();
	}




	// ------------------------------------------------------------------------




	public function index()
	{

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'users' => $this->user_model->get_all_users()
		);

		$this->twig->display('pages/admin/users.twig', $data);
	}




	// ------------------------------------------------------------------------




	public function add()
	{
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
		);
		$this->form_validation->set_rules('new_users', 'New Users', 'required');
		if ($this->form_validation->run())
		{
			if ( ! $this->input->is_ajax_request() )
				exit;
			list($ok, $error) = $this->user_model->add_users(
				$this->input->post('new_users'),
				$this->input->post('send_mail'),
				$this->input->post('delay')
			);
			$this->twig->display('pages/admin/add_user_result.twig', array('ok' => $ok, 'error' => $error));
		}
		else
		{
			$this->twig->display('pages/admin/add_user.twig', $data);
		}
	}




	// ------------------------------------------------------------------------




	/**
	 * Controller for deleting a user
	 * Called by ajax request
	 */
	public function delete()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$user_id = $this->input->post('user_id');
		if ( ! is_numeric($user_id) )
			$json_result = array('done' => 0, 'message' => 'Input Error');
		elseif ($this->user_model->delete_user($user_id))
			$json_result = array('done' => 1);
		else
			$json_result = array('done' => 0, 'message' => 'Deleting User Failed');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}




	// ------------------------------------------------------------------------




	/**
	 * Controller for deleting a user's submissions
	 * Called by ajax request
	 */
	public function delete_submissions()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$user_id = $this->input->post('user_id');
		if ( ! is_numeric($user_id) )
			$json_result = array('done' => 0, 'message' => 'Input Error');
		elseif ($this->user_model->delete_submissions($user_id))
			$json_result = array('done' => 1);
		else
			$json_result = array('done' => 0, 'message' => 'Deleting Submissions Failed');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}




	// ------------------------------------------------------------------------




	/**
	 * Uses PHPExcel library to generate excel file of users list
	 */
	public function list_excel()
	{

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
		$output_filename = 'sharifjudge_users';

		// Set active sheet
		$this->phpexcel->setActiveSheetIndex(0);
		$sheet = $this->phpexcel->getActiveSheet();

		// Add current time to document
		$sheet->fromArray(array('Time:',$now), null, 'A1', true);

		// Add header to document
		$header=array('#','User ID','Username','Display Name','Email','Role','First Login','Last Login');
		$sheet->fromArray($header, null, 'A3', true);
		$highest_column = $sheet->getHighestColumn();

		// Set custom style for header
		$sheet->getStyle('A3:'.$highest_column.'3')->applyFromArray(
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

		// Prepare user data (in $rows array)
		$users = $this->user_model->get_all_users();
		$i=0;
		$rows = array();
		foreach ($users as $user){
			array_push($rows, array(
				++$i,
				$user['id'],
				$user['username'],
				$user['display_name'],
				$user['email'],
				$user['role'],
				$user['first_login_time']===NULL?'Never':$user['first_login_time'],
				$user['last_login_time']===NULL?'Never':$user['last_login_time']
			));
		}

		// Add rows to document and set a background color of #7BD1BE
		$sheet->fromArray($rows, null, 'A4', true);
		// Add alternative colors to rows
		for ($i=4; $i<count($rows)+4; $i++){
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
		$sheet->getStyle('A4:'.$highest_column.$sheet->getHighestRow())->applyFromArray(
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

		// If class ZipArchive exists, export to excel2007, otherwise export to excel5
		if ( class_exists('ZipArchive') )
			$ext = 'xlsx';
		else
			$ext = 'xls';

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$output_filename.'.'.$ext.'"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, ($ext==='xlsx'?'Excel2007':'Excel5'));
		$objWriter->save('php://output');
	}


}