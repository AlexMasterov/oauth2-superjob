<?php

namespace AlexMasterov\OAuth2\Client\Tests\Provider;

use AlexMasterov\OAuth2\Client\Provider\Exception\SuperJobException;
use AlexMasterov\OAuth2\Client\Provider\SuperJob;
use Eloquent\Phony\Phpunit\Phony;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class SuperJobTest extends TestCase
{
    /**
     * @var SuperJob
     */
    private $provider;

    protected function setUp()
    {
        $this->provider = new SuperJob([
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'mock_redirect_uri',
        ]);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    protected function mockResponse($body)
    {
        $response = Phony::mock(ResponseInterface::class);
        $response->getHeader->with('content-type')->returns('application/json');
        $response->getBody->returns(json_encode($body));

        return $response;
    }

    protected function mockClient(ResponseInterface $response)
    {
        $client = Phony::mock(ClientInterface::class);
        $client->send->returns($response);

        return $client;
    }

    public function testAuthorizationUrl()
    {
        // Run
        $url = $this->provider->getAuthorizationUrl();
        $path = \parse_url($url, PHP_URL_PATH);

        // Verify
        $this->assertSame('/authorize/', $path);
    }

    public function testBaseAccessTokenUrl()
    {
        $params = [];

        // Run
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $path = \parse_url($url, PHP_URL_PATH);

        // Verify
        $this->assertSame('/2.0/oauth2/access_token/', $path);
    }

    public function testDefaultScopes()
    {
        $reflection = new \ReflectionClass(get_class($this->provider));
        $getDefaultScopesMethod = $reflection->getMethod('getDefaultScopes');
        $getDefaultScopesMethod->setAccessible(true);

        // Run
        $scope = $getDefaultScopesMethod->invoke($this->provider);

        // Verify
        $this->assertEquals([], $scope);
    }

    public function testGetAccessToken()
    {
        $body = [
            'access_token'  => 'mock_access_token',
            'refresh_token' => 'mock_refresh_token',
            'ttl'           => 1394748311,
            'expires_in'    => \time() * 3600,
            'token_type'    => 'bearer',
        ];

        $response = $this->mockResponse($body);
        $client = $this->mockClient($response->get());

        // Run
        $this->provider->setHttpClient($client->get());
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        // Verify
        $this->assertNull($token->getResourceOwnerId());
        $this->assertEquals($body['access_token'], $token->getToken());
        $this->assertEquals($body['refresh_token'], $token->getRefreshToken());
        $this->assertGreaterThanOrEqual($body['expires_in'], $token->getExpires());
    }

    public function testUserProperty()
    {
        $body = [
            'id'           => 123,
            'name'         => 'name',
            'phone_number' => '+77777777777',
            'email'        => 'email',
            'date_reg'     => 1152323382,
        ];

        $tokenOptions = [
            'access_token' => 'mock_access_token',
            'expires_in'   => 3600,
        ];

        $token = new AccessToken($tokenOptions);
        $response = $this->mockResponse($body);
        $client = $this->mockClient($response->get());

        // Run
        $this->provider->setHttpClient($client->get());
        $user = $this->provider->getResourceOwner($token);

        // Verify
        $this->assertEquals($body['id'], $user->getId());
        $this->assertEquals($body['name'], $user->getName());
        $this->assertEquals($body['phone_number'], $user->getPhoneNumber());
        $this->assertEquals($body['email'], $user->getEmail());
        $this->assertArrayHasKey('date_reg', $user->toArray());
    }

    public function testErrorResponses()
    {
        $code = 401;
        $body = [
            'error' => [
                'code'    => $code,
                'error'   => 'Foo error',
                'message' => 'Error message',
            ],
        ];

        $response = $this->mockResponse($body);
        $response->getStatusCode->returns($code);
        $client = $this->mockClient($response->get());

        $this->expectException(SuperJobException::class);
        $this->expectExceptionCode($code);

        unset($body['error']['code']);
        $this->expectExceptionMessage(implode(': ', $body['error']));

        // Run
        $this->provider->setHttpClient($client->get());
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
