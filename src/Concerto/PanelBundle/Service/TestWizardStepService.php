<?php

namespace Leap\PanelBundle\Service;

use Leap\PanelBundle\Repository\TestWizardStepRepository;
use Leap\PanelBundle\Entity\TestWizardStep;
use Leap\PanelBundle\Entity\User;
use Leap\PanelBundle\Repository\TestWizardRepository;
use Leap\PanelBundle\Security\ObjectVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestWizardStepService extends ASectionService
{

    private $validator;
    private $testWizardRepository;

    public function __construct(
        TestWizardStepRepository $repository,
        ValidatorInterface $validator,
        TestWizardRepository $testWizardRepository,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->validator = $validator;
        $this->testWizardRepository = $testWizardRepository;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestWizardStep();
        }
        return $object;
    }

    public function getByTestWizard($wizard_id)
    {
        return $this->authorizeCollection($this->repository->findByWizard($wizard_id));
    }

    public function save($object_id, $title, $description, $order, $wizard)
    {
        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestWizardStep();
        }
        $object->setTitle($title);
        if ($description !== null) {
            $object->setDescription($description);
        }
        $object->setOrderNum($order);
        $object->setWizard($wizard);
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->update($object);
        return array("object" => $object, "errors" => $errors);
    }

    private function update(TestWizardStep $object, $flush = true)
    {
        $this->repository->save($object, $flush);
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

    public function clear($wizard_id)
    {
        $wizard = parent::authorizeObject($this->testWizardRepository->find($wizard_id));
        if ($wizard)
            $this->repository->deleteByTestWizard($wizard_id);
        return array("errors" => array());
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
    {
        $pre_queue = array();
        if (!array_key_exists("TestWizardStep", $map))
            $map["TestWizardStep"] = array();
        if (array_key_exists("id" . $obj["id"], $map["TestWizardStep"]))
            return array("errors" => null, "entity" => $map["TestWizardStep"]["id" . $obj["id"]]);

        $wizard = null;
        if (array_key_exists("TestWizard", $map) && array_key_exists("id" . $obj["wizard"], $map["TestWizard"])) {
            $wizard = $map["TestWizard"]["id" . $obj["wizard"]];
        }

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $parent_instruction = self::getObjectImportInstruction(array(
            "class_name" => "TestWizard",
            "id" => $obj["wizard"]
        ), $instructions);
        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($parent_instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert(null, $src_ent, $obj, $map, $queue, $wizard);
        } else if ($parent_instruction["action"] == 2 && $src_ent) {
            $map["TestWizardStep"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew(null, $obj, $map, $queue, $wizard);

        array_splice($queue, 1, 0, $obj["params"]);

        return $result;
    }

    protected function findConversionSource($obj, $map)
    {
        $wizard = $map["TestWizard"]["id" . $obj["wizard"]];
        $ent = $this->repository->findOneBy(array("wizard" => $wizard, "title" => $obj["title"]));
        if ($ent == null)
            return null;
        return $this->get($ent->getId());
    }

    protected function importNew($new_name, $obj, &$map, &$queue, $wizard)
    {
        $ent = new TestWizardStep();
        $ent->setColsNum($obj["colsNum"]);
        $ent->setDescription($obj["description"]);
        $ent->setOrderNum($obj["orderNum"]);
        $ent->setTitle($obj["title"]);
        $ent->setWizard($wizard);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent);
        $map["TestWizardStep"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    protected function importConvert($new_name, $src_ent, $obj, &$map, &$queue, $wizard)
    {
        $ent = $src_ent;
        $ent->setColsNum($obj["colsNum"]);
        $ent->setDescription($obj["description"]);
        $ent->setOrderNum($obj["orderNum"]);
        $ent->setTitle($obj["title"]);
        $ent->setWizard($wizard);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent);
        $map["TestWizardStep"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    public function authorizeObject($object)
    {
        if (!self::$securityOn)
            return $object;
        if ($object && $this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object->getWizard()))
            return $object;
        return null;
    }

}
