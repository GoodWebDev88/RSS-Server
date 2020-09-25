<?php

/**
 * Controller to handle every configuration options.
 */
class RSSServer_configure_Controller extends Base_ActionController {
	/**
	 * This action is called before every other action in that class. It is
	 * the common boiler plate for every action. It is triggered by the
	 * underlying BASE template.
	 */
	public function prependAction() {
		if (!RSSServer_Auth::hasAccess()) {
			Base_Error::error(403);
		}
	}

	/**
	 * This action handles the display configuration page.
	 *
	 * It displays the display configuration page.
	 * If this action is reached through a POST request, it stores all new
	 * configuration values then sends a notification to the user.
	 *
	 * The options available on the page are:
	 *   - language (default: en)
	 *   - theme (default: Origin)
	 *   - content width (default: thin)
	 *   - display of read action in header
	 *   - display of favorite action in header
	 *   - display of date in header
	 *   - display of open action in header
	 *   - display of read action in footer
	 *   - display of favorite action in footer
	 *   - display of sharing action in footer
	 *   - display of tags in footer
	 *   - display of date in footer
	 *   - display of open action in footer
	 *   - html5 notification timeout (default: 0)
	 * Default values are false unless specified.
	 */
	public function displayAction() {
		if (Base_Request::isPost()) {
			RSSServer_Context::$user_conf->language = Base_Request::param('language', 'en');
			RSSServer_Context::$user_conf->theme = Base_Request::param('theme', RSSServer_Themes::$defaultTheme);
			RSSServer_Context::$user_conf->content_width = Base_Request::param('content_width', 'thin');
			RSSServer_Context::$user_conf->topline_read = Base_Request::param('topline_read', false);
			RSSServer_Context::$user_conf->topline_favorite = Base_Request::param('topline_favorite', false);
			RSSServer_Context::$user_conf->topline_date = Base_Request::param('topline_date', false);
			RSSServer_Context::$user_conf->topline_link = Base_Request::param('topline_link', false);
			RSSServer_Context::$user_conf->topline_display_authors = Base_Request::param('topline_display_authors', false);
			RSSServer_Context::$user_conf->bottomline_read = Base_Request::param('bottomline_read', false);
			RSSServer_Context::$user_conf->bottomline_favorite = Base_Request::param('bottomline_favorite', false);
			RSSServer_Context::$user_conf->bottomline_sharing = Base_Request::param('bottomline_sharing', false);
			RSSServer_Context::$user_conf->bottomline_tags = Base_Request::param('bottomline_tags', false);
			RSSServer_Context::$user_conf->bottomline_date = Base_Request::param('bottomline_date', false);
			RSSServer_Context::$user_conf->bottomline_link = Base_Request::param('bottomline_link', false);
			RSSServer_Context::$user_conf->html5_notif_timeout = Base_Request::param('html5_notif_timeout', 0);
			RSSServer_Context::$user_conf->show_nav_buttons = Base_Request::param('show_nav_buttons', false);
			RSSServer_Context::$user_conf->save();

			Base_Session::_param('language', RSSServer_Context::$user_conf->language);
			Base_Translate::reset(RSSServer_Context::$user_conf->language);
			invalidateHttpCache();

			Base_Request::good(_t('feedback.conf.updated'),
			                   array('c' => 'configure', 'a' => 'display'));
		}

		$this->view->themes = RSSServer_Themes::get();

		Base_View::prependTitle(_t('conf.display.title') . ' · ');
	}

