<?php
// force UTF-8 Ø
if (!defined('WEBPATH')) {
	die();
}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php
		npgFilters::apply('theme_head');
		scriptLoader($zenCSS);
		scriptLoader(dirname(dirname($zenCSS)) . '/common.css');

		if (class_exists('RSS')) {
			printRSSHeaderLink('Gallery', gettext('Gallery'));
		}
		?>
	</head>
	<body>
		<?php npgFilters::apply('theme_body_open'); ?>
		<div id="main">
			<div id="gallerytitle">
				<?php
				if (getOption('Allow_search')) {
					printSearchForm('');
				}
				?>
				<h2><?php
					printHomeLink('', ' | ');
					printGalleryTitle();
					?></h2>
			</div>
			<div id="padbox">
				<?php printGalleryDesc(); ?>
				<div id="albums">
					<?php
					printCodeblock(1);
					while (next_album()) {
						?>
						<div class="album">
							<div class="thumb">
								<a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php printAnnotatedAlbumTitle(); ?>"><?php printAlbumThumbImage(getAnnotatedAlbumTitle()); ?></a>
							</div>
							<div class="albumdesc">
								<h3><a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php printAnnotatedAlbumTitle(); ?>"><?php printAlbumTitle(); ?></a></h3>
								<small><?php printAlbumDate(""); ?></small>
								<div><?php printAlbumDesc(); ?></div>
							</div>
							<p style="clear: both; "></p>
						</div>
					<?php } ?>

				</div>
				<br class="clearall">
				<?php
				printCodeblock(2);
				printPageListWithNav("« " . gettext("prev"), gettext("next") . " »");
				$pages = $news = NULL;
				if (extensionEnabled('zenpage')) {
					$news = hasNews();
					$pages = hasPages();
				}
				if ($pages || $news) {
					?>
					<br /><hr />
					<?php
					if ($news) {
						?>
						<span class="zp_link">
							<?php
							printCustomPageURL(NEWS_LABEL, 'news');
							?>
						</span>
						<?php
					}
					if ($pages) {
						$pages = $_CMS->getPages(NULL, true); // top level only
						foreach ($pages as $item) {
							$pageobj = newPage($item['titlelink']);
							?>
							<span class="zp_link">
								<a href="<?php echo $pageobj->getLink(); ?>"><?php echo html_encode($pageobj->getTitle()); ?></a>
							</span>
							<?php
						}
					}
				}
				?>
			</div>
		</div>
		<div id="credit">
			<?php
			if (function_exists('printFavoritesURL')) {
				printFavoritesURL(NULL, '', ' | ', '<br />');
			}
			?>
			<?php @call_user_func('printUserLogin_out', '', ' | '); ?>
			<?php if (class_exists('RSS')) {
	printRSSLink('Gallery', '', 'RSS', ' | ');
}
?>
			<?php printCustomPageURL(gettext("Archive View"), "archive"); ?> |
			<?php
			if (extensionEnabled('daily-summary')) {
				printDailySummaryLink(gettext('Daily summary'), '', '', ' | ');
			}
			if (extensionEnabled('contact_form')) {
				printCustomPageURL(gettext('Contact us'), 'contact', '', '', ' | ');
			}
			if (!npg_loggedin() && function_exists('printRegisterURL')) {
				printRegisterURL(gettext('Register for this site'), '', ' | ');
			}
			?>
			<?php printSoftwareLink(); ?>
		</div>
		<?php @call_user_func('mobileTheme::controlLink'); ?>
		<?php @call_user_func('printLanguageSelector'); ?>
		<?php
		npgFilters::apply('theme_body_close');
		?>
	</body>
</html>