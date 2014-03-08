<?php
/**
 * Sharif Judge online judge
 * @file Notifications.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends CI_Controller
{

	private $notif_edit;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->load->model('notifications_model');
		$this->notif_edit = FALSE;
	}


	// ------------------------------------------------------------------------


	public function index()
	{
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'notifications' => $this->notifications_model->get_all_notifications()
		);

		$this->twig->display('pages/notifications.twig', $data);

	}


	// ------------------------------------------------------------------------


	public function add()
	{
		if ( $this->user->level <=1) // permission denied
			show_404();

		$this->form_validation->set_rules('title', 'title', 'trim');
		$this->form_validation->set_rules('text', 'text', ''); /* todo: xss clean */

		if($this->form_validation->run()){
			if ($this->input->post('id') === NULL)
				$this->notifications_model->add_notification($this->input->post('title'), $this->input->post('text'));
			else
				$this->notifications_model->update_notification($this->input->post('id'), $this->input->post('title'), $this->input->post('text'));
			redirect('notifications');
		}

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'notif_edit' => $this->notif_edit
		);

		if ($this->notif_edit !== FALSE)
			$data['title'] = 'Edit Notification';


		$this->twig->display('pages/admin/add_notification.twig', $data);

	}


	// ------------------------------------------------------------------------


	public function edit($notif_id = FALSE)
	{
		if ($this->user->level <= 1) // permission denied
			show_404();
		if ($notif_id === FALSE || ! is_numeric($notif_id))
			show_404();
		$this->notif_edit = $this->notifications_model->get_notification($notif_id);
		$this->add();
	}


	// ------------------------------------------------------------------------


	public function delete()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		if ($this->user->level <= 1) // permission denied
			$json_result = array('done' => 0, 'message' => 'Permission Denied');
		elseif ($this->input->post('id') === NULL)
			$json_result = array('done' => 0, 'message' => 'Input Error');
		else
		{
			$this->notifications_model->delete_notification($this->input->post('id'));
			$json_result = array('done' => 1);
		}

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}


	// ------------------------------------------------------------------------


	public function check()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$time  = $this->input->post('time');
		if ($time === NULL)
			exit('error');
		if ($this->notifications_model->have_new_notification(strtotime($time)))
			exit('new_notification');
		exit('no_new_notification');
	}

}