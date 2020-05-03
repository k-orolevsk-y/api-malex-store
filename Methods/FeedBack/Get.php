<?php
	namespace Korolevsky\Methods\FeedBack;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;

	class Get implements ApiInterface {

		/**
		 * FeedBackGet Constructor.
		 *
		 * @param array $request
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token']);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 1) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permission to use this method.' ]) ]);

			Handler::parametersValidator([ 'id' ], $request);

			$question = R::findOne('questions', 'WHERE `id` = ?', [ $request['id'] ]);
			if($question == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'id is invalid.' ]) ]);

			Handler::generateResponse(null, [ 'item' => Handler::autoTypeConversion($question->export()) ]);
		}
	}
