<?php
	namespace Korolevsky\Methods\Goods;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Remove implements ApiInterface {

		/**
		 * GoodsRemove Constructor.
		 *
		 * @param array $request
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permissions to use this method' ]) ]);

			if($request['user_id'] != null) {
				$time = time() - 604800;
				$goods = R::getAll('SELECT * FROM `goods` WHERE `time` > ? AND `deleted` = ?', [ $time, 0 ]);
				if($goods == null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'this user hasn\'t added products in the last 7 days' ]) ]);

				foreach($goods as $item) {
					$item = R::convertToBean('goods', $item);
					$item->deleted = 1;
					R::store($item);
				}

				if(count($goods) <= 20)	Handler::generateResponse(null, [ 'lang' => Constants::getLang('goods_removed_more'), 'count_deleted_goods' => count($goods), 'deleted_goods' => Handler::autoTypeConversion($goods) ]);
				else Handler::generateResponse(null, [ 'lang' => Constants::getLang('goods_removed_more'), 'count_deleted_goods' => count($goods) ]);
			}

			Handler::parametersValidator([ 'id' ], $request);

			$item = R::findOne('goods', 'WHERE `id` = ?', [ $request['id'] ]);
			if($item == null || $item['deleted'] == 1) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'id is invalid.' ]) ]);

			$item->deleted = 1;
			R::store($item);

			Handler::generateResponse(null, [ 'lang' => Constants::getLang('goods_removed'), 'item' => Handler::autoTypeConversion($item->export()) ]);
		}
	}