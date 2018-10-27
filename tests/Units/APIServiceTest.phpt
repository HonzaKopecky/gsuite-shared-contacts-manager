<?php

namespace App\Tests\Units;

require_once '../bootstrap.php';

use App\Model\APIQuery;
use App\Model\APIService;

class APIServiceTest extends \Tester\TestCase
{
    public function testGetRequest() {
        $query = new APIQuery();
        $query->setExpectedResponseCode(APIQuery::HTTP_RESPONSE_OK);
        $query->setTarget("https://www.seznam.cz");
        $query->setMethod(APIQuery::HTTP_METHOD_GET);
        $service = new APIService();
        $service->send($query,"");
        \Tester\Assert::notEqual(null,$query->getResponse());
    }
}

(new APIServiceTest())->run();