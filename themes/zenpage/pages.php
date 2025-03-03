<?php
// force UTF-8 Ø
if (!defined('WEBPATH')) {
	die();
}
if (class_exists('CMS')) {
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<?php
			npgFilters::apply('theme_head');

			scriptLoader($_themeroot . '/style.css');

			if (class_exists('RSS')) {
							printRSSHeaderLink("News", "Zenpage news", "");
			}
			?>
		</head>

		<body>
	<?php npgFilters::apply('theme_body_open'); ?>

			<div id="main">

				<div id="header">
					<h1><?php printGalleryTitle(); ?></h1>
					<?php
					if (getOption('Allow_search')) {
						printSearchForm("", "search", "", gettext("Search gallery"));
					}
					?>
				</div>

				<div id="content">
					<div id="breadcrumb">
						<h2><a href="<?php echo getGalleryIndexURL(); ?>"><?php echo gettext("Index"); ?></a><?php
							if (!isset($ishomepage)) {
								printZenpageItemsBreadcrumb(" » ", "");
							}
							?><strong><?php
								if (!isset($ishomepage)) {
									printPageTitle(" » ");
								}
								?></strong>
						</h2>
					</div>
					<div id="content-left">
						<h2><?php printPageTitle(); ?></h2>
						<?php
						printPageContent();
						printCodeblock(1);
						if (getTags()) {
							echo gettext('<strong>Tags:</strong>');
						} printTags('links', '', 'taglist', ', ');
						?>
						<br style="clear:both;" /><br />
						<?php
						@call_user_func('printCommentForm');
						?>
					</div><!-- content left-->


					<div id="sidebar">
	<?php include("sidebar.php"); ?>
					</div><!-- sidebar -->


					<div id="footer">
	<?php include("footer.php"); ?>
					</div>

				</div><!-- content -->

			</div><!-- main -->
			<?php
			npgFilters::apply('theme_body_close');
			?>
		</body>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>