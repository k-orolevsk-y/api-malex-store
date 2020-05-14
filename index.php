<?php
	namespace Korolevsky;
	require 'vendor/autoload.php';
	error_reporting(0);

	use Exception;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Handler {

		private const ACCESS_TOKEN_STEAM = 'at_steam';
		private const ACCESS_TOKEN_VK = 'at_vk';
		private const DB_DATA = [
			'host' => 'localhost',
			'dbname' => 'name',
			'user' => 'username',
			'pass' => 'password'
		];
		protected static string $oAuth = '0';

		/**
		 * Handler constructor.
		 */
		public function __construct() {
			$data = $this->getData();
			$this->connectDataBase();

			$method = array_pop(explode('/', explode('?', $_SERVER['REQUEST_URI'])[0]));

			unset($_GET['method']);
			unset($_POST['method']);
			if($data['method'] != null && $data['method'] != $method && $method != null) {
				$_SERVER['REQUEST_URI'] = $data['method'];

				Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'this method can not be called that way' ])  ]);
			} elseif($method == null) {
				$_SERVER['REQUEST_URI'] = $data['method'];
				$method = array_pop(explode('/', explode('?', $_SERVER['REQUEST_URI'])[0]));
			}


			if(mb_strcut($method, strlen($method) -4) == '.php') $method = mb_strcut($method, 0, -4);
			if($method == null) Handler::generateResponse([ Constants::getErrorsKey('error_method'), Constants::getErrors('error_method', [ '`method` field cannot be empty' ]) ]);

			$required_method = Constants::getMethod($method);
			if($required_method == null) Handler::generateResponse([ Constants::getErrorsKey('error_method'), Constants::getErrors('error_method', [ 'method `' . $method . '` does not exists' ]) ]);


			@require 'Methods/' . $required_method . '.php';
			$new_method = 'Korolevsky\Methods\\' . str_replace('/', '\\', $required_method);
			@new $new_method($data);
		}

		/**
		 * Getting merged POST and GET request
		 *
		 * @return array
		 */
		public static function getData() {
			return array_change_key_case($_POST + $_GET, CASE_LOWER);
		}

		/**
		 * Captcha needed function
		 *
		 * @param int $access_token_id
		 */
		private static function captchaNeeded(int $access_token_id) {
			$spamDetect = R::findOne('apiSpam', 'WHERE `access_token_id` = ?', [ $access_token_id ]);
			if($spamDetect == null) {
				$spamDetect = R::getRedBean()->dispense('apiSpam');
				$spamDetect->access_token_id = $access_token_id;
				$spamDetect->server_ip = $_SERVER['REMOTE_ADDR'];
				$spamDetect->needed_captcha_percent = 0;
				$spamDetect->last_captcha = 0;
				$spamDetect->last_used_method = time();
				try { R::store($spamDetect); } catch(Exception $exception) {}
			}

			if(time() - $spamDetect['needed_captcha_time'] > 5400 && $spamDetect['needed_captcha_time'] != 0) {
				$spamDetect['needed_captcha_time'] = 0;
				$spamDetect['used_methods'] = 0;
				$spamDetect['needed_captcha_percent'] -= 50;
			}


			if($spamDetect['needed_captcha_percent'] < 100) {
				if(time() - $spamDetect['last_used_method'] < 5) $spamDetect['needed_captcha_percent'] += 10;
				elseif(time() - $spamDetect['last_used_method'] < 10) $spamDetect['needed_captcha_percent'] += 5;
				elseif(time() - $spamDetect['last_used_method'] < 30) $spamDetect['needed_captcha_percent'] += 2;
				if($spamDetect['server_ip'] != $_SERVER['REMOTE_ADDR']) {
					$spamDetect['server_ip'] = $_SERVER['REMOTE_ADDR'];
					$spamDetect['needed_captcha_percent'] += 10;
				}

				if($spamDetect['needed_captcha_percent'] >= 70) $spamDetect['needed_captcha_time'] = time();
				if($spamDetect['needed_captcha_percent'] > 100) $spamDetect['needed_captcha_percent'] = 100;
			}



			$spamDetect->last_used_method = time();
			if($spamDetect['needed_captcha_time'] > 0) $spamDetect['used_methods']++;
			try { R::store($spamDetect); } catch(Exception $exception) {}

			if($spamDetect['used_methods'] >= 15 && time() - $spamDetect['last_used_method'] < 5400 && $spamDetect['needed_captcha_time'] > 0)
				Handler::generateResponse([ Constants::getErrorsKey('security_error'), Constants::getErrors('security_error', [ 'you have been restricted access from this access_token' ]), 'info' => [ 'lang' => Constants::getLang('security_error_captcha') ] ]);

			if( ($spamDetect['needed_captcha_percent'] >= 70 && time() - $spamDetect['last_captcha'] >= 300)
				||  $spamDetect['needed_captcha_percent'] >= 80 )
				Handler::generateResponse([ Constants::getErrorsKey('captcha_need'), Constants::getErrors('captcha_need', [ 'sent user to redirect_uri' ]), 'redirect_uri' => 'https://api.malex-store.ru/captcha?' . http_build_query([ 'sid' => (int) $spamDetect['id'] ]) ]);
		}

		/**
		 * Connect to database
		 */
		private function connectDataBase() {
			R::setup('mysql:host=' . self::DB_DATA['host'] . ';dbname=' . self::DB_DATA['dbname'], self::DB_DATA['user'], self::DB_DATA['pass']);

			if( !R::testConnection() ) Handler::generateResponse([ Constants::getErrorsKey('db_problem'), Constants::getErrors('db_problem') ]);
		}

		/**
		 * Generate response of API
		 *
		 * @param array|null $error
		 * @param array|null $returns
		 */
		public static function generateResponse(?array $error = null, ?array $returns = []) {
			header('Content-Type: application/json');
			$response = [];

			if($error != null) {
				$response['status'] = 'error';
				$response['error']['error_code'] = (int) $error[0];
				$response['error']['error_msg'] = $error[1];

				foreach(array_slice($error, 2) as $key => $value) $response['error'][$key] = $value;

				$response['error']['request_params'] = [];

				$method = array_pop(explode('/', explode('?', $_SERVER['REQUEST_URI'])[0]));
				array_push($response['error']['request_params'], [ 'key' => 'method', 'value' =>  $method == '' ? null : $method ]);
				array_push($response['error']['request_params'], [ 'key' => 'oAuth', 'value' => static::$oAuth ]);

				$data = Handler::getData();
				unset($data['access_token']);

				foreach($data as $key => $value) array_push($response['error']['request_params'], [ 'key' => $key, 'value' => $value ]);

				exit(json_encode($response));
			}

			$response['status'] = 'success';
			foreach($returns as $key => $value) $response['response'][$key] = $value;

			exit(json_encode($response));
		}

		/**
		 * Access token validator
		 *
		 * @param string|null $access_token
		 * @param bool $captcha_need
		 *
		 * @return bool|null
		 */
		public static function accessTokenValidator(?string $access_token, bool $captcha_need = false): ?bool {
			if($access_token == null) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'no access_token passed.' ]) ]);

			$from_db = R::findOne('access_tokens', 'WHERE `access_token` = ?', [ $access_token ]);
			if($from_db == null || $from_db['disabled'] == 1) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'access_token is invalid.' ]) ]);

			static::$oAuth = '1';

			$user = R::findOne('users', 'WHERE `id` = ?', [ $from_db['user_id'] ]);
			if($user['banned'] != null) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'account banned.' ]), 'info' => [ 'lang' => Constants::getLang('account_banned'), 'reason' => $user['banned'] ] ]);

			if($captcha_need) Handler::captchaNeeded($from_db['id']);

			return true;
		}

		/**
		 * Parameters validator of request
		 *
		 * @param array $params
		 * @param array $request
		 */
		public static function parametersValidator(array $params, array $request) {
			$params = str_replace(' ', '_', $params);

			if( ( $missed = array_diff($params, array_keys( array_diff( $request, [null] ) ) ) ) != null )
				Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ array_shift($missed) . ' a required parameter!' ]) ]);
		}

		/**
		 * Auto set type to var
		 *
		 * @param array $array
		 *
		 * @return array
		 */
		public static function autoTypeConversion(array $array): array {
			foreach($array as $key => $val) {
				if(is_array($val) || is_object($val)) $array[$key] = Handler::autoTypeConversion( (array) $val );

				$val_num = str_replace(',', '.', $val);
				if(is_numeric($val_num)) {
					if(strstr($val_num, '.') !== false) $array[$key] = floatval($val);
					else $array[$key] = intval($val_num);
				}
			}

			return $array;
		}

		/**
		 * Get user by access token
		 *
		 * @param string $access_token
		 * @param string|null $fields
		 *
		 * @return array|null
		 */
		public static function getUserByAccessToken(string $access_token, ?string $fields = null): ?array {
			$tokenInDB = R::findOne('access_tokens', 'WHERE `access_token` = ?', [ $access_token ]);
			if($tokenInDB == null || $tokenInDB['disabled'] == 1) return null;

			if($fields != null) {
				try {
					$user = R::getAll('SELECT ' . $fields . ' FROM `users` WHERE `id` = ?', [ $tokenInDB['user_id'] ])[0];
					if($user == null) return null;

					return Handler::autoTypeConversion($user);
				} catch(Exception $exception) {
					Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'fields is invalid' ]) ]);
				}
			}

			$user = R::load('users',  $tokenInDB['user_id']);
			if($user == null) return null;

			return Handler::autoTypeConversion($user->export());
		}

		public static function clientValidator(int $id, ?string $access_token = null, ?string $secret = null): ?array {
			$client = R::findOne('clients', 'WHERE `id` = ?', [ $id ]);

			if($client == null) Handler::generateResponse([ Constants::getErrorsKey('invalid_client_data'), Constants::getErrors('invalid_client_data', [ 'client_id is incorrect' ]) ]);

			if($access_token != null) {
				if(!password_verify($access_token, $client['access_token'])) Handler::generateResponse([ Constants::getErrorsKey('invalid_client_data'), Constants::getErrors('invalid_client_data', [ 'client_token is incorrect' ]) ]);
			}

			if($secret != null) {
				if(!password_verify($secret, $client['secret'])) Handler::generateResponse([ Constants::getErrorsKey('invalid_client_data'), Constants::getErrors('invalid_client_data', [ 'client_secret is incorrect' ]) ]);
			}

			return Handler::autoTypeConversion($client->export());
		}

		public static function scopeConversion(string $scope): ?array {
			$scopes = R::getAll('SELECT * FROM `scopes` WHERE `id` IN(' . str_replace('.', ',', $scope) .  ')');

			$return_scopes = [];
			foreach($scopes as $scope) array_push($return_scopes, $scope['key']);

			return Handler::autoTypeConversion($return_scopes);
		}

		/**
		 * Get termination for words
		 *
		 * @param int $number
		 * @param array $after
		 *
		 * @return string
		 */
		public static function pluralForm(int $number, array $after): string {
			$cases = array(2, 0, 1, 1, 1, 2);
			return $number.' '.$after[ ($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)] ];
		}

		public static function getAccessTokenSteam() {
			return static::ACCESS_TOKEN_STEAM;
		}

		public static function getAccessTokenVK() {
			return static::ACCESS_TOKEN_VK;
		}
	}

	/**
	 * Autoload php files
	 */
	spl_autoload_register(function($class_name) {
		if(explode('\\', $class_name)[0] == 'Korolevsky') $class_name = implode('/', array_slice(explode('\\', $class_name), 1));

		if(file_exists($class_name . '.php')) {
			require $class_name . '.php';
			return true;
		}
		return false;
	});

	new Handler();
