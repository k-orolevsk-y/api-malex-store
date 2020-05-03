<?php
	namespace Korolevsky\Methods\Auth;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class GetApps implements ApiInterface {


		/**
		 * AuthGetApps Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if ($user['admin'] < 2) Handler::generateResponse([Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', ['you don\'t have permissions to use this method'])]);

			$apps = R::getAll('SELECT * FROM `clients`');
			foreach ($apps as $key => $app) {
				if($app['user_by'] == $user['id'] && !$app['deleted']) $apps[$key]['can_edit'] = true;

				$apps[$key]['access_token'] = str_repeat('*', 26);
				$apps[$key]['secret'] = str_repeat('*', 26);

				$apps[$key]['scope'] = R::getAll('SELECT `key` FROM `scopes` WHERE `id` IN (' . str_replace('.', ',', $app['scope']) . ')');
			}

			Handler::generateResponse(null, [ 'count' => count($apps), 'apps' => Handler::autoTypeConversion($apps) ]);
		}
	}
