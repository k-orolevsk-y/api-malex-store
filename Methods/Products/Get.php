<?php
	namespace Korolevsky\Methods\Products;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class Get implements ApiInterface {

		/**
		 * ProductsGet Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::parametersValidator([ 'ids' ], $request);


			$ids = explode(',', str_replace([ ';', ' ' ], [ ',', '' ], $request['ids']));
			if(count($ids) > 200) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'max count of requested ids is 200.' ]) ]);

			$products = [];
			foreach($ids as $id) {
				$product = R::findOne('products', 'WHERE `id` = ?', [ $id ]);
				if($product == null) {
					array_push($products, null);
					continue;
				}

				$push_product = array_slice($product->export(), 0, count($product)-1);

				if($product['img'] != null) $push_product['img'] = 'https://api.malex-store.ru/files.get?' . http_build_query([ 'id' => $product['id'], 'type' => 'product_image' ]);
				if(mb_strlen($product['description']) > 70) $push_product['description_abbreviated'] = mb_strcut($product['description'], 0, 60) . '...';
				$push_product['count'] = R::count('goods', 'WHERE `product_id` = ? AND `deleted` = ?', [ $product['id'], 0 ]);
				$push_product['deleted'] = $product['deleted'];

				array_push($products, Handler::autoTypeConversion($push_product));
			}

			if(array_diff($products, [ null ]) == null) Handler::generateResponse([ 1, Constants::getErrors('parameters_error', [ 'ids is invalid' ]) ]);

			Handler::generateResponse(null, [ 'count' => count($products), 'items' => $products ]);
		}
	}