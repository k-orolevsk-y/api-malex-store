<?php
	namespace Korolevsky\Interfaces;

	interface CaptchaInterface {

		/**
		 * Captcha constructor.
		 */
		public function __construct();

		/**
		 * Captcha Handler
		 *
		 * @return mixed
		 */
		public function handler();

	}