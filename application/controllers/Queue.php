<?php
/**
 * Sharif Judge online judge
 * @file Queue.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Queue extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
		redirect('login');
		if ( $this->user->level <= 1) // permission denied
			show_404();
		$this->load->model('queue_model');
	}


	// ------------------------------------------------------------------------


	public function index()
	{

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'queue' => $this->queue_model->get_queue(),
			'working' => $this->settings_model->get_setting('queue_is_working')
		);

		$this->twig->display('pages/admin/queue.twig', $data);
	}


	// ------------------------------------------------------------------------


	public function pause()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$this->settings_model->set_setting('queue_is_working','0');
		echo 'success';
	}


	// ------------------------------------------------------------------------


	public function resume()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		process_the_queue();
		echo 'success';
	}


	// ------------------------------------------------------------------------


	public function empty_queue()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$this->queue_model->empty_queue();
		echo 'success';
	}
}