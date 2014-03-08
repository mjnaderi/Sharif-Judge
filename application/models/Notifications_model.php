<?php
/**
 * Sharif Judge online judge
 * @file Notifications_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns all notifications
	 */
	public function get_all_notifications()
	{
		return $this->db
			->order_by('id', 'desc')
			->get('notifications')
			->result_array();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns 10 latest notifications
	 */
	public function get_latest_notifications()
	{
		$result = $this->db
			->limit(10)
			->order_by('id', 'desc')
			->get('notifications')
			->result_array();
		foreach($result as &$item)
		{
			$item['text'] = substr(trim(strip_tags($item['text'])), 0, 300);
			$item['text'] = str_replace('&zwnj;', "\xE2\x80\x8c", $item['text']);
			$item['text'] = html_entity_decode($item['text']);
			$item['text'] = preg_replace('/\r?\n|\n?\r/', ' ', $item['text']);
		}
		return $result;
	}


	// ------------------------------------------------------------------------


	/**
	 * Add a new notification
	 */
	public function add_notification($title, $text)
	{
		$now = shj_now_str();
		$this->db->insert('notifications',
			array(
				'title' => $title,
				'text' => $text,
				'time' => $now
			)
		);
	}


	// ------------------------------------------------------------------------


	/**
	 * Update (edit) a notification
	 */
	public function update_notification($id, $title, $text)
	{
		$this->db
			->where('id', $id)
			->update('notifications',
				array(
					'title' => $title,
					'text' => $text
				)
			);
	}


	// ------------------------------------------------------------------------


	/**
     * Delete a notification
	 */
	public function delete_notification($id)
	{
		$this->db->delete('notifications', array('id' => $id));
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns a notification
	 */
	public function get_notification($notif_id)
	{
		$query = $this->db->get_where('notifications', array('id' => $notif_id));
		if ($query->num_rows() != 1)
			return FALSE;
		return $query->row_array();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns true if there is a notification after $time
	 * @todo optimize: check the ">" condition in sql query
	 */
	public function have_new_notification($time)
	{
		$notifs = $this->db->select('time')->get('notifications')->result_array();
		foreach ($notifs as $notif) {
			if (strtotime($notif['time']) > $time)
				return TRUE;
		}
		return FALSE;
	}

}