<?php

class Net {

	const DEFAULT_COOKIES = ['mobileClientVersion' => '0 (2.1.3)', 'mobileClient' => 'android', 'Steam_Language' => 'english'];

	public static function startSession() {
		$res = Net::doRequest(
			'https://steamcommunity.com/login?oauth_client_id=DE45CD61&oauth_scope=read_profile%20write_profile%20read_client%20write_client',
			self::DEFAULT_COOKIES
		);
		return $res[0];
	}

	private static function buildCookie($cookie) {
		$out = "";
		foreach ($cookie as $k => $c) {
			$out .= "{$k}={$c}; ";
		}
		return $out;
	}

	public static function doRequest($url, $cookies, $post = null, $ref = 'https://steamcommunity.com') {
		// create a new cURL resource
		$ch = curl_init();

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		if (isset($post)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		} else {
			curl_setopt($ch, CURLOPT_POST, 0);
		}
		curl_setopt($ch, CURLOPT_REFERER, $ref);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-With: com.valvesoftware.android.steam.community"));
		curl_setopt($ch, CURLOPT_COOKIE, self::buildCookie($cookies));
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.1.1; en-us; Google Nexus 4 - 4.1.1 - API 16 - 768x1280 Build/JRO03S) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// grab URL and pass it to the browser
		$res = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($res, 0, $header_size);
		$body = substr($res, $header_size);
		// close cURL resource, and free up system resources
		curl_close($ch);
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}
		return [$cookies, $body];
	}
}