	/**
	 * This action handles the reading configuration page.
	 *
	 * It displays the reading configuration page.
	 * If this action is reached through a POST request, it stores all new
	 * configuration values then sends a notification to the user.
	 *
	 * The options available on the page are:
	 *   - number of posts per page (default: 10)
	 *   - view mode (default: normal)
	 *   - default article view (default: all)
	 *   - load automatically articles
	 *   - display expanded articles
	 *   - display expanded categories
	 *   - hide categories and feeds without unread articles
	 *   - jump on next category or feed when marked as read
	 *   - image lazy loading
	 *   - stick open articles to the top
	 *   - display a confirmation when reading all articles
	 *   - auto remove article after reading
	 *   - article order (default: DESC)
	 *   - mark articles as read when:
	 *       - displayed
	 *       - opened on site
	 *       - scrolled
	 *       - received
	 * Default values are false unless specified.
	 */
	public function readingAction() {
		if (Base_Request::isPost()) {
			RSSServer_Context::$user_conf->posts_per_page = Base_Request::param('posts_per_page', 10);
			RSSServer_Context::$user_conf->view_mode = Base_Request::param('view_mode', 'normal');
			RSSServer_Context::$user_conf->default_view = Base_Request::param('default_view', 'adaptive');
			RSSServer_Context::$user_conf->show_fav_unread = Base_Request::param('show_fav_unread', false);
			RSSServer_Context::$user_conf->auto_load_more = Base_Request::param('auto_load_more', false);
			RSSServer_Context::$user_conf->display_posts = Base_Request::param('display_posts', false);
			RSSServer_Context::$user_conf->display_categories = Base_Request::param('display_categories', 'active');
			RSSServer_Context::$user_conf->hide_read_feeds = Base_Request::param('hide_read_feeds', false);
			RSSServer_Context::$user_conf->onread_jump_next = Base_Request::param('onread_jump_next', false);
			RSSServer_Context::$user_conf->lazyload = Base_Request::param('lazyload', false);
			RSSServer_Context::$user_conf->sides_close_article = Base_Request::param('sides_close_article', false);
			RSSServer_Context::$user_conf->sticky_post = Base_Request::param('sticky_post', false);
			RSSServer_Context::$user_conf->reading_confirm = Base_Request::param('reading_confirm', false);
			RSSServer_Context::$user_conf->auto_remove_article = Base_Request::param('auto_remove_article', false);
			RSSServer_Context::$user_conf->mark_updated_article_unread = Base_Request::param('mark_updated_article_unread', false);
			RSSServer_Context::$user_conf->sort_order = Base_Request::param('sort_order', 'DESC');
			RSSServer_Context::$user_conf->mark_when = array(
				'article' => Base_Request::param('mark_open_article', false),
				'site' => Base_Request::param('mark_open_site', false),
				'scroll' => Base_Request::param('mark_scroll', false),
				'reception' => Base_Request::param('mark_upon_reception', false),
			);
			RSSServer_Context::$user_conf->save();
			invalidateHttpCache();

			Base_Request::good(_t('feedback.conf.updated'),
			                   array('c' => 'configure', 'a' => 'reading'));
		}

		Base_View::prependTitle(_t('conf.reading.title') . ' · ');
	}

	/**
	 * This action handles the integration configuration page.
	 *
	 * It displays the integration configuration page.
	 * If this action is reached through a POST request, it stores all
	 * configuration values then sends a notification to the user.
	 *
	 * Before v1.16, we used sharing instead of integration. This has
	 * some unwanted behavior when the end-user was using an ad-blocker.
	 */
	public function integrationAction() {
		if (Base_Request::isPost()) {
			$params = Base_Request::fetchPOST();
			RSSServer_Context::$user_conf->sharing = $params['share'];
			RSSServer_Context::$user_conf->save();
			invalidateHttpCache();

			Base_Request::good(_t('feedback.conf.updated'),
			                   array('c' => 'configure', 'a' => 'integration'));
		}

		Base_View::prependTitle(_t('conf.sharing.title') . ' · ');
	}

	/**
	 * This action handles the shortcut configuration page.
	 *
	 * It displays the shortcut configuration page.
	 * If this action is reached through a POST request, it stores all new
	 * configuration values then sends a notification to the user.
	 *
	 * The authorized values for shortcuts are letters (a to z), numbers (0
	 * to 9), function keys (f1 to f12), backspace, delete, down, end, enter,
	 * escape, home, insert, left, page down, page up, return, right, space,
	 * tab and up.
	 */
	public function shortcutAction() {
		$this->view->list_keys = SHORTCUT_KEYS;

		if (Base_Request::isPost()) {
			RSSServer_Context::$user_conf->shortcuts = validateShortcutList(Base_Request::param('shortcuts'));
			RSSServer_Context::$user_conf->save();
			invalidateHttpCache();

			Base_Request::good(_t('feedback.conf.shortcuts_updated'), array('c' => 'configure', 'a' => 'shortcut'));
		} else {
			RSSServer_Context::$user_conf->shortcuts = validateShortcutList(RSSServer_Context::$user_conf->shortcuts);
		}

		Base_View::prependTitle(_t('conf.shortcut.title') . ' · ');
	}

