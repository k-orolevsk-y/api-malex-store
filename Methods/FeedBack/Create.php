<?php
	namespace Korolevsky\Methods\FeedBack;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Create implements ApiInterface {

		/**
		 * FeedBackCreate Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);
			Handler::parametersValidator([ 'email', 'message' ], $request);

			$user = Handler::getUserByAccessToken($request['access_token']);

			$last_question = R::findOne('questions', 'WHERE `user_id` = ? ORDER BY `time` DESC', [ $user['id'] ]);
			if(time() - $last_question['time'] < 7200) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'the last question was asked recently' ]), 'info' => [ 'lang' => Constants::getLang('answer_asked_recently'), 'time' => 7200 - (time() - $last_question['time']) ] ]);

			if(!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'email is invalid.' ]) ]);
			if(iconv_strlen($request['message']) < 40) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'message is invalid.' ]), 'info' => [ 'lang' => Constants::getLang('small_text', [ Handler::pluralForm(40 - iconv_strlen($request['message']), [ 'символ', 'символа', 'символов' ]) ]) ] ]);

			$question = R::dispense('questions');
			$question->email = $request['email'];
			$question->user_id = $user['id'];
			$question->admin_id = 0;
			$question->time = time();
			$question->status = 0;

			if($request['receipt'] != null) {
				if(!is_numeric($request['receipt'])) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'receipt is invalid.' ]) ]);
				$question->receipt = (int) $request['receipt'];
			} else {
				$question->receipt = 0;
			}

			if($request['product_id'] != null) {
				if(!is_numeric($request['product_id'])) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'product_id is invalid.' ]) ]);
				$question->product_id = (int) $request['product_id'];
			} else {
				$question->product_id = 0;
			}

			$question->message = $request['message'];
			$question->answer = null;
			R::store($question);

			$count = R::count('questions', 'WHERE `status` != ?', [ 2 ]);
			Handler::generateResponse(null, [ 'info' => [ 'lang' => Constants::getLang('question_created', [ $count ]) ] ]);
		}
	}