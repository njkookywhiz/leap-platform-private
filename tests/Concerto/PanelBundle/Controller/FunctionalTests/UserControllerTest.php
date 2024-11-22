<?php

namespace Tests\Leap\PanelBundle\Controller\FunctionalTests;

use Tests\Leap\PanelBundle\AFunctionalTest;
use Leap\PanelBundle\Entity\ATopEntity;
use Leap\PanelBundle\Entity\User;
use Leap\PanelBundle\Entity\Role;

class UserControllerTest extends AFunctionalTest
{

    private static $repository;

    public static function setUpBeforeClass()
    {
        $client = static::createClient();
        self::$encoderFactory = $client->getContainer()->get("test.security.encoder_factory");
        self::$entityManager = $client->getContainer()->get("doctrine")->getManager();
        self::$repository = static::$entityManager->getRepository("LeapPanelBundle:User");
    }

    protected function setUp()
    {
        $repo = self::$entityManager->getRepository("LeapPanelBundle:User");
        foreach ($repo->findAll() as $user) {
            self::$entityManager->remove($user);
        }
        self::$entityManager->flush();
        static::truncateClass("LeapPanelBundle:User");
        static::truncateClass("LeapPanelBundle:Role");

        $role = null;
        $roles = array(
            User::ROLE_FILE,
            User::ROLE_TABLE,
            User::ROLE_TEMPLATE,
            User::ROLE_TEST,
            User::ROLE_WIZARD,
            User::ROLE_SUPER_ADMIN
        );
        foreach ($roles as $r) {
            $role = new Role();
            $role->setName($r);
            $role->setRole($r);
            self::$entityManager->persist($role);
            self::$entityManager->flush();
        }

        $user = new User();
        $user->setEmail("username@domain.com");
        $user->setUsername("admin");
        $user->addRole($role);
        $encoder = self::$encoderFactory->getEncoder($user);
        $password = $encoder->encodePassword("admin", $user->getSalt());
        $passwordConfirmation = $encoder->encodePassword("admin", $user->getSalt());
        $user->setPassword($password);
        $user->setPasswordConfirmation($passwordConfirmation);
        $user->setAccessibility(ATopEntity::ACCESS_PUBLIC);
        self::$entityManager->persist($user);
        self::$entityManager->flush();
    }

    public function testCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/User/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "User",
                "id" => 1,
                "username" => "admin",
                "email" => "username@domain.com",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedBy" => "-",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                'archived' => '0',
                'role_super_admin' => '1',
                'role_test' => '0',
                'role_template' => '0',
                'role_table' => '0',
                'role_file' => '0',
                'role_wizard' => '0',
                'owner' => 1,
                'groups' => '',
                'starterContent' => false,
                "lockedBy" => null,
                "directLockBy" => null
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testFormActionNew()
    {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/User/form/add");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.username']")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='password'][ng-model='object.password']")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='password'][ng-model='object.passwordConfirmation']")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.email']")->count());
    }

    public function testFormActionEdit()
    {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/User/form/edit");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.username']")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='password'][ng-model='object.password']")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='password'][ng-model='object.passwordConfirmation']")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.email']")->count());
    }

    public function testDeleteAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/User/1/delete");
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

    public function testSaveActionNew()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/User/-1/save", array(
            "username" => "new_user",
            "email" => "new@user.com",
            "password" => "pass",
            "passwordConfirmation" => "pass",
            "accessibility" => ATopEntity::ACCESS_PUBLIC,
            'role_super_admin' => '1',
            'role_test' => '0',
            'role_template' => '0',
            'role_table' => '0',
            'role_file' => '0',
            'role_wizard' => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => array(
                "class_name" => "User",
                "id" => 2,
                "username" => "new_user",
                "email" => "new@user.com",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => "admin",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                'role_super_admin' => '1',
                'role_test' => '0',
                'role_template' => '0',
                'role_table' => '0',
                'role_file' => '0',
                'role_wizard' => '0',
                "archived" => "0",
                "owner" => 2,
                "groups" => "",
                'starterContent' => false,
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionRename()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/User/1/save", array(
            "username" => "renamed_user",
            "email" => "new@user.com",
            "password" => "pass",
            "passwordConfirmation" => "pass",
            "accessibility" => ATopEntity::ACCESS_PUBLIC,
            'role_super_admin' => '1',
            'role_test' => '0',
            'role_template' => '0',
            'role_table' => '0',
            'role_file' => '0',
            'role_wizard' => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => array(
                "class_name" => "User",
                "id" => 1,
                "username" => "renamed_user",
                "email" => "new@user.com",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => "renamed_user",
                'role_super_admin' => '1',
                'role_test' => '0',
                'role_template' => '0',
                'role_table' => '0',
                'role_file' => '0',
                'role_wizard' => '0',
                "archived" => "0",
                "owner" => 1,
                "groups" => "",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                'starterContent' => false,
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionSameName()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/User/1/save", array(
            "username" => "admin",
            "email" => "new@user.com",
            "password" => "pass",
            "passwordConfirmation" => "pass",
            "accessibility" => ATopEntity::ACCESS_PUBLIC,
            'role_super_admin' => '1',
            'role_test' => '0',
            'role_template' => '0',
            'role_table' => '0',
            'role_file' => '0',
            'role_wizard' => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => array(
                "class_name" => "User",
                "id" => 1,
                "username" => "admin",
                "email" => "new@user.com",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => "admin",
                'role_super_admin' => '1',
                'role_test' => '0',
                'role_template' => '0',
                'role_table' => '0',
                'role_file' => '0',
                'role_wizard' => '0',
                "archived" => "0",
                "owner" => 1,
                "groups" => "",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                'starterContent' => false,
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionNameAlreadyExists()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/User/-1/save", array(
            "username" => "new_user",
            "email" => "new@user.com",
            "password" => "pass",
            "passwordConfirmation" => "pass",
            "accessibility" => ATopEntity::ACCESS_PUBLIC,
            'role_super_admin' => '1',
            'role_test' => '0',
            'role_template' => '0',
            'role_table' => '0',
            'role_file' => '0',
            'role_wizard' => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => array(
                "class_name" => "User",
                "id" => 2,
                "username" => "new_user",
                "email" => "new@user.com",
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => "admin",
                'role_super_admin' => '1',
                'role_test' => '0',
                'role_template' => '0',
                'role_table' => '0',
                'role_file' => '0',
                'role_wizard' => '0',
                "archived" => "0",
                "owner" => 2,
                "groups" => "",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                'starterContent' => false,
                "lockedBy" => null,
                "directLockBy" => null
            )), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());

        $client->request("POST", "/admin/User/1/save", array(
            "username" => "new_user",
            "email" => "new2@user.com",
            "password" => "pass",
            "passwordConfirmation" => "pass",
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 1,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => null,
            "errors" => array("This login already exists in the system")
        ), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
    }

}
