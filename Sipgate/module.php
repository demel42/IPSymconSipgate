<?php

class Sipgate extends IPSModule
{
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
		if ($user == '') {
			echo 'no value for property "user"';
			$ok = false;
		}
		if ($password == '') {
			echo 'no value for property "password"';
			$ok = false;
		}
		$this->SetStatus($ok ? 102 : 201);
    }

    public function TestAccount()
    {
    }

    public function SendSMS(string $Telno, string $Msg)
    {
    }

}
