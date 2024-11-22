<?php

namespace Tests\Leap\PanelBundle\Controller\FunctionalTests;

use Symfony\Component\Yaml\Yaml;
use Tests\Leap\PanelBundle\AFunctionalTest;
use Leap\PanelBundle\Entity\ATopEntity;

class ViewTemplateControllerTest extends AFunctionalTest
{

    private static $repository;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("LeapPanelBundle:ViewTemplate");
    }

    protected function setUp()
    {
        parent::setUp();

        $client = self::createLoggedClient();

        $client->request("POST", "/admin/ViewTemplate/-1/save", array(
            "name" => "view",
            "html" => "html",
            "css" => "css",
            "js" => "js",
            "head" => "<link />",
            "description" => "description",
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
    }

    public function testCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/ViewTemplate/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "ViewTemplate",
                "id" => 1,
                "name" => "view",
                "description" => "description",
                "head" => "<link />",
                "html" => "html",
                "css" => "css",
                "js" => "js",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedBy" => "admin",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "lockedBy" => null,
                "directLockBy" => null
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testFormActionNew()
    {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/ViewTemplate/form/add");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
    }

    public function testFormActionEdit()
    {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/ViewTemplate/form/edit");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('View template source')")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
    }

    public function testDeleteAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/ViewTemplate/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);
        $this->assertCount(0, self::$repository->findAll());
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testExportAction($instructions, $format)
    {
        $client = self::createLoggedClient();
        $encodedInstructions = json_encode($instructions);

        $client->request("GET", "/admin/ViewTemplate/$encodedInstructions/export/$format");
        $content = null;
        switch ($format) {
            case "yml":
                $content = Yaml::parse($client->getResponse()->getContent());
                break;
            case "json":
                $content = json_decode($client->getResponse()->getContent(), true);
                break;
            case "compressed":
                $content = json_decode(gzuncompress($client->getResponse()->getContent()), true);
                break;

        }

        $this->assertArrayHasKey("hash", $content["collection"][0]);
        unset($content["collection"][0]["hash"]);

        $expected = array(
            array(
                'class_name' => 'ViewTemplate',
                'id' => 1,
                "starterContent" => false,
                'name' => 'view',
                'description' => 'description',
                'head' => '<link />',
                'html' => 'html',
                "css" => "css",
                "js" => "js",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "starterContent" => false,
                "groups" => ""
            ),
        );
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/x-download'));

        $this->assertEquals($expected, $content["collection"]);
    }

    public function testImportNewAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/ViewTemplate/import", array(
            "file" => "ViewTemplate_8.leap.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "ViewTemplate",
                    "id" => 8,
                    "name" => "some_template",
                    "rename" => "some_template",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null
                )
            )),
            "instant" => 1
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $decoded_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $decoded_response["result"]);
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testImportNewSameNameAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/ViewTemplate/import", array(
            "file" => "ViewTemplate_8.leap.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "ViewTemplate",
                    "id" => 8,
                    "name" => "some_template",
                    "rename" => "view",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "view"
                )
            )),
            "instant" => 1
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertCount(2, self::$repository->findAll());
        $decoded_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $decoded_response["result"]);
        $this->assertCount(1, self::$repository->findBy(array("name" => "view_1")));
    }

    public function testSaveActionNew()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/ViewTemplate/-1/save", array(
            "name" => "new_view",
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => array(
                "class_name" => "ViewTemplate",
                "id" => 2,
                "name" => "new_view",
                "description" => "",
                "head" => "",
                "html" => "",
                "css" => "",
                "js" => "",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => "admin",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionRename()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/ViewTemplate/1/save", array(
            "name" => "edited_view",
            "description" => "edited view description",
            "head" => "head",
            "html" => "html",
            "css" => "css",
            "js" => "js",
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => array(
                "class_name" => "ViewTemplate",
                "id" => 1,
                "name" => "edited_view",
                "description" => "edited view description",
                "head" => "head",
                "html" => "html",
                "css" => "css",
                "js" => "js",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => "admin",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionSameName()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/ViewTemplate/1/save", array(
            "name" => "view",
            "description" => "edited view description",
            "head" => "head",
            "html" => "html",
            "css" => "css",
            "js" => "js",
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => array(
                "class_name" => "ViewTemplate",
                "id" => 1,
                "name" => "view",
                "description" => "edited view description",
                "head" => "head",
                "html" => "html",
                "css" => "css",
                "js" => "js",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => "admin",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionNameAlreadyExists()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/ViewTemplate/-1/save", array(
            "name" => "new_view",
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => array(
                "class_name" => "ViewTemplate",
                "id" => 2,
                "name" => "new_view",
                "description" => "",
                "head" => "",
                "html" => "",
                "css" => "",
                "js" => "",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => "admin",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());

        $client->request("POST", "/admin/ViewTemplate/1/save", array(
            "name" => "new_view",
            "description" => "edited view description",
            "head" => "head",
            "html" => "html",
            "css" => "css",
            "js" => "js"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 1,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => null,
            "errors" => array("This name already exists in the system")
        ), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
    }

    public function exportDataProvider()
    {
        return array(
            array(array(
                "ViewTemplate" => array(
                    "id" => array(1),
                    "name" => array("view"),
                    "data" => array("0")
                )
            ), "yml"),
            array(array(
                "ViewTemplate" => array(
                    "id" => array(1),
                    "name" => array("view"),
                    "data" => array("0")
                )
            ), "json"),
            array(array(
                "ViewTemplate" => array(
                    "id" => array(1),
                    "name" => array("view"),
                    "data" => array("0")
                )
            ), "compressed")
        );
    }

}
