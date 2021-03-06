<?php
	namespace Korolevsky\Methods\Products;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Create implements ApiInterface {
		/**
		 * ProductsCreate Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 3) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permission to use this method' ]) ]);

			Handler::parametersValidator([ 'params' ], $request);

			$params = json_decode($request['params'], true);
			if(!$params || !is_array($params)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params (JSON) is invalid.' ]) ]);

			Handler::parametersValidator([ 'name', 'type', 'description', 'price' ], $params);

			$product = R::dispense('products');
			foreach($params as $key => $value) {
				if($key == 'name') {
					if(!is_string($value) || iconv_strlen($value) < 6 || iconv_strlen($value) > 255) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);

					$product->name = $value;
				}

				elseif($key == 'type') {
					if($value != 0 && $value != 1) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);

					$product->type = (int) $value;
				}

				elseif($key == 'img') {
					if($value != null && !is_string($value)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);

					if($value == null) {
						$product->img = null;
						continue;
					}

					$file = R::findOne('files', 'WHERE `id` = ?', [ $value ]);
					if($file == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid. (File not found)' ]) ]);
					if(explode('/', $file['type'])[0] != 'image') Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid. (File not image)' ]) ]);

					$product->img = $file['id'];
				}

				elseif($key == 'price') {
					if(!is_int($value) || $value < 75 || $value > 10000) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);

					$product->price = (int) $value;
				}

				elseif($key == 'description') {
					if(!is_string($value) || iconv_strlen($value) < 35) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is invalid.' ]) ]);

					$product->description = nl2br(strip_tags($value, '<br>'));
				}

				else Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'params[' . $key . '] is incorrect.' ]) ]);
			}

			$product->user_id = $user['id'];
			R::store($product);

			Handler::generateResponse(null, [ 'product' => Handler::autoTypeConversion($product->export()), 'info' => [ 'lang' => Constants::getLang('product_created') ] ]);
		}
	}