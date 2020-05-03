<?php
	namespace Korolevsky\Methods\Users;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Edit implements ApiInterface {

		/**
		 * UsersEdit Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user =  Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 2) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permission to use this method' ]) ]);

			Handler::parametersValidator([ 'user_id', 'params' ], $request);

			$target = R::findOne('users', 'WHERE `id` = ?', [ $request['user_id'] ]);
			if($target == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'user_id is invalid.' ]) ]);
			if($target['id'] == $user['id']) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'you cannot edit your own account' ]) ]);

			$params = json_decode($request['params'], true);
			if(!$params || !is_array($params)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params (JSON) is invalid.' ]) ]);

			$target_data = R::findOne('users_data', 'WHERE `id` = ?', [ $target['id'] ]);

			foreach($params as $key => $param) {

				if($key == 'name') {
					if(!is_string($param) || iconv_strlen($param) < 4) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);
					$target->name = $param;
				}

				elseif($key == 'money') {
					if(!is_numeric($param) || $param < 0) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);
					$param = intval($param);


					if($target_data['sumgive'] >= 1000 && time() - $target_data['lastgivemoney'] < 86400) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'user has already received the maximum amount of money per day' ]), 'info' => [ 'lang' => Constants::getLang('max_amount_give') ] ]);
					if( ($target_data['sumgive'] + $param > 1000 && time() - $target_data['lastgivemoney'] < 86400) || $param > 1000 ) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'amount issued will be greater than the amount that can be issued' ]), 'info' => [ 'lang' => Constants::getLang('be_max_amount_give'), 'can_give' => 1000 - $target_data['sumgive'] ] ]);


					$target->money += $param;
					$target_data->lastgivemoney = time();
					$target_data->sumgive += $param;
				}

				elseif($key == 'admin') {
					if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you can\'t give administrator rights' ]) ]);

					if($param < 0 || $param > 3 || !is_numeric($param)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);

					$target->admin = $param;
				}

				else Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] not used.' ]) ]);
			}

			R::store($target);
			R::store($target_data);

			Handler::generateResponse();
		}
	}
