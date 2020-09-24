# Requirements
* A recent browser like Firefox / IceCat, Internet Explorer 11 / Edge (minus a few details), Chromium / Chrome, Opera, Safari.
	* Works on mobile (except a few features)
* Light server running Linux or Windows
	* It even works on Raspberry Pi 1 with response time under a second (tested with 150 feeds, 22k articles)
* A web server: Apache2 (recommended), nginx, lighttpd (not tested on others)
* PHP 5.6+ (PHP 7+ recommended for higher performance)
	* Required extensions: [cURL](https://www.php.net/curl), [DOM](https://www.php.net/dom), [JSON](https://www.php.net/json), [XML](https://www.php.net/xml), [session](https://www.php.net/session), [ctype](https://www.php.net/ctype), and [PDO_MySQL](https://www.php.net/pdo-mysql) or [PDO_SQLite](https://www.php.net/pdo-sqlite) or [PDO_PGSQL](https://www.php.net/pdo-pgsql)
	* Recommended extensions: [GMP](https://www.php.net/gmp) (for API access on 32-bit platforms), [IDN](https://www.php.net/intl.idn) (for Internationalized Domain Names), [mbstring](https://www.php.net/mbstring) (for Unicode strings), [iconv](https://www.php.net/iconv) (for charset conversion), [ZIP](https://www.php.net/zip) (for import/export), [zlib](https://www.php.net/zlib) (for compressed feeds)
* MySQL 5.5.3+ or MariaDB equivalent, or SQLite 3.7.4+, or PostgreSQL 9.5+


## Advice
* For better security, expose only the `./public/` folder to the Web.
	* Be aware that the `./data/` folder contains all personal data, so it is a bad idea to expose it.
* The `./constants.php` file defines access to the application folder. If you want to customize your installation, look here first.
* If you encounter any problem, logs are accessible from the interface or manually in `./data/users/*/log*.txt` files.
	* The special folder `./data/users/_/` contains the part of the logs that are shared by all users.


# F.A.Q.:
* The date and time in the right-hand column is the date declared by the feed, not the time at which the article was received by RSS Server, and it is not used for sorting.
	* In particular, when importing a new feed, all of its articles will appear at the top of the feed list regardless of their declared date.

## Only for some options or configurations
* [bcrypt.js](https://github.com/dcodeIO/bcrypt.js)
* [phpQuery](https://github.com/phpquery/phpquery)

