<?php
/**
 * zenpage admin functions
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins/zenpage
 */

/**
 * Retrieves posted expiry date and checks it against the current date/time
 * Returns the posted date if it is in the future
 * Returns NULL if the date is past
 *
 * @return string
 */
function getExpiryDatePost() {
	$expiredate = sanitize($_POST['expiredate']);
	if ($expiredate > date(date('Y-m-d H:i:s'))) {
			return $expiredate;
	}
	return NULL;
}

/**
 * processes the taglist save
 *
 * @param object $object the object on which the save happened
 */
function processTags($object) {
	if (isset($_POST['tag_list_tags_'])) {
		$tags = sanitize($_POST['tag_list_tags_']);
	} else {
		$tags = array();
	}
	$tags = array_unique($tags);
	$object->setTags($tags);
}

/**
 * Returns a title link for a new object. Title link is based on the title.
 * If the title is a duplicate, a date/time string will be appended to insure
 * no duplication.
 *
 * @param db serialized string $title
 * @param string $table
 * @param array $reports
 * @return string
 */
function makeNewTitleLink($title, $table, &$reports) {
	$titlelink = seoFriendly(get_language_string($title));
	$append = RW_SUFFIX;
	if (RW_SUFFIX && $table == 'news_categories') {
		$append = '';
	}
	if (empty($titlelink)) {
		$titlelink = gettext('untitled');
	}

	$sql = 'SELECT `id` FROM ' . prefix($table) . ' WHERE `titlelink`=' . db_quote($titlelink . $append);
	$rslt = query_single_row($sql, false);
	if ($rslt) {
		//already exists
		$titlelink = $titlelink . '_' . date_format(date_create('now'), 'Y-m-d-H-i-s-u') . $append;
		$reports[] = "<p class='warningbox fade-message'>" . gettext('Duplicate page title') . '</p>';
	} else {
		$titlelink .= $append;
	}
	return $titlelink;
}

/* * ************************
  /* page functions
 * ************************* */

/**
 * Updates or adds a page and returns the object of that page
 *
 * @param array $reports report display
 *
 * @return object
 */
function updatePage(&$reports) {
	global $_current_admin_obj;
	$title = process_language_string_save("title", 2);
	$content = npgFunctions::updateImageProcessorLink(process_language_string_save("content", EDITOR_SANITIZE_LEVEL));
	$date = sanitize($_POST['date']);
	$pubdate = sanitize($_POST['pubdate']);
	$expiredate = getExpiryDatePost();
	$commentson = getcheckboxState('commentson');
	$permalink = getcheckboxState('permalink');
	$locked = getcheckboxState('locked');
	$show = getcheckboxState('show');
	$id = sanitize($_POST['id']);
	$newpage = $id == 0;

	if ($newpage) {
		$oldtitlelink = $titlelink = makeNewTitleLink($title, 'pages', $reports);
	} else {
		$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old']);
	}

	if ($edit_titlelink = getcheckboxState('edittitlelink')) {
		$titlelink = sanitize($_POST['titlelink'], 3);
		if (empty($titlelink)) {
			$titlelink = seoFriendly(get_language_string($title));
			if (empty($titlelink)) {
				$titlelink = seoFriendly($date);
			}
		}
	} else {
		if (!$permalink) { //	allow the link to change
			$link = seoFriendly(get_language_string($title));
			if (!empty($link)) {
				$titlelink = $link;
			}
		}
	}

	$rslt = true;
	if (RW_SUFFIX && $titlelink != $oldtitlelink) {
		//append RW_SUFFIX
		if (!preg_match('|^(.*)' . preg_quote(RW_SUFFIX) . '$|', $titlelink)) {
			$titlelink .= RW_SUFFIX;
		}
	}

	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$rslt = query('UPDATE ' . prefix('pages') . ' SET `titlelink`=' . db_quote($titlelink) . ' WHERE `id`=' . $id, false);
		if (!$rslt) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
			if (!$edit_titlelink) { //	we did this behind his back, don't complain
				$rslt = true;
			}
		}
	}

	// update page
	$page = newPage($titlelink, true);
	$notice = processCredentials($page);
	$page->setTitle($title);
	$page->setContent($content);
	$page->setCommentsAllowed($commentson);
	if (isset($_POST['author'])) {
		$page->setOwner(sanitize($_POST['author']));
	}
	$page->setPermalink($permalink);
	$page->setLocked($locked);
	$page->setExpiredate($expiredate);
	$page->setPublishDate($pubdate);
	if (getcheckboxState('resethitcounter')) {
		$page->set('hitcounter', 0);
	}
	if (getcheckboxState('reset_rating')) {
		$page->set('total_value', 0);
		$page->set('total_votes', 0);
		$page->set('used_ips', 0);
	}
	processTags($page);
	$page->setShow($show);

	if ($newpage) {
		$msg = npgFilters::apply('new_page', '', $page);
		if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Page <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"), get_language_string($titlelink)) . '</p>';
		} else if ($notice == '?mismatch=user') {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('You must supply a password for the Protected Page user') . '</p>';
		} else if ($notice) {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('Your passwords were empty or did not match') . '</p>';
		} else {
			$reports[] = "<p class='messagebox fade-message'>" . sprintf(gettext("Page <em>%s</em> added"), $titlelink) . '</p>';
		}
	} else {
		$msg = npgFilters::apply('update_page', '', $page, $oldtitlelink);
		if (!$rslt) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("A page with the title/titlelink <em>%s</em> already exists!"), $titlelink) . '</p>';
		} else if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Page <em>%s</em> updated but you need to give it a <strong>title</strong> before publishing!"), get_language_string($titlelink)) . '</p>';
		} else if ($notice == '?mismatch=user') {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('You must supply a password for the Protected Page user') . '</p>';
		} else if ($notice) {
			echo "<p class='errorbox fade-message'>" . gettext('Your passwords were empty or did not match') . '</p>';
		} else {
			$reports['success'] = "<p class='messagebox fade-message'>" . sprintf(gettext("Page <em>%s</em> updated"), $titlelink) . '</p>';
		}
	}
	npgFilters::apply('save_page_data', $page);
	if ($page->save() == 2) {
		$reports['success'] = "<p class='messagebox fade-message'>" . sprintf(gettext("Nothing was changed."), $titlelink) . '</p>';
	}

	$msg = npgFilters::apply('edit_error', $msg);
	if ($msg) {
		$reports[] = $msg;
	}
	return $page;
}

/**
 * Deletes an object from the database
 *
 */
