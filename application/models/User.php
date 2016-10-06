<?php
/**
 * Sharif Judge online judge
 * @file User_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Model
{

	public $username;
	public $selected_assignment;
	public $level;
	public $email;


	public function __construct()
	{
		parent::__construct();
		$this->username = $this->session->userdata('username');
		if ($this->username === NULL)
			return;

		$user = $this->db
			->select('selected_assignment, role, email')
			->get_where('users', array('username' => $this->username))
			->row();

		$this->email = $user->email;

		$query = $this->db->get_where('assignments', array('id' => $user->selected_assignment));
		if ($query->num_rows() != 1)
			$this->selected_assignment = array(
				'id' => 0,
				'name' => 'Not Selected',
				'finish_time' => 0,
				'extra_time' => 0,
				'problems' => 0
			);
		else
			$this->selected_assignment = $query->row_array();

		switch ($user->role)
		{
			case 'admin': $this->level = 3; break;
			case 'head_instructor': $this->level = 2; break;
			case 'instructor': $this->level = 1; break;
			case 'student': $this->level = 0; break;
		}
	}


	// ------------------------------------------------------------------------


	/**
	 * Select Assignment
	 *
	 * Sets selected assignment for $username
	 *
	 * @param $assignment_id
	 */
	public function select_assignment($assignment_id)
	{
		$this->db->where('username', $this->username)->update('users', array('selected_assignment'=>$assignment_id));
	}


	// ------------------------------------------------------------------------


	/**
	 * Save Widget Positions
	 *
	 * Updates position of dashboard widgets in database
	 *
	 * @param $positions
	 */
	public function save_widget_positions($positions)
	{
		$this->db
			->where('username', $this->username)
			->update('users', array('dashboard_widget_positions'=>$positions));
	}


	// ------------------------------------------------------------------------


	/**
	 * Get Widget Positions
	 *
	 * Returns positions of dashboard widgets from database
	 *
	 * @param none
	 * @return mixed
	 */
	public function get_widget_positions()
	{
		return json_decode(
			$this->db->select('dashboard_widget_positions')
			->get_where('users', array('username' => $this->username))
			->row()
			->dashboard_widget_positions,
			TRUE
		);
	}

}