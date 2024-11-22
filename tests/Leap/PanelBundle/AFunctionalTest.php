<?php

namespace Tests\Leap\PanelBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Leap\PanelBundle\Entity\User;
use Leap\PanelBundle\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Client;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;

abstract class AFunctionalTest extends WebTestCase {

    protected static $encoderFactory;
    protected static $entityManager;

    protected function setUp() {
        parent::setUp();

        static::truncateClass("LeapPanelBundle:DataTable");
        static::truncateClass("LeapPanelBundle:Test");
        static::truncateClass("LeapPanelBundle:TestNode");
        static::truncateClass("LeapPanelBundle:TestNodePort");
        static::truncateClass("LeapPanelBundle:TestNodeConnection");
        static::truncateClass("LeapPanelBundle:TestSession");
        static::truncateClass("LeapPanelBundle:TestSessionLog");
        static::truncateClass("LeapPanelBundle:TestWizardParam");
        static::truncateClass("LeapPanelBundle:TestWizardStep");
        static::truncateClass("LeapPanelBundle:TestWizard");
        static::truncateClass("LeapPanelBundle:TestVariable");
        static::truncateClass("LeapPanelBundle:ViewTemplate");
    }

    public static function setUpBeforeClass() {

        $client = static::createClient();
        self::$encoderFactory = $client->getContainer()->get("test.security.encoder_factory");
        self::$entityManager = $client->getContainer()->get("doctrine")->getManager();

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
        self::$entityManager->persist($user);
        self::$entityManager->flush();
    }

    public static function truncateClass($class) {
        $cmd = self::$entityManager->getClassMetadata($class);
        $connection = self::$entityManager->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();

        $prefix = "";
        if ($dbPlatform instanceof PostgreSqlPlatform) {
            $prefix.='ALTER SEQUENCE ' . $cmd->getTableName() . '_id_seq RESTART;';
        }
        if ($dbPlatform instanceof MySqlPlatform) {
            $prefix.='SET FOREIGN_KEY_CHECKS=0;';
        }

        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName()) . "";
        if ($dbPlatform instanceof PostgreSqlPlatform) {
            $q.= ' CASCADE';
        }
        $q.=";";

        $suffix = "";
        if ($dbPlatform instanceof MySqlPlatform) {
            $suffix.='SET FOREIGN_KEY_CHECKS=1;';
        }

        $connection->beginTransaction();
        $connection->executeUpdate($prefix . $q . $suffix);
        $connection->commit();
    }

    /**
     *
     * @return Client
     */
    public static function createLoggedClient() {
        $client = static::createClient();
        $client->followRedirects();
        $session = $client->getContainer()->get('session');
        $repo = $client->getContainer()->get("doctrine")->getRepository("LeapPanelBundle:User");
        $user = $repo->findOneBy(array("username" => "admin"));
        $firewall = 'admin_area';
        $token = new UsernamePasswordToken($user, null, $firewall, $user->getRoles());
        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
        return $client;
    }

}
