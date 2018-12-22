<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

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
        $cdata = $this->do_ApiCall('/account', '', true, 'GET');
        if ($cdata == '') {
            echo $this->Translate('invalid account-data');
            return;
        }
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $company = $jdata['company'];

        $cdata = $this->do_ApiCall('/authorization/userinfo', '', true, 'GET');
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $userid = $jdata['userId'];
        $masterSipId = $jdata['masterSipId'];

        $cdata = $this->do_ApiCall('/users', '', true, 'GET');
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $items = $jdata['items'];
        if (count($items) > 0) {
            $item = $items[0];
            $firstname = $item['firstname'];
            $lastname = $item['lastname'];
        } else {
            $firstname = '';
            $lastname = '';
        }

        $msg = $this->translate('valid account-data') . "\n";

        if ($company != '') {
            $msg = '  ' . $this->Translate('company') . '=' . $company . "\n";
        }
        $msg .= '  ' . $this->Translate('user-id') . '=' . $userid . "\n";
        $msg .= '  ' . $this->Translate('sip-id') . '=' . $masterSipId . "\n";
        $msg .= '  ' . $this->Translate('name') . '=' . $firstname . ' ' . $lastname . "\n";

        $cdata = $this->do_ApiCall('/w0/phonelines', '', true, 'GET');
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $msg .= "\n";
        $msg .= $this->translate('phonelines') . "\n";
        $items = $jdata['items'];
        foreach ($items as $item) {
            $this->SendDebug(__FUNCTION__, 'item=' . print_r($item, true), 0);
            $id = $item['id'];
            $alias = $item['alias'];

            $msg .= '  ';
            $msg .= $this->Translate('phone-id') . '=' . $id;
            $msg .= ', ';
            $msg .= $this->Translate('alias') . '=' . $alias;
            $msg .= "\n";
        }

        $cdata = $this->do_ApiCall('/w0/devices', '', true, 'GET');
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);
        $items = $jdata['items'];

        $msg .= "\n";
        $msg .= $this->translate('devices') . "\n";
        foreach ($items as $item) {
            $this->SendDebug(__FUNCTION__, 'item=' . print_r($item, true), 0);
            $id = $item['id'];
            $alias = $item['alias'];

            $msg .= '  ';
            $msg .= $this->Translate('device-id') . '=' . $id;
            $msg .= ', ';
            $msg .= $this->Translate('alias') . '=' . $alias;
            $msg .= "\n";
        }
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
        if ($cdata == '') {
            return false;
        }
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
        if ($msg == '') {
            $msg = 'Test-SMS';
        }

        $ok = $this->SendSMS($telno, $msg);
        echo $this->Translate('result of test') . ': ' . ($ok ? $this->Translate('success') : $this->Translate('failure'));
    }

    public function GetHistory()
    {
        $cdata = $this->do_ApiCall('/history', '', true, 'GET');
        return $cdata;
    }

    public function ShowHistory()
    {
        $cdata = $this->GetHistory();
        if ($cdata == '') {
            echo $this->Translate('no history');
            return;
        }
        $jdata = json_decode($cdata, true);

        $msg = $this->Translate('call-history') . ":\n";
        $items = $jdata['items'];
        foreach ($items as $item) {
            $this->SendDebug(__FUNCTION__, 'item=' . print_r($item, true), 0);

            $created = strtotime($this->GetArrayElem($item, 'created', ''));
            $direction = $this->GetArrayElem($item, 'direction', '');
            $source = $this->GetArrayElem($item, 'source', '');
            $target = $this->GetArrayElem($item, 'target', '');

            $msg .= '  ';
            $msg .= date('d.m. H:i', $created);
            $msg .= '  ';
            $msg .= $this->Translate('direction') . '=' . $direction;
            $msg .= ', ';
            $msg .= $this->Translate('source') . '=' . $source;
            $msg .= ', ';
            $msg .= $this->Translate('target') . '=' . $target;

            $type = $this->GetArrayElem($item, 'type', '');
            switch ($type) {
                case 'CALL':
                    $duration = $this->GetArrayElem($item, 'duration', 0);
                    $msg .= ', ';
                    $msg .= $this->Translate('duration') . '=' . $duration;
                    break;
                case 'SMS':
                    $smsContent = $this->GetArrayElem($item, 'smsContent', '');
                    $msg .= ', ';
                    $msg .= $this->Translate('message') . '=' . $smsContent;
                    break;
            }
            $msg .= "\n";
        }
        echo $msg;
    }

    public function GetCallList()
    {
        $cdata = $this->do_ApiCall('/calls', '', true, 'GET');
        return $cdata;
    }

    public function ShowCallList()
    {
        $cdata = $this->GetCallList();
        if ($cdata == '') {
            echo $this->Translate('no current calls');
            return;
        }
        $jdata = json_decode($cdata, true);

        $msg = $this->Translate('current calls') . ":\n";
        $data = $jdata['data'];
        foreach ($data as $dat) {
            $this->SendDebug(__FUNCTION__, 'dat=' . print_r($dat, true), 0);

            $direction = $this->GetArrayElem($dat, 'direction', '');
            $muted = $this->GetArrayElem($dat, 'muted', '');
            $recording = $this->GetArrayElem($dat, 'recording', '');
            $hold = $this->GetArrayElem($dat, 'hold', '');

            $msg .= '  ';
            $msg .= $this->Translate('muted') . '=' . $muted ? 'true' : 'false';
            $msg .= ', ';
            $msg .= $this->Translate('recording') . '=' . $recording ? 'true' : 'false';
            $msg .= ', ';
            $msg .= $this->Translate('hold') . '=' . $hold ? 'true' : 'false';

            $participants = $this->GetArrayElem($dat, 'participants', '');
            $this->SendDebug(__FUNCTION__, 'participants=' . print_r($participants, true), 0);

            $msg .= "\n";
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
            if ($ctoken == '') {
                return false;
            }
            $jtoken = json_decode($ctoken, true);
            $token = $jtoken['token'];

            $jtoken = [
                    'token'            => $token,
                    'token_expiration' => time() + 300
                ];
            $this->SetBuffer('Token', json_encode($jtoken));
        }

        return $token;
    }

    private function do_ApiCall($cmd_url, $postdata = '', $isJson = true, $customrequest = '')
    {
        $token = $this->getToken();
        if ($token == '') {
            return false;
        }

        $header = [];
        $header[] = 'Accept: application/json';

        if ($postdata != '') {
            $header[] = 'Content-Type: application/json';
            $header[] = 'Content-Length: ' . strlen(json_encode($postdata));
        } elseif ($customrequest == '') {
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $header[] = 'Authorization: Bearer ' . $token;

        $cdata = $this->do_HttpRequest($cmd_url, $header, $postdata, $isJson, $customrequest);
        $this->SendDebug(__FUNCTION__, 'cdata=' . print_r($cdata, true), 0);

        $this->SetStatus(102);
        return $cdata;
    }

    private function do_HttpRequest($cmd_url, $header = '', $postdata = '', $isJson = true, $customrequest = '')
    {
        $base_url = 'https://api.sipgate.com/v2';

        $url = $base_url . $cmd_url;

        if ($customrequest != '') {
            $req = $customrequest;
        } elseif ($postdata != '') {
            $req = 'post';
        } else {
            $req = 'get';
        }
        //$req = $customrequest != '' ? $customrequest : $postdata != '' ? 'post' : 'get';
        $this->SendDebug(__FUNCTION__, 'cmd_url=' . $cmd_url . ', customrequest=' . $customrequest . ', req=' . $req, 0);

        $this->SendDebug(__FUNCTION__, 'http-' . $req . ': url=' . $url, 0);
        $time_start = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($header != '') {
            $this->SendDebug(__FUNCTION__, '    header=' . print_r($header, true), 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if ($postdata != '') {
            $this->SendDebug(__FUNCTION__, '    postdata=' . json_encode($postdata), 0);
            if ($customrequest == '') {
                curl_setopt($ch, CURLOPT_POST, true);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        }
        if ($customrequest != '') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customrequest);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $cdata = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $duration = round(microtime(true) - $time_start, 2);
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
                $data = json_encode(['status' => 'ok']);
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
            $this->LogMessage('url=' . $url . ' => statuscode=' . $statuscode . ', err=' . $err, KL_WARNING);
            $this->SendDebug(__FUNCTION__, ' => statuscode=' . $statuscode . ', err=' . $err, 0);
            $this->SetStatus($statuscode);
        }

        return $data;
    }

    public function Hangup(string $callId)
    {
        $cdata = $this->do_ApiCall('/calls/' . $callId, '', true, 'DELETE');
        $this->SendDebug(__FUNCTION__, 'cdata=' . print_r($cdata, true), 0);

        $this->SetStatus(102);
        return $cdata;
    }

    public function TestAnnouncement()
    {
        $wav_url = 'https://static.sipgate.com/examples/wav/example.wav';

        $postdata = [
                'caller'   => 'e1',
                'callee'   => '+491718883302',
                'callerId' => '+4923274178948'
            ];

        $cdata = $this->do_ApiCall('/sessions/calls', $postdata, true);
        if ($cdata == '') {
            return;
        }
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);
        $sessionId = $jdata['sessionId'];

        /*
        sleep (15);

        $postdata = [
                'url'   => $wav_url
            ];
        $cdata = $this->do_ApiCall('/calls/' . $sessionId . '/announcements', $postdata, true);
        */
    }

    public function GetForwardings($deviceId = 'p0')
    {
        $cdata = $this->do_ApiCall('/w0/phonelines/' . $deviceId . '/forwardings', '', true, 'GET');
        $this->SendDebug(__FUNCTION__, 'cdata=' . print_r($cdata, true), 0);

        $this->SetStatus(102);
        return $cdata;
    }

    public function ShowForwardings()
    {
        $cdata = $this->do_ApiCall('/w0/phonelines', '', true, 'GET');
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $msg = $this->Translate('current forwardings') . ":\n";

        $phones = $jdata['items'];
        foreach ($phones as $phone) {
            $this->SendDebug(__FUNCTION__, 'phone=' . print_r($phone, true), 0);
            $id = $phone['id'];
            $alias = $phone['alias'];

            $msg .= '  ';
            $msg .= $this->Translate('phoneline') . '=' . $id;
            $msg .= ', ';
            $msg .= $this->Translate('alias') . '=' . $alias;
            $msg .= "\n";

            $cdata = $this->GetForwardings($id);
            $jdata = json_decode($cdata, true);
            $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

            $forwards = $jdata['items'];
            foreach ($forwards as $forward) {
                $this->SendDebug(__FUNCTION__, 'forward=' . print_r($forward, true), 0);

                $alias = $this->GetArrayElem($forward, 'alias', '');
                $destination = $this->GetArrayElem($forward, 'destination', '');
                $timeout = $this->GetArrayElem($forward, 'timeout', '');
                $active = $this->GetArrayElem($forward, 'active', '');

                $msg .= '    ';
                if ($active) {
                    $msg .= $this->Translate('destination') . '=' . $destination;
                    $msg .= ', ';
                    $msg .= $this->Translate('alias') . '=' . $alias;
                    $msg .= ', ';
                    $msg .= $this->Translate('timeout') . '=' . $timeout;
                }
                $msg .= "\n";
            }
        }
        echo $msg;
    }

    public function SetForwarding(string $destination, int $timeout, bool $active, $deviceId = 'p0')
    {
        $postdata = [
            'forwardings' => [
                        [
                            'destination' => $destination,
                            'timeout'     => $timeout,
                            'active'      => $active
                        ]
                ]
            ];
        $cdata = $this->do_ApiCall('/w0/phonelines/' . $deviceId . '/forwardings', $postdata, true, 'PUT');
        if ($cdata == '') {
            return false;
        }
        $jdata = json_decode($cdata, true);
        $status = $this->GetArrayElem($jdata, 'status', 'fail');
        return $status == 'ok' ? true : false;
    }

    public function TestForwarding(string $destination, int $timeout, bool $active)
    {
        $ok = $this->SetForwarding($destination, $timeout, $active);
        echo $this->Translate('result of test') . ': ' . ($ok ? $this->Translate('success') : $this->Translate('failure'));
    }
}
