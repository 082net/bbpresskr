<?php
/**
 * View
 *
 * Handles all of the views.
 *
 * @package bbPressKR
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 * 
 * Powered by Social_View from Social plugin (http://mailchimp.com/social-plugin-for-wordpress/)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

final class View {

	/**
	 * Initializes a view.
	 *
	 * @static
	 * @param  string  $file
	 * @param  array   $data
	 * @return View
	 */
	public static function factory($file = null, array $data = array()) {
		return new View($file, $data);
	}

	/**
	 * @var  string  view file
	 */
	protected $_file;

	/**
	 * @var  array  view data
	 */
	protected $_data = array();

	/**
	 * Sets the view file and data. Should be called by Social_View::factory().
	 *
	 * @param  string  $file
	 * @param  array   $data
	 */
	public function __construct($file = null, array $data = array()) {
		if (empty($this->_data)) {
			$this->_data = $data;
		}
		
		if ($file !== null) {
			$this->set_file($file);
		}
	}

	/**
	 * Calls render() when the object is echoed.
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->render();
		}
		catch (Exception $e) {
			// Log the exception
			error_log(print_r($e, true));
			return '';
		}
	}

	/**
	 * Sets view data.
	 *
	 * @param  mixed  $key
	 * @param  string  $value
	 * @return Social_View
	 */
	public function set($key, $value = null) {
		if (is_array($key)) {
			foreach ($key as $name => $value) {
				$this->_data[$name] = $value;
			}
		}
		else {
			$this->_data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Renders the view.
	 *
	 * @throws Exception
	 * @param  string  $file
	 * @return string
	 */
	public function render($file = null) {
		if ($file !== null) {
			$this->set_file($file);
		}

		if (empty($this->_file)) {
			throw new Exception(__('You must set a file to be used before rendering.', 'bbpresskr'));
		}

		$this->_data = apply_filters('bbpresskr_view_data', $this->_data, $this->_file);
		extract($this->_data, EXTR_SKIP);
		ob_start();
		try {
			include $this->path($this->_file);
		}
		catch (Exception $e) {
			ob_end_clean();
			throw $e;
		}

		return ob_get_clean();
	}

	/**
	 * Sets the file to use for the view.
	 *
	 * @throws Exception
	 * @param  string  $file
	 * @return void
	 */
	private function set_file($file) {
		$file = apply_filters('bbpresskr_view_set_file', $file, $this->_data);
		
		if (file_exists($file)) {
			$this->_file = $file;
		}
		else {
			if (file_exists($this->path($file))) {
				$this->_file = $file;
			}
		}

		if ($this->_file === null) {
			throw new Exception(sprintf(__('View %s does not exist.', 'bbpresskr'), $file));
		}
	}

	/**
	 * Builds the absolute URL path to the view.
	 *
	 * @param  string  $file
	 * @return string
	 */
	private function path($file) {
		if ( !preg_match( '/\.[a-z0-9]{1,4}$/i', $file) )
			$file .= '.php';
		return bbpresskr()->path.'/views/'.$file;
	}

} // End View
