<?php

namespace Leap\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Leap\PanelBundle\Entity\TestNode;
use Leap\PanelBundle\Entity\TestVariable;
use Leap\PanelBundle\Entity\TestNodeConnection;
use Leap\PanelBundle\Entity\TestWizardParam;
use \Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Leap\PanelBundle\Repository\TestNodePortRepository")
 * @UniqueEntity(fields={"node","type","name"}, ignoreNull="node", message="validate.test.ports.unique")
 * @ORM\HasLifecycleCallbacks
 */
class TestNodePort extends AEntity implements \JsonSerializable
{

    /**
     * @ORM\ManyToOne(targetEntity="TestNode", inversedBy="ports")
     */
    private $node;

    /**
     * @ORM\ManyToOne(targetEntity="TestVariable", inversedBy="ports")
     */
    private $variable;

    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="sourcePort", cascade={"remove"}, orphanRemoval=true)
     */
    private $sourceForConnections;

    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="destinationPort", cascade={"remove"}, orphanRemoval=true)
     */
    private $destinationForConnections;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $string;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $defaultValue;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $dynamic;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $exposed;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     * @Assert\Length(min="1", max="64", minMessage="validate.test.ports.name.min", maxMessage="validate.test.ports.name.max")
     * @Assert\NotBlank(message="validate.test.ports.name.blank")
     * @Assert\Regex("/^\.?[a-zA-Z][a-zA-Z0-9_]*(?<!_)$/", message="validate.test.ports.name.incorrect")
     */
    private $name;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $pointer;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $pointerVariable;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->string = true;
        $this->defaultValue = true;
        $this->dynamic = false;
        $this->exposed = false;
        $this->pointer = false;
        $this->pointerVariable = "";
        $this->sourceForConnections = new ArrayCollection();
        $this->destinationForConnections = new ArrayCollection();
    }

    public function __toString()
    {
        return "TestNodePort (#" . $this->getId() . ", name:" . $this->getName() . ")";
    }

    public function getOwner()
    {
        return $this->getNode()->getFlowTest()->getOwner();
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return TestNodePort
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set node
     *
     * @param TestNode $node
     * @return TestNodePort
     */
    public function setNode($node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Get node
     *
     * @return TestNode
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Set variable
     *
     * @param TestVariable $var
     * @return TestNodePort
     */
    public function setVariable($var)
    {
        $this->variable = $var;

        return $this;
    }

    /**
     * Get variable
     *
     * @return TestVariable
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Returns if a port value should be treated as string.
     *
     * @return boolean
     */
    public function isString()
    {
        return $this->string;
    }

    /**
     * Set if port value should be treated as string.
     *
     * @param boolean $string
     */
    public function setString($string)
    {
        $this->string = $string;
    }

    /**
     * Returns true if port has default value.
     *
     * @return boolean
     */
    public function hasDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set whether port has default value.
     *
     * @param boolean $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Returns true if port is dynamic.
     *
     * @return boolean
     */
    public function isDynamic()
    {
        return $this->dynamic;
    }

    /**
     * Set whether port is dynamic.
     *
     * @param boolean $dynamic
     * @return TestNodePort
     */
    public function setDynamic($dynamic)
    {
        $this->dynamic = $dynamic;
        return $this;
    }

    /**
     * Returns true if port is exposed.
     *
     * @return boolean
     */
    public function isExposed()
    {
        return $this->exposed;
    }

    /**
     * Set whether port is exposed.
     *
     * @param boolean $exposed
     * @return TestNodePort
     */
    public function setExposed($exposed)
    {
        $this->exposed = $exposed;
        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return TestNodePort
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return TestNodePort
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns true if port is pointer.
     *
     * @return boolean
     */
    public function isPointer()
    {
        return $this->pointer;
    }

    /**
     * Set whether port is pointer.
     *
     * @param boolean $pointer
     * @return TestNodePort
     */
    public function setPointer($pointer)
    {
        $this->pointer = $pointer;
        return $this;
    }

    /**
     * Get pointer variable name
     *
     * @return string
     */
    public function getPointerVariable()
    {
        return $this->pointerVariable;
    }

    /**
     * Set pointer variable
     *
     * @param string $name
     * @return TestNodePort
     */
    public function setPointerVariable($name)
    {
        $this->pointerVariable = $name;

        return $this;
    }

    /**
     * Get connections where port is source
     *
     * @return array
     */
    public function getSourceForConnections()
    {
        //return $this->getNode()->getFlowTest()->getNodesConnectionsBySourcePort($this);
        return $this->sourceForConnections->toArray();
    }

    public function getSourceForConnectionsByDefaultReturnFunction($defaultReturnFunction)
    {
        return $this->sourceForConnections->filter(function (TestNodeConnection $connection) use ($defaultReturnFunction) {
            return $connection->hasDefaultReturnFunction() === $defaultReturnFunction;
        })->toArray();
    }

    public function removeSourceForConnection(TestNodeConnection $connection)
    {
        $this->sourceForConnections->removeElement($connection);
        return $this;
    }

    public function addSourceForConnection(TestNodeConnection $connection)
    {
        $this->sourceForConnections->add($connection);
        return $this;
    }

    public function isSourceForConnection(TestNodeConnection $connection)
    {
        return $this->sourceForConnections->contains($connection);
    }

    /**
     * Get connections where port is destination
     *
     * @return array
     */
    public function getDestinationForConnections()
    {
        //return $this->getNode()->getFlowTest()->getNodesConnectionsByDestinationPort($this);
        return $this->destinationForConnections->toArray();
    }

    public function addDestinationForConnection(TestNodeConnection $connection)
    {
        $this->destinationForConnections->add($connection);
        return $this;
    }

    public function removeDestinationForConnection(TestNodeConnection $connection)
    {
        $this->destinationForConnections->removeElement($connection);
        return $this;
    }

    public function isDestinationForConnection(TestNodeConnection $connection)
    {
        return $this->destinationForConnections->contains($connection);
    }

    public function getAccessibility()
    {
        return $this->getNode()->getFlowTest()->getAccessibility();
    }

    public function hasAnyFromGroup($other_groups)
    {
        $groups = $this->getNode()->getFlowTest()->getGroupsArray();
        foreach ($groups as $group) {
            foreach ($other_groups as $other_group) {
                if ($other_group == $group) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getLockBy()
    {
        return $this->getNode()->getLockBy();
    }

    public function getTopEntity()
    {
        return $this->getNode()->getTopEntity();
    }

    public function getEntityHash()
    {
        $json = json_encode(array(
            "value" => $this->getValue(),
            "string" => $this->isString(),
            "defaultValue" => $this->hasDefaultValue(),
            "dynamic" => $this->isDynamic(),
            "type" => $this->getType(),
            "exposed" => $this->isExposed(),
            "name" => $this->getName(),
            "pointer" => $this->isPointer(),
            "pointerVariable" => $this->getPointerVariable()
        ));
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array(), &$normalizedIdsMap = null)
    {
        if ($this->variable) {
            $wizard = $this->variable->getTest()->getSourceWizard();
            if ($wizard) {
                foreach ($wizard->getParams() as $param) {
                    if ($this->variable->getParentVariable() == null)
                        continue;
                    if ($param->getVariable()->getId() == $this->variable->getParentVariable()->getId()) {
                        TestWizardParam::getParamValueDependencies($this->value, $param->getDefinition(), $param->getType(), $dependencies);
                        break;
                    }
                }
            }
        }

        $serialized = array(
            "class_name" => "TestNodePort",
            "id" => $this->id,
            "value" => $this->value,
            "node" => $this->node->getId(),
            "variable" => $this->variable ? $this->variable->getId() : null,
            "string" => $this->string ? "1" : "0",
            "defaultValue" => $this->defaultValue ? "1" : "0",
            "dynamic" => $this->dynamic ? "1" : "0",
            "type" => $this->type,
            "exposed" => $this->exposed ? "1" : "0",
            "name" => $this->getName(),
            "pointer" => $this->pointer ? "1" : "0",
            "pointerVariable" => $this->pointerVariable
        );

        if ($normalizedIdsMap !== null) {
            $serialized["id"] = self::normalizeId("TestNodePort", $serialized["id"], $normalizedIdsMap);
            $serialized["node"] = self::normalizeId("TestNode", $serialized["node"], $normalizedIdsMap);
            $serialized["variable"] = self::normalizeId("TestVariable", $serialized["variable"], $normalizedIdsMap);
        }

        return $serialized;
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
        $this->getNode()->removePort($this);
        if ($this->getVariable()) $this->getVariable()->removePort($this);
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        if (!$this->getNode()->hasPort($this)) $this->getNode()->addPort($this);
        if ($this->getVariable() && !$this->getVariable()->hasPort($this)) $this->getVariable()->addPort($this);
    }
}
