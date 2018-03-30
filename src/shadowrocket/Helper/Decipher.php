<?php
namespace ShadowRocket\Helper;

class Decipher extends Encipher {
    public function update($data) {
        if (strlen($data) == 0)
            return '';
        $tl = strlen($this->_tail);
        if ($tl)
            $data = $this->_tail . $data;
        $b = openssl_decrypt($data, $this->_algorithm, $this->_key, OPENSSL_RAW_DATA, $this->_iv);
        $result = substr($b, $tl);
        $dataLength = strlen($data);
        $mod = $dataLength % $this->_ivLength;
        if ($dataLength >= $this->_ivLength) {
            $iPos = -($mod + $this->_ivLength);
            $this->_iv = substr($data, $iPos, $this->_ivLength);
        }
        $this->_tail = $mod != 0 ? substr($data, -$mod) : '';
        return $result;
    }
}