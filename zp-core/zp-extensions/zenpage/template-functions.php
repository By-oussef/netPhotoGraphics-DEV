<?php
/**
 * zenpage template functions
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins/zenpage
 */
/* * ********************************************* */
/* ZENPAGE TEMPLATE FUNCTIONS
  /*********************************************** */


/* * ********************************************* */
/* General functions
  /*********************************************** */

/**
 * Checks if the current page is in news context.
 *
 * @return bool
 */
function is_News() {
	global $_CMS_current_article;
	return(!is_null($_CMS_current_article));
}

/**
 * Checks if the current page is the news page in general.
 *
 * @return bool
 */
function is_NewsPage() {
	global $_gallery_page;
	return $_gallery_page == 'news.php';
}

/**
 * Checks if the current page is a single news article page
 *
 * @return bool
 */
function is_NewsArticle() {
	return is_News() && in_context(ZENPAGE_SINGLE);
}

/**
 * Checks if the current page is a news category page
 *
 * @return bool
 */
function is_NewsCategory() {
	return in_context(ZENPAGE_NEWS_CATEGORY);
}

/**
 * Checks if the current page is a news archive page
 *
 * @return bool
 */
function is_NewsArchive() {
	return in_context(ZENPAGE_NEWS_DATE);
}

/**
 * Checks if the current page is a zenpage page
 *
 * @return bool
 */
function is_Pages() {
	return in_context(ZENPAGE_PAGE);
}

/**
 * returns the "sticky" value of the news article
 * @param obj $newsobj optional news object to check directly outside news context
 * @return bool
 */
function stickyNews($newsobj = NULL) {
	global $_CMS_current_article;
	if (is_null($newsobj)) {
		$newsobj = $_CMS_current_article;
	}
	return $newsobj->getSticky();

	return false;
}

/**
 * Wrapper function to get the author of a news article or page: Used by getNewsAuthor() and getPageAuthor().
 *
 * @param bool $fullname False for the user name, true for the full name
 *
 * @return string
 */
function getOwner($fullname = false) {
	global $_CMS_current_page, $_CMS_current_article, $_authority;

	if (is_Pages()) {
		$obj = $_CMS_current_page;
	} else if (is_News()) {
		$obj = $_CMS_current_article;
	} else {
		$obj = false;
	}
	if ($obj) {
		if ($fullname) {
			$admin = $_authority->getAnAdmin(array('`user`=' => $obj->getOwner(), '`valid`=' => 1));
			if (is_object($admin) && $admin->getName()) {
				return $admin->getName();
			}
		}
		return $obj->getOwner();
	}
	return false;
}

/* * ********************************************* */
/* News article functions
  /*********************************************** */

/**
 * Gets the latest news either only news articles or with the latest images or albums
 *
 * NOTE: This function excludes articles that are password protected via a category for not logged in users!
 *
 * @param int $number The number of news items to get
 * @param string $category Optional news articles by category (only "none" option)
 * @param bool $sticky place sticky articles at the front of the list
 * @param string $sortdirection 'asc' ascending otherwise descending
 * @return array
 */
function getLatestNews($number = 2, $category = '', $sticky = true, $sortdirection = 'desc') {
	global $_CMS;
	$sortdir = $sortdirection && strtolower($sortdirection) != 'asc';
	if (empty($category)) {
		$latest = $_CMS->getArticles($number, NULL, true, NULL, $sortdir, $sticky, NULL);
	} else {
		$catobj = newCategory($category);
		$latest = $catobj->getArticles($number, NULL, true, NULL, $sortdir, $sticky);
	}
	return $latest;
}

/**
 * Prints the latest news either only news articles or with the latest images or albums as a unordered html list
 *
 * NOTE: Latest images and albums require the image_album_statistic plugin
 *
 * @param int $number The number of news items to get
 * @param string $category Optional news articles by category (only "none" option"
 * @param bool $showdate If the date should be shown
 * @param bool $showcontent If the content should be shown
 * @param int $contentlength The lengths of the content
 * @param bool $showcat If the categories should be shown
 * @param string $readmore Text for the read more link, if empty the option value for "zenpage_readmore" is used
 * @param bool $sticky place sticky articles at the front of the list
 * @return string
 */
function printLatestNews($number = 5, $category = '', $showdate = true, $showcontent = true, $contentlength = 70, $showcat = true, $readmore = NULL, $sticky = true) {
	global $_gallery;
	if (is_null($readmore)) {
		$readmore = get_language_string(READ_MORE);
	}

	$latest = getLatestNews($number, $category, $sticky);
	echo "\n<ul id=\"latestnews\">\n";
	$count = "";
	foreach ($latest as $item) {
		$count++;
		$category = "";
		$categories = "";

		$obj = newArticle($item['titlelink']);
		$title = html_encode($obj->getTitle());
		$link = html_encode(getNewsURL($item['titlelink']));

		$count2 = 0;
		$category = $obj->getCategories();
		foreach ($category as $cat) {
			$catobj = newCategory($cat['titlelink']);
			$count2++;
			if ($count2 != 1) {
				$categories = $categories . ", ";
			}
			$categories = $categories . $catobj->getTitle();
		}
		$thumb = "";
		$content = $obj->getContent();
		$date = formattedDate(DATE_FORMAT, strtotime($item['date']));
		echo "<li>";
		echo "<h3><a href=\"" . $link . "\" title=\"" . getBare(html_encode($title)) . "\">" . $title . "</a></h3>\n";
		if ($showdate) {
			echo "<span class=\"latestnews-date\">" . $date . "</span>\n";
		}
		if ($showcontent) {
			echo "<span class=\"latestnews-desc\">" . shortenContent($content, $contentlength, SHORTENINDICATOR) . "</span>\n";
		}
		if ($showcat && !empty($categories)) {
			echo "<span class=\"latestnews-cats\">(" . html_encode($categories) . ")</span>\n";
		}
		echo "</li>\n";
		if ($count == $number) {
			break;
		}
	}
	echo "</ul>\n";
}

/**
 * Returns true if there are any news articles to show.
 *
 * @return bool
 * @global object $_CMS
 */
function hasNews() {
	global $_CMS;
	$news = $_CMS->news_enabled && $_CMS->getArticles(0, NULL, false, NULL, NULL, NULL, NULL, null, 1);
	return $news;
}

/**
 * Returns the number of news articles.
 *
 * When in search context this is the count of the articles found. Otherwise
 * it is the count of articles that match the criteria.
 *
 * @param bool $total
 * @return int
 */
function getNumNews($total = false) {
	global $_CMS, $_current_search;
	if ($_CMS->news_enabled) {
		if ($total) {
			return count($_CMS->getArticles(0));
		} else if (in_context(NPG_SEARCH)) {
			return count($_current_search->getArticles());
		} else {
			return count($_CMS->getArticles(0));
		}
	}
	return NULL;
}

/**
 * Returns the next news item on a page.
 * sets $_CMS_current_article to the next news item
 * Returns true if there is an new item to be shown
 *
 * @return bool
 */
function next_news() {
	global $_CMS, $_CMS_current_article, $_CMS_current_article_restore, $_CMS_articles, $_CMS_current_category, $_gallery, $_current_search;
	if ($_CMS->news_enabled && is_null($_CMS_articles)) {
		if (in_context(NPG_SEARCH)) {
			//note: we do not know how to paginate the search page, so for now we will return all news articles
			$_CMS_articles = $_current_search->getArticles(ARTICLES_PER_PAGE, NULL, true, NULL, NULL);
		} else {
			if (in_context(ZENPAGE_NEWS_CATEGORY)) {
				$_CMS_articles = $_CMS_current_category->getArticles(ARTICLES_PER_PAGE, NULL, false, NULL, NULL);
			} else {
				$_CMS_articles = $_CMS->getArticles(ARTICLES_PER_PAGE, NULL, false, NULL, NULL);
			}
			if (empty($_CMS_articles)) {
				return npgFilters::apply('next_object_loop', NULL, $_CMS_current_article);
			}
		}
		$_CMS_current_article_restore = $_CMS_current_article;
	}
	if (!empty($_CMS_articles)) {
		$news = array_shift($_CMS_articles);
		if (is_array($news)) {
			add_context(ZENPAGE_NEWS_ARTICLE);
			$_CMS_current_article = newArticle($news['titlelink']);
			return npgFilters::apply('next_object_loop', true, $_CMS_current_article);
		}
	}

	$_CMS_articles = NULL;
	$_CMS_current_article = $_CMS_current_article_restore;
	rem_context(ZENPAGE_NEWS_ARTICLE);
	return npgFilters::apply('next_object_loop', false, $_CMS_current_article);
}

/**
 * Gets the id of a news article/item
 *
 * @return int
 */
function getNewsID() {
	global $_CMS_current_article;
	if (!is_null($_CMS_current_article)) {
		return $_CMS_current_article->getID();
	}
}

/**
 * Gets the news article title
 *
 * @return string
 */
function getNewsTitle() {
	global $_CMS_current_article;
	if (!is_null($_CMS_current_article)) {
		return $_CMS_current_article->getTitle();
	}
}

/**
 * prints the news article title
 *
 * @param string $before insert if you want to use for the breadcrumb navigation or in the html title tag
 */
