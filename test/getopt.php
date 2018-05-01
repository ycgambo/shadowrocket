<?php
require_once __DIR__ . '/../vendor/autoload.php';


class MyHelp extends \GetOpt\Help {
    protected function renderUsage()
    {
        return $this->getText('usage-title') .
            $this->renderUsageCommand() .
            $this->renderUsageOptions() .
            $this->renderUsageOperands() . PHP_EOL . PHP_EOL .
            $this->renderDescription();
    }
}
test('');
test('add');
test('add --module');
test('add --module server --name name');



use GetOpt\GetOpt;

function test($buffer) {

    echo PHP_EOL . $buffer . PHP_EOL;

    $getopt = new GetOpt(array(
        array(null, 'cmd', GetOpt::REQUIRED_ARGUMENT),
        array('m', 'module', GetOpt::OPTIONAL_ARGUMENT),
        array('n', 'name', GetOpt::OPTIONAL_ARGUMENT),
    ));
    $getopt->addCommands(array(
        \GetOpt\Command::create('add', 'addddd'),
        \GetOpt\Command::create('del', 'addddd'),
    ));
    $getopt->setHelp(new \MyHelp());
    $getopt->process($buffer);

    foreach ($getopt as $key => $value) {
        echo sprintf('%s: %s', $key, $value) . PHP_EOL;
    }

    echo $getopt->getHelpText();
}

