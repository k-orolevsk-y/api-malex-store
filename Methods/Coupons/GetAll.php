<?php
	namespace Korolevsky\Methods\Coupons;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class GetAll implements ApiInterface {

		/**
		 * CouponsGetAll Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 1) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method.' ]) ]);

			if($request['deleted'] == 1) $coupons = R::getAll('SELECT * FROM `coupons`');
			else $coupons = R::getAll('SELECT * FROM `coupons` WHERE `deleted` = ?', [ 0 ]);

			Handler::generateResponse(null, [ 'count' => count($coupons), 'items' => Handler::autoTypeConversion($coupons) ]);
		}
	}