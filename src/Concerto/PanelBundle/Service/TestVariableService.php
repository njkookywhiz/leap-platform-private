<?php

namespace Leap\PanelBundle\Service;

use Leap\PanelBundle\Entity\TestVariable;
use Leap\PanelBundle\Entity\Test;
use Leap\PanelBundle\Repository\TestRepository;
use Leap\PanelBundle\Repository\TestVariableRepository;
use Leap\PanelBundle\Entity\User;
use Leap\PanelBundle\Security\ObjectVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestVariableService extends ASectionService
{

    private $validator;
    private $testNodePortService;
    private $testNodeConnectionService;
    private $testRepository;

    public function __construct(
        TestVariableRepository $repository,
        ValidatorInterface $validator,
        TestNodePortService $portService,
        TestNodeConnectionService $connectionService,
        TestRepository $testRepository,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->validator = $validator;
        $this->testNodePortService = $portService;
        $this->testNodeConnectionService = $connectionService;
        $this->testRepository = $testRepository;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestVariable();
        }
        return $object;
    }

    public function getAllVariables($test_id)
    {
        return $this->authorizeCollection($this->repository->findByTest($test_id));
    }

    public function getParameters($test_id)
    {
        return $this->authorizeCollection($this->repository->findByTestAndType($test_id, 0));
    }

    public function getReturns($test_id)
    {
        return $this->authorizeCollection($this->repository->findByTestAndType($test_id, 1));
    }

    public function getBranches($test_id)
    {
        return $this->authorizeCollection($this->repository->findByTestAndType($test_id, 2));
    }

    public function saveCollection($serializedVariables, Test $test, $flush = true)
    {
        $result = array("errors" => array());
        if (!$serializedVariables)
            return $result;
        $variables = json_decode($serializedVariables, true);

        for ($i = 0; $i < count($variables); $i++) {
            $var = $variables[$i];
            $parentVariable = null;
            if ($var["parentVariable"])
                $parentVariable = $this->repository->find($var["parentVariable"]);
            $r = $this->save($var["id"], $var["name"], $var["type"], $var["description"], $var["passableThroughUrl"], array_key_exists("value", $var) ? $var["value"] : null, $test, $parentVariable, $flush);
            if (count($r["errors"]) > 0) {
                for ($a = 0; $a < count($r["errors"]); $a++) {
                    array_push($result["errors"], $r["errors"][$a]);
                }
            }
        }
        return $result;
    }

    public function save($object_id, $name, $type, $description, $passableThroughUrl, $value, $test, $parentVariable = null, $flush = true)
    {
        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestVariable();
        }
        $object->setName($name);
        $object->setType($type);
        if ($description !== null) {
            $object->setDescription($description);
        }
        if ($passableThroughUrl !== null) {
            $object->setPassableThroughUrl($passableThroughUrl == 1);
        }

        $object->setParentVariable($parentVariable);
        $object->setValue($value);
        $object->setTest($test);
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->update($object, $flush);
        return array("object" => $object, "errors" => $errors);
    }

    public function update(TestVariable $object, $flush = true)
    {
        $isNew = $object->getId() === null;
        $changeSet = $this->repository->getChangeSet($object);
        if ($isNew || !empty($changeSet)) {
            $this->repository->save($object, $flush);
            $this->onObjectSaved($object, $isNew, $flush);
        }
    }

    private function onObjectSaved(TestVariable $object, $isNew, $flush = true)
    {
        $this->updateChildVariables($object, $flush);
        $this->testNodePortService->onTestVariableSaved($object, $isNew, $flush);
        if (!$isNew)
            $this->testNodeConnectionService->onTestVariableSaved($object, $isNew, $flush);
    }

    public function createVariablesFromSourceTest(Test $dstTest, $flush = true)
    {
        $wizard = $dstTest->getSourceWizard();
        foreach ($wizard->getTest()->getVariables() as $variable) {
            $description = $variable->getDescription();
            $name = $variable->getName();
            $url = $variable->isPassableThroughUrl();
            $type = $variable->getType();
            $value = $variable->getValue();

            foreach ($wizard->getParams() as $param) {
                if ($param->getVariable()->getId() === $variable->getId()) {
                    $description = $param->getDescription();
                    $url = $param->isPassableThroughUrl();
                    $value = $param->getValue();
                    break;
                }
            }

            $this->save(0, $name, $type, $description, $url, $value, $dstTest, $variable, $flush);
        }
    }

    private function updateChildVariables(TestVariable $parentVariable, $flush = true)
    {
        $description = $parentVariable->getDescription();
        $name = $parentVariable->getName();
        $url = $parentVariable->isPassableThroughUrl();
        $type = $parentVariable->getType();
        $value = $parentVariable->getValue();

        foreach ($parentVariable->getTest()->getWizards() as $wizard) {
            foreach ($wizard->getResultingTests() as $test) {
                $found = false;
                foreach ($test->getVariables() as $variable) {
                    if ($variable->getParentVariable() && $variable->getParentVariable()->getId() == $parentVariable->getId()) {
                        $found = true;
                        $variable->setName($name);

                        $hasWizardParam = $wizard->getParamByName($variable->getName()) !== null;
                        if (!$hasWizardParam) {
                            $variable->setValue($value);
                            $variable->setPassableThroughUrl($url);
                        }

                        $this->update($variable, $flush);
                        break;
                    }
                }
                if (!$found) {
                    $this->save(0, $name, $type, $description, $url, $value, $test, $parentVariable, $flush);
                }
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
            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
    {
        $pre_queue = array();
        if (!array_key_exists("TestVariable", $map))
            $map["TestVariable"] = array();
        if (array_key_exists("id" . $obj["id"], $map["TestVariable"])) {
            return array("errors" => null, "entity" => $map["TestVariable"]["id" . $obj["id"]]);
        }

        $test = null;
        if ($obj["test"]) {
            if (array_key_exists("Test", $map) && array_key_exists("id" . $obj["test"], $map["Test"])) {
                $test = $map["Test"]["id" . $obj["test"]];
            }
        }

        $parentVariable = null;
        if (array_key_exists("TestVariable", $map) && $obj["parentVariable"]) {
            $parentVariable = $map["TestVariable"]["id" . $obj["parentVariable"]];
        }

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $parent_instruction = self::getObjectImportInstruction(array(
            "class_name" => "Test",
            "id" => $obj["test"]
        ), $instructions);
        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($parent_instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert(null, $src_ent, $obj, $map, $renames, $queue, $test, $parentVariable);
        } else if ($parent_instruction["action"] == 2 && $src_ent) {
            $map["TestVariable"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew(null, $obj, $map, $renames, $queue, $test, $parentVariable);
        return $result;
    }

    protected function importNew($new_name, $obj, &$map, $renames, &$queue, $test, $parentVariable)
    {
        $ent = new TestVariable();
        $ent->setName($obj["name"]);
        $ent->setDescription($obj["description"]);
        $ent->setTest($test);
        $ent->setType($obj["type"]);
        $ent->setPassableThroughUrl($obj["passableThroughUrl"] == "1");
        $ent->setValue($obj['value']);
        $ent->setParentVariable($parentVariable);

        $wizard = $test->getSourceWizard();
        if ($wizard && $parentVariable) {
            foreach ($wizard->getParams() as $param) {
                if ($param->getVariable()->getId() === $parentVariable->getId()) {
                    $val = $ent->getValue();
                    foreach ($renames as $class => $renameMap) {
                        foreach ($renameMap as $oldName => $newName) {
                            $def = $param->getDefinition();
                            $moded = TestWizardParamService::modifyPropertiesOnRename($newName, $class, $oldName, $param->getType(), $def, $val, true);
                            if ($moded) {
                                $ent->setValue($val);
                            }
                        }
                    }
                    break;
                }
            }
        }

        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent);
        $map["TestVariable"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map)
    {
        $test = $map["Test"]["id" . $obj["test"]];
        $type = $obj["type"];
        $name = $obj["name"];

        $ent = $this->repository->findOneBy(array(
            "test" => $test,
            "type" => $type,
            "name" => $name
        ));
        if ($ent == null)
            return null;
        return $this->get($ent->getId());
    }

    protected function importConvert($new_name, $src_ent, $obj, &$map, $renames, &$queue, $test, $parentVariable)
    {
        $old_ent = clone $src_ent;
        $ent = $src_ent;
        $ent->setName($obj["name"]);
        $ent->setDescription($obj["description"]);
        $ent->setTest($test);
        $ent->setType($obj["type"]);
        $ent->setPassableThroughUrl($obj["passableThroughUrl"] == "1");
        $ent->setValue($obj['value']);
        $ent->setParentVariable($parentVariable);

        $wizard = $test->getSourceWizard();
        if ($wizard && $parentVariable) {
            foreach ($wizard->getParams() as $param) {
                if ($param->getVariable()->getId() === $parentVariable->getId()) {
                    $val = $ent->getValue();
                    $def = $param->getDefinition();
                    foreach ($renames as $class => $renameMap) {
                        foreach ($renameMap as $oldName => $newName) {
                            $moded = TestWizardParamService::modifyPropertiesOnRename($newName, $class, $oldName, $param->getType(), $def, $val, true);
                            if ($moded) {
                                $ent->setValue($val);
                            }
                        }
                    }
                    break;
                }
            }
        }

        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent);
        $map["TestVariable"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    public function authorizeObject($object)
    {
        if (!self::$securityOn)
            return $object;
        if ($object && $this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object->getTest()))
            return $object;
        return null;
    }
}