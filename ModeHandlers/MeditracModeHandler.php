<?php


namespace Adietz\HSPIConnect\ModeHandlers;
use Illuminate\Support\Facades\Redis;

class MeditracModeHandler extends ModeHandler
{

    public function getMode()
    {
        return "MEDITRAC";
    }
    public function getUrl()
    {
        return env('HSP_MSERV_URL', '');
    }

    function response($response)
    {
        return simplexml_load_string($response->getBody());
    }

    function getModeHeaders($additionalHeaders = [])
    {
        $headers = [];
        $headers['HSP-ApplicationKey'] = env('HSP_MCONNECT_KEY');
        $headers['HSP-SessionKey'] = $this->getSessionKey();
        $headers['HSP-SessionId'] = $this->getSessionId();
        return array_merge($headers, $additionalHeaders);
    }

    function getAuthKey()
    {
        return Redis::get('meditrac:userauthkey');
    }

    function getSessionId()
    {
        return Redis::get('meditrac:sessionid');
    }

    function getSessionKey()
    {
        return Redis::get('meditrac:sessionkey');
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
        Log::info('Attempting Meditrac Mode Reauthentication.');
        $service = "HSPAuthServices/Session/";
        $postdata = "<LoginInfo><UserName>" . env('HSP_USERNAME', '') . "</UserName><Password>" . env('HSP_PASSWORD',
                '') . "</Password></LoginInfo>";
        $userdata = IConnect::execute($service, 'POST', 'MAUTH', ['PostBody' => $postdata]);

        Redis::set('meditrac:sessionkey', $userdata->SessionKey);
        Redis::set('meditrac:sessionid', $userdata->SessionId);
        Redis::set('meditrac:userauthkey', $userdata->UserAuthKey);
    }
}