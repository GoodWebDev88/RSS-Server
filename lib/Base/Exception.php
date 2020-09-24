<?php
class Base_Exception extends Exception {
	const ERROR = 0;
	const WARNING = 10;
	const NOTICE = 20;

	public function __construct($message, $code = self::ERROR) {
		if ($code != Base_Exception::ERROR
		 && $code != Base_Exception::WARNING
		 && $code != Base_Exception::NOTICE) {
			$code = Base_Exception::ERROR;
		}

		parent::__construct($message, $code);
	}
}
