<?php
	namespace Korolevsky\Methods\Users;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class UnBan implements ApiInterface {

		/**
		 * UsersUnBan Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 2) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			Handler::parametersValidator([ 'user_id' ], $request);

			$target = R::findOne('users', 'WHERE `id` = ?', [ $request['user_id'] ]);

			if($target == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'user_id is invalid.' ]) ]);
			if($target['banned'] == null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'user hasn\'t banned' ]), 'info' => [ 'lang' => Constants::getLang('user_has_not_banned') ] ]);

			$target->banned = null;
			R::store($target);

			Handler::generateResponse();
		}
	}