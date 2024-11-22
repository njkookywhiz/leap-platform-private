<?php

namespace Leap\PanelBundle\Security;

use Leap\PanelBundle\Entity\DataTable;
use Leap\PanelBundle\Entity\Test;
use Leap\PanelBundle\Entity\TestNode;
use Leap\PanelBundle\Entity\TestNodeConnection;
use Leap\PanelBundle\Entity\TestNodePort;
use Leap\PanelBundle\Entity\TestSessionLog;
use Leap\PanelBundle\Entity\TestVariable;
use Leap\PanelBundle\Entity\TestWizard;
use Leap\PanelBundle\Entity\TestWizardParam;
use Leap\PanelBundle\Entity\TestWizardStep;
use Leap\PanelBundle\Entity\User;
use Leap\PanelBundle\Entity\ViewTemplate;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Leap\PanelBundle\Entity\ATopEntity;

class ObjectVoter extends Voter
{

    const ATTR_ACCESS = 'access';

    protected function supports($attribute, $object)
    {
        return ($object instanceof DataTable ||
                $object instanceof Test ||
                $object instanceof TestNode ||
                $object instanceof TestNodeConnection ||
                $object instanceof TestNodePort ||
                $object instanceof TestSessionLog ||
                $object instanceof TestVariable ||
                $object instanceof TestWizard ||
                $object instanceof TestWizardParam ||
                $object instanceof TestWizardStep ||
                $object instanceof ViewTemplate) && in_array($attribute, array(self::ATTR_ACCESS));
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        if (!$token->getUser() instanceof UserInterface || !$token->getUser() instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_ACCESS:
                //super admin or
                if ($token->getUser()->hasRoleName(User::ROLE_SUPER_ADMIN)) {
                    return true;
                }
                //public
                if ($object->getAccessibility() == ATopEntity::ACCESS_PUBLIC) {
                    return true;
                }
                //owner
                if ($object->getOwner() && $token->getUser()->getId() == $object->getOwner()->getId()) {
                    return true;
                }
                //group
                if ($object->getAccessibility() == ATopEntity::ACCESS_GROUP && $object->hasAnyFromGroup($token->getUser()->getGroupsArray())) {
                    return true;
                }
                break;
        }
        return false;
    }
}
