<?php

namespace Leap\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Leap\PanelBundle\Service\TestWizardService;
use Leap\PanelBundle\Service\TestWizardStepService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_WIZARD') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestWizardStepController extends ASectionController
{

    const ENTITY_NAME = "TestWizardStep";

    private $testWizardService;

    public function __construct(EngineInterface $templating, TestWizardStepService $service, TranslatorInterface $translator, TestWizardService $testWizardService)
    {
        parent::__construct($templating, $service, $translator);

        $this->entityName = self::ENTITY_NAME;
        $this->testWizardService = $testWizardService;
    }

    /**
     * @Route("/TestWizardStep/fetch/{object_id}/{format}", name="TestWizardStep_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/TestWizardStep/TestWizard/{wizard_id}/collection/{format}", name="TestWizardStep_collection_by_wizard", defaults={"format":"json"})
     * @param $wizard_id
     * @param string $format
     * @return Response
     */
    public function collectionByWizardAction($wizard_id, $format = "json")
    {
        return $this->templating->renderResponse('LeapPanelBundle::collection.' . $format . '.twig', array(
            'collection' => $this->service->getByTestWizard($wizard_id)
        ));
    }

    /**
     * @Route("/TestWizardStep/collection/{format}", name="TestWizardStep_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/TestWizardStep/{object_ids}/delete", name="TestWizardStep_delete", methods={"POST"})
     * @param Request $request
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction(Request $request, $object_ids)
    {
        return parent::deleteAction($request, $object_ids);
    }

    /**
     * @Route("/TestWizardStep/{object_id}/save", name="TestWizardStep_save", methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function saveAction(Request $request, $object_id)
    {
        if (!$this->service->canBeModified($object_id, $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $result = $this->service->save(
            $object_id,
            $request->get("title"),
            $request->get("description"),
            $request->get("orderNum"),
            $this->testWizardService->get($request->get("wizard")));
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/TestWizardStep/TestWizard/{wizard_id}/clear", name="TestWizardStep_clear", methods={"POST"})
     * @param Request $request
     * @param $wizard_id
     * @return Response
     */
    public function clearAction(Request $request, $wizard_id)
    {
        if (!$this->service->canBeModified($wizard_id, $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $this->service->clear($wizard_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