function printNewsTitle($before = '') {
	if ($title = getNewsTitle()) {
		if ($before) {
			echo '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		echo html_encode($title);
	}
}

/**
 * Returns the raw title of a news article.
 *
 *
 * @return string
 */
function getBareNewsTitle() {
	return getBare(getNewsTitle());
}

function printBareNewsTitle() {
	echo html_encode(getBareNewsTitle());
}

/**
 * Returns the link (url) of the current news article.
 * or of the titlelink passed if not empty
 *
 * @param string $titlelink
 * @return string
 */
function getNewsURL($titlelink = NULL) {
	global $_CMS_current_article;

	if (empty($titlelink)) {
		$obj = $_CMS_current_article;
	} else {
		$obj = newArticle($titlelink);
	}
	if (!is_null($obj)) {
			return $obj->getLink(true);
	}
	}

/**
 * Prints the title of a news article as a full html link
 *
 * @param string $before insert what you want to be show before the titlelink.
 */
function printNewsURL($before = '') {
	if (getNewsTitle()) {
		if ($before) {
			$before = '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		echo "<a href=\"" . html_encode(getNewsURL()) . "\" title=\"" . html_encode(getBareNewsTitle()) . "\">" . $before . html_encodeTagged(getNewsTitle()) . "</a>";
	}
}

/**
 * Gets the content of a news article
 *
 * If using the CombiNews feature this returns the description for gallery items (see printNewsContent for more)
 *
 * @param int $shorten The optional length of the content for the news list for example, will override the plugin option setting if set, "" (empty) for full content (not used for image descriptions!)
 * @param string $shortenindicator The placeholder to mark the shortening (e.g."(...)"). If empty the Zenpage option for this is used.
 * @param string $readmore The text for the "read more" link. If empty the term set in Zenpage option is used.
 *
 * @return string
 */
function getNewsContent($shorten = false, $shortenindicator = NULL, $readmore = false) {
	global $_current_image, $_gallery, $_CMS_current_article, $_current_page;
	if (!$_CMS_current_article->checkAccess()) {
		return '<p>' . gettext('<em>This entry belongs to a protected category.</em>') . '</p>';
	}

	$articlecontent = $_CMS_current_article->getContent();
	if ($shorten !== false || !is_NewsArticle()) { //then we shorten the content displayed
		if (is_null($shortenindicator)) {
			$shortenindicator = SHORTENINDICATOR;
		}
		if (is_null($readmore)) {
			$readmore = get_language_string(READ_MORE);
		}
		if (!$shorten) {
			$shorten = SHORTEN_LENGTH;
		}
		$readmorelink = '<p class="readmorelink"><a href="' . html_encode($_CMS_current_article->getLink()) . '" title="' . html_encode($readmore) . '">' . html_encode($readmore) . '</a></p>';
		$articlecontent = shortenContent($articlecontent, $shorten, $shortenindicator . $readmorelink);
	}

	return $articlecontent;
}

/**
 * Prints the news article content. Note: TinyMCE used by Zenpage for news articles may already add a surrounding <p></p> to the content.
 *
 * If using the CombiNews feature this prints the thumbnail or sized image for a gallery item.
 * If using the 'CombiNews sized image' mode it shows movies directly and the description below.
 *
 * @param int $shorten $shorten The lengths of the content for the news main page for example (only for video/audio descriptions, not for normal image descriptions)
 * @param string $shortenindicator The placeholder to mark the shortening (e.g."(...)"). If empty the Zenpage option for this is used.
 * @param string $readmore The text for the "read more" link. If empty the term set in Zenpage option is used.
 */
function printNewsContent($shorten = false, $shortenindicator = NULL, $readmore = false) {
	$newscontent = getNewsContent($shorten, $shortenindicator, $readmore);
	echo html_encodeTagged($newscontent);
}

/**
 * Gets the extracontent of a news article if in single news articles view or returns FALSE
 *
 * @return string
 */
function getNewsExtraContent() {
	global $_CMS_current_article;
	if (is_News()) {
		$extracontent = $_CMS_current_article->getExtraContent();
		return $extracontent;
	} else {
		return FALSE;
	}
}

/**
 * Prints the extracontent of a news article if in single news articles view
 *
 * @return string
 */
function printNewsExtraContent() {
	echo getNewsExtraContent();
}

/**
 * Returns the text for the read more link for news articles or gallery items if in CombiNews mode
 *
 * @return string
 */
function getNewsReadMore() {
	$readmore = get_language_string(READ_MORE);
	return $readmore;
}

/**
 * Gets the author of a news article (if in Combinews mode for gallery items the owner)
 *
 * @return string
 */
function getNewsAuthor($fullname = false) {
	if (is_News()) {
		return getOwner($fullname);
	}
	return false;
}

/**
 * Prints the author of a news article
 *
 * @return string
 */
function printNewsAuthor($fullname = false) {
	if (getNewsTitle()) {
		echo html_encode(getNewsAuthor($fullname));
	}
}

/**
 * Prints the title of the currently selected news category
 *
 * @param string $before insert what you want to be show before it
 */
function printCurrentNewsCategory($before = '') {
	global $_CMS_current_category;
	if (in_context(ZENPAGE_NEWS_CATEGORY)) {
		if ($before) {
			echo '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		echo html_encode($_CMS_current_category->getTitle());
	}
}

/**
 * Gets the description of the current news category
 *
 * @return string
 */
function getNewsCategoryDesc() {
	global $_CMS_current_category;
	if (!is_null($_CMS_current_category)) {
		return $_CMS_current_category->getDesc();
	}
}

/**
 * Prints the description of the news category
 *
 */
function printNewsCategoryDesc() {
	echo html_encodeTagged(getNewsCategoryDesc());
}

/**
 * Gets the categories of the current news article
 *
 * @return array
 */
function getNewsCategories() {
	global $_CMS_current_article;
	if (!is_null($_CMS_current_article)) {
		$categories = $_CMS_current_article->getCategories();
		return $categories;
	}
	return array();
}

/**
 * Prints the categories of current article as a unordered html list
 *
 * @param string $separator A separator to be shown between the category names if you choose to style the list inline
 * @param string $class The CSS class for styling
 * @return string
 */
function printNewsCategories($separator = '', $before = '', $class = '') {
	$categories = getNewsCategories();
	$catcount = count($categories);
	if ($catcount != 0) {
		if ($before) {
			echo '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		echo "<ul class=\"$class\">\n";
		$count = 0;
		if ($separator) {
			$separator = '<span class="betweentext">' . html_encode($separator) . '</span>';
		}
		foreach ($categories as $cat) {
			$count++;
			$catobj = newCategory($cat['titlelink']);
			if ($count >= $catcount) {
				$separator = "";
			}
			echo "<li><a href=\"" . $catobj->getLink() . "\" title=\"" . html_encode($catobj->getTitle()) . "\">" . $catobj->getTitle() . '</a>' . $separator . "</li>\n";
		}
		echo "</ul>\n";
	}
}

/**
 * Gets the creation date of the current news article
 *
 * @return string
 */
function getNewsDate() {
	global $_CMS_current_article;
	if (!is_null($_CMS_current_article)) {
		$d = $_CMS_current_article->getDateTime();
		return formattedDate(DATE_FORMAT, strtotime($d));
	}
	return false;
}

/**
 * Prints the date of the current news article
 *
 * @return string
 */
function printNewsDate() {
	echo html_encode(getNewsDate());
}

/**
 * Prints the monthy news archives sorted by year
 * NOTE: This does only include news articles.
 *
 * @param string $class optional class
 * @param string $yearclass optional class for "year"
 * @param string $monthclass optional class for "month"
 * @param string $activeclass optional class for the currently active archive
 * @param bool $yearsonly If set to true the archive only shows the years with total count (Default false)
 * @param string $order 'desc' (default) or 'asc' for descending or ascending
 */
function printNewsArchive($class = 'archive', $yearclass = 'year', $monthclass = 'month', $activeclass = "archive-active", $yearsonly = false, $order = 'desc') {
	global $_CMS;
	if (!empty($class)) {
		$class = "class=\"$class\"";
	}
	if (!empty($yearclass)) {
		$yearclass = "class=\"$yearclass\"";
	}
	if (!empty($monthclass)) {
		$monthclass = "class=\"$monthclass\"";
	}
	if (!empty($activeclass)) {
		$activeclass = "class=\"$activeclass\"";
	}
	$datecount = $_CMS->getAllArticleDates($yearsonly, $order);
	$lastyear = "";
	$nr = "";
	echo "\n<ul $class>\n";
	foreach ($datecount as $key => $val) {
		$nr++;
		if ($key == '0000-00-01') {
			$year = "no date";
			$month = "";
		} else {
			$dt = strftime('%Y-%B', strtotime($key));
			$year = substr($dt, 0, 4);
			$month = substr($dt, 5);
		}
		if ($lastyear != $year) {
			$lastyear = $year;
			if (!$yearsonly) {
				if ($nr != 1) {
					echo "</ul>\n</li>\n";
				}
				echo "<li $yearclass>$year\n<ul $monthclass>\n";
			}
		}
		if ($yearsonly) {
			$datetosearch = $key;
		} else {
			$datetosearch = strftime('%Y-%B', strtotime($key));
		}
		if (getCurrentNewsArchive('plain') == $datetosearch) {
			$active = $activeclass;
		} else {
			$active = "";
		}
		if ($yearsonly) {
			echo "<li $active><a href=\"" . html_encode(getNewsArchivePath($key, 1)) . "\" title=\"" . $key . " (" . $val . ")\" rel=\"nofollow\">$key ($val)</a></li>\n";
		} else {
			echo "<li $active><a href=\"" . html_encode(getNewsArchivePath(substr($key, 0, 7), 1)) . "\" title=\"" . $month . " (" . $val . ")\" rel=\"nofollow\">$month ($val)</a></li>\n";
		}
	}
	if ($yearsonly) {
		echo "</ul>\n";
	} else {
		echo "</ul>\n</li>\n</ul>\n";
	}
}

/**
 * Gets the current select news date (year-month) or formatted
 *
 * @param string $mode "formatted" for a formatted date or "plain" for the pure year-month (for example "2008-09") archive date
 * @param string $format If $mode="formatted" how the date should be printed (see PHP's strftime() function for the requirements)
 * @return string
 */
function getCurrentNewsArchive($mode = 'formatted', $format = '%B %Y') {
	global $_post_date;
	if (in_context(ZENPAGE_NEWS_DATE)) {
		$archivedate = $_post_date;
		if ($mode == "formatted") {
			$archivedate = strtotime($archivedate);
			$archivedate = strftime($format, $archivedate);
		}
		return $archivedate;
	}
	return false;
}

/**
 * Prints the current select news date (year-month) or formatted
 *
 * @param string $before What you want to print before the archive if using in a breadcrumb navigation for example
 * @param string $mode "formatted" for a formatted date or "plain" for the pure year-month (for example "2008-09") archive date
 * @param string $format If $mode="formatted" how the date should be printed (see PHP's strftime() function for the requirements)
 * @return string
 */
function printCurrentNewsArchive($before = '', $mode = 'formatted', $format = '%B %Y') {
	if ($date = getCurrentNewsArchive($mode, $format)) {
		if ($before) {
			echo '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		echo html_encode($date);
	}
}

/**
 * Prints all news categories as a unordered html list
 *
 * @param string $newsindex How you want to call the link the main news page without a category, leave empty if you don't want to print it at all.
 * @param bool $counter TRUE or FALSE (default TRUE). If you want to show the number of articles behind the category name within brackets,
 * @param string $css_id The CSS id for the list
 * @param string $css_class_active The css class for the active menu item
 * @param bool $startlist set to true to output the UL tab
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param string $css_class CSS class of the sub level list(s)
 * @param string $$css_class_active CSS class of the sub level list(s)
 * @param string $option The mode for the menu:
 * 												"list" context sensitive toplevel plus sublevel pages,
 * 												"list-top" only top level pages,
 * 												"omit-top" only sub level pages
 * 												"list-sub" lists only the current pages direct offspring
 * @param int $limit truncation of display text
 * @return string
 */
function printAllNewsCategories($newsindex = 'All news', $counter = true, $css_id = '', $css_class_topactive = '', $startlist = true, $css_class = '', $css_class_active = '', $option = 'list', $showsubs = false, $limit = NULL) {
	printNestedMenu($option, 'allcategories', $counter, $css_id, $css_class_topactive, $css_class, $css_class_active, $newsindex, $showsubs, $startlist, $limit);
}

/* * ********************************************* */
/* News article URL functions
  /*********************************************** */

/**
 * Returns the full path to a news category
 *
 * @param string $cat The category titlelink
 *
 * @return string
 */
function getNewsCategoryURL($cat = NULL) {
	global $_CMS, $_CMS_current_category;
	if (empty($cat)) {
		$obj = $_CMS_current_category->getTitlelink();
	} else {
		$obj = newCategory($cat);
	}
	return $obj->getLink(1);
}

/**
 * Prints the full link to a news category
 *
 * @param string $before If you want to print text before the link
 * @param string $catlink The category link of a category
 *
 * @return string
 */
function printNewsCategoryURL($before = '', $catlink = '') {
	$catobj = newCategory($catlink);
	echo "<a href=\"" . html_encode($catobj->getLink()) . "\" title=\"" . html_encode($catobj->getTitle()) . "\">";
	if ($before) {
		echo '<span class="beforetext">' . html_encode($before) . '</span>';
	}
	echo html_encode($catobj->getTitle()) . "</a>";
}

/**
 * Prints the full link of the news index page (news page 1)
 *
 * @param string $name The linktext
 * @param string $before The text to appear before the link text
 */
function printNewsIndexURL($name = NULL, $before = '', $archive = NULL) {
	global $_post_date, $_gallery_page;
	if (!in_context(SEARCH_LINKED)) {
		if (is_null($name)) {
			$name = NEWS_LABEL;
		}
		$link = getNewsIndexURL();
		if ($before) {
			echo '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		if ($_gallery_page !== 'news.php' || is_NewsArticle() || is_NewsCategory()) {
			echo "<a href=\"" . html_encode($link) . "\" title=\"" . html_encode(getBare($name)) . "\">" . html_encode(getbare($name)) . "</a>";
		} else {
			echo html_encode(getbare($name));
		}
	}
}

/**
 * Returns path of news date archive
 *
 * @return string
 */
function getNewsArchivePath($date, $page) {
	$rewrite = '/' . _NEWS_ARCHIVE_ . '/' . $date . '/';
	$plain = "/index.php?p=news&date=$date";
	if ($page > 1) {
		$rewrite .= $page;
		$plain .= "&page=$page";
	}
	return npgFilters::apply('getLink', rewrite_path($rewrite, $plain), 'archive.php', $page);
}

/* * ********************************************************* */
/* News index / category / date archive pagination functions
  /********************************************************** */

function getNewsPathNav($page) {
	global $_CMS_current_category, $_post_date;
	if (in_context(ZENPAGE_NEWS_CATEGORY)) {
		return $_CMS_current_category->getLink($page);
	}
	if (in_context(ZENPAGE_NEWS_DATE)) {
		return getNewsArchivePath($_post_date, $page);
	}
	$rewrite = '/' . _NEWS_ . '/';
	$plain = 'index.php?p=news';
	if ($page > 1) {
		$rewrite .= $page;
		$plain .= '&page=' . $page;
	}
	return npgFilters::apply('getLink', rewrite_path($rewrite, $plain), 'news.php', $page);
}

/**
 * Returns the url to the previous news page
 *
 * @return string
 */
function getPrevNewsPageURL() {
	global $_current_page;
	if (hasPrevNewsPage()) {
		return getNewsPathNav($_current_page - 1);
	} else {
		return false;
	}
}

/**
 * Checks if there is a next page for news pages
 *
 * @return bool
 */
function hasPrevNewsPage() {
	global $_current_page;
	return $_current_page > 1;
}

/**
 * Prints the link to the previous news page
 *
 * @param string $prev The linktext
 * @param string $class The CSS class for the disabled link
 *
 * @return string
 */
function printPrevNewsPageLink($prev = '« prev', $class = 'disabledlink') {
	global $_CMS, $_current_page;
	if ($link = getPrevNewsPageURL()) {
		echo "<a href='" . html_encode($link) . "' title='" . gettext("Prev page") . " " . ($_current_page - 1) . "' >" . html_encode($prev) . "</a>\n";
	} else {
		echo "<span class=\"$class\">" . html_encode($prev) . "</span>\n";
	}
}

/**
 * Returns the url to the next news page
 *
 * @return string
 */
function getNextNewsPageURL() {
	global $_current_page;
	if (hasNextNewsPage()) {
		return getNewsPathNav($_current_page + 1);
	} else {
		return false;
	}
}

/**
 * Checks if there is a next news page
 *
 * @global object $_CMS
 * @global int $_current_page
 * @return bool
 */
function hasNextNewsPage() {
	global $_CMS, $_current_page;
	$total = ceil($_CMS->getTotalArticles() / ARTICLES_PER_PAGE);
	return $_current_page < $total;
}

/**
 * Prints the link to the next news page
 *
 * @param string $next The linktext
 * @param string $class The CSS class for the disabled link
 *
 * @return string
 */
function printNextNewsPageLink($next = 'next »', $class = 'disabledlink') {
	global $_current_page;
	if (getNextNewsPageURL()) {
		echo "<a href='" . getNextNewsPageURL() . "' title='" . gettext("Next page") . " " . ($_current_page + 1) . "'>" . html_encode($next) . "</a>\n";
	} else {
		echo "<span class=\"$class\">" . html_encode($next) . "</span>\n";
	}
}

/**
 * Prints the page number list for news page navigation
 *
 * @param string $class The CSS class for the disabled link
 *
 * @return string
 */
function printNewsPageList($class = 'pagelist') {
	printNewsPageListWithNav("", "", false, $class, true);
}

/**
 * Prints the full news page navigation with prev/next links and the page number list
 *
 * @param string $next The next page link text
 * @param string $prev The prev page link text
 * @param bool $nextprev If the prev/next links should be printed
 * @param string $class The CSS class for the disabled link
 * @param bool $firstlast Add links to the first and last pages of you gallery
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 *
 * @return string
 */
function printNewsPageListWithNav($next, $prev, $nextprev = true, $class = 'pagelist', $firstlast = true, $navlen = 9) {
	global $_CMS, $_current_page;
	$total = ceil($_CMS->getTotalArticles() / ARTICLES_PER_PAGE);
	if ($total > 1) {
		if ($navlen == 0) {
					$navlen = $total;
		}
		$extralinks = 2;
		if ($firstlast) {
					$extralinks = $extralinks + 2;
		}
		$len = floor(($navlen - $extralinks) / 2);
		$j = max(round($extralinks / 2), min($_current_page - $len - (2 - round($extralinks / 2)), $total - $navlen + $extralinks - 1));
		$ilim = min($total, max($navlen - round($extralinks / 2), $_current_page + floor($len)));
		$k1 = round(($j - 2) / 2) + 1;
		$k2 = $total - round(($total - $ilim) / 2);
		echo "<ul class=\"$class\">\n";
		if ($nextprev) {
			echo "<li class=\"prev\">";
			printPrevNewsPageLink($prev);
			echo "</li>\n";
		}
		if ($firstlast) {
			echo '<li class = "' . ($_current_page == 1 ? 'current' : 'first') . '">';
			if ($_current_page == 1) {
				echo "1";
			} else {
				echo '<a href = "' . html_encode(getNewsPathNav(1)) . '" title = "' . gettext("Page") . ' 1">1</a>';
			}
			echo "</li>\n";
			if ($j > 2) {
				echo "<li>";
				$linktext = ($j - 1 > 2) ? '...' : $k1;
				echo '<a href = "' . html_encode(getNewsPathNav($k1)) . '" title = "' . sprintf(ngettext('Page %u', 'Page %u', $k1), $k1) . '">' . $linktext . '</a>';
				echo "</li>\n";
			}
		}
		for ($i = $j; $i <= $ilim; $i++) {
			echo "<li" . (($i == $_current_page) ? " class=\"current\"" : "") . ">";
			if ($i == $_current_page) {
				echo $i;
			} else {
				echo '<a href = "' . html_encode(getNewsPathNav($i)) . '" title = "' . sprintf(ngettext('Page %1$u', 'Page %1$u', $i), $i) . '">' . $i . '</a>';
			}
			echo "</li>\n";
		}
		if ($i < $total) {
			echo "<li>";
			$linktext = ($total - $i > 1) ? '...' : $k2;
			echo '<a href = "' . html_encode(getNewsPathNav($k2)) . '" title = "' . sprintf(ngettext('Page %u', 'Page %u', $k2), $k2) . '">' . $linktext . '</a>';
			echo "</li>\n";
		}
		if ($firstlast && $i <= $total) {
			echo "\n  <li class=\"last\">";
			if ($_current_page == $total) {
				echo $total;
			} else {
				echo '<a href = "' . html_encode(getNewsPathNav($total)) . '" title = "' . sprintf(ngettext('Page {%u}', 'Page {%u}', $total), $total) . '">' . $total . '</a>';
			}
			echo "</li>\n";
		}
		if ($nextprev) {
			echo '<li class = "next">';
			printNextNewsPageLink($next);
			echo "</li>\n";
		}
		echo "</ul>\n";
	}
}

function getTotalNewsPages() {
	global $_CMS;
	return ceil($_CMS->getTotalArticles() / ARTICLES_PER_PAGE);
}

/* * ********************************************************************* */
/* Single news article pagination functions (previous and next article)
  /*********************************************************************** */

/**
 * Returns the title and the titlelink of the next article in single news article pagination as an array
 * Returns false if there is none (or option is empty)
 *
 * @return mixed
 */
function getNextNewsURL() {
	global $_CMS_current_article;
	if (is_object($_CMS_current_article)) {
		$article = $_CMS_current_article->getNextArticle();
		if ($article) {
					return array("link" => $article->getLink(true), "title" => $article->getTitle());
		}
	}
	return false;
}

/**
 * Returns the title and the titlelink of the previous article in single news article pagination as an array
 * Returns false if there is none (or option is empty)
 *
 * NOTE: This is not available if using the CombiNews feature
 *
 * @return mixed
 */
function getPrevNewsURL() {
	global $_CMS_current_article;
	if (is_object($_CMS_current_article)) {
		$article = $_CMS_current_article->getPrevArticle();
		if ($article) {
					return array("link" => $article->getLink(true), "title" => $article->getTitle());
		}
	}
	return false;
}

/**
 * Prints the link of the next article in single news article pagination if available
 *
 * NOTE: This is not available if using the CombiNews feature
 *
 * @param string $next If you want to show something with the title of the article like a symbol
 * @return string
 */
function printNextNewsLink($next = " »") {
	$article_url = getNextNewsURL();
	if ($article_url && array_key_exists('link', $article_url) && $article_url['link'] != "") {
		echo "<a href=\"" . html_encode($article_url['link']) . "\" title=\"" . html_encode(getBare($article_url['title'])) . "\">" . $article_url['title'] . "</a> " . html_encode($next);
	}
}

/**
 * Prints the link of the previous article in single news article pagination if available
 *
 * NOTE: This is not available if using the CombiNews feature
 *
 * @param string $next If you want to show something with the title of the article like a symbol
 * @return string
 */
function printPrevNewsLink($prev = "« ") {
	$article_url = getPrevNewsURL();
	if ($article_url && array_key_exists('link', $article_url) && $article_url['link'] != "") {
		echo html_encode($prev) . " <a href=\"" . html_encode($article_url['link']) . "\" title=\"" . html_encode(getBare($article_url['title'])) . "\">" . $article_url['title'] . "</a>";
	}
}

/* * ******************************************************* */
/* Functions - shared by Pages and News articles
  /********************************************************* */

/**
 * Gets the statistic for pages, news articles or categories as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages, articles  and categories
 * 											 "news" for news articles
 * 											 "categories" for news categories
 * 											 "pages" for pages
 * @param string $mode "popular" most viewed for pages, news articles and categories
 * 										 "mostrated" for news articles and pages
 * 										 "toprated" for news articles and pages
 * 										 "random" for pages and news articles
 * @param string $sortdirection "asc" for ascending otherwise descending (default)
 * @return array
 */
function getZenpageStatistic($number = 10, $option = "all", $mode = "popular", $sortdirection = 'desc') {
	global $_CMS, $_CMS_current_article;
	$sortdir = $sortdirection && strtolower($sortdirection) != 'asc';
	$statsarticles = array();
	$statscats = array();
	$statspages = array();
	if ($option == "all" || $option == "news") {
		$articles = $_CMS->getArticles($number, NULL, true, $mode, $sortdir, false);
		$counter = "";
		$statsarticles = array();
		foreach ($articles as $article) {
			$counter++;
			$obj = newArticle($article['titlelink']);
			$statsarticles[$counter] = array(
					"id" => $obj->getID(),
					"title" => $obj->getTitle(),
					"titlelink" => $article['titlelink'],
					"hitcounter" => $obj->getHitcounter(),
					"total_votes" => $obj->getTotal_votes(),
					"rating" => $obj->getRating(),
					"content" => $obj->getContent(),
					"date" => $obj->getDateTime(),
					"pubdate" => $obj->getPublishDate(),
					"type" => "News"
			);
		}
		$stats = $statsarticles;
	}
	if (($option == "all" || $option == "categories") && $mode != "mostrated" && $mode != "toprated") {
		$categories = $_CMS->getAllCategories(true, $mode, $sortdir);
		$counter = "";
		$statscats = array();
		foreach ($categories as $cat) {
			$counter++;
			$statscats[$counter] = array(
					"id" => $cat['id'],
					"title" => html_encode(get_language_string($cat['title'])),
					"titlelink" => getNewsCategoryURL($cat['titlelink']),
					"hitcounter" => $cat['hitcounter'],
					"total_votes" => "",
					"rating" => "",
					"content" => '',
					"date" => '',
					"type" => "Category"
			);
		}
		$stats = $statscats;
	}
	if ($option == "all" || $option == "pages") {
		$pages = $_CMS->getPages(NULL, false, $number, $mode, $sortdir);
		$counter = "";
		$statspages = array();
		foreach ($pages as $page) {
			$counter++;
			$pageobj = newPage($page['titlelink']);
			$statspages[$counter] = array(
					"id" => $pageobj->getID(),
					"title" => $pageobj->getTitle(),
					"titlelink" => $page['titlelink'],
					"hitcounter" => $pageobj->getHitcounter(),
					"total_votes" => $pageobj->get('total_votes'),
					"rating" => $pageobj->get('rating'),
					"content" => $pageobj->getContent(),
					"date" => $pageobj->getDateTime(),
					"type" => "Page"
			);
		}
		$stats = $statspages;
	}
	if ($option == "all") {
		$stats = array_merge($statsarticles, $statscats, $statspages);
		if ($mode == 'random') {
			shuffle($stats);
		} else {
			switch ($sortdir) {
				case 'asc':
					$desc = false;
					break;
				case 'desc':
					$desc = true;
					break;
			}
			$stats = sortMultiArray($stats, $mode, $desc);
		}
	}
	return $stats;
}

/**
 * Prints the statistics Zenpage items as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages and articles
 * 											 "news" for news articles
 * 											 "pages" for pages
 * @param string $mode "popular" most viewed for pages, news articles and categories
 * 										 "mostrated" for news articles and pages
 * 										 "toprated" for news articles and pages
 * 										 "random" for pages, news articles and categories
 * @param bool $showstats if the value should be shown
 * @param bool $showtype if the type should be shown
 * @param bool $showdate if the date should be shown (news articles and pages only)
 * @param bool $showcontent if the content should be shown (news articles and pages only)
 * @param bool $contentlength The shortened lenght of the content
 * @param string $sortdir "asc" for ascending or "desc" for descending (default)
 */
function printZenpageStatistic($number = 10, $option = "all", $mode = "popular", $showstats = true, $showtype = true, $showdate = true, $showcontent = true, $contentlength = 40, $sortdir = 'desc') {
	$stats = getZenpageStatistic($number, $option, $mode);
	$contentlength = sanitize_numeric($contentlength);
	switch ($mode) {
		case 'popular':
			$cssid = "'zenpagemostpopular'";
			break;
		case 'mostrated':
			$cssid = "'zenpagemostrated'";
			break;
		case 'toprated':
			$cssid = "'zenpagetoprated'";
			break;
		case 'random':
			$cssid = "'zenpagerandom'";
			break;
	}
	echo "<ul id=$cssid>";
	foreach ($stats as $item) {
		switch ($mode) {
			case 'popular':
				$statsvalue = $item['hitcounter'];
				break;
			case 'mostrated':
				$statsvalue = $item['total_votes'];
				break;
			case 'toprated':
				$statsvalue = $item['rating'];
				break;
		}
		switch ($item['type']) {
			case 'Page':
				$titlelink = html_encode(getPageURL($item['titlelink']));
			case 'News':
				$titlelink = html_encode(getNewsURL($item['titlelink']));
				break;
			case 'Category':
				$titlelink = html_encode(getNewsCategoryURL($item['titlelink']));
				break;
		}
		echo '<li><a href = "' . $titlelink . '" title = "' . html_encode(getBare($item['title'])) . '"><h3>' . $item['title'];
		echo '<small>';
		if ($showtype) {
			echo ' [' . $item['type'] . ']';
		}
		if ($showstats && ($item['type'] != 'Category' && $mode != 'mostrated' && $mode != 'toprated')) {
			echo ' (' . $statsvalue . ')';
		}
		echo '</small>';
		echo '</h3></a>';
		if ($showdate && $item['type'] != 'Category') {
			echo "<p>" . formattedDate(DATE_FORMAT, strtotime($item['date'])) . "</p>";
		}
		if ($showcontent && $item['type'] != 'Category') {
			echo '<p>' . truncate_string(getBare($item['content']), $contentlength) . '</p>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Prints the most popular pages, news articles and categories as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages and articles
 * 											 "news" for news articles
 * 											 "pages" for pages
 * @param bool $showstats if the value should be shown
 * @param bool $showtype if the type should be shown
 * @param bool $showdate if the date should be shown (news articles and pages only)
 * @param bool $showcontent if the content should be shown (news articles and pages only)
 * @param bool $contentlength The shortened lenght of the content
 */
function printMostPopularItems($number = 10, $option = "all", $showstats = true, $showtype = true, $showdate = true, $showcontent = true, $contentlength = 40) {
	printZenpageStatistic($number, $option, "popular", $showstats, $showtype, $showdate, $showcontent, $contentlength);
}

/**
 * Prints the most rated pages and news articles as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages and articles
 * 											 "news" for news articles
 * 											 "pages" for pages
 * @param bool $showstats if the value should be shown
 * @param bool $showtype if the type should be shown
 * @param bool $showdate if the date should be shown (news articles and pages only)
 * @param bool $showcontent if the content should be shown (news articles and pages only)
 * @param bool $contentlength The shortened lenght of the content
 */
function printMostRatedItems($number = 10, $option = "all", $showstats = true, $showtype = true, $showdate = true, $showcontent = true, $contentlength = 40) {
	printZenpageStatistic($number, $option, "mostrated", $showstats, $showtype, $showdate, $showcontent, $contentlength);
}

/**
 * Prints the top rated pages and news articles as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages and articles
 * 											 "news" for news articles
 * 											 "pages" for pages
 * @param bool $showstats if the value should be shown
 * @param bool $showtype if the type should be shown
 * @param bool $showdate if the date should be shown (news articles and pages only)
 * @param bool $showcontent if the content should be shown (news articles and pages only)
 * @param bool $contentlength The shortened lenght of the content
 */
function printTopRatedItems($number = 10, $option = "all", $showstats = true, $showtype = true, $showdate = true, $showcontent = true, $contentlength = 40) {
	printZenpageStatistic($number, $option, "toprated", $showstats, $showtype, $showdate, $showcontent, $contentlength);
}

/**
 * Prints a context sensitive menu of all pages as a unordered html list
 *
 * @param string $option The mode for the menu:
 * 												"list" context sensitive toplevel plus sublevel pages,
 * 												"list-top" only top level pages,
 * 												"omit-top" only sub level pages
 * 												"list-sub" lists only the current pages direct offspring
 * @param string $mode 'pages' or 'categories'
 * @param bool $counter Only $mode = 'categories': Count the articles in each category
 * @param string $css_id CSS id of the top level list
 * @param string $css_class_topactive class of the active item in the top level list
 * @param string $css_class CSS class of the sub level list(s)
 * @param string $$css_class_active CSS class of the sub level list(s)
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" (default) if you don't use it, it is not printed then.
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param bool $startlist set to true to output the UL tab (false automatically if you use 'omit-top' or 'list-sub')
 * @param int $limit truncation limit display strings
 * @return string
 */
function printNestedMenu($option = 'list', $mode = NULL, $counter = TRUE, $css_id = NULL, $css_class_topactive = NULL, $css_class = NULL, $css_class_active = NULL, $indexname = NULL, $showsubs = 0, $startlist = true, $limit = NULL) {
	global $_CMS, $_gallery_page, $_CMS_current_article, $_CMS_current_page, $_CMS_current_category;
	if (is_null($limit)) {
		$limit = MENU_TRUNCATE_STRING;
	}

	if (is_null($css_id)) {
		switch ($mode) {
			case 'pages':
				$css_id = 'menu_pages';
				break;
			case 'categories':
			case 'allcategories':
				$css_id = 'menu_categories';
				break;
		}
	}
	if (is_null($css_class_topactive)) {
		$css_class_topactive = 'menu_topactive';
	}
	if (is_null($css_class)) {
		$css_class = 'submenu';
	}
	if (is_null($css_class_active)) {
		$css_class_active = 'menu-active';
	}
	if ($showsubs === true) {
			$showsubs = 9999999999;
	}
	switch ($mode) {
		case 'pages':
			$items = $_CMS->getPages();
			$currentitem_id = getPageID();
			if (is_object($_CMS_current_page)) {
				$currentitem_parentid = $_CMS_current_page->getParentID();
			} else {
				$currentitem_parentid = NULL;
			}
			$currentitem_sortorder = getPageSortorder();
			break;
		case 'categories':
		case 'allcategories':
			$articleCategories = array();
			if ($_CMS_current_article) { // should expand all categories it is a member of
				foreach ($_CMS_current_article->getCategories() as $catMember) {
					$cat = getItemByID('news_categories', $catMember['id']);
					$parentid = $catMember['parentid'];
					$articleCategories[$catMember['titlelink']] = $catMember['cat_id'];
					while ($parentid) {
						$cat = getItemByID('news_categories', $parentid);
						$articleCategories[@$cat->getTitleLink()] = $parentid;
						$parentid = @$cat->getParentID();
					}
				}
			}
			$items = $_CMS->getAllCategories();
			if (is_object($_CMS_current_category)) {
				$currentitem_sortorder = $_CMS_current_category->getSortOrder();
				$currentitem_id = $_CMS_current_category->getID();
				$currentitem_parentid = $_CMS_current_category->getParentID();
			} else {
				$currentitem_sortorder = NULL;
				$currentitem_id = NULL;
				$currentitem_parentid = NULL;
			}
			break;
	}

	// don't highlight current pages or foldout if in search mode as next_page() sets page context
	if (in_context(NPG_SEARCH) && $mode == 'pages') { // categories are not searched
		rem_context(ZENPAGE_PAGE);
	}

	if (0 == count($items) + (int) ($mode == 'allcategories')) {
			return;
	}
	// nothing to do
	$startlist = $startlist && !($option == 'omit-top' || $option == 'list-sub');
	if ($startlist) {
			echo '<ul id="' . $css_id . '">';
	}
	// if index link and if if with count
	if (!empty($indexname)) {
		if ($limit) {
			$display = shortenContent($indexname, $limit, MENU_TRUNCATE_INDICATOR);
		} else {
			$display = $indexname;
		}
		switch ($mode) {
			case 'pages':
				if ($_gallery_page == "index.php") {
					echo '<li class="' . $css_class_topactive . '">' . html_encode($display) . '</li>';
				} else {
					echo "<li><a href='" . html_encode(getGalleryIndexURL()) . "' title='" . html_encode($indexname) . "'>" . html_encode($display) . "</a></li>";
				}
				break;
			case 'categories':
			case 'allcategories':
				if (($_gallery_page == "news.php") && !is_NewsCategory() && !is_NewsArchive() && !is_NewsArticle()) {
					echo '<li class="' . $css_class_topactive . '">' . html_encode($display);
				} else {
					echo "<li><a href=\"" . html_encode(getNewsIndexURL()) . "\" title=\"" . html_encode($indexname) . "\">" . html_encode($display) . "</a>";
				}
				if ($counter) {
					if (in_context(ZENPAGE_NEWS_CATEGORY) && $mode == 'categories') {
						$totalcount = count($_CMS_current_category->getArticles(0));
					} else {
						save_context();
						rem_context(ZENPAGE_NEWS_DATE);
						$totalcount = count($_CMS->getArticles(0));
						restore_context();
					}
					echo ' <span style="white-space:nowrap;"><small>(' . sprintf(ngettext('%u article', '%u articles', $totalcount), $totalcount) . ')</small></span>';
				}
				echo "</li>\n";
				break;
		}
	}
	$baseindent = max(1, count(explode("-", $currentitem_sortorder)));
	$indent = 1;
	$open = array($indent => 0);
	$parents = array(NULL);
	$order = explode('-', $currentitem_sortorder);
	$mylevel = count($order);
	$myparentsort = array_shift($order);
	for ($c = 0; $c <= $mylevel; $c++) {
		$parents[$c] = NULL;
	}
	foreach ($items as $item) {
		$override = false;
		$password_class = '';
		switch ($mode) {
			case 'pages':
				$catcount = 1; //	so page items all show.
				$pageobj = newPage($item['titlelink']);
				$itemtitle = $pageobj->getTitle();
				$itemsortorder = $pageobj->getSortOrder();
				$itemid = $pageobj->getID();
				$itemparentid = $pageobj->getParentID();
				$itemtitlelink = $pageobj->getTitlelink();
				$itemurl = $pageobj->getLink();
				$count = '';
				if ($pageobj->isProtected()) {
					$password_class = ' has_password';
				}
				break;
			case 'categories':
			case 'allcategories':
				$catobj = newCategory($item['titlelink']);
				$itemtitle = $catobj->getTitle();
				$itemsortorder = $catobj->getSortOrder();
				$itemid = $catobj->getID();
				$override = in_array($itemid, $articleCategories); // to show news article category list
				$itemparentid = $catobj->getParentID();
				$itemtitlelink = $catobj->getTitlelink();
				$itemurl = $catobj->getLink();
				$catcount = count($catobj->getArticles());
				if ($counter) {
					$count = ' <span style="white-space:nowrap;"><small>(' . sprintf(ngettext('%u article', '%u articles', $catcount), $catcount) . ')</small></span>';
				} else {
					$count = '';
				}
				if ($catobj->isProtected()) {
					$password_class = ' has_password';
				}
				break;
		}
		if ($catcount) {
			$level = max(1, count(explode('-', $itemsortorder)));

			$process = (($level <= $showsubs && $option == "list") // user wants all the pages whose level is <= to the parameter
							|| ($option == 'list' || $option == 'list-top') && $level == 1 // show the top level
							|| (($option == 'list' || ($option == 'omit-top' && $level > 1)) && (($itemid == $currentitem_id) // current page
							|| ($itemparentid == $currentitem_id) // offspring of current page
							|| ($level < $mylevel && $level > 1 && (strpos($itemsortorder, $myparentsort) === 0) || $override)// direct ancestor
							|| (($level == $mylevel) && ($currentitem_parentid == $itemparentid)) // sibling
							)
							) || ($option == 'list-sub' && ($itemparentid == $currentitem_id) // offspring of the current page
							)
							);

			if ($process) {
				if ($level > $indent) {
					echo "\n" . str_pad("\t", $indent, "\t") . '<ul class="' . $css_class . '">' . "\n";
					$indent++;
					$parents[$indent] = NULL;
					$open[$indent] = 0;
				} else if ($level < $indent) {
					$parents[$indent] = NULL;
					while ($indent > $level) {
						if ($open[$indent]) {
							$open[$indent]--;
							echo "</li>\n";
						}
						$indent--;
						echo str_pad("\t", $indent, "\t") . "</ul>\n";
					}
				} else { // level == indent, have not changed
					if ($open[$indent]) { // level = indent
						echo str_pad("\t", $indent, "\t") . "</li>\n";
						$open[$indent]--;
					} else {
						echo "\n";
					}
				}
				if ($open[$indent]) { // close an open LI if it exists
					echo "</li>\n";
					$open[$indent]--;
				}
				echo str_pad("\t", $indent - 1, "\t");
				$open[$indent]++;
				$parents[$indent] = $itemid;
				if ($level == 1) { // top level
					$class = $css_class_topactive . $password_class;
				} else {
					$class = $css_class_active . $password_class;
				}
				if (!is_null($_CMS_current_page)) {
					$gettitle = $_CMS_current_page->getTitle();
					$getname = $_CMS_current_page->getTitlelink();
				} else if (!is_null($_CMS_current_category)) {
					$gettitle = $_CMS_current_category->getTitle();
					$getname = $_CMS_current_category->getTitlelink();
				} else {
					$gettitle = '';
					$getname = '';
				}
				$current = "";
				if ($itemtitlelink == $getname && !in_context(NPG_SEARCH)) {
					switch ($mode) {
						case 'pages':
							if ($_gallery_page == 'pages.php') {
								$current = $class;
							}
							break;
						case 'categories':
						case 'allcategories':
							if ($_gallery_page == 'news.php') {
								$current = $class;
							}
							break;
					}
				}
				if (empty($current)) {
					$current = trim($password_class);
				}
				if (!empty($current)) {
					$current = ' class="' . $current . '"';
				}
				if ($limit) {
					$itemtitle = shortenContent($itemtitle, $limit, MENU_TRUNCATE_INDICATOR);
				}
				echo '<li><a' . $current . ' href="' . html_encode($itemurl) . '" title="' . html_encode(getBare($itemtitle)) . '">' . html_encode($itemtitle) . '</a>' . $count;
			}
		}
	}
	// cleanup any hanging list elements
	while ($indent > 1) {
		if ($open[$indent]) {
			echo "</li>\n";
			$open[$indent]--;
		}
		$indent--;
		echo str_pad("\t", $indent, "\t") . "</ul>";
	}
	if ($open[$indent]) {
		echo "</li>\n";
		$open[$indent]--;
	} else {
		echo "\n";
	}
	if ($startlist) {
			echo "</ul>\n";
	}
	}

/**
 * Prints the parent items breadcrumb navigation for pages or categories
 *
 * @param string $before Text to place before the breadcrumb item
 * @param string $after Text to place after the breadcrumb item
 */
function printZenpageItemsBreadcrumb($before = NULL, $after = NULL) {
	global $_CMS_current_page, $_CMS_current_category, $_current_search;
	if (in_context(SEARCH_LINKED)) {
		$page = $_current_search->page;
		$searchwords = $_current_search->getSearchWords();
		$searchdate = $_current_search->getSearchDate();
		$searchfields = $_current_search->getSearchFields(true);
		if (is_NewsCategory()) {
			$search_obj_list = array('news' => $_current_search->getCategoryList());
		} else {
			$search_obj_list = NULL;
		}
		$searchpagepath = getSearchURL($searchwords, $searchdate, $searchfields, $page, $search_obj_list);
		if (empty($searchdate)) {
			$title = gettext("Return to search");
			$text = gettext("Search");
		} else {
			$title = gettext("Return to archive");
			$text = gettext("Archive");
		}
		if ($before) {
			echo '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		echo"<a href='" . $searchpagepath . "' title='" . html_encode($title) . "'>" . html_encode($text) . "</a>";
		if ($after) {
			echo '<span class="aftertext">' . html_encode($after) . '</span>';
		}
	} else {
		if (is_Pages()) {
			//$parentid = $_CMS_current_page->getParentID();
			$parentitems = $_CMS_current_page->getParents();
			$new = 'newPage';
		} else if (is_NewsCategory()) {
			//$parentid = $_CMS_current_category->getParentID();
			$parentitems = $_CMS_current_category->getParents();
			$new = 'newCategory';
		} else {
			$parentitems = array();
		}
		foreach ($parentitems as $item) {

			$obj = $new($item);

			if ($before) {
				echo '<span class="beforetext">' . html_encode($before) . '</span>';
			}
			echo"<a href='" . html_encode($obj->getLink()) . "'>" . html_encode($obj->getTitle()) . "</a>";
			if ($after) {
				echo '<span class="aftertext">' . html_encode($after) . '</span>';
			}
		}
	}
}

/* * ********************************************* */
/* Pages functions
  /*********************************************** */
$_CMS_pagelist = NULL;

/**
 *
 * Returns true if there are any pages to show
 *
 * @return bool
 */
function hasPages() {
	return getNumPages();
}

/**
 * Returns a count of the pages
 *
 * If in search context, the count is the number of items found.
 * If in a page context, the count is the number of sub-pages of the current page.
 * Otherwise it is the total number of pages.
 *
 * @param bool $total return the count of all pages
 *
 * @return int
 */
function getNumPages($total = false) {
	global $_CMS, $_CMS_pagelist, $_current_search, $_CMS_current_page;
	if ($_CMS->pages_enabled) {
		$addquery = '';
		if (!$total) {
			if (in_context(NPG_SEARCH)) {
				$_CMS_pagelist = $_current_search->getPages();
				return count($_CMS_pagelist);
			} else if (in_context(ZENPAGE_PAGE)) {
				if (!npg_loggedin(ADMIN_RIGHTS | ZENPAGE_PAGES_RIGHTS)) {
					$addquery = ' AND `show`=1';
				}
				return db_count('pages', 'WHERE parentid=' . $_CMS_current_page->getID() . $addquery);
			}
		}
		if (!npg_loggedin(ADMIN_RIGHTS | ZENPAGE_PAGES_RIGHTS)) {
			$addquery = ' WHERE `show`=1';
		}
		return db_count('pages', $addquery);
	}
	return NULL;
}

/**
 * Returns pages from the current page object/search/or parent pages based on context
 * Updates $_CMS_curent_page and returns true if there is another page to be delivered
 * @return boolean
 */
function next_page() {
	global $_CMS, $_next_pagelist, $_current_search, $_CMS_current_page, $_CMS_current_page_restore;

	if ($_CMS->pages_enabled && is_null($_next_pagelist)) {
		if (in_context(NPG_SEARCH)) {
			$_next_pagelist = $_current_search->getPages(NULL, false, NULL, NULL, NULL);
		} else if (in_context(ZENPAGE_PAGE)) {
			if (!is_null($_CMS_current_page)) {
				$_next_pagelist = $_CMS_current_page->getPages(NULL, false, NULL, NULL, NULL);
			}
		} else {
			$_next_pagelist = $_CMS->getPages(NULL, true, NULL, NULL, NULL);
		}
		save_context();
		add_context(ZENPAGE_PAGE);
		$_CMS_current_page_restore = $_CMS_current_page;
	}
	while (!empty($_next_pagelist)) {
		$page = newPage(array_shift($_next_pagelist));
		if ((npg_loggedin() && $page->isMyItem(LIST_RIGHTS)) || $page->checkForGuest()) {
			$_CMS_current_page = $page;
			return npgFilters::apply('next_object_loop', true, $_CMS_current_page);
		}
	}
	$_next_pagelist = NULL;
	$_CMS_current_page = $_CMS_current_page_restore;
	restore_context();
	return npgFilters::apply('next_object_loop', false, $_CMS_current_page);
}

/**
 * Returns title of a page
 *
 * @return string
 */
function getPageTitle() {
	global $_CMS_current_page;
	if (!is_null($_CMS_current_page)) {
		return $_CMS_current_page->getTitle();
	}
}

/**
 * Prints the title of a page
 *
 * @return string
 */
function printPageTitle($before = NULL) {
	if ($title = getPageTitle()) {
		if ($before) {
			echo '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		echo html_encode($title);
	}
}

/**
 * Returns the raw title of a page.
 *
 * @return string
 */
function getBarePageTitle() {
	return getBare(getPageTitle());
}

/**
 * prints the raw title of a page.
 *
 * @return string
 */
function printBarePageTitle() {
	echo html_encode(getBarePageTitle());
}

/**
 * Returns titlelink of a page
 *
 * @return string
 */
function getPageTitleLink() {
	global $_CMS_current_page;
	if (is_Pages()) {
		return $_CMS_current_page->getTitlelink();
	}
}

/**
 * Prints titlelink of a page
 * !!!!!!!!!!NOT THE URL TO THE PAGE!!!!!!!!!!!!!
 *
 * @return string
 */
function printPageTitleLink() {
	global $_CMS_current_page;
	echo html_encode(getPageURL(getPageTitleLink()));
}

/**
 * Returns the id of a page
 *
 * @return int
 */
function getPageID() {
	global $_CMS_current_page;
	if (is_Pages()) {
		return $_CMS_current_page->getID();
	}
}

/**
 * Prints the id of a page
 *
 * @return string
 */
function printPageID() {
	echo getPageID();
}

/**
 * Returns the id of the parent page of a page
 *
 * @return int
 */
function getPageParentID() {
	global $_CMS_current_page;
	if (is_Pages()) {
		return $_CMS_current_page->getParentid();
	}
}

/**
 * Returns the creation date of a page
 *
 * @return string
 */
function getPageDate() {
	global $_CMS_current_page;
	if (!is_null($_CMS_current_page)) {
		$d = $_CMS_current_page->getDatetime();
		return formattedDate(DATE_FORMAT, strtotime($d));
	}
	return false;
}

/**
 * Prints the creation date of a page
 *
 * @return string
 */
function printPageDate() {
	echo html_encode(getPageDate());
}

/**
 * Returns the last change date of a page if available
 *
 * @return string
 */
function getPageLastChangeDate() {
	global $_CMS_current_page;
	if (!is_null($_CMS_current_page)) {
		$d = $_CMS_current_page->getLastchange();
		return formattedDate(DATE_FORMAT, strtotime($d));
	}
	return false;
}

/**
 * Prints the last change date of a page
 *
 * @param string $before The text you want to show before the link
 * @return string
 */
function printPageLastChangeDate($before) {
	echo html_encode($before . getPageLastChangeDate());
}

/**
 * Returns page content either of the current page or if requested by titlelink directly. If not both return false
 * Set the titlelink of a page to call a specific even un-published page ($published = false) as a gallery description or on another custom page for example
 *
 * @param string $titlelink the titlelink of the page to print the content from
 * @param bool $published If titlelink is set, set this to false if you want to call an un-published page's content. True is default
 *
 * @return mixed
 */
function getPageContent($titlelink = NULL, $published = true) {
	global $_CMS_current_page;
	$page = NULL;
	if (empty($titlelink)) {
		if (is_Pages()) {
			$page = $_CMS_current_page;
			$published = false; //if you got to the page you must have had a link or the appropriate rights
		}
	} else {
		$page = newPage($titlelink);
	}
	if ($page && (!$published || $page->getShow() || $page->isMyItem(LIST_RIGHTS))) {
		return $page->getContent();
	}
	return false;
}

/**
 * Print page content either of the current page or if requested by titlelink directly. If not both return false
 * Set the titlelink of a page to call a specific even un-published page ($published = false) as a gallery description or on another custom page for example
 *
 * @param string $titlelink the titlelink of the page to print the content from
 * @param bool $published If titlelink is set, set this to false if you want to call an un-published page's content. True is default
 * @return mixed
 */
function printPageContent($titlelink = NULL, $published = true) {
	echo html_encodeTagged(getPageContent($titlelink, $published));
}

/**
 * Returns page extra content either of the current page or if requested by titlelink directly. If not both return false
 * Set the titlelink of a page to call a specific even un-published page ($published = false) as a gallery description or on another custom page for example
 *
 * @param string $titlelink the titlelink of the page to print the content from
 * @param bool $published If titlelink is set, set this to false if you want to call an un-published page's extra content. True is default
 * @return mixed
 */
function getPageExtraContent($titlelink = '', $published = true) {
	global $_CMS_current_page;
	$page = NULL;
	if (empty($titlelink)) {
		if (is_Pages()) {
			$page = $_CMS_current_page;
			$published = false; //if you got to the page you must have had a link or the appropriate rights
		}
	} else {
		$page = newPage($titlelink);
	}
	if ($page && (!$published || $page->checkAccess())) {
		return $page->getExtracontent();
	}
	return false;
}

/**
 * Prints page extra content if on a page either of the current page or if requested by titlelink directly. If not both return false
 * Set the titlelink of a page to call a specific even un-published page ($published = false) as a gallery description or on another custom page for example
 *
 * @param string $titlelink the titlelink of the page to print the content from
 * @param bool $published If titlelink is set, set this to false if you want to call an un-published page's extra content. True is default
 * @return mixed
 */
function printPageExtraContent($titlelink = NULL, $published = true) {
	echo getPageExtraContent($titlelink, $published);
}

/**
 * Returns the author of a page
 *
 * @param bool $fullname True if you want to get the full name if set, false if you want the login/screenname
 *
 * @return string
 */
function getPageAuthor($fullname = false) {
	if (is_Pages()) {
		return getOwner($fullname);
	}
	return false;
}

/**
 * Prints the author of a page
 *
 * @param bool $fullname True if you want to get the full name if set, false if you want the login/screenname
 * @return string
 */
function printPageAuthor($fullname = false) {
	if (getNewsTitle()) {
		echo html_encode(getPageAuthor($fullname));
	}
}

/**
 * Returns the sortorder of a page
 *
 * @return string
 */
function getPageSortorder() {
	global $_CMS_current_page;
	if (is_Pages()) {
		return $_CMS_current_page->getSortOrder();
	}
	return false;
}

/**
 * Returns full path to a specific page
 *
 * @return string
 */
function getPageURL($titlelink = '') {
	global $_CMS, $_CMS_current_page;
	if (empty($titlelink)) {
		$obj = $_CMS_current_page;
	} else {
		$obj = newPage($titlelink);
	}
	return $obj->getLink();
}

/**
 * Prints the url to a specific zenpage page
 *
 * @param string $linktext Text for the URL
 * @param string $titlelink page to include in URL
 * @param string $prev text to insert before the URL
 * @param string $next text to follow the URL
 * @param string $class optional class
 */
function printPageURL($linktext = NULL, $titlelink = NULL, $prev = '', $next = '', $class = NULL) {
	if (!is_null($class)) {
		$class = 'class="' . $class . '"';
	}
	if (is_null($linktext)) {
		$linktext = getPageTitle();
	}
	echo $prev . "<a href=\"" . html_encode(getPageURL($titlelink)) . "\" $class title=\"" . html_encode($linktext) . "\">" . html_encode($linktext) . "</a>" . $next;
}

/**
 * Prints excerpts of the direct subpages (1 level) of a page for a kind of overview. The setup is:
 * <div class='pageexcerpt'>
 * <h4>page title</h3>
 * <p>page content excerpt</p>
 * <p>read more</p>
 * </div>
 *
 * @param int $excerptlength The length of the page content, if nothing specifically set, the plugin option value for 'news article text length' is used
 * @param string $readmore The text for the link to the full page. If empty the read more setting from the options is used.
 * @param string $shortenindicator The optional placeholder that indicates that the content is shortened, if this is not set the plugin option "news article text shorten indicator" is used.
 * @return string
 */
function printSubPagesExcerpts($excerptlength = NULL, $readmore = NULL, $shortenindicator = NULL) {
	global $_CMS_current_page;
	if (is_null($readmore)) {
		$readmore = get_language_string(READ_MORE);
	}
	$pages = $_CMS_current_page->getPages();
	$subcount = 0;
	if (is_null($excerptlength)) {
		$excerptlength = SHORTEN_LENGTH;
	}
	if (is_null($readmore)) {
		$readmore = get_language_string(READ_MORE);
	}
	if (is_null($shortenindicator)) {
		$shortenindicator = SHORTENINDICATOR;
	}

	foreach ($pages as $page) {
		$pageobj = newPage($page['titlelink']);
		if ($pageobj->getParentID() == $_CMS_current_page->getID()) {
			$subcount++;
			$pagetitle = html_encode($pageobj->getTitle());
			$pagecontent = $pageobj->getContent();
			if ($pageobj->checkAccess()) {
				$readmorelink = '<p class="readmorelink"><a href="' . html_encode($pageobj->getLink()) . '" title="' . html_encode($readmore) . '">' . html_encode($readmore) . '</a></p>';
				$pagecontent = shortenContent($pagecontent, $excerptlength, $shortenindicator . $readmorelink);
			} else {
				$pagecontent = '<p><em>' . gettext('This page is password protected') . '</em></p>';
			}
			echo '<div class="pageexcerpt">';
			echo '<h4><a href="' . html_encode($pageobj->getLink()) . '" title="' . html_encode(getBare($pagetitle)) . '">' . $pagetitle . '</a></h4>';
			echo $pagecontent;
			echo '</div>';
		}
	}
}

/**
 * Prints a context sensitive menu of all pages as a unordered html list
 *
 * @param string $option The mode for the menu:
 * 												"list" context sensitive toplevel plus sublevel pages,
 * 												"list-top" only top level pages,
 * 												"omit-top" only sub level pages
 * 												"list-sub" lists only the current pages direct offspring
 * @param string $css_id CSS id of the top level list
 * @param string $css_class_topactive class of the active item in the top level list
 * @param string $css_class CSS class of the sub level list(s)
 * @param string $$css_class_active CSS class of the sub level list(s)
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" (default) if you don't use it, it is not printed then.
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param bool $startlist set to true to output the UL tab
 * @@param int $limit truncation of display text
 * @return string
 */
function printPageMenu($option = 'list', $css_id = NULL, $css_class_topactive = NULL, $css_class = NULL, $css_class_active = NULL, $indexname = NULL, $showsubs = 0, $startlist = true, $limit = NULL) {
	printNestedMenu($option, 'pages', false, $css_id, $css_class_topactive, $css_class, $css_class_active, $indexname, $showsubs, $startlist, $limit);
}

/**
 * If the titlelink is valid this will setup for the page
 * Returns true if page is setup and valid, otherwise returns false
 *
 * @param string $titlelink The page to setup
 *
 * @return bool
 */
function checkForPage($titlelink) {
	global $_CMS;
	if ($_CMS->pages_enabled && !empty($titlelink)) {
		Controller::load_zenpage_pages($titlelink);
		return in_context(ZENPAGE_PAGE);
	}
	return false;
}

/* * ********************************************* */
/* Comments
  /*********************************************** */

/**
 * Gets latest comments for news articles and pages
 *
 * @param int $number how many comments you want.
 * @param string $type 	"all" for all latest comments for all news articles and all pages
 * 											"news" for the lastest comments of one specific news article
 * 											"page" for the lastest comments of one specific page
 * @param int $itemID the ID of the element to get the comments for if $type != "all"
 */
function getLatestZenpageComments($number, $type = "all", $itemID = "") {
	$itemID = sanitize_numeric($itemID);
	$number = sanitize_numeric($number);
	$checkauth = npg_loggedin();

	if ($type == 'all' || $type == 'news') {
		$newspasswordcheck = "";
		if (npg_loggedin(MANAGE_ALL_NEWS_RIGHTS)) {
			$newsshow = '';
		} else {
			$newsshow = 'news.show=1 AND';
			$newscheck = query_full_array("SELECT * FROM " . prefix('news') . " ORDER BY date");
			foreach ($newscheck as $articlecheck) {
				$obj = newArticle($articlecheck['titlelink']);
				if ($obj->inProtectedCategory()) {
					if ($checkauth && $obj->isMyItem(LIST_RIGHTS)) {
						$newsshow = '';
					} else {
						$excludenews = " AND id != " . $articlecheck['id'];
						$newspasswordcheck = $newspasswordcheck . $excludenews;
					}
				}
			}
		}
	}
	if ($type == 'all' || $type == 'page') {
		$pagepasswordcheck = "";
		if (npg_loggedin(MANAGE_ALL_PAGES_RIGHTS)) {
			$pagesshow = '';
		} else {
			$pagesshow = 'pages.show=1 AND';
			$pagescheck = query_full_array("SELECT * FROM " . prefix('pages') . " ORDER BY date");
			foreach ($pagescheck as $pagecheck) {
				$obj = newPage($pagecheck['titlelink']);
				if ($obj->isProtected()) {
					if ($checkauth && $obj->isMyItem(LIST_RIGHTS)) {
						$pagesshow = '';
					} else {
						$excludepages = " AND pages.id != " . $pagecheck['id'];
						$pagepasswordcheck = $pagepasswordcheck . $excludepages;
					}
				}
			}
		}
	}
	switch (strtolower($type)) {
		case "news":
			$whereNews = " WHERE $newsshow news.id = " . $itemID . " AND c.ownerid = news.id AND c.type = 'news' AND c.private = 0 AND c.inmoderation = 0" . $newspasswordcheck;
			break;
		case "page":
			$wherePages = " WHERE $pagesshow pages.id = " . $itemID . " AND c.ownerid = pages.id AND c.type = 'pages' AND c.private = 0 AND c.inmoderation = 0" . $pagepasswordcheck;
			break;
		case "all":
			$whereNews = " WHERE $newsshow c.ownerid = news.id AND c.type = 'news' AND c.private = 0 AND c.inmoderation = 0" . $newspasswordcheck;
			$wherePages = " WHERE $pagesshow c.ownerid = pages.id AND c.type = 'pages' AND c.private = 0 AND c.inmoderation = 0" . $pagepasswordcheck;
			break;
	}
	$comments_news = array();
	$comments_pages = array();
	if ($type == "all" OR $type == "news") {
		$comments_news = query_full_array("SELECT c.id, c.name, c.type, c.website,"
						. " c.date, c.anon, c.comment, news.title, news.titlelink FROM " . prefix('comments') . " AS c, " . prefix('news') . " AS news "
						. $whereNews
						. " ORDER BY c.id DESC LIMIT $number");
	}
	if ($type == "all" OR $type == "page") {
		$comments_pages = query_full_array($sql = "SELECT c.id, c.name, c.type, c.website,"
						. " c.date, c.anon, c.comment, pages.title, pages.titlelink FROM " . prefix('comments') . " AS c, " . prefix('pages') . " AS pages "
						. $wherePages
						. " ORDER BY c.id DESC LIMIT $number");
	}
	$comments = array();
	foreach ($comments_news as $comment) {
		$comments[$comment['id']] = $comment;
	}
	foreach ($comments_pages as $comment) {
		$comments[$comment['id']] = $comment;
	}
	krsort($comments);
	return array_slice($comments, 0, $number);
}

/**
 * support to show an image from an album
 * The imagename is optional. If absent the album thumb image will be
 * used and the link will be to the album. If present the link will be
 * to the image.
 *
 * @param string $albumname
 * @param string $imagename
 * @param int $size the size to make the image. If omitted image will be 50% of 'image_size' option.
 * @param bool $linkalbum set true to link specific image to album instead of image
 */
function zenpageAlbumImage($albumname, $imagename = NULL, $size = NULL, $linkalbum = false) {
	global $_gallery;
	echo '<br />';
	$album = newAlbum($albumname);
	if ($album->loaded) {
		if (is_null($size)) {
			$size = floor(getOption('image_size') * 0.5);
		}
		$image = NULL;
		if (is_null($imagename)) {
			$linkalbum = true;
			$image = $album->getAlbumThumbImage();
		} else {
			$image = newImage($album, $imagename);
		}
		if ($image && $image->loaded) {
			makeImageCurrent($image);
			if ($linkalbum) {
				rem_context(NPG_IMAGE);
				echo '<a href="' . html_encode($album->getLink()) . '"   title="' . sprintf(gettext('View the %s album'), $albumname) . '">';
				add_context(NPG_IMAGE);
				printCustomSizedImage(sprintf(gettext('View the album %s'), $albumname), $size);
				rem_context(NPG_IMAGE | NPG_ALBUM);
				echo '</a>';
			} else {
				echo '<a href="' . html_encode(getImageURL()) . '" title="' . sprintf(gettext('View %s'), $imagename) . '">';
				printCustomSizedImage(sprintf(gettext('View %s'), $imagename), $size);
				rem_context(NPG_IMAGE | NPG_ALBUM);
				echo '</a>';
			}
		} else {
			?>
			<span style="background:red;color:black;">
				<?php
				printf(gettext('<code>zenpageAlbumImage()</code> did not find the image %1$s:%2$s'), $albumname, $imagename);
				?>
			</span>
			<?php
		}
	} else {
		?>
		<span style="background:red;color:black;">
			<?php
			printf(gettext('<code>zenpageAlbumImage()</code> did not find the album %1$s'), $albumname);
			?>
		</span>
		<?php
	}
}
?>