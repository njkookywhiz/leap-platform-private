<?php

namespace Leap\PanelBundle\Repository;

/**
 * TestNodeConnectionRepository
 */
class TestNodeConnectionRepository extends AEntityRepository
{

    public function findByNodes($sourceNode, $destinationNode)
    {
        return $this->getEntityManager()->getRepository("LeapPanelBundle:TestNodeConnection")->findBy(array("sourceNode" => $sourceNode, "destinationNode" => $destinationNode));
    }
}
