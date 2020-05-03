<?php
	namespace Korolevsky\Methods\Auth;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class CreateApp implements ApiInterface {

		/**
		 * AuthCreateApp Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			Handler::parametersValidator([ 'name', 'domain', 'scope' ], $request);

			$client = R::findOne('clients', 'WHERE `name` = ?', [ $request['name'] ]);
			if($client != null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'application with that name already exists' ]) ]);

			$domain = parse_url($request['domain']);
			if($domain['host'] == null) {
				$request['domain'] = 'http://' . $request['domain'];
				$domain = parse_url($request['domain']);
			}

			$explode_domain = explode('.', $domain['host']);
			if(count($explode_domain) <= 1) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'domain is invalid.' ]) ]);
			elseif(count($explode_domain) > 3) $domain = $explode_domain[count($explode_domain)-2] . '.' . $explode_domain[count($explode_domain)-1];
			else $domain = $domain['host'];


			$scopes = explode('.', $request['scope']);
			foreach($scopes as $scope) {
				$scopeDB = R::findOne('scopes', 'WHERE `id` = ?', [ $scope ]);
				if($scopeDB == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'scopes is invalid. (scope with id `' . $scope . '` doesn\'t exists)' ]) ]);
			}

			$access_token = bin2hex(random_bytes(28));
			$secret = bin2hex(random_bytes(28));

			$app = R::dispense('clients');
			$app->name = $request['name'];
			$app->scope = $request['scope'];
			$app->user_by = $user['id'];
			$app->host = $domain;
			$app->access_token = password_hash($access_token, PASSWORD_BCRYPT);
			$app->secret = password_hash($secret, PASSWORD_BCRYPT);
			$app->deleted = 0;
			R::store($app);

			$scopes = R::getAll('SELECT `key` FROM `scopes` WHERE `id` IN(' . implode(',', $scopes) . ')');

			Handler::generateResponse(null, [ 'lang' => Constants::getLang('app_created'), 'app' => Handler::autoTypeConversion([ 'id' => $app['id'] , 'name' => $app['name'], 'scopes' => $scopes, 'access_token' => $access_token, 'secret' => $secret ]) ]);
		}
	}
