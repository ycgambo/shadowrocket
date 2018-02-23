<?php

namespace ShadowRocket;

class Encipher
{
    protected $_algorithm;
    protected $_key;
    protected $_iv;
    protected $_tail;
    protected $_ivLength;
    public function __construct($algorithm, $key, $iv)
    {
        $this->_algorithm = $algorithm;
        $this->_key = $key;
        $this->_iv = $iv;
        $this->_ivLength = openssl_cipher_iv_length($algorithm);
    }
    public function update($data)
    {
        if (strlen($data) == 0)
            return '';
        $tl = strlen($this->_tail);
        if ($tl)
            $data = $this->_tail . $data;
        $b = openssl_encrypt($data, $this->_algorithm, $this->_key, OPENSSL_RAW_DATA, $this->_iv);
        $result = substr($b, $tl);
        $dataLength = strlen($data);
        $mod = $dataLength%$this->_ivLength;
        if ($dataLength >= $this->_ivLength) {
            $iPos = -($mod + $this->_ivLength);
            $this->_iv = substr($b, $iPos, $this->_ivLength);
        }
        $this->_tail = $mod!=0 ? substr($data, -$mod):'';
        return $result;
    }
}