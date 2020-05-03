<?php
	namespace Korolevsky\Methods\Money;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use Korolevsky\Constants;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Set implements ApiInterface {


		/**
		 * MoneySet Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::parametersValidator([ 'client_id', 'client_secret', 'user_id', 'value' ] ,$request);
			$client = Handler::clientValidator($request['client_id'],null, $request['client_secret']);
			$scopes = Handler::scopeConversion($client['scope']);

			if(!in_array('Money', $scopes)) Handler::generateResponse([ 350, Constants::getErrors('authorization_failed', [ 'application don\'t have scope \'Money\'' ]) ]);

			if( (int) $request['value'] >= 100000 || (int) $request['value'] < 0 ) Handler::generateResponse([ 1, Constants::getErrors('invalid_request', [ 'value is incorrect' ]) ]);

			$user = R::findOne('users', 'WHERE `id` = ?', [ $request['user_id'] ]);
			if($user == null) Handler::generateResponse([ 2, Constants::getErrors('parameters_error', [ 'user_id is invalid.' ]) ]);

			$user->money = (int) $request['value'];
			R::store($user);

			Handler::generateResponse(null, [ 'description' => Constants::getLang('information_save'), 'money' => (int) $user->money ]);
		}
	}