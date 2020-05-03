<?php
	namespace Korolevsky\Methods\Coupons;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Create implements ApiInterface {

		/**
		 * CouponsCreate Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 2) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method.' ]) ]);

			Handler::parametersValidator([ 'name', 'sale' ], $request);

			if(iconv_strlen($request['name']) < 6) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'name is invalid.' ]) ]);
			if($request['sale'] < 3 || $request['sale'] > 75 || !is_numeric($request['sale'])) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'sale is invalid.' ]) ]);

			$find = R::findOne('coupons', 'WHERE `coupon` = ? AND `deleted` = ?', [ mb_strtolower($request['name']), 0 ]);
			if($find != null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'coupon with the same name already exists.' ]) ]);

			$coupon = R::dispense('coupons');
			$coupon->sale = (int) $request['sale'];
			$coupon->coupon = mb_strtolower($request['name']);
			$coupon->user_id = $user['id'];
			$coupon->time = time();
			$coupon->deleted = 0;
			R::store($coupon);

			Handler::generateResponse(null, [ 'coupon' => [ 'created' => true, 'name' => mb_strtolower($request['name']),'sale' => (int) $request['sale'] ], 'info' => [ 'lang' => Constants::getLang('coupon_created') ] ]);
		}
	}
