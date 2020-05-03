<?php
	namespace Korolevsky\Methods\Auth;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class RemoveApp implements ApiInterface {

		/**
		 * AuthRemoveApp Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			Handler::parametersValidator([ 'app_id' ], $request);

			$app = R::findOne('clients', 'WHERE `id` = ?', [ $request['app_id'] ]);
			if($app == null || $app['deleted']) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'app_id is invalid.' ]) ]);
			if($app['user_by'] != $user['id']) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'you cannot remove this application because you are not it\'s creator' ]) ]);

			if($request['redirect_uri'] != null) {
				$redirect_uri = parse_url($request['redirect_uri']);
				if($redirect_uri['host'] == null) {
					$request['redirect_uri'] = 'http://' . $request['redirect_uri'];
					$redirect_uri = parse_url($request['redirect_uri']);
				}


				if($redirect_uri['host'] != 'malex-store.ru') Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'redirect_uri is invalid! Only links to malex-store.ru are supported.' ]) ]);
			} else $request['redirect_uri'] = 'https://malex-store.ru/';

			$hash = bin2hex(random_bytes(12));
			R::exec('INSERT INTO hash(`id`) VALUES (\'' . $hash . '\')');
			$nHash = R::dispense('hash');
			$nHash->id = $hash;
			$nHash->user_id = $user['id'];
			$nHash->time = time();
			$nHash->params = json_encode([ 'act' => 'auth.removeApp', 'redirect_uri' => $request['redirect_uri'], 'app' => $app['id'], 'time' => time() ]);
			R::store($nHash);

			Handler::generateResponse([ Constants::getErrorsKey('validation_required'), Constants::getErrors('validation_required', [ 'please open redirect_uri in browser' ]), 'redirect_uri' => 'https://api.malex-store.ru/confirm?' . http_build_query([ 'hash' => $hash ])  ]);
		}
	}