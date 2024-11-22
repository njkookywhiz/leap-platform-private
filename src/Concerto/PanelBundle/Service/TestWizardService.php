<?php

namespace Leap\PanelBundle\Service;

use Leap\PanelBundle\Entity\TestWizard;
use Leap\PanelBundle\Repository\TestWizardRepository;
use Leap\PanelBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestWizardService extends AExportableSectionService
{

    private $testService;
    public $testVariableService;
    public $testNodePortService;
    private $testWizardParamService;
    private $testWizardStepService;

    public function __construct(
        TestWizardRepository $repository,
        ValidatorInterface $validator,
        TestService $testService,
        TestVariableService $testVariableService,
        TestNodePortService $testNodePortService,
        TestWizardStepService $stepService,
        TestWizardParamService $paramService,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $validator, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->testService = $testService;
        $this->testVariableService = $testVariableService;
        $this->testNodePortService = $testNodePortService;
        $this->testWizardStepService = $stepService;
        $this->testWizardParamService = $paramService;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = null;
        if (is_numeric($object_id)) {
            $object = parent::get($object_id, $createNew, $secure);
        } else {
            $object = $this->repository->findOneByName($object_id);
            if ($secure) {
                $object = $this->authorizeObject($object);
            }
        }
        if ($createNew && $object === null) {
            $object = new TestWizard();
        }
        return $object;
    }

    public function save($object_id, $name, $description, $accessibility, $archived, $owner, $groups, $test, $serializedSteps)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestWizard();
            $object->setOwner($user);
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }

        if (!self::$securityOn || $this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setOwner($owner);
            $object->setGroups($groups);
        }

        $object->setArchived($archived);
        if ($test !== null) {
            $object->setTest($test);
        }
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->update($object);
        $this->updateParamValues($serializedSteps);
        return array("object" => $object, "errors" => $errors);
    }

    private function update(TestWizard $object, $flush = true)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $object->setUpdatedBy($user);
        $this->repository->save($object, $flush);
    }

    public function updateParamValues($serializedSteps)
    {
        if (!$serializedSteps)
            return;
        $steps = json_decode($serializedSteps, true);
        foreach ($steps as $step) {
            if (!array_key_exists("params", $step)) {
                continue;
            }
            foreach ($step["params"] as $param) {
                $obj = $this->testWizardParamService->get($param['id']);
                $old_obj = clone $obj;
                $obj->setValue($param["value"]);
                $obj->setOrder($param["order"]);
                $obj->setDefinition($param["definition"]);
                $this->testWizardParamService->update($obj, $old_obj);
            }
        }
    }

    public function delete($object_ids, $secure = true)
    {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if ($object === null)
                continue;

            if (count($object->getResultingTests()) > 0) {
                array_push($result, array("object" => $object, "errors" => array("validate.test.wizards.delete.referenced")));
                continue;
            }

            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function convertToExportable($array, $instruction = null, $secure = true)
    {
        $array = parent::convertToExportable($array, $instruction, $secure);
        unset($array["testName"]);
        return $array;
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
    {
        $pre_queue = array();
        if (!array_key_exists("TestWizard", $renames))
            $renames["TestWizard"] = array();
        if (!array_key_exists("TestWizard", $map))
            $map["TestWizard"] = array();
        if (array_key_exists("id" . $obj["id"], $map["TestWizard"]))
            return array("errors" => null, "entity" => $map["TestWizard"]["id" . $obj["id"]]);

        $test = null;
        if (array_key_exists("Test", $map) && array_key_exists("id" . $obj["test"], $map["Test"])) {
            $test = $map["Test"]["id" . $obj["test"]];
            if (!$test) {
                foreach ($queue as $elem) {
                    if ($elem["class_name"] == "Test" && $elem["id"] == $obj["test"]) {
                        array_push($pre_queue, $elem);
                        break;
                    }
                }
            }
        }
        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $instruction = self::getObjectImportInstruction($obj, $instructions);
        $old_name = $instruction["existing_object_name"];
        $new_name = $this->getNextValidName($this->formatImportName($instruction["rename"], $obj), $instruction["action"], $old_name);
        if ($instruction["action"] != 2 && $old_name != $new_name) {
            $renames["TestWizard"][$old_name] = $new_name;
        }

        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert($new_name, $src_ent, $obj, $map, $queue, $test);
            if (array_key_exists("clean", $instruction) && $instruction["clean"] == 1) $this->cleanConvert($result["entity"], $obj);
        } else if ($instruction["action"] == 2 && $src_ent) {
            $map["TestWizard"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew($new_name, $obj, $map, $queue, $test);

        if ($instruction["action"] != 2) {
            array_splice($queue, 1, 0, $obj["steps"]);
        }

        return $result;
    }

    private function cleanConvert(TestWizard $entity, $importArray)
    {
        foreach ($entity->getSteps() as $currentStep) {
            $found = false;
            foreach ($importArray["steps"] as $importStep) {
                if ($currentStep->getTitle() == $importStep["title"]) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->repository->delete($currentStep);
            }
        }
    }

    protected function importNew($new_name, $obj, &$map, &$queue, $test)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $starter_content = $obj["name"] == $new_name ? $obj["starterContent"] : false;

        $ent = new TestWizard();
        $ent->setName($new_name);
        $ent->setTest($test);
        $ent->setDescription($obj["description"]);
        $ent->setOwner($user);
        $ent->setStarterContent($starter_content);
        $ent->setAccessibility($obj["accessibility"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent);
        $map["TestWizard"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map)
    {
        return $this->get($obj["name"]);
    }

    protected function importConvert($new_name, $src_ent, $obj, &$map, &$queue, $test)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $ent = $src_ent;
        $ent->setName($new_name);
        $ent->setTest($test);
        $ent->setDescription($obj["description"]);
        $ent->setOwner($user);
        $ent->setStarterContent($obj["starterContent"]);
        $ent->setAccessibility($obj["accessibility"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent);
        $map["TestWizard"]["id" . $obj["id"]] = $ent;

        return array("errors" => null, "entity" => $ent);
    }
}
