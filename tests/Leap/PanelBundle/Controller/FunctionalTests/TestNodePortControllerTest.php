<?php

namespace Tests\Leap\PanelBundle\Controller\FunctionalTests;

use Tests\Leap\PanelBundle\AFunctionalTest;
use Leap\PanelBundle\Entity\ATopEntity;
use Leap\PanelBundle\Entity\Test;

class TestNodePortControllerTest extends AFunctionalTest
{

    private static $repository;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("LeapPanelBundle:TestNodePort");
    }

    protected function setUp()
    {
        self::truncateClass("LeapPanelBundle:Test");
        self::truncateClass("LeapPanelBundle:TestVariable");
        self::truncateClass("LeapPanelBundle:TestNode");
        self::truncateClass("LeapPanelBundle:TestNodePort");
        self::truncateClass("LeapPanelBundle:TestNodeConnection");

        $client = self::createLoggedClient();
        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "testFlow",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_REGULAR,
            "type" => Test::TYPE_FLOW,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test_s1",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_REGULAR,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestNode/-1/save", array(
            "flowTest" => 1,
            "sourceTest" => 2,
            "type" => 0,
            "posX" => 0,
            "posY" => 0,
            "title" => ""
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
    }

    public function testCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestNodePort/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestNodePort",
                "id" => 1,
                "node" => 3,
                "string" => "1",
                "defaultValue" => "1",
                "dynamic" => '0',
                "type" => 2,
                "exposed" => '1',
                "name" => "out",
                "pointer" => '0',
                "pointerVariable" => 'out',
                "variable" => 2,
                "value" => "0"
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodePort/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNull($entity);
    }

    public function testSaveActionEdit()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodePort/1/save", array(
            "node" => 1,
            "variable" => 2,
            "value" => "1",
            "string" => "0",
            "default" => "0",
            "dynamic" => "0",
            "exposed" => "1"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $expected = array(
            "class_name" => "TestNodePort",
            "id" => 1,
            "node" => 1,
            "string" => "0",
            "defaultValue" => "0",
            "dynamic" => '0',
            "type" => 2,
            "exposed" => '1',
            "name" => "out",
            "pointer" => '0',
            "pointerVariable" => 'out',
            "variable" => 2,
            "value" => "1"
        );

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => $expected
        ), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

}
