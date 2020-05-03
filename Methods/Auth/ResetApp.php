<?php
	namespace Korolevsky\Methods\Auth;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Exception;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class ResetApp implements ApiInterface {

		/**
		 * AuthResetApp Constructor.
		 *
		 * @param array $request
		 * @throws Exception
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			Handler::parametersValidator([ 'app_id' ], $request);

			$app = R::findOne('clients', 'WHERE `id` = ?', [ $request['app_id'] ]);
			if($app == null || $app['deleted']) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'app_id is invalid.' ]) ]);
			if($app['user_by'] != $user['id']) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'you cannot reset the data of this application because you are not it\'s creator' ]) ]);

			$access_token = bin2hex(random_bytes(28));
			$secret = bin2hex(random_bytes(28));

			$app->access_token = password_hash($access_token, PASSWORD_BCRYPT);
			$app->secret = password_hash($secret, PASSWORD_BCRYPT);
			R::store($app);

			$scopes = R::getAll('SELECT `key` FROM `scopes` WHERE `id` IN(' . str_replace('.', ',', $app['scope']) . ')');

			Handler::generateResponse(null, [ 'info' => [ 'lang' => Constants::getLang('app_reset') ], 'app' => Handler::autoTypeConversion([ 'id' => $app['id'], 'name' => $app['name'], 'scopes' => $scopes, 'access_token' => $access_token, 'secret' => $secret  ]) ]);
		}
	}
