<?php
include_once "ressources/mdp.php";
include_once "ressources/php/class/curl.php";

class PAYUTC extends CURL {
  private $header;
  private $body;
  private $url = 'https://api.nemopay.net/services/';

  public function __construct () {
    parent::__construct(false);

    $this->connect();
	}

  protected function connect() {
    $code = $this->request(
      'WEBSALE',
      'loginApp',
      array(
        'key' => NEMOPAY_KEY
      )
    );

    if ($code != 200)
      $this->errorFatal('connect', $code);

    return json_decode($this->body, true);
  }

  public function createTransaction($items, $email, $returnUrl) {
    $code = $this->request(
      'WEBSALE',
      'createTransaction',
      array(
        'items' => json_encode($items),
        'fun_id' => NEMOPAY_FUN_ID,
        'mail' => $email,
        'return_url' => $returnUrl
      )
    );

    if ($code != 200)
      $this->errorFatal('createTransaction', $code);

    return json_decode($this->body, true);
  }

  public function getTransactionInfo($idTransaction) {
    $code = $this->request(
      'WEBSALE',
      'getTransactionInfo',
      array(
        'fun_id' => NEMOPAY_FUN_ID,
        'tra_id' => $idTransaction,
      )
    );

    if ($code != 200)
      $this->errorFatal('createTransaction', $code);

    return json_decode($this->body, true);
  }

  public function getTransactionUrl() {
    return 'https://payutc.nemopay.net/validation?tra_id=';
  }

  protected function request($service, $option, $params) {
    $response = parent::post($this->url.$service.'/'.$option.'?system_id=payutc', $params);

    $http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);

    $this->header = substr($response, 0, $header_size);
    $this->body = substr($response, $header_size);

    return $http_code;
  }

	public function errorFatal($option, $http_code)	{
		$signature = date('Y/m/d H:i:s').'	'.$_SERVER['REMOTE_ADDR'];
    mail('samy.nastuzzi@etu.utc.fr', 'Erreur avec nemopay: '.$option.' - erreur: '.$http_code, $this->header.PHP_EOL.PHP_EOL.$this->body);
    echo 'Erreur avec nemopay: '.$option.' - erreur: '.$http_code.'<br />';
    echo $this->header.PHP_EOL.PHP_EOL.$this->body;
		echo 'Une erreur a été détectée et a été signalée.';
    exit;
	}
}
