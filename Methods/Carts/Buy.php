<?php
	namespace Korolevsky\Methods\Carts;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Buy implements ApiInterface {

		/**
		 * CartsBuy Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			$cart = R::getAll('SELECT * FROM `carts` WHERE `user_id` = ?', [ $user['id'] ]);
			if($cart == null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'cart is empty' ]), 'info' => [ 'lang' => Constants::getLang('cart_empty') ] ]);

			$goods = [];
			$cart_price = 0;
			foreach($cart as $item) {
				$product = R::findOne('products', 'WHERE `id` = ?', [ $item['product_id'] ]);
				$count = R::count('goods', 'WHERE `product_id` = ? AND `deleted` = ?', [ $item['product_id'], 0 ]);

				if($item['count'] > $count) {
					$item['count'] = $count;
					R::store(R::convertToBean('carts', $item));
				}

				if($product == null || $item['count'] <= 0) {
					R::trash(R::convertToBean('carts', $item));
					continue;
				}

				$goods += [ $item['product_id'] => (int) $item['count'] ];
				$cart_price += $item['count'] * $product['price'];
			}

			if($request['coupon'] != null) {
				$coupon = R::findOne('coupons', 'WHERE `coupon` = ? AND `deleted` = ?', [ $request['coupon'], 0 ]);
				if($coupon != null) $cart_price = (100 - $coupon['sale']) * ($cart_price / 100) ;
			}

			if($cart_price > $user['money']) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'you don\'t have enough money' ]), 'info' => [ 'your_money' => (int) $user['money'], 'cart_price' => $cart_price, 'lang' => Constants::getLang('no_enough_money') ] ]);

			$user['money'] -= $cart_price;
			R::store(R::convertToBean('users', $user));

			$bought_response = [];
			foreach($goods as $id => $count) {

				$b_goods = R::getAll('SELECT * FROM `goods` WHERE `product_id` = ? AND `deleted` = ?', [ $id, 0 ]);
				foreach($b_goods as $good) {
					$bought = R::dispense('bought');
					$bought->user_id = $user['id'];
					$bought->product_id = $good['product_id'];
					$bought->time = time();
					$bought->data = $good['data'];
					R::store($bought);

					array_push($bought_response, [ 'product_id' => $good['product_id'], 'data' => $good['data'], 'time' => $bought->time ]);

					$good = R::convertToBean('goods', $good);
					$good['deleted'] = 1;
					R::store($good);
				}

			}

			R::trashAll(R::convertToBeans('carts', $cart));

			Handler::generateResponse(null, [ 'bought' => Handler::autoTypeConversion($bought_response), 'info' => [ 'lang' => Constants::getLang('goods_bought') ] ]);
		}
	}
