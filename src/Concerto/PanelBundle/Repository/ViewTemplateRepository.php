<?php

namespace Leap\PanelBundle\Repository;

use Leap\PanelBundle\Repository\AEntityRepository;

/**
 * ViewTemplateRepository
 */
class ViewTemplateRepository extends AEntityRepository
{
    public function findDirectlyLocked()
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->select("vt")->from("Leap\PanelBundle\Entity\ViewTemplate", "vt")->where("vt.directLockBy IS NOT NULL");
        return $qb->getQuery()->getResult();
    }
}
