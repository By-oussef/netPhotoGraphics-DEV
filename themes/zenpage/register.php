<?php
// force UTF-8 Ø

if (!defined('WEBPATH')) {
	die();
}
if (function_exists('printRegistrationForm')) {
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<?php
			npgFilters::apply('theme_head');

			scriptLoader($_themeroot . '/style.css');
			?>
		</head>
		<body>
			<?php npgFilters::apply('theme_body_open'); ?>

			<div id="main">

				<div id="header">

					<h1><?php printGalleryTitle(); ?></h1>
				</div>

				<div id="content">

					<div id="breadcrumb">
						<h2><a href="<?php echo getGalleryIndexURL(); ?>"><strong><?php echo gettext("Index"); ?></strong></a>
						</h2>
					</div>

					<div id="content-left">
						<h1><?php echo gettext('User Registration') ?></h1>
						<?php printRegistrationForm(); ?>
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