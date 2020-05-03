<?php
	namespace Korolevsky\Methods\Coupons;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class Get implements ApiInterface {

		/**
		 * CouponsGet Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 1) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method.' ]) ]);

			Handler::parametersValidator([ 'name' ], $request);

			$coupon = R::findOne('coupons', 'WHERE `coupon` = ? AND `deleted` = ?', [ $request['name'], 0 ]);
			if($coupon == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'name is invalid.' ]) ]);

			if($coupon['user_id'] == $user['id'] || $user['admin'] >= 3) Handler::generateResponse(null, Handler::autoTypeConversion([ 'id' => $coupon['id'], 'name' => $coupon['coupon'], 'sale' => $coupon['sale'], 'user_id' => $coupon['user_id'], 'time' => $coupon['time'], 'can_edit' => 1  ]));
			Handler::generateResponse(null, Handler::autoTypeConversion([ 'id' => $coupon['id'], 'name' => $coupon['coupon'], 'sale' => $coupon['sale'], 'user_id' => $coupon['user_id'], 'time' => $coupon['time']  ]));
		}
	}
