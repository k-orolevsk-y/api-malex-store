<?php
	namespace Korolevsky\Methods\Products;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class GetAll implements ApiInterface {

		/**
		 * ProductsGetAll Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			if($request['offset'] != null) {
				if(!is_numeric($request['offset']) || $request['offset'] < 0) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'offset is invalid.' ]) ]);
			}
			if($request['count'] != null) {
				if(!is_numeric($request['count']) || $request['count'] < 0) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'count is invalid.' ]) ]);
			}

			if($request['offset'] != null) $offset = (int) $request['offset'];
			else $offset = 0;

			if($request['count'] != null) $count = (int) $request['count'];
			else $count = 20;


			if($request['deleted'] == 1) {
				if($request['reverse'] == true) $products = R::getAll('SELECT * FROM `products` ORDER BY `id` DESC LIMIT ? OFFSET ?', [ $count, $offset ]);
				else $products = R::getAll('SELECT * FROM `products` LIMIT ? OFFSET ?', [ $count, $offset ]);
			} else {
				if($request['reverse'] == true) $products = R::getAll('SELECT * FROM `products` WHERE `deleted` = ? ORDER BY `id` DESC LIMIT ? OFFSET ?', [ 0, $count, $offset ]);
				else $products = R::getAll('SELECT * FROM `products` WHERE `deleted` = ? LIMIT ? OFFSET ?', [ 0, $count, $offset ]);
			}

			foreach($products as $key => $product) {
				$products[$key] = array_slice($products[$key], 0, count($products[$key])-1);

				if($products[$key]['img'] != null) $products[$key]['img'] = 'https://api.malex-store.ru/files.get?' . http_build_query([ 'id' => $product['id'], 'type' => 'product_image' ]);
				if(mb_strlen($products[$key]['description']) > 70) $products[$key]['description_abbreviated'] = mb_strcut($product['description'], 0, 60) . '...';
				$products[$key]['count'] = R::count('goods', 'WHERE `product_id` = ? AND `deleted` = ?', [ $product['id'], 0 ]);
				$products[$key]['deleted'] = $product['deleted'];

				if($products[$key]['count'] <= 0 && $request['in_stock']) unset($products[$key]);
			}

			Handler::generateResponse(null, [ 'count' => count($products), 'items' => Handler::autoTypeConversion($products) ]);
		}
	}