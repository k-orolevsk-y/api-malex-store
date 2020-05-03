<?php
	namespace Korolevsky\Methods\Files;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class Get implements ApiInterface {

		/**
		 * FilesGet Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::parametersValidator([ 'id' ], $request);

			if($request['type'] == 'product_image') {
				$product = R::findOne('products', 'WHERE `id` = ?', [ $request['id'] ]);
				if($product == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'id is invalid' ]) ]);
				if($product['img'] == null) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'this product don\'t have image' ]) ]);

				$file = R::findOne('files', 'WHERE `id` = ?', [ $product['img'] ]);
				if($file == null) Handler::generateResponse([ Constants::getErrorsKey('server_error'), Constants::getErrors('server_error', [ 'file was deleted from database' ]) ]);
				if(!file_exists('Files/' . date('d.m.Y', $file['time']) . '/' . $file['name'])) Handler::generateResponse([ Constants::getErrorsKey('server_error'), Constants::getErrors('server_error', [ 'file was deleted' ]) ]);

				$type = explode('/', $file['type'])[0];
				header('Content-type: ' . $type);
				readfile('Files/' . date('d.m.Y', $file['time']) . '/' . $file['name']);
				return true;
			}

			$file = R::findOne('files', 'WHERE `id` = ?', [ $request['id'] ]);
			if($file == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'id is invalid' ]) ]);

			$access_ips = json_decode($file['access_ip'], true);
			if(!in_array($_SERVER['REMOTE_ADDR'], $access_ips)) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have access to this file, request it through `files.access`' ]) ]);

			if(!file_exists('Files/' . date('d.m.Y', $file['time']) . '/' . $file['name'])) Handler::generateResponse([ Constants::getErrorsKey('server_error'), Constants::getErrors('server_error', [ 'file was deleted' ]) ]);
			$type = explode('/', $file['type'])[0];

			if($type != 'image' && $type != 'audio' && $type != 'video' || $request['save'] == true) header('Content-Disposition: attachment; filename="' . $file['real_name'] . '.' . pathinfo($file['name'])['extension'] . '" ');
			else header('Content-type: ' . $file['type']);
			readfile('Files/' . date('d.m.Y', $file['time']) . '/' . $file['name']);
		}
	}