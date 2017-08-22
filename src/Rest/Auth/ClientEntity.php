<?php


namespace Gems\Rest\Auth;

use Gems\Rest\Model\EntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class ClientEntity implements ClientEntityInterface, EntityInterface
{
    /**
     * @var boolean Client is active
     */
    protected $active;

    /**
     * @var integer Client identifier
     */
    protected $id;

    /**
     * @var string Client name
     */
    protected $name;

    /**
     * @var string Redirect Uri's
     */
    protected $redirect;

    /**
     * @var string Client secret
     */
    protected $secret;

    /**
     * @var User Id
     */
    protected $userId;



    /**
     * Get if client is active
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Get the client's identifier.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the client's identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->userId;
    }

    /**
     * Get the client's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the registered redirect URI (as a string).
     *
     * Alternatively return an indexed array of redirect URIs.
     *
     * @return string|string[]
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Returns the registered redirect URI (as a string).
     *
     * Alternatively return an indexed array of redirect URIs.
     *
     * @return string|string[]
     */
    public function getRedirectUri()
    {
        return $this->redirect;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set if Client is active
     *
     * @param $active bool
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Set the name of the client
     *
     * @param $name string Name of the client
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the redirect uri's
     *
     * @param $uri string|array List of redirect URI's
     */
    public function setRedirect($uri)
    {
        $this->redirect = $uri;
    }

    /**
     * @param $userId string Userid /username
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
    }
}