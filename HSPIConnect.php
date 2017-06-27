<?php


namespace Adietz\HSPIConnect;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as GZRequest;
use Illuminate\Support\Facades\Log;



/**
 * Class HSPIConnect
 * @package Adietz\HSPIConnect
 */
class HSPIConnect
{

    /**
     * @var serverurl string
     */
    private $serverurl = "";
    /**
     * @var meditracAuthKey string
     */
    private $meditracAuthKey = "";
    /**
     * @var meditracSessionKey string
     */
    private $meditracSessionKey = "";
    /**
     * @var meditracSessionId string
     */
    private $meditracSessionId = "";
    /**
     * @var iconnectkey string
     */
    private $iconnectkey = "";
    /**
     * @var mconnectkey string
     */
    private $mconnectkey = "";
    /**
     * @var meditracservicesurl string
     */
    private $meditracservicesurl = "";
    /**
     * @var itransactservicesurl string
     */
    private $itransactservicesurl = "";
    /**
     * @var masqueradepassword string
     */
    private $masqueradepassword = "";
    /**
     * @var options array
     */
    private $options = [];
    /**
     * @var mode \adietz\HSPIConnect\Modes\Mode
     */
    private $modeHandler;
    /**
     * @var method string
     */
    private $method;

    /**
     * HSPIConnect constructor.
     */
    function __construct()
    {
        $this->serverurl = env('HSP_ICONNECT_URL', '');
        $this->mconnectkey = env('HSP_MCONNECT_KEY', '');
        $this->iconnectkey = env('HSP_ICONNECT_KEY', '');
        $this->meditracservicesurl = env('HSP_MSERV_URL', '');
        $this->itransactservicesurl = env('HSP_ISERV_URL', '');
        $this->masqueradepassword = env('HSP_GU_PASS', '');

    }


    /**
     * @param $url
     * @param $method
     * @param $mode
     * @param array $options
     * @return bool|\Psr\Http\Message\StreamInterface|\SimpleXMLElement
     */
    public function execute($url, $method, $mode, $options = [])
    {
        $this->modeHandler = $this->getModeHandler($mode);
        $this->method = $method;
        $attempts = 0;
        $numattempts = 3;
        $result = false;
        $this->options = $options;
        do {

            try {
                $result = $this->doCall($url, $options);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Log::error($e->getBody());
                $this->modeHandler->reAuthenticate();
                $result = false;
            }
            $attempts++;


        } while ($attempts < $numattempts && $result == false);
        Log::info(strtoupper($this->modeHandler->getMode()) . ' mode call to ' . $url . ' is complete.');
        return $result;
    }

    /**
     * @param $url
     * @param array $options
     * @return \Psr\Http\Message\StreamInterface|\SimpleXMLElement
     * @throws HSPIConnectException
     */
    public function doCall($url, $options = [])
    {
        $time_start = microtime(true);
        Log::info(strtoupper($this->modeHandler->getMode()) . ' mode call to ' . $url . ' is being attempted.');

        if (!$this->modeHandler->checkAuth()) {
            throw new HSPIConnectException('Authentication not successful');
        }
        $request = new GZRequest(
            $this->method,
            $this->modeHandler->getUrl() . $url,
            $this->modeHandler->getModeHeaders($this->getHeadersForMethod()),
            $options['PostBody'] ?? ''
        );

        $client = new Client([
            'timeout' => $options['TimeOut'] ?? env('HSP_ICONNECT_TIMEOUT', 10),
            'verify' => env('IConnectVerifySSLConnections', true),
        ]);

        try {
            $response = $client->send($request);
        } catch (ClientException $e) {

            Log::error($e->getResponse()->getBody()->getContents());
            $response = $e->getResponse();

        }

        $error = $this->errorCheck($response, $options);

        // log the time and call made for performance monitoring.
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        Log::info($this->modeHandler->getMode() . ' ' . $this->method . ' call to ' . $this->modeHandler->getUrl() . $url . ' completed in ' . $time . ' seconds');


        return $this->modeHandler->response($response);
    }


    public function getModeHandler($mode)
    {

        switch (strtoupper($mode)) {

            case "ITRANSACT":
                return new ModeHandlers\ITransactModeHandler;
            case "MEDITRAC":
                return new ModeHandlers\MeditracModeHandler();
            case "MASQ":
                return new ModeHandlers\MasqueradeModeHandler();
            case "AUTH":
                return new ModeHandlers\AuthModeHandler();
            case "MAUTH":
                return new ModeHandlers\MeditracAuthModeHandler();
            case "DOCUMENT":
                return new ModeHandlers\DocumentModeHandler();
            default:

                return new ModeHandlers\ITransactModeHandler;

        }
    }


    /**
     * @param $response
     * @param array $options
     * @return bool
     */
    public function errorCheck($response, $options = [])
    {
        if ($options['RawData'] ?? false) {
            return false;
        }

        if (strtoupper($this->modeHandler->getMode()) == "DOCUMENT") {
            return false;
        }

        $data = simplexml_load_string($response->getBody());
        if (!$data) {
            //response body could not be converted to XML
            $errors = libxml_get_errors();
            $xmlerrors = implode("|", $errors);
            return true;

        }

        if ($data->ErrorOccurred == 'true') {
            //handle the different types of errors

            if (str_contains(strtoupper($data->ErrorMessage), "SESSION KEY")) {
                $this->meditracReAuth();
            }
        }
        return false;

    }


    public function getHeadersForMethod()
    {
        $headers = [];

        switch (strtoupper($this->method)) {
            case "POST":
            case "PUT":
                $headers['Content-Type'] = "application/xml";
                break;
            default:
                break;
        }

        return array_merge($headers);
    }


}
