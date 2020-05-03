<?php
	namespace Korolevsky\Methods\Carts;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Clear implements ApiInterface {

		/**
		 * CartsClear Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			$cart = R::getAll('SELECT * FROM `carts` WHERE `user_id` = ?', [ $user['id'] ]);
			if($cart == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'cart is empty.' ]) ]);

			$hash = bin2hex(random_bytes(12));
			R::exec('INSERT INTO hash(`id`) VALUES(\'' . $hash . '\')');
			$nHash = R::dispense('hash');
			$nHash->id = $hash;
			$nHash->user_id = $user['id'];
			$nHash->time = time();
			$nHash->params = json_encode([ 'act' => 'carts.clear' ]);
			R::store($nHash);

			Handler::generateResponse([ Constants::getErrorsKey('validation_required'), Constants::getErrors('validation_required', [ 'please open redirect_uri' ]), 'redirect_uri' => 'https://api.malex-store.ru/confirm?' . http_build_query([ 'hash' => $hash ]) ]);
		}
	}