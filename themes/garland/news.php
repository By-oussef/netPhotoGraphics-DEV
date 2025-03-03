<?php
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

			scriptLoader($_themeroot . '/zen.css');

			if (class_exists('RSS')) {
							printRSSHeaderLink("News", "Zenpage news", "");
			}
			?>
		</head>
		<body class="sidebars">
	<?php npgFilters::apply('theme_body_open'); ?>
			<div id="navigation"></div>
			<div id="wrapper">
				<div id="container">
					<div id="header">
						<div id="logo-floater">
							<div>
								<h1 class="title"><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo html_encode(getGalleryTitle()); ?></a></h1>
								<span id="galleryDescription"><?php printGalleryDesc(); ?></span>
							</div>
						</div>
					</div>
					<!-- header -->
					<div class="sidebar">
						<div id="leftsidebar">
	<?php include("sidebar.php"); ?>
						</div>
					</div>

					<div id="center">
						<div id="squeeze">
							<div class="right-corner">
								<div class="left-corner">
									<!-- begin content -->
									<div class="main section" id="main">
										<h2 id="gallerytitle">
											<?php printHomeLink('', ' » '); ?>
											<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo html_encode(getGalleryTitle()); ?></a>
											<?php
											printNewsIndexURL(NULL, ' » ');
											printZenpageItemsBreadcrumb(' » ', '');
											printCurrentNewsCategory(" » ");
											printNewsTitle(" » ");
											printCurrentNewsArchive(" » ");
											?>
										</h2>
										<?php
										if (is_NewsArticle()) { // single news article
											?>
											<h3><?php printNewsTitle(); ?></h3>
											<div class="newsarticlecredit">
												<span class="newsarticlecredit-left">
													<?php
													$cat = getNewsCategories();
													$count = @call_user_func('getCommentCount');
													printNewsDate();
													if ($count > 0) {
														echo ' | ';
														printf(gettext("Comments: %d"), $count);
													}
													if (!empty($cat)) {
														echo ' | ';
														printNewsCategories(", ", gettext("Categories: "), "newscategories");
													}
													?>
												</span>
												<br />

												<?php printCodeblock(1); ?>
												<?php printNewsContent(); ?>
											<?php printCodeblock(2); ?>
											</div>
											<?php
											@call_user_func('printCommentForm');
										} else { // news article loop
											commonNewsLoop(true);
										}
										?>
	<?php footer(); ?>
										<p style="clear: both;"></p>
									</div>
									<!-- end content -->
									<span class="clear"></span> </div>
							</div>
						</div>
					</div>
					<span class="clear"></span>
					<div class="sidebar">
						<div id="rightsidebar">
							<?php
							if (is_NewsArticle()) {
								if (getPrevNewsURL()) {
									?>
									<div class="singlenews_prev"><?php printPrevNewsLink(); ?>&nbsp;&nbsp; </div>
									<?php
								}
								if (getNextNewsURL()) {
									?>
									<div class="singlenews_next"><?php printNextNewsLink(); ?></div>
									<?php
								}
								if (getPrevNewsURL() || getNextNewsURL()) {
									?>
									<br class="clearall">
									<?php
								}
								$cat = getNewsCategories();
								if (!empty($cat)) {
									printNewsCategories(", ", gettext("Categories: "), "newscategories");
									?>
									<br class="clearall">
									<?php
								}
								printTags('links', gettext('Tags: '), NULL, '');
							}
							?>
						</div><!-- right sidebar -->
					</div><!-- sidebar -->
				</div><!-- /container -->
			</div>
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