<?php
	namespace Korolevsky\Methods\Files;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Access implements ApiInterface {

		/**
		 * Constructor.
		 *
		 * @param array $request
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);
			Handler::parametersValidator([ 'id' ], $request);

			$file = R::findOne('files', 'WHERE `id` = ?', [ $request['id'] ]);
			if($file == null) Handler::generateResponse([ 1, Constants::getErrors('parameters_error', [ 'id is invalid.' ]) ]);
			if( $file['user_id'] != Handler::getUserByAccessToken($request['access_token'])['id'] ) Handler::generateResponse([ 2, Constants::getErrors('authorization_failed', [ 'this file doesn\'t belong to you.' ]) ]);

			$access_ips = json_decode($file['access_ip'], true);
			if(in_array($_SERVER['REMOTE_ADDR'], $access_ips)) Handler::generateResponse([ 2, Constants::getErrors('invalid_request', [ 'you have access to this file from this IP' ]) ]);

			array_push($access_ips, $_SERVER['REMOTE_ADDR']);

			$file->access_ip = json_encode($access_ips);
			R::store($file);

			Handler::generateResponse(null, [ 'info' => [ 'lang' => Constants::getLang('file_access') ], 'url' => 'https://api.malex-store.ru/files.get?' . http_build_query([ 'id' => $file['id'] ]) ]);
		}
	}
