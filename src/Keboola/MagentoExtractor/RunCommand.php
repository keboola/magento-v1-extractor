<?php
/**
 * @package magento-extractor
 * @copyright Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */
namespace Keboola\MagentoExtractor;

use Keboola\MagentoExtractorBundle\MagentoExtractor;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Keboola\ExtractorBundle\Config\Config;
use Keboola\ExtractorBundle\Config\JobConfig;
use Monolog\Handler\ErrorLogHandler;

class RunCommand extends Command
{
    protected function configure()
    {
        $this->setName('run');
        $this->setDescription('Runs Extractor');
        $this->addArgument('data directory', InputArgument::REQUIRED, 'Data directory');
    }

    protected function execute(InputInterface $input, OutputInterface $consoleOutput)
    {
        $dataDirectory = $input->getArgument('data directory');
        $config = $this->getConfig($dataDirectory);

        try {
            $outputPath = "$dataDirectory/out/tables";
            (new Filesystem())->mkdir([$outputPath]);

            $userConfiguration = $this->validateUserConfiguration($config);

            $logger = new \Monolog\Logger('extractor', [new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, \Monolog\Logger::WARNING)]);
            \Monolog\Registry::addLogger($logger);
            \Keboola\ExtractorBundle\Common\Logger::setLogger($logger);

            $config = new Config('magento-v1-extractor', getenv('KBC_CONFIGID'), []);
            $config->setRunId(getenv('KBC_RUNID'));
            $config->setAttributes([
                'signature_method' => 'PLAINTEXT',
                'api_url' => $userConfiguration['apiUrl'],
                'oauth' => [
                    'consumer_key' => $userConfiguration['oauthConsumerKey'],
                    'consumer_secret' => $userConfiguration['#oauthConsumerSecret'],
                    'oauth_token' => $userConfiguration['oauthToken'],
                    'oauth_token_secret' => $userConfiguration['#oauthTokenSecret'],
                ],
            ]);
            $config->setJobs(JobConfig::createFromArray($userConfiguration['jobs']));

            $extractor = new MagentoExtractor();
            $result = $extractor->run($config);
            foreach ($result as $name => $file) {
                rename($file->getPathname(), "{$outputPath}/{$name}.csv");
            }

            return 0;
        } catch (\Syrup\ComponentBundle\Exception\UserException $e) {
            $consoleOutput->writeln($e->getMessage());
            return 1;
        } catch (Exception $e) {
            $consoleOutput->writeln($e->getMessage());
            return 1;
        } catch (\Exception $e) {
            if ($consoleOutput instanceof ConsoleOutput) {
                $consoleOutput->getErrorOutput()->writeln("{$e->getMessage()}\n{$e->getTraceAsString()}");
            } else {
                $consoleOutput->writeln("{$e->getMessage()}\n{$e->getTraceAsString()}");
            }
            return 2;
        }
    }

    protected function getConfig($dataDirectory)
    {
        $configFile = "$dataDirectory/config.json";
        if (!file_exists($configFile)) {
            throw new \Exception("Config file not found at path $configFile");
        }
        $jsonDecode = new JsonDecode(true);
        return $jsonDecode->decode(file_get_contents($configFile), JsonEncoder::FORMAT);
    }

    public function validateUserConfiguration($config)
    {
        $required = ['apiUrl', 'oauthConsumerKey', '#oauthConsumerSecret', 'oauthToken', '#oauthTokenSecret', 'jobs'];
        return $this->getRequiredParameters($config, $required, 'parameters');
    }

    protected function getRequiredParameters($config, $required, $field)
    {
        $result = [];
        foreach ($required as $input) {
            if (!isset($config[$field][$input])) {
                throw new Exception("$input is missing from $field");
            }
            $result[$input] = $config[$field][$input];
        }
        return $result;
    }
}
