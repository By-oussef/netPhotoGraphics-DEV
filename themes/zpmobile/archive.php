<?php
// force UTF-8 Ø
if (!defined('WEBPATH')) {
	die();
}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php npgFilters::apply('theme_head'); ?>

		<meta name="viewport" content="width=device-width, initial-scale=1">

		<?php
		scriptLoader($_themeroot . '/style.css');
		jqm_loadScripts();
		?>
	</head>

	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div data-role="page" id="mainpage">

			<?php jqm_printMainHeaderNav(); ?>

			<div class="ui-content" role="main">
				<div class="content-primary">

					<h2><?php echo gettext("Archive view"); ?></h2>

					<div id="archive">
						<h3><?php echo gettext('Gallery'); ?></h3>
						<?php printAllDates(); ?>
						<hr />
						<?php
						if (extensionEnabled('zenpage') && hasNews()) {
							?>
							<h3><?php echo NEWS_LABEL; ?></h3>
							<?php printNewsArchive("archive"); ?>
							<hr />
							<?php
						}
						?>

						<h3><?php echo gettext('Popular Tags'); ?></h3>
						<div id="tag_cloud">
							<?php printAllTagsAs('cloud', 'tags'); ?>
						</div>
					</div>

				</div>
				<div class="content-secondary">
					<?php jqm_printMenusLinks(); ?>
				</div>
			</div><!-- /content -->
			<?php jqm_printBacktoTopLink(); ?>
			<?php jqm_printFooterNav(); ?>
		</div><!-- /page -->
		<?php npgFilters::apply('theme_body_close');
		?>
	</body>
</html>
