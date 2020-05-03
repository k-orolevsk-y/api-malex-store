<?php
	namespace Korolevsky\Methods;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException;
	use RedBeanPHP\RedException\SQL;

	class AccessToken implements ApiInterface {

		/**
		 * AccessToken Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 * @throws RedException
		 */
		public function __construct(array $request) {
			Handler::parametersValidator([ 'client_id', 'client_secret', 'code' ], $request);

			$code = R::findOne('access_codes', 'WHERE `code` = ?', [ $request['code'] ]);
			if($code == null || $code['disabled'] == 1) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'code is invalid.' ]) ]);

			$code['disabled'] = 1;
			R::store($code);

			$client = Handler::clientValidator($request['client_id'], null, $request['client_secret']);
			if($code['client_id'] != $client['id']) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'client_id you sent is not equal to client_id through which the code was received.' ]) ]);

			R::ext('xdispense', function($type){
				return R::getRedBean()->dispense($type);
			});

			$access_token = R::xdispense('access_tokens');
			$access_token->user_id = $code['user_id'];
			$access_token->client_id = $client['id'];
			$access_token->access_token = bin2hex(random_bytes(36));
			$access_token->time = time();
			$access_token->disabled = 0;
			R::store($access_token);

			Handler::generateResponse(null, [ 'user_id' => (int) $code['user_id'], 'access_token' => $access_token['access_token'] ]);
		}
	}
