<?php
	namespace Korolevsky\Methods\Auth;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class EditApp implements ApiInterface {

		/**
		 * AuthEditApp Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			Handler::parametersValidator([ 'app_id', 'params' ], $request);

			$app = R::findOne('clients', 'WHERE `id` = ?', [ $request['app_id'] ]);
			if($app == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'app_id is invalid.' ]) ]);
			if($app['user_by'] != $user['id']) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'you cannot reset the data of this application because you are not it\'s creator' ]) ]);

			$params = json_decode($request['params'], true);
			if($params == null || !$params || !is_array($params)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params is invalid.' ]) ]);

			foreach($params as $key => $value) {
				if($key == 'name') {
					if(!is_string($value)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid type.' ]) ]);

					$app->name = $value;
				} elseif($key == 'scope') {
					if(!is_array($value)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid type.' ]) ]);

					foreach($value as $item) {
						$scopeDB = R::findOne('scopes', 'WHERE `id` = ?', [ $item ]);
						if($scopeDB == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid. (scope with id `' . $item . '` doesn\'t exists)' ]) ]);
					}

					$scope = implode('.', $value);
					$app->scope = $scope;
				} elseif($key == 'host') {
					$domain = parse_url($request['domain']);
					if($domain['host'] == null) {
						$request['domain'] = 'http://' . $request['domain'];
						$domain = parse_url($request['domain']);
					}

					$explode_domain = explode('.', $domain['host']);
					if(count($explode_domain) <= 1) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);
					elseif(count($explode_domain) > 3) $domain = $explode_domain[count($explode_domain)-2] . '.' . $explode_domain[count($explode_domain)-1];
					else $domain = $domain['host'];

					$app->host = $domain;
				} else Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);
			}

			R::store($app);


			$scopes = R::getAll('SELECT `key` FROM `scopes` WHERE `id` IN (' . str_replace('.', ',', $app['scope']) . ')');
			Handler::generateResponse(null, [ 'lang' => Constants::getLang('information_save'), 'app' => [ 'id' => $app['id'], 'name' => $app['name'], 'scopes' => $scopes, 'access_token' => str_repeat('*', 26), 'secret' => str_repeat('*', 26) ] ]);
		}
	}
