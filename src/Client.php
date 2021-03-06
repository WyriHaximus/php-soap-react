<?php

namespace Clue\React\Soap;

use Clue\React\Buzz\Browser;
use Exception;
use SoapClient;
use Clue\React\Soap\Protocol\ClientEncoder;
use Clue\React\Soap\Protocol\ClientDecoder;
use Clue\React\Buzz\Message\Response;
use React\Promise\Deferred;

class Client
{
    private $wsdl;
    private $browser;
    private $encoder;
    private $decoder;

    public function __construct($wsdl, Browser $browser, ClientEncoder $encoder = null, ClientDecoder $decoder = null)
    {
        if ($encoder === null) {
            $encoder = new ClientEncoder($wsdl, $browser);
        }

        if ($decoder === null) {
            $decoder = new ClientDecoder($wsdl);
        }

        $this->wsdl = $wsdl;
        $this->browser = $browser;
        $this->encoder = $encoder;
        $this->decoder = $decoder;
    }

    public function soapCall($name, $args)
    {
        try {
            $this->encoder->__soapCall($name, $args);
        } catch (\Exception $e) {
            $deferred = new Deferred();
            $deferred->reject($e);
            return $deferred->promise();
        }

        $promise = $this->encoder->pending;
        $this->encoder->pending = null;

        return $promise->then(
            array($this, 'handleResponse'),
            array($this, 'handleError')
        );
    }

    public function handleResponse(Response $response)
    {
        return $this->decoder->decode((string)$response->getBody());
    }

    public function handleError(Exception $error)
    {
        throw $error;
    }

    public function getFunctions()
    {
        return $this->encoder->__getFunctions();
    }

    public function getTypes()
    {
        return $this->encoder->__getTypes();
    }
}
