<?php
if (function_exists("printAllNewsCategories")) {
	?>
	<div class="menu">
		<h3><?php echo NEWS_LABEL; ?></h3>
		<?php printAllNewsCategories(gettext("All"), TRUE, "", "menu-active"); ?>
	</div>
<?php } ?>

<?php if (function_exists("printAlbumMenu")) { ?>
	<div class="menu">
		<?php
		if (extensionEnabled('zenpage')) {
			if ($_gallery_page == 'index.php' || $_gallery_page != 'gallery.php') {
				?>
				<h3>
					<a href="<?php echo html_encode(getCustomPageURL('gallery')); ?>" title="<?php echo gettext('Album index'); ?>"><?php echo gettext("Gallery"); ?></a>
				</h3>
				<?php
			} else {
				?>
				<h3><?php echo gettext("Gallery"); ?></h3>
				<?php
			}
		} else {
			?>
			<h3><?php echo gettext("Gallery"); ?></h3>
			<?php
		}
		printAlbumMenu("list", false, "", "menu-active", "submenu", "menu-active", '');
		?>
	</div>
<?php } ?>

<?php if (function_exists("printPageMenu")) { ?>
	<div class="menu">
		<h3><?php echo gettext("Pages"); ?></h3>
		<?php printPageMenu("list", "", "menu-active", "submenu", "menu-active"); ?>
	</div>
	<?php
}
?>

<?php if (extensionEnabled('zenpage')) { ?>
	<div class="menu">
		<h3><?php echo gettext("Latest notes"); ?></h3>
		<ul>
			<?php
			$latest = getLatestNews(3);
			foreach ($latest as $item) {
				$title = htmlspecialchars(get_language_string($item['title']));
				$link = getNewsURL($item['titlelink']);
				echo "<li><a href=\"" . $link . "\" title=\"" . strip_tags(htmlspecialchars($title, ENT_QUOTES)) . "\">" . htmlspecialchars($title) . "</a></li>";
			}
			?>
		</ul>
	</div>
<?php } ?>

<div class="menu">
	<h3><?php echo gettext("Toolbox"); ?></h3>
	<ul>
		<?php
		if (function_exists('printFavoritesURL')) {
			printFavoritesURL(NULL, '<li>', '</li><li>', '</li>');
		}

		if ($_gallery_page == "archive.php") {
			echo "<li class='menu-active'>" . gettext("Site archive view") . "</li>";
		} else {
			echo "<li>";
			printCustomPageURL(gettext("Site archive view"), "archive");
			echo "</li>";
		}
		if (extensionEnabled('daily-summary')) {
			if ($_gallery_page == "summary.php") {
				echo "<li class='menu-active'>" . gettext("Daily summary") . "</li>";
			} else {
				echo "<li>";
				printDailySummaryLink(gettext('Daily summary'), '', '', '');
				echo "</li>";
			}
		}
		if (extensionEnabled('rss')) {
			if (!is_null($_current_album)) {
				printRSSLink('Album', '<li>', gettext('Albums'), '</li>', false);
			}
			printRSSLink('Gallery', '<li>', 'Gallery', '</li>', false);
			printRSSLink("News", "<li>", NEWS_LABEL, '</li>', false);
		}
		?>
	</ul>
</div>



<?php
if (getOption("zenpage_contactpage") && function_exists('printContactForm')) {
	?>
	<div class="menu">
		<ul>
			<li>
				<?php
				if ($_gallery_page != 'contact.php') {
					printCustomPageURL(gettext('Contact us'), 'contact', '', '');
				} else {
					echo gettext("Contact us");
				}
				?></li>
		</ul>
	</div>
	<?php
}
?>
<?php
if (!npg_loggedin() && function_exists('printRegistrationForm')) {
	?>
	<div class="menu">
		<ul>
			<li>
				<?php
				if ($_gallery_page != 'register.php') {
					printCustomPageURL(gettext('Register for this site'), 'register', '', '');
				} else {
					echo gettext("Register for this site");
				}
				?></li>
		</ul>
	</div>
	<?php
}
?>


<?php
if (function_exists("printUserLogin_out")) {
	?>
	<?php
	if (npg_loggedin()) {
		?>
		<div class="menu">
			<ul>
				<li>
					<?php
				}
				printUserLogin_out("", "");
				if (npg_loggedin()) {
					?>
				</li>
			</ul>
		</div>
		<?php
	}
}
?>
<?php
if (function_exists('printLanguageSelector')) {
	printLanguageSelector(true);
}
?>