<?php

global $_conf_vars;
$_options = array();

if (!isset($_SERVER['HTTP_HOST'])) {
	die();
}

if (!function_exists("gettext")) {
	require_once(dirname(__FILE__) . '/php-gettext/gettext.inc');
}
require_once(dirname(__FILE__) . '/version.php'); // Include the version info.

define('NPG_LAST_MODIFIED', gmdate('D, d M Y H:i:s') . ' GMT');

if (!defined('SORT_FLAG_CASE')) {
	define('SORT_FLAG_CASE', 0);
}
if (!defined('SORT_NATURAL')) {
	define('SORT_NATURAL', 0);
}
if (!defined('SORT_LOCALE_STRING')) {
	define('SORT_LOCALE_STRING', 0);
}

define('NEWLINE', "\n");

define('SCRIPTPATH', str_replace('\\', '/', dirname(dirname(__FILE__))));

//Note: these defines are for web path use only, they are rewritten to CORE_FOLDER and PLUGIN_FOLDER
define('CORE_PATH', 'npg');
define('PLUGIN_PATH', 'extensions');
define('USER_PLUGIN_PATH', 'extensions');

define('CORE_FOLDER', 'zp-core');
define('PLUGIN_FOLDER', 'zp-extensions');
define('COMMON_FOLDER', PLUGIN_FOLDER . '/common');
define('USER_PLUGIN_FOLDER', 'plugins');
define('ALBUMFOLDER', 'albums');
define('THEMEFOLDER', 'themes');
define('DATA_FOLDER', 'zp-data');
define('BACKUPFOLDER', DATA_FOLDER . '/backup');
define('CACHEFOLDER', 'cache');
define('UPLOAD_FOLDER', 'uploaded');
define('STATIC_CACHE_FOLDER', "cache_html");
define('CONFIGFILE', 'npg.cfg.php');
define('MUTEX_FOLDER', '.mutex');
define('UTILITIES_FOLDER', 'utilities');

//used by scriptLoader() to decide whether to inline the script (js or css)
define('INLINE_LOAD_THRESHOLD', 4096);

//bit masks for plugin priorities
define('CLASS_PLUGIN', 8192);
define('ADMIN_PLUGIN', 2048);
define('FEATURE_PLUGIN', 4096);
define('THEME_PLUGIN', 1024);
define('PLUGIN_PRIORITY', 1023);

//exif index defines
define('EXIF_SOURCE', 0);
define('EXIF_KEY', 1);
define('EXIF_DISPLAY_TEXT', 2);
define('EXIF_DISPLAY', 3);
define('EXIF_FIELD_SIZE', 4);
define('EXIF_FIELD_ENABLED', 5);
define('EXIF_FIELD_TYPE', 6);
define('EXIF_FIELD_LINKED', 7);


define('SYMLINK', function_exists('symlink') && strpos(@ini_get("suhosin.executor.func.blacklist"), 'symlink') === false);
define('CASE_INSENSITIVE', file_exists(strtoupper(__FILE__)));

$_debug = explode('-', NETPHOTOGRAPHICS_VERSION . '-');
$_debug = $_debug[1];
define('TEST_RELEASE', !empty($_debug));

define('DISPLAY_ERRORS', strpos($_debug, 'DISPLAY‑ERRORS')); // set to true to have PHP show errors on the web pages
define('DEBUG_404', strpos($_debug, '404')); // set to true to log 404 error processing debug information.
define('DEBUG_EXIF', strpos($_debug, 'EXIF')); // set to true to log start/finish of exif processing.
define('EXPLAIN_SELECTS', strpos($_debug, 'EXPLAIN')); //	set to true to log the "EXPLAIN" of SQL SELECT queries
define('DEBUG_FILTERS', strpos($_debug, 'FILTERS')); // set to true to log filter application sequence.
define('DEBUG_IMAGE', strpos($_debug, 'IMAGE')); // set to true to log image processing debug information.
define('DEBUG_LOCALE', strpos($_debug, 'LOCALE')); // used for examining language selection problems
define('DEBUG_LOGIN', strpos($_debug, 'LOGIN')); // set to true to log admin saves and login attempts
define('DEBUG_PLUGINS', strpos($_debug, 'PLUGINS')); // set to true to log plugin load sequence.
define('DEBUG_FEED', strpos($_debug, 'FEED')); // set to true to log class feed detected issues.
define('DEBUG_OBJECTS', strpos($_debug, 'OBJECTS')); // set to true to log object management.
define('TESTING_MODE', strpos($_debug, 'TESTING'));

