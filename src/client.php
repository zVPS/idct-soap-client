<?php

namespace IDCT\Networking\Soap;

class Client extends \SoapClient
{

    /**
     * Defines if request will be sent with a basic http auth
     * @var boolean 
     */
    protected $auth = false;

    /**
     * If auth set to true this will be sent as login for the basic http auth
     * @var string
     */
    protected $authLogin;

    /**
     * If auth set to true this will be sent as password for the basic http auth
     * @var string
     */
    protected $authPassword;

    /**
     * Associative array of custom headers to sent together with the request
     * @var array
     */
    protected $customHeaders = array();

    /**
     * If set to false then request will not fail in case of invalid SSL cert
     * @var boolean
     */
    protected $ignoreCertVerify = true;

    /**
     * Connection negotiation timeout in seconds 
     * @var int
     */
    protected $negotiationTimeout = 0;

    /**
     * Number of retries until exception is thrown
     * @var int
     */
    protected $persistanceFactor = 1;

    /**
     * Read timeout (after a successful connection) in seconds)
     * @var int
     */
    protected $persistanceTimeout = 0;

    /**
     * Constructor of the new object. Creates an instance of the new SoapClient.
     * Sets default values of the timeouts and number of retries.
     * 
     * @param sting $wsdl Url of the WebService's wsdl
     * @param array $options PHP SoapClient's array of options
     * @param int $negotiationTimeout Connection timeout in seconds. 0 to disable.
     * @param int $persistanceFactor Number of retries.
     * @param int $persistanceTimeout Read timeout in seconds. 0 to disable.
     */
    public function __construct($wsdl, $options, $verifyCertificate = true, $negotiationTimeout = 0, $persistanceFactor = 1, $persistanceTimeout = 0)
    {

        $this->setNegotiationTimeout($negotiationTimeout)
            ->setPersistanceFactor($persistanceFactor)
            ->setPersistanceTimeout($persistanceTimeout)
            ->setIgnoreCertVerify($verifyCertificate)
            ->applyDefaultHeaders();

        if (array_key_exists("login", $options)) {
            $this->auth = true;
            $this->authLogin = $options['login'];
            if (array_key_exists("password", $options)) {
                $this->authPassword = $options['password'];
            } else {
                $this->authPassword = null;
            }
        }

        parent::__construct($wsdl, $options);
    }

    /**
     * Sets the negotiation (connection) timeout in seconds.
     * Throws an exception in case a negative value.
     * Set 0 to disable the timeout.
     * @param int $timeoutInSeconds
     */
    public function setNegotiationTimeout($timeoutInSeconds)
    {
        if ($timeoutInSeconds < 0) {
            throw new \Exception('Negotiation timeout must be a positive integer or 0 to disable.');
        } else {
            $this->negotiationTimeout = $timeoutInSeconds;
        }

        return $this;
    }

    /**
     * Gets the negotiation (connection) timeout in seconds
     * @return int
     */
    public function getNegotiationTimeout()
    {
        return $this->negotiationTimeout;
    }

    /**
     * Sets the maximum number of full data read (connection+read) retries.
     * Value must be at least equal to one.
     * @param int $attempts
     */
    public function setPersistanceFactor($attempts)
    {
        if ($attempts < 1) {
            throw new \Exception('Number of attempts must be at least equal to 1.');
        } else {
            $this->persistanceFactor = $attempts;
        }

        return $this;
    }

    /**
     * Gets the maximum number of full data read (connection+read) retries.
     * @return int
     */
    public function getPersistanceFactor()
    {
        return $this->persistanceFactor;
    }

    /**
     * Sets the data read (after a successful negotiation) timeout in seconds.
     * Throws an exception when value is negative.
     * Set 0 to disable timeout.
     * @param type $timeoutInSeconds
     */
    public function setPersistanceTimeout($timeoutInSeconds)
    {
        if ($timeoutInSeconds < 0) {
            throw new \Exception('Persistance timeout must be a positive integer or 0 to disable.');
        } else {
            $this->persistanceTimeout = $timeoutInSeconds;
        }

        return $this;
    }

    /**
     * Gets the data read (after negotiation) timeout in seconds.
     * @return int
     */
    public function getPersistanceTimeout()
    {
        return $this->persistanceTimeout;
    }

    /**
     * Sets an array of custom http headers to be sent together with the request.
     * Throws an exception if not an array.
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            $this->customHeaders = $headers;
            return $this;
        } else {
            throw new \Exception('Not an array.');
        }
    }

    /**
     * Gets the array of custom headers to be sent together with the request.
     * @return array
     */
    public function getHeaders()
    {
        return $this->customHeaders;
    }

