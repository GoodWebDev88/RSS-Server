<?php
/**
 * The View class represents the view of the application
 */
class Base_View {
	const VIEWS_PATH_NAME = '/views';
	const LAYOUT_PATH_NAME = '/layout/';
	const LAYOUT_DEFAULT = 'layout';

	private $view_filename = '';
	private $layout_filename = '';

	private static $base_pathnames = array(APP_PATH);
	private static $title = '';
	private static $styles = array ();
	private static $scripts = array ();

	private static $params = array ();

	/**
	 * Constructor
	 * Determine if we use a layout or not
	 */
	public function __construct() {
		$this->_layout(self::LAYOUT_DEFAULT);
		$conf = Base_Configuration::get('system');
		self::$title = $conf->title;
	}

	/**
	 * [deprecated] Change the view file based on controller and action.
	 */
	public function change_view($controller_name, $action_name) {
		Base_Log::warning('Base_View::change_view is deprecated, it will be removed in a future version. Please use Base_View::_path instead.');
		$this->_path($controller_name. '/' . $action_name . '.phtml');
	}

	/**
	 * Change the view file based on a pathname relative to VIEWS_PATH_NAME.
	 *
	 * @param string $path the new path
	 */
	public function _path($path) {
		$this->view_filename = self::VIEWS_PATH_NAME . '/' . $path;
	}

	/**
	 * Add a base pathname to search views.
	 *
	 * New pathnames will be added at the beginning of the list.
	 *
	 * @param $base_pathname the new base pathname.
	 */
	public static function addBasePathname($base_pathname) {
		array_unshift(self::$base_pathnames, $base_pathname);
	}

	/**
	 * Builds the view
	 */
	public function build () {
		if ($this->layout_filename !== '') {
			$this->buildLayout();
		} else {
			$this->render();
		}
	}

	/**
	 * Include a view file.
	 *
	 * The file is searched inside list of $base_pathnames.
	 *
	 * @param $filename the name of the file to include.
	 * @return true if the file has been included, false else.
	 */
	private function includeFile($filename) {
		// We search the filename in the list of base pathnames. Only the first view
		// found is considered.
		foreach (self::$base_pathnames as $base) {
			$absolute_filename = $base . $filename;
			if (file_exists($absolute_filename)) {
				include $absolute_filename;
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the layout
	 */
	public function buildLayout() {
		header('Content-Type: text/html; charset=UTF-8');
		if (!$this->includeFile($this->layout_filename)) {
			Base_Log::notice('File not found: `' . $this->layout_filename . '`');
		}
	}

	/**
	 * Displays the View itself
	 */
	public function render() {
		if (!$this->includeFile($this->view_filename)) {
			Base_Log::notice('File not found: `' . $this->view_filename . '`');
		}
	}

	/**
	 * Adds a layout element
	 * @param $ part the partial element to add
	 */
	public function partial($part) {
		$fic_partial = self::LAYOUT_PATH_NAME . '/' . $part . '.phtml';
		if (!$this->includeFile($fic_partial)) {
			Base_Log::warning('File not found: `' . $fic_partial . '`');
		}
	}

	/**
	 * Displays a graphic element located in APP./views/helpers/
	 * @param $ helper the element to display
	 */
	public function renderHelper($helper) {
		$fic_helper = '/views/helpers/' . $helper . '.phtml';
		if (!$this->includeFile($fic_helper)) {
			Base_Log::warning('File not found: `' . $fic_helper . '`');
		}
	}

	/**
	 * Return renderHelper () in a string
	 * @param $ helper the element to process
	 */
	public function helperToString($helper) {
		ob_start();
		$this->renderHelper($helper);
		return ob_get_clean();
	}

	/**
	 * Choose the current view layout.
	 * @param $layout the layout name to use, false to use no layouts.
	 */
	public function _layout($layout) {
		if ($layout) {
			$this->layout_filename = self::LAYOUT_PATH_NAME . $layout . '.phtml';
		} else {
			$this->layout_filename = '';
		}
	}

	/**
	 * [deprecated] Choose if we want to use the layout or not.
	 * Please use the `_layout` function instead.
	 * @param $use true if we want to use the layout, false else
	 */
	public function _useLayout($use) {
		Base_Log::warning('Base_View::_useLayout is deprecated, it will be removed in a future version. Please use Base_View::_layout instead.');
		if ($use) {
			$this->_layout(self::LAYOUT_DEFAULT);
		} else {
			$this->_layout(false);
		}
	}

	/**
	 * Title management
	 */
	public static function title() {
		return self::$title;
	}

	public static function headTitle() {
		return '<title>' . self::$title . '</title>' . "\n";
	}

	public static function _title($title) {
		self::$title = $title;
	}

	public static function prependTitle($title) {
		self::$title = $title . self::$title;
	}

	public static function appendTitle($title) {
		self::$title = self::$title . $title;
	}

	/**
	 * Management of style sheets
	 */
	public static function headStyle() {
		$styles = '';

		foreach(self::$styles as $style) {
			$cond = $style['cond'];
			if ($cond) {
				$styles .= '<!--[if ' . $cond . ']>';
			}

			$styles .= '<link rel="stylesheet" ' .
				($style['media'] === 'all' ? '' : 'media="' . $style['media'] . '" ') .
				'href="' . $style['url'] . '" />';

			if ($cond) {
				$styles .= '<![endif]-->';
			}

			$styles .= "\n";
		}

		return $styles;
	}
	public static function prependStyle($url, $media = 'all', $cond = false) {
		array_unshift (self::$styles, array(
			'url' => $url,
			'media' => $media,
			'cond' => $cond
		));
	}
	public static function appendStyle($url, $media = 'all', $cond = false) {
		self::$styles[] = array(
			'url' => $url,
			'media' => $media,
			'cond' => $cond
		);
	}

	/**
	 * Managing JS scripts
	 */
	public static function headScript() {
		$scripts = '';

		foreach (self::$scripts as $script) {
			$cond = $script['cond'];
			if ($cond) {
				$scripts .= '<!--[if ' . $cond . ']>';
			}

			$scripts .= '<script src="' . $script['url'] . '"';
			if ($script['defer']) {
				$scripts .= ' defer="defer"';
			}
			if ($script['async']) {
				$scripts .= ' async="async"';
			}
			$scripts .= '></script>';

			if ($cond) {
				$scripts .= '<![endif]-->';
			}

			$scripts .= "\n";
		}

		return $scripts;
	}
	public static function prependScript($url, $cond = false, $defer = true, $async = true) {
		array_unshift(self::$scripts, array(
			'url' => $url,
			'cond' => $cond,
			'defer' => $defer,
			'async' => $async,
		));
	}
	public static function appendScript($url, $cond = false, $defer = true, $async = true) {
		self::$scripts[] = array(
			'url' => $url,
			'cond' => $cond,
			'defer' => $defer,
			'async' => $async,
		);
	}

	/**
	 * Managing parameters added to the view
	 */
	public static function _param($key, $value) {
		self::$params[$key] = $value;
	}
	public function attributeParams() {
		foreach (Base_View::$params as $key => $value) {
			$this->$key = $value;
		}
	}
}