function deleteZenpageObj($obj, $redirect = false) {
	$result = $obj->remove();
	if ($result) {
		if ($redirect) {
			if (strpos($redirect, '?' > 0)) {
				$redirect .= '&deleted';
			} else {
				$redirect .= '?deleted';
			}
			$parts = explode('?', $redirect);
			header('Location: ' . getAdminLink(PLUGIN_FOLDER . '/zenpage/' . $parts[0]) . isset($parts[1]) ? '?' . $parts[1] : '');
			exit();
		}
		switch ($obj->table) {
			case 'pages':
				$msg = gettext("Page successfully deleted!");
				break;
			case 'news':
				$msg = gettext("Article successfully deleted!");
				break;
			case 'news_categories':
				$msg = gettext("Category successfully deleted!");
				break;
		}
		return "<p class='messagebox fade-message'>" . $msg . "</p>";
	}
	switch ($obj->table) {
		case 'pages':
			$msg = gettext("Page delete failed!");
			break;
		case 'news':
			$msg = gettext("Article delete failed!");
			break;
		case 'news_categories':
			$msg = gettext("Category  delete failed!");
			break;
	}
	return "<p class='errorbox fade-message'>" . $msg . "</p>";
}

/**
 * Prints the table part of a single page item for the sortable pages list
 *
 * @param object $page The array containing the single page
 * @param bool $toodeep set to true to flag the element as having a problem with nesting level
 */
function printPagesListTable($page, $toodeep) {
	if ($toodeep) {
		$handle = DRAG_HANDLE_ALERT;
	} else {
		$handle = DRAG_HANDLE;
	}
	?>
	<div class="page-list_row">
		<div class="page-list_handle">
			<?php echo $handle; ?>
		</div>
		<div class="page-list_title">
			<?php
			echo '<a href="' . getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?page&amp;titlelink=' . urlencode($page->getTitlelink()) . '"> ';
			checkForEmptyTitle($page->getTitle(), "page");
			echo "</a>" . checkHitcounterDisplay($page->getHitcounter());
			?>
		</div>
		<div class="page-list_extra">
			<span>
				<?php echo html_encode($page->getOwner()); ?>
			</span>
		</div>
		<div class="page-list_extra">
			<?php printPublished($page); ?>
		</div>
		<div class="page-list_extra">
			<?php printExpired($page); ?>
		</div>
		<div class="page-list_iconwrapper">
			<div class="page-list_icon">
				<?php
				if ($page->getPassword()) {
					echo LOCK;
				} else {
					echo LOCK_OPEN;
				}
				?>
			</div>
			<div class="page-list_icon">
				<?php echo linkPickerIcon($page); ?>
			</div>

			<?php
			if (checkIfLocked($page)) {
				?>
				<div class="page-list_icon">
					<?php printPublishIconLink($page, NULL); ?>
				</div>
				<div class="page-list_icon">
					<?php
					if ($page->getCommentsAllowed()) {
						?>
						<a href="?commentson=0&amp;titlelink=<?php echo html_encode($page->getTitlelink()); ?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo gettext('Disable comments'); ?>">
							<?php echo BULLSEYE_GREEN; ?>
						</a>
						<?php
					} else {
						?>
						<a href="?commentson=1&amp;titlelink=<?php echo html_encode($page->getTitlelink()); ?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo gettext('Enable comments'); ?>">
							<?php echo BULLSEYE_RED; ?>
						</a>
						<?php
					}
					?>
				</div>
				<?php
			} else {
				?>
				<div class="page-list_icon">
					<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/placeholder.png"  style="border: 0px;" />
				</div>
				<div class="page-list_icon">
					<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/placeholder.png"  style="border: 0px;" />
				</div>
				<?php
			}
			?>

			<div class="page-list_icon">
				<a href="<?php echo WEBPATH; ?>/index.php?p=pages&amp;title=<?php echo js_encode($page->getTitlelink()); ?>" title="<?php echo gettext("View page"); ?>">
					<?php echo BULLSEYE_BLUE; ?>
				</a>
			</div>

			<?php
			if (checkIfLocked($page)) {
				if (extensionEnabled('hitcounter')) {
					?>
					<div class="page-list_icon">
						<a href="?hitcounter=1&amp;titlelink=<?php echo html_encode($page->getTitlelink()); ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('hitcounter') ?>" title="<?php echo gettext("Reset hitcounter"); ?>">
							<?php echo RECYCLE_ICON; ?>
						</a>
					</div>
					<?php
				}
				?>
				<div class="page-list_icon">
					<a href="javascript:confirmDelete('<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/pages.php'); ?>?delete=<?php echo $page->getTitlelink(); ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('delete') ?>',deletePage)" title="<?php echo gettext("Delete page"); ?>">
						<?php echo WASTEBASKET; ?>
					</a>
				</div>
				<div class="page-list_icon">
					<input class="checkbox" type="checkbox" name="ids[]" value="<?php echo $page->getTitlelink(); ?>" />
				</div>
				<?php
			} else {
				?>
				<div class="page-list_icon">
					<?php echo BULLSEYE_LIGHTGRAY; ?>
				</div>
				<div class="page-list_icon">
					<?php echo BULLSEYE_LIGHTGRAY; ?>
				</div>
				<div class="page-list_icon">
					<?php echo BULLSEYE_LIGHTGRAY; ?>
				</div>
				<div class="page-list_icon">
					<input class="checkbox" type="checkbox" name="disable" value="1" disabled="disabled" />
				</div>
				<?php
			}
			?>
		</div><!--  icon wrapper end -->
	</div>
	<?php
}

/* * ************************
  /* news article functions
 * ************************* */

/**
 * Updates or adds a news article and returns the object of that article
 *
 * @param array $reports display
 * @param bool $newarticle true if a new article
 *
 * @return object
 */
