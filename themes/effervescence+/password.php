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

	</head>

	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<!-- Wrap Header -->
		<div id="header">
			<div id="gallerytitle">

				<!-- Logo -->
				<div id="logo">
					<?php printLogo(); ?>
				</div>
			</div>

			<!-- Crumb Trail Navigation -->
			<div id="wrapnav">
				<div id="navbar">
					<?php printHomeLink('', ' | '); ?>

					<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Index'); ?>"><?php printGalleryTitle(); ?></a></span>
					<?php
					if (isset($hint)) {
						?>
						|	<?php
						echo gettext('A password is required for the page you requested');
					}
					?>
				</div>
			</div>

		</div>

		<!-- Wrap Main Body -->
		<div id="content">
			<small>&nbsp;</small>
			<div id="main">
				<?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false, isset($hint) ? WEBPATH : NULL); ?>
			</div>
		</div>

		<?php
		printFooter(false);
		npgFilters::apply('theme_body_close');
		?>

	</body>
</html>