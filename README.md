Backplane 2 PHP Client (v0.9.0)
=======================

This library integrates server side Backplane clients with the Backplane server protocol [https://github.com/janrain/janrain-backplane-2].

Usage
-----

The SDK should be initialized like this:

    require '../src/ClientCredentials.php';
    require '../src/BackplaneClient.php';

    $credentials = new ClientCredentials();
    $credentials->setBackplaneServerUrl('https://backplane.com');
    $credentials->setClientId('client-id-goes-here');
    $credentials->setClientSecret('client-secret-goes-here');

    $client = new BackplaneClient($credentials);

To initialize the library and retrieve an access token from the Backplane server:

    try {
        $accessToken = $client->initializeAccessToken(null, 'client_credentials');
    } catch (Exception $e) {
        // handle exception
    }

Because the access token will expire, your code will need to be able to recover from this event.

    try {
        $message = $client->getSingleMessage($messageURL);
    } catch (ExpiredTokenException $e) {
        // recover by requesting a new token
    }

Because this SDK is intended to be flexible, you will need to implement your own method of
storing the dynamic access token in a file or in a database.  It is not good practice (nor efficient) to
request a new token each time an interaction is desired with the Backplane server.

Tests
-----

To run phpunit tests, from the tests directory, use the following:

    phpunit ClientTest.php

If you have the xdebug extension installed, you may produce a code coverage report:

    phpunit --coverage-html ./report ClientTest.php