function updateArticle(&$reports, $newarticle = false) {
	global $_current_admin_obj;

	$title = process_language_string_save("title", 2);
	$content = npgFunctions::updateImageProcessorLink(process_language_string_save("content", EDITOR_SANITIZE_LEVEL));
	$show = getcheckboxState('show');
	$date = sanitize($_POST['date']);
	$pubdate = sanitize($_POST['pubdate']);
	$expiredate = getExpiryDatePost();
	$permalink = getcheckboxState('permalink');
	$commentson = getcheckboxState('commentson');
	$locked = getcheckboxState('locked');
	$id = sanitize($_POST['id']);
	$newarticle = $id == 0;

	if ($newarticle) {
		$oldtitlelink = $titlelink = makeNewTitleLink($title, 'news', $reports);
	} else {
		$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old'], 3);
	}

	if ($edit_titlelink = getcheckboxState('edittitlelink')) {
		$titlelink = sanitize($_POST['titlelink'], 3);
		if (empty($titlelink)) {
			$titlelink = seoFriendly(get_language_string($title));
			if (empty($titlelink)) {
				$titlelink = seoFriendly($date);
			}
		}
	} else {
		if (!$permalink) { //	allow the title link to change.
			$link = seoFriendly(get_language_string($title));
			if (!empty($link)) {
				$titlelink = $link;
			}
		}
	}

	if (RW_SUFFIX && ($newarticle || $titlelink != $oldtitlelink)) {
		//append RW_SUFFIX
		if (!preg_match('|^(.*)' . preg_quote(RW_SUFFIX) . '$|', $titlelink)) {
			$titlelink .= RW_SUFFIX;
		}
	}

	$rslt = true;
	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$rslt = query('UPDATE ' . prefix('news') . ' SET `titlelink`=' . db_quote($titlelink) . ' WHERE `id`=' . $id, false);
		if (!$rslt) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
			if (!$edit_titlelink) { //	we did this behind his back, don't complain
				$rslt = true;
			}
		}
	}

	// update article
	$article = newArticle($titlelink, true);
	$article->setTitle($title);
	$article->setContent($content);
	$article->setDateTime($date);
	$article->setCommentsAllowed($commentson);
	if (isset($_POST['author'])) {
		$article->setOwner(sanitize($_POST['author']));
	}
	$article->setPermalink($permalink);
	$article->setLocked($locked);
	$article->setExpiredate($expiredate);
	$article->setPublishDate($pubdate);
	$article->setSticky(sanitize_numeric($_POST['sticky']));
	if (getcheckboxState('resethitcounter')) {
		$article->set('hitcounter', 0);
	}
	if (getcheckboxState('reset_rating')) {
		$article->set('total_value', 0);
		$article->set('total_votes', 0);
		$article->set('used_ips', 0);
	}
	processTags($article);
	$categories = array();
	$myCategories = array_flip($_current_admin_obj->getObjects('news_categories'));

	if (isset($_POST['addcategories'])) {
		$cats = sanitize($_POST['addcategories']);
		$result2 = query_full_array("SELECT * FROM " . prefix('news_categories') . " ORDER BY titlelink", true, 'id');
		if ($result2) {
			foreach ($cats as $cat) {
				if (isset($result2[$cat])) {
					$categories[] = $result2[$cat]['titlelink'];
				}
			}
		}
		if (!npg_loggedin(MANAGE_ALL_NEWS_RIGHTS)) {
			foreach ($categories as $key => $cat) {
				if (!isset($myCategories[$cat])) {
					unset($categories[$key]);
				}
			}
		}
	}
	$article->setCategories($categories);
	$article->setShow($show);

	if (!npg_loggedin(MANAGE_ALL_NEWS_RIGHTS) && empty($categories)) {
		//	check if he is allowed to make un-categorized articles
		if (!isset($myCategories['`'])) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Article <em>%s</em> may not be un-categorized."), $titlelink) . '</p>';
			unset($myCategories['`']);
			$cagegories[] = array_shift($myCategories);
		}
	}


	if ($newarticle) {
		$msg = npgFilters::apply('new_article', '', $article);
		if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Article <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"), get_language_string($titlelink)) . '</p>';
		} else {
			$reports[] = "<p class='messagebox fade-message'>" . sprintf(gettext("Article <em>%s</em> added"), $titlelink) . '</p>';
		}
	} else {
		$msg = npgFilters::apply('update_article', '', $article, $oldtitlelink);
		if (!$rslt) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("An article with the title/titlelink <em>%s</em> already exists!"), $titlelink) . '</p>';
		} else if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Article <em>%s</em> updated but you need to give it a <strong>title</strong> before publishing!"), get_language_string($titlelink)) . '</p>';
		} else {
			$reports['success'] = "<p class='messagebox fade-message'>" . sprintf(gettext("Article <em>%s</em> updated"), $titlelink) . '</p>';
		}
	}
	npgFilters::apply('save_article_data', $article);
	if ($article->save() == 2) {
		$reports['success'] = "<p class='messagebox fade-message'>" . sprintf(gettext("Nothing was changed."), $titlelink) . '</p>';
	}
	$msg = npgFilters::apply('edit_error', $msg);
	if ($msg) {
		$reports[] = $msg;
	}
	return $article;
}

/**
 * Print the categories of a news article for the news articles list
 *
 * @param obj $obj object of the news article
 */
function printNewsCategories($obj) {
	$cat = $obj->getCategories();
	$number = 0;
	foreach ($cat as $cats) {
		$number++;
		if ($number != 1) {
			echo ", ";
		}
		echo get_language_string($cats['title']);
	}
}

function printAuthorDropdown() {
	$rslt = query_full_array('SELECT DISTINCT `owner` FROM ' . prefix('news'));
	if (count($rslt) > 1) {
		$authors = array();
		foreach ($rslt as $row) {
			$authors[] = $row['owner'];
		}
		if (isset($_GET['author'])) {
			$authors[] = $cur_author = sanitize($_GET['author']);
			if (!in_array($cur_author, $authors)) {
				$authors[] = $cur_author;
			}
			$selected = 'selected="selected"';
		} else {
			$selected = $cur_author = NULL;
		}
		$option = getNewsAdminOption('author');
		?>
		<form name="AutoListBox0" id="articleauthordropdown" style="float:left; margin:5px;" action="#" >
			<select name="ListBoxURL" size="1" onchange="npg_gotoLink(this.form)">
				<?php
				echo "<option $selected value='news.php" . getNewsAdminOptionPath($option) . "'>" . gettext("All authors") . "</option>";
				foreach ($authors as $author) {
					if ($cur_author == $author) {
						$selected = 'selected="selected"';
					} else {
						$selected = '';
					}
					echo "<option $selected value='news.php" . getNewsAdminOptionPath(array_merge(array('author' => $author), $option)) . "'>$author</option>\n";
				}
				?>
			</select>

		</form>
		<?php
	}
}

/**
 * Prints the dropdown menu for the date archive selector for the news articles list
 *
 */
function printNewsDatesDropdown() {
	global $_CMS;
	$datecount = $_CMS->getAllArticleDates();
	$lastyear = "";
	$nr = "";
	$option = getNewsAdminOption('date');
	if (!isset($_GET['date'])) {
		$selected = 'selected = "selected"';
	} else {
		$selected = "";
		if (!in_array($_GET['date'], $datecount)) {
			$datecount[$_GET['date']] = $_GET['date'];
		}
	}
	?>
	<form name="AutoListBox1" id="articledatesdropdown" style="float:left; margin:5px;" action="#" >
		<select name="ListBoxURL" size="1" onchange="npg_gotoLink(this.form)">
			<?php
			echo "<option $selected value='news.php" . getNewsAdminOptionPath($option) . "'>" . gettext("View all months") . "</option>\n";
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
				if (isset($_GET['category'])) {
					$catlink = "&amp;category=" . sanitize($_GET['category']);
				} else {
					$catlink = "";
				}
				$check = $month . "-" . $year;
				if (isset($_GET['date']) AND $_GET['date'] == substr($key, 0, 7)) {
					$selected = "selected='selected'";
				} else {
					$selected = "";
				}
				echo "<option $selected value='news.php" . getNewsAdminOptionPath(array_merge(array('date' => substr($key, 0, 7)), $option)) . "'>$month $year ($val)</option>\n";
			}
			?>
		</select>

	</form>
	<?php
}

/**
 *
 * Compiles an option parameter list
 * @param string $test parameter to exclude. e.g. because it is the drop-down for that irem
 * @return array
 */