	/**
	 * This action handles the archive configuration page.
	 *
	 * It displays the archive configuration page.
	 * If this action is reached through a POST request, it stores all new
	 * configuration values then sends a notification to the user.
	 *
	 * The options available on that page are:
	 *   - duration to retain old article (default: 3)
	 *   - number of article to retain per feed (default: 0)
	 *   - refresh frequency (default: 0)
	 */
	public function archivingAction() {
		if (Base_Request::isPost()) {
			if (!Base_Request::paramBoolean('enable_keep_max')) {
				$keepMax = false;
			} elseif (!$keepMax = Base_Request::param('keep_max')) {
				$keepMax = RSSServer_Feed::ARCHIVING_RETENTION_COUNT_LIMIT;
			}
			if ($enableRetentionPeriod = Base_Request::paramBoolean('enable_keep_period')) {
				$keepPeriod = RSSServer_Feed::ARCHIVING_RETENTION_PERIOD;
				if (is_numeric(Base_Request::param('keep_period_count')) && preg_match('/^PT?1[YMWDH]$/', Base_Request::param('keep_period_unit'))) {
					$keepPeriod = str_replace('1', Base_Request::param('keep_period_count'), Base_Request::param('keep_period_unit'));
				}
			} else {
				$keepPeriod = false;
			}

			RSSServer_Context::$user_conf->ttl_default = Base_Request::param('ttl_default', RSSServer_Feed::TTL_DEFAULT);
			RSSServer_Context::$user_conf->archiving = [
				'keep_period' => $keepPeriod,
				'keep_max' => $keepMax,
				'keep_min' => Base_Request::param('keep_min_default', 0),
				'keep_favourites' => Base_Request::paramBoolean('keep_favourites'),
				'keep_labels' => Base_Request::paramBoolean('keep_labels'),
				'keep_unreads' => Base_Request::paramBoolean('keep_unreads'),
			];
			RSSServer_Context::$user_conf->keep_history_default = null;	//Legacy < RSSServer 1.15
			RSSServer_Context::$user_conf->old_entries = null;	//Legacy < RSSServer 1.15
			RSSServer_Context::$user_conf->save();
			invalidateHttpCache();

			Base_Request::good(_t('feedback.conf.updated'),
			                   array('c' => 'configure', 'a' => 'archiving'));
		}

		$volatile = [
				'enable_keep_period' => false,
				'keep_period_count' => '3',
				'keep_period_unit' => 'P1M',
			];
		$keepPeriod = RSSServer_Context::$user_conf->archiving['keep_period'];
		if (preg_match('/^PT?(?P<count>\d+)[YMWDH]$/', $keepPeriod, $matches)) {
			$volatile = [
				'enable_keep_period' => true,
				'keep_period_count' => $matches['count'],
				'keep_period_unit' => str_replace($matches['count'], 1, $keepPeriod),
			];
		}
		RSSServer_Context::$user_conf->volatile = $volatile;

		$entryDAO = RSSServer_Factory::createEntryDao();
		$this->view->nb_total = $entryDAO->count();

		$databaseDAO = RSSServer_Factory::createDatabaseDAO();
		$this->view->size_user = $databaseDAO->size();

		if (RSSServer_Auth::hasAccess('admin')) {
			$this->view->size_total = $databaseDAO->size(true);
		}

		Base_View::prependTitle(_t('conf.archiving.title') . ' · ');
	}

