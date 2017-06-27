<?php

namespace Adietz\HSPIConnect\Modes;


class DocumentModeHandler extends ModeHandler
{

    function response($response)
    {
        return $response->getBody();
    }
}