<?php
/*
EmeraldCryptoCoin-PHP

Class to making calls to Emerald's Crypto Coins JSON RPC API using PHP
https://github.com/wetrust/EmeraldCryptoCoin-PHP

====================

The MIT License (MIT)
Copyright (c) 2017 wetrust EIRL
Copyright (c) 2014 Cristopher Castro González @cristophernic
Copyright (c) 2013 Andrew LeCody

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

====================

// Crear un objeto EMD:
$EMD = new EMD('username','password');

// Puedes sobre escribir el host a usar.
$EMD = new EMD('username','password','host','port');
// Defaults are:
//	host = localhost
//	port = 12128
//	proto = http

// Usa todos los métodos que entrega el demonio de Emerald Crypto Coin. Algunos ejemplos:
$EMD->getinfo();
$EMD->getrawtransaction('78b54347bf466dca38fdd891dc90eed90c1899b5febb4d6b546bad38fdce18be',1);
$EMD->getblock('3eb5f799c1618fcb8de5a03f388e3f2b0579f16a3c7a5f73000a474815d3371a');

// La respuesta en versión bruta es almacenada en $EMD->response y el JSON en bruto es almacenado en $EMD->raw_response

// Si el método falla por alguna razón, este retornará FALSE y podrás obtener el error en $EMD->error
echo $EMD->error;

// El codigo de estado HTTP lo puedes encontrar en $EMD->status y obtendrás un estado HTTP valido o retornará 0 si cURL no se puede conectar.
// Example:
echo $EMD->status;

*/

class EMD {
    // Configuration options
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $CACertificate;

    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;

    private $id = 0;

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     */
    function __construct($username, $password, $host = 'localhost', $port = 12128, $url = null) {
        $this->username      = $username;
        $this->password      = $password;
        $this->host          = $host;
        $this->port          = $port;
        $this->url           = $url;

        // Set some defaults
        $this->proto         = 'http';
        $this->CACertificate = null;
    }

    /**
     * @param string|null $certificate
     */
    function setSSL($certificate = null) {
        $this->proto         = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }

    function __call($method, $params) {
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;

        // If no parameters are passed, this will be an empty array
        $params = array_values($params);

        // The ID should be unique for each call
        $this->id++;

        // Build the request, it's ok that params might have any empty array
        $request = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id'     => $this->id
        ));

        // Build the cURL session
        $curl    = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => $request
        );

        // This prevents users from getting the following warning when open_basedir is set:
        // Warning: curl_setopt() [function.curl-setopt]: CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }

        if ($this->proto == 'https') {
            // If the CA Certificate was specified we change CURL to look for it
            if ($this->CACertificate != null) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            }
            else {
                // If not we need to assume the SSL cannot be verified so we set this flag to FALSE to allow the connection
                $options[CURLOPT_SSL_VERIFYPEER] = FALSE;
            }
        }

        curl_setopt_array($curl, $options);

        // Execute the request and decode to an array
        $this->raw_response = curl_exec($curl);
        $this->response     = json_decode($this->raw_response, TRUE);

        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);

        curl_close($curl);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if ($this->response['error']) {
            // If emeraldd returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        }
        elseif ($this->status != 200) {
            // If emeraldd didn't return a nice error message, we need to make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }

        if ($this->error) {
            return FALSE;
        }

        return $this->response['result'];
    }
}
