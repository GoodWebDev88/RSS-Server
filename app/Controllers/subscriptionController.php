<?php

/**
 * Controller to handle subscription actions.
 */
class RSSServer_subscription_Controller extends Base_ActionController {
	/**
	 * This action is called before every other action in that class. It is
	 * the common boiler plate for every action. It is triggered by the
	 * underlying BASE template.
	 */
	public function firstAction() {
		if (!RSSServer_Auth::hasAccess()) {
			Base_Error::error(403);
		}

		$catDAO = RSSServer_Factory::createCategoryDao();
		$feedDAO = RSSServer_Factory::createFeedDao();

		$catDAO->checkDefault();
		$feedDAO->updateTTL();
		$this->view->categories = $catDAO->listSortedCategories(false);
		$this->view->default_category = $catDAO->getDefault();
	}

	/**
	 * This action handles the main subscription page
	 *
	 * It displays categories and associated feeds.
	 */
	public function indexAction() {
		Base_View::appendScript(Base_Url::display('/scripts/category.js?' . @filemtime(PUBLIC_PATH . '/scripts/category.js')));
		Base_View::prependTitle(_t('sub.title') . ' · ');

		$this->view->onlyFeedsWithError = Base_Request::paramTernary('error');

		$id = Base_Request::param('id');
		$this->view->displaySlider = false;
		if (false !== $id) {
			$type = Base_Request::param('type');
			$this->view->displaySlider = true;
			switch ($type) {
				case 'category':
					$categoryDAO = RSSServer_Factory::createCategoryDao();
					$this->view->category = $categoryDAO->searchById($id);
					break;
				default:
					$feedDAO = RSSServer_Factory::createFeedDao();
					$this->view->feed = $feedDAO->searchById($id);
					break;
			}
		}
	}

	/**
	 * This action handles the feed configuration page.
	 *
	 * It displays the feed configuration page.
	 * If this action is reached through a POST request, it stores all new
	 * configuraiton values then sends a notification to the user.
	 *
	 * The options available on the page are:
	 *   - name
	 *   - description
	 *   - website URL
	 *   - feed URL
	 *   - category id (default: default category id)
	 *   - CSS path to article on website
	 *   - display in main stream (default: 0)
	 *   - HTTP authentication
	 *   - number of article to retain (default: -2)
	 *   - refresh frequency (default: 0)
	 * Default values are empty strings unless specified.
	 */
	public function feedAction() {
		if (Base_Request::param('ajax')) {
			$this->view->_layout(false);
		}

		$feedDAO = RSSServer_Factory::createFeedDao();
		$this->view->feeds = $feedDAO->listFeeds();

		$id = Base_Request::param('id');
		if ($id === false || !isset($this->view->feeds[$id])) {
			Base_Error::error(404);
			return;
		}

		$feed = $this->view->feeds[$id];
		$this->view->feed = $feed;

		Base_View::prependTitle(_t('sub.title.feed_management') . ' · ' . $feed->name() . ' · ');

		if (Base_Request::isPost()) {
			$user = trim(Base_Request::param('http_user_feed' . $id, ''));
			$pass = Base_Request::param('http_pass_feed' . $id, '');

			$httpAuth = '';
			if ($user != '' && $pass != '') {	//TODO: Sanitize
				$httpAuth = $user . ':' . $pass;
			}

			$cat = intval(Base_Request::param('category', 0));

			$mute = Base_Request::param('mute', false);
			$ttl = intval(Base_Request::param('ttl', RSSServer_Feed::TTL_DEFAULT));
			if ($mute && RSSServer_Feed::TTL_DEFAULT === $ttl) {
				$ttl = RSSServer_Context::$user_conf->ttl_default;
			}

			$feed->_attributes('mark_updated_article_unread', Base_Request::paramTernary('mark_updated_article_unread'));
			$feed->_attributes('read_upon_reception', Base_Request::paramTernary('read_upon_reception'));
			$feed->_attributes('clear_cache', Base_Request::paramTernary('clear_cache'));

			if (RSSServer_Auth::hasAccess('admin')) {
				$feed->_attributes('ssl_verify', Base_Request::paramTernary('ssl_verify'));
				$timeout = intval(Base_Request::param('timeout', 0));
				$feed->_attributes('timeout', $timeout > 0 ? $timeout : null);
			} else {
				$feed->_attributes('ssl_verify', null);
				$feed->_attributes('timeout', null);
			}

			if (Base_Request::paramBoolean('use_default_purge_options')) {
				$feed->_attributes('archiving', null);
			} else {
				if (!Base_Request::paramBoolean('enable_keep_max')) {
					$keepMax = false;
				} elseif (!$keepMax = Base_Request::param('keep_max')) {
					$keepMax = RSSServer_Feed::ARCHIVING_RETENTION_COUNT_LIMIT;
				}
				if ($enableRetentionPeriod = Base_Request::paramBoolean('enable_keep_period')) {
					$keepPeriod = RSSServer_Feed::ARCHIVING_RETENTION_PERIOD;
					if (is_numeric(Base_Request::param('keep_period_count')) && preg_match('/^PT?1[YMWDH]$/', Base_Request::param('keep_period_unit'))) {
						$keepPeriod = str_replace(1, Base_Request::param('keep_period_count'), Base_Request::param('keep_period_unit'));
					}
				} else {
					$keepPeriod = false;
				}
				$feed->_attributes('archiving', [
					'keep_period' => $keepPeriod,
					'keep_max' => $keepMax,
					'keep_min' => intval(Base_Request::param('keep_min', 0)),
					'keep_favourites' => Base_Request::paramBoolean('keep_favourites'),
					'keep_labels' => Base_Request::paramBoolean('keep_labels'),
					'keep_unreads' => Base_Request::paramBoolean('keep_unreads'),
				]);
			}

			$feed->_filtersAction('read', preg_split('/[\n\r]+/', Base_Request::param('filteractions_read', '')));

			$values = array(
				'name' => Base_Request::param('name', ''),
				'description' => sanitizeHTML(Base_Request::param('description', '', true)),
				'website' => checkUrl(Base_Request::param('website', '')),
				'url' => checkUrl(Base_Request::param('url', '')),
				'category' => $cat,
				'pathEntries' => Base_Request::param('path_entries', ''),
				'priority' => intval(Base_Request::param('priority', RSSServer_Feed::PRIORITY_MAIN_STREAM)),
				'httpAuth' => $httpAuth,
				'ttl' => $ttl * ($mute ? -1 : 1),
				'attributes' => $feed->attributes(),
			);

			invalidateHttpCache();

			$url_redirect = array('c' => 'subscription', 'params' => array('id' => $id));
			if ($feedDAO->updateFeed($id, $values) !== false) {
				$feed->_category($cat);
				$feed->faviconPrepare();

				Base_Request::good(_t('feedback.sub.feed.updated'), $url_redirect);
			} else {
				Base_Request::bad(_t('feedback.sub.feed.error'), $url_redirect);
			}
		}
	}

