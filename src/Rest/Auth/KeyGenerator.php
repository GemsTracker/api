<?php

namespace Gems\Rest\Auth;

use Defuse\Crypto\Key;

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
        // For windows (xampp) uncomment following line:
        //$config['config'] = 'E:\xampp71\php\extras\openssl\openssl.cnf';
        $resource = openssl_pkey_new($config);
        if ($resource === false) {
            die('If you are on windows, provide a valid location for the openssl.cnf. See ' . __FILE__);
        }

        openssl_pkey_export($resource, $privKey, null, $config);
        $this->privateKey = $privKey;

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
    
    public function generateApplicationKey()
    {
        $key = Key::createNewRandomKey();
        return $key->saveToAsciiSafeString();
    }
}