<?php

namespace Leap\PanelBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Leap\PanelBundle\Service\TestVariableService;
use Symfony\Component\HttpFoundation\Request;
use Leap\PanelBundle\Service\TestService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestVariableController extends ASectionController
{

    const ENTITY_NAME = "TestVariable";

    private $testService;

    public function __construct(EngineInterface $templating, TestVariableService $service, TranslatorInterface $translator, TestService $testService)
    {
        parent::__construct($templating, $service, $translator);

        $this->entityName = self::ENTITY_NAME;
        $this->testService = $testService;
    }

    /**
     * @Route("/TestVariable/fetch/{object_id}/{format}", name="TestVariable_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/TestVariable/Test/{test_id}/collection", name="TestVariable_by_test_collection")
     * @param $test_id
     * @return Response
     */
    public function collectionByTestAction($test_id)
    {
        $collection = $this->service->getAllVariables($test_id);
        return $this->templating->renderResponse("LeapPanelBundle::collection.json.twig", array("collection" => $collection));
    }

    /**
     * @Route("/TestVariable/Test/{test_id}/parameters/collection", name="TestVariable_parameters_collection")
     * @param $test_id
     * @return Response
     */
    public function parametersCollectionAction($test_id)
    {
        $collection = $this->service->getParameters($test_id);
        return $this->templating->renderResponse("LeapPanelBundle::collection.json.twig", array("collection" => $collection));
    }

    /**
     * @Route("/TestVariable/Test/{test_id}/returns/collection", name="TestVariable_returns_collection")
     * @param $test_id
     * @return Response
     */
    public function returnsCollectionAction($test_id)
    {
        $collection = $this->service->getReturns($test_id);
        return $this->templating->renderResponse("LeapPanelBundle::collection.json.twig", array("collection" => $collection));
    }

    /**
     * @Route("/TestVariable/Test/{test_id}/branches/collection", name="TestVariable_branches_collection")
     * @param $test_id
     * @return Response
     */
    public function branchesCollectionAction($test_id)
    {
        $collection = $this->service->getBranches($test_id);
        return $this->templating->renderResponse("LeapPanelBundle::collection.json.twig", array("collection" => $collection));
    }

    /**
     * @Route("/TestVariable/{object_id}/save", name="TestVariable_save", methods={"POST"})
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
            $request->get("name"),
            $request->get("type"),
            $request->get("description"),
            $request->get("passableThroughUrl"),
            $request->get("value"),
            $this->testService->get($request->get("test"))
        );
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/TestVariable/{object_ids}/delete", name="TestVariable_delete", methods={"POST"})
     * @param Request $request
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction(Request $request, $object_ids)
    {
        return parent::deleteAction($request, $object_ids);
    }
}
