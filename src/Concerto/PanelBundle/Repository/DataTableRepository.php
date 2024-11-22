<?php

namespace Leap\PanelBundle\Repository;

/**
 * DataTableRepository
 */
class DataTableRepository extends AEntityRepository
{
    public function findDirectlyLocked()
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->select("dt")->from("Leap\PanelBundle\Entity\DataTable", "dt")->where("dt.directLockBy IS NOT NULL");
        return $qb->getQuery()->getResult();
    }
}