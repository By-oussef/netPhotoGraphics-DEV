<?php
/**
 * Mailing using {@link http://sourceforge.net/projects/phpmailer/ Sourceforge PHPMailer} classes
 *
 * Configure the plugin options as necessary for your e-mail server.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/PHPMailer
 * @pluginCategory mail
 */
$plugin_is_filter = 800 | CLASS_PLUGIN;
$plugin_description = gettext("Outgoing mail handler based on the <em>PHPMailer</em> class mailing facility.");
$plugin_disable = (npgFilters::has_filter('sendmail') && !extensionEnabled('PHPMailer')) ? sprintf(gettext('Only one Email handler plugin may be enabled. <a href="#%1$s"><code>%1$s</code></a> is already enabled.'), stripSuffix(npgFilters::script('sendmail'))) : '';

$option_interface = '_PHPMailer';

if ($plugin_disable) {
	enableExtension('PHPMailer', 0);
} else {
	npgFilters::register('sendmail', '_PHPMailer');
}

/**
 * Option handler class
 *
 */
class _PHPMailer {

	/**
	 * class instantiation function
	 *
	 * @return _PHPMailer
	 */
	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('PHPMailer_mail_protocol', 'sendmail');
			setOptionDefault('PHPMailer_server', '');
			setOptionDefault('PHPMailer_pop_port', '110');
			setOptionDefault('PHPMailer_smtp_port', '25');
			setOptionDefault('PHPMailer_user', '');
			setOptionDefault('PHPMailer_password', '');
			setOptionDefault('PHPMailer_secure', 0);
		}
		if (getOption('PHPMailer_secure') == 1) {
					setOption('PHPMailer_secure', 'ssl');
		}
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(gettext('Mail protocol') => array('key' => 'PHPMailer_mail_protocol', 'type' => OPTION_TYPE_RADIO,
						'buttons' => array('POP3' => 'pop3', 'SMTP' => 'smtp', 'SendMail' => 'sendmail'),
						'desc' => gettext('Select the mail protocol you wish to be used.')),
				gettext('Outgoing mail server') => array('key' => 'PHPMailer_server', 'type' => OPTION_TYPE_TEXTBOX,
						'desc' => gettext('Outgoing mail server.')),
				gettext('Secure mail') => array('key' => 'PHPMailer_secure', 'type' => OPTION_TYPE_RADIO,
						'buttons' => array(gettext('no') => 0, gettext('SSL') => 'ssl', gettext('TLS') => 'tls'),
						'desc' => gettext('Set to use a secure protocol.')),
				gettext('Mail user') => array('key' => 'PHPMailer_user', 'type' => OPTION_TYPE_TEXTBOX,
						'desc' => gettext('<em>User ID</em> for mail server.')),
				gettext('Mail password') => array('key' => 'PHPMailer_password', 'type' => OPTION_TYPE_CUSTOM,
						'desc' => gettext('<em>Password</em> for mail server.')),
				gettext('POP port') => array('key' => 'PHPMailer_pop_port', 'type' => OPTION_TYPE_TEXTBOX,
						'desc' => gettext('POP port number.')),
				gettext('SMTP port') => array('key' => 'PHPMailer_smtp_port', 'type' => OPTION_TYPE_TEXTBOX,
						'desc' => gettext('SMTP port number.'))
		);
	}

	/**
	 * Custom opton handler--creates the clear ratings button
	 *
	 * @param string $option
	 * @param string $currentValue
	 */
	function handleOption($option, $currentValue) {
		if ($option == "PHPMailer_password") {
			?>
			<input type="password" size="40" name="<?php echo $option; ?>" style="width: 338px" value="<?php echo html_encode($currentValue); ?>" />
			<?php
		}
	}

}

//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\POP3;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function _PHPMailer($msg, $email_list, $subject, $message, $from_mail, $from_name, $cc_addresses, $replyTo) {
	require_once(dirname(__FILE__) . '/PHPMailer/PHPMailer.php');
	require_once(dirname(__FILE__) . '/PHPMailer/POP3.php');
	require_once(dirname(__FILE__) . '/PHPMailer/SMTP.php');
	require_once(dirname(__FILE__) . '/PHPMailer/Exception.php');

	switch (getOption('PHPMailer_mail_protocol')) {
		case 'pop3':
			$pop = new POP3();
			$authorized = $pop->authorise(getOption('PHPMailer_server'), getOption('PHPMailer_pop_port'), 30, getOption('PHPMailer_user'), getOption('PHPMailer_password'), 0);
			$mail = new PHPMailer();
			$mail->isSMTP();
			$mail->Port = getOption('PHPMailer_smtp_port');
			$mail->Host = getOption('PHPMailer_server');
			break;
		case 'smtp':
			$mail = new PHPMailer();
			$mail->SMTPAuth = true; // enable SMTP authentication
			$mail->isSMTP();
			$mail->Username = getOption('PHPMailer_user');
			$mail->Password = getOption('PHPMailer_password');
			$mail->Host = getOption('PHPMailer_server');
			$mail->Port = getOption('PHPMailer_smtp_port');
			break;
		case 'sendmail':
			$mail = new PHPMailer();
			$mail->isSendmail();
			break;
	}
	$mail->SMTPSecure = getOption('PHPMailer_secure');
	$mail->CharSet = 'UTF-8';
	$mail->From = $from_mail;
	$mail->FromName = $from_name;
	$mail->Subject = $subject;
	$mail->Body = $message;
	$mail->AltBody = '';
	$mail->IsHTML($html);

	foreach ($email_list as $to_name => $to_mail) {
		if (is_numeric($to_name)) {
			$mail->addAddress($to_mail);
		} else {
			$mail->addAddress($to_mail, $to_name);
		}
	}
	if (count($cc_addresses) > 0) {
		foreach ($cc_addresses as $cc_name => $cc_mail) {
			if (is_numeric($cc_name)) {
				$mail->addCC($cc_mail);
			} else {
				$mail->addCC($cc_mail, $cc_name);
			}
		}
	}
	if (count($bcc_addresses) > 0) {
		foreach ($bcc_addresses as $bcc_name => $bcc_mail) {
			if (is_numeric($bcc_name)) {
				$mail->addBCC($bcc_mail);
			} else {
				$mail->addBCC($bcc_mail, $bcc_name);
			}
		}
	}

	if ($replyTo) {
		$names = array_keys($replyTo);
		$mail->addReplyTo(array_shift($replyTo), array_shift($names));
	}
	if (!$mail->Send()) {
		if (!empty($msg)) {
					$msg .= '<br />';
		}
		$msg .= sprintf(gettext('<code>PHPMailer</code> failed to send <em>%1$s</em>. ErrorInfo:%2$s'), $subject, $mail->ErrorInfo);
	}
	return $msg;
}
?>
