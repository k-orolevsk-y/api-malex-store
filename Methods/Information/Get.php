<?php
	namespace Korolevsky\Methods\Information;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class Get implements ApiInterface {

		/**
		 * InformationGet Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			$info = R::getAll('SELECT name,value FROM `information`');

			$return = [];
			foreach($info as $item) $return += [ $item['name'] => $item['value'] ];

			Handler::generateResponse(null, Handler::autoTypeConversion($return));
		}
	}