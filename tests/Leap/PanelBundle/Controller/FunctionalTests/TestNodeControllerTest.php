<?php

namespace Tests\Leap\PanelBundle\Controller\FunctionalTests;

use Tests\Leap\PanelBundle\AFunctionalTest;
use Leap\PanelBundle\Entity\ATopEntity;
use Leap\PanelBundle\Entity\Test;

class TestNodeControllerTest extends AFunctionalTest
{

    private static $repository;
    private static $portRepository;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("LeapPanelBundle:TestNode");
        self::$portRepository = static::$entityManager->getRepository("LeapPanelBundle:TestNodePort");
    }

    protected function setUp()
    {
        parent::setUp();

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

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "response",
            "test" => 2,
            "type" => 1,
            "passableThroughUrl" => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "params",
            "test" => 2,
            "type" => 0,
            "passableThroughUrl" => '0'
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

        $client->request('POST', '/admin/TestNode/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestNode",
                "id" => 1,
                "type" => 1,
                "posX" => 15000,
                "posY" => 15000,
                "title" => "",
                "flowTest" => 1,
                "sourceTest" => 1,
                "sourceTestName" => "testFlow",
                "sourceTestDescription" => "description",
                "ports" => array()
            ),
            array(
                "class_name" => "TestNode",
                "id" => 2,
                "type" => 2,
                "posX" => 15500,
                "posY" => 15100,
                "title" => "",
                "flowTest" => 1,
                "sourceTest" => 1,
                "sourceTestName" => "testFlow",
                "sourceTestDescription" => "description",
                "ports" => array()
            ),
            array(
                "class_name" => "TestNode",
                "id" => 3,
                "type" => 0,
                "posX" => 0,
                "posY" => 0,
                "title" => "",
                "flowTest" => 1,
                "sourceTest" => 2,
                "sourceTestName" => "test_s1",
                "sourceTestDescription" => "description",
                "ports" => array(
                    array(
                        "class_name" => "TestNodePort",
                        "id" => 1,
                        "node" => 3,
                        "string" => "1",
                        "defaultValue" => "1",
                        "dynamic" => '0',
                        "type" => 0,
                        "exposed" => '0',
                        "name" => "params",
                        "pointer" => '0',
                        "pointerVariable" => 'params',
                        "variable" => 4,
                        "value" => null
                    ),
                    array(
                        "class_name" => "TestNodePort",
                        "id" => 2,
                        "node" => 3,
                        "string" => "1",
                        "defaultValue" => "1",
                        "dynamic" => '0',
                        "type" => 1,
                        "exposed" => '0',
                        "name" => "response",
                        "pointer" => '0',
                        "pointerVariable" => 'response',
                        "variable" => 3,
                        "value" => null
                    ),
                    array(
                        "class_name" => "TestNodePort",
                        "id" => 3,
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
                )
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testCollectionByFlowTestAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestNode/flow/1/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestNode",
                "id" => 1,
                "type" => 1,
                "posX" => 15000,
                "posY" => 15000,
                "title" => "",
                "flowTest" => 1,
                "sourceTest" => 1,
                "sourceTestName" => "testFlow",
                "sourceTestDescription" => "description",
                "ports" => array()
            ),
            array(
                "class_name" => "TestNode",
                "id" => 2,
                "type" => 2,
                "posX" => 15500,
                "posY" => 15100,
                "title" => "",
                "flowTest" => 1,
                "sourceTest" => 1,
                "sourceTestName" => "testFlow",
                "sourceTestDescription" => "description",
                "ports" => array()
            ),
            array(
                "class_name" => "TestNode",
                "id" => 3,
                "type" => 0,
                "posX" => 0,
                "posY" => 0,
                "title" => "",
                "flowTest" => 1,
                "sourceTest" => 2,
                "sourceTestName" => "test_s1",
                "sourceTestDescription" => "description",
                "ports" => array(
                    array(
                        "class_name" => "TestNodePort",
                        "id" => 1,
                        "node" => 3,
                        "string" => "1",
                        "defaultValue" => "1",
                        "dynamic" => '0',
                        "type" => 0,
                        "exposed" => '0',
                        "name" => "params",
                        "pointer" => '0',
                        "pointerVariable" => 'params',
                        "variable" => 4,
                        "value" => null
                    ),
                    array(
                        "class_name" => "TestNodePort",
                        "id" => 2,
                        "node" => 3,
                        "string" => "1",
                        "defaultValue" => "1",
                        "dynamic" => '0',
                        "type" => 1,
                        "exposed" => '0',
                        "name" => "response",
                        "pointer" => '0',
                        "pointerVariable" => 'response',
                        "variable" => 3,
                        "value" => null
                    ),
                    array(
                        "class_name" => "TestNodePort",
                        "id" => 3,
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
                )
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));

        $client->request('POST', '/admin/TestNode/flow/2/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array();
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNode/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
        $this->assertCount(3, self::$portRepository->findAll());
    }

    public function testSaveActionNew()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNode/-1/save", array(
            "flowTest" => 1,
            "sourceTest" => 2,
            "type" => 0,
            "posX" => 100,
            "posY" => 100,
            "title" => ""
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $expected = array(
            "class_name" => "TestNode",
            "id" => 4,
            "type" => 0,
            "posX" => 100,
            "posY" => 100,
            "title" => "",
            "flowTest" => 1,
            "sourceTest" => 2,
            "sourceTestName" => "test_s1",
            "sourceTestDescription" => "description",
            "ports" => array(
                array(
                    "class_name" => "TestNodePort",
                    "id" => 4,
                    "node" => 4,
                    "string" => "1",
                    "defaultValue" => "1",
                    "dynamic" => '0',
                    "type" => 0,
                    "exposed" => '0',
                    "name" => "params",
                    "pointer" => '0',
                    "pointerVariable" => 'params',
                    "variable" => 4,
                    "value" => null
                ),
                array(
                    "class_name" => "TestNodePort",
                    "id" => 5,
                    "node" => 4,
                    "string" => "1",
                    "defaultValue" => "1",
                    "dynamic" => '0',
                    "type" => 1,
                    "exposed" => '0',
                    "name" => "response",
                    "pointer" => '0',
                    "pointerVariable" => 'response',
                    "variable" => 3,
                    "value" => null
                ),
                array(
                    "class_name" => "TestNodePort",
                    "id" => 6,
                    "node" => 4,
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
            )
        );

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => $expected
        ], $decodedResponse);
        $this->assertCount(4, self::$repository->findAll());
    }

    public function testSaveActionEdit()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNode/3/save", array(
            "flowTest" => 1,
            "sourceTest" => 2,
            "type" => 0,
            "posX" => 200,
            "posY" => 200,
            "title" => "comment",
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $expected = array(
            "class_name" => "TestNode",
            "id" => 3,
            "type" => 0,
            "posX" => 200,
            "posY" => 200,
            "title" => "comment",
            "flowTest" => 1,
            "sourceTest" => 2,
            "sourceTestName" => "test_s1",
            "sourceTestDescription" => "description",
            "ports" => array(
                array(
                    "class_name" => "TestNodePort",
                    "id" => 1,
                    "node" => 3,
                    "string" => "1",
                    "defaultValue" => "1",
                    "dynamic" => '0',
                    "type" => 0,
                    "exposed" => '0',
                    "name" => "params",
                    "pointer" => '0',
                    "pointerVariable" => 'params',
                    "variable" => 4,
                    "value" => null
                ),
                array(
                    "class_name" => "TestNodePort",
                    "id" => 2,
                    "node" => 3,
                    "string" => "1",
                    "defaultValue" => "1",
                    "dynamic" => '0',
                    "type" => 1,
                    "exposed" => '0',
                    "name" => "response",
                    "pointer" => '0',
                    "pointerVariable" => 'response',
                    "variable" => 3,
                    "value" => null
                ),
                array(
                    "class_name" => "TestNodePort",
                    "id" => 3,
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
            )
        );

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => $expected
        ), $decodedResponse);
        $this->assertCount(3, self::$repository->findAll());
    }

}
