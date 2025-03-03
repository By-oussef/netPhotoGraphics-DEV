<?php
/**
 * Form for registering users
 *
 */
?>
<form id="mailform" class="register_user remove-bottom" action="<?php echo sanitize($_SERVER['REQUEST_URI']); ?>" method="post" autocomplete="off">
	<input type="hidden" name="register_user" value="yes" />

	<div>
		<label for="admin_name"><?php echo gettext("Name:"); ?></label>
		<input type="text" id="admin_name" name="admin_name" value="<?php echo html_encode($admin_n); ?>" size="22" />
	</div>

	<div>
		<label for="adminuser"><?php
			if (getOption('register_user_email_is_id')) {
							echo gettext("Email:");
			} else {
							echo gettext("User ID:");
			}
			?></label>
		<input type="text" id="adminuser" name="user" value="<?php echo html_encode($user); ?>" size="22" />
	</div>

	<div>
		<?php npg_Authority::printPasswordForm(NULL, false, NULL, false, $flag = '<strong>*</strong>'); ?>
	</div>


	<?php if (!getOption('register_user_email_is_id')) { ?>
		<div>
			<label for="admin_email"><?php echo gettext("Email:"); ?></label>
			<input type="text" id="admin_email" name="admin_email" value="<?php echo html_encode($admin_e); ?>" size="22" />
		</div>
	<?php } ?>

	<?php
	$html = npgFilters::apply('register_user_form', '');
	if (!empty($html)) {
			echo $html;
	}
	?>

	<?php
	if (getOption('register_user_captcha')) {
		?>
		<div>
			<?php
			$captcha = $_captcha->getCaptcha(gettext("Enter CAPTCHA<strong>*</strong>:"));
			if (isset($captcha['submitButton'])) {
				$class = ' ' . $captcha['submitButton']['class'];
				$buttonExtra = ' ' . $captcha['submitButton']['extra'];
			}
			if (isset($captcha['html']) && isset($captcha['input'])) {
							echo $captcha['html'];
			}
			if (isset($captcha['input'])) {
				echo $captcha['input'];
			} else {
				if (isset($captcha['html'])) {
									echo $captcha['html'];
				}
			}
			if (isset($captcha['hidden'])) {
							echo $captcha['hidden'];
			}
			?>
		</div>
		<?php
	}
	?>

	<div id="contact-submit">
		<button class="button buttons<?php echo $class; ?>"<?php echo $buttonExtra; ?>><?php echo gettext('Send e-mail'); ?></button>
	</div>

	<?php if (function_exists('federated_login_buttons')) { ?>
		<fieldset id="Federated_buttons_fieldlist">
			<legend><?php echo gettext('You may also register using federated credentials'); ?></legend>
			<?php federated_login_buttons(WEBPATH . '/index.php'); ?>
		</fieldset>
	<?php } ?>

</form>