<?php
/**
 * The Helper class represents a helper for recurring tasks
 */
class Base_Helper {

	/**
	 * Wrapper for htmlspecialchars.
	 * Force UTf-8 value and can be used on array too.
	 */
	public static function htmlspecialchars_utf8($var) {
		if (is_array($var)) {
			return array_map(array('Base_Helper', 'htmlspecialchars_utf8'), $var);
		}
		return htmlspecialchars($var, ENT_COMPAT, 'UTF-8');
	}
}
