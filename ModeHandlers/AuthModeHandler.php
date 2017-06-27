<?php


namespace Adietz\HSPIConnect\ModeHandlers;


class AuthModeHandler extends ModeHandler
{
    public function getMode()
    {
        return "AUTH";
    }
    function getModeHeaders($additionalheaders = [])
    {
        $headers = [];
        $headers['HSP-ApplicationKey'] = env('HSP_ICONNECT_KEY', '');
        return $headers;
    }

}