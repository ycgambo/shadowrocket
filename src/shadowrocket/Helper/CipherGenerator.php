<?php

namespace ShadowRocket\Helper;

use phpseclib\Crypt\Random;
use phpseclib\Crypt\AES;

class CipherGenerator
{
    public static function generate($key, $algorithm)
    {
        switch ($algorithm) {
            case 'aes-128-cfb':
            case 'aes-192-cfb':
            case 'aes-256-cfb':
            case 'aes-128-ctr':
            case 'aes-192-ctr':
            case 'aes-256-ctr':
                return self::AES_Cipher($key, $algorithm);
                break;
            default:
                return self::AES_Cipher($key, 'aes-256-cfb');
        }
    }

    private static function AES_Cipher($key, $algorithm)
    {
        $mode = array(
            'cfb' => AES::MODE_CFB,
            'ctr' => AES::MODE_CTR,
        );

        $cipher = new AES($mode[substr($algorithm, 8, 3)]);
        $cipher->setKeyLength(intval(substr($algorithm, 4, 3)));
        $cipher->setKey($key);
        $cipher->setIV(Random::string($cipher->getBlockLength() >> 3));

        return new AES();
    }
}