<?php
	namespace Korolevsky\Methods\Goods;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class Get implements ApiInterface {

		/**
		 * GoodsGet Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 2) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method.' ]) ]);

			if($request['product_id'] != null) {
				$product = R::findOne('products', 'WHERE `id` = ?', [ $request['product_id'] ]);
				if($product == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'product_id is invalid' ]) ]);

				$goods = R::getAll('SELECT * FROM `goods` WHERE `product_id` = ?', [ $product['id'] ]);
				if($goods == null) Handler::generateResponse([ Constants::getErrorsKey('server_error'), Constants::getErrors('server_error', [ 'no goods in database' ]) ]);

				if($user['admin'] < 3) foreach($goods as $key => $value) $goods[$key]['data'] = str_repeat('*', strlen($value['data']));
				Handler::generateResponse(null, [ 'count' => count($goods), 'items' => Handler::autoTypeConversion($goods) ]);
			}

			if($request['only_deleted'] == true) $goods = R::getAll('SELECT * FROM `goods` WHERE `deleted` = ?', [ 0 ]);
			else $goods = R::getAll('SELECT * FROM `goods`');


			if($goods == null) Handler::generateResponse([ Constants::getErrorsKey('server_error'), Constants::getErrors('server_error', [ 'no goods in database' ]) ]);

			if($user['admin'] < 3) foreach($goods as $key => $value) $goods[$key]['data'] = str_repeat('*', strlen($value['data']));
			Handler::generateResponse(null, [ 'count' => count($goods), 'items' => Handler::autoTypeConversion($goods) ]);
		}
	}
