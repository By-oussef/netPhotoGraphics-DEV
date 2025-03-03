<?php
/**
 * Use to tweet new objects as they are published.
 *
 * You will need to setup application credentials on the
 * {@link http://dev.twitter.com/ Twitter Developer's Console.} for use by this
 * Plugin. You will need a <i>Consumer Key</i>, <i>Consumer Secret</i>, <i>oAuth Token</i>,
 * and <i>oAuth Secret</i>. The first two of these are the same credentials used
 * by the <var>twitterLogin</var> plugin.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/tweet_news
 * @pluginCategory admin
 */
$plugin_is_filter = 9 | FEATURE_PLUGIN;
$plugin_description = gettext('Tweet news articles when published.');
$plugin_disable = (function_exists('curl_init')) ? false : gettext('The <em>php_curl</em> extension is required');
$plugin_notice = (extensionEnabled('TinyURL')) ? '' : gettext('Enable the tinyURL plugin to shorten URLs in your tweets.');

$option_interface = 'tweet';

if ($plugin_disable) {
	enableExtension('tweet_news', 0);
} else {
	npgFilters::register('show_change', 'tweet::published');
	if (getOption('tweet_news_albums')) {
			npgFilters::register('new_album', 'tweet::published');
	}
	if (getOption('tweet_news_images')) {
			npgFilters::register('new_image', 'tweet::published');
	}
	if (getOption('tweet_news_news')) {
			npgFilters::register('new_article', 'tweet::newZenpageObject');
	}
	if (getOption('tweet_news_pages')) {
			npgFilters::register('new_page', 'tweet::newZenpageObject');
	}
	npgFilters::register('admin_overview', 'tweet::errorsOnOverview');
	npgFilters::register('admin_note', 'tweet::errorsOnAdmin');
	npgFilters::register('edit_album_utilities', 'tweet::tweeter');
	npgFilters::register('save_album_data', 'tweet::tweeterExecute');
	npgFilters::register('edit_image_utilities', 'tweet::tweeter');
	npgFilters::register('save_image_data', 'tweet::tweeterExecute');
	npgFilters::register('general_utilities', 'tweet::tweeter');
	npgFilters::register('save_article_data', 'tweet::tweeterZenpageExecute');
	npgFilters::register('save_page_data', 'tweet::tweeterZenpageExecute');

	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . "/common/oAuth/twitteroauth.php");
}

/**
 *
 * Standard options interface
 * @author Stephen
 *
 */
