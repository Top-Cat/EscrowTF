<?php

class Post {

	private static $authData;

	public static function run() {
		$result = self::handlePost() ?: [];
		die(json_encode($result));
	}

	private static function handlePost() {
		if (isset($_POST['authdata'])) {
			self::$authData = Crypt::decrypt($_POST['authdata'], $_POST['ekey']);
			if (is_null(self::$authData)) {
				return ['badcrypt' => true];
			}
		}

		return self::handleAction() ?: self::handleSMS() ?: self::handleLogin();
	}

	private static function handleAction() {
		if (!isset($_POST['action'])) return;

		$auth = new Oauth(self::$authData);

		if ($_POST['action'] == 'doconf') {
			$auth->doConf($_POST['id'], $_POST['key'], $_POST['op']);
			$_POST['action'] = 'get_conf';
		}

		if ($_POST['action'] == 'get_code') {
			$code = SteamGuard::generateSteamGuardCode(self::$authData->shared_secret);
			return [
				'code' => $code[0],
				'rcode' => self::$authData->revocation_code,
				'time' => $code[1]
			];
		} elseif ($_POST['action'] == 'get_conf') {
			return ['success' => true, 'conf' => $auth->getConfirmations()];
		} elseif ($_POST['action'] == 'revoke_code') {
			return $auth->revoke();
		}
	}

	private static function handleSMS() {
		if (!isset($_POST['sms'])) return;

		$tries = 0;
		while ($tries < 30) {
			$code = SteamGuard::generateSteamGuardCode(self::$authData->shared_secret);

			$res = Net::doRequest(
				'https://api.steampowered.com/ITwoFactorService/FinalizeAddAuthenticator/v0001',
				[],
				[
					'steamid' => self::$authData->steamid,
					'access_token' => self::$authData->access_token,
					'activation_code' => $_POST['sms'],
					'authenticator_code' => $tries == 0 ? '' : $code[0],
					'authenticator_time' => $code[1]
				],
				'https://steamcommunity.com/mobilelogin?oauth_client_id=DE45CD61&oauth_scope=read_profile%20write_profile%20read_client%20write_client'
			);

			$data = json_decode($res[1]);
			if ($data->response->status == 89) {
				//die("Bad SMS Code");
				break;
			}

			if (!$data->response->success) {
				//die("Fail");
				break;
			}

			if ($data->response->want_more) {
				$_POST['sms'] = '';
				$tries++;
				continue;
			}

                	return ['success' => true];
        	}
		return ['success' => false];
	}

	private static function handleLogin() {
		if (!isset($_POST['login'])) return;

		$user = new User($_POST['user'], $_POST['pass']);

		$cookies = Net::startSession();

		$user
			->setCaptcha($_POST['gid'] ?: -1, $_POST['captcha'] ?: '')
			->setEmailSteamId($_POST['emailsteamid'] ?: '')
			->setTwoFactorCode($_POST['2fa'] ?: '');

		$loginData = $user->doLogin($cookies);
		if (!$loginData['success']) return $loginData;

		$hasPhone = $loginData['oauth']->hasPhone();
		if (!$hasPhone) return ['phone_needed' => true];

		$authData = $loginData['oauth']->addAuthenticator();

		$authData = array_merge($authData, [
			'drift' => time() - $authData['server_time'],
			'access_token' => $oauth->oauth_token,
			'wgtoken' => $oauth->wgtoken,
			'wgtoken_secure' => $oauth->wgtoken_secure,
			'steamid' => $oauth->steamid
		]);

		return [
			'sms_needed' => true,
			'revocation_code' => $authData['revocation_code'],
			'authdata' => Crypt::encrypt($authData, $_POST['key'])
		];
	}

}
