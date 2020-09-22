<?php

/**
 * The context object handles the current configuration file and different
 * useful functions associated to the current view state.
 */
class RSSServer_Context {
	public static $user_conf = null;
	public static $system_conf = null;
	public static $categories = array();
	public static $tags = array();

	public static $name = '';
	public static $description = '';

	public static $total_unread = 0;
	public static $total_starred = array(
		'all' => 0,
		'read' => 0,
		'unread' => 0,
	);

	public static $get_unread = 0;
	public static $current_get = array(
		'all' => false,
		'starred' => false,
		'feed' => false,
		'category' => false,
		'tag' => false,
		'tags' => false,
	);
	public static $next_get = 'a';

	public static $state = 0;
	public static $order = 'DESC';
	public static $number = 0;
	public static $search;
	public static $first_id = '';
	public static $next_id = '';
	public static $id_max = '';
	public static $sinceHours = 0;

	public static $isCli = false;

	/**
	 * Initialize the context.
	 *
	 * Set the correct configurations and $categories variables.
	 */
	public static function init() {
		// Init configuration.
		self::$system_conf = Minz_Configuration::get('system');
		self::$user_conf = Minz_Configuration::get('user');

		//Legacy
		$oldEntries = (int)RSSServer_Context::$user_conf->param('old_entries', 0);
		$keepMin = (int)RSSServer_Context::$user_conf->param('keep_history_default', -5);
		if ($oldEntries > 0 || $keepMin > -5) {	//rssServer < 1.15
			$archiving = RSSServer_Context::$user_conf->archiving;
			$archiving['keep_max'] = false;
			if ($oldEntries > 0) {
				$archiving['keep_period'] = 'P' . $oldEntries . 'M';
			}
			if ($keepMin > 0) {
				$archiving['keep_min'] = $keepMin;
			} elseif ($keepMin == -1) {	//Infinite
				$archiving['keep_period'] = false;
				$archiving['keep_min'] = false;
			}
			RSSServer_Context::$user_conf->archiving = $archiving;
		}

		//Legacy < 1.16.1
		if (!in_array(RSSServer_Context::$user_conf->display_categories, [ 'active', 'all', 'none' ], true)) {
			RSSServer_Context::$user_conf->display_categories = RSSServer_Context::$user_conf->display_categories === true ? 'all' : 'active';
		}
	}

	/**
	 * Returns if the current state includes $state parameter.
	 */
	public static function isStateEnabled($state) {
		return self::$state & $state;
	}

	/**
	 * Returns the current state with or without $state parameter.
	 */
	public static function getRevertState($state) {
		if (self::$state & $state) {
			return self::$state & ~$state;
		} else {
			return self::$state | $state;
		}
	}

	/**
	 * Return the current get as a string or an array.
	 *
	 * If $array is true, the first item of the returned value is 'f' or 'c' and
	 * the second is the id.
	 */
	public static function currentGet($array = false) {
		if (self::$current_get['all']) {
			return 'a';
		} elseif (self::$current_get['starred']) {
			return 's';
		} elseif (self::$current_get['feed']) {
			if ($array) {
				return array('f', self::$current_get['feed']);
			} else {
				return 'f_' . self::$current_get['feed'];
			}
		} elseif (self::$current_get['category']) {
			if ($array) {
				return array('c', self::$current_get['category']);
			} else {
				return 'c_' . self::$current_get['category'];
			}
		} elseif (self::$current_get['tag']) {
			if ($array) {
				return array('t', self::$current_get['tag']);
			} else {
				return 't_' . self::$current_get['tag'];
			}
		} elseif (self::$current_get['tags']) {
			return 'T';
		}
	}

	/**
	 * Return true if the current request targets a feed (and not a category or all articles), false otherwise.
	 */
	public static function isFeed() {
		return self::$current_get['feed'] != false;
	}

	/**
	 * Return true if $get parameter correspond to the $current_get attribute.
	 */
	public static function isCurrentGet($get) {
		$type = $get[0];
		$id = substr($get, 2);

		switch($type) {
		case 'a':
			return self::$current_get['all'];
		case 's':
			return self::$current_get['starred'];
		case 'f':
			return self::$current_get['feed'] == $id;
		case 'c':
			return self::$current_get['category'] == $id;
		case 't':
			return self::$current_get['tag'] == $id;
		case 'T':
			return self::$current_get['tags'] || self::$current_get['tag'];
		default:
			return false;
		}
	}

