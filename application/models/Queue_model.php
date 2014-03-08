<?php
/**
 * Sharif Judge online judge
 * @file Queue_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Queue_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns TRUE if one submission with $username, $assignment and $problem
	 * is already in queue (for preventing multiple submission)
	 */
	public function in_queue ($username, $assignment, $problem)
	{
		$query = $this->db->get_where('queue', array('username'=>$username, 'assignment'=>$assignment, 'problem'=>$problem));
		return ($query->num_rows() > 0);
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns all the submission queue
	 */
	public function get_queue ()
	{
		return $this->db->get('queue')->result_array();
	}


	// ------------------------------------------------------------------------


	/**
	 * Empties the queue
	 */
	public function empty_queue ()
	{
		return $this->db->empty_table('queue');
	}


	// ------------------------------------------------------------------------


	public function add_to_queue($submit_info)
	{

		$submit_info['is_final'] = 0;
		$submit_info['status'] = 'PENDING';

		$this->db->insert('submissions', $submit_info);

		$this->db->insert('queue', array(
			'submit_id' => $submit_info['submit_id'],
			'username' => $submit_info['username'],
			'assignment' => $submit_info['assignment'],
			'problem' => $submit_info['problem'],
			'type' => 'judge'
		));
	}


	// ------------------------------------------------------------------------


	/**
	 * Adds submissions of a problem to queue for rejudge
	 */
	public function rejudge($assignment_id, $problem_id)
	{
		$problem = $this->assignment_model->problem_info($assignment_id, $problem_id);
		if ($problem['is_upload_only'])
			return;

		// Changing the status of all submissions of selected problem to PENDING

		$this->db->where(
			array(
				'assignment' => $assignment_id,
				'problem' => $problem_id
			)
		)->update('submissions', array('pre_score' => 0, 'status' => 'PENDING'));

		// Adding submissions to queue:

		$submissions = $this->db
			->select('submit_id, username, assignment, problem')
			->order_by('submit_id')
			->get_where('submissions', array('assignment'=>$assignment_id, 'problem'=>$problem_id))
			->result_array();

		foreach($submissions as $submission)
		{
			$this->db->insert('queue',
				array(
					'submit_id' => $submission['submit_id'],
					'username' => $submission['username'],
					'assignment' => $submission['assignment'],
					'problem' => $submission['problem'],
					'type' => 'rejudge'
				)
			);
		}
		// Now ready for rejudge
	}


	// ------------------------------------------------------------------------


	/**
	 * Adds a single submission to queue for rejudge
	 */
	public function rejudge_single($submission)
	{
		$problem = $this->assignment_model->problem_info($submission['assignment'], $submission['problem']);
		if ($problem['is_upload_only'])
			return;

		// Changing the status of submission to PENDING
		$this->db->where(array(
			'submit_id' => $submission['submit_id'],
			'username' => $submission['username'],
			'assignment' => $submission['assignment'],
			'problem' => $submission['problem']
		))->update('submissions', array('pre_score'=>0, 'status'=>'PENDING'));

		// Adding Submission to Queue
		$this->db->insert('queue', array(
			'submit_id' => $submission['submit_id'],
			'username' => $submission['username'],
			'assignment' => $submission['assignment'],
			'problem' => $submission['problem'],
			'type' => 'rejudge'
		));
		// Now ready for rejudge
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns the first item of the queue
	 */
	public function get_first_item()
	{
		$query = $this->db->order_by('id')->limit(1)->get('queue');
		if ($query->num_rows() != 1)
			return NULL;
		return $query->row_array();
	}


	// ------------------------------------------------------------------------


	/**
	 * Removes an item from the queue
	 */
	public function remove_item($username, $assignment, $problem, $submit_id)
	{
		$this->db->delete('queue', array(
			'submit_id' => $submit_id,
			'username' => $username,
			'assignment' => $assignment,
			'problem' => $problem
		));
	}


	// ------------------------------------------------------------------------


	/**
	 * Saves the result of judge in database
	 * This function is called from Queueprocess controller
	 */
	public function save_judge_result_in_db ($submission, $type)
	{

		$arr = array(
			'status' => $submission['status'],
			'pre_score' => $submission['pre_score'],
		);

		if ($type === 'judge')
		{
			$this->db->where(array(
				'is_final' => 1,
				'username' => $submission['username'],
				'assignment' => $submission['assignment'],
				'problem' => $submission['problem'],
			))->update('submissions', array('is_final'=>0));
			$arr['is_final'] = 1;
		}

		$this->db->where(array(
			'submit_id' => $submission['submit_id'],
			'username' => $submission['username'],
			'assignment' => $submission['assignment'],
			'problem' => $submission['problem']
		))->update('submissions', $arr);

		// update scoreboard:
		$this->load->model('scoreboard_model');
		$this->scoreboard_model->update_scoreboard($submission['assignment']);
	}

}