<?php
/**
 * @package magento-extractor
 * @copyright Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

require __DIR__ . '/bootstrap.php';

use Symfony\Component\Console\Application;
use Keboola\MagentoExtractor\RunCommand;
use Symfony\Component\Console\Output\ConsoleOutput;

$application = new Application;
$application->add(new RunCommand);
$application->run(null, new ConsoleOutput());
