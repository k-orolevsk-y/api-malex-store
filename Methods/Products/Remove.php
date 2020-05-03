<?php
	namespace Korolevsky\Methods\Products;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Remove implements ApiInterface {

		/**
		 * ProductsRemove Constructor.
		 *
		 * @param array $request
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permission to use this method' ]) ]);

			Handler::parametersValidator([ 'id' ], $request);

			$product = R::findOne('products', 'WHERE `id` = ? AND `deleted` = ?', [ $request['id'], 0 ]);
			if($product == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'id is invalid.' ]) ]);

			$hash = bin2hex(random_bytes(12));
			R::exec('INSERT INTO hash(`id`) VALUES (\'' . $hash . '\')');
			$nHash = R::dispense('hash');
			$nHash->id = $hash;
			$nHash->user_id = $user['id'];
			$nHash->time = time();
			$nHash->params = json_encode([ 'act' => 'products.remove', 'product_id' => $product['id'] ]);
			R::store($nHash);

			Handler::generateResponse([ Constants::getErrorsKey('validation_required'), Constants::getErrors('validation_required', [ 'please open redirect_uri' ]), 'redirect_uri' => 'https://api.malex-store.ru/confirm?' . http_build_query([ 'hash' => $hash ])  ]);
		}
	}
