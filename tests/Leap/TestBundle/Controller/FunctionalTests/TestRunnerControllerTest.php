<?php

namespace Tests\Leap\TestBundle\Controller\FunctionalTests;

use Tests\Leap\PanelBundle\AFunctionalTest;
use Leap\PanelBundle\Entity\ATopEntity;
use Leap\PanelBundle\Entity\TestSession;
use Leap\PanelBundle\Entity\Test;
use Leap\PanelBundle\Service\TestSessionService;

class TestRunnerControllerTest extends AFunctionalTest {

    private static $repository;
    private static $testRepository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("LeapPanelBundle:TestSession");
        self::$testRepository = static::$entityManager->getRepository("LeapPanelBundle:Test");
    }

    protected function setUp() {
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

        $session = new TestSession();
        $session->setTest(self::$testRepository->find(1));
        $session->setClientIp("192.168.0.100");
        $session->setClientBrowser("Gecko");
        $session->setDebug(false);
        $session->setParams(json_encode(array()));
        $session->setHash(sha1("secret1"));
        $session->setStatus(TestSessionService::STATUS_RUNNING);
        self::$entityManager->persist($session);
        self::$entityManager->flush();
    }

    public function testSubmitNotAuthorizedSession() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.1");
        $client->request("POST", "/test/session/abc123/submit", array(
            "test_node_hash" => "someHash"
        ));
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

}
