<?php
	namespace Korolevsky\Methods\Users;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Logout implements ApiInterface {

		/**
		 * UsersLogout Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);
			$user = Handler::getUserByAccessToken($request['access_token']);

			if($request['all'] == true) {
				$tokens = R::getAll('SELECT * FROM `access_tokens` WHERE `user_id` = ? AND `access_token` != ?', [ $user['id'], $request['access_token'] ]);
				foreach($tokens as $token) {
					$token = R::convertToBean('access_tokens', $token);
					$token->disabled = 1;
					R::store($token);
				}

				$codes = R::getAll('SELECT * FROM `access_codes` WHERE `user_id` = ?', [ $user['id'] ]);
				foreach($codes as $code) {
					$code = R::convertToBean('access_codes', $code);
					$code->disabled = 1;
					R::store($code);
				}

				Handler::generateResponse();
			}

			$access_token = R::findOne('access_tokens', 'WHERE `access_token` = ?', [ $request['access_token'] ]);
			$access_token->disabled = 1;
			R::store($access_token);

			Handler::generateResponse();
		}
	}