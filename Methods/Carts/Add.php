<?php
	namespace Korolevsky\Methods\Carts;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Add implements ApiInterface {

		/**
		 * CartsAdd Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);
			Handler::parametersValidator([ 'product_id', 'count' ], $request);

			$user = Handler::getUserByAccessToken($request['access_token']);

			$product = R::findOne('products', 'WHERE `id` = ?', [ $request['product_id'] ]);
			if($product == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'product_id is invalid.' ]) ]);

			$count = R::count('goods', 'WHERE `product_id` = ? AND `deleted` = ?', [ $product['id'], 0 ]);
			if($count <= 0) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'the product is out of stock' ]), 'info' => [ 'lang' => Constants::getLang('product_out_stock') ] ]);
			if($request['count'] > $count) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'count is invalid.' ]), 'count' => (int) $count ]);

			$goods = R::findOne('carts', 'WHERE `user_id` = ? AND `product_id` = ?', [ $user['id'], $product['id'] ]);
			if($goods != null) {
				$goods->count += $request['count'];
				if($goods['count'] > $count) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'count is invalid.' ]), 'counts' => [ 'available_goods' => (int) $count, 'in_cart' => (int) $goods['count'] ] ]);

				R::store($goods);
				Handler::generateResponse();
			}

			$new_goods = R::dispense('carts');
			$new_goods->user_id = $user['id'];
			$new_goods->product_id = $product['id'];
			$new_goods->count = $request['count'];
			R::store($new_goods);

			Handler::generateResponse();
		}
	}
