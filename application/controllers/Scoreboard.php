<?php
/**
 * Sharif Judge online judge
 * @file Scoreboard.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Scoreboard extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		if ($this->input->is_cli_request())
			return;
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
	}


	// ------------------------------------------------------------------------


	public function index()
	{

		$this->load->model('scoreboard_model');

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'scoreboard' => $this->scoreboard_model->get_scoreboard($this->user->selected_assignment['id'])
		);

		$this->twig->display('pages/scoreboard.twig', $data);
	}


}