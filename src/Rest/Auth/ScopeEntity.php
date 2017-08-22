<?php

namespace Gems\Rest\Auth;

use Gems\Rest\Model\EntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class ScopeEntity implements ScopeEntityInterface, EntityInterface
{
    /**
     * @var int Scope unique ID
     */
    protected $id;

    /**
     * @var string Scope name / identification
     */
    protected $name;

    /**
     * @var string Scope description
     */
    protected $description;

    /**
     * Get the scope description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the scope unique identifier.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the scope's identifier name
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->name;
    }

    /**
     * @return string get the scope's name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->getIdentifier();
    }

    /**
     * @param $description string Scope description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param $name string Scope identifier name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}