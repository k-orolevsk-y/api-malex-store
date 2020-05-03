<?php
	namespace Korolevsky\Methods\Coupons;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Remove implements ApiInterface {

		/**
		 * CouponsRemove Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 2) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method.' ]) ]);

			Handler::parametersValidator([ 'name' ], $request);


			$coupon = R::findOne('coupons', 'WHERE `coupon` = ? AND `deleted` = ?', [ mb_strtolower($request['name']), 0 ]);
			if($coupon == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'name is invalid.' ]) ]);
			if($coupon['user_id'] != $user['id'] && $user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'you cannot remove this coupon' ]) ]);

			$coupon->deleted = 1;
			R::store($coupon);

			Handler::generateResponse();
		}
	}
