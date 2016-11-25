<?php

namespace AlexMasterov\OAuth2\Client\Tests\Provider;

use AlexMasterov\OAuth2\Client\Provider\Exception\SuperJobException;
use AlexMasterov\OAuth2\Client\Provider\SuperJob;
use Eloquent\Phony\Phpunit\Phony;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;

class SuperJobTest extends TestCase
{
    /**
     * @var SuperJob
     */
    protected $provider;

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

    public function testAuthorizationUrl()
    {
        // Run
        $url = $this->provider->getAuthorizationUrl();
        $path = parse_url($url, PHP_URL_PATH);

        // Verify
        $this->assertSame('/authorize/', $path);
    }

    public function testBaseAccessTokenUrl()
    {
        $params = [];

        // Run
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $path = parse_url($url, PHP_URL_PATH);

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
        $rawResponse = [
            'access_token'  => 'mock_access_token',
            'refresh_token' => 'mock_refresh_token',
            'ttl'           => 1394748311,
            'expires_in'    => time() * 3600,
            'token_type'    => 'bearer',
        ];

        $response = Phony::mock(ResponseInterface::class);
        $response->getHeader->with('content-type')->returns('application/json');
        $response->getBody->returns(json_encode($rawResponse));

        $client = Phony::mock(ClientInterface::class);
        $client->send->returns($response->get());

        // Run
        $this->provider->setHttpClient($client->get());
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);

        // Verify
        $this->assertEquals($rawResponse['access_token'], $token->getToken());
        $this->assertEquals($rawResponse['refresh_token'], $token->getRefreshToken());
        $this->assertGreaterThanOrEqual($rawResponse['expires_in'], $token->getExpires());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserProperty()
    {
        $rawProperty = [
            'id'           => 123,
            'name'         => 'name',
            'phone_number' => '+77777777777',
            'email'        => 'email',
            'date_reg'     => 1152323382,
        ];

        $response = Phony::mock(ResponseInterface::class);
        $response->getHeader->with('content-type')->returns('application/json');
        $response->getBody->returns(json_encode($rawProperty));

        $client = Phony::mock(ClientInterface::class);
        $client->send->returns($response->get());

        $token = new AccessToken([
            'access_token' => 'mock_access_token',
            'expires_in' => 3600,
        ]);

        // Run
        $this->provider->setHttpClient($client->get());
        $user = $this->provider->getResourceOwner($token);

        // Verify
        $this->assertEquals($rawProperty['id'], $user->getId());
        $this->assertEquals($rawProperty['name'], $user->getName());
        $this->assertEquals($rawProperty['phone_number'], $user->getPhoneNumber());
        $this->assertEquals($rawProperty['email'], $user->getEmail());

        $this->assertArrayHasKey('date_reg', $user->toArray());
    }

    public function testErrorResponses()
    {
        $error = [
            'code'    => 401,
            'message' => 'Error message',
            'error'   => 'Foo error',
        ];

        $message = $error['error'].': '. $error['message'];

        $response = Phony::mock(ResponseInterface::class);
        $response->getStatusCode->returns(401);
        $response->getHeader->with('content-type')->returns('application/json');
        $response->getBody->returns(json_encode(compact('error')));

        $client = Phony::mock(ClientInterface::class);
        $client->send->returns($response->get());

        // Run
        $this->provider->setHttpClient($client->get());

        $errorMessage = '';
        $errorCode = 0;

        try {
            $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        } catch (SuperJobException $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();
            $errorBody = $e->getResponseBody();
        }

        // Verify
        $this->assertEquals($message, $errorMessage);
        $this->assertEquals(401, $errorCode);
        $this->assertEquals(json_encode(compact('error')), $errorBody);
    }
}
