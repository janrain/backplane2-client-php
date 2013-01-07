<?php

/**
 * @author Tom Raney <traney@janrain.com>
 */

require_once "BackplaneClientBase.php";

class BackplaneClient extends BackplaneClientBase
{

    private static $CURL_ARRAY = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'backplane-php-client-0.9.0',
        CURLOPT_SSL_VERIFYHOST => true,
        CURLOPT_HEADER => false
    );


    protected function doPost($url, $postFields, $header, $basicAuth)
    {

        $ch = curl_init($url);
        $opts = self::$CURL_ARRAY;

        $opts[CURLOPT_POST] = true;

        if ($postFields != null) {
            $opts[CURLOPT_POSTFIELDS] = $postFields;
        }
        if ($header != null) {
            $opts[CURLOPT_HTTPHEADER] = $header;
        }

        if ($basicAuth) {
            $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $opts[CURLOPT_USERPWD] = $basicAuth;
        }

        curl_setopt_array($ch, $opts);

        $result = curl_exec($ch);

        $response = array("result" => $result, "httpStatus" => curl_getinfo($ch, CURLINFO_HTTP_CODE));

        curl_close($ch);
        return $response;

    }

    protected function doGet($url, $header)
    {

        $ch = curl_init($url);

        $opts = self::$CURL_ARRAY;

        $opts[CURLOPT_POST] = false;

        if ($header) {
            $opts[CURLOPT_HTTPHEADER] = $header;
        }

        curl_setopt_array($ch, $opts);

        $result = curl_exec($ch);

        $response = array("result" => $result, "httpStatus" => curl_getinfo($ch, CURLINFO_HTTP_CODE));

        curl_close($ch);
        return $response;

    }
}
