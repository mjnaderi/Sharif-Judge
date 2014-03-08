<?php
/**
 * Sharif Judge online judge
 * @file User_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	/**
	 * Have User
	 *
	 * Returns TRUE if there is a user with username $username in database
	 *
	 * @param $username
	 * @return bool
	 */
	public function have_user($username)
	{
		$query = $this->db->get_where('users', array('username'=>$username));
		if ($query->num_rows() == 0)
			return FALSE;
		if ($username === $query->row()->username) // needed (because of utf8_general_ci [ci=case insensitive])
			return TRUE;
		return FALSE;
	}


	// ------------------------------------------------------------------------


	/**
	 * User ID to Username
	 *
	 * Converts user id to username (returns FALSE if user does not exist)
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function user_id_to_username($user_id)
	{
		if( ! is_numeric($user_id))
			return FALSE;
		$query = $this->db->select('username')->get_where('users', array('id'=>$user_id));
		if ($query->num_rows() == 0)
			return FALSE;
		return $query->row()->username;
	}


	// ------------------------------------------------------------------------


	/**
	 * Username to User ID
	 *
	 * Converts username to user id (returns FALSE if user does not exist)
	 *
	 * @param $username
	 * @return bool
	 */
	public function username_to_user_id($username)
	{
		$query = $this->db->select('id')->get_where('users', array('username'=>$username));
		if ($query->num_rows() == 0)
			return FALSE;
		return $query->row()->id;
	}


	// ------------------------------------------------------------------------


	/**
	 * Have Email
	 *
	 * Returns TRUE if a user (except $username) with given email exists
	 *
	 * @param $email
	 * @param bool $username
	 * @return bool
	 */
	public function have_email($email, $username = FALSE)
	{
		$query = $this->db->get_where('users', array('email'=>$email));
		if ($query->num_rows() >= 1){
			if($username !== FALSE && $query->row()->username == $username)
				return FALSE;
			else
				return TRUE;
		}
		return FALSE;
	}


	// ------------------------------------------------------------------------


	/**
	 * Add User
	 *
	 * Adds a single user
	 *
	 * @param $username
	 * @param $email
	 * @param $password
	 * @param $role
	 * @return bool|string
	 */
	public function add_user($username, $email, $password, $role)
	{
		if ( ! $this->form_validation->alpha_numeric($username) )
			return 'Username may only contain alpha-numeric characters.';
		if (strlen($username) < 3 OR strlen($username) > 20 OR strlen($password) < 6 OR strlen($password) > 200)
			return 'Username or password length error.';
		if ($this->have_user($username))
			return 'User with this username exists.';
		if ($this->have_email($email))
			return 'User with this email exists.';
		if (strtolower($username) !== $username)
			return 'Username must be lowercase.';
		$roles = array('admin', 'head_instructor', 'instructor', 'student');
		if ( ! in_array($role, $roles))
			return 'Users role is not valid.';
		$this->load->library('password_hash', array(8, FALSE));
		$user=array(
			'username' => $username,
			'email' => $email,
			'password' => $this->password_hash->HashPassword($password),
			'role' => $role
		);
		$this->db->insert('users', $user);
		return TRUE; //success
	}


	// ------------------------------------------------------------------------


	/**
	 * Add Users
	 *
	 * Adds multiple users
	 *
	 * @param $text
	 * @param $send_mail
	 * @param $delay
	 * @return array
	 */
	public function add_users($text, $send_mail, $delay)
	{

		$lines = preg_split('/\r?\n|\n?\r/', $text);
		$users_ok = array();
		$users_error = array();

		// loop over lines of $text :
		foreach ($lines as $line)
		{
			$line = trim($line);

			if (strlen($line) == 0 OR $line[0] == '#')
				continue; //ignore comments and empty lines

			$parts = preg_split('/\s+/', $line);
			if (count($parts) != 4)
				continue; //ignore lines that not contain 4 parts

			if (strtolower(substr($parts[2], 0, 6)) == 'random')
			{
				// generate random password
				$len = trim(substr($parts[2], 6), '[]');
				if (is_numeric($len)){
					$this->load->helper('string');
					$parts[2] = shj_random_password($len);
				}
			}

			$result = $this->add_user($parts[0], $parts[1], $parts[2], $parts[3]);

			if ($result === TRUE)
				array_push($users_ok, array($parts[0], $parts[1], $parts[2], $parts[3]));
			else
				array_push($users_error, array($parts[0], $parts[1], $parts[2], $parts[3], $result));

		} // end of loop

		if ($send_mail)
		{
			// sending usernames and passwords by email
			$this->load->library('email');
			$config = array(
				'mailtype'  => 'html',
				'charset'   => 'iso-8859-1'
			);
			/*
			// You can use gmail's smtp server
			$config = Array(
				'protocol' => 'smtp',
				'smtp_host' => 'ssl://smtp.googlemail.com',
				'smtp_port' => 465,
				'smtp_user' => 'example@gmail.com',
				'smtp_pass' => 'your-gmail-password',
				'mailtype'  => 'html',
				'charset'   => 'iso-8859-1'
			);
			*/
			$this->email->initialize($config);
			$this->email->set_newline("\r\n");
			$count_users = count($users_ok);
			$counter = 0;
			foreach ($users_ok as $user)
			{
				$counter++;
				$this->email->from($this->settings_model->get_setting('mail_from'), $this->settings_model->get_setting('mail_from_name'));
				$this->email->to($user[1]);
				$this->email->subject('Sharif Judge Username and Password');
				$text = $this->settings_model->get_setting('add_user_mail');
				$text = str_replace('{SITE_URL}', base_url(), $text);
				$text = str_replace('{ROLE}', $user[3], $text);
				$text = str_replace('{USERNAME}', $user[0], $text);
				$text = str_replace('{PASSWORD}', htmlspecialchars($user[2]), $text);
				$text = str_replace('{LOGIN_URL}', base_url(), $text);
				$this->email->message($text);
				$this->email->send();
				if ($counter < $count_users)
					sleep($delay);
			}
		}

		return array($users_ok, $users_error);

	}


	// ------------------------------------------------------------------------


	/**
	 * Delete User
	 *
	 * Deletes a user with given user id
	 * Returns TRUE (success) or FALSE (failure)
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function delete_user($user_id)
	{
		$this->db->trans_start();

		$username = $this->user_id_to_username($user_id);
		if ($username === FALSE)
			return FALSE;
		$this->db->delete('users', array('id'=>$user_id));
		$this->db->delete('submissions', array('username' => $username));
		// each time we delete a user, we should update all scoreboards
		$this->load->model('scoreboard_model');
		$this->scoreboard_model->update_scoreboards();

		$this->db->trans_complete();

		if ($this->db->trans_status()) {
			// Delete submitted files
			shell_exec("cd {$this->settings_model->get_setting('assignments_root')}; rm -r */*/{$username};");
			return TRUE; //success
		}
		return FALSE; // failure
	}


	// ------------------------------------------------------------------------


	/**
	 * Delete Submissions
	 *
	 * Deletes all submissions of user with given user id
	 * Returns TRUE (success) or FALSE (failure)
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function delete_submissions($user_id)
	{
		$this->db->trans_start();

		$username = $this->user_id_to_username($user_id);
		if ($username === FALSE)
			return FALSE;
		// delete all submissions from database
		$this->db->delete('submissions', array('username'=>$username));
		// each time we delete a user's submissions, we should update all scoreboards
		$this->load->model('scoreboard_model');
		$this->scoreboard_model->update_scoreboards();

		$this->db->trans_complete();

		if ($this->db->trans_status()) {
			// delete all submitted files
			shell_exec("cd {$this->settings_model->get_setting('assignments_root')}; rm -r */*/{$username};");
			return TRUE; // success
		}

		return FALSE; // failure
	}


	// ------------------------------------------------------------------------


	/**
	 * Validate User
	 *
	 * Returns TRUE if given username and password is valid for login
	 *
	 * @param $username
	 * @param $password
	 * @return bool
	 */
	public function validate_user($username, $password)
	{
		$this->load->library('password_hash', array(8, FALSE));
		$query = $this->db->get_where('users', array('username' => $username));
		if ($query->num_rows() != 1)
			return FALSE;
		if ($query->row()->username !== $username) // needed (because of utf8_general_ci [ci=case insensitive])
			return FALSE;
		if ($this->password_hash->CheckPassword($password, $query->row()->password))
			return TRUE;
		return FALSE;
	}


	// ------------------------------------------------------------------------


	/**
	 * Selected Assignment
	 *
	 * Returns selected assignment by given username
	 * @param $username
	 * @return mixed
	 */
	public function selected_assignment($username)
	{
		$query = $this->db->select('selected_assignment')->get_where('users', array('username'=>$username));
		if ($query->num_rows() != 1){//logout
			$this->session->sess_destroy();
			redirect('login');
		}
		return $query->row()->selected_assignment;
	}


	// ------------------------------------------------------------------------


	/**
	 * Get Display Name
	 *
	 * Returns name of the user with given username
	 *
	 * @return array
	 */
	public function get_names()
	{
		$query = $this->db->select('username, display_name')->get('users');
		$tmp = $query->result_array();
		$result = array();
		foreach ($tmp as $row)
			$result[$row['username']] = $row['display_name'];
		return $result;
	}


	// ------------------------------------------------------------------------


	/**
	 * Update Profile
	 *
	 * Updates User Profile (Name, Email, Password, Role)
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function update_profile($user_id)
	{
		$query = $this->db->get_where('users', array('id'=>$user_id));
		if ($query->num_rows() != 1)
			return FALSE;
		$the_user = $query->row();
		$username = $the_user->username;

		$user=array(
			'display_name' => $this->input->post('display_name'),
			'email' => $this->input->post('email')
		);

		// if a role is provided, change the role
		// (only admins are able to provide a role)
		if ($this->input->post('role') !== NULL)
			$user['role'] = $this->input->post('role');

		// if a password is provided, change the password:
		if ($this->input->post('password') != ''){
			$this->load->library('password_hash', array(8, FALSE));
			$user['password'] = $this->password_hash->HashPassword($this->input->post('password'));
		}

		$this->db->where('username', $username)->update('users', $user);
	}


	// ------------------------------------------------------------------------


	/**
	 * Send Password Reset Mail
	 *
	 * Generates a password reset key and sends an email containing the link
	 * for resetting password (in case of password lost)
	 *
	 * @param $email
	 */
	public function send_password_reset_mail($email)
	{
		// exit if $email is invalid:
		if ( ! $this->have_email($email) )
			return;

		// generate a random password reset key:
		$this->load->helper('string');
		$passchange_key = random_string('alnum', 50);

		// save the key in users table:
		$now = shj_now();
		$this->db->where('email', $email)->update('users', array('passchange_key'=>$passchange_key, 'passchange_time'=>date('Y-m-d H:i:s', $now)));

		// send the email:
		$this->load->library('email');
		$config = array(
			'mailtype'  => 'html',
			'charset'   => 'iso-8859-1'
		);
		/*
		// You can use gmail's smtp server
		$config = Array(
			'protocol' => 'smtp',
			'smtp_host' => 'ssl://smtp.googlemail.com',
			'smtp_port' => 465,
			'smtp_user' => 'example@gmail.com',
			'smtp_pass' => 'your-gmail-password',
			'mailtype'  => 'html',
			'charset'   => 'iso-8859-1'
		);
		*/
		$this->email->initialize($config);
		$this->email->set_newline("\r\n");
		$this->email->from($this->settings_model->get_setting('mail_from'), $this->settings_model->get_setting('mail_from_name'));
		$this->email->to($email);
		$this->email->subject('Password Reset');
		$text = $this->settings_model->get_setting('reset_password_mail');
		$text = str_replace('{SITE_URL}', base_url(), $text);
		$text = str_replace('{RESET_LINK}', site_url('login/reset/'.$passchange_key), $text);
		$text = str_replace('{VALID_TIME}', '1 hour', $text); // links are valid for 1 hour
		$this->email->message($text);
		$this->email->send();
	}


	// ------------------------------------------------------------------------


	/**
	 * Password Reset Key Is Valid
	 *
	 * Returns TRUE if the given password reset key is valid
	 * And returns an error message if key is invalid
	 *
	 * @param $passchange_key
	 * @return bool|string
	 */
	public function passchange_is_valid($passchange_key)
	{
		$query = $this->db->select('passchange_time')->get_where('users', array('passchange_key'=>$passchange_key));
		if ($query->num_rows() != 1)
			return 'Invalid password reset link.';
		$time = strtotime($query->row()->passchange_time);
		$now = shj_now();
		if ($now-$time > 3600 OR $now-$time < 0) // reset link is valid for 1 hour
			return 'The link is expired.';
		return TRUE;
	}


	// ------------------------------------------------------------------------


	/**
	 * Reset Password
	 *
	 * Resets password for given password reset key (in case of lost password)
	 *
	 * @param $passchange_key
	 * @param $newpassword
	 * @return bool
	 */
	public function reset_password($passchange_key, $newpassword)
	{
		$query = $this->db->get_where('users', array('passchange_key'=>$passchange_key));
		if ($query->num_rows() != 1)
			return FALSE; //failure
		$this->load->library('password_hash', array(8, FALSE));
		$this->db->where('username', $query->row()->username)->update('users', array('passchange_key'=>'', 'password' => $this->password_hash->HashPassword($newpassword)));
		return TRUE; //success
	}


	// ------------------------------------------------------------------------


	/**
	 * Get All Users
	 *
	 * Returns an array of all users (for Users page)
	 *
	 * @return mixed
	 */
	public function get_all_users()
	{
		return $this->db->order_by('role', 'asc')->order_by('id')->get('users')->result_array();
	}


	// ------------------------------------------------------------------------


	/**
	 * Get User
	 *
	 * Returns database row for given user id
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function get_user($user_id)
	{
		$query = $this->db->get_where('users', array('id'=>$user_id));
		if ($query->num_rows() != 1)
			return FALSE;
		return $query->row();
	}


	// ------------------------------------------------------------------------


	/**
	 * Update Login Time
	 *
	 * Updates First Login Time and Last Login Time for given username
	 *
	 */
	public function update_login_time($username)
	{
		$now = shj_now_str();

		$first_login = $this->db->select('first_login_time')->get_where('users', array('username'=>$username))->row()->first_login_time;
		if ($first_login === NULL)
			$this->db->where('username', $username)->update('users', array('first_login_time'=>$now));

		$this->db->where('username', $username)->update('users', array('last_login_time'=>$now));
	}





}