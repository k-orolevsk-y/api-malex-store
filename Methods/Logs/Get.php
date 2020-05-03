<?php
	namespace Korolevsky\Methods\Logs;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class Get implements ApiInterface {


		/**
		 * LogsGet Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 1) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method.' ]) ]);

			$logs = R::getAll('SELECT * FROM `logs`');
			Handler::generateResponse(null, [ 'count' => count($logs), 'items' => Handler::autoTypeConversion($logs) ]);
		}
	}