<?php

/**
 * @author Tom Raney <traney@janrain.com>
 */

require '../src/ClientCredentials.php';
require '../src/BackplaneClientMock.php';

class ClientTest extends PHPUnit_Framework_TestCase
{
    public function testClientCredentials()
    {
        $credentials = new ClientCredentials();
        $credentials->setBackplaneServerUrl("foo.com");
        $this->assertEquals($credentials->getBackplaneServerUrl(), "foo.com");
        $credentials->setClientId("clientId");
        $this->assertEquals($credentials->getClientId(), "clientId");
        $credentials->setClientSecret("secret");
        $this->assertEquals($credentials->getClientSecret(), "secret");
        $credentials->setRedirectUri("redirecturi.com");
        $this->assertEquals($credentials->getRedirectUri(), "redirecturi.com");
    }

    public function testRegularToken()
    {
        // for the test, it's not necessary to populate the ClientCredentials object
        $client = new BackplaneClientMock(new ClientCredentials());
        $mockResponse = $this->getMockAccessTokenResponse();
        $response = "f(" . json_encode($mockResponse) . ");";

        $client->setResponse(array("result" => $response, "httpStatus" => 200));
        $setArray = $client->getResponse();
        $this->assertEquals($setArray['result'], $response);

        $regularToken = $client->getRegularAccessToken(self::$bus);

        $this->assertEquals($regularToken->getAccessToken(), $mockResponse->access_token);
        $this->assertEquals($regularToken->getRefreshToken(), $mockResponse->refresh_token);
        $this->assertEquals($regularToken->getScope(), $mockResponse->scope);
        $this->assertEquals($regularToken->getTokenType(), $mockResponse->token_type);
        $this->assertEquals($client->retrieveChannelFromScope($regularToken->getScope()), self::$channel);
        $this->assertEquals($regularToken->getExpiresIn(), $mockResponse->expires_in);
    }

    public function testSingleMessage()
    {
        $client = $this->testAccessToken();
        $singleMessage = $this->getMockMessageResponse();
        $client->setResponse(array("result" => json_encode($singleMessage), "httpStatus" => 200));
        try {
            $processedMessage = $client->getSingleMessage("fakeMessageUrl");
            $this->assertEquals($processedMessage->payload, $singleMessage->payload);
        } catch (Exception $e) {
            $this->fail('should not receive Exception here => ' . $e->getMessage());
        }

        // get single message and simulate failure
        $response = json_encode($this->getMockExpiredTokenResponse());
        $client->setResponse(array("result" => $response, "httpStatus" => 403));
        try {
            $processedMessage = $client->getSingleMessage("fakeMessageUrl");
            $this->fail('Expected ExpiredTokenException');
        } catch (ExpiredTokenException $e) {
            // should get here
        }

        // get single message and simulate failure
        $client->setResponse(array("result" => "random server error", "httpStatus" => 500));
        try {
            $processedMessage = $client->getSingleMessage("fakeMessageUrl");
            $this->fail('Expected Exception');
        } catch (Exception $e) {
            // should get here
        }
    }

    public function testMultipleMessages()
    {
        // get multiple messages
        $response = $this->getMockMessagesResponseJSON();
        $client = $this->testAccessToken();
        $client->setResponse(array("result" => $response, "httpStatus" => 200));
        try {
            $processedMessages = $client->getMessages(null, 10);
            $this->assertEquals(sizeof($processedMessages->messages), 2);

            // use that response to get the next frame of messages
            $processedMessages = $client->getMessages($processedMessages, 10);

        } catch (Exception $e) {
            $this->fail('should not receive Exception here => ' . $e->getMessage());
        }

        // simulate error
        $response = json_encode($this->getMockExpiredTokenResponse());
        $client->setResponse(array("result" => $response, "httpStatus" => 403));
        try {
            $processedMessages = $client->getMessages(null, 10);
            $this->fail('ExpiredTokenException expected');
        } catch (ExpiredTokenException $e) {
            // should get here
        } catch (Exception $e) {
            $this->fail('Expected ExpiredTokenException');
        }
    }

