<?php
	namespace Korolevsky\Methods\FeedBack;
	@require 'Interfaces/ApiInterface.php';
	@require 'vendor/autoload.php';

	use Korolevsky\Constants;
	use Korolevsky\Handler;
	use Korolevsky\Interfaces\ApiInterface;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\PHPMailer;
	use RedBeanPHP\R;
	use RedBeanPHP\RedException\SQL;

	class Reply implements ApiInterface {
		/**
		 * FeedBackReply Constructor.
		 *
		 * @param array $request
		 *
		 * @throws SQL
		 * @throws Exception
		 */
		public function __construct(array $request) {
			Handler::accessTokenValidator($request['access_token'], true);

			$user = Handler::getUserByAccessToken($request['access_token']);
			if($user['admin'] < 1) Handler::generateResponse([ Constants::getErrorsKey('authorization_failed'), Constants::getErrors('authorization_failed', [ 'you don\'t have permission to use this method.' ]) ]);

			Handler::parametersValidator([ 'id', 'status', 'answer' ], $request);

			$question = R::findOne('questions', 'WHERE `id` = ?', [ $request['id'] ]);
			if($question == null) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'id is invalid.' ]) ]);
			if($question['status'] == 2) Handler::generateResponse([ Constants::getErrorsKey('invalid_request'), Constants::getErrors('invalid_request', [ 'this question has already been answered' ]), 'info' => [ 'lang' => Constants::getLang('question_answered') ] ]);
			if($request['status'] != 1 && $request['status'] != 2) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'status is invalid.' ]) ]);
			if($request['status'] == $question['status']) Handler::generateResponse([ Constants::getErrorsKey('parameters_error'), Constants::getErrors('parameters_error', [ 'can\'t put the status is same.' ]), 'info' => [ 'lang' => Constants::getLang('status_same') ]  ]);


			$mail = new PHPMailer();
			$mail->isSMTP();
			$mail->Host = 'smtp.yandex.ru';
			$mail->SMTPAuth = true;
			$mail->Username = 'email';
			$mail->Password = 'pass';
			$mail->SMTPSecure = 'ssl';
			$mail->Port = 465;
			$mail->setFrom('email');
			$mail->addAddress($question['email']);
			$mail->isHTML();
			$mail->CharSet = 'utf-8';

			$letter = file_get_contents('Files/Letter.html');
			$target = R::findOne('users', 'WHERE `id` = ?', [ $question['user_id'] ]);

			$question->admin_id = $user['id'];
			$question->status = (int) $request['status'];
			$question->answer = $request['answer'];
			R::store($question);

			if($request['status'] == 1) {
				$mail->Subject = 'MALEX-STORE.RU | Рассмотрение вопроса.';
				$mail->Body = $this->letterHandler($letter, [ 'title' => 'Рассмотрение вопроса', 'text' => 'Здравствуйте, ' . $target['name'] .  '!<br>Ваш вопрос только что был принят на рассмотрение Администрацией сайта. К сожалению, на его ответ нам требуется немного больше времени, чем ожидалось. <br> Мы постараемся ответить как можно скорее!', 'text button' => 'Задать новый вопрос', 'url button' => 'https://malex-store.ru/contact.php' ]);
			} else {
				$mail->Subject = 'MALEX-STORE.RU | Ответ на вопрос.';
				$mail->Body = $this->letterHandler($letter, [ 'title' => 'Ответ на вопрос', 'text' => $request['answer'] . '<br><br>С уважением команда Администрации сайта MALEX-STORE.RU', 'text button' => 'Перейти на сайт', 'url button' => 'https://malex-store.ru/' ]);
			}

			if(!$mail->send()) {
				$letter_send = false;
				$error_letter = [ 'letter_error' => $mail->ErrorInfo ];
			} else {
				$letter_send = true;
				$error_letter = [];
			}

			Handler::generateResponse(null, [ 'info' => [ 'letter_send' => $letter_send, 'lang' => Constants::getLang('information_save') ] + $error_letter ]);
		}


		private function letterHandler(string $letter, array $replacement): string {
			foreach($replacement as $key => $value) $letter = str_replace('%' . $key . '%', $value, $letter);

			return $letter;
		}
	}
