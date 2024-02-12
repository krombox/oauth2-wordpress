<?php

namespace Krombox\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class WordPressResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getId()
    {
        //vardump($this->response);die();
        return $this->getValueByKey($this->response, 'id');
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getValueByKey($this->response, 'email');
    }

    /**
     * Get resource owner username
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getValueByKey($this->response, 'username');
    }

    /**
     * Get resource owner link
     *
     * @return string|null
     */
    public function getLink()
    {
        return $this->getValueByKey($this->response, 'link');
    }

    /**
     * Get resource owner avatar url
     *
     * @return string|null
     */
    public function getAvatarUrl($size = 96)
    {
        $avatarUrls = $this->getValueByKey($this->response, 'avatar_urls');
        return in_array($size, array('24','48','96')) ? $avatarUrls[$size] : null;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
