<?php

class RSSServer_Factory {

	public static function createUserDao($username = null) {
		return new RSSServer_UserDAO($username);
	}

	public static function createCategoryDao($username = null) {
		$conf = Minz_Configuration::get('system');
		switch ($conf->db['type']) {
			case 'sqlite':
				return new RSSServer_CategoryDAOSQLite($username);
			default:
				return new RSSServer_CategoryDAO($username);
		}
	}

	public static function createFeedDao($username = null) {
		$conf = Minz_Configuration::get('system');
		switch ($conf->db['type']) {
			case 'sqlite':
				return new RSSServer_FeedDAOSQLite($username);
			default:
				return new RSSServer_FeedDAO($username);
		}
	}

	public static function createEntryDao($username = null) {
		$conf = Minz_Configuration::get('system');
		switch ($conf->db['type']) {
			case 'sqlite':
				return new RSSServer_EntryDAOSQLite($username);
			case 'pgsql':
				return new RSSServer_EntryDAOPGSQL($username);
			default:
				return new RSSServer_EntryDAO($username);
		}
	}

	public static function createTagDao($username = null) {
		$conf = Minz_Configuration::get('system');
		switch ($conf->db['type']) {
			case 'sqlite':
				return new RSSServer_TagDAOSQLite($username);
			case 'pgsql':
				return new RSSServer_TagDAOPGSQL($username);
			default:
				return new RSSServer_TagDAO($username);
		}
	}

	public static function createStatsDAO($username = null) {
		$conf = Minz_Configuration::get('system');
		switch ($conf->db['type']) {
			case 'sqlite':
				return new RSSServer_StatsDAOSQLite($username);
			case 'pgsql':
				return new RSSServer_StatsDAOPGSQL($username);
			default:
				return new RSSServer_StatsDAO($username);
		}
	}

	public static function createDatabaseDAO($username = null) {
		$conf = Minz_Configuration::get('system');
		switch ($conf->db['type']) {
			case 'sqlite':
				return new RSSServer_DatabaseDAOSQLite($username);
			case 'pgsql':
				return new RSSServer_DatabaseDAOPGSQL($username);
			default:
				return new RSSServer_DatabaseDAO($username);
		}
	}

}
