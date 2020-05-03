<?php
	namespace Korolevsky\Methods\Goods;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Add implements ApiInterface {

		/**
		 * GoodsAdd Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 2) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method.' ]) ]);

			Handler::parametersValidator([ 'product_id', 'data' ], $request);

			$product = R::findOne('products', 'WHERE `id` = ?', [ $request['product_id'] ]);
			if($product == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'product_id is invalid' ]) ]);

			$goods = R::dispense('goods');
			$goods->product_id = $product['id'];
			$goods->user_id = $user['id'];
			$goods->data = $request['data'];
			$goods->time = time();
			$goods->deleted = 0;
			R::store($goods);

			if($user['admin'] < 3) $goods['data'] = str_repeat('*', strlen($goods['data']));

			Handler::generateResponse(null, [ 'lang' => Constants::getLang('goods_added'), 'new' => Handler::autoTypeConversion($goods->export()) ]);
		}
	}
