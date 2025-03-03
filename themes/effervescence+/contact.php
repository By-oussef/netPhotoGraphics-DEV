<?php
// force UTF-8 Ø

if (!defined('WEBPATH')) {
	die();
}
if (function_exists('printContactForm')) {
	$enableRightClickOpen = "true";

	$backgroundImagePath = "";
// End of config
	?>
	<!DOCTYPE html>
	<html>
		<head>

			<?php npgFilters::apply('theme_head'); ?>

		</head>

		<body onload="blurAnchors()">
			<?php npgFilters::apply('theme_body_open'); ?>

			<!-- Wrap Header -->
			<div id="header">
				<div id="gallerytitle">

					<!-- Logo -->
					<div id="logo">
						<?php
						printLogo();
						?>
					</div> <!-- logo -->
				</div> <!-- gallerytitle -->

				<!-- Crumb Trail Navigation -->

				<div id="wrapnav">
					<div id="navbar">
						<span><?php printHomeLink('', ' | '); ?>
							<?php
							if (getOption('gallery_index')) {
								?>
								<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Main Index'); ?>"><?php printGalleryTitle(); ?></a>
								<?php
							} else {
								?>
								<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Albums Index'); ?>"><?php printGalleryTitle(); ?></a>
								<?php
							}
							?></a></span> |
						<?php
						echo "<em>" . gettext('Contact') . "</em>";
						?>
					</div>
				</div> <!-- wrapnav -->

			</div> <!-- header -->

			<!-- Wrap Subalbums -->
			<div id="subcontent">
				<div id="submain">
					<h3><?php echo gettext('Contact us') ?></h3>

					<?php printContactForm(); ?>
				</div>
			</div>


			<!-- Footer -->
			<div class="footlinks">

				<?php printThemeInfo(); ?>
				<?php print_SW_Link(); ?>

			</div> <!-- footerlinks -->


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