<?php

namespace Leap\PanelBundle\Repository;

/**
 * TestWizardRepository
 */
class TestWizardRepository extends AEntityRepository
{
    public function findDirectlyLocked()
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->select("tw")->from("Leap\PanelBundle\Entity\TestWizard", "tw")->where("tw.directLockBy IS NOT NULL");
        return $qb->getQuery()->getResult();
    }
}