	/**
	 * Set the current $get attribute.
	 *
	 * Valid $get parameter are:
	 *   - a
	 *   - s
	 *   - f_<feed id>
	 *   - c_<category id>
	 *   - t_<tag id>
	 *
	 * $name and $get_unread attributes are also updated as $next_get
	 * Raise an exception if id or $get is invalid.
	 */
	public static function _get($get) {
		$type = $get[0];
		$id = substr($get, 2);
		$nb_unread = 0;

		if (empty(self::$categories)) {
			$catDAO = RSSServer_Factory::createCategoryDao();
			self::$categories = $catDAO->listCategories();
		}

		switch($type) {
		case 'a':
			self::$current_get['all'] = true;
			self::$name = _t('index.feed.title');
			self::$description = self::$system_conf->meta_description;
			self::$get_unread = self::$total_unread;
			break;
		case 's':
			self::$current_get['starred'] = true;
			self::$name = _t('index.feed.title_fav');
			self::$description = self::$system_conf->meta_description;
			self::$get_unread = self::$total_starred['unread'];

			// Update state if favorite is not yet enabled.
			self::$state = self::$state | RSSServer_Entry::STATE_FAVORITE;
			break;
		case 'f':
			// We try to find the corresponding feed. When allowing robots, always retrieve the full feed including description
			$feed = RSSServer_Context::$system_conf->allow_robots ? null : RSSServer_CategoryDAO::findFeed(self::$categories, $id);
			if ($feed === null) {
				$feedDAO = RSSServer_Factory::createFeedDao();
				$feed = $feedDAO->searchById($id);
				if (!$feed) {
					throw new RSSServer_Context_Exception('Invalid feed: ' . $id);
				}
			}
			self::$current_get['feed'] = $id;
			self::$current_get['category'] = $feed->category();
			self::$name = $feed->name();
			self::$description = $feed->description();
			self::$get_unread = $feed->nbNotRead();
			break;
		case 'c':
			// We try to find the corresponding category.
			self::$current_get['category'] = $id;
			if (!isset(self::$categories[$id])) {
				$catDAO = RSSServer_Factory::createCategoryDao();
				$cat = $catDAO->searchById($id);
				if (!$cat) {
					throw new RSSServer_Context_Exception('Invalid category: ' . $id);
				}
			} else {
				$cat = self::$categories[$id];
			}
			self::$name = $cat->name();
			self::$get_unread = $cat->nbNotRead();
			break;
		case 't':
			// We try to find the corresponding tag.
			self::$current_get['tag'] = $id;
			if (!isset(self::$tags[$id])) {
				$tagDAO = RSSServer_Factory::createTagDao();
				$tag = $tagDAO->searchById($id);
				if (!$tag) {
					throw new RSSServer_Context_Exception('Invalid tag: ' . $id);
				}
			} else {
				$tag = self::$tags[$id];
			}
			self::$name = $tag->name();
			self::$get_unread = $tag->nbUnread();
			break;
		case 'T':
			self::$current_get['tags'] = true;
			self::$name = _t('index.menu.tags');
			self::$get_unread = 0;
			break;
		default:
			throw new RSSServer_Context_Exception('Invalid getter: ' . $get);
		}

		self::_nextGet();
	}

	/**
	 * Set the value of $next_get attribute.
	 */
	private static function _nextGet() {
		$get = self::currentGet();
		// By default, $next_get == $get
		self::$next_get = $get;

		if (empty(self::$categories)) {
			$catDAO = RSSServer_Factory::createCategoryDao();
			self::$categories = $catDAO->listCategories();
		}

		if (self::$user_conf->onread_jump_next && strlen($get) > 2) {
			$another_unread_id = '';
			$found_current_get = false;
			switch ($get[0]) {
			case 'f':
				// We search the next unread feed with the following priorities: next in same category, or previous in same category, or next, or previous.
				foreach (self::$categories as $cat) {
					$sameCat = false;
					foreach ($cat->feeds() as $feed) {
						if ($found_current_get) {
							if ($feed->nbNotRead() > 0) {
								$another_unread_id = $feed->id();
								break 2;
							}
						} elseif ($feed->id() == self::$current_get['feed']) {
							$found_current_get = true;
						} elseif ($feed->nbNotRead() > 0) {
							$another_unread_id = $feed->id();
							$sameCat = true;
						}
					}
					if ($found_current_get && $sameCat) {
						break;
					}
				}

				// If there is no more unread feed, show main stream
				self::$next_get = $another_unread_id == '' ? 'a' : 'f_' . $another_unread_id;
				break;
			case 'c':
				// We search the next category with at least one unread article.
				foreach (self::$categories as $cat) {
					if ($cat->id() == self::$current_get['category']) {
						// Here is our current category! Next one could be our
						// champion if it has unread articles.
						$found_current_get = true;
						continue;
					}

					if ($cat->nbNotRead() > 0) {
						$another_unread_id = $cat->id();
						if ($found_current_get) {
							// Unread articles and the current category has
							// already been found? Leave the loop!
							break;
						}
					}
				}

				// If there is no more unread category, show main stream
				self::$next_get = $another_unread_id == '' ? 'a' : 'c_' . $another_unread_id;
				break;
			}
		}
	}

	/**
	 * Determine if the auto remove is available in the current context.
	 * This feature is available if:
	 *   - it is activated in the configuration
	 *   - the "read" state is not enable
	 *   - the "unread" state is enable
	 *
	 * @return boolean
	 */
	public static function isAutoRemoveAvailable() {
		if (!self::$user_conf->auto_remove_article) {
			return false;
		}
		if (self::isStateEnabled(RSSServer_Entry::STATE_READ)) {
			return false;
		}
		if (!self::isStateEnabled(RSSServer_Entry::STATE_NOT_READ)) {
			return false;
		}
		return true;
	}

	/**
	 * Determine if the "sticky post" option is enabled. It can be enable
	 * by the user when it is selected in the configuration page or by the
	 * application when the context allows to auto-remove articles when they
	 * are read.
	 *
	 * @return boolean
	 */
	public static function isStickyPostEnabled() {
		if (self::$user_conf->sticky_post) {
			return true;
		}
		if (self::isAutoRemoveAvailable()) {
			return true;
		}
		return false;
	}

}