function getNewsAdminOption($exclude) {
	$test = array('author' => 0, 'category' => 0, 'date' => 0, 'published' => 0, 'sortorder' => 0, 'articles_page' => 1);
	if ($exclude) {
		unset($test[$exclude]);
	}
	$list = array();
	foreach ($test as $item => $type) {
		if (isset($_GET[$item])) {
			if ($type) {
				$list[$item] = (int) sanitize_numeric($_GET[$item]);
			} else {
				$list[$item] = sanitize($_GET[$item]);
			}
		}
	}
	return $list;
}

/**
 * Creates the adminpaths for news articles if you use the dropdowns on the admin news article list together
 *
 * @param array $list an parameter array of item=>value for instance, the result of getNewsAdminOption()
 * @return string
 */
function getNewsAdminOptionPath($list) {
	$optionpath = '';
	$char = '?';
	foreach ($list as $p => $q) {
		if ($q) {
			$optionpath .= $char . $p . '=' . $q;
		} else {
			$optionpath .= $char . $p;
		}
		$char = '&amp;';
	}
	return $optionpath;
}

/**
 * Prints the dropdown menu for the published/un- publis hd selector for the news articles list
 *
 */
function printUnpublishedDropdown() {
	global $_CMS;
	?>
	<form name="AutoListBox3" id="unpublisheddropdown" style="float:left; margin:5px;"	action="#">
		<select name="ListBoxURL" size="1"	onchange="npg_gotoLink(this.form)">
			<?php
			$all = "";
			$published = "";
			$unpublished = "";
			$sticky = '';
			if (isset($_GET['published'])) {
				switch ($_GET['published']) {
					case "no":
						$unpublished = "selected='selected'";
						break;
					case "yes":
						$published = "selected='selected'";
						break;
					case 'sticky':
						$sticky = "selected='selected'";
						break;
				}
			} else {
				$all = "selected='selected'";
			}
			$option = getNewsAdminOption('published');
			echo "<option $all value='news.php" . getNewsAdminOptionPath($option) . "'>" . gettext("All articles") . "</option>\n";
			echo "<option $published value='news.php" . getNewsAdminOptionPath(array_merge(array('published' => 'yes'), $option)) . "'>" . gettext("Published") . "</option>\n";
			echo "<option $unpublished value='news.php" . getNewsAdminOptionPath(array_merge(array('published' => 'no'), $option)) . "'>" . gettext("Un-published") . "</option>\n";
			echo "<option $sticky value='news.php" . getNewsAdminOptionPath(array_merge(array('published' => 'sticky'), $option)) . "'>" . gettext("Sticky") . "</option>\n";
			?>
		</select>

	</form>
	<?php
}

/**
 * Prints the dropdown menu for the sortorder selector for the news articles list
 *
 * @author Stephen Billard
 * @Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
function printSortOrderDropdown() {
	global $_CMS;
	?>
	<form name="AutoListBox4" id="sortorderdropdown" style="float:left; margin:5px;"	action="#">
		<select name="ListBoxURL" size="1"	onchange="npg_gotoLink(this.form)">
			<?php
			if (isset($_GET['sortorder'])) {
				$selected = $_GET['sortorder'];
			} else {
				$selected = 'publishdate-desc';
			}
			$option = getNewsAdminOption('sortorder');
			$selections = array(
					'date-desc' => gettext("Order by creation date descending"),
					'date-asc' => gettext("Order by creation date ascending"),
					'publishdate-desc' => gettext("Order by published descending"),
					'publishdate-asc' => gettext("Order by published ascending"),
					'expiredate-desc' => gettext("Order by expired descending"),
					'expiredate-asc' => gettext("Order by expired ascending"),
					'lastchange-desc' => gettext("Order by last change descending"),
					'lastchange-asc' => gettext("Order by last change ascending"),
					'title-desc' => gettext("Order by title descending"),
					'title-asc' => gettext("Order by title ascending")
			);
			foreach ($selections as $sortorder => $text) {
				?>
				<option<?php if ($sortorder == $selected) {
	echo ' selected="selected"';
}
?> value="news.php<?php echo getNewsAdminOptionPath(array_merge(array('sortorder' => $sortorder), $option)); ?>"><?php echo $text; ?></option>
				<?php
			}
			?>
		</select>
	</form>
	<?php
}

/**
 * Prints the dropdown menu for the category selector for the news articles list
 *
 */
function printCategoryDropdown() {
	global $_CMS;
	$result = $_CMS->getAllCategories(false);
	if (count($result) > 0) {
		if (isset($_GET['date'])) {
			$datelink = "&amp;date=" . sanitize($_GET['date']);
			$datelinkall = "?date=" . sanitize($_GET['date']);
		} else {
			$datelink = "";
			$datelinkall = "";
		}

		$selected = $category = "";
		if (isset($_GET['category'])) {
			$add = array('titlelink' => $category);
			$category = sanitize($_GET['category']);
		} else {
			$selected = "selected='selected'";
		}
		$option = getNewsAdminOption('category');
		?>
		<form name ="AutoListBox2" id="categorydropdown" style="float:left; margin:5px;" action="#" >
			<select name="ListBoxURL" size="1" onchange="npg_gotoLink(this.form)">
				<?php
				echo "<option $selected value='news.php" . getNewsAdminOptionPath($option) . "'>" . gettext("All categories") . "</option>\n";
				if ($category == '`') {
					$selected = "selected='selected'";
				} else {
					$selected = "";
				}
				echo "<option $selected value='news.php" . getNewsAdminOptionPath(array_merge(array(
						'category' => '`'), $option)) . "'>" . gettext("Un-categorized") . "</option>\n";

				foreach ($result as $cat) {
					$catobj = newCategory($cat['titlelink']);
					$count = count($catobj->getArticles(0, 'all'));
					$count = " (" . $count . ")";
					if ($category == $cat['titlelink']) {
						$selected = "selected='selected'";
					} else {
						$selected = "";
					}
					//This is much easier than hacking the nested list function to work with this
					$getparents = $catobj->getParents();
					$levelmark = '';
					foreach ($getparents as $parent) {
						$levelmark .= '» ';
					}
					$title = $catobj->getTitle();
					if (empty($title)) {
						$title = '*' . $catobj->getTitlelink() . '*';
					}
					if ($selected || $count != " (0)") { //	don't list empty categories
						echo "<option $selected value='news.php" . getNewsAdminOptionPath(array_merge(array(
								'category' => $catobj->getTitlelink()), $option)) . "'>" . $levelmark . $title . $count . "</option>\n";
					}
				}
				?>
			</select>
		</form>
		<?php
	}
}

/**
 * Prints the dropdown menu for the articles per page selector for the news articles list
 *
 */
