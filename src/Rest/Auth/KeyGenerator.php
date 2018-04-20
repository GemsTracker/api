<?php
/**
 * Created by PhpStorm.
 * User: Jasper
 * Date: 19/04/2018
 * Time: 15:18
 */

namespace Gems\Rest\Auth;


class KeyGenerator
{
    protected $bits = 4096;

    protected $fileMode = 0600;

    protected $privateKey;

    protected $publicKey;

    protected $privateKeyLocation;

    protected $publicKeyLocation;

    public function __construct($config)
    {
        if (isset(
            $config['certificates'],
            $config['certificates']['private'],
            $config['certificates']['public']
        )) {
            $this->privateKeyLocation = $config['certificates']['private'];
            $this->publicKeyLocation = $config['certificates']['public'];
        }
    }

    public function generateKeys()
    {
        $config = [
            'private_key_bits' => $this->bits,
        ];
        $resource = openssl_pkey_new($config);

        openssl_pkey_export($resource, $this->privateKey);

        $publicKey = openssl_pkey_get_details($resource);
        $this->publicKey = $publicKey["key"];

        try {
            \MUtil_File::ensureDir(dirname($this->privateKeyLocation));
            \MUtil_File::ensureDir(dirname($this->publicKeyLocation));
        } catch(\Zend_Exception $e) {
            echo $e->getMessage();
            return false;
        }

        file_put_contents($this->privateKeyLocation, $this->privateKey);
        chmod($this->privateKeyLocation, $this->fileMode);

        file_put_contents($this->publicKeyLocation, $this->publicKey);
        chmod($this->publicKeyLocation, $this->fileMode);

        return true;
    }
}