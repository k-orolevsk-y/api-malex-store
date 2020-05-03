<?php
	namespace Korolevsky\Methods;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Confirm implements ApiInterface {

		/**
		 * Confirm Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::parametersValidator([ 'hash' ], $request);

			$hash = R::findOne('hash', 'WHERE `id` = ? AND `deleted` = ?', [ $request['hash'], 0 ]);
			if($hash == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'hash is invalid.' ]) ]);

			$params = json_decode($hash['params'], true);

			$hash->deleted = 1;
			R::store($hash);

			if(time() - $hash['time'] > 300) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'time to use the hash is over.' ]) ]);

			if($params['act'] == 'auth.removeApp') {

				$app = R::findOne('clients', 'WHERE `id` = ?', [ $params['app'] ]);
				if($app == null || $app['deleted'] == 1) header('Location: ' . $params['redirect_uri'] . '?' . http_build_query([ 'error' => 'app_already_removed' ]));

				$app->deleted = 1;
				R::store($app);

				header('Location: ' . $params['redirect_uri'] . '?' . http_build_query([ 'success' => 1 ]));
			}
			if($params['act'] == 'carts.clear') {

				$user = R::findOne('users', 'WHERE `id` = ?', [ $hash['user_id'] ]);
				$cart = R::getAll('SELECT * FROM `carts` WHERE `user_id` = ?', [ $user['id'] ]);
				if($cart == null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'cart is empty' ]) ]);

				R::trashAll(R::convertToBeans('carts', $cart));

				Handler::generateResponse(null, [ 'info' => [ 'lang' => Constants::getLang('cart_cleared') ] ]);
			}
			if($params['act'] == 'products.remove') {

				$product = R::findOne('products', 'WHERE `id` = ?', [ $params['product_id'] ]);
				$product->deleted = 1;
				R::store($product);

				Handler::generateResponse(null, [ 'info' => [ 'lang' => Constants::getLang('product_removed') ] ]);
			}
			if($params['act'] == 'information.set') {

				$info = R::findOne('information', 'WHERE `name` = ?', [ $params['name'] ]);
				$info->value = $params['value'];
				R::store($info);

				Handler::generateResponse();
			}

			Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'hash is invalid.' ]) ]);
		}
	}