	/**
	 * This action handles the user queries configuration page.
	 *
	 * If this action is reached through a POST request, it stores all new
	 * configuration values then sends a notification to the user then
	 * redirect to the same page.
	 * If this action is not reached through a POST request, it displays the
	 * configuration page and verifies that every user query is runable by
	 * checking if categories and feeds are still in use.
	 */
	public function queriesAction() {
		$category_dao = RSSServer_Factory::createCategoryDao();
		$feed_dao = RSSServer_Factory::createFeedDao();
		$tag_dao = RSSServer_Factory::createTagDao();
		if (Base_Request::isPost()) {
			$params = Base_Request::param('queries', array());

			foreach ($params as $key => $query) {
				if (!$query['name']) {
					$query['name'] = _t('conf.query.number', $key + 1);
				}
				$queries[] = new RSSServer_UserQuery($query, $feed_dao, $category_dao);
			}
			RSSServer_Context::$user_conf->queries = $queries;
			RSSServer_Context::$user_conf->save();

			Base_Request::good(_t('feedback.conf.updated'),
			                   array('c' => 'configure', 'a' => 'queries'));
		} else {
			$this->view->queries = array();
			foreach (RSSServer_Context::$user_conf->queries as $key => $query) {
				$this->view->queries[$key] = new RSSServer_UserQuery($query, $feed_dao, $category_dao);
			}
		}

		Base_View::prependTitle(_t('conf.query.title') . ' · ');
	}

	/**
	 * This action handles the creation of a user query.
	 *
	 * It gets the GET parameters and stores them in the configuration query
	 * storage. Before it is saved, the unwanted parameters are unset to keep
	 * lean data.
	 */
	public function addQueryAction() {
		$category_dao = RSSServer_Factory::createCategoryDao();
		$feed_dao = RSSServer_Factory::createFeedDao();
		$tag_dao = RSSServer_Factory::createTagDao();
		$queries = array();
		foreach (RSSServer_Context::$user_conf->queries as $key => $query) {
			$queries[$key] = new RSSServer_UserQuery($query, $feed_dao, $category_dao, $tag_dao);
		}
		$params = Base_Request::fetchGET();
		$params['url'] = Base_Url::display(array('params' => $params));
		$params['name'] = _t('conf.query.number', count($queries) + 1);
		$queries[] = new RSSServer_UserQuery($params, $feed_dao, $category_dao, $tag_dao);

		RSSServer_Context::$user_conf->queries = $queries;
		RSSServer_Context::$user_conf->save();

		Base_Request::good(_t('feedback.conf.query_created', $query['name']),
		                   array('c' => 'configure', 'a' => 'queries'));
	}

	/**
	 * This action handles the system configuration page.
	 *
	 * It displays the system configuration page.
	 * If this action is reach through a POST request, it stores all new
	 * configuration values then sends a notification to the user.
	 *
	 * The options available on the page are:
	 *   - instance name (default: RSSServer)
	 *   - auto update URL (default: false)
	 *   - force emails validation (default: false)
	 *   - user limit (default: 1)
	 *   - user category limit (default: 16384)
	 *   - user feed limit (default: 16384)
	 *   - user login duration for form auth (default: 2592000)
	 *
	 * The `force-email-validation` is ignored with PHP < 5.5
	 */
	public function systemAction() {
		if (!RSSServer_Auth::hasAccess('admin')) {
			Base_Error::error(403);
		}

		$can_enable_email_validation = version_compare(PHP_VERSION, '5.5') >= 0;
		$this->view->can_enable_email_validation = $can_enable_email_validation;

		if (Base_Request::isPost()) {
			$limits = RSSServer_Context::$system_conf->limits;
			$limits['max_registrations'] = Base_Request::param('max-registrations', 1);
			$limits['max_feeds'] = Base_Request::param('max-feeds', 16384);
			$limits['max_categories'] = Base_Request::param('max-categories', 16384);
			$limits['cookie_duration'] = Base_Request::param('cookie-duration', 2592000);
			RSSServer_Context::$system_conf->limits = $limits;
			RSSServer_Context::$system_conf->title = Base_Request::param('instance-name', 'RSSServer');
			RSSServer_Context::$system_conf->auto_update_url = Base_Request::param('auto-update-url', false);
			if ($can_enable_email_validation) {
				RSSServer_Context::$system_conf->force_email_validation = Base_Request::param('force-email-validation', false);
			}
			RSSServer_Context::$system_conf->save();

			invalidateHttpCache();

			Base_Session::_param('notification', array(
				'type' => 'good',
				'content' => _t('feedback.conf.updated')
			));
		}
	}
}
