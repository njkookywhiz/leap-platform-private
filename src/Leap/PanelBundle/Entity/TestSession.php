<?php

namespace Leap\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\Index;
use DateTime;

/**
 * @ORM\Table(
 *     uniqueConstraints={@UniqueConstraint(name="hash_idx", columns={"hash"})},
 *     indexes={@Index(name="idx", columns={"status","updated"})}
 * )
 * @ORM\Entity(repositoryClass="Leap\PanelBundle\Repository\TestSessionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TestSession
{

    const STATUS_RUNNING = 0;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     *
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="sessions")
     */
    private $test;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $submitterPort;

    /**
     *
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     *
     * @ORM\Column(type="integer")
     */
    private $timeLimit;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $params;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $error;

    /**
     *
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $clientIp;

    /**
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $clientBrowser;

    /**
     *
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $hash;

    /**
     *
     * @ORM\Column(type="boolean")
     */
    private $debug;

    public function __construct()
    {
        $this->created = new DateTime("now");
        $this->updated = new DateTime("now");
        $this->status = self::STATUS_RUNNING;
        $this->timeLimit = 0;
        $this->submitterPort = 0;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set updated
     *
     * @param DateTime $updated
     * @return TestSession
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function getOwner()
    {
        return $this->getTest()->getOwner();
    }

    /**
     * Set submitter port
     *
     * @param integer $submitterPort
     * @return TestSession
     */
    public function setSubmitterPort($submitterPort)
    {
        $this->submitterPort = $submitterPort;

        return $this;
    }

    /**
     * Get submitter port
     *
     * @return integer
     */
    public function getSubmitterPort()
    {
        return $this->submitterPort;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TestSession
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set test
     *
     * @param Test $test
     * @return TestSession
     */
    public function setTest(Test $test = null)
    {
        $this->test = $test;

        return $this;
    }

    /**
     * Get test
     *
     * @return Test
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * Set time limit
     *
     * @param integer $timeLimit
     * @return TestSession
     */
    public function setTimeLimit($timeLimit)
    {
        $this->timeLimit = $timeLimit;

        return $this;
    }

    /**
     * Get time limit (in seconds)
     *
     * @return integer
     */
    public function getTimeLimit()
    {
        return $this->timeLimit;
    }

    /**
     * Set params
     *
     * @param string $params
     * @return TestSession
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set error
     *
     * @param string $error
     * @return TestSession
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set client ip
     *
     * @param string $clientIp
     * @return TestSession
     */
    public function setClientIp($clientIp)
    {
        $this->clientIp = $clientIp;

        return $this;
    }

    /**
     * Get client ip
     *
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * Set client browser
     *
     * @param string $clientBrowser
     * @return TestSession
     */
    public function setClientBrowser($clientBrowser)
    {
        $this->clientBrowser = $clientBrowser;

        return $this;
    }

    /**
     * Get client browser
     *
     * @return string
     */
    public function getClientBrowser()
    {
        return $this->clientBrowser;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return TestSession
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set debug
     *
     * @param boolean $debug
     * @return TestSession
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Get debug
     *
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    public function getAccessibility()
    {
        return $this->getTest()->getAccessibility();
    }

    public function hasAnyFromGroup($other_groups)
    {
        $groups = $this->getTest()->getGroupsArray();
        foreach ($groups as $group) {
            foreach ($other_groups as $other_group) {
                if ($other_group == $group) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @ORM\PreUpdate() */
    public function preUpdate()
    {
        $this->setUpdated(new DateTime("now"));
    }
}
