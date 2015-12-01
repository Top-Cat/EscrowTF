<?php

class Oauth {

	private $oauth;

	public function __construct($loginData) {
		$this->oauth = $loginData;
	}

	private function generateCookies() {
		// We can ditch the session now we have completed oauth
		return array_merge(Net::DEFAULT_COOKIES, [
			'steamid' => $this->oauth->steamid,
			'steamLogin' => $this->oauth->steamid.'%7C%7C'.$this->oauth->wgtoken,
			'steamLoginSecure' => $this->oauth->steamid.'%7C%7C'.$this->oauth->wgtoken_secure,
			'dob' => ''
		]);
	}

	public function hasPhone() {
		$res = Net::doRequest(
			'https://steamcommunity.com/steamguard/phoneajax',
			$this->generateCookies(),
			[
				'op' => 'has_phone',
				'arg' => 'null'
			],
			'https://steamcommunity.com/mobilelogin?oauth_client_id=DE45CD61&oauth_scope=read_profile%20write_profile%20read_client%20write_client'
		);

		$phoneData = json_decode($res[1]);
		return $phoneData->has_phone;
	}

	public function getConfirmations() {
		$time = time() - $this->getDrift();
		$key = urlencode(SteamGuard::getMobileKeyFor($this->oauth->identity_secret, $time, 'conf'));

		$res = Net::doRequest(
			"https://steamcommunity.com/mobileconf/conf?p=escrowtf:{$this->oauth->steamid}&a={$this->oauth->steamid}&k={$key}&t={$time}&m=android&tag=conf",
			$this->generateCookies()
		);

		$out = [];
		$doc = new DOMDocument();
		$doc->loadHTML($res[1]);
		$list = $doc->getElementById('mobileconf_list');
		if (isset($list)) {
			foreach ($list->childNodes as $elem) {
				if ($elem->tagName == 'div' && $elem->getAttribute('class') == 'mobileconf_list_entry') {
					$id = $elem->getAttribute('data-confid');
					$obj = ['id' => $id, 'key' => $elem->getAttribute('data-key'), 'desc' => 'Confirmation id '.$id];
					$next = false;
					foreach ($elem->getElementsByTagName('div') as $child) {
						if ($child->getAttribute('class') == 'mobileconf_list_entry_description') {
							$next = true;
						} elseif ($next) {
							$obj['desc'] = $child->textContent;
							break;
						}
					}
					$out[] = $obj;
				}
			}
		}

		return $out;
	}

	public function revoke() {
		$res = Net::doRequest(
			'https://api.steampowered.com/ITwoFactorService/RemoveAuthenticator/v0001',
			[],
			[
				'steamid' => $this->oauth->steamid,
				'steamguard_scheme' => 2,
				'revocation_code' => $this->oauth->revocation_code,
				'access_token' => $this->oauth->access_token
			]
		);
		$data = json_decode($res[1]);
		return $data->response;
	}

	public function doConf($id, $key, $op) {
		$time = time() - $this->getDrift();
		$authKey = urlencode(SteamGuard::getMobileKeyFor($this->oauth->identity_secret, $time, $op));
		$res = Net::doRequest(
			"https://steamcommunity.com/mobileconf/ajaxop?op={$op}&p=escrowtf:{$this->oauth->steamid}&a={$this->oauth->steamid}&k={$authKey}&t={$time}&m=android&tag={$op}&cid={$id}&ck={$key}",
			$this->generateCookies()
		);
	}

	public function getDrift() {
		$res = Net::doRequest(
			'https://api.steampowered.com/ITwoFactorService/QueryTime/v0001',
			[],
			['steamid' => 0]
		);

		$out = json_decode($res[1], true);
		return time() - intval($out['response']['server_time']);
	}

	public function addAuthenticator() {
		$res = Net::doRequest(
			'https://api.steampowered.com/ITwoFactorService/AddAuthenticator/v0001',
			[],
			[
				'steamid' => $this->oauth->steamid,
				'access_token' => $this->oauth->oauth_token,
				'authenticator_type' => 1,
				'device_identifier' => 'escrowtf:'.$this->oauth->steamid,
				'sms_phone_id' => 1
			],
			'https://steamcommunity.com/mobilelogin?oauth_client_id=DE45CD61&oauth_scope=read_profile%20write_profile%20read_client%20write_client'
		);

		$out = json_decode($res[1], true);
		return $out['response'];
	}
}
