<?php

/**
 * The Url class is used to manage URLs through BASE
 */
class Base_Url {
	/**
	 * Displays a formatted url
	 * @param $ url the url to format defined as an array:
	 * $ url ['c'] = controller
	 * $ url ['a'] = action
	 * $ url ['params'] = array of additional parameters
	 * or as a string
	 * @param $encoding to indicate how to encode the & (& or &amp; for html)
	 * @return the formatted url
	 */
	public static function display($url = array (), $encoding = 'html', $absolute = false) {
		$isArray = is_array($url);

		if ($isArray) {
			$url = self::checkUrl($url);
		}

		$url_string = '';

		if ($absolute) {
			$url_string = Base_Request::getBaseUrl();
			if (strlen($url_string) < strlen('http://a.bc')) {
				$url_string = Base_Request::guessBaseUrl();
				if (PUBLIC_RELATIVE === '..') {
					//TODO: Implement proper resolver of relative parts such as /test/./../
					$url_string = dirname($url_string);
				}
			}
			if ($isArray) {
				$url_string .= PUBLIC_TO_INDEX_PATH;
			}
			if ($absolute === 'root') {
				$url_string = parse_url($url_string, PHP_URL_PATH);
			}
		} else {
			$url_string = $isArray ? '.' : PUBLIC_RELATIVE;
		}

		if ($isArray) {
			$url_string .= '/' . self::printUri($url, $encoding);
		} elseif ($encoding === 'html') {
			$url_string = Base_Helper::htmlspecialchars_utf8($url_string . $url);
		} else {
			$url_string .= $url;
		}

		return $url_string;
	}

	/**
	 * Build the URI of a URL
	 * @param the url in the form of an array
	 * @param $ encoding to indicate how to encode them &(& or &amp; for html)
	 */
	private static function printUri($url, $encoding) {
		$uri = '';
		$separator = '?';

		if ($encoding === 'html') {
			$and = '&amp;';
		} else {
			$and = '&';
		}

		if (isset($url['c'])
		 && $url['c'] != Base_Request::defaultControllerName()) {
			$uri .= $separator . 'c=' . $url['c'];
			$separator = $and;
		}

		if (isset($url['a'])
		 && $url['a'] != Base_Request::defaultActionName()) {
			$uri .= $separator . 'a=' . $url['a'];
			$separator = $and;
		}

		if (isset($url['params'])) {
			unset($url['params']['c']);
			unset($url['params']['a']);
			foreach ($url['params'] as $key => $param) {
				$uri .= $separator . urlencode($key) . '=' . urlencode($param);
				$separator = $and;
			}
		}

		return $uri;
	}

	/**
	 * Check that the elements of the array representing a url are ok
	 * @param the url in the form of an array (otherwise will directly return $ url)
	 * @return the verified url	 
	 */
	public static function checkUrl($url) {
		$url_checked = $url;

		if (is_array($url)) {
			if (!isset ($url['c'])) {
				$url_checked['c'] = Base_Request::defaultControllerName();
			}
			if (!isset ($url['a'])) {
				$url_checked['a'] = Base_Request::defaultActionName();
			}
			if (!isset ($url['params'])) {
				$url_checked['params'] = array();
			}
		}

		return $url_checked;
	}
}

function _url($controller, $action) {
	$nb_args = func_num_args();

	if($nb_args < 2 || $nb_args % 2 != 0) {
		return false;
	}

	$args = func_get_args();
	$params = array();
	for($i = 2; $i < $nb_args; $i = $i + 2) {
		$params[$args[$i]] = $args[$i + 1];
	}

	return Base_Url::display(array('c' => $controller, 'a' => $action, 'params' => $params));
}
