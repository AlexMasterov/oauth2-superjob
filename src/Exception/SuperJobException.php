<?php

namespace AlexMasterov\OAuth2\Client\Provider\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class SuperJobException extends IdentityProviderException
{
    /**
     * @param ResponseInterface $response
     * @param string|array $data
     *
     * @return static
     */
    public static function errorResponse(ResponseInterface $response, $data)
    {
        $error = $data['error'];

        $message = $error['error'];
        if (!empty($error['message'])) {
            $message .= ': '.$error['message'];
        }

        $code = $error['code'] ? $error['code'] : $response->getStatusCode();
        $body = (string) $response->getBody();

        return new static($message, $code, $body);
    }
}