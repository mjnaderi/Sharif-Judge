<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Library to wrap Twig layout engine. Originally from Bennet Matschullat.
 * Code cleaned up to CodeIgniter standards by Erik Torsner
 *
 * PHP Version 5.3
 *
 * @category Layout
 * @package  Twig
 * @author   Bennet Matschullat <bennet@3mweb.de>
 * @author   Erik Torsner <erik@torgesta.com>
 * @license  Don't be a dick http://www.dbad-license.org/
 * @link     https://github.com/bmatschullat/Twig-Codeigniter
 */

/**
 * Main (and only) class for the Twig wrapper library
 */
class Twig
{
	const TWIG_CONFIG_FILE = 'twig';

	/**
	 * Path to templates. Usually application/views.
	 *
	 * @var string
	 */
	protected $template_dir;

	/**
	 * Path to cache.  Usually applcation/cache.
	 *
	 * @var string
	 */
	protected $cache_dir;

	/**
	 * Reference to code CodeIgniter instance.
	 *
	 * @var CodeIgniter object
	 */
	private $_ci;

	/**
	 * Twig environment see http://twig.sensiolabs.org/api/v1.8.1/Twig_Environment.html.
	 *
	 * @var Twig_Envoronment object
	 */
	private $twig;

	/**
	 * constructor of twig ci class
	 */
	public function __construct()
	{
		$this->_ci = & get_instance();
		$this->_ci->config->load(self::TWIG_CONFIG_FILE); // load config file
		// set include path for twig
		//ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ');
		require_once APPPATH.'third_party/Twig/lib/Twig/Autoloader.php';
		// register autoloader
		Twig_Autoloader::register();
		log_message('debug', 'twig autoloader loaded');
		// init paths
		$this->template_dir = $this->_ci->config->item('template_dir');
		$this->cache_dir = $this->_ci->config->item('cache_dir');
		// load environment
		$loader = new Twig_Loader_Filesystem($this->template_dir);
		$this->twig = new Twig_Environment($loader, array(
			'cache' => $this->cache_dir,
			'auto_reload' => TRUE
		));
		$this->ci_function_init();
	}

	/**
	 * render a twig template file
	 *
	 * @param string $template template name
	 * @param array $data contains all varnames
	 * @param boolean $render render or return raw?
	 *
	 * @return string
	 *
	 */
	public function render($template, $data = array(), $render = TRUE)
	{
		$template = $this->twig->loadTemplate($template);
		return ($render) ? $template->render($data) : $template;
	}

	/**
	 * Execute the template and send to CI output
	 *
	 * @param string $template Name of template
	 * @param array $data Parameters for template
	 *
	 * @return void
	 *
	 */
	public function display($template, $data = array())
	{
		$template = $this->twig->loadTemplate($template);
		$this->_ci->output->append_output($template->render($data));
	}

	/**
	 * Entry point for controllers (and the likes) to register
	 * callback functions to be used from Twig templates
	 *
	 * @param string $name name of function
	 * @param Twig_FunctionInterface $function Function pointer
	 *
	 * @return void
	 *
	 */
	public function register_function($name, Twig_FunctionInterface $function)
	{
		$this->twig->addFunction($name, $function);
	}

	/**
	 * Initialize standard CI functions
	 *
	 * @return void
	 */
	public function ci_function_init()
	{
		$this->twig->addGlobal('SHJ_VERSION', SHJ_VERSION);

		/* Functions */
		$this->twig->addFunction('base_url', new Twig_Function_Function('base_url', array('is_safe' => array('html'))));
		$this->twig->addFunction('site_url', new Twig_Function_Function('site_url', array('is_safe' => array('html'))));
		$this->twig->addFunction('anchor', new Twig_Function_Function('anchor'));
		$this->twig->addFunction('shj_now_str', new Twig_Function_Function('shj_now_str', array('is_safe' => array('html'))));
		$this->twig->addFunction('rtrim', new Twig_Function_Function('rtrim'));
		$this->twig->addFunction('floor', new Twig_Function_Function('floor'));
		$this->twig->addFunction('ceil', new Twig_Function_Function('ceil'));
		$this->twig->addFunction('isset', new Twig_Function_Function('isset'));
		$this->twig->addFunction('time_hhmm', new Twig_Function_Function('time_hhmm', array('is_safe' => array('html'))));
		$this->twig->addFunction('md5', new Twig_Function_Function('md5', array('is_safe' => array('html'))));
		// form functions
		$this->twig->addFunction('form_open', new Twig_Function_Function('form_open', array('is_safe' => array('html'))));
		$this->twig->addFunction('form_open_multipart', new Twig_Function_Function('form_open_multipart', array('is_safe' => array('html'))));
		$this->twig->addFunction('form_error', new Twig_Function_Function('form_error', array('is_safe' => array('html'))));
		$this->twig->addFunction('set_value', new Twig_Function_Function('set_value'));
		$this->twig->addFunction('set_checkbox', new Twig_Function_Function('set_checkbox'));
		/*$this->twig->addFunction('form_hidden', new Twig_Function_Function('form_hidden'));
		$this->twig->addFunction('form_input', new Twig_Function_Function('form_input'));
		$this->twig->addFunction('form_password', new Twig_Function_Function('form_password'));
		$this->twig->addFunction('form_upload', new Twig_Function_Function('form_upload'));
		$this->twig->addFunction('form_textarea', new Twig_Function_Function('form_textarea'));
		$this->twig->addFunction('form_dropdown', new Twig_Function_Function('form_dropdown'));
		$this->twig->addFunction('form_multiselect', new Twig_Function_Function('form_multiselect'));
		$this->twig->addFunction('form_fieldset', new Twig_Function_Function('form_fieldset'));
		$this->twig->addFunction('form_fieldset_close', new Twig_Function_Function('form_fieldset_close'));
		$this->twig->addFunction('form_checkbox', new Twig_Function_Function('form_checkbox'));
		$this->twig->addFunction('form_radio', new Twig_Function_Function('form_radio'));
		$this->twig->addFunction('form_submit', new Twig_Function_Function('form_submit'));
		$this->twig->addFunction('form_label', new Twig_Function_Function('form_label'));
		$this->twig->addFunction('form_reset', new Twig_Function_Function('form_reset'));
		$this->twig->addFunction('form_button', new Twig_Function_Function('form_button'));
		$this->twig->addFunction('form_close', new Twig_Function_Function('form_close'));
		$this->twig->addFunction('form_prep', new Twig_Function_Function('form_prep'));
		$this->twig->addFunction('set_select', new Twig_Function_Function('set_select'));
		$this->twig->addFunction('set_checkbox', new Twig_Function_Function('set_checkbox'));
		$this->twig->addFunction('set_radio', new Twig_Function_Function('set_radio'));*/

		// This filter is used in add_assignment.twig
		$this->twig->addFilter(
			new Twig_SimpleFilter(
				'extra_time_formatter',
				function ($extra_time) {
					// convert to minutes
					$extra_time = floor($extra_time/60);
					// convert to H*60
					if ($extra_time % 60 == 0 )
						$extra_time = ($extra_time/60) . '*60';
					return $extra_time;
				}
			)
		);

		$this->_ci->load->model('user');
		$this->twig->addGlobal('user', $this->_ci->user);

	}
}

/* End of file Twig.php */
/* Location: ./libraries/Twig.php */