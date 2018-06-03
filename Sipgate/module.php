<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

if (!defined('IPS_BOOLEAN')) {
    define('IPS_BOOLEAN', 0);
}
if (!defined('IPS_INTEGER')) {
    define('IPS_INTEGER', 1);
}
if (!defined('IPS_FLOAT')) {
    define('IPS_FLOAT', 2);
}
if (!defined('IPS_STRING')) {
    define('IPS_STRING', 3);
}

class Sipgate extends IPSModule
{
	use SipgateCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('user', '');
        $this->RegisterPropertyString('password', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

        $ok = true;
        if ($user == '' || $password == '') {
            $ok = false;
        }
        $this->SetStatus($ok ? 102 : 201);
    }

    public function TestAccount()
    {
		$cdata = $this->do_ApiCall('/account', '', true);
		if ($cdata == '') {
			echo $this->Translate('invalid account-data');
			return;
		}
		$jdata = json_decode($cdata, true);
		$this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

		// cdata={"company":"","mainProductType":"BASIC","logoUrl":"","verified":true}
		$company = $jdata['company'];

		$cdata = $this->do_ApiCall('/authorization/userinfo', '', true);
		$jdata = json_decode($cdata, true);
		$this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

		// cdata={"userId":"455428","sub":"w0","domain":"sipgate.de","masterSipId":"2326984","locale":"de_DE","isTestAccount":false,"isAdmin":true,"product":"basic","flags":["FRONTEND2016"]}
		$userid = $jdata['userId'];
		$masterSipId = $jdata['masterSipId'];

		$cdata = $this->do_ApiCall('/users', '', true);
		$jdata = json_decode($cdata, true);
		$this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

		// cdata={"items":[{"id":"w0","firstname":"Christian","lastname":"Damsky","email":"christian@damsky.name","defaultDevice":"e0","busyOnBusy":false,"admin":true}]}
		$items = $jdata['items'];
		if (count($items) > 0) {
			$item = $items[0];
			$firstname = $item['firstname'];
			$lastname = $item['lastname'];
		} else {
			$firstname = '';
			$lastname = '';
		}

		$msg = $this->translate('valid account-data');

		if ($company != ''){
			if ($msg != '') $msg .= "\n";
			$msg = '  ' . $this->Translate('company') . '=' . $company;
		}

		if ($msg != '') $msg .= "\n";
		$msg .= '  ' . $this->Translate('user-id') . '=' . $userid;

		if ($msg != '') $msg .= "\n";
		$msg .= '  ' . $this->Translate('sip-id') . '=' . $masterSipId;

		if ($msg != '') $msg .= "\n";
		$msg .= '  ' . $this->Translate('name') . '=' . $firstname . ' ' . $lastname;

		echo $msg;
    }

    public function SendSMS(string $Telno, string $Msg)
    {
		$postdata = [
				'smsId'     => 's0',
				'recipient' => $Telno,
				'message'   => substr($Msg, 0, 160)
			];
		$cdata = $this->do_ApiCall('/sessions/sms', $postdata, true);
		if ($cdata == '')
			return false;
		$jdata = json_decode($cdata, true);
		$status = $this->GetArrayElem($jdata, 'status', 'fail');
		return $status == 'ok' ? true : false;
    }

    public function TestSMS(string $telno, string $msg)
    {
		if ($telno == '') {
			echo $this->Translate('missing telno');
			return;
		}
		if ($msg == '')
			$msg = "Test-SMS";

		$ok = $this->SendSMS($telno, $msg);
		echo $this->Translate('result of test') . ': ' . ($ok ? $this->Translate('success') : $this->Translate('failure'));
    }

    public function GetHistory()
    {
		$cdata = $this->do_ApiCall('/history', '', true);
		return $cdata;
    }

    public function TestHistory()
    {
		$cdata = $this->GetHistory();
		if ($cdata == '') {
			echo $this->Translate('no history');
			return;
		}
		$jdata = json_decode($cdata, true);

		$msg = $this->Translate('call-history:');
		$items = $jdata['items'];
		foreach ($items as $item) {
			$this->SendDebug(__FUNCTION__, 'item=' . print_r($item, true), 0);

			$created = strtotime($this->GetArrayElem($item, 'created', ''));
			$direction = $this->GetArrayElem($item, 'direction', '');
			$source = $this->GetArrayElem($item, 'source', '');
			$target = $this->GetArrayElem($item, 'target', '');

			if ($msg != '') $msg .= "\n";
			$msg .= '  ';
			$msg .= 'created=' . date('d.m. H:i', $created);
			$msg .= ', ';
			$msg .= 'direction=' . $direction;
			$msg .= ', ';
			$msg .= 'source=' . $source;
			$msg .= ', ';
			$msg .= 'target=' . $target;

			$type = $this->GetArrayElem($item, 'type', '');
			switch ($type) {
				case 'CALL':
					$duration = $this->GetArrayElem($item, 'duration', 0);
					$msg .= ', ';
					$msg .= 'duration=' . $duration;
					break;
				case 'SMS':
					$smsContent = $this->GetArrayElem($item, 'smsContent', '');
					$msg .= ', ';
					$msg .= 'message=' . $smsContent;
					break;
			}
		}
		echo $msg;
    }

