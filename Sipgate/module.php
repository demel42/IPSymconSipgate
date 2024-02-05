<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class Sipgate extends IPSModule
{
    use Sipgate\StubsCommonLib;
    use SipgateLocalLib;

    private $oauthIdentifer = 'sipgate';

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonContruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyInteger('UpdateDataInterval', '24');

        $this->RegisterAttributeString('ApiRefreshToken', '');

        $this->SetBuffer('ApiAccessToken', '');

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RegisterTimer('UpdateData', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($tstamp, $senderID, $message, $data)
    {
        parent::MessageSink($tstamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $this->RegisterOAuth($this->oauthIdentifer);
            $this->SetUpdateInterval();
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $vpos = 1;
        $this->MaintainVariable('Credit', $this->Translate('credit'), VARIABLETYPE_FLOAT, 'Sipgate.Currency', $vpos++, true);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        if ($this->GetConnectUrl() == false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_NOSYMCONCONNECT);
            return;
        }

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterOAuth($this->oauthIdentifer);
        }

        $refresh_token = $this->ReadAttributeString('ApiRefreshToken');
        if ($refresh_token == '') {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_NOLOGIN);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterOAuth($this->oauthIdentifer);
            $this->SetUpdateInterval();
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Sipgate Basic');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'Label',
            'caption' => $this->GetConnectStatusText(),
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Sipgate Login',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Push "Login at Sipgate" in the action part of this configuration form.'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'At the webpage from Sipgate log in with your Sipgate username and password.'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'If the connection to IP-Symcon was successfull you get the message: "Sipgate successfully connected!". Close the browser window.'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Return to this configuration form.'
                ],
            ],
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Call settings',
            'items'   => [
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'UpdateDataInterval',
                    'minimum' => 0,
                    'suffix'  => 'Hours',
                    'caption' => 'Update interval',
                ],
            ],
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");',
        ];
        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Test account',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestAccount", "");',
        ];
        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Login at Sipgate',
            'onClick' => 'echo "' . $this->Login() . '";',
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ]
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Test area',
            'expanded'  => false,
            'items'     => [
                [
                    'type'    => 'TestCenter',
                ],
                [
                    'type'    => 'Label',
                ],
                [
                    'type'    => 'Label',
                    'caption' => ''
                ],
                [
                    'type'    => 'RowLayout',
                    'items'   => [
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'telno',
                            'caption' => 'telno'
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'msg',
                            'caption' => 'msg'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Test SMS',
                            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestSMS", json_encode(["telno" => $telno, "msg" => $msg]));',
                        ],
                    ],
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Show Call-History',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "ShowHistory", "");',
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Show current Calls',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "ShowCallList", "");',
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Show current Forwardings',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "ShowForwardings", "");',
                ],
                [
                    'type'    => 'RowLayout',
                    'items'   => [
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'destination',
                            'caption' => 'destination'
                        ],
                        [
                            'type'    => 'NumberSpinner',
                            'name'    => 'timeout',
                            'caption' => 'timeout'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'active',
                            'caption' => 'active'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Test Forwarding',
                            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestForwarding", json_encode(["destination" => $destination, "timeout" => $timeout, "active" => $active]));',
                        ],
                    ],
                ],
            ],
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    private function Login()
    {
        $url = 'https://oauth.ipmagic.de/authorize/' . $this->oauthIdentifer . '?username=' . urlencode(IPS_GetLicensee());
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    protected function ProcessOAuthData()
    {
        if (!isset($_GET['code'])) {
            $this->SendDebug(__FUNCTION__, 'code missing, _GET=' . print_r($_GET, true), 0);
            $this->WriteAttributeString('ApiRefreshToken', '');
            $this->SetBuffer('ApiAccessToken', '');
            $this->MaintainStatus(self::$IS_NOLOGIN);
            return;
        }
        $refresh_token = $this->GetApiRefreshToken($_GET['code']);
        $this->SendDebug(__FUNCTION__, 'refresh_token=' . $refresh_token, 0);
        $this->WriteAttributeString('ApiRefreshToken', $refresh_token);
        if ($this->GetStatus() == self::$IS_NOLOGIN) {
            $this->MaintainStatus(IS_ACTIVE);
        }
    }

    protected function Call4ApiAccessToken($content)
    {
        $url = 'https://oauth.ipmagic.de/access_token/' . $this->oauthIdentifer;
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        $this->SendDebug(__FUNCTION__, '    content=' . print_r($content, true), 0);

        $statuscode = 0;
        $err = '';
        $jdata = false;

        $time_start = microtime(true);
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($content)
            ]
        ];
        $context = stream_context_create($options);
        $cdata = @file_get_contents($url, false, $context);
        $duration = round(microtime(true) - $time_start, 2);
        $httpcode = 0;
        if ($cdata == false) {
            $this->LogMessage('file_get_contents() failed: url=' . $url . ', context=' . print_r($context, true), KL_WARNING);
            $this->SendDebug(__FUNCTION__, 'file_get_contents() failed: url=' . $url . ', context=' . print_r($context, true), 0);
        } elseif (isset($http_response_header[0]) && preg_match('/HTTP\/[0-9\.]+\s+([0-9]*)/', $http_response_header[0], $r)) {
            $httpcode = $r[1];
        } else {
            $this->LogMessage('missing http_response_header, cdata=' . $cdata, KL_WARNING);
            $this->SendDebug(__FUNCTION__, 'missing http_response_header, cdata=' . $cdata, 0);
        }
        $this->SendDebug(__FUNCTION__, ' => httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);
        $this->SendDebug(__FUNCTION__, '    cdata=' . $cdata, 0);

        if ($httpcode != 200) {
            if ($httpcode == 401) {
                $statuscode = self::$IS_UNAUTHORIZED;
                $err = 'got http-code ' . $httpcode . ' (unauthorized)';
            } elseif ($httpcode == 409) {
                $data = $cdata;
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = self::$IS_SERVERERROR;
                $err = 'got http-code ' . $httpcode . ' (server error)';
            } else {
                $statuscode = self::$IS_HTTPERROR;
                $err = 'got http-code ' . $httpcode;
            }
        } elseif ($cdata == '') {
            $statuscode = self::$IS_NODATA;
            $err = 'no data';
        } else {
            $jdata = json_decode($cdata, true);
            if ($jdata == '') {
                $statuscode = self::$IS_INVALIDDATA;
                $err = 'malformed response';
            } else {
                if (!isset($jdata['refresh_token'])) {
                    $statuscode = self::$IS_INVALIDDATA;
                    $err = 'malformed response';
                }
            }
        }
        if ($statuscode) {
            $this->SendDebug(__FUNCTION__, '    statuscode=' . $statuscode . ', err=' . $err, 0);
            $this->MaintainStatus($statuscode);
            return false;
        }
        return $jdata;
    }

    private function GetApiAccessToken($access_token = '', $expiration = 0)
    {
        if ($access_token == '' && $expiration == 0) {
            $data = $this->GetBuffer('ApiAccessToken');
            if ($data != '') {
                $jtoken = json_decode($data, true);
                $access_token = isset($jtoken['access_token']) ? $jtoken['access_token'] : '';
                $expiration = isset($jtoken['expiration']) ? $jtoken['expiration'] : 0;
                if ($expiration < time()) {
                    $this->SendDebug(__FUNCTION__, 'access_token expired', 0);
                    $access_token = '';
                }
                if ($access_token != '') {
                    $this->SendDebug(__FUNCTION__, 'access_token=' . $access_token . ', valid until ' . date('d.m.y H:i:s', $expiration), 0);
                    return $access_token;
                }
            } else {
                $this->SendDebug(__FUNCTION__, 'no saved access_token', 0);
            }
            $refresh_token = $this->ReadAttributeString('ApiRefreshToken');
            $this->SendDebug(__FUNCTION__, 'refresh_token=' . print_r($refresh_token, true), 0);
            if ($refresh_token == '') {
                $this->SendDebug(__FUNCTION__, 'has no refresh_token', 0);
                $this->WriteAttributeString('ApiRefreshToken', '');
                $this->SetBuffer('ApiAccessToken', '');
                $this->MaintainStatus(self::$IS_NOLOGIN);
                return false;
            }
            $jdata = $this->Call4ApiAccessToken(['refresh_token' => $refresh_token]);
            if ($jdata == false) {
                $this->SendDebug(__FUNCTION__, 'got no access_token', 0);
                $this->SetBuffer('ApiAccessToken', '');
                return false;
            }
            $access_token = $jdata['access_token'];
            $expiration = time() + $jdata['expires_in'];
            if (isset($jdata['refresh_token'])) {
                $refresh_token = $jdata['refresh_token'];
                $this->SendDebug(__FUNCTION__, 'new refresh_token=' . $refresh_token, 0);
                $this->WriteAttributeString('ApiRefreshToken', $refresh_token);
            }
        }
        $this->SendDebug(__FUNCTION__, 'new access_token=' . $access_token . ', valid until ' . date('d.m.y H:i:s', $expiration), 0);
        $jtoken = [
            'access_token' => $access_token,
            'expiration'   => $expiration,
        ];
        $this->SetBuffer('ApiAccessToken', json_encode($jtoken));
        return $access_token;
    }

    private function GetApiRefreshToken($code)
    {
        $this->SendDebug(__FUNCTION__, 'code=' . $code, 0);
        $jdata = $this->Call4ApiAccessToken(['code' => $code]);
        if ($jdata == false) {
            $this->SendDebug(__FUNCTION__, 'got no token', 0);
            $this->SetBuffer('ApiAccessToken', '');
            return false;
        }
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);
        $access_token = $jdata['access_token'];
        $expiration = time() + $jdata['expires_in'];
        $refresh_token = $jdata['refresh_token'];
        $this->GetApiAccessToken($access_token, $expiration);
        return $refresh_token;
    }

    private function SetUpdateInterval()
    {
        $hour = $this->ReadPropertyInteger('UpdateDataInterval');
        $msec = $hour > 0 ? $hour * 1000 * 60 * 60 : 0;
        $this->MaintainTimer('UpdateData', $msec);
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }

        switch ($ident) {
            case 'UpdateData':
                $this->UpdateData();
                break;
            case 'TestAccount':
                $this->TestAccount();
                break;
            case 'TestSMS':
                $this->TestSMS($value);
                break;
            case 'ShowHistory':
                $this->ShowHistory();
                break;
            case 'ShowCallList':
                $this->ShowCallList();
                break;
            case 'ShowForwardings':
                $this->ShowForwardings();
                break;
            case 'TestForwarding':
                $this->TestForwarding($value);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }

    private function UpdateData()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            if ($this->GetStatus() == self::$IS_NOLOGIN) {
                $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => pause', 0);
                $this->MaintainTimer('UpdateData', 0);
            } else {
                $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            }
            return;
        }

        $this->SendDebug(__FUNCTION__, '', 0);

        $cdata = $this->do_ApiCall('/balance', '', true, 'GET');
        if ($cdata == '') {
            $this->SendDebug(__FUNCTION__, 'invalid balance-data', 0);
            return;
        }

        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $amount = floatval($jdata['amount']) / 10000;

        $this->SetValue('Credit', $amount);
        $this->SendDebug(__FUNCTION__, 'set variable "Credit" to ' . $amount, 0);

        $this->MaintainStatus(IS_ACTIVE);
        $this->SetUpdateInterval();
    }

    private function TestAccount()
    {
        $cdata = $this->do_ApiCall('/account', '', true, 'GET');
        if ($cdata == '') {
            $msg = $this->Translate('invalid account-data');
            $this->PopupMessage($msg);
            return;
        }
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $company = $jdata['company'];

        $cdata = $this->do_ApiCall('/balance', '', true, 'GET');
        if ($cdata == '') {
            $msg = $this->Translate('invalid balance-data');
            $this->PopupMessage($msg);
            return;
        }
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $amount = floatval($jdata['amount']) / 10000;
        $currency = $jdata['currency'];

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

        $msg = $this->Translate('valid account-data') . PHP_EOL;

        if ($company != '') {
            $msg = '  ' . $this->Translate('company') . '=' . $company . PHP_EOL;
        }

        $msg .= '  ' . $this->Translate('credit') . '=' . sprintf('%.02f', $amount) . ' ' . $currency . PHP_EOL;
        $msg .= PHP_EOL;

        $msg .= '  ' . $this->Translate('user-id') . '=' . $userid . PHP_EOL;
        $msg .= '  ' . $this->Translate('sip-id') . '=' . $masterSipId . PHP_EOL;
        $msg .= '  ' . $this->Translate('name') . '=' . $firstname . ' ' . $lastname . PHP_EOL;

        $cdata = $this->do_ApiCall('/w0/phonelines', '', true, 'GET');
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $msg .= PHP_EOL;
        $msg .= $this->Translate('phonelines') . PHP_EOL;
        $items = $jdata['items'];
        foreach ($items as $item) {
            $this->SendDebug(__FUNCTION__, 'item=' . print_r($item, true), 0);
            $id = $item['id'];
            $alias = $item['alias'];

            $msg .= '  ';
            $msg .= $this->Translate('phone-id') . '=' . $id;
            $msg .= ', ';
            $msg .= $this->Translate('alias') . '=' . $alias;
            $msg .= PHP_EOL;
        }

        $cdata = $this->do_ApiCall('/w0/devices', '', true, 'GET');
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);
        $items = $jdata['items'];

        $msg .= PHP_EOL;
        $msg .= $this->Translate('devices') . PHP_EOL;
        foreach ($items as $item) {
            $this->SendDebug(__FUNCTION__, 'item=' . print_r($item, true), 0);
            $id = $item['id'];
            $alias = $item['alias'];

            $msg .= '  ';
            $msg .= $this->Translate('device-id') . '=' . $id;
            $msg .= ', ';
            $msg .= $this->Translate('alias') . '=' . $alias;
            $msg .= PHP_EOL;
        }

        $this->PopupMessage($msg);
    }

    public function SendSMS(string $telno, string $msg)
    {
        $telno = preg_replace('<^\\+>', '00', $telno);
        $telno = preg_replace('<\\D+>', '', $telno);
        $postdata = [
            'smsId'     => 's0',
            'recipient' => $telno,
            'message'   => substr($msg, 0, 160)
        ];
        $cdata = $this->do_ApiCall('/sessions/sms', $postdata, true);
        if ($cdata == '') {
            return false;
        }
        $jdata = json_decode($cdata, true);
        $status = $this->GetArrayElem($jdata, 'status', 'fail');
        return $status == 'ok' ? true : false;
    }

    private function TestSMS($params)
    {
        $jparams = json_decode($params, true);
        $telno = isset($jparams['telno']) ? $jparams['telno'] : '';
        if ($telno == '') {
            $msg = $this->Translate('missing telno');
            $this->PopupMessage($msg);
            return;
        }
        $msg = isset($jparams['msg']) ? $jparams['msg'] : '';
        if ($msg == '') {
            $msg = 'Test-SMS';
        }

        $ok = $this->SendSMS($telno, $msg);
        $msg = $this->Translate('result of test') . ': ' . ($ok ? $this->Translate('success') : $this->Translate('failure'));
        $this->PopupMessage($msg);
    }

    public function GetHistory()
    {
        $cdata = $this->do_ApiCall('/history', '', true, 'GET');
        return $cdata;
    }

    private function ShowHistory()
    {
        $cdata = $this->GetHistory();
        if ($cdata == '') {
            $msg = $this->Translate('no history');
            $this->PopupMessage($msg);
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
            $msg .= PHP_EOL;
        }

        $this->PopupMessage($msg);
    }

    public function GetCallList()
    {
        $cdata = $this->do_ApiCall('/calls', '', true, 'GET');
        return $cdata;
    }

    private function ShowCallList()
    {
        $cdata = $this->GetCallList();
        if ($cdata == '') {
            $msg = $this->Translate('no current calls');
            $this->PopupMessage($msg);
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

            $msg .= PHP_EOL;
        }

        $this->PopupMessage($msg);
    }

    private function do_ApiCall($cmd_url, $postdata = '', $isJson = true, $customrequest = '')
    {
        $access_token = $this->GetApiAccessToken();
        if ($access_token == '') {
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
        $header[] = 'Authorization: Bearer ' . $access_token;

        $cdata = $this->do_HttpRequest($cmd_url, $header, $postdata, $isJson, $customrequest);
        $this->SendDebug(__FUNCTION__, 'cdata=' . print_r($cdata, true), 0);

        $this->MaintainStatus(IS_ACTIVE);
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
        $cerrno = curl_errno($ch);
        $cerror = $cerrno ? curl_error($ch) : '';
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $duration = round(microtime(true) - $time_start, 2);
        $this->SendDebug(__FUNCTION__, ' => errno=' . $cerrno . ', httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);

        $statuscode = 0;
        $err = '';
        $data = '';
        if ($cerrno) {
            $statuscode = self::$IS_SERVERERROR;
            $err = 'got curl-errno ' . $cerrno . ' (' . $cerror . ')';
        } elseif ($httpcode != 200) {
            if ($httpcode == 401) {
                $statuscode = self::$IS_UNAUTHORIZED;
                $err = "got http-code $httpcode (unauthorized)";
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = self::$IS_SERVERERROR;
                $err = "got http-code $httpcode (server error)";
            } elseif ($httpcode == 204) {
                $data = json_encode(['status' => 'ok']);
            } else {
                $statuscode = self::$IS_HTTPERROR;
                $err = "got http-code $httpcode";
            }
        } elseif ($cdata == '') {
            $statuscode = self::$IS_INVALIDDATA;
            $err = 'no data';
        } else {
            if ($isJson) {
                $jdata = json_decode($cdata, true);
                if ($jdata == '') {
                    $statuscode = self::$IS_INVALIDDATA;
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
            $this->MaintainStatus($statuscode);
        }

        return $data;
    }

    public function GetForwardings(string $deviceId = 'p0')
    {
        $cdata = $this->do_ApiCall('/w0/phonelines/' . $deviceId . '/forwardings', '', true, 'GET');
        $this->SendDebug(__FUNCTION__, 'cdata=' . print_r($cdata, true), 0);

        $this->MaintainStatus(IS_ACTIVE);
        return $cdata;
    }

    private function ShowForwardings()
    {
        $cdata = $this->do_ApiCall('/w0/phonelines', '', true, 'GET');
        $jdata = json_decode($cdata, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $msg = $this->Translate('current forwardings') . ":\n";

        if (isset($jdata['items'])) {
            $phones = $jdata['items'];
            foreach ($phones as $phone) {
                $this->SendDebug(__FUNCTION__, 'phone=' . print_r($phone, true), 0);
                $id = $phone['id'];
                $alias = $phone['alias'];

                $msg .= '  ';
                $msg .= $this->Translate('phoneline') . '=' . $id;
                $msg .= ', ';
                $msg .= $this->Translate('alias') . '=' . $alias;
                $msg .= PHP_EOL;

                $cdata = $this->GetForwardings($id);
                $jdata = json_decode($cdata, true);
                $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

                if (isset($jdata['items'])) {
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
                        $msg .= PHP_EOL;
                    }
                }
            }
        }

        $this->PopupMessage($msg);
    }

    public function SetForwarding(string $destination, int $timeout, bool $active, string $deviceId = 'p0')
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

    private function TestForwarding($params)
    {
        $jparams = json_decode($params, true);
        $destination = isset($jparams['destination']) ? $jparams['destination'] : '';
        if ($destination == '') {
            $msg = $this->Translate('missing destination');
            $this->PopupMessage($msg);
            return;
        }
        $timeout = isset($jparams['timeout']) ? $jparams['timeout'] : 0;
        $active = isset($jparams['active']) ? $jparams['active'] : false;

        $ok = $this->SetForwarding($destination, $timeout, $active);
        $msg = $this->Translate('result of test') . ': ' . ($ok ? $this->Translate('success') : $this->Translate('failure'));
        $this->PopupMessage($msg);
    }
}