class tweet {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('tweet_news_consumer', NULL);
			setOptionDefault('tweet_news_consumer_secret', NULL);
			setOptionDefault('tweet_news_oauth_token', NULL);
			setOptionDefault('tweet_news_oauth_token_secret', NULL);
			setOptionDefault('tweet_news_categories_none', NULL);
			setOptionDefault('tweet_news_images', NULL);
			setOptionDefault('tweet_news_albums', NULL);
			setOptionDefault('tweet_news_news', 1);
			setOptionDefault('tweet_news_protected', NULL);
			setOptionDefault('tweet_news_pages', 0);
			setOptionDefault('tweet_language', NULL);
		}
	}

	/**
	 *
	 * supported options
	 */
	function getOptionsSupported() {
		global $_CMS;
		$options = array(
				gettext('Consumer Key') => array('key' => 'tweet_news_consumer', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 1,
						'desc' => gettext('This your Twitter <em>Consumer Key</em>.')),
				gettext('Consumer Secret') => array('key' => 'tweet_news_consumer_secret', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext('This is your <em>Consumer Secret</em>.')),
				gettext('Access Token') => array('key' => 'tweet_news_oauth_token', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 4,
						'desc' => gettext('The application <em>oAuth Token</em>.')),
				gettext('Access Token Secret') => array('key' => 'tweet_news_oauth_token_secret', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 5,
						'desc' => gettext('The application <em>oAuth Token Secret</em>.')),
				gettext('Protected objects') => array('key' => 'tweet_news_protected', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 7,
						'desc' => gettext('If checked, protected items will be tweeted. <strong>Note:</strong> followers will need the password to visit the tweeted link.'))
		);
		$list = array('<em>' . gettext('Albums') . '</em>' => 'tweet_news_albums', '<em>' . gettext('Images') . '</em>' => 'tweet_news_images');
		if (extensionEnabled('zenpage')) {
			$list['<em>' . gettext('News') . '</em>'] = 'tweet_news_news';
			$list['<em>' . gettext('Pages') . '</em>'] = 'tweet_news_pages';
		} else {
			setOption('tweet_news_news', 0);
			setOption('tweet_news_pages', 0);
		}
		$options[gettext('Tweet')] = array('key' => 'tweet_news_items', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
				'order' => 6,
				'checkboxes' => $list,
				'desc' => gettext('If a <em>type</em> is checked, a Tweet will be made when an object of that <em>type</em> is published.'));
		If (getOption('multi_lingual')) {
			$options[gettext('Tweet Language')] = array('key' => 'tweet_language', 'type' => OPTION_TYPE_SELECTOR,
					'order' => 5.5,
					'selections' => i18n::generateLanguageList(),
					'desc' => gettext('Select the language for the Tweet message.'));
		}
		if (getOption('tweet_news_news') && is_object($_CMS)) {
			$catlist = getSerializedArray(getOption('tweet_news_categories'));
			$news_categories = $_CMS->getAllCategories(false);
			$catlist = array(gettext('*not categorized*') => 'tweet_news_categories_none');
			foreach ($news_categories as $category) {
				$option = 'tweet_news_categories_' . $category['titlelink'];
				$catlist[get_language_string($category['title'])] = $option;
				setOptionDefault($option, NULL);
			}
			$options[gettext('News categories')] = array('key' => 'tweet_news_categories', 'type' => OPTION_TYPE_CHECKBOX_UL,
					'order' => 6.5,
					'checkboxes' => $catlist,
					'desc' => gettext('Only those <em>news categories</em> checked will be Tweeted. <strong>Note:</strong> <em>*not categorized*</em> means those news articles which have no category assigned.'));
		}

		return $options;
	}

	/**
	 *
	 * place holder
	 * @param string $option
	 * @param mixed $currentValue
	 */
	function handleOption($option, $currentValue) {

	}

	/**
	 *
	 * Actual tweet processing of message
	 * @param string $status
	 */
	private static function sendTweet($status) {
		global $tweet;
		if (!is_object($tweet)) {
			$consumerKey = getOption('tweet_news_consumer');
			$consumerSecret = getOption('tweet_news_consumer_secret');
			$OAuthToken = getOption('tweet_news_oauth_token');
			$OAuthSecret = getOption('tweet_news_oauth_token_secret');
			$tweet = new TwitterOAuth($consumerKey, $consumerSecret, $OAuthToken, $OAuthSecret);
		}
		$response = $tweet->post('statuses/update', array('status' => $status));
		if (isset($response->error)) {
			return $response->error;
		}
		return false;
	}

	/**
	 *
	 * filter for new news articles
	 * @param string $msg
	 * @param object $article
	 */
	static function newZenpageObject($msg, $article) {
		$error = self::tweetObjectWithCheck($article);
		if ($error) {
			$msg .= '<p class="errorbox">' . $error . '</p>';
		}
		return $msg;
	}

	/**
	 *
	 * filter for the setShow() methods
	 * @param object $obj
	 */
	static function published($obj) {
		if ($obj->getShow()) {
			$error = self::tweetObjectWithCheck($obj);
			if ($error) {
				query('INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`aux`,`data`) VALUES ("tweet_news","error",' . db_quote($error) . ')');
			}
		}
		return $obj;
	}

	/**
	 *
	 * used by the filters to decide if to tweet
	 * @param object $obj
	 */
	private static function tweetObjectWithCheck($obj) {
		$error = '';
		$type = $obj->table;
		if (getOption('tweet_news_' . $type)) {
			if ($obj->getShow()) {
				if (getOption('tweet_news_protected') || !$obj->isProtected()) {
					switch ($type = $obj->table) {
						case 'pages':
							$dt = $obj->getPublishDate();
							if ($dt > date('Y-m-d H:i:s')) {
								$result = query_single_row('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="tweet_news" AND `aux`="pending_pages" AND `data`=' . db_quote($obj->getTitlelink()));
								if (!$result) {
									query('INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`aux`,`data`) VALUES ("tweet_news","pending_pages",' . db_quote($obj->getTitlelink()) . ')');
								}
							} else {
								$error = self::tweetObject($obj);
							}
							break;
						case 'news':
							$tweet = false;
							$mycategories = $obj->getCategories();
							if (empty($mycategories)) {
								$tweet = getOption('tweet_news_categories_none');
							} else {
								foreach ($mycategories as $cat) {
									if ($tweet = getOption('tweet_news_categories_' . $cat['titlelink'])) {
										break;
									}
								}
							}
							if (!$tweet) {
								break;
							}
							$dt = $obj->getPublishDate();
							if ($dt > date('Y-m-d H:i:s')) {
								$result = query_single_row('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="tweet_news" AND `aux`="pending" AND `data`=' . db_quote($obj->getTitlelink()));
								if (!$result) {
									query('INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`aux`,`data`) VALUES ("tweet_news","pending",' . db_quote($obj->getTitlelink()) . ')');
								}
							} else {
								$error = self::tweetObject($obj);
							}
							break;
						case 'albums':
							$dt = $obj->getPublishDate();
							if ($dt > date('Y-m-d H:i:s')) {
								$result = query_single_row('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="tweet_news" AND `aux`="pending_albums" AND `data`=' . db_quote($obj->name));
								if (!$result) {
									query('INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`aux`,`data`) VALUES ("tweet_news","pending_albums",' . db_quote($obj->name) . ')');
								}
							} else {
								$error = self::tweetObject($obj);
							}
							break;
						case 'images':
							$dt = $obj->getPublishDate();
							if ($dt > date('Y-m-d H:i:s')) {
								$result = query_single_row('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="tweet_news" AND `aux`="pending_images" AND `data`=' . db_quote($obj->imagefolder . '/' . $obj->filename));
								if (!$result) {
									query('INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`aux`,`data`) VALUES ("tweet_news","pending_images",' . db_quote($obj->imagefolder . '/' . $obj->filename) . ')');
								}
							} else {
								$error = self::tweetObject($obj);
							}
							break;
					}
				}
			}
		}
		return $error;
	}

	/**
	 * Composes the tweet at 140 characters or less
	 * @param string $link
	 * @param string $title
	 * @param string $text
	 *
	 * @return string
	 */
	private static function composeStatus($link, $title, $text) {
		$text = trim(html_decode(getBare($text)));
		if ($title) {
			$title = trim(html_decode(getBare($title))) . ': ';
		}
		if (strlen($title . $text . ' ' . $link) > 140) {
			$c = 140 - strlen($link);
			if (mb_strlen($title) >= ($c - 25)) { //	not much point in the body if shorter than 25
				$text = truncate_string($title, $c - 4, '... ') . $link; //	allow for ellipsis
			} else {
				$c = $c - mb_strlen($title) - 5;
				$text = $title . truncate_string($text, $c, '... ') . $link;
			}
		} else {
			$text = $title . $text . ' ' . $link;
		}
		$error = self::sendTweet($text);
		if ($error) {
			$error = sprintf(gettext('Error tweeting <code>%1$s</code>: %2$s'), $text, $error);
		}
		return $error;
	}

	/**
	 *
	 * Formats the message and calls sendTweet() on an object
	 * @param object $obj
	 */
	private static function tweetObject($obj) {
		if (getOption('multi_lingual')) {
			$cur_locale = i18n::getUserLocale();
			i18n::setupCurrentLocale(getOption('tweet_language')); //	the log will be in the language of the master user.
		}
		$error = '';
		if (class_exists('tinyURL')) {
			$link = FULLHOSTPATH . tinyURL::getURL($obj);
		} else {
			$link = FULLHOSTPATH . $obj->getLink();
		}
		switch ($type = $obj->table) {
			case 'pages':
			case 'news':
				$error = self::composeStatus($link, $obj->getTitle(), $obj->getContent());
				break;
			case 'albums':
			case 'images':
				if ($type == 'images') {
					$text = sprintf(gettext('New image: [%2$s]%1$s '), $item = $obj->getTitle(), $obj->imagefolder);
				} else {
					$text = sprintf(gettext('New album: %s '), $item = $obj->getTitle());
				}
				$error = self::composeStatus($link, '', $item);
				break;
			case 'comments':
				$error = self::composeStatus($link, '', $obj->getComment());
				break;
		}
		if (isset($cur_locale)) {
			i18n::setupCurrentLocale($cur_locale); //	restore to whatever was in effect.
		}
		return $error;
	}

	/**
	 *
	 * returns any tweet errors accumulated but not displayed. Clears the list
	 */
	private static function tweetFetchErrors() {
		$result = query_full_array('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="tweet_news" AND `aux`="error"');
		$errors = '';
		foreach ($result as $error) {
			$errors .= $error['data'] . '<br />';
			query('DELETE FROM' . prefix('plugin_storage') . ' WHERE `id`=' . $error['id']);
		}
		return $errors;
	}

	/**
	 *
	 * filter which will display tweet errors on the admin overview page
	 * @param string $side
	 */
	static function errorsOnOverview($side) {
		if ($side == 'left') {
			$errors = self::tweetFetchErrors();
			if ($errors) {
				?>
				<div class="box" id="overview-news">
					<h2 class="h2_bordered"><?php echo gettext("Tweet News Errors:"); ?></h2>
					<?php echo '<p class="errorbox">' . $errors . '</p>'; ?>
				</div>
				<?php
			}
		}
		return $side;
	}

	/**
	 *
	 * filter to display tweet errors on an admin page
	 * @param string $tab
	 * @param string $subtab
	 */
	static function errorsOnAdmin($tab, $subtab) {
		$errors = self::tweetFetchErrors();
		if ($errors) {
			echo '<p class="errorbox">' . $errors . '</p>';
		}
		return $tab;
	}

	/**
	 *
	 * filter to place tweet action on object edit pages
	 * @param string $before
	 * @param object $object
	 * @param int $prefix
	 */
	static function tweeter($before, $object, $prefix = NULL) {
		$output = '<p class="checkbox">' . "\n" . '<label>' . "\n" . '<input type="checkbox" name="tweet_me' . $prefix . '" id="tweet_me' . $prefix . '" value="1" /> <img src="' . WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/tweet_news/twitter_newbird_blue.png" /> ' . gettext('Tweet me') . "\n</label>\n</p>\n";
		return $before . $output;
	}

	/**
	 *
	 * processes the image and album tweet request
	 * @param object $object
	 * @param string $prefix
	 */
	static function tweeterExecute($object, $prefix) {
		if (isset($_POST['tweet_me' . $prefix])) {
			$error = self::tweetObject($object);
			if ($error) {
				query('INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`aux`,`data`) VALUES ("tweet_news","error",' . db_quote($error) . ')');
			}
		}
		return $object;
	}

	/**
	 *
	 * filter to process zenpage tweet requests
	 * @param unknown_type $custom
	 * @param unknown_type $object
	 */
	static function tweeterZenpageExecute($object) {
		self::tweeterExecute($object, '');
		return $custom;
	}

}
?>