<?php include ("inc-header.php"); ?>
<div class="wrapper contrast top">
	<div class="container">
		<div class="sixteen columns">
			<?php include ("inc-search.php"); ?>
			<h1><?php echo gettext("Password required..."); ?></h1>
		</div>
	</div>
</div>
<div class="wrapper">
	<div class="container">
		<div class="sixteen columns">
			<div class="errorbox">
				<?php if (!npg_loggedin()) { ?>
					<div class="error"><?php echo gettext("Please Login"); ?></div>
					<?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false, isset($hint) ? WEBPATH : NULL); ?>
				<?php } else { ?>
					<div class="errorbox">
						<p><?php echo gettext('You are logged in...'); ?></p>
					</div>
				<?php } ?>

				<?php
				if (!npg_loggedin() && function_exists('printRegistrationForm') && $_gallery->isUnprotectedPage('register')) {
					printCustomPageURL(gettext('Register for this site'), 'register', '', '<br />');
					echo '<br />';
				}
				?>
			</div>
		</div>
	</div>
</div>
<?php include ("inc-bottom.php"); ?>
<?php include ("inc-footer.php"); ?>