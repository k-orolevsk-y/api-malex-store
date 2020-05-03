<?php
	namespace Korolevsky\Methods\Auth;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class GetApp implements ApiInterface {


		/**
		 * AuthGetApp Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			Handler::parametersValidator([ 'app_id' ], $request);

			$app = R::findOne('clients', 'WHERE `id` = ?', [ $request['app_id'] ]);
			if($app == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'app_id is invalid.' ]) ]);


			$scopes = R::getAll('SELECT `key` FROM `scopes` WHERE `id` IN (' . str_replace('.', ',', $app['scope']) . ')');
			Handler::generateResponse(null, [ 'app' => Handler::autoTypeConversion([ 'id' => $app['id'], 'name' => $app['name'], 'user_by' => $app['user_by'], 'scopes' => $scopes, 'access_token' => str_repeat('*', 56), 'secret' => str_repeat('*', 56), 'deleted' => $app['deleted'] ]) ]);
		}
	}