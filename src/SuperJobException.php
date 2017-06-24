<?php
declare(strict_types=1);

namespace AlexMasterov\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class SuperJobException extends IdentityProviderException
{
    public static function errorResponse(ResponseInterface $response, $data): SuperJobException
    {
        $error = $data['error'];

        $message = $error['error'];
        if (!empty($error['message'])) {
            $message .= ': ' . $error['message'];
        }

        $code = $error['code'] ?? $response->getStatusCode();
        $body = (string) $response->getBody();

        return new static($message, $code, $body);
    }
}
