<?php
	namespace Korolevsky\Methods\Users;
	@require 'vendor/autoload.php';
	@require 'Interfaces/ApiInterface.php';

	use Korolevsky\Constants;
	use Exception;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Get implements ApiInterface {

		/**
		 * UsersGet Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$fields = 'id,name,vkid,steamid,money,banned,';
			if($request['fields'] != null) {
				$request['fields'] = str_replace([ ';', ' '  ], [ ',', '' ], $request['fields']);
				if(mb_strcut($request['fields'], strlen($request['fields'])-1) == ',') $request['fields'] = mb_strcut($request['fields'], 0, -1);

				foreach(explode(',', $request['fields']) as $val) {
					try {
						$check = R::getAll('SELECT ' . $val . ' FROM `users`');
					} catch(Exception $exception) {
						Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'fields is invalid.' ]) ]);
					}

					if($check == null) {
						Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'field `' . $val . '` is invalid.' ]) ]);
					}
				}

				$fields .= $request['fields'];
			} else $fields = mb_strcut($fields, 0, -1);


			if($request['user_ids'] == null) {
				$user = Handler::getUserByAccessToken($request['access_token'], $fields);
				if($user == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'fields is invalid.' ]) ]);

				$cart = R::getAll('SELECT product_id,count FROM `carts` WHERE `user_id` = ?', [ $user['id'] ]);
				$user['cart'] = [ 'count' => count($cart), 'items' => $cart ];

				$bought = R::getAll('SELECT product_id,data,time FROM `bought` WHERE `user_id` = ?', [ $user['id'] ]);
				$user['bought'] = [
					'count' => count($bought),
					'items' => $bought
				];

				$auth_history = R::getAll('SELECT client_id,ip,browser,device,time FROM `auth_history` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT ?', [ $user['id'], 5 ]);
				foreach($auth_history as $key => $item) {
					$client = R::findOne('clients', 'WHERE `id` = ?', [ $item['client_id'] ]);
					$auth_history[$key]['client_name'] = $client['name'];
				}

				$user['auth_history'] = array_reverse($auth_history);

				Handler::generateResponse(null, [ 'count' => 1,  'items' => [ Handler::autoTypeConversion($user) ] ]);
			}

			$user_ids = explode(',', str_replace([ ' ', ';' ], [ '', ','], $request['user_ids']));
			if(count($user_ids) > 200) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'max count of requested user_ids is 200.' ]) ]);

			$items = [];
			foreach($user_ids as $id) {
				try {
					$user = R::getAll('SELECT ' . $fields . ' FROM `users` WHERE `id` = ?', [ $id ])[0];
				} catch(Exception $exception) {
					Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'fields is invalid.' ]) ]);
				}

				$access_token_user = Handler::getUserByAccessToken($request['access_token']);

				if($user['id'] == $access_token_user['id'] || $access_token_user['admin'] > 1) {
					$cart = R::getAll('SELECT product_id,count FROM `carts` WHERE `user_id` = ?', [ $user['id'] ]);
					$user['cart'] = [ 'count' => count($cart), 'items' => $cart ];

					$bought = R::getAll('SELECT product_id,data,time FROM `bought` WHERE `user_id` = ?', [ $user['id'] ]);
					$user['bought'] = [
						'count' => count($bought),
						'items' => $bought
					];

					$auth_history = R::getAll('SELECT client_id,ip,browser,device,time FROM `auth_history` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT ?', [ $user['id'], 5 ]);
					foreach($auth_history as $key => $item) {
						$client = R::findOne('clients', 'WHERE `id` = ?', [ $item['client_id'] ]);
						$auth_history[$key]['client_name'] = $client['name'];
					}

					$user['auth_history'] = array_reverse($auth_history);
				}

				array_push($items, $user);
			}

			if($items == null || array_diff($items, array(null)) == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'user_ids' ]) ]);

			Handler::generateResponse(null, [ 'count' => count($items),  'items' => Handler::autoTypeConversion($items) ]);
		}

	}