function printArticlesPerPageDropdown($subpage) {
	global $_CMS, $articles_page;
	$option = getNewsAdminOption('articles_page');
	?>
	<form name="AutoListBox5" id="articlesperpagedropdown" method="POST" style="float:left; margin:5px;"	action="#">
		<select name="ListBoxURL" size="1"	onchange="npg_gotoLink(this.form)">
			<?php
			$list = array_unique(array(15, 30, 60, max(1, getOption('articles_per_page'))));
			sort($list);
			foreach ($list as $count) {
				?>
				<option <?php if ($articles_page == $count) {
	echo 'selected="selected"';
}
?> value="news.php<?php echo getNewsAdminOptionPath(array_merge(array('articles_page' => $count, 'subpage' => (int) ($subpage * $articles_page / $count)), $option)); ?>"><?php printf(gettext('%u per page'), $count); ?></option>
				<?php
			}
			?>
			<option <?php if ($articles_page == 0) {
	echo 'selected="selected"';
}
?> value="news.php<?php echo getNewsAdminOptionPath(array_merge(array('articles_page' => 'all'), $option)); ?>"><?php echo gettext("All"); ?></option>
		</select>

	</form>
	<?php
}

/* * ************************
  /* Category functions
 * ************************* */

/**
 * Updates or adds a category
 *
 * @param array $reports the results display
 *
 */
function updateCategory(&$reports) {
	$date = date('Y-m-d_H-i-s');
	$id = sanitize_numeric($_POST['id']);
	$permalink = getcheckboxState('permalink');
	$title = process_language_string_save("title", 2);
	$desc = process_language_string_save("desc", EDITOR_SANITIZE_LEVEL);
	$id = sanitize($_POST['id']);
	$newcategory = $id == 0;

	if ($newcategory) {
		$oldtitlelink = $titlelink = makeNewTitleLink($title, 'news_categories', $reports);
	} else {
		$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old'], 3);
		if (getcheckboxState('edittitlelink')) {
			$titlelink = sanitize($_POST[
							'titlelink'], 3);
			if (empty($titlelink)) {
				$titlelink = seoFriendly(get_language_string($title));
				if (empty($titlelink)) {
					$titlelink = seoFriendly($date);
				}
			}
		} else {
			if (!$permalink) { //	allow the link to change
				$link = seoFriendly(get_language_string($title));
				if (!empty($link)) {
					$titlelink = $link;
				}
			}
		}
	}
	$titleok = true;
	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$titleok = query('UPDATE ' . prefix('news_categories') . ' SET `titlelink`=' . db_quote($titlelink) . ' WHERE `id`=' . $id, false);
		if (!$titleok) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
		}
	}
	//update category
	$show = getcheckboxState('show');
	$cat = newCategory($titlelink, true);
	$notice = processCredentials($cat);
	$cat->setPermalink(getcheckboxState('permalink'));
	$cat->set('title', $title);
	$cat->setDesc($desc);
	if (getcheckboxState('resethitcounter')) {
		$cat->set('hitcounter', 0);
	}
	if (getcheckboxState('reset_rating')) {
		$cat->set('total_value', 0);
		$cat->set('total_votes', 0);
		$cat->set('used_ips', 0);
	}
	$cat->setShow($show);

	if ($newcategory) {
		$msg = npgFilters::apply('new_category', '', $cat);
		if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Category <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"), $titlelink) . '</p>';
		} else if ($notice == '?mismatch=user') {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('You must supply a password for the Protected Category user') . '</p>';
		} else if ($notice) {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('Your passwords were empty or did not match') . '</p>';
		} else {
			$reports[
							] = "<p class='messagebox fade-message'>" . sprintf(gettext("Category <em>%s</em> added"), $titlelink) . '</p>';
		}
	} else {
		$msg = npgFilters::apply('update_category', '', $cat, $oldtitlelink);
		if ($titleok) {
			if (empty($titlelink) OR empty($title)) {
				$reports[] = "<p class='errorbox fade-message'>" . gettext("You forgot to give your category a <strong>title or titlelink</strong>!") . "</p>";
			} else if ($notice == '?mismatch=user') {
				$reports[] = "<p class='errorbox fade-message'>" . gettext('You must supply a password for the Protected Category user') . '</p>';
			} else if ($notice) {
				$reports[] = "<p class='errorbox fade-message'>" . gettext('Your passwords were empty or did not match') . '</p>';
			} else {
				$reports[] = "<p class='messagebox fade-message'>" . gettext("Category updated!") . "</p>";
			}
		} else {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("A category with the title/titlelink <em>%s</em> already exists!"), html_encode($cat->getTitle())) . "</p>";
		}
	}
	npgFilters::apply('save_category_data', $cat);
	if ($cat->save() == 2) {
		$msg = "<p class='messagebox fade-message'>" . sprintf(gettext("Nothing was changed."), $titlelink) . '</p>';
	}
	$msg = npgFilters::apply('edit_error', $msg);
	if ($msg) {
		$reports[] = $msg;
	}
	return $cat;
}

/**
 * Prints the list entry of a single category for the sortable list
 *
 * @param array $cat Array storing the db info of the category
 * @param string $toodeep If the category is protected
 * @return string
 */
function printCategoryListSortableTable($cat, $toodeep) {
	global $_CMS;
	if ($toodeep) {
		$handle = DRAG_HANDLE_ALERT;
	} else {
		$handle = DRAG_HANDLE;
	}
	$count = count($cat->getArticles(0, false));
	if ($cat->getTitle()) {
		$cattitle = $cat->getTitle();
	} else {
		$cattitle = "<span style='color: red; font-weight: bold'> <strong>*</strong>" . $cat->getTitlelink() . "*</span>";
	}
	?>

	<div class="page-list_row">
		<div class="page-list_handle">
			<?php echo $handle; ?>
		</div>
		<div class="page-list_title">
			<?php echo '<a href="' . getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?newscategory&amp;titlelink=' . $cat->getTitlelink() . '" title="' . gettext('Edit this category') . '">' . $cattitle . '</a>' . checkHitcounterDisplay($cat->getHitcounter()); ?>
		</div>
		<div class="page-list_extra">
			<?php echo $count; ?>
			<?php echo gettext("articles"); ?>
		</div>

		<div class="page-list_iconwrapper">
			<div class="page-list_icon"><?php
				$password = $cat->getPassword();
				if ($password) {
					echo LOCK;
				} else {
					echo LOCK_OPEN;
				}
				?>
			</div>
			<div class="page-list_icon">
				<?php echo linkPickerIcon($cat); ?>
			</div>
			<div class="page-list_icon">
				<?php
				if ($cat->getShow()) {
					$title = gettext("Un-publish");
					?>
					<a href="?publish=0&amp;titlelink=<?php echo html_encode($cat->getTitlelink()); ?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo $title; ?>">
						<?php echo CHECKMARK_GREEN; ?>
					</a>
					<?php
				} else {
					$title = gettext("Publish");
					?>
					<a href="?publish=1&amp;titlelink=<?php echo html_encode($cat->getTitlelink()); ?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo $title; ?>">
						<?php echo EXCLAMATION_RED; ?>
					</a>
					<?php
				}
				?>
			</div>
			<div class="page-list_icon">
				<?php if ($count == 0) { ?>
					<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/placeholder.png"  style="border: 0px;" />
					<?php
				} else {
					?>
					<a href="<?php echo WEBPATH; ?>/index.php?p=news&amp;category=<?php echo js_encode($cat->getTitlelink()); ?>" title="<?php echo gettext("view category"); ?>">
						<?php echo BULLSEYE_BLUE; ?>
					</a>
				<?php } ?>
			</div>
			<?php
			if (extensionEnabled('hitcounter')) {
				?>
				<div class="page-list_icon"><a
						href="?hitcounter=1&amp;id=<?php echo $cat->getID(); ?>&amp;tab=categories&amp;XSRFToken=<?php echo getXSRFToken('hitcounter') ?>" title="<?php echo gettext("Reset hitcounter"); ?>">
							<?php echo RECYCLE_ICON; ?>
					</a>
				</div>
				<?php
			}
			?>
			<div class="page-list_icon">
				<a href="javascript:confirmDelete('<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/categories.php'); ?>?delete=<?php echo js_encode($cat->getTitlelink()); ?>&amp;tab=categories&amp;XSRFToken=<?php echo getXSRFToken('delete_category') ?>',deleteCategory)"
					 title="<?php echo gettext("Delete Category"); ?>">
						 <?php echo WASTEBASKET; ?>
				</a>
			</div>
			<div class="page-list_icon">
				<input class="checkbox" type="checkbox" name="ids[]" value="<?php echo $cat->getTitlelink(); ?>" />
			</div>
		</div>
	</div>
	<?php
}

