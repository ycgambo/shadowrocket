<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Encryptor.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Net;

use phpseclib\Crypt\Random;

class Encryptor
{
    protected $_password;
    protected $_algorithm;

    protected $_key;
    protected $_keyLen;
    protected $_ivLen;

    protected $_encipherIv;
    protected $_encipherTail;
    protected $_isEncipherIvSent;

    protected $_decipherIv;
    protected $_decipherTail;
    protected $_isDecipherIvGot;

    public static $_methodSupported = array(
        'aes-128-cfb' => array(16, 16),
        'aes-192-cfb' => array(24, 16),
        'aes-256-cfb' => array(32, 16),
    );

    public function __construct($password, $algorithm)
    {
        $this->_password = $password;
        $this->_algorithm = strtolower($algorithm);

        $this->_key = $this->computeKey($password, $algorithm);
        $this->_keyLen = self::$_methodSupported[$algorithm][0];
        $this->_ivLen = self::$_methodSupported[$algorithm][1];

        $this->_encipherIv = Random::string($this->_ivLen);

        $this->_isEncipherIvSent = false;
        $this->_isDecipherIvGot = false;
    }

    protected function computeKey($password, $algorithm)
    {
        list($key_len, $iv_len) = self::$_methodSupported[$algorithm];

        $m = array();
        $i = 0;
        $count = $key_len + $iv_len;
        $data = $password;
        do {
            $count -= strlen($m[] = md5($data, true));
            $data = $m[$i++] . $password;
        } while ($count > 0);

        return substr(implode($m), 0, $key_len);
    }

    public function encrypt($buffer)
    {
        if ($this->_isEncipherIvSent) {
            return $this->doEncrypt($buffer);
        } else {
            $this->_isEncipherIvSent = true;
            return $this->_encipherIv . $this->doEncrypt($buffer);
        }
    }

    public function decrypt($buffer)
    {
        if ($this->_isDecipherIvGot) {
            return $this->doDecrypt($buffer);
        } else {
            $this->_decipherIv = substr($buffer, 0, $this->_ivLen);
            if ($this->_decipherIv) {
                $this->_isDecipherIvGot = true;
                return $this->doDecrypt(substr($buffer, $this->_ivLen));
            }
        }
        return '';
    }

    protected function doEncrypt($data)
    {
        if (empty($data)) {
            return '';
        }
        if ($tl = strlen($this->_encipherTail)) {
            $data = $this->_encipherTail. $data;
        }
        $b = openssl_encrypt($data, $this->_algorithm, $this->_key, 1, $this->_encipherIv);
        $result = substr($b, $tl);
        $dataLength = strlen($data);
        $mod = $dataLength % $this->_ivLen;
        if ($dataLength >= $this->_ivLen) {
            $iPos = -($mod + $this->_ivLen);
            $this->_encipherIv = substr($b, $iPos, $this->_ivLen);
        }
        $this->_encipherTail = $mod != 0 ? substr($data, -$mod) : '';
        return $result;
    }

    protected function doDecrypt($data)
    {
        if (empty($data)) {
            return '';
        }
        if ($tl = strlen($this->_decipherTail)) {
            $data = $this->_decipherTail. $data;
        }
        $b = openssl_decrypt($data, $this->_algorithm, $this->_key, 1, $this->_decipherIv);
        $result = substr($b, $tl);
        $dataLength = strlen($data);
        $mod = $dataLength % $this->_ivLen;
        if ($dataLength >= $this->_ivLen) {
            $iPos = -($mod + $this->_ivLen);
            $this->_decipherIv = substr($data, $iPos, $this->_ivLen);
        }
        $this->_decipherTail = $mod != 0 ? substr($data, -$mod) : '';
        return $result;
    }
}