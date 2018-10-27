<?php

namespace App\Model;

use Nette\Database\ConnectionException;
use Nette\SmartObject;
use Tracy\Debugger;

/**
 * Class provides functionality to send an APIQuery to a server using curl PHP functionality.
 */
class APIService {
	use SmartObject;

    const CODE_AUTH = 401;

    /** Send an APIQuery to it's target with authorization token in header.
     * @param APIQuery $q
     * @param string $token
     */
    public function send(APIQuery &$q, $token) {
		Debugger::barDump("API REQUEST PERFORMED");
        $con = curl_init($q->getTarget());
        $headers = ['GData-Version: 3.0', 'Authorization: Bearer '.$token];

        if($q->getMethod() == APIQuery::HTTP_METHOD_GET)
            $this->setupGET($con, $headers);
        if($q->getMethod() == APIQuery::HTTP_METHOD_POST)
            $this->setupPOST($con, $headers, $q);
        if($q->getMethod() == APIQuery::HTTP_METHOD_DELETE)
            $this->setupDELETE($con, $headers, $q);
        if($q->getMethod() == APIQuery::HTTP_METHOD_PUT)
            $this->setupPUT($con, $headers, $q);

        curl_setopt($con, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($con);

        if(curl_errno($con) !== 0) {
            curl_close($con);
            throw new ConnectionException("Error occured during cURL request.");
        }

        $info = curl_getinfo($con);

        if($info['http_code'] !== $q->getExpectedResponseCode()) {
            curl_close($con);
            Debugger::log($response);
            throw new ConnectionException("Server responded with different status code: " . $info['http_code'], $info['http_code']);
        }

        $q->setResponseCode($info['http_code']);
        $q->setResponse($response);

        curl_close($con);
    }

    /** Setup the curl connection as a GET request
     * @param resource $con
     * @param array $headers
     */
    private function setupGET(&$con, &$headers) {
        curl_setopt($con, CURLOPT_HTTPGET, true);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
    }

    /** Setup the curl connection as POST request. Will also fill the body of the request.
     * @param resource $con
     * @param array $headers
     * @param APIQuery $query
     */
    private function setupPOST(&$con, array &$headers, APIQuery &$query) {
        curl_setopt($con, CURLOPT_CUSTOMREQUEST, APIQuery::HTTP_METHOD_POST);
        curl_setopt($con, CURLOPT_POSTFIELDS, $query->getBody());
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        $headers[] = 'Content-Type: '.$query->getContentType();
        $headers[] = 'Content-Length: ' . strlen($query->getBody());
    }

    /** Setup the curl connection as DELETE request. Will also fill the body of the request.
     * @param resource $con
     * @param array $headers
     * @param APIQuery $query
     */
    private function setupDELETE(&$con, array &$headers, APIQuery &$query) {
        curl_setopt($con, CURLOPT_CUSTOMREQUEST, APIQuery::HTTP_METHOD_DELETE);
        curl_setopt($con, CURLOPT_POSTFIELDS, $query->getBody());
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        $headers[] = 'Content-Type: '.$query->getContentType();
        $headers[] = 'Content-Length: ' . strlen($query->getBody());
        $headers[] = 'If-Match: *';
    }

    /** Setup the curl connection as PUT request. Will also fill the body of the request.
     * @param resource $con
     * @param array $headers
     * @param APIQuery $query
     */
    private function setupPUT(&$con, array &$headers, APIQuery &$query) {
        curl_setopt($con, CURLOPT_CUSTOMREQUEST, APIQuery::HTTP_METHOD_PUT);
        curl_setopt($con, CURLOPT_POSTFIELDS, $query->getBody());
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        $headers[] = 'Content-Type: '.$query->getContentType();
        $headers[] = 'Content-Length: ' . strlen($query->getBody());
        $headers[] = 'If-Match: *';
    }
}