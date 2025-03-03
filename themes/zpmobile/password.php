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
					<?php if (isset($hint)) {
						?>
						<h2><a href="<?php echo getGalleryIndexURL(); ?>">Index</a>
							<?php if (isset($hint)) {
								?>» <strong><strong><?php echo gettext("A password is required for the page you requested"); ?></strong></strong>
								<?php
							}
							?></h2>
						<?php
					}
					?>

					<div id="content-error">
						<div class="errorbox">
							<?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false, isset($hint) ? WEBPATH : NULL); ?>
						</div>
						<?php
						if (!npg_loggedin() && function_exists('printRegisterURL') && $_gallery->isUnprotectedPage('register')) {
							printRegisterURL(gettext('Register for this site'), '<br />');
							echo '<br />';
						}
						?>
					</div>

				</div>

			</div><!-- /content -->
			<?php jqm_printBacktoTopLink(); ?>
			<?php jqm_printFooterNav(); ?>
		</div><!-- /page -->

		<?php npgFilters::apply('theme_body_close');
		?>
	</body>
</html>
