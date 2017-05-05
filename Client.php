<?php

namespace FilippoToso\ScreenLab;

use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\BadResponseException;

class Client
{

    protected $email = null;
    protected $password = null;
    protected $access_token = null;

    /**
     * Create an instance of the client.
     * @param String $email        ScreenLab email address (optional)
     * @param String $password     ScreenLab password (optional)
     * @param String $access_token Previously generated access token (optional)
     */
    public function __construct($email = null, $password = null, $access_token = null) {
        $this->email = $email;
        $this->password = $password;
        $this->access_token = $access_token;
    }

    /**
     * Execute an HTTP GET request to ScreenLab API
     * @param  String $url The url of the API endpoint
     * @return Array|FALSE  The result of the request
     */
    protected function get($url) {

        $client = new HTTPClient();

        try {
            $res = $client->request('GET', $url, [
                'headers' => [
                    'X-Accesstoken' => $this->access_token,
                ],
            ]);
        }
        catch (BadResponseException $e) {
            return FALSE;
        }

        $data = json_decode($res->getBody(), TRUE);

        return $data;

    }

    /**
     * Execute an HTTP POST request to ScreenLab API
     * @param  String $url The url of the API endpoint
     * @param  Array $data The parameters of the request
     * @return Array|FALSE  The result of the request
     */
    protected function post($url, $data) {

        $client = new HTTPClient();

        try {
            $res = $client->request('POST', $url, [
                'json' => $data,
                'headers' => [
                    'X-Accesstoken' => $this->access_token,
                ],
            ]);
        }
        catch (BadResponseException $e) {
            return FALSE;
        }

        $data = json_decode($res->getBody(), TRUE);

        return $data;

    }

    /**
     * Execute a request to the API using a callback
     * In case of error, try again updating the access token
     * @param  callable $callback   The callback that executes the request
     * @return Array|FALSE  The result of the request
     */
    protected function retry(callable $callback) {

        if (is_null($this->access_token)) {
            if (is_null($this->email) || is_null($this->password)) {
                return FALSE;
            }
            $this->refreshAccessToken($this->email, $this->password)
        }

        $result = call_user_func($callback);

        if ($result === FALSE) {
            if ($this->refreshAccessToken($this->email, $this->password)) {
                $result = call_user_func($callback);
            }
        }

        return $result;

    }

    /**
     * Get the access token from the API
     * @param  String $email    The user's email
     * @param  String $password The user's password
     * @return Array           The user's access token
     */
    public static function getAccessToken($email, $password) {

        if (is_null($email) || is_null($password)) {
            return FALSE;
        }

        $client = new HTTPClient();

        try {
            $res = $client->request('POST', 'https://screenlab.io/api/auth/session/', [
                'json' => ['email' => $email, 'password' => $password],
            ]);
        }
        catch (BadResponseException $e) {
            return FALSE;
        }

        $data = json_decode($res->getBody(), TRUE);

        return $data;

    }

    /**
     * Refreshes the access token for the provided user
     * @param  String $email    The user's email (optional)
     * @param  String $password The user's password (optional)
     * @return String           The user's access token
     */
    public function refreshAccessToken($email = null, $password = null) {

        $email = is_null($email) ? $this->email : $email;
        $email = is_null($password) ? $this->password : $password;

        $data = static::getAccessToken($email, $password);

        if (!isset($data['session']['accesstoken'])) {
            return FALSE;
        }

        $this->email = $email;
        $this->password = $password;
        $this->access_token = $data['session']['accesstoken'];

        return $this->access_token;

    }

    /**
     * Generate a scan of the provided URL
     * @param  String  $url    The URL to be analyzed
     * @param  String  $name   The name of the screenshot (default sha1($url))
     * @param  integer $width  The width of the screenshot (default 1366 pixels)
     * @param  integer $height The height of the screenshot (default 768 pixels)
     * @return Array|FALSE  The result of the request
     */
    public function generateScan($url, $name = null, $width = 1366, $height = 768) {

        $data = [
            'testUrl' => $url,
            'name' => is_null($name) ? sha1($url) : $name,
            'width' => $width,
            'height' => $height,
        ];

        return $this->retry(function() use($data) {
            return $this->post('https://screenlab.io/api/scan', $data);
        });

    }

    /**
     * Get a single scan by ID
     * @param  integer $id The ID of the scan
     * @return Array|FALSE     The result of the request
     */
    public function getScan($id) {

        return $this->retry(function() use($id) {
            return $this->get('https://screenlab.io/api/scan/' . $id);
        });

    }

    /**
     * Get the list of all scans
     * @return Array|FALSE     The result of the request
     */
    public function getScans() {

        return $this->retry(function() {
            return $this->get('https://screenlab.io/api/scans');
        });

    }

}
