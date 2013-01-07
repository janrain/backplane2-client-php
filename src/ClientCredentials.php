<?php

/**
 * @author Tom Raney <traney@janrain.com>
 */

class ClientCredentials {

    private $backplaneServerUrl;
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function setBackplaneServerUrl($backplaneServerUrl)
    {
        $this->backplaneServerUrl = $backplaneServerUrl;
    }

    public function getBackplaneServerUrl()
    {
        return $this->backplaneServerUrl;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }


}

?>
