<?php

namespace Adietz\HSPIConnect\ModeHandlers;

use Illuminate\Support\Facades\Redis;

class MasqueradeModeHandler extends ModeHandler
{
    private $masqUsername;
    private $masqPassword;

    function __construct($options = [])
    {
        $this->masqUsername = $options['masqUsername'];
    }

    public function getMode()
    {
        return "MASQ";
    }
    function getModeHeaders($additionalheaders = [])
    {
        $headers = [];
        $headers['HSP-ApplicationKey'] = $this->iconnectkey;
        $headers['HSP-SessionKey'] = $this->getSessionKey();
        $headers['HSP-SessionId'] = $this->getSessionId();
        return $headers;
    }

    function getAuthKey()
    {
        return Redis::get('masquerade:' . $this->masqUsername . ':userauthkey');
    }

    function getSessionId()
    {
        return Redis::get('masquerade:' . $this->masqUsername . ':sessionid');
    }

    function getSessionKey()
    {
        return Redis::get('masquerade:' . $this->masqUsername . ':sessionkey');

    }

    function checkAuth()
    {
        $sessionid = $this->getSessionId();
        $sessionkey = $this->getSessionKey();

        if ($sessionid == '' || $sessionkey == '') {
            $this->reAuthenticate();
        }

        return true;
    }

    function reAuthenticate()
    {
        Log::info('Attempting Masquerade Mode Reauthentication.');
        $service = "HSPAuthServices/Session/";
        $postdata = "<LoginInfo><UserName>" . $this->masqUsername . "</UserName><Password>" . $this->masqueradepassword . "</Password></LoginInfo>";
        $userdata = IConnect::execute($service, 'POST', 'AUTH',
            ['PostBody' => $postdata, 'masqUsername' => $this->masqUsername]);

        Redis::set('masquerade:' . $this->masqUsername . ':sessionkey', $userdata->SessionKey);
        Redis::set('masquerade:' . $this->masqUsername . ':sessionid', $userdata->SessionId);
        Redis::set('masquerade:' . $this->masqUsername . ':userauthkey', $userdata->UserAuthKey);
    }
}