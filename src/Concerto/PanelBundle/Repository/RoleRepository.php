<?php

namespace Leap\PanelBundle\Repository;

/**
 * RoleRepository
 */
class RoleRepository extends AEntityRepository {

    public function findOneByRole($role) {
        return $this->getEntityManager()->getRepository("LeapPanelBundle:Role")->findOneBy(array("role" => $role));
    }

}
