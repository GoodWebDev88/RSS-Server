<?php

class RSSServer_BadUrl_Exception extends RSSServer_Feed_Exception {

	public function __construct($url) {
		parent::__construct('`' . $url . '` is not a valid URL');
	}

}
