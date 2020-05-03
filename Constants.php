<?php
	namespace Korolevsky;

	class Constants {

		private const ERRORS = [
			'error_not_found' => 'The requested API error/lang (%s) was not found! Report this bug by email: api@malex-store.ru',
			'authorization_failed' => 'Authorization failed: %s',
			'error_method' => 'Error getting method: %s.',
			'db_problem' => 'Error connecting to database, try retrying query.',
			'parameters_error' => 'One of the parameters specified was missing or invalid: %s',
			'invalid_request' => 'Invalid request: %s.',
			'invalid_client_data' => 'Invalid client data: %s.',
			'server_error' => 'Server error: %s.',
			'validation_required' => 'Validation required: %s.',
			'captcha_need' => 'Captcha need: %s.',
			'captcha_error' => 'Captcha error: %s.',
			'security_error' => 'Security error: %s.'
		];

		private const ERRORS_KEY = [
			'authorization_failed' => 913,
			'error_method' => 901,
			'db_problem' => 902,
			'parameters_error' => 903,
			'invalid_request' => 904,
			'invalid_client_data' => 905,
			'server_error' => 906,
			'validation_required' => 907,
			'captcha_need' => 908,
			'captcha_error' => 909,
			'security_error' => 910
		];

		private const LANG = [
			'information_save' => 'Информация сохранена!',
			'file_access' => 'Вам выдан доступ к файлу с этого IP.',
			'goods_added' => 'Товар был успешно добавлен!',
			'goods_removed_more' => 'Товары были удалены!',
			'goods_removed' => 'Товар был удалён!',
			'app_created' => 'Приложение успешно создано! Запомните данные от него (access_token, secret), потому что их невозможно восстановить!',
			'app_reset' => 'Данные сброшены. Запомните access_token и secret, т.к. их невозможно восстановить!',
			'cart_empty' => 'Ваша корзина пуста. Давайте заполним её!',
			'cart_cleared' => 'Вы очистили свою корзину!',
			'item_removed_from_cart' => 'Товар был удалён из корзины!',
			'account_banned' => 'Ваш аккаунт был заблокирован Администрацией сайта!',
			'max_amount_give' => 'Данному пользователю уже выдана максимальная сумма за день!',
			'be_max_amount_give' => 'Выданная сумма будет станет выше той, которую можно выдать!',
			'reason_small' => 'Причина должна быть как минимум из 6 символов!',
			'user_has_banned' => 'Пользователь уже заблокирован!',
			'user_has_not_banned' => 'Пользователь не заблокирован!',
			'security_error_captcha' => 'К сожалению, в последнее время Вы слишком часто игнорировали прохождение капчи и мы были вынуждены временно ограничить доступ.',
			'no_enough_money' => 'У Вас недостаточно средств на счету!',
			'goods_bought' => 'Товары успешно куплены!',
			'product_out_stock' => 'Данного товара нет в наличии!',
			'coupon_created' => 'Купон успешно создан!',
			'small_text' => 'Данное сообщение слишком маленькое. Введите ещё %s!',
			'question_created' => 'Ваш вопрос успешно зарегестрирован под номером %s. Администратция сайта ответит на него в близжайшее время.',
			'answer_asked_recently' => 'Вы недавно уже создавали вопрос. Пожалуйста, подождите перед тем как создавать новый!',
			'question_answered' => 'На данный вопрос уже есть ответ!',
			'status_same' => 'У вопроса уже и так стоит этот статус!',
			'product_removed' => 'Продукт был успешно удалён!',
			'product_created' => 'Продукт успешно создан!'
		];

		private const METHODS  = [
			'authorization' => 'Authorization',
			'access_token' => 'AccessToken',
			'confirm' => 'Confirm',
			'captcha' => 'Captcha',
			'users.ban' => 'Users/Ban',
			'users.edit' => 'Users/Edit',
			'users.get' => 'Users/Get',
			'users.logout' => 'Users/Logout',
			'users.unBan' => 'Users/UnBan',
			'money.add' => 'Money/Add',
			'money.set' => 'Money/Set',
			'files.upload' => 'Files/Upload',
			'files.access' => 'Files/Access',
			'files.get' => 'Files/Get',
			'products.create' => 'Products/Create',
			'products.edit' => 'Products/Edit',
			'products.get' => 'Products/Get',
			'products.getAll' => 'Products/GetAll',
			'products.remove' => 'Products/Remove',
			'goods.get' => 'Goods/Get',
			'goods.add' => 'Goods/Add',
			'goods.remove' => 'Goods/Remove',
			'auth.getScopes' => 'Auth/GetScopes',
			'auth.createApp' => 'Auth/CreateApp',
			'auth.getApp' => 'Auth/GetApp',
			'auth.resetApp' => 'Auth/ResetApp',
			'auth.editApp' => 'Auth/EditApp',
			'auth.getApps' => 'Auth/GetApps',
			'auth.removeApp' => 'Auth/RemoveApp',
			'logs.get' => 'Logs/Get',
			'carts.get' => 'Carts/Get',
			'carts.add' => 'Carts/Add',
			'carts.clear' => 'Carts/Clear',
			'carts.remove' => 'Carts/Remove',
			'carts.buy' => 'Carts/Buy',
			'coupons.create' => 'Coupons/Create',
			'coupons.edit' => 'Coupons/Edit',
			'coupons.get' => 'Coupons/Get',
			'coupons.getAll' => 'Coupons/GetAll',
			'coupons.remove' => 'Coupons/Remove',
			'feedback.get' => 'FeedBack/Get',
			'feedback.getAll' => 'FeedBack/GetAll',
			'feedback.create' => 'FeedBack/Create',
			'feedback.reply' => 'FeedBack/Reply',
			'information.get' => 'Information/Get',
			'information.set' => 'Information/Set'
		];

		private function __construct() {}

		public static function getErrors(string $key, ?array $replacement = null): string {
			$error = static::ERRORS[$key];
			if($error == null) return Constants::getErrors('error_not_found', [ $key ]);

			if($replacement != null) foreach($replacement as $value) $error = preg_replace('/%s/', $value, $error, 1);

			return $error;
		}

		public static function getMethod(string $method): ?string {
			return static::METHODS[$method];
		}

		public static function getLang(string $key, ?array $replacement = null): string {
			$lang = static::LANG[$key];
			if($lang == null) return Constants::getErrors('error_not_found', [ $key ]);
			if($replacement != null) foreach($replacement as $value) $lang = preg_replace('/%s/', $value, $lang, 1);

			return $lang;
		}

		public static function getErrorsKey(string $key): int {
			$error_key = static::ERRORS_KEY[$key];
			if($error_key == null) return 0;

			return $error_key;
		}

	}
