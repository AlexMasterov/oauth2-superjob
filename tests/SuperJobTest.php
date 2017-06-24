<?php
declare(strict_types=1);

namespace AlexMasterov\OAuth2\Client\Provider\Tests;

use AlexMasterov\OAuth2\Client\Provider\{
    SuperJob,
    SuperJobException,
    SuperJobResourceOwner,
    Tests\CanAccessTokenStub,
    Tests\CanMockHttp
};
use PHPUnit\Framework\TestCase;

class SuperJobTest extends TestCase
{
    use CanAccessTokenStub;
    use CanMockHttp;

    public function testAuthorizationUrl()
    {
        // Execute
        $url = $this->provider()
            ->getAuthorizationUrl();

        // Verify
        self::assertSame('/authorize/', path($url));
    }

    public function testBaseAccessTokenUrl()
    {
        static $params = [];

        // Execute
        $url = $this->provider()
            ->getBaseAccessTokenUrl($params);

        // Verify
        self::assertSame('/2.0/oauth2/access_token/', path($url));
    }

    public function testResourceOwnerDetailsUrl()
    {
        // Stub
        $apiUrl = $this->apiUrl();
        $tokenParams = [
            'access_token' => 'mock_access_token',
        ];

        $accessToken = $tokenParams['access_token'];

        // Execute
        $detailUrl = $this->provider()
            ->getResourceOwnerDetailsUrl($this->accessToken($tokenParams));

        // Verify
        self::assertSame(
            "{$apiUrl}user/current/?access_token={$accessToken}",
            $detailUrl
        );
    }

    public function testDefaultScopes()
    {
        $getDefaultScopes = function () {
            return $this->getDefaultScopes();
        };

        // Execute
        $defaultScopes = $getDefaultScopes->call($this->provider());

        // Verify
        self::assertSame([], $defaultScopes);
    }

    public function testCheckResponse()
    {
        $getParseResponse = function () use (&$response, &$data) {
            return $this->checkResponse($response, $data);
        };

        // Stub
        $code = 401;
        $data = ['error' => [
                'code'    => $code,
                'error'   => 'Foo error',
                'message' => 'Error message',
            ],
        ];

        // Mock
        $response = $this->mockResponse('', '', $code);

        // Verify
        self::expectException(SuperJobException::class);
        self::expectExceptionCode($code);

        unset($data['error']['code']);
        self::expectExceptionMessage(implode(': ', $data['error']));

        // Execute
        $getParseResponse->call($this->provider());
    }

    public function testCreateResourceOwner()
    {
        $getCreateResourceOwner = function () use (&$response, &$token) {
            return $this->createResourceOwner($response, $token);
        };

        // Stub
        $token = $this->accessToken();
        $response = [
            'id'           => random_int(1, 1000),
            'name'         => 'mock_name',
            'phone_number' => 'mock_phone_number',
            'email'        => 'mock_email',
            'date_reg'     => time(),
        ];

        // Execute
        $resourceOwner = $getCreateResourceOwner->call($this->provider());

        // Verify
        self::assertInstanceOf(SuperJobResourceOwner::class, $resourceOwner);
        self::assertEquals($response['id'], $resourceOwner->getId());
        self::assertEquals($response['name'], $resourceOwner->getName());
        self::assertEquals($response['phone_number'], $resourceOwner->getPhoneNumber());
        self::assertEquals($response['email'], $resourceOwner->getEmail());
        self::assertSame($response, $resourceOwner->toArray());
    }

    private function provider(...$args): SuperJob
    {
        static $default = [
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'mock_redirect_uri',
        ];

        $values = array_replace($default, ...$args);

        return new SuperJob($values);
    }

    private function apiUrl(): string
    {
        $getApiUrl = function () {
            return $this->urlApi;
        };

        return $getApiUrl->call($this->provider());
    }
}

function path(string $url): string
{
    return parse_url($url, PHP_URL_PATH);
}
