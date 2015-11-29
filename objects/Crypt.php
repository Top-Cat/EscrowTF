<?php

class Crypt {

	private static $key;

	public static function init() {
		self::$key = base64_decode("YOUR KEY HERE");
	}

	private static function IVSize() {
		return mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
	}

	private static function IV() {
		return mcrypt_create_iv(self::IVSize(), MCRYPT_RAND);
	}

	public static function encrypt($arr, $iv) {
		$out = json_encode($arr);
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::$key, $out, MCRYPT_MODE_CBC, md5($iv)));
	}

	public static function decrypt($str, $iv) {
		$cipher = base64_decode($str);

		$dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::$key, $cipher, MCRYPT_MODE_CBC, md5($iv));
		return json_decode(str_replace("\0", "", $dec));
	}

}
Crypt::init();

