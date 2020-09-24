<?php
/**
 * The ActionController class represents the application controller
 */
class Base_ActionController {
	protected $view;
	private $csp_policies = array(
		'default-src' => "'self'",
	);

	/**
	 * Constructeur
	 */
	public function __construct () {
		$this->view = new Base_View();
		$view_path = Base_Request::controllerName() . '/' . Base_Request::actionName() . '.phtml';
		$this->view->_path($view_path);
		$this->view->attributeParams ();
	}

	/**
	 * Getteur
	 */
	public function view () {
		return $this->view;
	}

	/**
	 * Set CSP policies.
	 *
	 * A default-src directive should always be given.
	 *
	 * References:
	 * - https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
	 * - https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/default-src
	 *
	 * @param array $policies An array where keys are directives and values are sources.
	 */
	protected function _csp($policies) {
		if (!isset($policies['default-src'])) {
			$action = Base_Request::controllerName() . '#' . Base_Request::actionName();
			Base_Log::warning(
				"Default CSP policy is not declared for action {$action}.",
				ADMIN_LOG
			);
		}
		$this->csp_policies = $policies;
	}

	/**
	 * Send HTTP Content-Security-Policy header based on declared policies.
	 */
	public function declareCspHeader() {
		$policies = [];
		foreach ($this->csp_policies as $directive => $sources) {
			$policies[] = $directive . ' ' . $sources;
		}
		header('Content-Security-Policy: ' . implode('; ', $policies));
	}

	/**
	 * Methods to be redefined (or not) by inheritance
	 * firstAction is the first method executed by the Dispatcher
	 * lastAction is the last
	 */
	public function init () { }
	public function firstAction () { }
	public function lastAction () { }
}
