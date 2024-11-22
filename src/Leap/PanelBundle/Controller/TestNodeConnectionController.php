<?php

namespace Leap\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Leap\PanelBundle\Service\TestService;
use Leap\PanelBundle\Service\TestNodeConnectionService;
use Leap\PanelBundle\Service\TestNodeService;
use Leap\PanelBundle\Service\TestNodePortService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestNodeConnectionController extends ASectionController
{

    const ENTITY_NAME = "TestNodeConnection";

    private $testService;
    private $testNodeService;
    private $testPortService;

    public function __construct(EngineInterface $templating, TestService $testService, TestNodeConnectionService $connectionService, TestNodeService $nodeService, TestNodePortService $portService, TranslatorInterface $translator)
    {
        parent::__construct($templating, $connectionService, $translator);

        $this->entityName = self::ENTITY_NAME;

        $this->testService = $testService;
        $this->testNodeService = $nodeService;
        $this->testPortService = $portService;
    }

    /**
     * @Route("/TestNodeConnection/fetch/{object_id}/{format}", name="TestNodeConnection_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/TestNodeConnection/collection/{format}", name="TestNodeConnection_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/TestNodeConnection/flow/{test_id}/collection", name="TestNodeConnection_collection_by_flow_test")
     * @param $test_id
     * @return Response
     */
    public function collectionByFlowTestAction($test_id)
    {
        return $this->templating->renderResponse('LeapPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getByFlowTest($test_id)
        ));
    }

    /**
     * @Route("/TestNodeConnection/{object_ids}/delete", name="TestNodeConnection_delete", methods={"POST"})
     * @param Request $request
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction(Request $request, $object_ids)
    {
        return parent::deleteAction($request, $object_ids);
    }

    /**
     * @Route("/TestNodeConnection/{object_id}/save", name="TestNodeConnection_save", methods={"POST"})
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

        $sourcePort = $request->get("sourcePort");
        $destinationPort = $request->get("destinationPort");

        $result = $this->service->save(
            $object_id,
            $this->testService->get($request->get("flowTest")),
            $this->testNodeService->get($request->get("sourceNode")),
            $sourcePort ? $this->testPortService->get($sourcePort) : null,
            $this->testNodeService->get($request->get("destinationNode")),
            $destinationPort ? $this->testPortService->get($destinationPort) : null,
            $request->get("returnFunction"),
            $request->get("default") === "1");
        return $this->getSaveResponse($result);
    }

}
