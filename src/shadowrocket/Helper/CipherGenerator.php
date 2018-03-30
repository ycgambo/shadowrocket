<?php

namespace ShadowRocket\Helper;

class CipherGenerator
{
    public static function generate($key, $algorithm)
    {
        return new Encryptor($key, $algorithm);
    }
}