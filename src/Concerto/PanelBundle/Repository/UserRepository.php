<?php

namespace Leap\PanelBundle\Repository;

use Leap\PanelBundle\Entity\User;

/**
 * UserRepository
 */
class UserRepository extends AEntityRepository {

    public function findAllExcept(User $user) {
        $query = $this->getEntityManager()->createQuery(
                        'SELECT u
            FROM LeapPanelBundle:User u
            WHERE u.id != :id'
                )->setParameter('id', $user->getId());
        return $query->getResult();
    }

}
