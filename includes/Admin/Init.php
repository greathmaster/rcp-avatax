<?php

namespace RCP_Avatax\Admin;

class Init {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \RCP_Avatax\Init
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Init ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		$this->includes();
	}

	protected function includes() {
		License::get_instance();
		Settings::get_instance();
		Levels::get_instance();
	}

}