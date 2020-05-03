<?php
	namespace Korolevsky\Methods\Files;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Upload implements ApiInterface {

		/**
		 * FileUpload Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$files = $_FILES;
			if($files == null) Handler::generateResponse([ 1, Constants::getErrors('parameters_error', [ 'files is undefined.' ]) ]);

			$uploaded_files = [];
			foreach ($files as $file) {

				$id = bin2hex(random_bytes(8));
				R::exec('INSERT INTO files(`id`) VALUES (\'' . $id . '\')');
				$upload = R::dispense('files');
				$upload->id = $id;
				$upload->name = bin2hex(random_bytes(8)) . '.' . pathinfo($file['name'])['extension'];
				$upload->real_name = pathinfo($file['name'])['filename'];
				$upload->type = $file['type'];
				$upload->user_id = Handler::getUserByAccessToken($request['access_token'])['id'];
				$upload->time = time();
				$upload->access_ip = json_encode([ $_SERVER['REMOTE_ADDR'] ]);
				R::store($upload);

				if(!is_dir('Files/' . date('d.m.Y'))) mkdir('Files/' . date('d.m.Y'));

				move_uploaded_file($file['tmp_name'], 'Files/' . date('d.m.Y') . '/' . $upload['name']);
				array_push($uploaded_files, [ 'id' => $upload['id'], 'name' => $file['name'], 'type' => $file['type'], 'url' => 'https://api.malex-store.ru/files.get?' . http_build_query([ 'id' => $upload['id'] ]) ]);

			}

			Handler::generateResponse(null, [ 'count' => count($uploaded_files), 'files' => $uploaded_files ]);
		}
	}