unset($_debug);

$_DB_details = array(
		'mysql_host' => 'not connected',
		'mysql_database' => 'not connected',
		'mysql_prefix' => 'not connected',
		'mysql_user' => '',
		'mysql_pass' => ''
);
define('DB_NOT_CONNECTED', serialize($_DB_details));
define('MYSQL_CONNECTION_RETRIES', 10);
define('ER_TOO_MANY_USER_CONNECTIONS', 1203);
define('ER_CON_COUNT_ERROR', 1040);
define('ER_SERVER_GONE', 2006);
define('ER_SERVER_NO_RESPONSE', 2002);

/**
 * OFFSET_PATH definitions:
 * 		0		root scripts (e.g. the root index.php)
 * 		1		core scripts
 * 		2		setup scripts
 * 		3		plugin scripts
 * 		4		scripts in the theme folders
 */
$const_webpath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$const_serverpath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));

/**
 * see if we are executing out of any of the known script folders. If so we know how to adjust the paths
 * if not we presume the script is in the root of the installation. If it is not the script better have set
 * the SERVERPATH and WEBPATH defines to the correct values
 */
if (!preg_match('~(.*?)/(' . CORE_FOLDER . ')~', $const_webpath, $matches)) {
	preg_match('~(.*?)/(' . USER_PLUGIN_FOLDER . '|' . THEMEFOLDER . ')~', $const_webpath, $matches);
}

if ($matches) {
	$const_webpath = $matches[1];
	$const_serverpath = substr($const_serverpath, 0, strpos($const_serverpath, '/' . $matches[2]));

	if (!defined('OFFSET_PATH')) {
		switch ($matches[2]) {
			case CORE_FOLDER:
				define('OFFSET_PATH', 1);
				break;
			case USER_PLUGIN_FOLDER:
				define('OFFSET_PATH', 3);
				break;
			case THEMEFOLDER:
				define('OFFSET_PATH', 4);
				break;
		}
	}
} else {
	if (!defined('OFFSET_PATH')) {
		define('OFFSET_PATH', 0);
	}
}
$const_webpath = rtrim($const_webpath, '/');
if ($const_webpath == '.') {
	$const_webpath = '';
}

if (!defined('SERVERPATH')) {
	define('SERVERPATH', $const_serverpath);
}
define('CORE_SERVERPATH', SERVERPATH . '/' . CORE_FOLDER . '/');
if (!defined('WEBPATH')) {
	define('WEBPATH', $const_webpath);
}

unset($matches);
unset($const_webpath);
unset($const_serverpath);

define('DESIRED_PHP_EXTENSIONS', 'bz2,curl,exif,gettext,hash,iconv,intl,mbstring,openssl,session,tidy,zip');

// Contexts (Bitwise and combinable)
define("NPG_INDEX", 1);
define("NPG_ALBUM", 2);
define("NPG_IMAGE", 4);
define("NPG_COMMENT", 8);
define("NPG_SEARCH", 16);
define("SEARCH_LINKED", 32);
define("ALBUM_LINKED", 64);
define('IMAGE_LINKED', 128);
define('ZENPAGE_NEWS_PAGE', 256);
define('ZENPAGE_NEWS_ARTICLE', 512);
define('ZENPAGE_NEWS_CATEGORY', 1024);
define('ZENPAGE_NEWS_DATE', 2048);
define('ZENPAGE_PAGE', 4096);
define('ZENPAGE_SINGLE', 8192);

