<?php

namespace AlexMasterov\OAuth2\Client\Provider;

use AlexMasterov\OAuth2\Client\Provider\Exception\SuperJobException;
use AlexMasterov\OAuth2\Client\Provider\SuperJobResourceOwner;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class SuperJob extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string
     */
    protected $urlApi = 'https://api.superjob.ru/2.0/';

    /**
     * @var string
     */
    protected $urlAuthorize = 'https://www.superjob.ru/authorize/';

    /**
     * @var string
     */
    protected $urlAccessToken = 'https://api.superjob.ru/2.0/oauth2/access_token/';

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $redirectUri;

    /**
     * @inheritDoc
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->urlAuthorize;
    }

    /**
     * @inheritDoc
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        if (empty($params['code'])) {
            $params['code'] = '';
        }

        if (empty($params['redirect_uri'])) {
            $params['redirect_uri'] = $this->redirectUri;
        }

        return $this->urlAccessToken.'?'.
            $this->buildQueryString($params);
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->urlApi.'user/current/?'.
            $this->buildQueryString([
                'access_token' => (string) $token,
            ]);
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getAuthorizationParameters(array $options)
    {
        $options['response_type'] = 'code';
        $options['client_id'] = $this->clientId;

        if (empty($options['state'])) {
            $options['state'] = $this->state;
        }

        if (empty($options['redirect_uri'])) {
            $options['redirect_uri'] = $this->redirectUri;
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            throw SuperJobException::errorResponse($response, $data);
        }
    }

    /**
     * @inheritDoc
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new SuperJobResourceOwner($response);
    }
}
