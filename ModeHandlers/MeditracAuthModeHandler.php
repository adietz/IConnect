<?php


namespace Adietz\HSPIConnect\ModeHandlers;

class MeditracAuthModeHandler extends ModeHandler
{
    public function getMode()
    {
        return "MAUTH";
    }


    public function getUrl()
    {
        return env('HSP_MCONNECT_URL', '');
    }


    function getModeHeaders($additionalHeaders = [])
    {
        $headers = [];
        $headers['HSP-ApplicationKey'] = env('HSP_MCONNECT_KEY', '');
        return $headers;
    }


}