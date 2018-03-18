<?php

namespace Krombox\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class WordPress extends AbstractProvider
{
	use BearerAuthorizationTrait;

	/**
	 * @var string
	 */
	private $urlAuthorize;

	/**
	 * @var string
	 */
	private $urlAccessToken;

	/**
	 * @var string
	 */
	private $urlResourceOwnerDetails;

	/**
	 * Domain
	 *
	 * @var string
	 */
	private $domain;

	public function __construct(array $options = [], array $collaborators = [])
	{
		$this->assertRequiredOptions($options);

		$possible   = $this->getConfigurableOptions();
		$configured = array_intersect_key($options, array_flip($possible));

		foreach ($configured as $key => $value) {
			$this->$key = $value;
		}

		// Remove all options that are only used locally
		$options = array_diff_key($options, $configured);

		parent::__construct($options, $collaborators);
	}

	/**
	 * Returns all options that can be configured.
	 *
	 * @return array
	 */
	protected function getConfigurableOptions()
	{
		return array_merge($this->getRequiredOptions(), [
			'urlAuthorize',
			'urlAccessToken',
			'urlResourceOwnerDetails',
		]);
	}

	/**
	 * Returns all options that are required.
	 *
	 * @return array
	 */
	protected function getRequiredOptions()
	{
		return [
			'domain'            
		];
	}

	/**
	 * Verifies that all required options have been passed.
	 *
	 * @param  array $options
	 * @return void
	 * @throws Exception
	 */
	protected function assertRequiredOptions(array $options)
	{
		$missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);

		if (!empty($missing)) {
			throw new \Exception(
				'Required options not defined: ' . implode(', ', array_keys($missing))
			);
		}
	}

	/**
	 * Get authorization url to begin OAuth flow
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl()
	{    
		return $this->urlAuthorize ? $this->urlAuthorize : $this->domain . '/oauth/authorize';        
	}

	/**
	 * Get access token url to retrieve token
	 *
	 * @param  array $params
	 *
	 * @return string
	 */
	public function getBaseAccessTokenUrl(array $params)
	{
		return $this->urlAccessToken ? $this->urlAccessToken : $this->domain . '/oauth/token';        
	}

	/**
	 * Get provider url to fetch user details
	 *
	 * @param  AccessToken $token
	 *
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl(AccessToken $token)
	{
		return $this->urlResourceOwnerDetails ? $this->urlResourceOwnerDetails : $this->domain . '/wp-json/wp/v2/users/me?context=edit';	
	}

	/**
	 * Get the default scopes used by this provider.
	 *
	 * This should not be a complete list of all scopes, but the minimum
	 * required for the provider user interface!
	 *
	 * @return array
	 */
	protected function getDefaultScopes()
	{
		return [];
	}

	/**
	 * Check a provider response for errors.
	 *     
	 * @throws IdentityProviderException
	 * @param  ResponseInterface $response
	 * @param  string $data Parsed response data
	 * @return void
	 */
	protected function checkResponse(ResponseInterface $response, $data)
	{
		if (!empty($data['error'])) {
			$message = $data['error'].': '.$data['error_description'];            
			throw new IdentityProviderException($message, $response->getStatusCode(), $data);
		}    	
	}

	/**
	 * Generate a user object from a successful user details request.
	 *
	 * @param array $response
	 * @param AccessToken $token
	 * @return League\OAuth2\Client\Provider\ResourceOwnerInterface
	 */
	protected function createResourceOwner(array $response, AccessToken $token)
	{
		return new WordPressResourceOwner($response);        
	}
}