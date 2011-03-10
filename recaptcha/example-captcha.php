<!doctype html>
<html>
	<head><title>Captcha Example</title></head>
	<body>
		<form action="" method="post">
			<?php
				require_once 'class.ReCaptcha.php';

				# Keys at: http://www.google.com/recaptcha/admin/create
				$pubkey = '';
				$privkey = '';

				$captcha = new ReCaptcha($pubkey, $privkey);

				$error = null;

				# was there a reCAPTCHA response?
				if ($_POST['recaptcha_response_field']) {
					$response = $captcha->check_answer(
							$_SERVER['REMOTE_ADDR'],
							$_POST['recaptcha_challenge_field'],
							$_POST['recaptcha_response_field']
						);

					if ($response->is_valid)
						print "You got it!";

					$error = $response->error;
				}
				
				print $captcha->get_html($error);
			?>
			<br>
			<input type="submit" value="submit">
		</form>
	</body>
</html>
