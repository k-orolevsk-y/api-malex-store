<?php
	namespace Korolevsky\Methods\Carts;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Get implements ApiInterface {

		/**
		 * CartsGet Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);
			$response = [];

			if($request['user_id'] != null) {
				$user = R::findOne('users', 'WHERE `id` = ?', [ $request['user_id'] ]);
				if($user == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'user_id is invalid.' ]) ]);

				$response += [ 'user_id' => (int) $user['id'] ];
			} else {
				$user = Handler::getUserByAccessToken($request['access_token']);
			}
			$cart = R::getAll('SELECT id,product_id,count FROM `carts` WHERE `user_id` = ?', [ $user['id'] ]);

			$cart_price = 0;
			foreach($cart as $key => $item) {
				$product = R::findOne('products', 'WHERE `id` = ?', [ $item['product_id'] ]);
				$item = R::convertToBean('carts', $item);

				if($item['count'] <= 0 || $product == null) {
					R::trash($item);
					unset($cart[$key]);
					continue;
				}

				$count = R::count('goods', 'WHERE `product_id` = ? AND `deleted` = ?', [ $product['id'], 0 ]);
				if($item['count'] > $count) {
					$item->count = $count;
					R::store($item);

					$cart[$key]['count'] = $count;
				}

				$cart[$key]['title'] = $product['name'];

				if($product['img'] == null) $cart[$key]['img'] = null;
				else $cart[$key]['img'] = 'https://api.malex-store.ru/files.get?' . http_build_query([ 'id' => $product['id'], 'type' => 'product_image' ]);

				$cart[$key]['product_price'] = $product['price'];
				$cart[$key]['price'] = $product['price'] * $item['count'];
				$cart_price += $cart[$key]['price'];
			}
			if($cart == null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'cart is empty' ]), 'info' => [ 'lang' => Constants::getLang('cart_empty') ] ]);

			if($request['coupon'] != null) {

				$coupon = R::findOne('coupons', 'WHERE `coupon` = ? AND `deleted` = ?', [ $request['coupon'], 0 ]);
				if($coupon != null) {

					$cart_price = (100 - $coupon['sale']) * ($cart_price / 100) ;
					$response += [ 'coupon' => [ 'name' => $coupon['coupon'], 'sale' => (int) $coupon['sale'] ] ];

				}

			}

			$response += [ 'cart_price' => (int) $cart_price, 'count' => count($cart) , 'items' => Handler::autoTypeConversion($cart) ];
			if($cart_price > $user['money']) $response += [ 'error' => [ 'error_code' => Constants::getErrorsKey('invalid_request'), 'error_msg' => Constants::getErrors('invalid_request', [ 'user no enough money' ]), 'info' => [ 'lang' => Constants::getLang('no_enough_money') ] ] ];

			Handler::generateResponse(null, $response);
		}

	}
