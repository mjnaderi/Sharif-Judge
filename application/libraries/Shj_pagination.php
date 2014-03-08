<?php
/**
 * Sharif Judge online judge
 * @file Shj_pagination.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Shj_pagination
{

	private $base_url;
	private $cur_page;
	private $total_rows;
	private $per_page;
	private $num_links;
	private $ul_class;
	private $cur_li_class;
	private $total_pages;

	public function __construct($config = array())
	{
		$this->base_url = $config['base_url']; // assuming it has no trailing slash
		$this->cur_page = $config['cur_page'];
		$this->total_rows = $config['total_rows'];
		$this->per_page = $config['per_page'];
		$this->num_links = $config['num_links'];
		$this->ul_class = $config['full_ul_class'];
		$this->cur_li_class = $config['cur_li_class'];
		if ($config['per_page'] != 0)
			$this->total_pages = ceil($config['total_rows']/$config['per_page']);
	}

	public function create_links()
	{
		if ($this->per_page == 0)
			return '';

		$output = '<ul class="'.$this->ul_class.'">';

		if ($this->cur_page > $this->total_pages)
			$this->cur_page = $this->total_pages;

		$start_page = $this->cur_page - $this->num_links;
		if ($start_page <= 0)
			$start_page = 1;

		$end_page = $this->cur_page + $this->num_links;
		if ($end_page > $this->total_pages)
			$end_page = $this->total_pages;

		if ($end_page == 1 || $end_page == 0)
			return '';

		// Rendering Output

		if ($this->cur_page != 1)
		{
			$output .= '<li><a href="'.$this->base_url.'">&lsaquo; First</a></li>';
			$output .= '<li><a href="'.$this->base_url.'/page/'.($this->cur_page-1).'">&lsaquo;</a></li>';
		}

		for ($i = $start_page; $i <= $end_page; $i++)
		{
			$output .= '<li'.($i==$this->cur_page?' class="current_page"':'').'><a href="'.$this->base_url.'/page/'.$i.'">'.$i.'</a></li>';
		}

		if ($this->cur_page != $this->total_pages)
		{
			$output .= '<li><a href="'.$this->base_url.'/page/'.($this->cur_page+1).'">&rsaquo;</a></li>';
			$output .= '<li><a href="'.$this->base_url.'/page/'.$this->total_pages.'">Last ('.$this->total_pages.') &rsaquo;</a></li>';
		}

		$output .= '</ul>';

		return $output;

	}
}

/* End of file Shj_pagination.php */
/* Location: ./application/libraries/Shj_pagination.php */