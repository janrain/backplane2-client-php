<?php

/**
 * @author Tom Raney <traney@janrain.com>
 */

require_once "ClientCredentials.php";
require_once "AccessToken.php";

if (!function_exists('curl_init')) {
    throw new Exception('Backplane client requires CURL extension');
}

class BackplaneClientException extends Exception
{
}

class InvalidTokenException extends Exception
{
}

class ExpiredTokenException extends Exception
{
}

abstract class BackplaneClientBase
{
    /** @var ClientCredentials $clientCredentials */
    protected $clientCredentials;
    /** @var AccessToken $accessToken */
    protected $accessToken;

    abstract protected function doPost($url, $postFields, $header, $basicAuth);

    abstract protected function doGet($url, $header);


    function __construct($clientCredentials)
    {
        $this->clientCredentials = $clientCredentials;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Post a message
     *
     * @throws BackplaneClientException|ExpiredTokenException|InvalidTokenException
     * @param $message
     */
    public function postMessage($message)
    {
        $url = $this->clientCredentials->getBackplaneServerUrl() . '/v2/message';

        $result = $this->doPost($url,
                                json_encode($message),
                                array(
                                     'Content-type: application/json',
                                     'Authorization: Bearer ' . $this->accessToken->getAccessToken()
                                ), null);

        $http_status = $result['httpStatus'];

        if ($http_status !== 201) {
            return $this->determineException($result['result']);
        }


    }

    /**
     * Retrieve a single message body
     *
     * @throws BackplaneClientException|ExpiredTokenException|InvalidTokenException
     * @param $messageUrl
     * @return message body
     */

    public function getSingleMessage($messageUrl)
    {
        $result = $this->doGet($messageUrl,
                               array(
                                    'Authorization: Bearer ' . $this->accessToken->getAccessToken()
                               ));

        $http_status = $result['httpStatus'];

        if ($http_status !== 200) {
            return $this->determineException($result['result']);
        }

        return json_decode($result['result']);
    }


    /**
     * Call the Backplane server and retrieve all messages in the scope of the access token.
     *
     * The Backplane server will only return a maximum of N messages per call - N being defined
     * by the particular server deployment.
     *
     * This method will *not* loop and retrieve all messages that may be available.  It is up to the caller
     * of this function to use the returned messageWrapper object and the 'moreMessages' boolean to determine
     * if additional calls are required to retrieve all messages.
     *
     * @throws BackplaneClientException|ExpiredTokenException|InvalidTokenException
     * @param $messageWrapper (may be null)
     * @param $block (may be null)
     * @return messageWrapper object that contains an array of messages.
     */

    public function getMessages($messageWrapper, $block)
    {

        $url = $this->clientCredentials->getBackplaneServerUrl() . "/v2/messages";

        if ($messageWrapper != null) {
            $url = $messageWrapper->nextURL;
        }

        if ($block != null) {
            if ($messageWrapper != null) {
                $url .= "&block=$block";
            }
            else {
                $url .= "?block=$block";
            }
        }

        $result = $this->doGet($url,
                               array(
                                    'Authorization: Bearer ' . $this->accessToken->getAccessToken()
                               ));

        $http_status = $result['httpStatus'];

        if ($http_status !== 200) {
            return $this->determineException($result['result']);
        }

        return json_decode($result['result']);
    }

    /**
     * Call to initialize the access token for the client or refresh an existing token.
     *
     * A submitted 'scope' value will result in an access token that is scoped precisely for
     * the particular use desired (e.g. 'bus:foo.com').  If the scope value is set to null,
     * the resulting access token will have a scope for all grants issued for this client,
     * aka the 'super token'.
     *
     * @throws BackplaneClientException
     * @param $scope - may be null
     * @param $grantType - must be either 'client_credentials' or 'refresh_token'
     * @return AccessToken
     */

    public function initializeAccessToken($scope, $grantType)
    {
        $url = $this->clientCredentials->getBackplaneServerUrl() . '/v2/token';
        $parameters = "grant_type=" . urlencode($grantType) . ($scope != null ? "&scope=" . urlencode($scope) : "");

        if ($this->accessToken != null && $grantType === "refresh_token") {
            $parameters .= "&refresh_token=" . $this->accessToken->getRefreshToken();
        }

        $result = $this->doPost($url,
                               $parameters,
                               null,
                               $this->clientCredentials->getClientId() .
                                        ':' . $this->clientCredentials->getClientSecret()
        );

        if ($result['httpStatus'] !== 200) {
            throw new BackplaneClientException("An error has occurred => '" . $result . "'");
        }

        $response = json_decode($result["result"]);
        $this->accessToken = new AccessToken($response->access_token,
                                             $response->refresh_token,
                                             $response->token_type,
                                             $response->expires_in,
                                             $response->scope);

        return $this->accessToken;
    }

    /**
     * getRegularAccessToken is a function provided for testing purposes.
     *
     * The "regular access token"
     * [see http://backplanex.com/specification-documents/backplane-protocol-2-0-draft-12/#access.level.regular]
     * is a token issued to a 'public' client - which is a client without credentials.  This token request always
     * requires a valid bus and will always return a new channel name in the response.
     *
     * @param $bus
     * @return AccessToken
     */

    public function getRegularAccessToken($bus)
    {
        $url = $this->clientCredentials->getBackplaneServerUrl() . '/v2/token?callback=f&bus=' . $bus;
        $result = $this->doGet($url, null);

        // strip out callback wrapper
        $json = substr($result['result'], 2, -2);
        $obj = json_decode($json);

        return new AccessToken($obj->access_token,
                               $obj->refresh_token,
                               $obj->token_type,
                               $obj->expires_in,
                               $obj->scope);
    }

    /**
     * Function to assist with parsing out the channel string from the scope string, which
     * would normally only come up while dealing with the scope value returned after requesting
     * a regular access token.
     *
     * @param $scope
     * @return string
     */

    public function retrieveChannelFromScope($scope)
    {
        $index = strstr($scope, "channel");
        return substr($index, 8);
    }

    /**
     * Determine the appropriate Exception for the given input.
     *
     * @throws BackplaneClientException|ExpiredTokenException|InvalidTokenException
     * @param $result
     * @return void
     */

    private function determineException($result)
    {
        if (strstr($result, "expired token")) {
            throw new ExpiredTokenException("The access token has expired");
        } else if (strstr($result, "invalid token")) {
            throw new InvalidTokenException("The access token is invalid => " . $result);
        } else {
            throw new BackplaneClientException("An error has occurred => '" . $result . "'");
        }
    }
}

?>


