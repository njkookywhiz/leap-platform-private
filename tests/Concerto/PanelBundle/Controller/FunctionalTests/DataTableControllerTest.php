<?php

namespace Tests\Leap\PanelBundle\Controller\FunctionalTests;

use Symfony\Component\Yaml\Yaml;
use Tests\Leap\PanelBundle\AFunctionalTest;

class DataTableControllerTest extends AFunctionalTest
{

    private static $repository;
    private static $driver_class;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("LeapPanelBundle:DataTable");
        self::$driver_class = get_class(static::$entityManager->getConnection()->getDatabasePlatform());
    }

    protected function setUp()
    {
        parent::setUp();

        $this->dropTable("main_table");
        $this->dropTable("main_table_1");
        $this->dropTable("imported_table");
        $this->dropTable("new_table");
        $this->dropTable("edited_table");

        //creating main table
        $client = self::createLoggedClient();
        $client->request("POST", "/admin/DataTable/-1/save", array(
            "name" => "main_table",
            "description" => "table description",
            "accessibility" => 0
        ));

        $errorMsg = $client->getResponse()->isSuccessful() ? "" : $client->getCrawler()->filter("title")->text();
        $this->assertTrue($client->getResponse()->isSuccessful(), $errorMsg);
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/DataTable/1/row/insert");
        $client->request("POST", "/admin/DataTable/1/row/1/update", array(
            "values" => array("temp" => "temp1")
        ));
        $client->request("POST", "/admin/DataTable/1/row/insert");
        $client->request("POST", "/admin/DataTable/1/row/2/update", array(
            "values" => array("temp" => "temp2")
        ));
    }

    private function dropTable($name)
    {
        $fromSchema = static::$entityManager->getConnection()->getSchemaManager()->createSchema();
        $toSchema = clone $fromSchema;
        try {
            $toSchema->dropTable($name);

            $sql = $fromSchema->getMigrateToSql($toSchema, static::$entityManager->getConnection()->getDatabasePlatform());
            foreach ($sql as $query) {
                static::$entityManager->getConnection()->executeQuery($query);
            }
        } catch (\Exception $ex) {

        }
    }

    public function testCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/DataTable/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "DataTable",
                "id" => 1,
                "name" => "main_table",
                "description" => "table description",
                "columns" => array(
                    array(
                        'name' => 'id',
                        'type' => 'bigint',
                        'nullable' => false,
                        'length' => ''
                    ),
                    array(
                        'name' => 'temp',
                        'type' => 'text',
                        'nullable' => false,
                        'length' => ''
                    )
                ),
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedBy" => 'admin',
                "lockedBy" => null,
                "directLockBy" => null
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testFormActionNew()
    {
        $client = self::createLoggedClient();
        $crawler = $client->request(
            "GET", "/admin/DataTable/form/add"
        );
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
    }

    public function testFormActionEdit()
    {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/DataTable/form/edit");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Data table structure')")->count());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Table data')")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
    }

    public function testDeleteAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/delete");
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

    /**
     * @dataProvider exportDataProvider
     */
    public function testExportAction($instructions, $format)
    {
        $client = self::createLoggedClient();
        $encodedInstructions = json_encode($instructions);

        $client->request("GET", "/admin/DataTable/$encodedInstructions/export/$format");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/x-download'));

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

        $this->assertEquals(array(array(
            'class_name' => 'DataTable',
            'id' => 1,
            'name' => 'main_table',
            'description' => 'table description',
            'accessibility' => 0,
            "archived" => "0",
            "starterContent" => false,
            "groups" => "",
            'columns' => array(
                array(
                    'name' => 'id',
                    'type' => 'bigint',
                    'nullable' => false,
                    'length' => ''
                ),
                array(
                    'name' => 'temp',
                    'type' => 'text',
                    'nullable' => false,
                    'length' => ''
                )
            ))), $content["collection"]);
    }

    public function testImportNewAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/import", array(
            "file" => "DataTable_1.leap.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "DataTable",
                    "id" => 8,
                    "name" => "imported_table",
                    "rename" => "imported_table",
                    "action" => "0",
                    "data" => "1",
                    "starter_content" => false,
                    "existing_object_name" => null
                )
            )),
            "instant" => 1
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $decoded_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $decoded_response["result"]);
        $new_entity = self::$repository->find(2);
        $this->assertNotNull($new_entity);
    }

    public function testImportNewSameNameAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/import", array(
            "file" => "DataTable_1.leap.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "DataTable",
                    "id" => 8,
                    "name" => "imported_table",
                    "rename" => "main_table",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "main_table"
                )
            )),
            "instant" => 1
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertCount(2, self::$repository->findAll());
        $decoded_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $decoded_response["result"]);
        $this->assertCount(1, self::$repository->findBy(array("name" => "main_table_1")));
    }

    public function testSaveActionNew()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/-1/save", array(
            "name" => "new_table",
            "accessibility" => 0
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => array(
                "class_name" => "DataTable",
                "id" => 2,
                "name" => "new_table",
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "description" => "",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "columns" => array(),
                "updatedBy" => "admin",
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionRename()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/save", array(
            "name" => "edited_table",
            "description" => "edited table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => array(
                "class_name" => "DataTable",
                "id" => 1,
                "name" => "edited_table",
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "description" => "edited table description",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "columns" => $decodedResponse["object"]['columns'],
                "updatedBy" => "admin",
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionSameName()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/save", array(
            "name" => "main_table",
            "description" => "edited table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => array(
                "class_name" => "DataTable",
                "id" => 1,
                "name" => "main_table",
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "description" => "edited table description",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "columns" => $decodedResponse["object"]['columns'],
                "updatedBy" => "admin",
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionNameAlreadyExists()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/-1/save", array(
            "name" => "new_table",
            "description" => "table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => array(
                "class_name" => "DataTable",
                "id" => 2,
                "name" => "new_table",
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "description" => "table description",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "columns" => $decodedResponse["object"]['columns'],
                "updatedBy" => "admin",
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());

        $client->request("POST", "/admin/DataTable/1/save", array(
            "name" => "new_table",
            "description" => "edited table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 1,
            "object" => null,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array("This name already exists in the system")
        ), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNotNull($entity);
        $this->assertEquals("main_table", $entity->getName());
        $this->assertEquals("table description", $entity->getDescription());
    }

    public function testColumnCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array(
                "name" => "id",
                "type" => "bigint",
                "nullable" => false,
                'length' => ''
            ),
            array(
                "name" => "temp",
                "type" => "text",
                "nullable" => false,
                'length' => ''
            )
        ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testDataCollectionAction()
    {
        $client = self::createLoggedClient();
        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "temp1"),
                array("id" => 2, "temp" => "temp2")
            ),
            "count" => 2
        );

        $client->request("POST", "/admin/DataTable/1/data/collection");

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDataCollectionActionPrefixed()
    {
        $client = self::createLoggedClient();
        $expected = array(
            "content" => array(
                array("col_id" => 1, "col_temp" => "temp1"),
                array("col_id" => 2, "col_temp" => "temp2")
            ),
            "count" => 2
        );

        $client->request("POST", "/admin/DataTable/1/data/collection/1");

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    /**
     * Is this test meaningful anymore?
     */
    public function testDataSectionAction()
    {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/DataTable/1/data/section");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Add row')")->count());
    }

    public function testDeleteColumnAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/temp/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(array(
            "name" => "id",
            "type" => "bigint",
            "nullable" => false,
            'length' => ''
        )), json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteRowAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/row/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
        ], $decodedResponse);

        $expected = array(
            "content" => array(
                array("id" => 2, "temp" => "temp2")
            ),
            "count" => 1
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveColumnActionNew()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/0/save", array(
            "name" => "new_col",
            "type" => "text"
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array()
        ), json_decode($client->getResponse()->getContent(), true));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array(
                "name" => "id",
                "type" => "bigint",
                "nullable" => false,
                'length' => ''
            ),
            array(
                "name" => "temp",
                "type" => "text",
                "nullable" => false,
                'length' => ''
            ),
            array(
                "name" => "new_col",
                "type" => "text",
                "nullable" => false,
                'length' => ''
            )
        ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveColumnActionSameName()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/temp/save", array(
            "name" => "temp",
            "type" => "string"
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array(
                "name" => "id",
                "type" => "bigint",
                "nullable" => false,
                'length' => ''
            ),
            array(
                "name" => "temp",
                "type" => "string",
                "nullable" => false,
                'length' => '1024'
            )
        ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveColumnActionAlreadyExists()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/temp/save", array(
            "name" => "id",
            "type" => "text"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 1, "errors" => array("This column already exists in the table")), json_decode($client->getResponse()->getContent(), true));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array(
                "name" => "id",
                "type" => "bigint",
                "nullable" => false,
                'length' => ''
            ),
            array(
                "name" => "temp",
                "type" => "text",
                "nullable" => false,
                'length' => ''
            )
        ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveColumnAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/temp/save", array(
            "name" => "new_temp",
            "type" => "string"
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $this->assertEquals(array(
            "result" => 0,
            "errors" => array()
        ), json_decode($client->getResponse()->getContent(), true));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array(
                "name" => "id",
                "type" => "bigint",
                "nullable" => false,
                'length' => ''
            ),
            array(
                "name" => "new_temp",
                "type" => "string",
                "nullable" => false,
                'length' => '1024'
            )
        ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testInsertRowAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/row/insert");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);

        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "temp1"),
                array("id" => 2, "temp" => "temp2"),
                array("id" => 3, "temp" => "")
            ),
            "count" => 3
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testUpdateRowAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/row/2/update", array(
            "values" => array("temp" => "updated_temp2")
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);

        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "temp1"),
                array("id" => 2, "temp" => "updated_temp2")
            ),
            "count" => 2
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testUpdateRowActionPrefixed()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/row/2/update/1", array(
            "values" => array("col_temp" => "updated_temp2")
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);

        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "temp1"),
                array("id" => 2, "temp" => "updated_temp2")
            ),
            "count" => 2
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testImportCsvAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/csv/1/1/,/%22/import", array(
            "file" => "csv_table.csv"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);

        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "export1"),
                array("id" => 2, "temp" => "export2")
            ),
            "count" => 2
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function exportDataProvider()
    {
        return array(
            array(array(
                "DataTable" => array(
                    "id" => array(1),
                    "name" => array("main_table"),
                    "data" => array("1")
                )
            ), "yml"),
            array(array(
                "DataTable" => array(
                    "id" => array(1),
                    "name" => array("main_table"),
                    "data" => array("1")
                )
            ), "json"),
            array(array(
                "DataTable" => array(
                    "id" => array(1),
                    "name" => array("main_table"),
                    "data" => array("1")
                )
            ), "compressed")
        );
    }

}