    private function getToken()
    {
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

		$dtoken = $this->GetBuffer('Token');
		$jtoken = json_decode($dtoken, true);
		$token = isset($jtoken['token']) ? $jtoken['token'] : '';
		$token_expiration = isset($jtoken['token_expiration']) ? $jtoken['token_expiration'] : 0;

		if ($token_expiration < time()) {
			$postdata = [
					'username' => $user,
					'password' => $password
				];

			$header = [
					'Accept: application/json',
					'Content-Type: application/x-www-form-urlencoded'
				];

			$ctoken = $this->do_HttpRequest('/authorization/token', $header, $postdata, true);
			$this->SendDebug(__FUNCTION__, 'ctoken=' . print_r($ctoken, true), 0);
			if ($ctoken == '')
				return false;
			$jtoken = json_decode($ctoken, true);
			$token = $jtoken['token'];

			$jtoken = [
					'token' => $token,
					'token_expiration' => time() + 300
				];
			$this->SetBuffer('Token', json_encode($jtoken));
		}

		return $token;
    }

    private function do_ApiCall($cmd_url, $postdata = '', $isJson = true)
    {
		$token = $this->getToken();
		if ($token == '')
			return false;

		$header = [];
		$header[] = 'Accept: application/json';

		if ($postdata == '') {
			$header[] = 'Content-Type: application/x-www-form-urlencoded';
		} else {
			$header[] = 'Content-Type: application/json';
			$header[] = 'Content-Length: ' . strlen(json_encode($postdata));
		}

		$header[] = 'Authorization: Bearer ' . $token;

		$cdata = $this->do_HttpRequest($cmd_url, $header, $postdata, $isJson);
		$this->SendDebug(__FUNCTION__, 'cdata=' . print_r($cdata, true), 0);

		$this->SetStatus(102);
		return $cdata;
    }

    private function do_HttpRequest($cmd_url, $header = '', $postdata = '', $isJson = true)
    {
		$base_url = 'https://api.sipgate.com/v2';

		$url = $base_url . $cmd_url;

        $this->SendDebug(__FUNCTION__, 'http-' . ($postdata != '' ? 'post' : 'get') . ': url=' . $url, 0);
        $time_start = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($header != '') {
			$this->SendDebug(__FUNCTION__, '    header=' . print_r($header, true), 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
        if ($postdata != '') {
			$this->SendDebug(__FUNCTION__, '    postdata=' . print_r($postdata, true), 0);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $cdata = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $duration = floor((microtime(true) - $time_start) * 100) / 100;
        $this->SendDebug(__FUNCTION__, ' => httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);

        $statuscode = 0;
        $err = '';
        $data = '';
        if ($httpcode != 200) {
            if ($httpcode == 401) {
                $statuscode = 201;
                $err = "got http-code $httpcode (unauthorized)";
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = 202;
                $err = "got http-code $httpcode (server error)";
            } elseif ($httpcode == 204) {
				// 204 = No Content	= Die Anfrage wurde erfolgreich durchgeführt, die Antwort enthält jedoch bewusst keine Daten.
				// kommt zB bei senden von SMS
				$data = json_encode(array('status' => 'ok'));
            } else {
                $statuscode = 203;
                $err = "got http-code $httpcode";
            }
        } elseif ($cdata == '') {
            $statuscode = 204;
            $err = 'no data';
        } else {
			if ($isJson) {
				$jdata = json_decode($cdata, true);
			    if ($jdata == '') {
					$statuscode = 204;
					$err = 'malformed response';
				} else {
					$data = $cdata;
				}
			} else {
				$data = $cdata;
			}
        }

        if ($statuscode) {
            echo "url=$url => statuscode=$statuscode, err=$err";
            $this->SendDebug(__FUNCTION__, ' => statuscode=' . $statuscode . ', err=' . $err, 0);
            $this->SetStatus($statuscode);
        }

        return $data;
    }
}
