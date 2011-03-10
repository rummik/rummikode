<!doctype html>
<html>
	<head><title>Mailhide Example</title></head>
	<body>
	<?php
		require_once'class.ReCaptcha.php';

		# Keys at: http://www.google.com/recaptcha/mailhide/apikey
		$pubkey = '';
		$privkey = '';

		$captcha = new ReCaptcha($pubkey, $privkey);

		$email = 'example@example.com';
	?>

	The Mailhide version of <?php print $email; ?> is:
	<?php print $captcha->mailhide_html($email); ?>. <br>

	The url for the email is:
	<?php print $captcha->mailhide_url($email); ?> <br>
	</body>
</html>
