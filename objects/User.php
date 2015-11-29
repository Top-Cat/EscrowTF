<?php

class User {

	private $user;
	private $pass;

	private $captchaGid = -1;
	private $captchaText = '';
	private $emailSteamId = '';
	private $twoFactorCode = '';

	private $RSATimestamp = 0;

	private $loginData;

	public function __construct($user, $pass) {
		$this->user = $user;
		$this->pass = $pass;
	}

	public function setCaptcha($gid, $text) {
		$this->captchaGid = $gid;
		$this->captchaText = $text;
		return $this;
	}

	public function setEmailSteamId($id) {
		$this->emailSteamId = $id;
		return $this;
	}

	public function setTwoFactorCode($code) {
		$this->twoFactorCode = $code;
		return $this;
	}

	private function getRSAKey() {
		$res = Net::doRequest(
			'https://steamcommunity.com/login/getrsakey/',
			[],
			['username' => $this->user]
		);
		return json_decode($res[1]);
	}

	private function getEncryptedPassword() {
		$key = $this->getRSAKey();

		$rsa = new Crypt_RSA();
		$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$rsa->loadKey([
			'n' => new Math_BigInteger($key->publickey_mod, 16),
			'e' => new Math_BigInteger($key->publickey_exp, 16)
		]);

		return ['code' => base64_encode($rsa->encrypt($this->pass)), 'time' => $key->timestamp];
	}

	public function doLogin($cookies) {
		$encPass = $this->getEncryptedPassword();

		$res = Net::doRequest(
			'https://steamcommunity.com/login/dologin/',
			$cookies,
			[
				'username' => $this->user,
				'password' => $encPass['code'],
				'twofactorcode' => '',
				'captchagid' => $this->captchaGid,
				'captcha_text' => $this->captchaText,
				'emailsteamid' => $this->emailSteamId,
				'emailauth' => $this->twoFactorCode,
				'rsatimestamp' => $encPass['time'],
				'remember_login' => false,
				'oauth_client_id' => 'DE45CD61',
				'oauth_scope' => 'read_profile write_profile read_client write_client',
				'loginfriendlyname' => 'escrow.tf'
			]
		);

		$this->loginData = json_decode($res[1], true);
		if (isset($this->loginData['oauth'])) $this->loginData['oauth'] = new Oauth(json_decode($this->loginData['oauth']));

		// Get steam to send an email even when a bad code is supplied
		if (!$loginData['success'] && isset($loginData['emailauth_needed']) && $loginData['emailauth_needed'] && !empty($this->twoFactorCode)) {
			$this->setTwoFactorCode('');
			return $this->doLogin($cookies);
		}

		return $this->loginData;
	}

}
