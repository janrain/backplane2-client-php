<?php

/**
 * @author Tom Raney <traney@janrain.com>
 */

class AccessToken
{
    private $access_token;
    private $refresh_token;
    private $token_type;
    private $expires_in; //seconds from the time it was issued
    private $scope;

    function __construct($access_token, $refresh_token, $token_type, $expires_in, $scope)
    {
        $this->access_token = $access_token;
        $this->refresh_token = $refresh_token;
        $this->token_type = $token_type;
        $this->expires_in = $expires_in;
        $this->scope = $scope;
    }

    public function getAccessToken()
    {
        return $this->access_token;
    }

    public function getExpiresIn()
    {
        return $this->expires_in;
    }

    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getTokenType()
    {
        return $this->token_type;
    }


}

?>