/**
 * Prints the checkboxes to select and/or show the category of an news article on the edit or add page
 *
 * @param int $id ID of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * @param string $option "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 */
function printCategoryCheckboxListEntry($cat, $articleid, $option, $class = '') {
	$selected = '';
	if (($option != "all") && !$cat->transient && !empty($articleid)) {
		$cat2news = query_single_row("SELECT cat_id FROM " . prefix('news2cat') . " WHERE news_id = " . $articleid . " AND cat_id = " . $cat->getID());
		if ($cat2news['cat_id'] != "") {
			$selected = "checked ='checked'";
		} else {
			$selected = "";
		}
	}
	$catname = $cat->getTitle();
	$catlink = $cat->getTitlelink();
	if ($cat->getPassword()) {
		$protected = '<img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/lock.png" />';
	} else {
		$protected = '';
	}
	$catid = $cat->getID();
	echo '<label for="cat' . $catid . '"><input name="addcategories[]" class="' . $class . '" id="cat' . $catid . '" type="checkbox" value="' . $catid . '"' . $selected . ' />' . $catname . ' ' . $protected . "</label>\n";
}

/* * ************************
  /* General functions
 * ************************* */

/**
 * Prints the nested list for pages and categories
 *
 * @param string $listtype 'cats-checkboxlist' for a fake nested checkbock list of categories for the news article edit/add page
 * 												'cats-sortablelist' for a sortable nested list of categories for the admin categories page
 * 												'pages-sortablelist' for a sortable nested list of pages for the admin pages page
 * @param int $articleid Only for $listtype = 'cats-checkboxlist': For ID of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * @param string $option Only for $listtype = 'cats-checkboxlist': "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 * @return string | bool
 */
function printNestedItemsList($listtype = 'cats-sortablelist', $articleid = '', $option = '', $class = 'nestedItem') {
	global $_CMS;

	switch ($listtype) {
		case 'cats-checkboxlist':
			$items = $_CMS->getAllCategories(false, 'sort_order', false);
			$classInstantiator = 'newCategory';
			$rights = LIST_RIGHTS;
			$ulclass = "";
			break;
		case 'cats-sortablelist':
			$items = $_CMS->getAllCategories(false, 'sort_order', false);
			$classInstantiator = 'newCategory';
			$rights = ZENPAGE_NEWS_RIGHTS;
			$ulclass = " class=\"page-list\"";
			break;
		case 'pages-sortablelist':
			$items = $_CMS->getPages(false, false, NULL, $sorttype = 'sort_order', false);
			$classInstantiator = 'newPage';
			$rights = ZENPAGE_PAGES_RIGHTS;
			$ulclass = " class=\"page-list\"";
			break;
		default:
			$items = array();
			$ulclass = "";
			break;
	}
	$indent = 1;
	$open = array(1 => 0);
	$rslt = false;
	foreach ($items as $item) {
		$itemobj = $classInstantiator($item['titlelink']);
		if ($rights == LIST_RIGHTS) {
			//	list the catagory if the user has it as a managed object with edit rights
			$subrights = $itemobj->subRights();
			$ismine = $subrights & MANAGED_OBJECT_RIGHTS_EDIT;
		} else {
			$ismine = $itemobj->isMyItem($rights);
		}
		if ($ismine) {
			$itemsortorder = $itemobj->getSortOrder();
			$itemid = $itemobj->getID();
			$order = explode('-', $itemsortorder);
			$level = max(1, count($order));
			if ($toodeep = $level > 1 && $order[$level - 1] === '') {
				$rslt = true;
			}
			if ($level > $indent) {
				echo "\n" . str_pad("\t", $indent, "\t") . "<ul" . $ulclass . ">\n";
				$indent++;
				$open[$indent] = 0;
			} else if ($level < $indent) {
				while ($indent > $level) {
					$open[$indent]--;
					$indent--;
					echo "</li>\n" . str_pad("\t", $indent, "\t") . "</ul>\n";
				}
			} else { // indent == level
				if ($open[$indent]) {
					echo str_pad("\t", $indent, "\t") . "</li>\n";
					$open[$indent]--;
				} else {
					echo "\n";
				}
			}
			if ($open[$indent]) {
				echo str_pad("\t", $indent, "\t") . "</li>\n";
				$open[$indent]--;
			}
			switch ($listtype) {
				case 'cats-checkboxlist':
					echo "<li>\n";
					printCategoryCheckboxListEntry($itemobj, $articleid, $option, $class);
					break;
				case 'cats-sortablelist':
					echo str_pad("\t", $indent - 1, "\t") . "<li id=\"id_" . $itemid . "\">";
					printCategoryListSortableTable($itemobj, $toodeep);
					break;
				case 'pages-sortablelist':
					echo str_pad("\t", $indent - 1, "\t") . "<li id=\"id_" . $itemid . "\">";
					printPagesListTable($itemobj, $toodeep);
					break;
			}
			$open[$indent]++;
		}
	}
	while ($indent > 1) {
		echo "</li>\n";
		$open[$indent]--;
		$indent--;
		echo str_pad("\t", $indent, "\t") . "</ul>";
	}
	if ($open[$indent]) {
		echo "</li>\n";
	} else {
		echo "\n";
	}
	return $rslt;
}

/**
 * Updates the sortorder of the items list in the database
 *
 * @param string $mode 'pages' or 'categories'
 * @return array
 */
