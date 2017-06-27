<?php


namespace Adietz\HSPIConnect\ModeHandlers;


abstract class ModeHandler
{
    /**
     * @var options array
     */
    public $options;

    //itransact mode is the most commonly used, so we'll build for that here in the abstract and override where necessary

    abstract public function getMode();


    public function __construct($options = [])
    {
        $this->options = $options;
    }

    public function getUrl()
    {
        return env('HSP_ISERV_URL', '');
    }

    public function response($response)
    {
        return simplexml_load_string($response->getBody());
    }

    public function getModeHeaders($additionalHeaders = [])
    {
        $headers = [];
        $headers['HSP-ApplicationKey'] = env('HSP_ICONNECT_KEY');
        $headers['HSP-SessionKey'] = $this->getSessionKey();
        $headers['HSP-SessionId'] = $this->getSessionId();
        return array_merge($headers, $additionalHeaders);
    }

    public function getAuthKey()
    {
        return session('hspauthkey', '');
    }

    public function getSessionId()
    {
        return session('hspsessionid', '');
    }

    public function getSessionKey()
    {
        return session('hspsessionkey', '');
    }

    public function checkAuth()
    {
        return true;
    }

    public function reAuthenticate()
    {
        //itransact mode, the default, requires user input, so no automatic reauthentication will be done.
    }
}