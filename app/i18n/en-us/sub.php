<?php

return array(
	'add' => 'Feed and category creation has been moved <a href=\'%s\'>here</a>. It is also accessible from the menu on the left and from the ✚ icon available on the main page.',
	'api' => array(
		'documentation' => 'Copy the following URL to use it within an external tool.',
		'title' => 'API',
	),
	'bookmarklet' => array(
		'documentation' => 'Drag this button to your bookmarks toolbar or right-click it and choose "Bookmark This Link". Then click the "Subscribe" button in any page you want to subscribe to.',
		'label' => 'Subscribe',
		'title' => 'Bookmarklet',
	),
	'category' => array(
		'_' => 'Category',
		'add' => 'Add a category',
		'archiving' => 'Archiving',
		'empty' => 'Empty category',
		'information' => 'Information',
		'position' => 'Display position',
		'position_help' => 'To control category sort order',
		'title' => 'Title',
	),
	'feed' => array(
		'add' => 'Add a RSS feed',
		'advanced' => 'Advanced',
		'archiving' => 'Archiving',
		'auth' => array(
			'configuration' => 'Login',
			'help' => 'Allows access to HTTP protected RSS feeds',
			'http' => 'HTTP Authentication',
			'password' => 'HTTP password',
			'username' => 'HTTP username',
		),
		'clear_cache' => 'Always clear cache',
		'css_help' => 'Retrieves truncated RSS feeds (caution, requires more time!)',
		'css_path' => 'Article CSS selector on original website',
		'description' => 'Description',
		'empty' => 'This feed is empty. Please verify that it is still maintained.',
		'error' => 'This feed has encountered a problem. Please verify that it is always reachable then update it.',
		'filteractions' => array(
			'_' => 'Filter actions',
			'help' => 'Write one search filter per line.',
		),
		'information' => 'Information',
		'keep_min' => 'Minimum number of articles to keep',
		'maintenance' => array(
			'clear_cache' => 'Clear cache',
			'clear_cache_help' => 'Clear the cache for this feed.',
			'reload_articles' => 'Reload articles',
			'reload_articles_help' => 'Reload articles and fetch complete content if a selector is defined.',
			'title' => 'Maintenance',
		),
		'moved_category_deleted' => 'When you delete a category, its feeds are automatically classified under <em>%s</em>.',
		'mute' => 'mute',
		'no_selected' => 'No feed selected.',
		'number_entries' => '%d articles',
		'priority' => array(
			'_' => 'Visibility',
			'archived' => 'Do not show (archived)',
			'main_stream' => 'Show in main stream',
			'normal' => 'Show in its category',
		),
		'selector_preview' => array(
			'show_raw' => 'Show source code',
			'show_rendered' => 'Show content',
		),
		'show' => array(
			'all' => 'Show all feeds',
			'error' => 'Show only feeds with errors',
		),
		'showing' => array(
			'error' => 'Showing only feeds with errors',
		),
		'ssl_verify' => 'Verify SSL security',
		'stats' => 'Statistics',
		'think_to_add' => 'You may add some feeds.',
		'timeout' => 'Timeout in seconds',
		'title' => 'Title',
		'title_add' => 'Add an RSS feed',
		'ttl' => 'Do not automatically refresh more often than',
		'url' => 'Feed URL',
		'validator' => 'Check the validity of the feed',
		'website' => 'Website URL',
		'websub' => 'Instant notification with WebSub',
	),
	'firefox' => array(
		'documentation' => 'Follow the steps described <a href="https://developer.mozilla.org/en-US/Firefox/Releases/2/Adding_feed_readers_to_Firefox#Adding_a_new_feed_reader_manually">here</a> to add RSSServer to Firefox feed reader list.',
		'obsolete_63' => 'From version 63 and onwards, Firefox has removed the ability to add your own subscription services that are not standalone programs.',
		'title' => 'Firefox feed reader',
	),
	'import_export' => array(
		'export' => 'Export',
		'export_labelled' => 'Export your labeled articles',
		'export_opml' => 'Export list of feeds (OPML)',
		'export_starred' => 'Export your favorites',
		'feed_list' => 'List of %s articles',
		'file_to_import' => 'File to import<br />(OPML, JSON or ZIP)',
		'file_to_import_no_zip' => 'File to import<br />(OPML or JSON)',
		'import' => 'Import',
		'starred_list' => 'List of favorite articles',
		'title' => 'Import / export',
	),
	'menu' => array(
		'add' => 'Add a feed or category',
		'add_feed' => 'Add a feed',
		'bookmark' => 'Subscribe (RSSServer bookmark)',
		'import_export' => 'Import / export',
		'subscription_management' => 'Subscription management',
		'subscription_tools' => 'Subscription tools',
		'tag_management' => 'Tag management',	// TODO - Translation
	),
	'tag' => array(
		'name' => 'Name',	// TODO - Translation
		'new_name' => 'New name',	// TODO - Translation
		'old_name' => 'Old name',	// TODO - Translation
	),
	'title' => array(
		'_' => 'Subscription management',
		'add' => 'Add a feed or category',
		'add_category' => 'Add a category',
		'add_feed' => 'Add a feed',
		'add_tag' => 'Add a tag',	// TODO - Translation
		'delete_tag' => 'Delete a tag',	// TODO - Translation
		'feed_management' => 'RSS feeds management',
		'rename_tag' => 'Rename a tag',	// TODO - Translation
		'subscription_tools' => 'Subscription tools',
	),
);