//icons
define('ARROW_DOWN_GREEN', '<span class="font_icon" style="color: green;font-size: large;">&dArr;</span>');
define('ARROW_RIGHT_BLUE', '<span class="font_icon" style="color: blue;font-size:large;">&rArr;</span>');
define('ARROW_UP_GRAY', '<span class="font_icon" style="color: lightgray;font-size: large;">&uArr;</span>');
define('ARROW_UP_GREEN', '<span class="font_icon" style="color: green;font-size: large;">&uArr;</span>');
define('BACK_ARROW_BLUE', '<span class="font_icon" style="color: blue;font-size:large;">&#10094;</span>');
define('BULLSEYE_BLUE', '<span class="font_icon" style="color: blue;font-size: large;">&#9678;</span>');
define('BULLSEYE_DARKORANGE', '<span class="font_icon" style="color: darkorange;font-size: large;;">&#9678;</span>');
define('BULLSEYE_GREEN', '<span class="font_icon" style="color: green;font-size: large;">&#9678;</span>');
define('BULLSEYE_LIGHTGRAY', '<span class="font_icon" style="color: lightgray;font-size: large;">&#9678;</span>');
define('BULLSEYE_RED', '<span class="font_icon" style="color: red;font-size: large;">&#9678;</span>');
define('BURST_BLUE', '<span class="font_icon" style="color: blue;font-size: large;">&#10040;</span>');
define('CHECKMARK_GREEN', '<span class="font_icon" style="color: green;font-size: large;">&#10003;</span>');
define('CIRCLED_BLUE_STAR', '<span class="font_icon" style="color: blue;font-size: large;">&#10026;</span>');
define('CLIPBOARD', '<span class="font_icon" style="font-family: Sego UI Emoji; color: goldenrod;">&#128203;</span>');
define('CLOCKFACE', '<span class="font_icon" style="letter-spacing: -4px;">&#128343;</span>');
define('CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN', '<span class="font_icon" style="font-size:large;color:green;">&#8635;</span>');
define('CLOCKWISE_OPEN_CIRCLE_ARROW_RED', '<span class="font_icon" style="font-size:large;color:red;">&#8635;</span>');
define('CROSS_MARK_RED', '<span class="font_icon" style="color: red;">&#10060;</span>');
define('CURVED_UPWARDS_AND_RIGHTWARDS_ARROW_BLUE', '<span class="font_icon" style="color:blue;font-size:large;">&#10150;</span>');
define('DRAG_HANDLE', '<span class="font_icon" style="color:lightsteelblue;font-size: x-large;">&#10021;</span>');
define('DRAG_HANDLE_ALERT', '<span class="font_icon" style="color:red;font-size: x-large;">&#10021;</span>');
define('ENVELOPE', '<span class="font_icon" style="font-size: large;">&#9993;</span>');
define('EXCLAMATION_RED', '<span class="font_icon" style="color: red; font-family: Times New Roman; font-weight: bold;font-size: large;">&#33;</span>');
define('GEAR_SYMBOL', '&#9881;');
define('HIDE_ICON', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/hide.png" /></span>');
define('INFORMATION_BLUE', '<span class="font_icon" style="color: blue; font-family: Times New Roman; font-size: large;">&#8505;</span>');
define('INSTALL', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/install.png" /></span>');
define('SETUP', '<span class="font_icon" style="font-size: large;">&#x1F6E0;&#xFE0F;</span>');
define('KEY_RED', '<span class="font_icon" style="color: red;">&#128273;</span>');
define('LOCK', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/lock.png" /></span>');
define('LOCK_OPEN', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/lock_open.png" /></span>');
define('MENU_SYMBOL', '&#9776;');
define('NO_ENTRY', '<span class="font_icon" style="color: red;">&#9940;</span>');
define('NORTH_WEST_CORNER_ARROW', '<span class="font_icon" style="color: green;font-weight: bold;">&#8689;</span>');
define('OPTIONS_ICON', '<span class="font_icon" style="font-size: large;">' . GEAR_SYMBOL . '</span>');
define('PENCIL_ICON', '<span class="font_icon" style="color: darkgoldenrod; font-size: large;">&#x270E;</span>');
define('PLUS_ICON', '<span class="font_icon" style="color: green;font-size: large;">&#x271A;</span>');
define('RECYCLE_ICON', '<span class="font_icon" style="color: red;font-size: large;font-weight: bold;">&#x2672;</span>');
define('SOUTH_EAST_CORNER_ARROW', '<span class="font_icon" style="color: green;font-weight: bold;">&#8690;</span>');
define('SWAP_ICON', '<span class="font_icon" style="font-size: x-large;">&#x21C4;</span>');
define('WARNING_SIGN_ORANGE', '<span class="font_icon" style="color: darkorange;font-size: large;">&#9888;</span>');
define('WASTEBASKET', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/trashcan.png" /></span>');
define('BADGE_BLUE', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_blue.png" /></span>');
define('BADGE_GOLD', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_gold.png" /></span>');
//end icons