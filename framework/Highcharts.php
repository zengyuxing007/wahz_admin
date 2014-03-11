<?php 
/**
 * http://www.highcharts.com/ref/#series--legendIndex
 * set_type 
 */
class Highcharts {
	
	private static $chart_id = 0;
	
	private $shared_opts 	= array(); // shared grah data
	private $global_opts	= array(); // All stocked graph data
	private $opts					= array(); // current graph data
	private $replace_keys, $orig_keys, $serie_index = 0;
	
	public $js_chart_name = 'chart'; // name of the js var	

		
	/**
	 * __call function.
	 * 
	 * @access public
	 * @param mixed $func
	 * @param mixed $args
	 * @return void
	 */
	public function __call($func, $args)
	{
		if (strpos($func, 'set_') !== false){
			$_arr = explode('set_', $func);
			if(isset($_arr[1]) and $_arr[1]){
				$this->opts[$_arr[1]] = $args;
			}
		}
		
		if (strpos($func,'_'))
		{
			list($action, $type) = explode('_', $func);
		
			if (! isset($this->opts[$type]))
			{
				$this->opts[$type] = array();
			}
		}
		
		return $this;
	}
	
	function __construct($id = ''){
		if ($id){
			$this->render_to($id);
		}
		$this->opts['series'] = array();
		
		$this->opts['credits']['enabled'] = false;
		$this->opts['chart']['zoomType'] = 'x';
	}
	
	/**
	 * set_title function.
	 * set title and subtitle in one shoot
	 * 
	 * @access public
	 * @param string $title. (default: '')
	 * @param array $options. (default: array())
	 * @return void
	 */
	public function set_title($title = '', $subtitle = '')
	{
		if ($title) $this->opts['title']['text'] = $title;
		if ($subtitle) $this->opts['subtitle']['text'] = $subtitle;

		return $this;
	}
	

	//设置 xAxis yAxis
	/**
	 * set_Axis(array('categories'=>'2,4'))
	 * Enter description here ...
	 * @param unknown_type $option
	 * @param unknown_type $type
	 */
	public function set_Axis($option = '', $type = 'y'){
		
		if (is_array($option)){
			foreach ($option as $key=>$value){
				//@todo 无效 if (isset($value['title']))
				//$this->opts[$type . 'Axis'][]['title'] = $value['title'];
				$this->opts[$type . 'Axis'][$key] = $value;
			}
		}
		return $this;
	}
	
	/**
	 * set_axis_titles function.
	 * quickly set x and y texts
	 * 
	 * @access public
	 * @param string $x_label. (default: '')
	 * @param string $y_label. (default: '')
	 * @return void
	 */
	function set_axis_titles($x_title = '', $y_title = '')
	{
		if ($x_title) $this->opts['xAxis']['title']['text'] = $x_title;
		if ($y_title) $this->opts['yAxis']['title']['text'] = $y_title;
		
		return $this;
	}
	
	/**
	 * render_to function.
	 * set the container's id to render the graph
	 * 
	 * @access public
	 * @param string $id. (default: '')
	 * @return void
	 */
	public function render_to($id = '')
	{
		$this->opts['chart']['renderTo'] = $id;

		return $this;
	}
	
	/**
	 * set_type function.
	 * The default series type for the chart
	 * 
	 * @access public
	 * @param string $type. (default: '')  column | line | ...
	 * @return void
	 */
	public function set_type($type = '')
	{
		if ($type AND is_string($type)) $this->opts['chart']['type'] = $type;
		
		return $this;
	}
	
	/**
	 * set_dimensions function.
	 * fastly set dimension of the graph is desired
	 * 
	 * @access public
	 * @param mixed $width. (default: null)
	 * @param mixed $height. (default: null)
	 * @return void
	 */
	public function set_dimensions($width = null, $height = null)
	{
		if ($width)  $this->opts['chart']['width'] = (int)$width;
		if ($height) $this->opts['chart']['height'] = (int)$height;
		
		return $this;
	}

	public function set_data($type,$result){
		$this->opts[$type] = $result;
		return $this;	
	}
	
