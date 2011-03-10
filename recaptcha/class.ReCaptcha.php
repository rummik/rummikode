<?php
	/*
	 * Based on recaptchalib.php
	 *
	 * This is a PHP library that handles calling reCAPTCHA.
	 *    - Get a reCAPTCHA API Key
	 *          https://www.google.com/recaptcha/admin/create
	 *
	 * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
	 * Copyright (c) 2010 Kim Zick -- http://www.rummik.com/
	 * AUTHORS:
	 *   Mike Crawford
	 *   Ben Maurer
	 *   Kim Zick
	 *
	 * Permission is hereby granted, free of charge, to any person obtaining a copy
	 * of this software and associated documentation files (the "Software"), to deal
	 * in the Software without restriction, including without limitation the rights
	 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	 * copies of the Software, and to permit persons to whom the Software is
	 * furnished to do so, subject to the following conditions:
	 *
	 * The above copyright notice and this permission notice shall be included in
	 * all copies or substantial portions of the Software.
	 *
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	 * THE SOFTWARE.
	 */

	/**
	 * A ReCaptchaResponse is returned from ReCaptcha::check_answer()
	 */
	class ReCaptchaResponse {
		var $is_valid;
		var $error;

		/**
		 * Initializes some variables on object construction
		 * @param boolean $is_valid Is the response valid?
		 * @param string $error Error message (optional)
		 */
		function __construct($is_valid, $error='') {
			$this->is_valid = (bool)   $is_valid;
			$this->error    = (string) $error;
		}
	}

	class ReCaptcha {
		var $use_ssl;

		var $api_server        = 'http://www.google.com/recaptcha/api';
		var $api_secure_server = 'https://www.google.com/recaptcha/api';

		var $verify_server     = 'www.google.com';

		private $public;
		private $private;

		/**
		 * Initializes some variables on object construction
		 * @param string $public A public key for reCAPTCHA
		 * @param string $private A private key for reCAPTCHA
		 * @param boolean $use_ssl Make requests over SSL? (optional, default is false)
		 */
		function __construct($public, $private, $use_ssl=false) {
			$this->public  = (string) $public;
			$this->private = (string) $private;
			$this->use_ssl = (bool)   $use_ssl;
		}

		/**
		 * Encodes the given data into a query string format
		 * @param $data - array of string elements to be encoded
		 * @return string - encoded request
		 */
		private function qsencode($data) {
			  $req = '';
			  foreach ($data as $key => $value)
				  $req .= "&$key=" . urlencode(stripslashes($value));

			  # remove the preceeding '&'
			  return substr($req, 1);
		}

		/**
		 * Submits an HTTP POST to a reCAPTCHA server
		 * @param string $host
		 * @param string $path
		 * @param array $data
		 * @param int $port
		 * @return array response
		 */
		private function http_post($host, $path, $data, $port=80) {
			if (!($sock = @fsockopen($host, $port, $errno, $errstr, 10)))
				return false;

			$req = $this->qsencode($data);

			$header  = "POST $path HTTP/1.0\r\n";
			$header .= "Host: $host\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded;\r\n";
			$header .= 'Content-Length: ' . strlen($req) . "\r\n";
			$header .= "User-Agent: reCAPTCHA/PHP\r\n\r\n";

			fwrite($sock, $header . $req);

			$response = '';
			while (!feof($sock))
				$response .= fgets($sock, 1160);

			fclose($fs);

			return explode("\r\n\r\n", $response, 2);
		}

		/**
		 * Gets the challenge HTML (javascript and non-javascript version).
		 * This is called from the browser, and the resulting reCAPTCHA HTML widget
		 * is embedded within the HTML form it was called from.
		 * @param string $pubkey A public key for reCAPTCHA
		 * @param string $error The error given by reCAPTCHA (optional, default is null)
		 * @param boolean $use_ssl Should the request be made over ssl? (optional, default is false)
		 * @return string - The HTML to be embedded in the user's form.
		 */
		function get_html($error=null) {
			$server = $this->use_ssl ? $this->api_secure_server : $this->api_server;

			$error = $error ? "&amp;error=$error" : '';

			return '<script type="text/javascript" src="' . $server . '/challenge?k=' . $this->public . $error . '"></script>' .
			       '<noscript><iframe src="' . $server . '/noscript?k=' . $this->public . $error . '" width="500" height="300" frameborder="0"</iframe><br />' .
			       '<textarea name="recaptcha_callenge_field" rows="3" cols="40"></textarea></noscript>';
		}

		/**
		 * Calls an HTTP POST function to verify if the user's guess was correct
		 * @param string $remoteip
		 * @param string $challenge
		 * @param string $response
		 * @param array $extra_params an array of extra variables to post to the server
		 * @return ReCaptchaResponse
		 */
		function check_answer($remoteip, $challenge, $response, $extra_params=array()) {
			if (!strlen($challenge) || !strlen($response))
				return new ReCaptchaResponse(false, 'incorrect-captcha-sol');

			$params = array(
				'privatekey' => $this->private,
				'remoteip'   => $remoteip,
				'challenge'  => $challenge,
				'response'   => $response
			);

			$response = $this->http_post($this->verify_server, '/recaptcha/api/verify', $params + $extra_params);

			$answers = explode("\n", $response[1]);

			if (trim($answers[0] == 'true'))
				return new ReCaptchaResponse(true);

			return new ReCaptchaResponse(false, $answers[1]);
		}

		/**
		 * If your application has a configuration page where you enter a key, you
		 * should provide a link using this function.
		 * @param string $domain The domain where the page is hosted (optional)
		 * @param string $appname The name of your application (optional)
		 * @return string URL where a user can sign up for reCAPTCHA
		 */
		function get_signup_url($domain=null, $appname=null) {
			return 'https://www.google.com/recaptcha/admin/create?' . $this->qsencode(array('domains' => $domain, 'app' => $appname));
		}

		/**
		 * This function generates a mailhide URL for a given email address.
		 * @param string $email An email address to hide
		 * @return string reCAPTCHA mailhide URL
		 */
		function mailhide_url($email) {
			# pad email address to the end of the block
			$pad = 16 - (strlen ($email) % 16);
			$val = str_pad($email, strlen ($email) + $pad, chr($pad));

			$key = @pack('H*', $this->private);
			$crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $val, MCRYPT_MODE_CBC, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");

			return 'http://www.google.com/recaptcha/mailhide/d?k=' . $this->public . '&c=' . strtr(base64_encode($crypt), '+/', '-_');
		}

		/**
		 * This function gnerates a reCAPTCHA mailhide link for a given email address.
		 * @param string $email An email address to hide
		 * @return string reCAPTCHA mailhide HTML link
		 */
		function mailhide_html($email) {
			$url = $this->mailhide_url($email);

			$parts = preg_split("/@/", $email);

			# cut out part of the name (wouldn't substr($parts[0], 0, ceil(strlen($parts[0]) / 3)) be cleaner?)
			$len = strlen($parts[0]);
			$parts[0] = substr($parts[0], 0, $len <= 6 ? ($len <= 4 ? 1 : 3) : 4);

			return htmlentities($parts[0]) . '<a href="' . htmlentities($url) . '" onclick="window.open(\'' . htmlentities($url) . "', '', " .
				"'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;" .
				'" title="Reveal this e-mail address" target="_blank">...</a>@' . htmlentities($parts[1]);
		}
	}
?>
