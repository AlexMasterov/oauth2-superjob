<?php

namespace AlexMasterov\OAuth2\Client\Provider;

use League\OAuth2\Client\{
    Provider\ResourceOwnerInterface,
    Tool\ArrayAccessorTrait
};

class SuperJobResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->getValueByKey($this->response, 'phone_number');
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getValueByKey($this->response, 'id');
    }

    /**
     * @return string
     */
    public function getEMail()
    {
        return $this->getValueByKey($this->response, 'email');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getValueByKey($this->response, 'name');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }

    /**
     * @var array
     */
    protected $response = [];
}
