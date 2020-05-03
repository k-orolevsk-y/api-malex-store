<?php
	namespace Korolevsky\Methods\Coupons;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Edit implements ApiInterface {

		/**
		 * CouponsEdit Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 2) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method.' ]) ]);

			Handler::parametersValidator([ 'name', 'params' ], $request);

			$coupon = R::findOne('coupons', 'WHERE `coupon` = ?', [ mb_strtolower($request['name']) ]);
			if($coupon == null || $coupon['deleted'] == 1) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'name is invalid.' ]) ]);

			$params = json_decode($request['params'], true);
			if($params == null || !is_array($params)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params (JSON) is invalid.' ]) ]);

			foreach($params as $key => $value) {
				if($key == 'name') {
					if(!is_string($value) || iconv_strlen($value) < 6) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);

					$coupon->coupon = mb_strtolower($value);
				}

				elseif($key == 'sale') {
					if(!is_numeric($value) || $value < 3 || $value > 75) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);

					$coupon->sale = (int) $value;
				}

				else Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is incorrect.' ]) ]);
			}
			R::store($coupon);

			Handler::generateResponse(null, [ 'info' => [ 'lang' => Constants::getLang('information_save') ] ]);
		}
	}