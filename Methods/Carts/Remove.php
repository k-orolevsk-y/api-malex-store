<?php
	namespace Korolevsky\Methods\Carts;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Remove implements ApiInterface {

		/**
		 * CartsRemove Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);
			Handler::parametersValidator([ 'product_id' ], $request);

			$user = Handler::getUserByAccessToken($request['access_token']);

			$cart = R::findOne('carts', 'WHERE `user_id` = ? AND `product_id` = ?', [ $user['id'], $request['product_id'] ]);
			if($cart == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'product_id is invalid.' ]) ]);

			R::trash($cart);
			Handler::generateResponse(null, [ 'lang' => Constants::getLang('item_removed_from_cart') ]);
		}
	}
