<?php
	namespace Korolevsky\Methods\Auth;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class GetScopes implements ApiInterface {

		/**
		 * AuthGetScopes Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] <= 0) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			$scopes = R::getAll('SELECT * FROM `scopes`');
			Handler::generateResponse(null, [ 'count' => count($scopes), 'items' => Handler::autoTypeConversion($scopes) ]);
		}
	}
