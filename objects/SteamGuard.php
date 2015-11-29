<?php

class SteamGuard {

	private static $codeTranslations = [50, 51, 52, 53, 54, 55, 56, 57, 66, 67, 68, 70, 71, 72, 74, 75, 77, 78, 80, 81, 82, 84, 86, 87, 88, 89];

	public static function generateSteamGuardCode($secret, $time = null) {
		$time = $time ?: time();

		$sharedSecret = base64_decode($secret);
		$timeStr = pack('N*', 0) . pack('N*', floor($time / 30));
		$code = hash_hmac("sha1", $timeStr, $sharedSecret, true);

		$b = ord($code[19]) & 0xF;
		$codePoint = (ord($code[$b]) & 0x7F) << 24 | (ord($code[$b + 1]) & 0xFF) << 16 | (ord($code[$b + 2]) & 0xFF) << 8 | (ord($code[$b + 3]) & 0xFF);

		$code = '';
		for ($i = 0; $i < 5; ++$i) {
			$code .= chr(self::$codeTranslations[$codePoint % count(self::$codeTranslations)]);
			$codePoint /= count(self::$codeTranslations);
		}

		return [$code, $time];
	}

	public static function getMobileKeyFor($secret, $time, $action = null) {
		$identitySecret = base64_decode($secret);

		$n2 = isset($action) ? min(40, 8 + strlen($action)) : 8;
		$array = $action ? substr($action, 0, 32) : '';
		for ($i = 8; $i > 0; $i--) {
			$array = chr($time & 0xFF) . $array;
			$time >>= 8;
		}

		$code = hash_hmac("sha1", $array, $identitySecret, true);
		return base64_encode($code);
	}
}
