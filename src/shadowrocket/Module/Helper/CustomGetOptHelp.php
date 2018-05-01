<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       GetOptHelp.php
 * @author     ycgambo
 * @update     4/30/18 4:25 PM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Helper;


use GetOpt\Help;

class CustomGetOptHelp extends Help
{
    protected function renderUsage()
    {
        return '----------------------------------------'. PHP_EOL .
            $this->getText('usage-title') .
            $this->renderUsageCommand() .
            $this->renderUsageOptions() .
            $this->renderUsageOperands() . PHP_EOL . PHP_EOL .
            $this->renderDescription();
    }
}