    /**
     * Sets a custom header to be sent together with the request.
     * Throws an exception if header's name is not at least 1 char long.
     * @param string $header
     * @param string $value
     */
    public function setHeader($header, $value)
    {
        if (strlen($header) < 1) {
            throw new \Exception('Header must be a string.');
        }
        $this->customHeaders[$header] = $value;
        return $this;
    }

    /**
     * Gets a custom header from the array of headers to be sent with the request or null.
     * @param string $header
     * @return string
     */
    public function getHeader($header)
    {
        return $this->customHeaders[$header];
    }

    /**
     * Sets a boolean value of the flag which indicates if request should not worry about invalid SSL certificate.
     * @param boolean $value
     */
    public function setIgnoreCertVerify($value)
    {
        $this->ignoreCertVerify = $value;
        return $this;
    }

    /**
     * Gets the value of the flag which indicates if request should not worry about invalid SSL certificate.
     * @return boolean
     */
    public function getIgnoreCertVerify()
    {
        return $this->ignoreCertVerify;
    }

    /**
     * Return colon seperated headers in a string
     * for curl http header option
     * @param array $headers
     * @return string
     */
    private function buildHeaders(array $headers)
    {
        $headersFormatted = array();

        foreach ($headers as $header => $value) {
            $headersFormatted[] = $header . ": " . $value;
        }

        return $headersFormatted;
    }

    /**
     * 
     * @return \IDCT\Networking\Soap\Client
     */
    private function applyDefaultHeaders()
    {
        $defaultHeaders = array(
            'Content-Type' => 'type/application-xml',
        );

        foreach ($defaultHeaders as $headerKey => $headerValue) {
            $this->setHeader($headerKey, $headerValue);
        }

        return $this;
    }

    /**
     * @param resource $ch cURL Handle
     * @param type $rawResponse
     * @return \IDCT\Networking\Soap\Response
     */
    private function buildResponse($ch, $rawResponse)
    {

        /** @todo remove these */
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);

        $response = new Response();

        if (curl_errno($ch) <> 0 && (curl_errno($ch) === 28 || curl_errno($ch) === 7)) {
            $response->setStatus(Response::STATUS_TIMEOUT);
            $response->setError('Service unavailable, please try again shortly.');
        } elseif (curl_errno($ch) <> 0) {
            $response->setStatus(Response::STATUS_FAIL);
            $response->setError('Something really went bang.... Ka-Blamo!');/** @todo improve error */
        } else {
            $response->setStatus(Response::STATUS_SUCCESS);
            $response->setResponse($rawResponse);
        }

        return $response;
    }

    /**
     * 
     * @param int $attempt
     * @return boolean
     */
    private function exhaustedAttempts($attempt)
    {
        return ($attempt >= $this->persistanceFactor) ? true : false;
    }

    /**
     * Performs the request using cUrl, should not be called directly, but through
     * normal usage of PHP SoapClient (using particular methods of the WebService).
     * Throws an exception if connection or data read fails more than the number of retries (persistanceFactor).
     * Returns data response / content.
     * 
     * @param string $request Request (XML/Data) to be sent to the WebService parsed by SoapClient.
     * @param string $location WebService URL.
     * @param string $action Currently not used. In the signature for compatibility with SoapClient. TODO: to be used with particular soap versions.
     * @param int $version Currently not used. In the signature for compatibility with SoapClient. TODO: add Soap Version selection.
     * @param bool $one_way Currently not used. In the signature for compatibility with SoapClient.
     * @return mixed
     */
    public function __doRequest($request, $location, $action, $version, $one_way = null)
    {
        for ($attempt = 1; $attempt < $this->persistanceFactor; $attempt++) {
            $ch = curl_init($location);
            curl_setopt($ch, CURLOPT_HEADER, false);
            if ($one_way !== true) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->negotiationTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->persistanceTimeout);
            $defaultHeaders = array(
                'SOAPAction' => '"' . $action . '"'
            );
            $headers = array_merge($defaultHeaders, $this->customHeaders);
            $headersFormatted = $this->buildHeaders($headers);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headersFormatted);
            if ($this->getIgnoreCertVerify() === true) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            }

            if ($this->auth === true) {
                $credentials = $this->authLogin;
                $credentials .= ($this->authPassword !== null) ? ":" . $this->authPassword : "";
                curl_setopt($ch, CURLOPT_USERPWD, $credentials);
            }

            $rawResponse = curl_exec($ch);
            $response = $this->buildResponse($ch, $rawResponse);

            if ($this->exhaustedAttempts($attempt)) {
                break;
            }
        }
        return $response;
    }

}
