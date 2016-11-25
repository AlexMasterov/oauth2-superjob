<?php

namespace AlexMasterov\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SuperJobResourceOwner implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response = [];

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->response['phone_number'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->response['id'];
    }

    /**
     * @return string
     */
    public function getEMail()
    {
        return $this->response['email'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->response['name'];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
