<?php

namespace App\Model;

use Nette\SmartObject;

/** Class represents a query that is going to be send to Google. It holds all important information to be able to contact Google API.
 */
class APIQuery
{
	use SmartObject;

    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_DELETE = 'DELETE';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_RESPONSE_OK = 200;
    const HTTP_RESPONSE_CREATED = 201;
    const HTTP_RESPONSE_NOT_FOUND = 404;
    const CONTENT_ATOM = "application/atom+xml";

    /** @var string */
    private $headers;
    /** @var string */
    private $body;
    /** @var string */
    private $response;
    /** @var int */
    private $responseCode;
    /** @var string */
    private $target;
    /** @var string */
    private $method;
    /** @var int */
    private $expectedResponseCode;
    /** @var string */
    private $contentType;

    /**
     * @return string
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $headers
     * @return APIQuery
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return APIQuery
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     * @return APIQuery
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @param int $responseCode
     * @return APIQuery
     */
    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param string $target
     * @return APIQuery
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     * @return APIQuery
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpectedResponseCode()
    {
        return $this->expectedResponseCode;
    }

    /**
     * @param int $expectedResponseCode
     * @return APIQuery
     */
    public function setExpectedResponseCode($expectedResponseCode)
    {
        $this->expectedResponseCode = $expectedResponseCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     * @return APIQuery
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }




}