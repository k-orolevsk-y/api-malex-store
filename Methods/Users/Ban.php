<?php
	namespace Korolevsky\Methods\Users;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Ban implements ApiInterface {

		/**
		 * UsersBan Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 2) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			Handler::parametersValidator([ 'user_id', 'reason' ], $request);

			$target = R::findOne('users', 'WHERE `id` = ?', [ $request['user_id'] ]);

			if($target == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'user_id is invalid.' ]) ]);
			if($target['banned'] != null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'user has banned' ]), 'info' => [ 'lang' => Constants::getLang('user_has_banned') ] ]);
			if($target['id'] == $user['id']) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'you cannot ban yourself' ]) ]);
			if($target['admin'] >= 3 || $target['admin'] > $user['admin']) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'you cannot ban this user' ]) ]);


			if(iconv_strlen($request['reason']) < 6) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'reason must have at least 6 characters.' ]), 'info' => [ 'lang' => Constants::getLang('reason_small') ] ]);

			$target->banned = $request['reason'];
			R::store($target);

			Handler::generateResponse();
		}
	}
