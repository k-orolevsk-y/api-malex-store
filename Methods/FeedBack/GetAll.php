<?php
	namespace Korolevsky\Methods\FeedBack;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class GetAll implements ApiInterface {

		/**
		 * FeedBackGetAll Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 1) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permission to use this method.' ]) ]);

			if($request['reverse']) $questions = R::getAll('SELECT * FROM `questions` ORDER BY `id` DESC');
			else $questions = R::getAll('SELECT * FROM `questions`');

			Handler::generateResponse(null, [ 'count' => count($questions), 'items' => Handler::autoTypeConversion($questions) ]);
		}
	}