function updateItemSortorder($mode = 'pages') {
	if (!empty($_POST['order'])) { // if someone didn't sort anything there are no values!
		$order = processOrder($_POST['order']);
		$parents = array('NULL');
		foreach ($order as $id => $orderlist) {
			$id = str_replace('id_', '', $id);
			$level = count($orderlist);
			$parents[$level] = $id;
			$myparent = $parents[$level - 1];
			switch ($mode) {
				case 'pages':
					$dbtable = prefix('pages');
					break;
				case 'categories':
					$dbtable = prefix('news_categories');
					break;
			}
			$sql = "UPDATE " . $dbtable . " SET `sort_order` = " . db_quote(implode('-', $orderlist)) . ", `parentid`= " . $myparent . " WHERE `id`=" . $id;
			query($sql);
		}
		return true;
	}
	return false;
}

/**
 * Checks if no title has been provide for items on new item creation
 * @param string $titlefield The title field
 * @param string $type 'page', 'news' or 'category'
 * @return string
 */
function checkForEmptyTitle($titlefield, $type, $truncate = true) {
	switch (strtolower($type)) {
		case "page":
			$text = gettext("Untitled page");
			break;
		case "news":
			$text = gettext("Untitled article");
			break;
		case "category":
			$text = gettext("Untitled category");
			break;
	}
	$title = getBare($titlefield);
	if ($title) {
		if ($truncate) {
			$title = truncate_string($title, 40);
		}
	} else {
		$title = "<span style='color: red; font-weight: bold'>" . $text . "</span>";
	}
	echo $title;
}

/**
 * Checks if there are hitcounts and if they are displayed behind the news article, page or category title
 *
 * @param string $item The array of the current news article, page or category in the list.
 * @return string
 */
function checkHitcounterDisplay($item) {
	if ($item == 0) {
		$hitcount = "";
	} else {
		if ($item == 1) {
			$hits = gettext("hit");
		} else {
			$hits = gettext("hits");
		}
		$hitcount = " (" . $item . " " . $hits . ")";
	}
	return $hitcount;
}

/**
 * returns an array of how many pages, articles, categories and news or pages comments we got.
 *
 * @param string $option What the statistic should be shown of: "news", "pages", "categories"
 */
function getNewsPagesStatistic($option, $all = TRUE) {
	global $_CMS;
	switch ($option) {
		case "news":
			$items = $_CMS->getArticles();
			$type = gettext("Articles");
			break;
		case "pages":
			$items = $_CMS->getPages(false);
			$type = gettext("Pages");
			break;
		case "categories":
			$type = gettext("Categories");
			$items = $_CMS->getAllCategories(false);
			break;
	}
	$total = count($items);
	$pub = 0;
	foreach ($items as $item) {
		switch ($option) {
			case "news":
				$itemobj = newArticle($item['titlelink']);
				break;
			case "pages":
				$itemobj = newPage($item['titlelink']);
				break;
			case "categories":
				$itemobj = newCategory($item['titlelink']);
				break;
		}
		if ($all || $itemobj->subRights() & MANAGED_OBJECT_RIGHTS_EDIT) {
			if ($itemobj->getShow()) {
				$pub++;
			}
		} else {
			$total--;
		}
	}
	return array($total, $type, $total - $pub);
}

function printPagesStatistic() {
	list($total, $type, $unpub) = getNewsPagesStatistic("pages", FALSE);
	if (empty($unpub)) {
		printf(ngettext('<strong>%1$u</strong> Page', '<strong>%1$u</strong> Pages', $total), $total);
	} else {
		printf(ngettext('<strong>%1$u</strong> Page (%2$u un-published)', '<strong>%1$u</strong> Pages (%2$u un-published)', $total), $total, $unpub);
	}
}

function printNewsStatistic($total = NULL, $unpub = NULL) {
	if (is_null($total)) {
		list($total, $type, $unpub) = getNewsPagesStatistic("news", FALSE);
	}
	if (empty($unpub)) {
		printf(ngettext('<strong>%1$u</strong> Article', '<strong>%1$u</strong> Articles', $total), $total);
	} else {
		printf(ngettext('<strong>%1$u</strong> Article (%2$u un-published)', '<strong>%1$u</strong> Articles (%2$u un-published)', $total), $total, $unpub);
	}
}

function printCategoriesStatistic() {
	list($total, $type, $unpub) = getNewsPagesStatistic("categories", FALSE);
	if (empty($unpub)) {
		printf(ngettext('<strong>%1$u</strong> Category', '<strong>%1$u</strong> Categories', $total), $total);
	} else {
		printf(ngettext('<strong>%1$u</strong> Category (%2$u un-published)', '<strong>%1$u</strong> Categories (%2$u un-published)', $total), $total, $unpub);
	}
}

/**
 * Prints the links to JavaScript and CSS files zenpage needs.
 *
 * @param bool $sortable set to true for tabs with sorts.
 *
 */
function zenpageJSCSS() {
	scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/zenpage/zenpage.css');
	?>
	<script type="text/javascript">
		// <!-- <![CDATA[
		window.addEventListener('load', function () {
			$("#tip a").click(function () {
				$("#tips").toggle("slow");
			});
		}, false);
		// ]]> -->
	</script>
	<?php
}

function printZenpageIconLegend() {
	?>
	<ul class="iconlegend">
		<li>
			<?php
			if (GALLERY_SECURITY == 'public') {
				?>
				<?php echo LOCK; ?>
				<?php echo LOCK_OPEN; ?>
				<?php echo gettext("has/does not have password"); ?>
				<?php
			}
			?>
		</li>
		<li><?php echo CLIPBOARD . ' ' . gettext("pick source"); ?></li>
		<li>
			<?php echo CHECKMARK_GREEN; ?>
			<?php echo EXCLAMATION_RED; ?>
			<?php echo CLOCKFACE . '&nbsp;'; ?>
			<?php echo gettext("published/not published/scheduled for publishing"); ?>
		</li>
		<li>
			<?php echo BULLSEYE_GREEN; ?>
			<?php echo BULLSEYE_RED; ?>
			<?php echo gettext("comments on/off"); ?>
		</li>
		<li><?php echo BULLSEYE_BLUE; ?>
			<?php echo gettext("view"); ?>
		</li>
		<?php
		if (extensionEnabled('hitcounter')) {
			?>
			<li>
				<?php echo RECYCLE_ICON; ?>
				<?php echo gettext("reset hitcounter"); ?>
			</li>
			<?php
		}
		?>
		<li>
			<?php echo WASTEBASKET; ?>
			<?php echo gettext("delete"); ?>
		</li>
	</ul>
	<br class="clearall">
	<?php
}

/**
 * Prints data info for objects
 *
 * @param string $object Object of the page or news article to check
 * @return string
 */
function printPublished($object) {
	$dt = $object->get('publishdate');
	if ($dt > date('Y-m-d H:i:s')) {
		echo '<span class="scheduledate">' . $dt . '</strong>';
	} else {
		echo '<span>' . $dt . '</span>';
	}
}