	public function set_tooltip($code='') {
		if ($code) {
			$this->opts['tooltip']['formatter'] = $code;
		} else {
			$this->opts['tooltip']['formatter'] = "function() { 
														return  this.y ;
													}";
		}
		return $this;
    }
    
	/**
	 * set_serie function.
     *
	 * @access public
	 * @param string $s_serie_name. (default: '')
	 * @param array $a_value. (default: array())
	 * @return void
	 */
	public function set_serie($options = array(), $serie_name = '')
	{
		if ( ! $serie_name AND ! isset($options['name']))
		{
			$serie_name = count($this->opts['series']);
		}
		// override with the serie name passed
		else if ($serie_name AND isset($options['name']))
		{
			$options['name'] = $serie_name;
		}
		
		$index = $this->find_serie_name($serie_name);
		    		
		if (count($options) > 0)
		{
		    foreach($options as $key => $value)
		    {
					$value = (is_numeric($value)) ? (float)$value : $value;
					$this->opts['series'][$index][$key] = $value;
		    }
		}
		return $this;
	}
	
	/**
	 * set_serie_option function.
	 * We are settings each serie options for graph
	 * 
	 * @access public
	 * @param string $s_serie_name. (default: '')
	 * @param string $s_option. (default: '')
	 * @param string $value. (default: '')
	 * @return void
	 */
	public function set_serie_options($options = array(), $serie_name = '')
	{
		if ($serie_name AND count($options) > 0)
		{
			$index = $this->find_serie_name($serie_name);
						
			foreach ($options as $key => $opt)
			{
				$this->opts['series'][$index][$key] = $opt;
			}
		}
		return $this;
	}
	
	/**
	 * push_serie_data function.
	 * 
	 * @access public
	 * @param string $s_serie_name. (default: '')
	 * @param string $s_value. (default: '')
	 * @return void
	 */
	public function push_serie_data($value = '', $serie_name = ''){
		
		if ($serie_name AND $value)
		{
			$index = $this->find_serie_name($serie_name);
			
			$value = (is_numeric($value)) ? (float)$value : $value;
				
			$this->opts['series'][$index]['data'][] = $value;
		}
		return $this;
	}
	
	
	/**
	 * find_serie_name function.
	 * fonction qui permet de savoir si une série existe
	 * 
	 * @access private
	 * @return void
	 */
	private function find_serie_name($name)
	{
		$tot_indexes = count($this->opts['series']);
		
		if ($tot_indexes > 0)
		{
			foreach($this->opts['series'] as $index => $serie)
			{
				if (isset($serie['name']) AND strtolower($serie['name']) == strtolower($name))
				{
					return $index;
				}
			}
		}
		
		$this->opts['series'][$tot_indexes]['name'] = $name;
		
		return $tot_indexes;
	}
	
	
	/**
	 * push_categorie function.
	 * Add custom name to axes.
	 * 
	 * @access public
	 * @param mixed $value
	 * @return void
	 */
	public function push_categorie($value, $axis = 'x')
	{
		if(trim($value)!= '') $this->opts[$axis.'Axis']['categories'][] = $value;

		return $this;
	}	
	
	public function set_categorie($option, $axis= 'x'){
		if (is_array($option)){
			$this->opts[$axis . 'Axis']['categories'] = $option;
		} else {
			if(trim($option)!= '') $this->opts[$axis.'Axis']['categories'][] = $option;
		}
		return $this;
	}

	
	
	// AUTOMATIC DATABASE RENDERING
	/**
	 * from_result function.
	 * 
	 * @access public
	 * @param array $data. (default: array())
	 * @return void
	 */
	public function from_result($data = array())
	{
		if (! isset($this->opts['series']))
		{
			$this->opts['series'] = array();
		}
				
		foreach ($data['data'] as $row)
		{
			if (isset($data['x_labels'])) $this->push_categorie($row->$data['x_labels'],'x');
			if (isset($data['y_labels'])) $this->push_categorie($row->$data['y_labels'],'y');
			
			foreach ($data['series'] as $name => $value)
			{	
				// there is no options, juste assign name / value pair
				if (is_string($value))
				{
					$text = (is_string($name)) ? $name : $value;
					$dat  = $row->$value;
				}
				
				// options are passed
				else if (is_array($value))
				{
					if (isset($value['name']))
					{
						$text = $value['name'];
						unset($value['name']);
					}
					else
					{
						$text = $value['row'];
					}
					$dat = $row->{$value['row']};
					unset($value['row']);
					
					$this->set_serie_options($value, $text);
				}
				
				$this->push_serie_data($dat, $text);
			}
		}
		return $this;
	}
	
	
	
	/**
	 * add function.
	 * If options is a string, then the index of the current
	 * options to store it
	 * 
	 * @access public
	 * @param array $options. (default: array())
	 * @return void
	 */
	public function add($options = array(), $clear = true)
	{
		if (count($this->global_opts) <= self::$chart_id AND ! empty($this->opts['series']))
		{
			if (is_string($options) AND trim($options) !== '')
			{
				$this->global_opts[$options] = $this->opts;
			}
			else
			{
				$this->global_opts[self::$chart_id] = (count($options)> 0) ? $options : $this->opts;
			}
		}
		
		self::$chart_id++;	
		
		if ($clear === true) $this->clear();
					
		return $this;
	}
	

	/**
	 * get function.
	 * return the global options array as json string
	 * 
	 * @access public
	 * @return void
	 */
	public function get($clear = true)
	{
		$this->add();
		
		foreach ($this->global_opts as $key => $opts)
		{
			$this->global_opts[$key] = $this->encode($opts);
		}	
		
		return $this->process_get($this->global_opts, $clear, 'json');
	}
	
	/**
	 * get_array function.
	 * return the raw options array
	 * 
	 * @access public
	 * @return void
	 */
	public function get_array($clear = true)
	{
		$this->add();
		
		return $this->process_get($this->global_opts, $clear, 'array');
	}
	
	/**
	 * encode function.
	 * Search and replace delimited functions by encode_function()
	 * We need to remove quotes from json string in order to
	 * make javascript function works.
	 * 
	 * @access public
	 * @param mixed $options
	 * @return void
	 */
	public function encode($options)
	{
		//$options = str_replace('\\', '', json_encode($options));
		$options = json_encode($options);
		// 对中文进行过滤，不进行  json_encode
		//preg_replace("/\\\u([0-9a-f]+)/ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $options);
	
		$patterns = array ('/:\"function(.*);}\"/U');
		$replace = array (':function\1;}');
		
		$options = preg_replace($patterns, $replace, $options);
		$options = str_replace("/\r\n/", " ", $options);
		return str_replace($this->replace_keys, $this->orig_keys, $options);
	}
	
	/**
	 * process_get function.
	 * This functon send the output for get() and get_array().
	 * it will return an associative array if some global variables are defined.
	 * 
	 * @access private
	 * @param mixed $options
	 * @param mixed $clear
	 * @return Json / Array
	 */
	private function process_get($options, $clear, $type)
	{
		if (count($this->shared_opts) > 0)
		{
			$global = ($type == 'json') ? $this->encode($this->shared_opts) : $this->shared_opts;
			
			$options = array('global' => $global, 'local' => $options);
		}
		
		if ($clear === true) $this->clear();
		
		return $options;
	}
	
	/**
	 * get_embed function.
	 * Return javascript embedable code and friend div
	 * 
	 * @access public
	 * @return void
	 */
	public function render()
	{
		$this->add();
		
		$i = 1; $d = 1; $divs = '';

		$embed  = '<script type="text/javascript">'."\n";
		$embed .= '$(function(){'."\n";
		
		foreach ($this->global_opts as $opts)
		{
			if (count($this->shared_opts) > 0 AND $i === 1)
      {
      	
        $embed .= 'Highcharts.setOptions('.$this->encode($this->shared_opts).');'."\n";
      }

      if ($opts['chart']['renderTo'] == 'hc_chart')
      {
        $opts['chart']['renderTo'] .= '_'.$d;
        $d++;
      }
			
			$embed .= 'var '.$this->js_chart_name.'_'.	$i.' = new Highcharts.Chart('.$this->encode($opts).');'."\n";
			$divs  .= '<div id="'.$opts['chart']['renderTo'].'"></div>'."\n";
			$i++;
		}
        
		$embed .= '});'."\n";
		$embed .= '</script>'."\n";
		$embed .= $divs;
        
		$this->clear();
		return $embed;
	}
	
	
	/**
	 * clear function.
	 * clear instance properties. Very general at the moment, should only reset
	 * desired vars when lib will be finish
	 * 
	 * @access public
	 * @return void
	 */
	public function clear($shared = false)
	{
		$this->opts = array();
		$this->opts['series'] = array();
		$this->opts['chart']['renderTo'] = 'hc_chart';
		$this->serie_index = 0;
		
		if ($shared === true) $this->shared_opts = array();
		
		return $this;
	}
	
	public function export_excel()
	{
		$data = $this->get_array();
		return $data[0]['series'];
	
	}
	


}
