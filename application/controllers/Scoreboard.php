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

	public function simplify(){
		$this->load->model('scoreboard_model');

		$a = $this->scoreboard_model->get_scoreboard($this->user->selected_assignment['id']);

		//Remove excess info
		$a = preg_replace('/[0-9]+:[0-9]+(\*\*)?/', '', $a);
		$a = preg_replace('/-/', '', $a);
		$a = preg_replace('/[0-9]+\*/', '0', $a);
		$a = preg_replace('/\n+/', "\n", $a);

		//Remove the legend
		$c = 0;
		$i = strlen($a) - 1;
		for(; $i > 0; $i--){
		    if($a[$i] == "\n") $c++;
		    if($c == 3) break;
		}
		$a = substr($a, 0, $i);

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'scoreboard' => $a
		);


		$this->twig->display('pages/scoreboard.twig', $data);
	}
}
