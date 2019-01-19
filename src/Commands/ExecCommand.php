<?php

namespace MVF\Servicer\Commands;

use MVF\Servicer\QueueInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Functional\each;

class ExecCommand extends Command
{
    const ACTION = 'action';
    const HEADERS = 'header';
    const BODY = 'body';

    /**
     * @var QueueInterface[]
     */
    private $queues;

    /**
     * DaemonCommand constructor.
     *
     * @param QueueInterface ...$queues
     */
    public function __construct(QueueInterface ...$queues)
    {
        $this->queues = $queues;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('exec');
        $this->setDescription('Run specified action');
        $this->setHelp('Not implemented');
        $this->addArgument(self::ACTION, InputArgument::REQUIRED, 'The action to be executed');

        $this->addOption(
            self::HEADERS,
            '-H',
            (InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY),
            'The list of headers',
            []
        );

        $this->addOption(
            self::BODY,
            '-B',
            InputOption::VALUE_OPTIONAL,
            'The payload of the action',
            '{}'
        );
    }

    /**
     * Defines the behaviour of the command.
     *
     * @param InputInterface  $input  Defines inputs
     * @param OutputInterface $output Defines outputs
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $headers = $this->getHeaders($input);
        $body = \GuzzleHttp\json_decode($input->getOption(self::BODY));
        each($this->queues, $this->triggerActions($headers, $body));
    }

    private function triggerActions(\stdClass $headers, \stdClass $body): callable
    {
        return function (QueueInterface $queue) use ($headers, $body) {
            $queue->getEvents()->triggerAction($headers, $body);
        };
    }

    private function getHeaders(InputInterface $input): \stdClass
    {
        $headers = (object)[];
        foreach ($input->getOption(self::HEADERS) as $header) {
            $pattern = '/^(\w*)=(.*)$/';
            if (preg_match($pattern, $header, $matches) !== false) {
                [$full, $key, $value] = $matches;
                $field = strtolower($key);
                $headers->$field = $value;
            }
        }

        $headers->event = $input->getArgument(self::ACTION);

        return $headers;
    }
}