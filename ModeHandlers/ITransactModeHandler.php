<?php

namespace Adietz\HSPIConnect\ModeHandlers;
use Illuminate\Support\Facades\Redis;
class ITransactModeHandler extends ModeHandler
{

    public function getMode()
    {
        return "ITRANSACT";
    }

    public function response($response)
    {
        return simplexml_load_string($response->getBody());
    }
}