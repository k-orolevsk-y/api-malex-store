<?php
	namespace Korolevsky\Methods;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException;
	use RedBeanPHP\RedException\SQL;
	use VK\Client\Enums\VKLanguage;
	use VK\Client\VKApiClient;
	use VK\Exceptions\VKApiException;
	use VK\Exceptions\VKClientException;

	class Authorization implements ApiInterface {

		/**
		 * Authorization Constructor.
		 *
		 * @param array $request
		 *
		 * @throws RedException
		 * @throws SQL
		 * @throws VKApiException
		 * @throws VKClientException
		 */
		public function __construct(array $request) {
			Handler::parametersValidator([ 'client_id', 'client_token' ], $request);
			$client = Handler::clientValidator(intval($request['client_id']), $request['client_token']);
			$scopes = Handler::scopeConversion($client['scope']);

			if($request['steamid'] == null && $request['vkid'] == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'vkid or steamid cannot be null.' ]) ]);

			if(!in_array('Authorization', $scopes)) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'application don\'t have scope \'Authorization\'' ]) ]);

			if($request['response'] == 'token') {
				if($request['client_secret'] == null) $request['client_secret'] = 1;
				Handler::clientValidator(intval($request['client_id']), $request['client_token'], $request['client_secret']);

				$response = 'token';
			} elseif($request['response'] == 'code') {
				$response = 'code';
			} else { $response = 'code'; }

			if($request['redirect_uri'] != null && $response == 'code') {
				if($client['host'] == null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'redirect_uri is incorrect, please check application domain in settings' ]) ]);

				$parse = parse_url($request['redirect_uri']);
				if($parse['host'] == null) {
					$request['redirect_uri'] = 'http://' . $request['redirect_uri'];
					$parse = parse_url($request['redirect_uri']);
				}

				$explode_host = explode('.', $parse['host']);
				if(count($explode_host) >= 3) $host = $explode_host[count($explode_host)-2] . '.' . $explode_host[count($explode_host)-1];
				else $host = $parse['host'];

				if(mb_strtolower($host) != mb_strtolower($client['host']) && mb_strtolower($host) != 'malex-store.ru'  ) Handler::generateResponse([ 303, Constants::getErrors('invalid_request', [ 'redirect_uri is incorrect, please check application domain in settings' ]) ]);

				$redirect_uri = $request['redirect_uri'];
			} else $redirect_uri = 'https://api.malex-store.ru/blank.html';

			$user = R::findOne('users', 'WHERE `vkid` = ? OR `steamid` = ?', [ $request['vkid'], $request['steamid'] ]);
			if($user == null) {
				$user = R::dispense('users');
				if($request['steamid'] != null) {
					$query = [
						'key' => Handler::getAccessTokenSteam(),
						'steamids' => $request['steamid']
					];

					$from_steam = json_decode(file_get_contents(
						'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?'
						. http_build_query($query)
					), true)['response']['players'][0];
					if($from_steam == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'steamid is invalid.' ]) ]);

					$user->name = $from_steam['personname'];
					$user->steamid = $from_steam['steamid'];
				} else {
					$vk = new VKApiClient(5.101, VKLanguage::RUSSIAN);
					$from_vk = $vk->users()->get(Handler::getAccessTokenVK(), [
						'user_ids' => $request['vkid']
					])[0];
					if($from_vk == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'vkid is invalid.' ]) ]);

					$user->name = $from_vk['first_name'] . ' ' . $from_vk['last_name'];
					$user->vkid = $from_vk['id'];
				}
				R::store($user);

				R::exec('INSERT INTO users_data(`id`) VALUES (\'' . $user['id'] . '\')');
				$user_data = R::getRedBean()->dispense('users_data');
				$user_data->id = $user['id'];
				$user_data->regip = $_SERVER['REMOTE_ADDR'];
				R::store($user_data);
			}

			R::ext('xdispense', function( $type ){
				return R::getRedBean()->dispense( $type );
			});
			if($response == 'token') {
				$auth = R::xdispense('access_tokens');
				$auth->user_id = $user['id'];
				$auth->client_id = $client['id'];
				$auth->access_token = bin2hex(random_bytes(36));
				$auth->time = time();
				$auth->disabled = 0;

				$return = [ 'access_token' => $auth['access_token'] ];
			} else {
				$auth = R::xdispense('access_codes');
				$auth->user_id = $user['id'];
				$auth->client_id = $client['id'];
				$auth->code = bin2hex(random_bytes(24));
				$auth->time = time();
				$auth->disabled = 0;

				$return = [ 'code' => $auth['code'] ];
			}
			R::store($auth);


			$auth_history = R::xdispense('auth_history');
			$auth_history->user_id = $user['id'];
			$auth_history->client_id = $client['id'];
			$auth_history->ip = $_SERVER['REMOTE_ADDR'];
			$auth_history->browser = get_browser(null, true)['browser'];
			$auth_history->device = str_replace('Win', 'Windows ', get_browser(null, true)['platform']);
			$auth_history->time = time();
			R::store($auth_history);


			if( count(explode('?', $redirect_uri)) >= 2 ) header("Location: " . $redirect_uri . '&' . http_build_query($return + [ 'user_id' => $user['id'], 'client_id' => $client['id']  ]));
			else header("Location: " . $redirect_uri . '?' . http_build_query( $return + [ 'user_id' => $user['id'], 'client_id' => $client['id']  ]));
		}

	}