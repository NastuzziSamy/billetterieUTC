<?php

class CURL {
	protected $ch;
	protected $cookies;

	public function __construct()	{
		$this->ch = curl_init();
		$this->cookies = array();

		curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);

		if (strpos($_SERVER['HTTP_HOST'], 'utc.fr')) {
			curl_setopt($this->ch, CURLOPT_PROXY, 'proxyweb.utc.fr');
			curl_setopt($this->ch, CURLOPT_PROXYPORT, '3128');
		}
	}

	protected function execute() {
		$cookie = array();

		foreach($this->cookies as $key => $value ) {
		  $cookie[] = "{$key}={$value}";
		};

		$cookie = implode('; ', $cookie);

		curl_setopt($this->ch, CURLOPT_COOKIE, $cookie);
		$result = curl_exec($this->ch);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);

    $this->cookies = array();
    foreach($matches[1] as $item) {
      parse_str($item, $cookie);

      $this->cookies = array_merge($this->cookies, $cookie);
    }

		return $result;
	}

	public function get($url) {
		curl_setopt($this->ch, CURLOPT_URL, $url);
		return $this->execute();
	}

	public function post($url, $params)	{
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));
		return $this->execute();
	}

	public function error()	{
		return curl_error($this->ch);
	}
}
?>
