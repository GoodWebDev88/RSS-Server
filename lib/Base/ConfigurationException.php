<?php

class Base_ConfigurationException extends Base_Exception {
	public function __construct($error, $code = self::ERROR) {
		$message = 'Configuration error: ' . $error;
		parent::__construct($message, $code);
	}
}
