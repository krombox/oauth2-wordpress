<?php 

namespace Krombox\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Tool\QueryBuilderTrait;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mockery as m;

class WordpressTest extends \PHPUnit_Framework_TestCase
{
    use QueryBuilderTrait;

    protected $domain = 'https://example.com';

    protected $provider;

    protected function setUp()
    {
        $this->provider = new \Krombox\OAuth2\Client\Provider\Wordpress([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'domain' => $this->domain
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }    

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testRequiredOptions()
    {        
        try {
            new \Krombox\OAuth2\Client\Provider\Wordpress([
                'clientId' => 'mock_client_id',
                'clientSecret' => 'mock_secret',
                'redirectUri' => 'none'                
            ]);
        } catch (\Exception $e) {
            $this->assertEquals('Required options not defined: domain', $e->getMessage());
        }
    }    

    public function testUserData()
    {
        $userId = rand(1000,9999);        
        $username = uniqid();
        $email = uniqid();
        $avatarUrlDefault = 'http://1.gravatar.com/avatar/7fa9a0d816188e53f95fcb9e8aeb63iw';
        $avatarUrl24 = $avatarUrlDefault.'?s=24';
        $avatarUrl48 = $avatarUrlDefault.'?s=48';
        $avatarUrl96 = $avatarUrlDefault.'?s=96';

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token","expires_in":3600,"token_type":"bearer","refresh_token":"mock_refresh_token","scope":"basic"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"id": '.$userId.', "username": "'.$username.'", "email": "'.$email.'", "avatar_urls":{"24":"'. $avatarUrl24 .'","48":"'. $avatarUrl48 .'","96":"'. $avatarUrl96 .'"}}');    

        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($userId, $user->getId());        
        $this->assertEquals($username, $user->getUsername());        
        $this->assertEquals($email, $user->getEmail());
        $this->assertNotNull($user->getAvatarUrl());
        $this->assertNotNull($user->getAvatarUrl(48));
        $this->assertNull($user->getAvatarUrl(77));    
        $this->assertContains('24', $user->getAvatarUrl(24));    
    }

    /**
     *
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testUserDataFails()
    {
        $status = rand(400,600);
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token","expires_in":3600,"token_type":"bearer","refresh_token":"mock_refresh_token","scope":"basic"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"error": "invalid_request","error_description": "Unknown request"}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);        
        $userResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);
    }    
}