/**
 * Prints data info for objects
 *
 * @param string $object Object of the page or news article to check
 * @return string
 */
function printExpired($object) {
	$dt = $object->getExpireDate();
	if (!empty($dt)) {
		$expired = $dt < date('Y-m-d H:i:s');
		if ($expired) {
			echo ' <span class="expired">' . $dt . "</span>";
		} else {
			echo ' <span class="expiredate">' . $dt . "</span>";
		}
	}
}

/**
 * Prints the publish/un-published/scheduled publishing icon with a link for the pages and news articles list.
 *
 * @param string $object Object of the page or news article to check
 * @return string
 */
function printPublishIconLink($object, $urladd) {
	if ($urladd) {
		$urladd = '&amp;' . ltrim($urladd, '?');
	}
	if ($object->getShow()) {
		$title = gettext("Un-publish");
		$icon = CHECKMARK_GREEN;
		$publish = 0;
	} else {
		$title = gettext("Publish");
		$publish = 1;
		if ($object->getPublishDate() > date('Y-m-d H:i:s')) {
			//overriding scheduling
			$icon = CLOCKFACE;
		} else {
			$icon = EXCLAMATION_RED;
		}
	}
	?>
	<a href="?publish=<?php echo $publish; ?>&amp;titlelink=<?php echo html_encode($object->getTitlelink()) . $urladd; ?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo $title; ?>">
		<?php echo $icon; ?>
	</a>
	<?php
}

/**
 * Checks if a checkbox is selected and checks it if.
 *
 * @param string $field the array field of an item array to be checked (for example "permalink" or "comments on")
 */
function checkIfChecked($field) {
	if ($field) {
		echo 'checked="checked"';
	}
}

/**
 * Checks if the current logged in  user is allowed to edit the page/article.
 * Only that author or any user with admin rights will be able to edit or unlock.
 *
 * @param object $obj The page or article to check
 * @return bool
 */
function checkIfLocked($obj) {
	global $_current_admin_obj;
	if ($obj->getLocked()) {
		if (npg_loggedin($obj->manage_rights)) {
			return true;
		}
		return $obj->getOwner() == $_current_admin_obj->getUser();
	} else {
		return true;
	}
}

/**
 * Checks if the current edit.php page is called for news articles or for pages.
 *
 * @param string $page What you want to check for, "page" or "newsarticle"
 * @return bool
 */
function is_AdminEditPage($page) {
	return isset($_GET[$page]);
}

/**
 * Processes the check box bulk actions
 *
 */
function processZenpageBulkActions($type) {
	global $_CMS;
	$action = false;
	if (isset($_POST['ids'])) {
		//echo "action for checked items:". $_POST['checkallaction'];
		$action = sanitize($_POST['checkallaction']);
		switch ($type) {
			case'Article':
				$table = 'news';
				break;
			case 'Page':
				$table = 'pages';
				break;
			case 'Category':
				$table = 'news_categories';
				break;
		}
		$result = npgFilters::apply('processBulkCMSSave', NULL, $action, $table);
		$links = sanitize($_POST['ids']);
		$total = count($links);
		$message = NULL;
		$sql = '';
		if ($action != 'noaction') {
			if ($total > 0) {
				if ($action == 'addtags' || $action == 'alltags') {
					$tags = bulkTags();
				}
				if ($action == 'addcats') {
					if (isset($_POST['addcategories'])) {
						$cats = sanitize($_POST['addcategories']);
					} else {
						$cats = array();
					}
				}
				if ($action == 'changeowner') {
					$newowner = sanitize($_POST['massownerselect']);
				}
				$n = 0;
				foreach ($links as $titlelink) {

					$obj = new $type($titlelink);
					if (is_null($result)) {
						switch ($action) {
							case 'deleteall':
								$obj->remove();
								break;
							case 'addtags':
								$mytags = array_unique(array_merge($tags, $obj->getTags(false)));
								$obj->setTags($mytags);
								break;
							case 'cleartags':
								$obj->setTags(array());
								break;
							case 'alltags':
								$allarticles = $obj->getArticles('', 'all', true);
								foreach ($allarticles as $article) {
									$newsobj = newArticle($article['titlelink']);
									$mytags = array_unique(array_merge($tags, $newsobj->getTags(false)));
									$newsobj->setTags($mytags);
									$newsobj->save();
								}
								break;
							case 'clearalltags':
								$allarticles = $obj->getArticles('', 'all', true);
								foreach ($allarticles as $article) {
									$newsobj = newArticle($article['titlelink']);
									$newsobj->setTags(array());
									$newsobj->save();
								}
								break;
							case 'addcats':
								$catarray = array();
								$allcats = $obj->getCategories();
								foreach ($cats as $cat) {
									$catitem = $_CMS->getCategory($cat);
									$catarray[] = $catitem['titlelink']; //to use the setCategories method we need an array with just the titlelinks!
								}
								$allcatsarray = array();
								foreach ($allcats as $allcat) {
									$allcatsarray[] = $allcat['titlelink']; //same here!
								}
								$mycats = array_unique(array_merge($catarray, $allcatsarray));
								$obj->setCategories($mycats);
								break;
							case 'clearcats':
								$obj->setCategories(array());
								break;
							case 'showall':
								$obj->setShow(1);
								break;
							case 'hideall':
								$obj->setShow(0);
								break;
							case 'commentson':
								$obj->setCommentsAllowed(1);
								break;
							case 'commentsoff':
								$obj->setCommentsAllowed(0);
								break;
							case 'resethitcounter':
								$obj->set('hitcounter', 0);
								break;
							case 'changeowner':
								$obj->setOwner($newowner);
								break;
						}
					} else {
						$obj->set($action, $result);
					}
					$obj->save();
				}
			}
		}
	}
	return $action;
}

function zenpageBulkActionMessage($action) {
	switch ($action) {
		case 'deleteall':
			$message = gettext('Selected items deleted');
			break;
		case 'showall':
			$message = gettext('Selected items published');
			break;
		case 'hideall':
			$message = gettext('Selected items unpublished');
			break;
		case 'commentson':
			$message = gettext('Comments enabled for selected items');
			break;
		case 'commentsoff':
			$message = gettext('Comments disabled for selected items');
			break;
		case 'resethitcounter':
			$message = gettext('Hitcounter for selected items');
			break;
		case 'addtags':
			$message = gettext('Tags added to selected items');
			break;
		case 'cleartags':
			$message = gettext('Tags cleared from selected items');
			break;
		case 'alltags':
			$message = gettext('Tags added to articles of selected items');
			break;
		case 'clearalltags':
			$message = gettext('Tags cleared from articles of selected items');
			break;
		case 'addcats':
			$message = gettext('Categories added to selected items');
			break;
		case 'clearcats':
			$message = gettext('Categories cleared from selected items');
			break;
		default:
			return false;
	}
	if (isset($message)) {
		return "<p class='messagebox fade-message'>" . $message . "</p>";
	}
	return false;
}
?>
