<?php

/**
 * @author Tom Raney <traney@janrain.com>
 */

require_once "BackplaneClientBase.php";
 
class BackplaneClientMock extends BackplaneClientBase {

    private $response;

    protected function doPost($url, $postFields, $header, $basicAuth)
    {
        return $this->response;
    }

    protected function doGet($url, $header)
    {
        return $this->response;
    }

    public function setResponse($response) {
        $this->response = $response;
    }

    public function getResponse() {
        return $this->response;
    }

}