	public function categoryAction() {
		$this->view->_layout(false);

		$categoryDAO = RSSServer_Factory::createCategoryDao();

		$id = Base_Request::param('id');
		$category = $categoryDAO->searchById($id);
		if ($id === false || null === $category) {
			Base_Error::error(404);
			return;
		}
		$this->view->category = $category;

		if (Base_Request::isPost()) {
			if (Base_Request::paramBoolean('use_default_purge_options')) {
				$category->_attributes('archiving', null);
			} else {
				if (!Base_Request::paramBoolean('enable_keep_max')) {
					$keepMax = false;
				} elseif (!$keepMax = Base_Request::param('keep_max')) {
					$keepMax = RSSServer_Feed::ARCHIVING_RETENTION_COUNT_LIMIT;
				}
				if ($enableRetentionPeriod = Base_Request::paramBoolean('enable_keep_period')) {
					$keepPeriod = RSSServer_Feed::ARCHIVING_RETENTION_PERIOD;
					if (is_numeric(Base_Request::param('keep_period_count')) && preg_match('/^PT?1[YMWDH]$/', Base_Request::param('keep_period_unit'))) {
						$keepPeriod = str_replace(1, Base_Request::param('keep_period_count'), Base_Request::param('keep_period_unit'));
					}
				} else {
					$keepPeriod = false;
				}
				$category->_attributes('archiving', [
					'keep_period' => $keepPeriod,
					'keep_max' => $keepMax,
					'keep_min' => intval(Base_Request::param('keep_min', 0)),
					'keep_favourites' => Base_Request::paramBoolean('keep_favourites'),
					'keep_labels' => Base_Request::paramBoolean('keep_labels'),
					'keep_unreads' => Base_Request::paramBoolean('keep_unreads'),
				]);
			}

			$position = Base_Request::param('position');
			$category->_attributes('position', '' === $position ? null : (int) $position);

			$values = [
				'name' => Base_Request::param('name', ''),
				'attributes' => $category->attributes(),
			];

			invalidateHttpCache();

			$url_redirect = array('c' => 'subscription', 'params' => array('id' => $id, 'type' => 'category'));
			if (false !== $categoryDAO->updateCategory($id, $values)) {
				Base_Request::good(_t('feedback.sub.category.updated'), $url_redirect);
			} else {
				Base_Request::bad(_t('feedback.sub.category.error'), $url_redirect);
			}
		}
	}

	/**
	 * This action displays the bookmarklet page.
	 */
	public function bookmarkletAction() {
		Base_View::prependTitle(_t('sub.title.subscription_tools') . ' . ');
	}
}