    public function testPostMessage()
    {
        $client = $this->testAccessToken();
        $client->setResponse(array("result" => "", "httpStatus" => 201));
        $singleMessage = $this->getMockMessageResponse();
        try {
            $response = $client->postMessage($singleMessage);
        } catch (Exception $e) {
            $this->fail('should not receive Exception here');
        }
    }

    public function testPostMessageInvalidToken()
    {
        $client = $this->testAccessToken();
        $response = json_encode($this->getMockInvalidTokenResponse());

        $client->setResponse(array("result" => $response, "httpStatus" => 403));
        $singleMessage = $this->getMockMessageResponse();
        try {
            $response = $client->postMessage($singleMessage);
            $this->fail('expected InvalidTokenException');
        } catch (InvalidTokenException $e) {
            // should get here
        }
    }

    public function testPostMessageExpiredToken()
    {
        $client = $this->testAccessToken();
        $response = json_encode($this->getMockExpiredTokenResponse());
        $client->setResponse(array("result" => $response, "httpStatus" => 403));
        $singleMessage = $this->getMockMessageResponse();
        try {
            $response = $client->postMessage($singleMessage);
            $this->fail('expected ExpiredTokenException');
        } catch (ExpiredTokenException $e) {
            // should get here
        }
    }

    public function testAccessToken()
    {
        $client = new BackplaneClientMock(new ClientCredentials());

        // simulate failure
        $client->setResponse(array("result" => "something wrong", "httpStatus" => 500));
        try {
            $client->initializeAccessToken(null, "client_credentials");
            $this->fail('Expected Exception');
        } catch (Exception $e) {
            // should get here
        }

        $mockResponse = $this->getMockAccessTokenResponse();
        $response = json_encode($mockResponse);
        $client->setResponse(array("result" => $response, "httpStatus" => 200));
        $privilegedToken = $client->initializeAccessToken(null, 'client_credentials');

        $this->assertEquals($privilegedToken->getAccessToken(), $mockResponse->access_token);
        $this->assertEquals($client->getAccessToken(), $privilegedToken);

        $privilegedToken = $client->initializeAccessToken(null, 'refresh_token');
        $client->setAccessToken($privilegedToken);
        return $client;
    }

    private static $bus = "foo.com";
    private static $channel = "channel_foo";

    private function getMockInvalidTokenResponse()
    {
        return (object)array(
            "error" => "invalid_request",
            "error_description" => "invalid token"
        );
    }

    private function getMockExpiredTokenResponse()
    {
        return (object)array(
            "error" => "invalid_request",
            "error_description" => "expired token"
        );
    }

    private function getMockAccessTokenResponse()
    {
        return (object)array(
            "token_type" => "Bearer",
            "access_token" => "AA123456789",
            "expires_in" => 604799,
            "scope" => "bus:" . self::$bus . " channel:" . self::$channel,
            "refresh_token" => "AR123456789"
        );
    }

    private function getMockMessageResponse()
    {
        return $message['message'] = (object)array(
            "channel" => "channel",
            "bus" => "bus",
            "payload" => "bar",
            "sticky" => false,
            "type" => "test"
        );
    }

    private function getMockMessagesResponseJSON()
    {
        return '
        {
            "nextURL":"https://backplane.com/v2/messages?since=2013-01-06T19:55:40.524Z-z2lbJUnkZt",
            "messages":[
                {
                "messageURL":"https://backplane.com/v2/message/2013-01-06T18:57:04.630Z-B5bguKLF2t",
                "source":"http://foo.com",
                "type":"test",
                "bus":"foo",
                "channel":"WHajRMv8N98ucjEJ6qffwrnL6Hzz2epJ",
                "sticky":"false",
                "expire":"2013-01-07T02:57:04Z",
                "payload":"bar"
                },
                {
                "messageURL":"https://backplane.com/v2/message/2013-01-06T19:07:31.385Z-zOnv6H6A10",
                "source":"http://foo.com",
                "type":"test",
                "bus":"foo",
                "channel":"pbY1P5F7IrLmcDLKkCiqlqmqPaO4PnjB",
                "sticky":"false",
                "expire":"2013-01-07T03:07:31Z",
                "payload":"bar"
                }
            ],
            "moreMessages":false
        }';
    }


}

?>