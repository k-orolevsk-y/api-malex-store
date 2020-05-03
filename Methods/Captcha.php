<?php
	namespace Korolevsky\Methods;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Captcha implements ApiInterface {

		/**
		 * Captcha Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::parametersValidator([ 'sid' ], $request);

			if($request['redirect_uri'] != null) {
				if(parse_url($request['redirect_uri'])['scheme'] == null) $request['redirect_uri'] = 'http://' . $request['redirect_uri'];
				if(count(explode('?', $request['redirect_uri'])) > 1) $request['redirect_uri'] .= '&';
				else $request['redirect_uri'] .= '?';

				$redirect_uri = $request['redirect_uri'];
			} else {
				$redirect_uri = 'https://malex-store.ru/?';
			}


			$spamDetect = R::findOne('apiSpam', 'WHERE `id` = ?', [ $request['sid'] ]);
			if($spamDetect == null) {
				header('Location: ' . $redirect_uri . 'captcha_error=1');
				die();
			}
			if($spamDetect['needed_captcha_percent'] < 70) {
				header('Location: ' . $redirect_uri . 'captcha_error=2');
				die();
			}
			if($spamDetect['used_methods'] > 15 && time() - $spamDetect['last_used_method'] < 5400) {
				header('Location: ' . $redirect_uri . 'captcha_error=3');
				die();
			}

			if($request['token'] == null)
				die('<style>.loading{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(75deg);width:15px;height:15px;z-index:2500}.loading-done{animation:close-loading 1.5s forwards;animation-iteration-count:1}.loading::before,.loading::after{content:"";position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:15px;height:15px;border-radius:15px;animation:loading 1.5s infinite linear}.loading::before{box-shadow:15px 15px #e77f67, -15px -15px #778beb}.loading::after{box-shadow:15px 15px #f8a5c2, -15px -15px #f5cd79;transform:translate(-50%,-50%) rotate(90deg)}.not-loaded{background:white!important;position:fixed;width:100%;height:100%;z-index:2000}@keyframes loading{50%{height:45px}}</style><div class="not-loaded" id="stub"></div><div class="loading" id="preloader"></div><script src="JS/Captcha.js"></script>');

			$recaptcha_api = 'https://www.google.com/recaptcha/api/siteverify';
			$captcha_secret = 'captcha_secret';

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $recaptcha_api);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( [ 'secret' => $captcha_secret, 'response' => $request['token'] ] ));

			$out = json_decode(curl_exec($curl), true);
			curl_close($curl);


			if(!$out['success']) {
				header('Location: ' . $redirect_uri . 'captcha_success=0');
				die();
			}

			if($spamDetect['needed_captcha_percent'] >= 100) {
				if($out['score'] > 0.5) $spamDetect->needed_captcha_percent -= 100;
				else $spamDetect->needed_captcha_percent -= 50;
			} elseif($spamDetect['needed_captcha_percent'] > 80) {
				if($out['score'] > 0.5) $spamDetect->needed_captcha_percent -= 75;
				else $spamDetect->needed_captcha_percent -= 45;
			} else {
				if($out['score'] > 0.5) $spamDetect->needed_captcha_percent -= 60;
				else $spamDetect->needed_captcha_percent -= 35;
			}


			$spamDetect['needed_captcha_time'] = 0;
			$spamDetect->last_captcha = time();
			$spamDetect['used_methods'] = 0;
			R::store($spamDetect);

			header('Location: ' . $redirect_uri . 'captcha_success=1');
			die();
		}
	}