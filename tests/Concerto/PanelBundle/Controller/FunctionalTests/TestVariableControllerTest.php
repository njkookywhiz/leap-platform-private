<?php

namespace Tests\Leap\PanelBundle\Controller\FunctionalTests;

use Tests\Leap\PanelBundle\AFunctionalTest;
use Leap\PanelBundle\Entity\ATopEntity;
use Leap\PanelBundle\Entity\Test;

class TestVariableControllerTest extends AFunctionalTest
{

    private static $repository;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("LeapPanelBundle:TestVariable");
    }

    protected function setUp()
    {
        parent::setUp();

        $client = self::createLoggedClient();
        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_REGULAR,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "response",
            "test" => 1,
            "type" => 1,
            "passableThroughUrl" => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "param",
            "description" => "param desc",
            "test" => 1,
            "type" => 0,
            "passableThroughUrl" => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
    }

    public function testCollectionByTestAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestVariable/Test/1/collection');
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $expected = array(
            array(
                "class_name" => "TestVariable",
                "id" => 1,
                "name" => "out",
                "description" => "",
                "type" => 2,
                "test" => 1,
                "passableThroughUrl" => "0",
                "value" => "0",
                "parentVariable" => null
            ),
            array(
                "class_name" => "TestVariable",
                "id" => 2,
                "name" => "response",
                "description" => "",
                "type" => 1,
                "test" => 1,
                "passableThroughUrl" => "0",
                "value" => null,
                "parentVariable" => null
            ),
            array(
                "class_name" => "TestVariable",
                "id" => 3,
                "name" => "param",
                "description" => "param desc",
                "type" => 0,
                "test" => 1,
                "passableThroughUrl" => "0",
                "value" => null,
                "parentVariable" => null
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testParametersCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestVariable/Test/1/parameters/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestVariable",
                "id" => 3,
                "name" => "param",
                "description" => "param desc",
                "type" => 0,
                "test" => 1,
                "passableThroughUrl" => "0",
                "value" => null,
                "parentVariable" => null
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testReturnsCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestVariable/Test/1/returns/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestVariable",
                "id" => 2,
                "name" => "response",
                "description" => "",
                "type" => 1,
                "test" => 1,
                "passableThroughUrl" => "0",
                "value" => null,
                "parentVariable" => null
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testBranchesCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestVariable/Test/1/branches/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestVariable",
                "id" => 1,
                "name" => "out",
                "description" => "",
                "type" => 2,
                "test" => 1,
                "passableThroughUrl" => "0",
                "value" => "0",
                "parentVariable" => null
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveActionNew()
    {
        $client = self::createLoggedClient();
        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "param2",
            "type" => 0,
            "description" => "description",
            "test" => 1,
            "value" => "123",
            "passableThroughUrl" => "0"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => [],
            "object" => [
                "class_name" => "TestVariable",
                "id" => 4,
                "name" => "param2",
                "type" => 0,
                "description" => "description",
                "test" => 1,
                "value" => "123",
                "passableThroughUrl" => "0",
                "parentVariable" => null
            ]
        ], $decodedResponse);
        $this->assertCount(4, self::$repository->findAll());
    }

    public function testSaveActionRename()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestVariable/1/save", array(
            "name" => "param3",
            "description" => "edited var description",
            "type" => 0,
            "test" => 1,
            "value" => "123",
            "passableThroughUrl" => "0"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => [],
            "object" => [
                "class_name" => "TestVariable",
                "id" => 1,
                "name" => "param3",
                "type" => 0,
                "description" => "edited var description",
                "test" => 1,
                "value" => "123",
                "passableThroughUrl" => "0",
                "parentVariable" => null
            ]
        ], $decodedResponse);
        $this->assertCount(3, self::$repository->findAll());
    }

    public function testSaveActionSameName()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestVariable/3/save", array(
            "name" => "param",
            "description" => "edited var description",
            "type" => 0,
            "test" => 1,
            "value" => "123",
            "passableThroughUrl" => "0"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => [],
            "object" => [
                "class_name" => "TestVariable",
                "id" => 3,
                "name" => "param",
                "type" => 0,
                "description" => "edited var description",
                "test" => 1,
                "value" => "123",
                "passableThroughUrl" => "0",
                "parentVariable" => null
            ]
        ], $decodedResponse);
        $this->assertCount(3, self::$repository->findAll());
    }

    public function testSaveActionNameAlreadyExists()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "new_param",
            "description" => "new var description",
            "type" => 0,
            "test" => 1,
            "value" => "123",
            "passableThroughUrl" => "0"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => [],
            "object" => [
                "class_name" => "TestVariable",
                "id" => 4,
                "name" => "new_param",
                "type" => 0,
                "description" => "new var description",
                "test" => 1,
                "value" => "123",
                "passableThroughUrl" => "0",
                "parentVariable" => null
            ]
        ], $decodedResponse);
        $this->assertCount(4, self::$repository->findAll());

        $client->request("POST", "/admin/TestVariable/3/save", array(
            "name" => "new_param",
            "description" => "edited var description",
            "type" => 0,
            "test" => 1,
            "value" => "123",
            "passableThroughUrl" => "0"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 1,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => null,
            "errors" => array("Variable with that name and type already is assigned to the test")
        ], $decodedResponse);
        $this->assertCount(4, self::$repository->findAll());
    }

}
